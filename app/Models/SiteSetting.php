<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Прості налаштування сайту «ключ → значення» (перемикачі функцій тощо).
 */
class SiteSetting extends Model
{
    public const PROMO_LANDING_ENABLED = 'promo_landing_enabled';

    /** Секрет для перегляду лендингів з адмінки, поки вони вимкнені */
    public const PROMO_PREVIEW_KEY = 'promo_preview_key';

    protected $table = 'site_settings';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::query()->find($key)?->value ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function flag(string $key): bool
    {
        return (bool) (int) static::get($key, '0');
    }
}
