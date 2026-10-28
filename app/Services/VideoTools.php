<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Обгортка над ffprobe/ffmpeg: тривалість кліпу і склейка кількох mp4 в один.
 */
class VideoTools
{
    public function available(): bool
    {
        return Process::run([config('services.ffmpeg.ffprobe'), '-version'])->successful()
            && Process::run([config('services.ffmpeg.ffmpeg'), '-version'])->successful();
    }

    public function duration(string $path): ?float
    {
        $info = $this->probe($path);

        return $info['duration'] ?? null;
    }

    /**
     * @return array{duration: float, width: int, height: int, video: string, audio: ?string}|null
     */
    public function probe(string $path): ?array
    {
        $result = Process::run([
            config('services.ffmpeg.ffprobe'), '-v', 'error', '-print_format', 'json',
            '-show_entries', 'format=duration:stream=codec_type,codec_name,width,height', $path,
        ]);
        if ($result->failed()) {
            return null;
        }

        $json = json_decode($result->output(), true) ?: [];
        $streams = collect($json['streams'] ?? []);
        $video = $streams->firstWhere('codec_type', 'video');
        if (! $video) {
            return null;
        }

        return [
            'duration' => (float) ($json['format']['duration'] ?? 0),
            'width' => (int) $video['width'],
            'height' => (int) $video['height'],
            'video' => $video['codec_name'],
            'audio' => $streams->firstWhere('codec_type', 'audio')['codec_name'] ?? null,
        ];
    }

    /**
     * Склеює кліпи по черзі. Однакові за форматом кліпи (як зазвичай віддає Gemini) копіюються без
     * перекодування, різні — приводяться до розміру першого кліпу.
     *
     * @param  array<int, string>  $paths  абсолютні шляхи
     */
    public function concat(array $paths, string $output): void
    {
        $infos = array_map(fn (string $path) => $this->probe($path) ?? throw new RuntimeException("Не вдалося прочитати відео: {$path}"), $paths);

        $signature = fn (array $info) => [$info['width'], $info['height'], $info['video'], $info['audio']];
        $sameFormat = collect($infos)->every(fn (array $info) => $signature($info) === $signature($infos[0]));

        if ($sameFormat && $this->concatCopy($paths, $output)) {
            return;
        }

        $this->concatReencode($paths, $infos, $output);
    }

    private function concatCopy(array $paths, string $output): bool
    {
        $list = tempnam(sys_get_temp_dir(), 'concat');
        file_put_contents($list, collect($paths)->map(fn ($path) => "file '" . str_replace("'", "'\\''", $path) . "'")->implode("\n"));

        $result = Process::timeout(300)->run([
            config('services.ffmpeg.ffmpeg'), '-v', 'error', '-y', '-f', 'concat', '-safe', '0', '-i', $list,
            '-c', 'copy', '-movflags', '+faststart', $output,
        ]);
        @unlink($list);

        return $result->successful();
    }

    private function concatReencode(array $paths, array $infos, string $output): void
    {
        ['width' => $width, 'height' => $height] = $infos[0];
        $args = [config('services.ffmpeg.ffmpeg'), '-v', 'error', '-y'];
        $filters = [];
        $pairs = '';

        foreach ($paths as $i => $path) {
            array_push($args, '-i', $path);
            $filters[] = "[{$i}:v]scale={$width}:{$height}:force_original_aspect_ratio=decrease,"
                . "pad={$width}:{$height}:(ow-iw)/2:(oh-ih)/2,setsar=1,fps=24[v{$i}]";
            // Кліп без звуку отримує тишу, щоб concat не зламався.
            $filters[] = $infos[$i]['audio']
                ? "[{$i}:a]aresample=48000,aformat=channel_layouts=stereo[a{$i}]"
                : "anullsrc=r=48000:cl=stereo,atrim=duration={$infos[$i]['duration']}[a{$i}]";
            $pairs .= "[v{$i}][a{$i}]";
        }
        $filters[] = $pairs . 'concat=n=' . count($paths) . ':v=1:a=1[v][a]';

        array_push($args, '-filter_complex', implode(';', $filters), '-map', '[v]', '-map', '[a]',
            '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '20', '-c:a', 'aac', '-b:a', '160k',
            '-movflags', '+faststart', $output);

        $result = Process::timeout(600)->run($args);
        if ($result->failed()) {
            throw new RuntimeException('ffmpeg: ' . trim($result->errorOutput()));
        }
    }
}
