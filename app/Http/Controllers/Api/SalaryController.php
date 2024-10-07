<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rate;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    public function getUsers() {
        /** @var User  $workers */
        $date = Carbon::parse('06-10-2024');
//        $workers = User::getAllWorkers()->get();
        $roles = Role::all();
        foreach ($roles as $role) {
            $salary[] = $this->calculatePayments($date, $role->name);
        }
        dd($salary);
    }

    private function calculatePayments(Carbon $date, $roleName): JsonResponse
    {
        $role = Role::where('name', $roleName)->first();
        $rate = $role->rates->where('working_date', $date->toDateString())->first();
        if (!$rate) {
            return response()->json(['error' => 'No rate found for this date'], 404);
        }

        $users = User::getAllWorkers()->whereHas('roles', function ($query) use ($roleName) {
            $query->where('name', $roleName);
        })->with(['workingHours' => function ($query) use ($date) {
            $query->where('working_day', $date->toDateString());
        }])->get();

        $totalHours = $users->sum(function ($user) {
            return $user->workingHours->sum('hours');
        });

        $payments = $users->mapWithKeys(function ($user) use ($roleName, $rate, $totalHours, $date) {
            $userHours = $user->workingHours->sum('hours');
            $coefficient = $userHours / $totalHours;
            $payment = (($rate->rate * $coefficient) + $user->rate * $userHours);
            return [
                $user->name => $payment,
                'date' => $date->toDateString(),
                'role' => $roleName,
            ];
        });

        return response()->json($payments);
    }
}
