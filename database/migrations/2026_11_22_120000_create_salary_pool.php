<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Зарплата «з каси»: щодня % від каси ділиться між тими, хто працював,
 * пропорційно годинам × коефіцієнт посади.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // null — роль не бере участі в розподілі (наприклад, чисто технічна роль доступу)
            $table->decimal('salary_coefficient', 4, 2)->nullable()->after('label');
        });

        // Каса дня і відсоток, за яким рахувався фонд (знімок, щоб зміна налаштування не переписувала минулі дні)
        Schema::create('salary_days', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->decimal('cash', 12, 2);
            $table->decimal('pool_percent', 5, 2);
            $table->unsignedInteger('entered_by')->nullable();
            $table->timestamps();
        });

        Schema::table('working_hours', function (Blueprint $table) {
            // self — працівник вніс сам, admin — адміністратор, poster — старий імпорт з Poster
            $table->string('source', 10)->default('admin')->after('working_day');
            $table->unsignedInteger('entered_by')->nullable()->after('source');
        });
        DB::table('working_hours')->update(['source' => 'poster']);

        // Один запис на працівника за день: прибираємо можливі дублікати, лишаючи найновіший
        DB::statement('DELETE FROM working_hours WHERE id NOT IN (SELECT keep_id FROM (SELECT MAX(id) AS keep_id FROM working_hours GROUP BY user_id, working_day) AS latest)');
        Schema::table('working_hours', function (Blueprint $table) {
            $table->unique(['user_id', 'working_day']);
        });

        $now = now();
        DB::table('site_settings')->insertOrIgnore(['key' => 'salary_pool_percent', 'value' => '15', 'created_at' => $now, 'updated_at' => $now]);
    }

    public function down(): void
    {
        Schema::table('working_hours', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'working_day']);
            $table->dropColumn(['source', 'entered_by']);
        });
        Schema::dropIfExists('salary_days');
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('salary_coefficient');
        });
        DB::table('site_settings')->where('key', 'salary_pool_percent')->delete();
    }
};
