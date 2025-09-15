<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'stripe_price_id' => 'price_free',
                'price_monthly' => 0.00,
                'user_limit' => 2,
                'pricing_model' => 'fixed',
                'description' => 'Perfect for small MSPs just getting started',
                'features' => json_encode([
                    '2 users included',
                    'Basic ticketing',
                    'Basic client management',
                    'Community support',
                    'Basic reporting',
                    'Basic invoicing',
                ]),
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'stripe_price_id' => 'price_pro_monthly',
                'price_monthly' => 79.00,
                'user_limit' => 10,
                'pricing_model' => 'fixed',
                'description' => 'Ideal for growing MSPs with expanding teams',
                'features' => json_encode([
                    '10 users included',
                    'Advanced ticketing',
                    'Client management',
                    'Advanced invoicing',
                    'Project management',
                    'Asset tracking',
                ]),
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'stripe_price_id' => 'price_enterprise_monthly',
                'price_monthly' => 0.00, // Base price, actual pricing is per-user
                'price_per_user_monthly' => 29.00,
                'pricing_model' => 'per_user',
                'minimum_users' => 10,
                'user_limit' => null, // Unlimited
                'description' => 'Complete solution for established MSPs with large teams',
                'features' => json_encode([
                    'Unlimited users (minimum 10)',
                    'Full platform access',
                    'Unlimited clients',
                    'API access',
                    'Advanced reporting',
                    'Custom integrations',
                ]),
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $planData) {
            SubscriptionPlan::firstOrCreate(
                ['name' => $planData['name']],
                $planData
            );
        }
    }
}