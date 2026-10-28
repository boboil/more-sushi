<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_video_scenarios', function (Blueprint $table) {
            $table->decimal('cost', 10, 4)->default(0)->after('duration');
            $table->decimal('last_cost', 10, 4)->nullable()->after('cost');
            $table->json('last_usage')->nullable()->after('last_cost');
        });
    }

    public function down(): void
    {
        Schema::table('blog_video_scenarios', function (Blueprint $table) {
            $table->dropColumn(['cost', 'last_cost', 'last_usage']);
        });
    }
};
