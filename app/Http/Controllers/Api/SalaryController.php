<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Utils\PosterAuthController;
use App\Models\Rate;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkingHours;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    /**
     * @throws Exception
     */
    public function getEmployeesWorkingHours(Request $request): JsonResponse
    {
        $month = Carbon::createFromFormat('Y-m', $request->input('month'));
        if ($month->endOfMonth()->isPast()) {
            $endOfPeriod = true;
        } else {
            $endOfPeriod = false;
        }
        $period = CarbonPeriod::create(
            $month->startOfMonth()->format('Y-m-d'),
            $endOfPeriod ? $month->endOfMonth()->format('Y-m-d') : Carbon::now()->format('Y-m-d')
        );
        $dateFrom = Carbon::parse($month->startOfMonth()->toDateTimeString());
        $dateTo = Carbon::parse($month->endOfMonth()->toDateTimeString());
        $posterUtil = new PosterAuthController();
        $userWithHours = $posterUtil->getUsersWorkingHours($dateFrom, $dateTo);
        $data = [];
        foreach ($userWithHours as $userWithHour) {
            $dailyDetails =  [];
            $totalEarnings = 0;
            foreach ($period as $date) {
                $paymentDetails = $this->calculatePaymentsForUser($date, $userWithHour['id']);
                $totalEarnings += $paymentDetails['earnings'];
                $dailyDetails[] = $paymentDetails;
            }
            $data[] = [
                'id' => $userWithHour['id'],
                'name' => $userWithHour['name'],
                'position' => $userWithHour['roles'][0]['label'],
                'totalHours' => round($userWithHour['worked_time'] / 60, 1),
                'totalEarnings' => round($totalEarnings, 1),
                'showDetails' => false,
                'dailyDetails' => $dailyDetails
            ];
        }
        return response()->json($data);
    }

    /**
     * @throws Exception
     */
    private function calculatePaymentsForUser(Carbon $date, $userId): array
    {
        /** @var User $user */
        /** @var Role $role */
        $user = User::with(['roles', 'workingHours' => function ($query) use ($date) {
            $query->where('working_day', $date->toDateString());
        }])->findOrFail($userId);
        $role = $user->roles->first();
        if (!$role) {
            throw new Exception("User has no roles assigned.");
        }
        $rateObject = $role->rates->where('working_date', $date->toDateString())->first();
        $rateValue = $rateObject ? $rateObject->rate : 0;

        $users = User::getAllWorkers()->whereHas('roles', function ($query) use ($role) {
            $query->where('name', $role->name);
        })->with(['workingHours' => function ($query) use ($date) {
            $query->where('working_day', $date->toDateString());
        }])->get();

        $totalHours = $users->sum(function ($user) {
            return $user->workingHours->sum('hours');
        });
        $userHours = $user->workingHours->sum('hours');
        $coefficient = $totalHours ? $userHours / $totalHours : 0;
        $bonus = $userHours && $totalHours ? $rateValue * $coefficient : 0;
        $payment = $bonus + ($user->rate ?? 0) * $userHours;
        return [
            'date' => $date->toDateString(),
            'rate' => $user->rate,
            'role' => $role->name,
            'hours' => $userHours,
            'bonus' => $bonus,
            'earnings' => round($payment, 1)
        ];
    }

    public function getWorkingHoursByDay(Request $request): void
    {
        $month = Carbon::createFromFormat('Y-m', $request->input('month'));
        if ($month->endOfMonth()->isPast()) {
            $endOfPeriod = true;
        } else {
            $endOfPeriod = false;
        }
        $period = CarbonPeriod::create(
            $month->startOfMonth()->format('Y-m-d'),
            $endOfPeriod ? $month->endOfMonth()->format('Y-m-d') : Carbon::now()->format('Y-m-d')
        );
        foreach ($period as $date) {
            $posterUtil = new PosterAuthController();
            $userWithHours = $posterUtil->getUsersWorkingHours($date, $date);
            foreach ($userWithHours as $userWithHour) {
                WorkingHours::updateOrCreate(
                    [
                        'working_day' => $date,
                        'user_id' => $userWithHour['id'],
                    ],
                    [
                        'hours' => round($userWithHour['worked_time'] / 60, 1)
                    ]
                );
            }
        }
    }
}
