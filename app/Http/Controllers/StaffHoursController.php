<?php

namespace App\Http\Controllers;

use App\Models\WorkingHours;
use App\Services\Salary\SalaryPool;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * «Мої години»: працівник бачить і вносить лише власні години. Чужих годин і грошей тут немає.
 */
class StaffHoursController extends Controller
{
    public function __construct(private readonly SalaryPool $pool)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $start = isset($data['month']) ? CarbonImmutable::createFromFormat('!Y-m', $data['month']) : CarbonImmutable::today()->startOfMonth();
        $end = $start->endOfMonth();
        $user = $request->user();

        $records = WorkingHours::query()
            ->where('user_id', $user->id)
            ->whereDate('working_day', '>=', $start->toDateString())->whereDate('working_day', '<=', $end->toDateString())
            ->get()
            ->keyBy(fn (WorkingHours $h) => $h->working_day->toDateString());

        $days = [];
        for ($date = $start; $date->lessThanOrEqualTo($end); $date = $date->addDay()) {
            $record = $records->get($date->toDateString());
            $days[] = [
                'date' => $date->toDateString(),
                'day' => $date->day,
                'weekday' => $date->dayOfWeekIso,
                'hours' => $record?->hours,
                'source' => $record?->source,
                'editable' => $this->pool->canSelfEdit($date, $record),
            ];
        }

        return response()->json([
            'name' => $user->name,
            'month' => $start->format('Y-m'),
            'today' => CarbonImmutable::today()->toDateString(),
            'editDays' => SalaryPool::SELF_EDIT_DAYS,
            'editFrom' => $this->pool->selfEditFrom()->toDateString(),
            'totalHours' => round($records->sum('hours'), 2),
            'days' => $days,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'hours' => ['nullable', 'numeric', 'min:0', 'max:' . SalaryPool::MAX_HOURS],
        ]);
        $user = $request->user();
        $date = CarbonImmutable::parse($data['date']);
        $existing = WorkingHours::query()->where('user_id', $user->id)->whereDate('working_day', $date->toDateString())->first();

        if (! $this->pool->canSelfEdit($date, $existing)) {
            return response()->json([
                'message' => $existing && $existing->source !== WorkingHours::SOURCE_SELF
                    ? 'Години за цей день уже вніс адміністратор — зверніться до нього, щоб змінити.'
                    : 'Можна вносити години лише з ' . $this->pool->selfEditFrom()->format('d.m') . ' і не наперед.',
            ], 422);
        }

        $hours = isset($data['hours']) ? (float) $data['hours'] : null;
        $this->pool->setHours($user->id, $date, $hours, WorkingHours::SOURCE_SELF, $user->id);

        return $this->index($request->merge(['month' => $date->format('Y-m')]));
    }
}
