<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        SubscriptionPlan::create([
            'name' => 'Plan Simple',
            'description' => 'Acceso a funciones básicas',
            'price' => 9.99,
            'billing_cycle' => 'MONTHLY',
            'paypal_plan_id' => 'P-XXXXXXXXXXXXX' // ID real de PayPal
        ]);

        SubscriptionPlan::create([
            'name' => 'Plan VIP',
            'description' => 'Acceso a todas las funciones',
            'price' => 19.99,
            'billing_cycle' => 'MONTHLY',
            'paypal_plan_id' => 'P-YYYYYYYYYYYYY' // ID real de PayPal
        ]);

        User::create([
            'name' => 'Admin',
            'email' => 'admin@local.dev',
            'password' => bcrypt('asd123..'),
        ]);
    }
}
