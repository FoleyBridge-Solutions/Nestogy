<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class UpdatePerUserPricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update existing plans to per-user pricing model
        $plans = [
            [
                'name' => 'starter',
                'stripe_price_id' => 'price_starter_per_user_monthly',
                'price_monthly' => 0, // Legacy field, keeping for compatibility
                'price_per_user_monthly' => 29.00,
                'pricing_model' => SubscriptionPlan::PRICING_PER_USER,
                'minimum_users' => 1,
                'base_price' => 0,
                'user_limit' => 10, // Soft limit for starter
                'description' => 'Perfect for small MSPs getting started',
                'features' => [
                    'core_psa_features',
                    'basic_ticketing',
                    'client_management', 
                    'basic_invoicing',
                    'email_support',
                    'api_access',
                ],
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'professional',
                'stripe_price_id' => 'price_professional_per_user_monthly',
                'price_monthly' => 0, // Legacy field, keeping for compatibility
                'price_per_user_monthly' => 45.00,
                'pricing_model' => SubscriptionPlan::PRICING_PER_USER,
                'minimum_users' => 2,
                'base_price' => 0,
                'user_limit' => null, // Unlimited
                'description' => 'Advanced features for growing MSPs',
                'features' => [
                    'core_psa_features',
                    'advanced_ticketing',
                    'client_management',
                    'advanced_invoicing',
                    'project_management',
                    'asset_tracking',
                    'contract_management',
                    'priority_support',
                    'api_access',
                    'integrations',
                    'automated_workflows',
                ],
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'enterprise',
                'stripe_price_id' => 'price_enterprise_per_user_monthly',
                'price_monthly' => 0, // Legacy field, keeping for compatibility
                'price_per_user_monthly' => 65.00,
                'pricing_model' => SubscriptionPlan::PRICING_PER_USER,
                'minimum_users' => 5,
                'base_price' => 0,
                'user_limit' => null, // Unlimited
                'description' => 'Full-featured platform for enterprise MSPs',
                'features' => [
                    'core_psa_features',
                    'advanced_ticketing',
                    'client_management',
                    'advanced_invoicing',
                    'project_management',
                    'asset_tracking',
                    'contract_management',
                    'voip_tax_engine',
                    'advanced_reporting',
                    'custom_branding',
                    'dedicated_support',
                    'api_access',
                    'integrations',
                    'automated_workflows',
                    'white_label',
                    'sla_guarantees',
                    'custom_features',
                ],
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $planData) {
            $plan = SubscriptionPlan::where('name', $planData['name'])->first();
            
            if ($plan) {
                // Update existing plan
                $plan->update($planData);
                $this->command->info("Updated {$planData['name']} plan to per-user pricing: $" . $planData['price_per_user_monthly'] . "/user/month");
            } else {
                // Create new plan if it doesn't exist
                SubscriptionPlan::create($planData);
                $this->command->info("Created {$planData['name']} plan with per-user pricing: $" . $planData['price_per_user_monthly'] . "/user/month");
            }
        }
        
        // Remove the Free plan as it doesn't fit per-user model well
        SubscriptionPlan::where('name', 'Free')->delete();
        $this->command->info('Removed Free plan (not suitable for per-user model)');
        
        $this->command->info('Per-user pricing update completed!');
        $this->command->info('New pricing: Starter $29/user, Professional $45/user, Enterprise $65/user');
    }
}