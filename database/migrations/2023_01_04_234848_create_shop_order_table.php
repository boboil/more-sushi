<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_order', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_delivery_type');
            $table->string('customer_street')->nullable();
            $table->string('customer_building')->nullable();
            $table->string('online_payment');
            $table->integer('sticks_educational')->default(0);
            $table->integer('sticks_standard')->default(0);
            $table->boolean('is_as_soon_as_possible')->default(1);
            $table->string('time')->nullable();
            $table->timestamps();
        });
        Schema::create('shop_order_shop_product', function (Blueprint $table) {
            $table->id();
            $table->integer('shop_order_id');
            $table->integer('shop_product_id');
            $table->integer('shop_product_quantity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_order');
        Schema::dropIfExists('shop_order_shop_product');
    }
};
