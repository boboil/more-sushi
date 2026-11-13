<?php

namespace App\Services\Promo;

use App\Models\Shop\PromoDay;
use App\Models\SiteSetting;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

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
