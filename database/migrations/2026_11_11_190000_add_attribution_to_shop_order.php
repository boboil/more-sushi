<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_order', function (Blueprint $table) {
            // звідки прийшов відвідувач: UTM, тип click-id, зовнішній реферер (див. OrderAttribution)
            $table->json('attribution')->nullable()->after('sum');
        });
    }

    public function down(): void
    {
        Schema::table('shop_order', function (Blueprint $table) {
            $table->dropColumn('attribution');
        });
    }
};
