<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'billing_cycle',
        'paypal_plan_id',
        'is_active'
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}