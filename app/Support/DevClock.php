<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Підмінений годинник для локального тестування «Акції дня» (php artisan promo:dev --clock=…).
 * Працює лише в локальній пісочниці (POSTER_FAKE_ORDERS): час «заморожений» на заданій миті для всього бекенду.
 */
class DevClock
{
    public static function path(): string
    {
        return storage_path('app/promo-clock');
    }

    /**
     * Лише в локальній пісочниці (POSTER_FAKE_ORDERS у .claude/launch.json і promo-dev.sh).
     * На APP_ENV не спираємось: на проді він теж «local», а config('app.env') зламаний (env('local')).
     */
    public static function allowed(): bool
    {
        return (bool) config('services.poster.fake_orders');
    }

    public static function boot(): void
    {
        if (self::allowed() && is_file(self::path())) {
            Carbon::setTestNow(Carbon::parse(trim(file_get_contents(self::path())), config('app.timezone')));
        }
    }

    public static function active(): bool
    {
        return self::allowed() && is_file(self::path());
    }

    public static function set(?string $moment): void
    {
        if ($moment === null) {
            @unlink(self::path());
            Carbon::setTestNow();

            return;
        }
        $at = Carbon::parse($moment, config('app.timezone'));
        file_put_contents(self::path(), $at->format('Y-m-d H:i:s'));
        Carbon::setTestNow($at);
    }
}
