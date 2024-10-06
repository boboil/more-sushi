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
        Schema::create('l_orders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('address')->nullable();
            $table->string('time')->nullable();
            $table->string('sum')->nullable();
            $table->boolean('self_pickup')->default(1);
            $table->timestamps();
        });

        Schema::create('l_order_shop_product', function (Blueprint $table) {
            $table->id();
            $table->integer('l_order_id');
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
        Schema::dropIfExists('l_orders');
        Schema::dropIfExists('l_order_shop_product');
    }
};
