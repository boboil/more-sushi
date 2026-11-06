<?php

namespace App\Services;

use App\Models\Blog\VideoReel;
use App\Models\Blog\VideoScenario;
use App\Models\Blog\YouTubeConnection;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Завантаження готових відео на канал YouTube через YouTube Data API.
 *
 * Підключення: OAuth 2.0 від імені власника каналу, refresh token лежить зашифрованим у БД.
 * На акаунті кілька каналів, тому підключити можна лише канал із services.youtube.channel_id.
 */
class YouTubeService
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const API_URL = 'https://www.googleapis.com/youtube/v3';

    private const UPLOAD_URL = 'https://www.googleapis.com/upload/youtube/v3/videos';

    private const SCOPES = [
        'https://www.googleapis.com/auth/youtube.upload',
        'https://www.googleapis.com/auth/youtube.readonly',
    ];

    /** Категорія «Люди й блоги». */
    private const CATEGORY_ID = '22';

    private const LANGUAGE = 'uk';

    public const PRIVACY = [
        'private' => 'Приватне',
        'unlisted' => 'Не для всіх (за посиланням)',
        'public' => 'Для всіх',
    ];

    public const DEFAULT_DESCRIPTION = "Замовити суші у Сумах: https://moresushi.in.ua/\n"
        . "Телефон: 099-066-35-11\n"
        . "Суми, вул. Троїцька 17, з 11:00 до 20:30\n\n"
        . '#суші #суми #дариморя';

    public function configured(): bool
    {
        return filled(config('services.youtube.client_id')) && filled(config('services.youtube.client_secret'));
    }

    public function connection(): ?YouTubeConnection
    {
        return YouTubeConnection::query()->where('channel_id', config('services.youtube.channel_id'))->first();
    }

    public function redirectUri(): string
    {
        return rtrim((string) config('app.url'), '/') . '/admin/youtube/callback';
    }

    public function authUrl(string $state): string
    {
        return self::AUTH_URL . '?' . http_build_query([
            'client_id' => config('services.youtube.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => implode(' ', self::SCOPES),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);
    }

    /**
     * Міняє код з Google на токени і зберігає канал, якщо це саме наш канал.
     */
    public function connect(string $code): YouTubeConnection
    {
        $tokens = Http::asForm()->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => config('services.youtube.client_id'),
            'client_secret' => config('services.youtube.client_secret'),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ]);
        if ($tokens->failed() || ! $tokens->json('refresh_token')) {
            throw new RuntimeException('Google не видав доступ: ' . $this->errorMessage($tokens));
        }

        $channels = Http::withToken($tokens->json('access_token'))
            ->get(self::API_URL . '/channels', ['part' => 'snippet', 'mine' => 'true']);
        if ($channels->failed()) {
            throw new RuntimeException('Не вдалося прочитати канал: ' . $this->errorMessage($channels));
        }

        $channel = $channels->json('items.0');
        if (($channel['id'] ?? null) !== config('services.youtube.channel_id')) {
            $title = $channel['snippet']['title'] ?? 'без назви';
            throw new RuntimeException("Обрано канал «{$title}». У вікні Google потрібно вибрати «Дари Моря Суші».");
        }

        return YouTubeConnection::query()->updateOrCreate(
            ['channel_id' => $channel['id']],
            ['channel_title' => $channel['snippet']['title'], 'refresh_token' => $tokens->json('refresh_token')],
        );
    }

    /**
     * @param  array{title: string, description: ?string, privacy: string, synthetic: bool}  $meta
     * @return string id відео на YouTube
     */
    public function upload(VideoScenario|VideoReel $video, array $meta): string
    {
        $disk = Storage::disk('public_root');
        if (! $video->video_path || ! $disk->exists($video->video_path)) {
            throw new RuntimeException('Файл відео не знайдено');
        }

        $path = $disk->path($video->video_path);
        $token = $this->accessToken();

        $session = Http::withToken($token)
            ->withHeaders([
                'X-Upload-Content-Length' => (string) filesize($path),
                'X-Upload-Content-Type' => 'video/mp4',
            ])
            ->timeout(60)
            ->post(self::UPLOAD_URL . '?uploadType=resumable&part=snippet,status', [
                'snippet' => [
                    'title' => $meta['title'],
                    'description' => (string) ($meta['description'] ?? ''),
                    'categoryId' => self::CATEGORY_ID,
                    'defaultLanguage' => self::LANGUAGE,
                    'defaultAudioLanguage' => self::LANGUAGE,
                ],
                'status' => [
                    'privacyStatus' => $meta['privacy'],
                    'selfDeclaredMadeForKids' => false,
                    'containsSyntheticMedia' => $meta['synthetic'],
                ],
            ]);

        $sessionUrl = $session->header('Location');
        if ($session->failed() || ! $sessionUrl) {
            throw new RuntimeException($this->errorMessage($session));
        }

        $response = Http::withToken($token)
            ->withBody(Utils::streamFor(Utils::tryFopen($path, 'r')), 'video/mp4')
            ->timeout(600)
            ->put($sessionUrl);

        $id = $response->json('id');
        if ($response->failed() || ! $id) {
            throw new RuntimeException($this->errorMessage($response));
        }

        $video->forceFill(['youtube_video_id' => $id, 'youtube_uploaded_at' => now()])->save();

        return $id;
    }

    private function accessToken(): string
    {
        $connection = $this->connection();
        if (! $connection) {
            throw new RuntimeException('Канал YouTube не підключено');
        }

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'refresh_token' => $connection->refresh_token,
            'client_id' => config('services.youtube.client_id'),
            'client_secret' => config('services.youtube.client_secret'),
            'grant_type' => 'refresh_token',
        ]);

        if ($response->json('error') === 'invalid_grant') {
            $connection->delete();
            throw new RuntimeException('Доступ до YouTube відкликано або він застарів. Підключіть канал ще раз.');
        }

        $token = $response->json('access_token');
        if ($response->failed() || ! $token) {
            throw new RuntimeException('Не вдалося оновити доступ: ' . $this->errorMessage($response));
        }

        return $token;
    }

    private function errorMessage(Response $response): string
    {
        $error = $response->json('error');
        if (is_array($error)) {
            $reason = $error['errors'][0]['reason'] ?? null;

            return trim(($error['message'] ?? 'Помилка YouTube') . ($reason ? " ({$reason})" : ''));
        }

        return $response->json('error_description') ?? (is_string($error) ? $error : "HTTP {$response->status()}");
    }
}
