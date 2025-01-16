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
        Schema::table('admin_order', function (Blueprint $table) {
            $table->integer('sum_product_without_sets')->after('sum_product')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('admin_order', function (Blueprint $table) {
            $table->dropColumn('sum_product_without_sets');
        });
    }
};
