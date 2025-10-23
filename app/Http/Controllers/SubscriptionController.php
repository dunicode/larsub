<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubscriptionPlan;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index()
    {
        $plans = SubscriptionPlan::where('is_active', true)->get();
        return view('subscriptions.index', compact('plans'));
    }
}