<?php

namespace App\Services;

use App\Models\Blog\VideoReel;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Склеює готові відео сценаріїв ролика в один mp4.
 */
class VideoReelBuilder
{
    public const DIRECTORY = 'blog-video/reels';

    public function __construct(private readonly VideoTools $tools)
    {
    }

    public function build(VideoReel $reel): void
    {
        $disk = Storage::disk('public_root');
        $scenarios = $reel->scenarios();

        $missing = $scenarios->filter(fn ($scenario) => ! $scenario->video_path || ! $disk->exists($scenario->video_path));
        if ($scenarios->isEmpty() || $missing->isNotEmpty()) {
            throw new RuntimeException($scenarios->isEmpty()
                ? 'Додайте хоча б один сценарій з готовим відео'
                : 'Немає готового відео у сценаріях: ' . $missing->pluck('title')->implode(', '));
        }

        if (! $this->tools->available()) {
            throw new RuntimeException('На сервері не встановлено ffmpeg — склеювати ролики нічим');
        }

        set_time_limit(600);

        $path = self::DIRECTORY . '/' . $reel->id . '-' . now()->format('Ymd-His') . '.mp4';
        $disk->makeDirectory(self::DIRECTORY);
        $this->tools->concat($scenarios->map(fn ($scenario) => $disk->path($scenario->video_path))->all(), $disk->path($path));

        if ($reel->video_path && $reel->video_path !== $path) {
            $disk->delete($reel->video_path);
        }

        $reel->forceFill([
            'video_path' => $path,
            'duration' => $this->tools->duration($disk->path($path)),
            'error' => null,
            'built_at' => now(),
        ])->save();
    }
}
