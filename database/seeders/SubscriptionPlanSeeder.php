<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
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
                'description' => 'Perfect for small MSPs just getting started',
                'price_monthly' => 0.00,
                'price_yearly' => 0.00,
                'pricing_model' => 'fixed', // Fixed pricing (free)
                'stripe_price_id' => null, // No Stripe ID for free plan
                'stripe_price_id_yearly' => null,
                'max_users' => 2, // NOT including client portal users
                'max_clients' => 25,
                'features' => [
                    'basic_ticketing',
                    'basic_client_management',
                    'community_support',
                    'basic_reporting',
                    'basic_invoicing',
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Ideal for growing MSPs with expanding teams',
                'price_monthly' => 79.00,
                'price_yearly' => 790.00,
                'pricing_model' => 'fixed', // Fixed pricing
                'stripe_price_id' => env('STRIPE_PRICE_PRO_MONTHLY', 'price_pro_monthly'),
                'stripe_price_id_yearly' => env('STRIPE_PRICE_PRO_YEARLY', 'price_pro_yearly'),
                'max_users' => 10, // NOT including client portal users
                'max_clients' => 250,
                'features' => [
                    'advanced_ticketing',
                    'client_management',
                    'advanced_invoicing',
                    'project_management',
                    'asset_tracking',
                    'priority_support',
                    'advanced_reporting',
                    'automation_rules',
                    'custom_fields',
                    'time_tracking',
                    'contract_management',
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Complete solution for established MSPs with large teams',
                'price_monthly' => 290.00, // Base price for minimum 10 users at $29/user
                'price_yearly' => 2900.00, // Yearly base price
                'price_per_user_monthly' => 29.00, // Per user pricing
                'pricing_model' => 'per_user', // Changed to per-user pricing
                'minimum_users' => 10, // Minimum 10 users required
                'stripe_price_id' => env('STRIPE_PRICE_ENTERPRISE_MONTHLY', 'price_enterprise_monthly'),
                'stripe_price_id_yearly' => env('STRIPE_PRICE_ENTERPRISE_YEARLY', 'price_enterprise_yearly'),
                'max_users' => null, // Unlimited - NOT including client portal users
                'max_clients' => null, // Unlimited
                'features' => [
                    'full_platform_access',
                    'unlimited_clients',
                    'api_access',
                    'advanced_reporting',
                    'custom_integrations',
                    'dedicated_support',
                    'sla_guarantees',
                    'white_labeling',
                    'advanced_security',
                    'priority_feature_requests',
                    'custom_training',
                    'data_export',
                    'advanced_permissions',
                    'audit_logging',
                    'multi_company_support',
                    'custom_workflows',
                ],
                'is_active' => true,
                'sort_order' => 3,
            ]
        ];

        foreach ($plans as $planData) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }

        $this->command->info('Subscription plans seeded successfully!');
        $this->command->comment('Plans configured: Free (2 users), Pro (10 users), Enterprise (unlimited)');
        if (app()->environment() !== 'production') {
            $this->command->warn('Remember to update Stripe price IDs in production!');
        }
    }
}