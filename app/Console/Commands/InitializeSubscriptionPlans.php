<?php

namespace App\Console\Commands;

use App\Models\SubscriptionPlan;
use Illuminate\Console\Command;

/**
 * InitializeSubscriptionPlans Command
 * 
 * Creates initial subscription plans from the saas.php config file.
 * This is a one-time setup command to populate the database with
 * default plans that can then be managed through the settings interface.
 */
class InitializeSubscriptionPlans extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'subscription-plans:init 
                           {--force : Force creation even if plans already exist}';

    /**
     * The console command description.
     */
    protected $description = 'Initialize subscription plans from config/saas.php default plans';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $existingPlans = SubscriptionPlan::count();

        if ($existingPlans > 0 && !$this->option('force')) {
            $this->warn("Subscription plans already exist ({$existingPlans} found).");
            $this->info('Use --force to override existing plans.');
            return Command::SUCCESS;
        }

        $defaultPlans = config('saas.default_plans', []);

        if (empty($defaultPlans)) {
            $this->error('No default plans found in config/saas.php');
            return Command::FAILURE;
        }

        $this->info('Creating subscription plans from config...');

        $created = 0;
        $updated = 0;

        foreach ($defaultPlans as $key => $planConfig) {
            $planData = [
                'name' => $planConfig['name'],
                'price_monthly' => $planConfig['price_monthly'],
                'user_limit' => $planConfig['max_users'] ?? null,
                'features' => $planConfig['features'] ?? [],
                'description' => $this->generateDescription($planConfig),
                'stripe_price_id' => 'price_' . strtolower($key) . '_monthly', // Placeholder - update in Stripe
                'is_active' => true,
                'sort_order' => $this->getSortOrder($key),
            ];

            $plan = SubscriptionPlan::where('name', $planData['name'])->first();

            if ($plan) {
                $plan->update($planData);
                $updated++;
                $this->line("Updated: {$planData['name']}");
            } else {
                SubscriptionPlan::create($planData);
                $created++;
                $this->line("Created: {$planData['name']}");
            }
        }

        $this->newLine();
        $this->info("Subscription plans initialized successfully!");
        $this->info("Created: {$created} plans");
        $this->info("Updated: {$updated} plans");
        
        $this->newLine();
        $this->warn('Important: Update Stripe Price IDs in the settings interface');
        $this->info('Go to Settings → Billing & Financial → Subscription Plans');

        return Command::SUCCESS;
    }

    /**
     * Generate a description based on plan configuration.
     */
    protected function generateDescription(array $config): string
    {
        $userLimit = isset($config['max_users']) 
            ? "Up to {$config['max_users']} users" 
            : 'Unlimited users';

        $clientLimit = isset($config['max_clients']) 
            ? " and {$config['max_clients']} clients" 
            : '';

        return "Perfect for MSPs needing {$userLimit}{$clientLimit}.";
    }

    /**
     * Get sort order based on plan key.
     */
    protected function getSortOrder(string $key): int
    {
        $order = [
            'free' => 0,
            'starter' => 1,
            'professional' => 2,
            'enterprise' => 3,
        ];

        return $order[$key] ?? 99;
    }
}