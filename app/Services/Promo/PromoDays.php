<?php

namespace App\Services\Promo;

use App\Models\Shop\PromoDay;
use App\Models\SiteSetting;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * «Акція дня»: увімкнені лендинги, акція сьогодні і чи діє вона зараз (час закладу — Europe/Kyiv, див. config/app.php).
 */
class PromoDays
{
    public function enabled(): bool
    {
        return SiteSetting::flag(SiteSetting::PROMO_LANDING_ENABLED);
    }

    public function setEnabled(bool $enabled): void
    {
        SiteSetting::set(SiteSetting::PROMO_LANDING_ENABLED, $enabled ? '1' : '0');
    }

    /** Ключ попереднього перегляду: лендинг з ?preview=<ключ> відкривається навіть вимкненим. */
    public function previewKey(): string
    {
        $key = SiteSetting::get(SiteSetting::PROMO_PREVIEW_KEY);
        if (blank($key)) {
            $key = Str::random(32);
            SiteSetting::set(SiteSetting::PROMO_PREVIEW_KEY, $key);
        }

        return $key;
    }

    public function isPreview(?string $key): bool
    {
        return filled($key) && hash_equals($this->previewKey(), $key);
    }

    /** Посилання «Переглянути» з адмінки: сторінка в стані «акція діє зараз» незалежно від дня й години. */
    public function previewUrl(PromoDay $day): string
    {
        return rtrim(config('services.front.url'), '/') . '/promo/' . PromoDay::DAYS[$day->weekday]['slug'] . '?preview=' . $this->previewKey();
    }

    /** @return Collection<int, PromoDay> за порядком днів */
    public function all(): Collection
    {
        return PromoDay::query()->with('products')->orderBy('weekday')->get();
    }

    /** Акція, яка діє саме зараз (лендинги ввімкнені, день активний, у межах годин), інакше null. */
    public function current(?CarbonInterface $now = null): ?PromoDay
    {
        if (! $this->enabled()) {
            return null;
        }
        $now ??= now();
        $day = PromoDay::query()->with('products')->where('weekday', $now->isoWeekday())->where('is_active', true)->first();

        return $day && $this->withinHours($day, $now) ? $day : null;
    }

    public function withinHours(PromoDay $day, CarbonInterface $now): bool
    {
        $time = $now->format('H:i');

        return $time >= $day->active_from && $time < $day->active_to;
    }
}
