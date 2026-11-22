<?php

namespace App\Http\Controllers;

use App\Models\SalaryDay;
use App\Services\Salary\SalaryPool;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Каса дня з фронту: вносить може кассир (manager) або super admin — за сьогодні і SELF_EDIT_DAYS днів назад.
 * Фонд, години і виплати тут не показуються.
 */
class StaffCashController extends Controller
{
    public function __construct(private readonly SalaryPool $pool)
    {
    }

    public static function allowed($user): bool
    {
        return $user && ($user->isManager() || $user->isSuperAdmin());
    }

    public function index(Request $request): JsonResponse
    {
        abort_unless(self::allowed($request->user()), 403);

        $today = CarbonImmutable::today();
        $from = $today->subDays(SalaryPool::SELF_EDIT_DAYS);
        $cash = SalaryDay::query()
            ->whereDate('date', '>=', $from->toDateString())->whereDate('date', '<=', $today->toDateString())
            ->get()
            ->keyBy(fn (SalaryDay $d) => $d->date->toDateString());

        $days = [];
        for ($date = $today; $date->greaterThanOrEqualTo($from); $date = $date->subDay()) {
            $days[] = [
                'date' => $date->toDateString(),
                'weekday' => $date->dayOfWeekIso,
                'cash' => isset($cash[$date->toDateString()]) ? (float) $cash[$date->toDateString()]->cash : null,
            ];
        }

        return response()->json(['today' => $today->toDateString(), 'editDays' => SalaryPool::SELF_EDIT_DAYS, 'days' => $days]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(self::allowed($request->user()), 403);

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'cash' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
        ]);
        $today = CarbonImmutable::today();
        $date = CarbonImmutable::parse($data['date']);

        if ($date->greaterThan($today) || $date->lessThan($today->subDays(SalaryPool::SELF_EDIT_DAYS))) {
            return response()->json(['message' => 'Касу можна вносити лише за останні ' . SalaryPool::SELF_EDIT_DAYS . ' днів і не наперед.'], 422);
        }

        $cash = isset($data['cash']) && $data['cash'] !== '' ? (float) $data['cash'] : null;
        $this->pool->setCash($date, $cash, $request->user()->id);

        return $this->index($request);
    }
}
