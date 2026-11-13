<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Повідомлення в службову Telegram-групу (TELEGRAM_BOT_TOKEN, TELEGRAM_ALERT_CHAT_ID).
 * Ніколи не кидає винятків: збій Telegram не має ламати замовлення.
 */
class TelegramNotifier
{
    public function configured(): bool
    {
        return filled(config('services.telegram.bot_token')) && filled(config('services.telegram.alert_chat_id'));
    }

    public function send(string $html): bool
    {
        if (! $this->configured()) {
            Log::warning('Telegram alert skipped: bot token or chat id is not configured');

            return false;
        }

        try {
            $response = Http::timeout(5)->asForm()->post(
                'https://api.telegram.org/bot' . config('services.telegram.bot_token') . '/sendMessage',
                [
                    'chat_id' => config('services.telegram.alert_chat_id'),
                    'text' => mb_substr($html, 0, 4000),
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => 'true',
                ],
            );
            if (! $response->successful()) {
                Log::error('Telegram alert failed', ['status' => $response->status(), 'body' => $response->json('description')]);
            }

            return $response->successful();
        } catch (Throwable $e) {
            Log::error('Telegram alert failed', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
