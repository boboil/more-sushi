<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WorkingHours;
use App\Services\Salary\SalaryPool;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * «Мої години»: працівник бачить і вносить лише власні години. Чужих годин і грошей тут немає.
 * Касир додатково обирає працівника і вносить години за нього (SalaryPool::canCashierEdit).
 */
class StaffHoursController extends Controller
{
    public function __construct(private readonly SalaryPool $pool)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'user_id' => ['nullable', 'integer'],
        ]);
        $start = isset($data['month']) ? CarbonImmutable::createFromFormat('!Y-m', $data['month']) : CarbonImmutable::today()->startOfMonth();
        $end = $start->endOfMonth();
        $viewer = $request->user();
        $target = $this->target($request, $data['user_id'] ?? null);
        $asCashier = $target->id !== $viewer->id;

        $records = WorkingHours::query()
            ->where('user_id', $target->id)
            ->whereDate('working_day', '>=', $start->toDateString())->whereDate('working_day', '<=', $end->toDateString())
            ->get()
            ->keyBy(fn (WorkingHours $h) => $h->working_day->toDateString());

        // своя зарплата — лише власнику, касир чужих грошей не бачить
        $pay = $asCashier ? null : $this->ownPay($target, $start);

        $days = [];
        for ($date = $start; $date->lessThanOrEqualTo($end); $date = $date->addDay()) {
            $record = $records->get($date->toDateString());
            $days[] = [
                'date' => $date->toDateString(),
                'day' => $date->day,
                'weekday' => $date->dayOfWeekIso,
                'hours' => $record?->hours,
                'source' => $record?->source,
                'pay' => $pay ? ($pay['cells'][$date->toDateString()]['pay'] ?? null) : null,
                'editable' => $asCashier ? $this->pool->canCashierEdit($date, $record) : $this->pool->canSelfEdit($date, $record),
            ];
        }

        return response()->json([
            'userId' => $target->id,
            'name' => $target->name,
            'month' => $start->format('Y-m'),
            'today' => CarbonImmutable::today()->toDateString(),
            'editDays' => SalaryPool::SELF_EDIT_DAYS,
            'editFrom' => $this->pool->selfEditFrom()->toDateString(),
            'asCashier' => $asCashier,
            'totalHours' => round($records->sum('hours'), 2),
            'pay' => $pay ? ['total' => $pay['pay'], 'perHour' => $pay['perHour']] : null,
            'days' => $days,
            // список працівників — лише касиру
            'staff' => $viewer->canManageStaffHours()
                ? $this->pool->staff()->push($viewer)->unique('id')->sortBy('name')->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])->values()
                : null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'hours' => ['nullable', 'numeric', 'min:0', 'max:' . SalaryPool::MAX_HOURS],
            'user_id' => ['nullable', 'integer'],
        ]);
        $viewer = $request->user();
        $target = $this->target($request, $data['user_id'] ?? null);
        $asCashier = $target->id !== $viewer->id;
        $date = CarbonImmutable::parse($data['date']);
        $existing = WorkingHours::query()->where('user_id', $target->id)->whereDate('working_day', $date->toDateString())->first();

        $allowed = $asCashier ? $this->pool->canCashierEdit($date, $existing) : $this->pool->canSelfEdit($date, $existing);
        if (! $allowed) {
            $locked = $existing && ! in_array($existing->source, $asCashier ? [WorkingHours::SOURCE_SELF, WorkingHours::SOURCE_CASHIER] : [WorkingHours::SOURCE_SELF], true);

            return response()->json([
                'message' => $locked
                    ? ($existing->source === WorkingHours::SOURCE_CASHIER
                        ? 'Години за цей день уже вніс касир — зверніться до нього, щоб змінити.'
                        : 'Години за цей день уже вніс адміністратор — зверніться до нього, щоб змінити.')
                    : 'Можна вносити години лише з ' . $this->pool->selfEditFrom()->format('d.m') . ' і не наперед.',
            ], 422);
        }

        $hours = isset($data['hours']) ? (float) $data['hours'] : null;
        $this->pool->setHours($target->id, $date, $hours, $asCashier ? WorkingHours::SOURCE_CASHIER : WorkingHours::SOURCE_SELF, $viewer->id);

        return $this->index($request->merge(['month' => $date->format('Y-m'), 'user_id' => $target->id]));
    }

    /**
     * Зарплата працівника за місяць з повного розрахунку (частка залежить від годин колег,
     * але назовні віддаємо лише власні суми).
     *
     * @return array{cells: array<string, array{pay: float}>, pay: float, perHour: float}|null
     */
    private function ownPay(User $user, CarbonImmutable $month): ?array
    {
        $employee = collect($this->pool->month($month)['employees'])->firstWhere('id', $user->id);

        return $employee ? ['cells' => $employee['cells'], 'pay' => $employee['pay'], 'perHour' => $employee['perHour']] : null;
    }

    /** Чиї години: свої або (лише касиру) обраного працівника. */
    private function target(Request $request, ?int $userId): User
    {
        $viewer = $request->user();
        if ($userId === null || $userId === $viewer->id) {
            return $viewer;
        }
        abort_unless($viewer->canManageStaffHours(), 403, 'Вносити години іншим може лише касир.');

        return User::query()->findOrFail($userId);
    }
}
