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
        Schema::create('working_hours', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->float('hours');
            $table->date('working_day');
            $table->timestamps();
            $table->index('user_id');
            $table->index('working_day');
        });
        Schema::create('rates', function (Blueprint $table) {
            $table->id();
            $table->integer('role_id');
            $table->float('rate');
            $table->date('working_date');
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
        Schema::dropIfExists('working_hours');
        Schema::dropIfExists('rates');
    }
};
