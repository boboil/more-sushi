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
        Schema::create('product_shop', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->integer('price');
            $table->integer('discount')->nullable();
            $table->integer('count')->nullable();
            $table->integer('weight')->nullable();
            $table->longText('consist')->nullable();
            $table->boolean('stock')->default(0);
            $table->boolean('latest')->default(0);
            $table->string('main_image')->nullable();
            $table->longText('images')->nullable();
            $table->longText('description')->nullable();
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
        Schema::dropIfExists('product_shop');
    }
};
