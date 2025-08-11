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
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for small teams getting started with business management',
                'price_monthly' => 29.00,
                'price_yearly' => 290.00,
                'stripe_price_id' => 'price_starter_monthly', // Update with actual Stripe price ID
                'stripe_price_id_yearly' => 'price_starter_yearly',
                'max_users' => 5,
                'max_clients' => 100,
                'features' => [
                    'basic_ticketing',
                    'client_management', 
                    'basic_invoicing',
                    'email_support',
                    '5_users_included',
                    'basic_reporting'
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Ideal for growing businesses that need advanced features',
                'price_monthly' => 79.00,
                'price_yearly' => 790.00,
                'stripe_price_id' => 'price_professional_monthly',
                'stripe_price_id_yearly' => 'price_professional_yearly',
                'max_users' => 25,
                'max_clients' => 500,
                'features' => [
                    'advanced_ticketing',
                    'client_management',
                    'advanced_invoicing',
                    'project_management',
                    'asset_tracking',
                    'priority_support',
                    'api_access',
                    '25_users_included',
                    'advanced_reporting',
                    'automation_rules',
                    'custom_fields'
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Complete solution for large organizations with complex needs',
                'price_monthly' => 199.00,
                'price_yearly' => 1990.00,
                'stripe_price_id' => 'price_enterprise_monthly',
                'stripe_price_id_yearly' => 'price_enterprise_yearly',
                'max_users' => null, // Unlimited
                'max_clients' => null, // Unlimited
                'features' => [
                    'full_platform_access',
                    'unlimited_users',
                    'unlimited_clients',
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
                    'audit_logging'
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
        $this->command->warn('Remember to update Stripe price IDs in your subscription plans after creating them in Stripe dashboard.');
    }
}