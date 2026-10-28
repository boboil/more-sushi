<?php

namespace App\Services;

use App\Models\Blog\VideoCharacter;
use App\Models\Blog\VideoScenario;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use RuntimeException;

/**
 * Генерація відео через Gemini Omni Flash (Interactions API, фоновий режим).
 *
 * start() ставить задачу і зберігає interaction_id, extend() продовжує готове відео
 * (previous_interaction_id), refresh() опитує задачу та завантажує mp4 на диск public_root.
 */
class GeminiVideoService
{
    public function __construct(private readonly VideoTools $tools)
    {
    }

    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta';

    public const VIDEO_DIRECTORY = 'blog-video/videos';

    /** Довша сторона референсу, щоб запит не роздувався base64-картинками. */
    private const REFERENCE_MAX_SIDE = 1536;

    /**
     * @return array{prompt: string, images: array<int, string>}
     */
    public function buildPrompt(VideoScenario $scenario): array
    {
        $images = [];
        $castLines = [];

        // Стартовий кадр уже містить персонажів, тож їхні фото не дублюємо.
        if ($scenario->start_frame) {
            $images[] = $scenario->start_frame;
        }

        foreach ($scenario->characters() as $character) {
            $refs = [];
            foreach ($scenario->start_frame ? [] : ($character->reference_images ?? []) as $path) {
                $refs[] = '<IMAGE_REF_' . count($images) . '>';
                $images[] = $path;
            }

            $line = "- {$character->name}";
            if ($refs) {
                $line .= ' — looks exactly like the person in ' . implode(', ', $refs);
            }
            if ($character->description) {
                $line .= '. Appearance: ' . rtrim($character->description, '. ');
            }
            if ($character->voice) {
                $line .= '. Voice: ' . rtrim($character->voice, '. ');
            }
            $castLines[] = $line;
        }

        $extraRefs = [];
        foreach ($scenario->extra_references ?? [] as $path) {
            $extraRefs[] = '<IMAGE_REF_' . count($images) . '>';
            $images[] = $path;
        }

        $sections = ['A short video for a sushi restaurant blog.'];
        if ($scenario->start_frame) {
            $sections[] = 'Use <IMAGE_REF_0> as the exact first frame and visual anchor: keep the characters, '
                . 'their faces, clothing, kitchen layout and lighting exactly as in it. Do not mirror it.';
        }
        if ($scenario->setting) {
            $sections[] = "Scene: {$scenario->setting}";
        }
        if ($scenario->style) {
            $sections[] = "Visual style: {$scenario->style}";
        }
        if ($castLines) {
            $sections[] = "Characters:\n" . implode("\n", $castLines);
        }
        if ($extraRefs) {
            $sections[] = 'Use ' . implode(', ', $extraRefs) . ' as reference for the location, props and food.';
        }
        if ($dialogue = $this->dialogueSection($scenario->lines ?? [], $scenario->language)) {
            $sections[] = $dialogue;
        }
        if ($scenario->extra_instructions) {
            $sections[] = $scenario->extra_instructions;
        }

        return ['prompt' => implode("\n\n", $sections), 'images' => $images];
    }

    public function start(VideoScenario $scenario): void
    {
        ['prompt' => $prompt, 'images' => $images] = $this->buildPrompt($scenario);

        $input = array_map(fn (string $path) => $this->imagePart($path), $images);
        $input[] = ['type' => 'text', 'text' => $prompt];

        $response = $this->client()->timeout(120)->post(self::BASE_URL . '/interactions', [
            'model' => config('services.gemini.video_model'),
            'background' => true,
            'input' => $input,
            'response_format' => [
                'type' => 'video',
                'aspect_ratio' => $scenario->aspect_ratio,
                'resolution' => $scenario->resolution,
                'delivery' => 'uri',
            ],
        ]);

        $id = $response->json('id');
        if ($response->failed() || ! $id) {
            throw new RuntimeException($this->errorMessage($response->json(), $response->body()));
        }

        $scenario->forceFill([
            'status' => VideoScenario::STATUS_PROCESSING,
            'interaction_id' => $id,
            'previous_interaction_id' => null,
            'extending' => false,
            'extensions' => [],
            'file_id' => null,
            'last_prompt' => $prompt,
            'error' => null,
        ])->save();
    }

    /**
     * Промпт продовження: ті самі персонажі й голоси, нові репліки.
     */
    public function buildExtensionPrompt(VideoScenario $scenario, array $lines, ?string $instructions): string
    {
        $sections = ['Extend this video with a seamless continuation of about 8 seconds. Keep exactly the same '
            . 'characters, faces, clothing, voices, location, lighting and camera style. No cuts, no subtitles, no music.'];

        $voices = $this->charactersFor($lines)
            ->filter(fn (VideoCharacter $character) => filled($character->voice))
            ->map(fn (VideoCharacter $character) => "- {$character->name}: " . rtrim($character->voice, '. '));
        if ($voices->isNotEmpty()) {
            $sections[] = "Voices (same as before):\n" . $voices->implode("\n");
        }
        if ($dialogue = $this->dialogueSection($lines, $scenario->language)) {
            $sections[] = $dialogue;
        }
        if (filled($instructions)) {
            $sections[] = $instructions;
        }

        return implode("\n\n", $sections);
    }

    public function extend(VideoScenario $scenario, array $lines, ?string $instructions = null): void
    {
        if (! $scenario->canBeExtended()) {
            throw new RuntimeException('Продовжити можна лише готове відео коротше ' . VideoScenario::MAX_DURATION . ' секунд');
        }

        $prompt = $this->buildExtensionPrompt($scenario, $lines, $instructions);

        $response = $this->client()->timeout(120)->post(self::BASE_URL . '/interactions', [
            'model' => config('services.gemini.video_model'),
            'background' => true,
            'previous_interaction_id' => $scenario->interaction_id,
            'input' => [['type' => 'text', 'text' => $prompt]],
            'response_format' => [
                'type' => 'video',
                'aspect_ratio' => $scenario->aspect_ratio,
                'resolution' => $scenario->resolution,
                'delivery' => 'uri',
            ],
        ]);

        $id = $response->json('id');
        if ($response->failed() || ! $id) {
            throw new RuntimeException($this->errorMessage($response->json(), $response->body()));
        }

        $extensions = $scenario->extensions ?? [];
        $extensions[] = [
            'lines' => array_values($lines),
            'instructions' => $instructions,
            'prompt' => $prompt,
            'status' => VideoScenario::STATUS_PROCESSING,
            'duration' => null,
        ];

        $scenario->forceFill([
            'status' => VideoScenario::STATUS_PROCESSING,
            'extending' => true,
            'previous_interaction_id' => $scenario->interaction_id,
            'interaction_id' => $id,
            'extensions' => $extensions,
            'file_id' => null,
            'error' => null,
        ])->save();
    }

    /**
     * Перевіряє задачу; повертає true, якщо статус сценарію змінився.
     */
    public function refresh(VideoScenario $scenario): bool
    {
        if (! $scenario->isProcessing() || ! $scenario->interaction_id) {
            return false;
        }

        if (! $scenario->file_id) {
            $response = $this->client()->get(self::BASE_URL . '/interactions/' . $scenario->interaction_id);
            if ($response->failed()) {
                return $this->fail($scenario, $this->errorMessage($response->json(), $response->body()));
            }

            $status = $response->json('status');
            if (in_array($status, ['in_progress', 'queued', 'pending', null], true)) {
                return false;
            }
            $this->recordUsage($scenario, $response->json('usage'));
            if ($status !== 'completed') {
                return $this->fail($scenario, $this->errorMessage($response->json(), "Статус задачі: {$status}"));
            }

            $video = $this->findVideo($response->json() ?? []);
            if (! $video) {
                return $this->fail($scenario, 'Модель не повернула відео. ' . $this->modelText($response->json() ?? []));
            }

            if (! empty($video['data'])) {
                return $this->saveVideo($scenario, base64_decode($video['data']));
            }

            // uri буває як ресурсом файлу, так і готовим посиланням ".../files/{id}:download?alt=media".
            $fileId = Str::before(Str::before(Str::afterLast($video['uri'], 'files/'), ':'), '?');
            $scenario->forceFill(['file_id' => $fileId])->save();
        }

        return $this->refreshFile($scenario);
    }

    private function refreshFile(VideoScenario $scenario): bool
    {
        $file = $this->client()->get(self::BASE_URL . '/files/' . $scenario->file_id);
        if ($file->failed()) {
            return $this->fail($scenario, $this->errorMessage($file->json(), $file->body()));
        }

        $state = $file->json('state');
        if ($state === 'FAILED') {
            return $this->fail($scenario, $this->errorMessage($file->json(), 'Генерація відео не вдалась (FAILED)'));
        }
        if ($state !== 'ACTIVE') {
            return false;
        }

        $download = $this->client()->timeout(300)
            ->get(self::BASE_URL . '/files/' . $scenario->file_id . ':download', ['alt' => 'media']);
        if ($download->failed()) {
            return $this->fail($scenario, 'Не вдалося завантажити відео: ' . $download->status());
        }

        return $this->saveVideo($scenario, $download->body());
    }

    private function saveVideo(VideoScenario $scenario, string $bytes): bool
    {
        $disk = Storage::disk('public_root');
        $path = self::VIDEO_DIRECTORY . '/' . $scenario->id . '-' . now()->format('Ymd-His') . '.mp4';
        $disk->put($path, $bytes);
        $duration = $this->tools->duration($disk->path($path));

        if ($scenario->extending) {
            [$path, $duration, $added] = $this->joinExtension($scenario, $path, $duration);
            $extensions = $scenario->extensions ?? [];
            $last = array_key_last($extensions);
            if ($last !== null) {
                $extensions[$last]['status'] = VideoScenario::STATUS_DONE;
                $extensions[$last]['duration'] = $added;
            }
            $scenario->extensions = $extensions;
        }

        $scenario->forceFill([
            'status' => VideoScenario::STATUS_DONE,
            'extending' => false,
            'previous_interaction_id' => null,
            'video_path' => $path,
            'duration' => $duration,
            'error' => null,
            'generated_at' => now(),
        ])->save();

        return true;
    }

    /**
     * Gemini може повернути або все відео разом із продовженням, або лише новий шматок.
     * Якщо новий файл помітно не довший за попередній — це шматок, і його треба приклеїти.
     *
     * @return array{0: string, 1: ?float, 2: ?float} шлях, загальна тривалість, скільки додано
     */
    private function joinExtension(VideoScenario $scenario, string $path, ?float $duration): array
    {
        $disk = Storage::disk('public_root');
        $base = $scenario->video_path;
        $baseDuration = $base && $disk->exists($base) ? $this->tools->duration($disk->path($base)) : null;

        if ($duration === null || $baseDuration === null) {
            return [$path, $duration, null];
        }
        if ($duration > $baseDuration + 1) {
            return [$path, $duration, round($duration - $baseDuration, 2)];
        }

        $joined = self::VIDEO_DIRECTORY . '/' . $scenario->id . '-' . now()->format('Ymd-His') . '-joined.mp4';
        $this->tools->concat([$disk->path($base), $disk->path($path)], $disk->path($joined));
        $disk->delete($path);

        return [$joined, $this->tools->duration($disk->path($joined)), $duration];
    }

    private function fail(VideoScenario $scenario, string $error): bool
    {
        $error = Str::limit($error, 2000);

        // Невдале продовження не псує вже готове відео: повертаємось до нього.
        if ($scenario->extending) {
            $extensions = $scenario->extensions ?? [];
            $last = array_key_last($extensions);
            if ($last !== null) {
                $extensions[$last]['status'] = VideoScenario::STATUS_FAILED;
            }

            $scenario->forceFill([
                'status' => VideoScenario::STATUS_DONE,
                'extending' => false,
                'interaction_id' => $scenario->previous_interaction_id,
                'previous_interaction_id' => null,
                'extensions' => $extensions,
                'error' => 'Продовження не вдалось: ' . $error,
            ])->save();

            return true;
        }

        $scenario->forceFill(['status' => VideoScenario::STATUS_FAILED, 'error' => $error])->save();

        return true;
    }

    /**
     * Вартість задачі в USD за usage з відповіді: вхід, відео, інший вивід і «роздуми» моделі.
     */
    public function cost(?array $usage): ?float
    {
        if (! $usage) {
            return null;
        }

        $prices = config('services.gemini.prices');
        $video = collect($usage['output_tokens_by_modality'] ?? [])->where('modality', 'video')->sum('tokens');
        $otherOutput = max(0, ($usage['total_output_tokens'] ?? 0) - $video) + ($usage['total_thought_tokens'] ?? 0);

        return round((
            ($usage['total_input_tokens'] ?? 0) * $prices['input']
            + $video * $prices['output_video']
            + $otherOutput * $prices['output_text']
        ) / 1_000_000, 4);
    }

    private function recordUsage(VideoScenario $scenario, ?array $usage): void
    {
        $cost = $this->cost($usage);
        if ($cost === null) {
            return;
        }

        if ($scenario->extending) {
            $extensions = $scenario->extensions ?? [];
            $last = array_key_last($extensions);
            if ($last !== null) {
                $extensions[$last]['cost'] = $cost;
                $scenario->extensions = $extensions;
            }
        }

        $scenario->forceFill([
            'last_usage' => $usage,
            'last_cost' => $cost,
            'cost' => round(($scenario->cost ?? 0) + $cost, 4),
        ])->save();
    }

    /**
     * Репліки у форматі «Ім'я (ремарка): "текст"» і хто саме говорить.
     */
    private function dialogueSection(array $lines, string $language): ?string
    {
        $names = $this->charactersFor($lines)->pluck('name', 'id');
        $lines = collect($lines)->filter(fn (array $line) => filled($line['text'] ?? null) || filled($line['direction'] ?? null));
        if ($lines->isEmpty()) {
            return null;
        }

        $dialogue = $lines->map(function (array $line) use ($names) {
            $speaker = $names->get($line['character_id'] ?? null, 'Narrator');
            $direction = filled($line['direction'] ?? null) ? " ({$line['direction']})" : '';
            $text = filled($line['text'] ?? null) ? ': "' . trim($line['text']) . '"' : ' — silent, no speech';

            return "{$speaker}{$direction}{$text}";
        })->implode("\n");

        $section = "Dialogue, spoken in {$this->languageName($language)} exactly as written, "
            . "in this order, with natural lip sync:\n{$dialogue}";

        $speakers = $lines->filter(fn (array $line) => filled($line['text'] ?? null))
            ->map(fn (array $line) => $names->get($line['character_id'] ?? null))->filter()->unique();
        if ($speakers->isNotEmpty()) {
            $section .= "\nOnly {$speakers->implode(' and ')} speak" . ($speakers->count() === 1 ? 's' : '')
                . '; everyone else stays silent.';
        }

        return $section;
    }

    private function charactersFor(array $lines): Collection
    {
        $ids = collect($lines)->pluck('character_id')->filter()->unique()->values();
        $characters = VideoCharacter::query()->whereIn('id', $ids)->get()->keyBy('id');

        return $ids->map(fn ($id) => $characters->get($id))->filter()->values();
    }

    private function imagePart(string $path): array
    {
        $absolute = Storage::disk('public_root')->path($path);
        if (! is_file($absolute)) {
            throw new RuntimeException("Референс не знайдено: {$path}");
        }

        $image = Image::make($absolute)->orientate()->resize(
            self::REFERENCE_MAX_SIDE,
            self::REFERENCE_MAX_SIDE,
            function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            },
        );

        return [
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'data' => base64_encode((string) $image->encode('jpg', 88)),
        ];
    }

    /**
     * Шукає у відповіді перший блок з відео (data або uri), не прив'язуючись до точної вкладеності steps.
     */
    private function findVideo(array $node): ?array
    {
        if (str_starts_with((string) ($node['mime_type'] ?? ''), 'video/') && (! empty($node['uri']) || ! empty($node['data']))) {
            return $node;
        }

        foreach ($node as $child) {
            if (is_array($child) && ($found = $this->findVideo($child))) {
                return $found;
            }
        }

        return null;
    }

    private function modelText(array $json): string
    {
        $texts = [];
        array_walk_recursive($json, function ($value, $key) use (&$texts) {
            if ($key === 'text' && is_string($value)) {
                $texts[] = $value;
            }
        });

        return Str::limit(implode(' ', array_slice($texts, -2)), 500);
    }

    private function errorMessage(?array $json, string $fallback): string
    {
        return (string) (data_get($json, 'error.message') ?: Str::limit($fallback, 500));
    }

    private function languageName(string $language): string
    {
        return match (mb_strtolower(trim($language))) {
            'українська', 'ukrainian' => 'Ukrainian',
            'англійська', 'english' => 'English',
            'польська', 'polish' => 'Polish',
            default => $language,
        };
    }

    private function client(): PendingRequest
    {
        $key = config('services.gemini.key');
        if (! $key) {
            throw new RuntimeException('GEMINI_API_KEY не задано в .env');
        }

        return Http::withHeaders(['x-goog-api-key' => $key])->acceptJson()->timeout(60);
    }
}
