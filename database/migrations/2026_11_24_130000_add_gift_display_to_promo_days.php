<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Як показувати подарунок акції Poster на сайті: товар Poster => товари сайту (фото, назва, ціна).
 * Потрібно, коли подарунок у Poster — окрема техкарта-набір («Подарунок Піца Карбонара Піца Тоні Монтана» #1430),
 * не пов'язана з картками сайту. Подарунок, у якого на сайті є товар з тим самим poster_id, знаходиться сам.
 * Формат: [{"poster_id": 1430, "products": [87, 83]}]
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promo_days', function (Blueprint $table) {
            $table->json('gift_display')->nullable()->after('poster_promotion_id');
        });

        // вівторок: «Дві піци» = Піца Тоні Монтана (Poster 1295) + Піца Карбонара 450г (Poster 285)
        $pizzas = collect([1295, 285])
            ->map(fn (int $posterId) => DB::table('product_shop')->where('poster_id', $posterId)->value('id'))
            ->filter()->values()->all();
        if (count($pizzas) === 2) {
            DB::table('promo_days')->where('weekday', 2)->where('poster_promotion_id', 93)
                ->update(['gift_display' => json_encode([['poster_id' => 1430, 'products' => $pizzas]])]);
        }
    }

    public function down(): void
    {
        Schema::table('promo_days', function (Blueprint $table) {
            $table->dropColumn('gift_display');
        });
    }
};
