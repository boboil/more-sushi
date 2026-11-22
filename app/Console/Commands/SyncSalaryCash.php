<?php

namespace App\Console\Commands;

use App\Services\Salary\PosterCashSync;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Підтягнути касу з Poster: за замовчуванням поточний місяць (лише незакриті дні).
 */
class SyncSalaryCash extends Command
{
    protected $signature = 'salary:sync-cash {--month= : Місяць Y-m} {--force : Перечитати всі дні місяця}';

    protected $description = 'Каса для зарплати з Poster (виторг потрібних категорій)';

    public function handle(PosterCashSync $sync): int
    {
        $month = $this->option('month') ? CarbonImmutable::createFromFormat('!Y-m', $this->option('month')) : CarbonImmutable::today();
        $result = $sync->syncMonth($month, (bool) $this->option('force'));

        $this->info("Оновлено днів: {$result['synced']}");
        if ($result['failed']) {
            $this->error('Не вдалося: ' . implode(', ', $result['failed']));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
