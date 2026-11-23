<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Зв'язок «Акції дня» з акцією в Poster (clients.getPromotion): за нею сайт рахує
 * знижку/подарунок так само, як каса, і передає акцію в онлайн-замовлення.
 * Проставляє акції, налаштовані в Poster на листопад 2026 (пт і сб — без акції в Poster).
 */
return new class extends Migration
{
    private const PROMOTIONS = [1 => 91, 2 => 93, 3 => 94, 4 => 95, 7 => 96];

    public function up(): void
    {
        Schema::table('promo_days', function (Blueprint $table) {
            $table->unsignedInteger('poster_promotion_id')->nullable()->after('weekday');
        });

        foreach (self::PROMOTIONS as $weekday => $promotionId) {
            DB::table('promo_days')->where('weekday', $weekday)->update(['poster_promotion_id' => $promotionId]);
        }
    }

    public function down(): void
    {
        Schema::table('promo_days', function (Blueprint $table) {
            $table->dropColumn('poster_promotion_id');
        });
    }
};
