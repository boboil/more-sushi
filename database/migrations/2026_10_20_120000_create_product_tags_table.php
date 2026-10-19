<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Теги товарів — використовуються як фільтри в меню сайту (Гостре, Лосось…)
     */
    public function up(): void
    {
        Schema::create('product_tags', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('enable')->default(true);
            $table->timestamps();
        });

        Schema::create('product_shop_tag', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('product_shop')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('product_tags')->cascadeOnDelete();
            $table->primary(['product_id', 'tag_id']);
        });

        // Стартовий набір фільтрів з дизайну; slug-и збігаються з тими, що вже використовує новий фронт
        $now = now();
        DB::table('product_tags')->insert(collect([
            ['spicy', 'Гостре'],
            ['baked', 'Запечене'],
            ['salmon', 'Лосось'],
            ['tuna', 'Тунець'],
            ['eel', 'Вугор'],
            ['shrimp', 'Креветка'],
            ['crab', 'Краб'],
        ])->map(fn (array $tag, int $i) => [
            'slug' => $tag[0],
            'title' => $tag[1],
            'sort_order' => $i,
            'enable' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_shop_tag');
        Schema::dropIfExists('product_tags');
    }
};
