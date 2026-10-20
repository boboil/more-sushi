<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Товари для головного банера на першому екрані сайту
     */
    public function up(): void
    {
        Schema::table('product_shop', function (Blueprint $table) {
            $table->boolean('featured')->default(false)->index();
            $table->unsignedSmallInteger('featured_sort')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('product_shop', function (Blueprint $table) {
            $table->dropIndex(['featured']);
            $table->dropColumn(['featured', 'featured_sort']);
        });
    }
};
