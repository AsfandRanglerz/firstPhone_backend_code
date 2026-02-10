<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Api\Interfaces\VendorSubscriptionRepositoryInterface;
use App\Models\SubscriptionPlan;

class VendorSubscriptionController extends Controller
{
    protected $vendorSubscriptionRepo;

    public function __construct(VendorSubscriptionRepositoryInterface $vendorSubscriptionRepo)
    {
        $this->vendorSubscriptionRepo = $vendorSubscriptionRepo;
    }

    public function subscribe(Request $request)
    {
        return $this->vendorSubscriptionRepo->subscribe($request);
    }

    public function current(Request $request)
    {
        return $this->vendorSubscriptionRepo->current($request);
    }

    public function getSubscriptionPlans()
    {
        $plans = SubscriptionPlan::where('is_active', 1)->latest()->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Subscription plans retrieved successfully',
            'data' => $plans
        ], 200);
    }
}
