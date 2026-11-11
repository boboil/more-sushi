<?php

namespace App\Services\Statistics;

/**
 * Звідки прийшло замовлення: сайт пам'ятає останній непрямий візит (UTM, click-id реклами
 * або зовнішній реферер) 30 днів і надсилає його із замовленням як `attribution`.
 * Зберігається в shop_order.attribution.
 */
final class OrderAttribution
{
    public const UNTRACKED = 'Без міток';

    public const DIRECT = 'Прямий захід';

    /** Ключі, які приймаємо від сайту. Решту відкидаємо. */
    private const KEYS = ['source', 'medium', 'campaign', 'content', 'term', 'click_id_type', 'referrer', 'landing_page', 'touched_at'];

    private const MAX_LENGTH = 200;

    private const SEARCH_ENGINES = '/(^|\.)(google|bing|duckduckgo|yahoo|ecosia|yandex)\./';

    public static function sanitize(mixed $input): ?array
    {
        if (! is_array($input)) {
            return null;
        }

        $clean = [];
        foreach (self::KEYS as $key) {
            $value = $input[$key] ?? null;
            if (! is_scalar($value)) {
                continue;
            }
            $value = trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string) $value) ?? '');
            if ($value !== '') {
                $clean[$key] = mb_substr($value, 0, self::MAX_LENGTH);
            }
        }

        return $clean ?: null;
    }

    /**
     * Канал для звітів: "chatgpt / cpc", "google / organic", "chatgpt.com / referral"…
     */
    public static function channel(?array $attribution): string
    {
        if (! $attribution) {
            return self::UNTRACKED; // замовлення до впровадження або localStorage був недоступний
        }

        $source = self::normalize($attribution['source'] ?? null);
        $medium = self::normalize($attribution['medium'] ?? null);
        $referrer = self::normalize($attribution['referrer'] ?? null);

        if ($source !== null) {
            return $medium !== null ? "{$source} / {$medium}" : $source;
        }
        if ($referrer !== null) {
            return preg_match(self::SEARCH_ENGINES, $referrer, $match)
                ? "{$match[2]} / organic"
                : "{$referrer} / referral";
        }
        if (! empty($attribution['click_id_type'])) {
            return self::normalize($attribution['click_id_type']) . ' / click';
        }

        return self::DIRECT;
    }

    public static function campaign(?array $attribution): ?string
    {
        $campaign = $attribution ? self::normalize($attribution['campaign'] ?? null, lowercase: false) : null;
        if ($campaign === null) {
            return null;
        }

        return (self::normalize($attribution['source'] ?? null) ?? '—') . " · {$campaign}";
    }

    private static function normalize(mixed $value, bool $lowercase = true): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        $value = trim($value);

        return $lowercase ? mb_strtolower(preg_replace('/^www\./i', '', $value)) : $value;
    }
}
