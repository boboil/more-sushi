<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Каса тепер приходить з Poster: коли день востаннє синхронізовано. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_days', function (Blueprint $table) {
            $table->timestamp('synced_at')->nullable()->after('entered_by');
        });
    }

    public function down(): void
    {
        Schema::table('salary_days', function (Blueprint $table) {
            $table->dropColumn('synced_at');
        });
    }
};
