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
                'name' => SubscriptionPlan::PLAN_STARTER,
                'stripe_price_id' => 'price_starter_monthly',
                'price_monthly' => 49.00,
                'user_limit' => 10,
                'description' => 'Perfect for small teams getting started with MSP management',
                'features' => json_encode([
                    'core_psa_features',
                    'basic_reporting',
                    'email_support',
                    'api_access',
                ]),
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => SubscriptionPlan::PLAN_PROFESSIONAL,
                'stripe_price_id' => 'price_professional_monthly',
                'price_monthly' => 79.00,
                'user_limit' => null, // Unlimited
                'description' => 'Advanced features for growing MSPs',
                'features' => json_encode([
                    'core_psa_features',
                    'advanced_reporting',
                    'custom_branding',
                    'priority_support',
                    'api_access',
                    'integrations',
                    'automated_workflows',
                ]),
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => SubscriptionPlan::PLAN_ENTERPRISE,
                'stripe_price_id' => 'price_enterprise_monthly',
                'price_monthly' => 149.00,
                'user_limit' => null, // Unlimited
                'description' => 'Full-featured platform for enterprise MSPs',
                'features' => json_encode([
                    'core_psa_features',
                    'advanced_reporting',
                    'custom_branding',
                    'dedicated_support',
                    'api_access',
                    'integrations',
                    'automated_workflows',
                    'white_label',
                    'sla_guarantees',
                    'custom_features',
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