<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Domains\Contract\Models\Contract;
use App\Domains\Ticket\Models\SLA;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as Faker;

class ContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Contract Seeder...');
        $faker = Faker::create();

        DB::transaction(function () use ($faker) {
            $companies = Company::where('id', '>', 1)->get(); // Skip Nestogy Platform

            foreach ($companies as $company) {
                $this->command->info("Creating contracts for company: {$company->name}");
                
                // Get SLAs for this company
                $slas = SLA::where('company_id', $company->id)->get();
                $platinumSla = $slas->firstWhere('name', 'Platinum SLA');
                $goldSla = $slas->firstWhere('name', 'Gold SLA');
                $silverSla = $slas->firstWhere('name', 'Silver SLA');
                $bronzeSla = $slas->firstWhere('name', 'Bronze SLA');
                
                // Get users for this company
                $users = User::where('company_id', $company->id)->pluck('id')->toArray();
                
                // Get clients for this company grouped by size
                $clients = Client::where('company_id', $company->id)
                    ->where('lead', false)
                    ->get();
                
                foreach ($clients as $client) {
                    $employeeCount = $client->employee_count ?? 10;
                    
                    if ($employeeCount >= 100) {
                        // All enterprise clients get comprehensive managed services
                        $this->createEnterpriseContract($client, $company, $platinumSla ?? $goldSla, $users, $faker);
                    } elseif ($employeeCount >= 20) {
                        // 70% of mid-market clients get standard managed services
                        if ($faker->boolean(70)) {
                            $this->createMidMarketContract($client, $company, $goldSla ?? $silverSla, $users, $faker);
                        }
                    } else {
                        // 30% of small business get basic support contracts
                        if ($faker->boolean(30)) {
                            $this->createSmallBusinessContract($client, $company, $bronzeSla ?? $silverSla, $users, $faker);
                        }
                    }
                }
                
                $this->command->info("Completed contracts for company: {$company->name}");
            }
        });

        $this->command->info('Contract Seeder completed!');
    }

    /**
     * Create enterprise contract
     */
    private function createEnterpriseContract($client, $company, $sla, $users, $faker)
    {
        $startDate = $faker->dateTimeBetween('-2 years', '-6 months');
        $termMonths = 36; // 3-year terms
        $endDate = Carbon::instance($startDate)->addMonths($termMonths);
        $monthlyValue = $faker->randomFloat(2, 5000, 10000);
        
        // Determine status based on dates
        $status = $this->determineContractStatus($endDate, $faker);
        
        Contract::create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'contract_number' => 'CNT-' . strtoupper($faker->bothify('####-????')),
            'contract_type' => 'managed_services',
            'status' => $status,
            'signature_status' => $status === 'active' ? 'signed' : 'pending',
            'title' => 'Comprehensive Managed Services Agreement - ' . $client->name,
            'description' => 'Full IT infrastructure management, 24/7 monitoring, help desk support, and strategic consulting services.',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'term_months' => $termMonths,
            'renewal_type' => 'automatic',
            'renewal_notice_days' => 90,
            'auto_renewal' => true,
            'contract_value' => $monthlyValue * $termMonths,
            'currency_code' => 'USD',
            'payment_terms' => 'Net 30',
            'pricing_structure' => [
                'model' => 'per_asset',
                'monthly_base' => $monthlyValue,
                'included_hours' => 40,
                'overage_rate' => 250,
            ],
            'sla_terms' => [
                'sla_id' => $sla->id ?? null,
                'response_time' => '1 hour',
                'resolution_time' => '4 hours',
                'uptime_guarantee' => '99.9%',
            ],
            'created_by' => !empty($users) ? $faker->randomElement($users) : null,
            'signed_at' => $status === 'active' ? Carbon::instance($startDate)->addDays($faker->numberBetween(1, 14)) : null,
            'executed_at' => $status === 'active' ? Carbon::instance($startDate)->addDays($faker->numberBetween(15, 30)) : null,
        ]);
    }

    /**
     * Create mid-market contract
     */
    private function createMidMarketContract($client, $company, $sla, $users, $faker)
    {
        $startDate = $faker->dateTimeBetween('-2 years', '-3 months');
        $termMonths = $faker->randomElement([12, 24]); // 1-2 year terms
        $endDate = Carbon::instance($startDate)->addMonths($termMonths);
        $monthlyValue = $faker->randomFloat(2, 2000, 5000);
        
        $status = $this->determineContractStatus($endDate, $faker);
        
        Contract::create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'contract_number' => 'CNT-' . strtoupper($faker->bothify('####-????')),
            'contract_type' => 'managed_services',
            'status' => $status,
            'signature_status' => $status === 'active' ? 'signed' : 'pending',
            'title' => 'Standard Managed Services Agreement - ' . $client->name,
            'description' => 'Server and network management, help desk support, and monthly maintenance.',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'term_months' => $termMonths,
            'renewal_type' => 'manual',
            'renewal_notice_days' => 60,
            'auto_renewal' => $faker->boolean(50),
            'contract_value' => $monthlyValue * $termMonths,
            'currency_code' => 'USD',
            'payment_terms' => 'Net 30',
            'pricing_structure' => [
                'model' => 'flat_rate',
                'monthly_fee' => $monthlyValue,
                'included_hours' => 20,
                'overage_rate' => 175,
            ],
            'sla_terms' => [
                'sla_id' => $sla->id ?? null,
                'response_time' => '2 hours',
                'resolution_time' => '8 hours',
                'uptime_guarantee' => '99.5%',
            ],
            'created_by' => !empty($users) ? $faker->randomElement($users) : null,
            'signed_at' => $status === 'active' ? Carbon::instance($startDate)->addDays($faker->numberBetween(1, 7)) : null,
            'executed_at' => $status === 'active' ? Carbon::instance($startDate)->addDays($faker->numberBetween(8, 15)) : null,
        ]);
    }

    /**
     * Create small business contract
     */
    private function createSmallBusinessContract($client, $company, $sla, $users, $faker)
    {
        $startDate = $faker->dateTimeBetween('-18 months', 'now');
        $termMonths = $faker->randomElement([6, 12]); // Monthly or annual terms
        $endDate = Carbon::instance($startDate)->addMonths($termMonths);
        $monthlyValue = $faker->randomFloat(2, 500, 2000);
        
        $status = $this->determineContractStatus($endDate, $faker);
        
        Contract::create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'contract_number' => 'CNT-' . strtoupper($faker->bothify('####-????')),
            'contract_type' => $faker->randomElement(['support', 'break_fix']),
            'status' => $status,
            'signature_status' => $status === 'active' ? 'signed' : 'pending',
            'title' => 'Basic Support Agreement - ' . $client->name,
            'description' => 'Remote support, basic monitoring, and quarterly check-ups.',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'term_months' => $termMonths,
            'renewal_type' => 'manual',
            'renewal_notice_days' => 30,
            'auto_renewal' => false,
            'contract_value' => $monthlyValue * $termMonths,
            'currency_code' => 'USD',
            'payment_terms' => $faker->randomElement(['Net 15', 'Net 30', 'Due on Receipt']),
            'pricing_structure' => [
                'model' => 'block_hours',
                'monthly_hours' => 10,
                'hourly_rate' => 125,
                'rollover' => false,
            ],
            'sla_terms' => [
                'sla_id' => $sla->id ?? null,
                'response_time' => '4 hours',
                'resolution_time' => '48 hours',
                'business_hours_only' => true,
            ],
            'created_by' => !empty($users) ? $faker->randomElement($users) : null,
            'signed_at' => $status === 'active' ? Carbon::instance($startDate)->addDays($faker->numberBetween(1, 3)) : null,
            'executed_at' => $status === 'active' ? Carbon::instance($startDate)->addDays($faker->numberBetween(4, 7)) : null,
        ]);
    }

    /**
     * Determine contract status based on dates
     */
    private function determineContractStatus($endDate, $faker)
    {
        $now = Carbon::now();
        
        if ($endDate < $now) {
            // Contract has expired
            return $faker->randomElement(['expired', 'terminated']);
        } elseif ($endDate < $now->addDays(90)) {
            // Contract expiring soon
            return 'active'; // But marked for renewal
        } else {
            // Active contract
            return 'active';
        }
    }
}