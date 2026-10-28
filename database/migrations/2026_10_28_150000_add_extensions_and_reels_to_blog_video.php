<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_video_scenarios', function (Blueprint $table) {
            $table->json('extensions')->nullable()->after('lines');
            $table->boolean('extending')->default(false)->after('status');
            $table->string('previous_interaction_id')->nullable()->after('interaction_id');
            $table->decimal('duration', 6, 2)->nullable()->after('video_path');
        });

        Schema::create('blog_video_reels', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->json('items')->nullable();
            $table->string('video_path')->nullable();
            $table->decimal('duration', 6, 2)->nullable();
            $table->text('error')->nullable();
            $table->timestamp('built_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_video_reels');

        Schema::table('blog_video_scenarios', function (Blueprint $table) {
            $table->dropColumn(['extensions', 'extending', 'previous_interaction_id', 'duration']);
        });
    }
};
