<?php

namespace App\Services\Salary;

use App\Models\Role;
use App\Models\SalaryDay;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\WorkingHours;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Зарплата «з каси» (як у таблиці «Розрахунок»):
 *  фонд дня  = каса дня × відсоток;
 *  вага      = години × коефіцієнт посади;
 *  виплата   = фонд дня × вага працівника / сума ваг усіх, хто працював цього дня.
 */
class SalaryPool
{
    public const PERCENT_SETTING = 'salary_pool_percent';

    public const DEFAULT_PERCENT = 15.0;

    /** Скільки днів назад працівник може сам виправляти свої години. */
    public const SELF_EDIT_DAYS = 7;

    public const MAX_HOURS = 24;

    /**
     * Перший місяць після запуску: до BACKFILL_UNTIL працівники можуть вносити години
     * за будь-який день починаючи з BACKFILL_FROM (ще ніхто нічого не заповнював).
     */
    public const BACKFILL_FROM = '2026-11-01';

    public const BACKFILL_UNTIL = '2026-11-30';

    public function percent(): float
    {
        return (float) SiteSetting::get(self::PERCENT_SETTING, (string) self::DEFAULT_PERCENT);
    }

    public function setPercent(float $percent): void
    {
        SiteSetting::set(self::PERCENT_SETTING, (string) $percent);
    }

    /** Застосувати поточний відсоток до вже внесених кас місяця. */
    public function applyPercentToMonth(CarbonInterface $month, float $percent): void
    {
        SalaryDay::query()
            ->whereDate('date', '>=', $month->copy()->startOfMonth()->toDateString())->whereDate('date', '<=', $month->copy()->endOfMonth()->toDateString())
            ->update(['pool_percent' => $percent]);
    }

    public function setCash(CarbonInterface $date, ?float $cash, ?int $enteredBy): void
    {
        if ($cash === null) {
            SalaryDay::query()->whereDate('date', $date->toDateString())->delete();

            return;
        }

        $day = SalaryDay::query()->whereDate('date', $date->toDateString())->first() ?? new SalaryDay(['date' => $date->toDateString(), 'pool_percent' => $this->percent()]);
        $day->fill(['cash' => $cash, 'entered_by' => $enteredBy])->save();
    }

    /** 0 або null — видалити запис за день. */
    public function setHours(int $userId, CarbonInterface $date, ?float $hours, string $source, ?int $enteredBy): void
    {
        $query = WorkingHours::query()->where('user_id', $userId)->whereDate('working_day', $date->toDateString());

        if (! $hours) {
            $query->delete();

            return;
        }

        $record = $query->first() ?? new WorkingHours(['user_id' => $userId, 'working_day' => $date->toDateString()]);
        $record->fill(['hours' => round($hours, 2), 'source' => $source, 'entered_by' => $enteredBy])->save();
    }

    /** Чи може працівник сам змінити свої години за цей день. */
    public function canSelfEdit(CarbonInterface $date, ?WorkingHours $existing): bool
    {
        $today = CarbonImmutable::today();
        $day = CarbonImmutable::parse($date->toDateString());

        if ($day->greaterThan($today) || $day->lessThan($this->selfEditFrom())) {
            return false;
        }

        // години, які виставив адміністратор, працівник уже не переписує
        return $existing === null || $existing->source === WorkingHours::SOURCE_SELF;
    }

    /**
     * Чи може касир змінити години іншого працівника за цей день.
     * Ті ж дні, що й працівнику; за сьогодні — будь-які години, навіть від адміністратора.
     */
    public function canCashierEdit(CarbonInterface $date, ?WorkingHours $existing): bool
    {
        $today = CarbonImmutable::today();
        $day = CarbonImmutable::parse($date->toDateString());

        if ($day->greaterThan($today) || $day->lessThan($this->selfEditFrom())) {
            return false;
        }

        return $day->equalTo($today)
            || $existing === null
            || in_array($existing->source, [WorkingHours::SOURCE_SELF, WorkingHours::SOURCE_CASHIER], true);
    }

    /** Найраніша дата, за яку працівник ще може сам внести години. */
    public function selfEditFrom(): CarbonImmutable
    {
        $today = CarbonImmutable::today();
        $from = $today->subDays(self::SELF_EDIT_DAYS);

        if ($today->lessThanOrEqualTo(CarbonImmutable::parse(self::BACKFILL_UNTIL))) {
            $from = $from->min(CarbonImmutable::parse(self::BACKFILL_FROM));
        }

        return $from;
    }

    /** Працівники, що беруть участь у розподілі: є роль з коефіцієнтом. */
    public function staff(): Collection
    {
        return User::query()
            ->whereHas('roles', fn ($q) => $q->where('salary_coefficient', '>', 0))
            ->with('roles')
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<int, Role> */
    public function roles(): Collection
    {
        return Role::query()->orderBy('id')->get();
    }

    /**
     * Повний розрахунок за місяць.
     *
     * @return array{
     *     days: array<string, array{date: string, day: int, cash: ?float, percent: ?float, pool: float, weight: float, paid: float, unallocated: bool}>,
     *     employees: list<array{id: int, name: string, role: ?string, coefficient: float, cells: array<string, array{hours: float, source: string, pay: float}>, hours: float, pay: float, perHour: float, daysWorked: int, noCoefficient: bool}>,
     *     totals: array{cash: float, pool: float, paid: float, hours: float}
     * }
     */
    public function month(CarbonInterface $month): array
    {
        $start = CarbonImmutable::parse($month->toDateString())->startOfMonth();
        $end = $start->endOfMonth();

        $cashByDate = SalaryDay::query()
            ->whereDate('date', '>=', $start->toDateString())->whereDate('date', '<=', $end->toDateString())
            ->get()
            ->keyBy(fn (SalaryDay $d) => $d->date->toDateString());

        $hours = WorkingHours::query()
            ->whereDate('working_day', '>=', $start->toDateString())->whereDate('working_day', '<=', $end->toDateString())
            ->where('hours', '>', 0)
            ->get();

        // учасники: усі з коефіцієнтом + ті, хто вніс години без коефіцієнта (щоб адмін побачив проблему)
        $staff = $this->staff();
        $extraIds = $hours->pluck('user_id')->unique()->diff($staff->pluck('id'));
        if ($extraIds->isNotEmpty()) {
            $staff = $staff->concat(User::query()->with('roles')->whereIn('id', $extraIds)->get())->sortBy('name')->values();
        }
        $coefficients = $staff->mapWithKeys(fn (User $u) => [$u->id => $u->salaryCoefficient()]);
        $hoursByUser = $hours->groupBy('user_id');

        $days = [];
        for ($date = $start; $date->lessThanOrEqualTo($end); $date = $date->addDay()) {
            $key = $date->toDateString();
            $cashDay = $cashByDate->get($key);
            $weight = $hours->filter(fn (WorkingHours $h) => $h->working_day->toDateString() === $key)
                ->sum(fn (WorkingHours $h) => $h->hours * ($coefficients[$h->user_id] ?? 0));
            $pool = $cashDay?->pool() ?? 0.0;

            $days[$key] = [
                'date' => $key,
                'day' => $date->day,
                'cash' => $cashDay ? (float) $cashDay->cash : null,
                'percent' => $cashDay ? (float) $cashDay->pool_percent : null,
                'pool' => $pool,
                'weight' => round($weight, 4),
                'paid' => 0.0,
                'unallocated' => $pool > 0 && $weight <= 0,
            ];
        }

        $employees = [];
        foreach ($staff as $user) {
            $coefficient = $coefficients[$user->id];
            $cells = [];
            $totalHours = 0.0;
            $totalPay = 0.0;

            foreach ($hoursByUser->get($user->id, collect()) as $record) {
                $key = $record->working_day->toDateString();
                $day = $days[$key];
                $pay = $day['weight'] > 0 ? $day['pool'] * ($record->hours * $coefficient) / $day['weight'] : 0.0;

                $cells[$key] = ['hours' => $record->hours, 'source' => $record->source, 'pay' => round($pay, 2)];
                $days[$key]['paid'] += $pay;
                $totalHours += $record->hours;
                $totalPay += $pay;
            }

            $employees[] = [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->salaryRoleLabel(),
                'coefficient' => $coefficient,
                'cells' => $cells,
                'hours' => round($totalHours, 2),
                'pay' => round($totalPay, 2),
                'perHour' => $totalHours > 0 ? round($totalPay / $totalHours, 2) : 0.0,
                'daysWorked' => count($cells),
                'noCoefficient' => $coefficient <= 0,
            ];
        }

        foreach ($days as $key => $day) {
            $days[$key]['paid'] = round($day['paid'], 2);
        }

        $daysCollection = collect($days);

        return [
            'days' => $days,
            'employees' => $employees,
            'totals' => [
                'cash' => round($daysCollection->sum(fn ($d) => $d['cash'] ?? 0), 2),
                'pool' => round($daysCollection->sum('pool'), 2),
                'paid' => round(collect($employees)->sum('pay'), 2),
                'hours' => round(collect($employees)->sum('hours'), 2),
            ],
        ];
    }
}
