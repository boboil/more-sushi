<?php

namespace App\Http\Controllers;

use App\Services\YouTubeService;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

/**
 * Підключення каналу YouTube: перехід на Google і повернення в адмінку.
 */
class YouTubeController extends Controller
{
    public function connect(Request $request, YouTubeService $youtube): RedirectResponse
    {
        abort_unless($youtube->configured(), 503, 'YouTube не налаштовано');

        $state = Str::random(40);
        $request->session()->put('youtube.state', $state);
        $previous = url()->previous();
        $request->session()->put('youtube.return', str_starts_with($previous, url('/admin') . '/') ? $previous : url('/admin'));

        return redirect()->away($youtube->authUrl($state));
    }

    public function callback(Request $request, YouTubeService $youtube): RedirectResponse
    {
        $state = $request->session()->pull('youtube.state');
        $return = $request->session()->pull('youtube.return', url('/admin'));

        abort_unless($state && hash_equals($state, (string) $request->query('state')), 403);

        try {
            if ($request->filled('error') || ! $request->filled('code')) {
                throw new \RuntimeException('Доступ не надано');
            }

            $connection = $youtube->connect($request->query('code'));
            Notification::make()->success()->title("Канал «{$connection->channel_title}» підключено")->send();
        } catch (Throwable $e) {
            report($e);
            Notification::make()->danger()->title('Не вдалося підключити YouTube')->body($e->getMessage())->persistent()->send();
        }

        return redirect()->to($return);
    }
}
