<?php

namespace App\Services;

use App\Models\Blog\VideoCharacter;
use App\Models\Blog\VideoReel;
use App\Models\Blog\VideoScenario;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Імпорт сценаріїв з JSON (формат описано в BLOG_VIDEO.md, промпт для ChatGPT — resources/prompts/blog-video-chatgpt.md).
 *
 * Персонажі шукаються за ім'ям без урахування регістру; нових створюємо, наявних не чіпаємо,
 * якщо не попросили оновити описи. Фото персонажів і стартові кадри додаються вже в адмінці.
 */
class VideoScenarioImporter
{
    public const PROMPT_TEMPLATE = 'prompts/blog-video-chatgpt.md';

    public const ASPECT_RATIOS = ['9:16', '16:9'];

    public const RESOLUTIONS = ['360p', '720p', '1080p', '4k'];

    /**
     * Розбирає відповідь ChatGPT: чистий JSON або JSON у блоці ```json ... ```.
     */
    public function parse(string $content): array
    {
        $content = trim(preg_replace('/^\xEF\xBB\xBF/', '', $content));
        if (preg_match('/```(?:json)?\s*(.+?)```/s', $content, $match)) {
            $content = trim($match[1]);
        }

        $data = json_decode($content, true);
        if (! is_array($data)) {
            throw new RuntimeException('Файл не є коректним JSON: ' . json_last_error_msg());
        }

        // Один сценарій без обгортки теж приймаємо.
        if (! isset($data['scenarios']) && isset($data['lines'])) {
            $data = ['characters' => Arr::pull($data, 'characters', []), 'scenarios' => [$data]];
        }

        return $data;
    }

    /**
     * @return array{scenarios: Collection<int, VideoScenario>, created_characters: Collection<int, VideoCharacter>, reel: ?VideoReel}
     */
    public function import(array $data, bool $updateCharacters = false): array
    {
        $this->validate($data);

        return DB::transaction(function () use ($data, $updateCharacters) {
            $characters = VideoCharacter::query()->get()->keyBy(fn (VideoCharacter $character) => $this->key($character->name));
            $created = collect();

            $resolve = function (string $name, array $fields = []) use ($characters, $created, $updateCharacters): VideoCharacter {
                $character = $characters->get($this->key($name));
                $fields = array_filter(Arr::only($fields, ['description', 'voice']), 'filled');

                if (! $character) {
                    $character = VideoCharacter::query()->create(['name' => trim($name)] + $fields);
                    $characters->put($this->key($name), $character);
                    $created->push($character);
                } elseif ($updateCharacters && $fields && ! $created->contains($character)) {
                    $character->update($fields);
                }

                return $character;
            };

            foreach ($data['characters'] ?? [] as $character) {
                $resolve($character['name'], $character);
            }

            $scenarios = collect($data['scenarios'])->map(fn (array $item) => VideoScenario::query()->create([
                'title' => $item['title'],
                'setting' => $item['setting'] ?? null,
                'style' => $item['style'] ?? null,
                'extra_instructions' => $item['extra_instructions'] ?? null,
                'language' => $item['language'] ?? $data['language'] ?? 'українська',
                'aspect_ratio' => $item['aspect_ratio'] ?? $data['aspect_ratio'] ?? '9:16',
                'resolution' => $item['resolution'] ?? $data['resolution'] ?? '720p',
                'clip_seconds' => $item['duration'] ?? $data['duration'] ?? VideoScenario::DEFAULT_CLIP_SECONDS,
                'lines' => collect($item['lines'])->map(fn (array $line) => [
                    'character_id' => $resolve($line['character'])->id,
                    'direction' => $line['direction'] ?? null,
                    'text' => $line['text'] ?? null,
                ])->all(),
            ]));

            $reel = null;
            if (filled($data['reel_title'] ?? null) && $scenarios->count() > 1) {
                $reel = VideoReel::query()->create([
                    'title' => $data['reel_title'],
                    'items' => $scenarios->map(fn (VideoScenario $scenario) => ['scenario_id' => $scenario->id])->all(),
                ]);
            }

            return ['scenarios' => $scenarios, 'created_characters' => $created, 'reel' => $reel];
        });
    }

    /**
     * Шаблон промпта для ChatGPT зі списком персонажів, які вже є в адмінці.
     */
    public function chatGptPrompt(): string
    {
        $characters = VideoCharacter::query()->orderBy('name')->get()
            ->map(fn (VideoCharacter $character) => collect([
                "- {$character->name}",
                $character->description ? '  Зовнішність: ' . Str::squish($character->description) : null,
                $character->voice ? '  Голос: ' . Str::squish($character->voice) : null,
            ])->filter()->implode("\n"))
            ->implode("\n");

        return str_replace(
            ['{{characters}}', '{{recent}}'],
            [
                $characters ?: '(персонажів ще немає — придумай їх і опиши в "characters")',
                $this->recentScenarios() ?: '(роликів ще немає)',
            ],
            file_get_contents(resource_path(self::PROMPT_TEMPLATE)),
        );
    }

    /**
     * Останні сценарії з репліками, щоб ChatGPT не повторював сюжети й панчлайни.
     */
    private function recentScenarios(int $limit = 12): string
    {
        $names = VideoCharacter::query()->pluck('name', 'id');

        return VideoScenario::query()->latest('id')->limit($limit)->get()
            ->map(function (VideoScenario $scenario) use ($names) {
                $lines = collect($scenario->lines ?? [])
                    ->filter(fn (array $line) => filled($line['text'] ?? null))
                    ->map(fn (array $line) => ($names[$line['character_id'] ?? null] ?? '?') . ': ' . Str::squish($line['text']))
                    ->implode(' / ');
                $title = preg_replace('/^\d+\s*[—-]\s*/u', '', $scenario->title);

                return "- {$title}" . ($lines ? " — {$lines}" : '');
            })
            ->implode("\n");
    }

    private function validate(array $data): void
    {
        $validator = Validator::make($data, [
            'language' => ['nullable', 'string', 'max:50'],
            'aspect_ratio' => ['nullable', 'in:' . implode(',', self::ASPECT_RATIOS)],
            'resolution' => ['nullable', 'in:' . implode(',', self::RESOLUTIONS)],
            'duration' => $this->durationRule(),
            'reel_title' => ['nullable', 'string', 'max:255'],
            'characters' => ['nullable', 'array'],
            'characters.*.name' => ['required', 'string', 'max:255'],
            'characters.*.description' => ['nullable', 'string'],
            'characters.*.voice' => ['nullable', 'string'],
            'scenarios' => ['required', 'array', 'min:1'],
            'scenarios.*.title' => ['required', 'string', 'max:255'],
            'scenarios.*.setting' => ['nullable', 'string'],
            'scenarios.*.style' => ['nullable', 'string'],
            'scenarios.*.extra_instructions' => ['nullable', 'string'],
            'scenarios.*.language' => ['nullable', 'string', 'max:50'],
            'scenarios.*.aspect_ratio' => ['nullable', 'in:' . implode(',', self::ASPECT_RATIOS)],
            'scenarios.*.resolution' => ['nullable', 'in:' . implode(',', self::RESOLUTIONS)],
            'scenarios.*.duration' => $this->durationRule(),
            'scenarios.*.lines' => ['required', 'array', 'min:1'],
            'scenarios.*.lines.*.character' => ['required', 'string', 'max:255'],
            'scenarios.*.lines.*.direction' => ['nullable', 'string', 'max:255'],
            'scenarios.*.lines.*.text' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            throw new RuntimeException("Файл не пройшов перевірку:\n" . implode("\n", $validator->errors()->all()));
        }
    }

    private function durationRule(): array
    {
        return ['nullable', 'integer', 'between:' . VideoScenario::MIN_CLIP_SECONDS . ',' . VideoScenario::MAX_CLIP_SECONDS];
    }

    private function key(string $name): string
    {
        return Str::lower(trim($name));
    }
}
