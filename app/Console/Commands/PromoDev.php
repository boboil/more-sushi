<?php

namespace App\Console\Commands;

use App\Models\Shop\PromoDay;
use App\Services\Promo\PromoCart;
use App\Services\Promo\PromoDays;
use App\Services\Promo\PosterPromotions;
use App\Support\DevClock;
use Illuminate\Console\Command;

/**
 * Локальне тестування «Акції дня»: стан, підмінений годинник, увімкнення/вимкнення лендингів і днів.
 *
 *   php artisan promo:dev                              стан
 *   php artisan promo:dev --clock="2026-11-25 14:00"   «зараз» = середа 14:00 (лише з POSTER_FAKE_ORDERS=true)
 *   php artisan promo:dev --clock=wed                  найближча середа, 14:00
 *   php artisan promo:dev --clock=off                  справжній час
 *   php artisan promo:dev --landing=on                 лендинги й акції на сайті (= перемикач в адмінці)
 *   php artisan promo:dev --day=3 --active=off         вимкнути середу
 *   php artisan promo:dev --day=3 --poster=94          прив'язати середу до акції Poster (0 — відв'язати)
 */
class PromoDev extends Command
{
    protected $signature = 'promo:dev
        {--clock= : "Y-m-d H:i", mon…sun (о 14:00) або off}
        {--landing= : on|off}
        {--day= : день тижня 1–7}
        {--active= : on|off для --day}
        {--poster= : ID акції Poster для --day (0 — без Poster)}';

    protected $description = 'Локальне тестування «Акції дня»: годинник, перемикачі, стан';

    private const DAYS = ['mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6, 'sun' => 7];

    public function handle(PromoDays $promoDays, PromoCart $promoCart, PosterPromotions $poster): int
    {
        if (($clock = $this->option('clock')) !== null) {
            if (! DevClock::allowed()) {
                $this->error('Підміна годинника працює лише в локальній пісочниці (POSTER_FAKE_ORDERS=true)');

                return self::FAILURE;
            }
            DevClock::set($clock === 'off' ? null : $this->moment($clock));
        }
        if (($landing = $this->option('landing')) !== null) {
            $promoDays->setEnabled($this->flag($landing));
        }
        if ($weekday = (int) $this->option('day')) {
            $day = PromoDay::query()->where('weekday', $weekday)->firstOrFail();
            if (($active = $this->option('active')) !== null) {
                $day->is_active = $this->flag($active);
            }
            if (($posterId = $this->option('poster')) !== null) {
                $day->poster_promotion_id = (int) $posterId ?: null;
            }
            $day->save();
        }

        $now = now();
        $this->line('Зараз: ' . $now->format('D d.m.Y H:i') . (DevClock::active() ? '  (підмінено, --clock=off — справжній)' : ''));
        $this->line('Лендинги й акції на сайті: ' . ($promoDays->enabled() ? 'увімкнено' : 'вимкнено (--landing=on)'));
        $this->line('Замовлення в Poster: ' . (config('services.poster.fake_orders') ? 'НЕ відправляються, лог storage/logs/poster-fake-orders.log' : 'СПРАВЖНІ'));
        $current = $promoDays->current();

        $this->table(['День', 'Діє', 'Години', 'Poster', 'Акція Poster', 'Сьогодні'], $promoDays->all()->map(function (PromoDay $day) use ($poster, $promoCart, $current, $now) {
            $promotion = $day->poster_promotion_id ? $poster->find($day->poster_promotion_id) : null;
            $info = match (true) {
                ! $day->poster_promotion_id => '— (вручну оператором)',
                ! $promotion => 'не знайдено в Poster',
                ! $promotion->supported() => "«{$promotion->name}» — механіку сайт не рахує",
                default => "«{$promotion->name}»" . ($promotion->dateEnd ? ' до ' . $promotion->dateEnd->format('d.m') : ''),
            };
            $today = $current?->is($day)
                ? ($promoCart->promotionFor($day, $now) ? 'ДІЄ (Poster)' : ($day->poster_promotion_id ? 'діє, але Poster-акція не активна' : 'ДІЄ'))
                : '';

            return [$day->label(), $day->is_active ? 'так' : 'ні', "{$day->active_from}–{$day->active_to}", $day->poster_promotion_id ? '#' . $day->poster_promotion_id : '—', $info, $today];
        })->all());

        return self::SUCCESS;
    }

    private function moment(string $value): string
    {
        $key = strtolower(substr($value, 0, 3));
        if (isset(self::DAYS[$key])) {
            $date = now()->startOfDay();
            while ($date->isoWeekday() !== self::DAYS[$key]) {
                $date->addDay();
            }

            return $date->format('Y-m-d') . ' 14:00';
        }

        return $value;
    }

    private function flag(string $value): bool
    {
        return in_array(strtolower($value), ['on', '1', 'true', 'yes'], true);
    }
}
