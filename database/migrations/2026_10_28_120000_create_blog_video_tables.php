<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_video_characters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('voice')->nullable();
            $table->json('reference_images')->nullable();
            $table->timestamps();
        });

        Schema::create('blog_video_scenarios', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('setting')->nullable();
            $table->text('style')->nullable();
            $table->string('start_frame')->nullable();
            $table->json('lines')->nullable();
            $table->text('extra_instructions')->nullable();
            $table->json('extra_references')->nullable();
            $table->string('language')->default('українська');
            $table->string('aspect_ratio', 8)->default('9:16');
            $table->string('resolution', 8)->default('720p');
            $table->string('status', 16)->default('draft');
            $table->string('interaction_id')->nullable();
            $table->string('file_id')->nullable();
            $table->string('video_path')->nullable();
            $table->longText('last_prompt')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_video_scenarios');
        Schema::dropIfExists('blog_video_characters');
    }
};
