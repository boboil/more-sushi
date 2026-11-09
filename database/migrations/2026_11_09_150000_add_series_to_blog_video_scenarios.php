<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_video_scenarios', function (Blueprint $table) {
            $table->string('series')->nullable()->after('title')->index();
            $table->unsignedSmallInteger('series_episode')->nullable()->after('series');
        });
    }

    public function down(): void
    {
        Schema::table('blog_video_scenarios', function (Blueprint $table) {
            $table->dropIndex(['series']);
            $table->dropColumn(['series', 'series_episode']);
        });
    }
};
