<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Лендинги «Акція дня» (/promo/<день>): одна акція на кожен день тижня + загальний перемикач.
 * Тексти — приклади з дизайну, їх замінюють в адмінці. Лендинги вимкнені, доки їх не ввімкнуть.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_days', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('weekday')->unique(); // 1 = понеділок … 7 = неділя (ISO)
            $table->boolean('is_active')->default(true);
            $table->string('offer_type', 20)->default('percent'); // percent | gift
            $table->unsignedTinyInteger('discount_percent')->nullable();
            $table->string('offer_title');
            $table->string('offer_short', 40);
            $table->string('gift_badge', 20)->nullable();
            $table->string('active_from', 5)->default('00:00');
            $table->string('active_to', 5)->default('23:59');
            $table->text('terms')->nullable();
            $table->string('hero_image')->nullable();
            $table->timestamps();
        });

        Schema::create('promo_day_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_day_id')->constrained('promo_days')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        $now = now();
        $terms = 'Працює на доставку і самовивіз. Не підсумовується з іншими знижками та промокодами.';
        $days = [
            [1, 20, 'на всі сети', '-20% на сети'],
            [2, 50, 'на другий рол у замовленні', '-50% на другий рол'],
            [3, 15, 'на сети для двох', '-15% для двох'],
            [4, 25, 'на роли з лососем', '-25% з лососем'],
            [5, 30, 'на всі філадельфії', '-30% філадельфії'],
            [6, 20, 'на сімейні сети', '-20% сімейний'],
            [7, null, 'десерт у подарунок до замовлення', 'Десерт у дар'],
        ];
        DB::table('promo_days')->insert(array_map(fn ($d) => [
            'weekday' => $d[0],
            'is_active' => true,
            'offer_type' => $d[1] === null ? 'gift' : 'percent',
            'discount_percent' => $d[1],
            'offer_title' => $d[2],
            'offer_short' => $d[3],
            'active_from' => '11:00',
            'active_to' => '20:30',
            'terms' => $terms,
            'created_at' => $now,
            'updated_at' => $now,
        ], $days));

        DB::table('site_settings')->insert(['key' => 'promo_landing_enabled', 'value' => '0', 'created_at' => $now, 'updated_at' => $now]);
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_day_product');
        Schema::dropIfExists('promo_days');
        Schema::dropIfExists('site_settings');
    }
};
