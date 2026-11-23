<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Shop\PromoDay;
use App\Services\Promo\PosterPromotion;
use App\Services\Promo\PromoCart;
use App\Services\Promo\PromoDays;
use App\Support\DevClock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Дані для лендингів «Акція дня». Коли лендинги вимкнені в адмінці — порожній список,
 * крім попереднього перегляду з адмінки (?preview=<ключ>).
 */
class PromoDayController extends Controller
{
    public function index(Request $request, PromoDays $promoDays, PromoCart $promoCart): JsonResponse
    {
        $enabled = $promoDays->enabled();
        $preview = $promoDays->isPreview($request->query('preview'));
        if (! $enabled && ! $preview) {
            return new JsonResponse(['enabled' => false, 'days' => []] + $this->clock());
        }

        return new JsonResponse([
            'enabled' => $enabled,
            'preview' => $preview,
            'days' => $promoDays->all()->map(fn (PromoDay $day) => $this->day($day, $promoCart))->values(),
        ] + $this->clock());
    }

    /** Локально з підміненим годинником фронт рахує акцію за тим самим часом. */
    private function clock(): array
    {
        return DevClock::active() ? ['clock' => now()->toIso8601String()] : [];
    }

    /**
     * Кошик під «Акцію дня»: що дасть акція Poster (знижка, подарунок, підказка).
     * Поза годинами акції чи без прив'язаної акції Poster — active: false.
     */
    public function quote(Request $request, PromoDays $promoDays): JsonResponse
    {
        $data = $request->validate([
            'lines' => 'array',
            'lines.*.id' => 'required|integer',
            'lines.*.quantity' => 'required|integer|min:1|max:99',
            'gift' => 'nullable|integer',
        ]);
        $lines = collect($data['lines'] ?? [])->groupBy('id')->map(fn ($l) => (int) $l->sum('quantity'))->all();
        $quote = $promoDays->quote($lines, $data['gift'] ?? null);

        return new JsonResponse($quote?->toArray() ?? ['active' => false]);
    }

    private function day(PromoDay $day, PromoCart $promoCart): array
    {
        // з прив'язаною акцією Poster знижка й товари зі знижкою — як на касі (у її період дії)
        $promotion = $promoCart->promotionFor($day, ignoreWeekday: true);
        $posterPercent = $promotion && $day->isPercent() && $promotion->resultType === PosterPromotion::RESULT_PERCENT
            ? (int) round($promotion->discountPercent) : null;

        return [
            'weekday' => $day->weekday,
            'key' => PromoDay::DAYS[$day->weekday]['key'],
            'slug' => PromoDay::DAYS[$day->weekday]['slug'],
            'label' => $day->label(),
            'short' => PromoDay::DAYS[$day->weekday]['short'],
            'isActive' => $day->is_active,
            'offerType' => $day->offer_type,
            'discountPercent' => $day->isPercent() ? ($posterPercent ?? $day->discount_percent) : null,
            'offerTitle' => $day->offer_title,
            'offerShort' => $day->offer_short,
            'giftBadge' => $day->gift_badge,
            'activeFrom' => $day->active_from,
            'activeTo' => $day->active_to,
            'terms' => $day->terms,
            'heroImage' => $day->hero_image ? asset($day->hero_image) : null,
            'productIds' => $day->products->pluck('id')->values(),
            'posterPromotionId' => $day->poster_promotion_id,
            // прив'язано до Poster: товари, чия ціна в меню знижена акцією (порожньо — знижка лише в кошику);
            // null — акція без Poster, ціни як раніше за productIds
            'discountProductIds' => $day->poster_promotion_id ? ($promotion ? $promoCart->discountProductIds($promotion) : []) : null,
        ];
    }
}
