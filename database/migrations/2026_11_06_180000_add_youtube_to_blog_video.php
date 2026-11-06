<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_youtube_connections', function (Blueprint $table) {
            $table->id();
            $table->string('channel_id')->unique();
            $table->string('channel_title');
            $table->text('refresh_token');
            $table->timestamps();
        });

        foreach (['blog_video_scenarios', 'blog_video_reels'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('youtube_video_id')->nullable()->after('video_path');
                $table->timestamp('youtube_uploaded_at')->nullable()->after('youtube_video_id');
            });
        }
    }

    public function down(): void
    {
        foreach (['blog_video_scenarios', 'blog_video_reels'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn(['youtube_video_id', 'youtube_uploaded_at']);
            });
        }

        Schema::dropIfExists('blog_youtube_connections');
    }
};
