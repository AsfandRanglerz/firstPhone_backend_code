<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriptionPlanRequest;
use App\Http\Requests\UpdateSubscriptionPlanRequest;
use App\Repositories\Interfaces\SubscriptionPlanInterface;

class SubscriptionPlanController extends Controller
{
    protected $subscriptionPlanRepo;

    public function __construct(SubscriptionPlanInterface $subscriptionPlanRepo)
    {
        $this->subscriptionPlanRepo = $subscriptionPlanRepo;
    }

    public function index()
    {
        $subscriptionPlans = $this->subscriptionPlanRepo->all();
        return view('admin.subscription.index', compact('subscriptionPlans'));
    }

    public function create()
    {
        return view('admin.subscription.create');
    }

    public function store(StoreSubscriptionPlanRequest $request)
    {
        $this->subscriptionPlanRepo->create($request->validated());
        return redirect()->route('subscription.index')->with('success', 'Subscription plan created successfully');
    }

    public function edit($id)
    {
        $plan = $this->subscriptionPlanRepo->find($id);
        return view('admin.subscription.edit', compact('plan'));
    }

    public function update(UpdateSubscriptionPlanRequest $request, $id)
    {
        $this->subscriptionPlanRepo->update($id, $request->validated());
        return redirect()->route('subscription.index')->with('success', 'Subscription plan updated successfully');
    }

    public function delete($id)
    {
        $this->subscriptionPlanRepo->delete($id);
        return redirect()->route('subscription.index')->with('success', 'Subscription plan deleted successfully');
    }
}
