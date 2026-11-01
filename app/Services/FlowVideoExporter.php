<?php

namespace App\Services;

use App\Models\Blog\VideoScenario;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Ручна генерація в Google Flow: архів з промптом і референсами для Flow,
 * а потім прив'язка скачаного з Flow mp4 до сценарію.
 */
class FlowVideoExporter
{
    public function __construct(
        private readonly GeminiVideoService $gemini,
        private readonly VideoTools $tools,
    ) {
    }

    /**
     * Той самий промпт, що йде в API, але замість <IMAGE_REF_n> — номери картинок з архіву.
     *
     * @return array{prompt: string, images: array<int, array{path: string, file: string, role: string}>}
     */
    public function prepare(VideoScenario $scenario): array
    {
        ['prompt' => $prompt, 'images' => $paths] = $this->gemini->buildPrompt($scenario);

        $roles = [];
        foreach ($scenario->characters() as $character) {
            foreach ($character->reference_images ?? [] as $path) {
                $roles[$path] ??= ["фото персонажа {$character->name}", Str::slug($character->name) ?: 'character'];
            }
        }
        foreach ($scenario->extra_references ?? [] as $path) {
            $roles[$path] ??= ['референс сцени (локація, страви, реквізит)', 'scene'];
        }
        if ($scenario->start_frame) {
            $roles[$scenario->start_frame] = ['стартовий кадр', 'start-frame'];
        }

        $images = [];
        foreach ($paths as $index => $path) {
            $number = $index + 1;
            [$role, $slug] = $roles[$path] ?? ['референс', 'reference'];
            $images[] = [
                'path' => $path,
                'file' => sprintf('%d-%s.%s', $number, $slug, pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg'),
                'role' => $role,
            ];
            $prompt = str_replace("<IMAGE_REF_{$index}>", "image {$number}", $prompt);
        }

        return ['prompt' => $prompt, 'images' => $images];
    }

    /**
     * Збирає zip у тимчасовий файл і повертає шлях до нього.
     */
    public function export(VideoScenario $scenario): string
    {
        ['prompt' => $prompt, 'images' => $images] = $this->prepare($scenario);
        $disk = Storage::disk('public_root');

        $path = tempnam(sys_get_temp_dir(), 'flow') . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Не вдалося створити архів');
        }

        $zip->addFromString('prompt.txt', $prompt);
        $zip->addFromString('README.txt', $this->readme($scenario, $images));
        foreach ($images as $image) {
            if (! $disk->exists($image['path'])) {
                throw new RuntimeException("Не знайдено файл референсу: {$image['path']}");
            }
            $zip->addFromString($image['file'], $disk->get($image['path']));
        }
        $zip->close();

        return $path;
    }

    /**
     * Прив'язує до сценарію mp4, скачаний з Flow (уже збережений на диску public_root).
     */
    public function attach(VideoScenario $scenario, string $path): void
    {
        $disk = Storage::disk('public_root');
        if (! $disk->exists($path)) {
            throw new RuntimeException('Файл відео не завантажився');
        }

        $scenario->forceFill([
            'status' => VideoScenario::STATUS_DONE,
            // Без interaction_id продовження через API неможливе — продовжують у самому Flow.
            'interaction_id' => null,
            'previous_interaction_id' => null,
            'extending' => false,
            'extensions' => [],
            'file_id' => null,
            'video_path' => $path,
            'duration' => $this->tools->duration($disk->path($path)),
            'last_prompt' => $this->prepare($scenario)['prompt'],
            'last_cost' => null,
            'last_usage' => null,
            'error' => null,
            'generated_at' => now(),
        ])->save();
    }

    private function readme(VideoScenario $scenario, array $images): string
    {
        $lines = [
            "Сценарій: {$scenario->title}",
            "Формат: {$scenario->aspect_ratio}, якість: {$scenario->resolution}, модель: Omni Flash",
            '',
            'Як згенерувати в Google Flow (labs.google/flow):',
            '1. Новий проєкт, модель Omni Flash, формат ' . $scenario->aspect_ratio . '.',
        ];

        if ($images) {
            $lines[] = $scenario->start_frame
                ? '2. Картинку 1 (стартовий кадр) поставте першим кадром (Frames to Video), решту — як інгредієнти в тому ж порядку.'
                : '2. Додайте картинки як інгредієнти (Ingredients) строго в порядку номерів — у промпті вони згадуються як "image 1", "image 2"...';
            $lines[] = '';
            foreach ($images as $image) {
                $lines[] = "   {$image['file']} — {$image['role']}";
            }
            $lines[] = '';
        } else {
            $lines[] = '2. Картинок немає — тільки текст.';
        }

        return implode("\n", array_merge($lines, [
            '3. Вставте весь текст з prompt.txt у поле промпта і запустіть генерацію.',
            '4. Скачайте mp4 і в адмінці відкрийте сценарій -> «Завантажити відео з Flow».',
            '',
            'Продовження такого відео робиться в самому Flow (Extend): скачайте фінальну версію і завантажте її в адмінку ще раз.',
        ]));
    }
}
