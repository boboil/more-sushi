<?php

namespace App\Console\Commands;

use App\Services\PosterProductSync;
use Illuminate\Console\Command;

/**
 * Те саме, що кнопка «Синхронізувати з Poster» в адмінці; з --dry-run лише показує зміни.
 */
class SyncPosterProducts extends Command
{
    protected $signature = 'poster:sync-products {--dry-run : Лише показати, що зміниться} {--no-images : Не оновлювати фото}';

    protected $description = 'Синхронізувати товари сайту з Poster (вимкнути відсутні, увімкнути наявні)';

    public function handle(PosterProductSync $sync): int
    {
        $plan = $this->option('dry-run') ? $sync->plan() : $sync->run(! $this->option('no-images'));

        $this->line($this->option('dry-run') ? 'Пробний прогін, нічого не змінено.' : 'Синхронізацію виконано.');
        $this->section('Вимкнути', $plan['disable']->map(fn ($p) => "#{$p->id} [poster {$p->poster_id}] " . trim($p->title)));
        $this->section('Увімкнути', $plan['enable']->map(fn ($p) => "#{$p->id} [poster {$p->poster_id}] " . trim($p->title)));
        $this->section('Нові товари', $plan['create']->map(fn ($i) => "[poster {$i['product_id']}] " . trim($i['product_name']) . " — {$i['category_name']}"));
        $this->section('Зміна ціни', $plan['update']->map(fn ($u) => "#{$u['product']->id} " . trim($u['product']->title) . ": {$u['product']->price} → {$u['price']}"));

        return self::SUCCESS;
    }

    private function section(string $title, $lines): void
    {
        $this->newLine();
        $this->info("{$title}: {$lines->count()}");
        foreach ($lines as $line) {
            $this->line("  {$line}");
        }
    }
}
