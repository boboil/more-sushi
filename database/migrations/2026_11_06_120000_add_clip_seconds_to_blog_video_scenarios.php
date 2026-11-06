<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_video_scenarios', function (Blueprint $table) {
            $table->unsignedTinyInteger('clip_seconds')->default(10)->after('resolution');
        });
    }

    public function down(): void
    {
        Schema::table('blog_video_scenarios', function (Blueprint $table) {
            $table->dropColumn('clip_seconds');
        });
    }
};
