<?php

namespace App\Http\Controllers\User\Plan;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class UserPlanController extends Controller
{

    public function getActivePlan(Request $request)
    {
        $user = $request->user();

        $active = $user->planSubscriptions()
            ->where('status', 'active')
            ->latest('start_date')
            ->first();

        return response()->json($active);
    }

    public function getSubscriptionHistory(Request $request)
    {
        $user = $request->user();

        $subscriptions = $user->planSubscriptions()
            ->latest('start_date')
            ->paginate(10);

        return response()->json($subscriptions);
    }



}
