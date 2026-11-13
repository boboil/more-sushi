<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Shop\PromoDay;
use App\Services\Promo\PromoDays;
use Illuminate\Http\JsonResponse;

/**
 * Дані для лендингів «Акція дня». Коли лендинги вимкнені в адмінці — порожній список.
 */
class PromoDayController extends Controller
{
    public function index(PromoDays $promoDays): JsonResponse
    {
        if (! $promoDays->enabled()) {
            return new JsonResponse(['enabled' => false, 'days' => []]);
        }

        return new JsonResponse([
            'enabled' => true,
            'days' => $promoDays->all()->map(fn (PromoDay $day) => [
                'weekday' => $day->weekday,
                'key' => PromoDay::DAYS[$day->weekday]['key'],
                'slug' => PromoDay::DAYS[$day->weekday]['slug'],
                'label' => $day->label(),
                'short' => PromoDay::DAYS[$day->weekday]['short'],
                'isActive' => $day->is_active,
                'offerType' => $day->offer_type,
                'discountPercent' => $day->isPercent() ? $day->discount_percent : null,
                'offerTitle' => $day->offer_title,
                'offerShort' => $day->offer_short,
                'giftBadge' => $day->gift_badge,
                'activeFrom' => $day->active_from,
                'activeTo' => $day->active_to,
                'terms' => $day->terms,
                'heroImage' => $day->hero_image ? asset($day->hero_image) : null,
                'productIds' => $day->products->pluck('id')->values(),
            ])->values(),
        ]);
    }
}
