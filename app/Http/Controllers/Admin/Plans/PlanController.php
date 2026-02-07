<?php

namespace App\Http\Controllers\Admin\Plans;


use App\Models\Plan\Plan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Admin\Plans\AdminPlanStoreRequest;

class PlanController extends Controller
{
    // Fetch all plans (list of plans)
    public function index()
    {
        $plans = Plan::orderBy('created_at', 'desc')->get(); // Get all plans ordered by latest
        return response()->json([
            'plans' => $plans
        ]);
    }

    // Fetch a single plan by ID
    public function show($id)
    {
        $plan = Plan::find($id); // Find plan by ID

        if (!$plan) {
            return response()->json(['message' => 'Plan not found'], 404);
        }

        return response()->json($plan->makeVisible('features'));
    }

    // Create a new plan
    public function store(AdminPlanStoreRequest $request)
    {

    $plan = Plan::create([
        'name' => $request->name,
        'duration' => $request->duration,
        'original_price' => $request->original_price,
        'monthly_price' => $request->monthly_price,
        'discount_percentage' => $request->discount_percentage,
        'features' => $request->features, // stored as JSON array
    ]);

    return response()->json([
        'message' => 'Plan created successfully',
        'plan' => $plan->makeVisible('features'),
    ], 201);
}


    public function update(AdminPlanStoreRequest $request, $id)
{
    $plan = Plan::find($id);

    // Update the plan
    $plan->update([
        'name' => $request->name,
        'duration' => $request->duration,
        'original_price' => $request->original_price,
        'monthly_price' => $request->monthly_price,
        'discount_percentage' => $request->discount_percentage,
        'features' => $request->features,
    ]);

    return response()->json([
        'message' => 'Plan updated successfully',
        'plan' => $plan->makeVisible('features'),
    ]);
}


    // Delete a plan
    public function destroy($id)
    {
        $plan = Plan::find($id); // Find plan by ID

        if (!$plan) {
            return response()->json(['message' => 'Plan not found'], 404);
        }

        $plan->delete(); // Delete the plan
        return response()->json(['message' => 'Plan deleted successfully']);
    }
}
