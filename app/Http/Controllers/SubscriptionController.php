<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function plans()
    {
        $plans = SubscriptionPlan::where('is_active', true)->get();
        return view('subscriptions.plans', compact('plans'));
    }

    public function list()
    {
        $subscriptions = Subscription::where("user_id", auth()->user()->id)->get();
        return view('subscriptions.list', ["subscriptions"=> $subscriptions]);
    }
}