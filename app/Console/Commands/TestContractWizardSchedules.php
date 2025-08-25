<?php

namespace App\Console\Commands;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractSchedule;
use App\Domains\Contract\Services\ContractService;
use App\Models\Client;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestContractWizardSchedules extends Command
{
    protected $signature = 'test:contract-wizard-schedules {--reset}';
    protected $description = 'Test contract wizard schedule saving functionality';

    public function handle()
    {
        $this->info('Testing Contract Wizard Schedule Saving Functionality');
        $this->info('='.str_repeat('=', 60));

        // Clean up previous test data if --reset flag is used
        if ($this->option('reset')) {
            $this->cleanupTestData();
        }

        // Get test user and client
        $user = User::first();
        if (!$user) {
            $this->error('No users found. Please create a user first.');
            return 1;
        }

        $client = Client::where('company_id', $user->company_id)->first();
        if (!$client) {
            $this->error('No clients found. Please create a client first.');
            return 1;
        }

        $this->info("Testing with User: {$user->name} (ID: {$user->id})");
        $this->info("Testing with Client: {$client->name} (ID: {$client->id})");
        $this->newLine();

        // Test schedule saving functionality
        $testData = $this->createTestContractData($client);
        
        try {
            $this->info('1. Testing Contract Creation with All Schedule Types...');
            
            // Authenticate as the user for service calls
            auth()->login($user);
            
            // Simulate the contract service
            $contractService = app(ContractService::class);
            
            // Create contract
            $contract = $contractService->createContract($testData);
            
            $this->info("✓ Contract created successfully (ID: {$contract->id})");
            
            // Test schedule creation
            $this->info('2. Validating Schedule Records...');
            $this->validateScheduleRecords($contract);
            
            // Test schedule data integrity
            $this->info('3. Testing Schedule Data Integrity...');
            $this->validateScheduleDataIntegrity($contract);
            
            $this->info('4. Testing Schedule Types and Content...');
            $this->validateScheduleContent($contract);
            
            $this->newLine();
            $this->info('✓ All tests passed successfully!');
            $this->info('Contract wizard schedule saving functionality is working correctly.');
            
        } catch (\Exception $e) {
            $this->error('✗ Test failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            Log::error('Contract wizard test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    private function createTestContractData(Client $client): array
    {
        return [
            'client_id' => $client->id,
            'title' => 'Test Contract - Schedule Validation',
            'description' => 'Comprehensive test of all schedule types',
            'contract_type' => 'service_agreement',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addYear()->format('Y-m-d'),
            'status' => 'draft',
            'contract_value' => 50000.00,
            'billing_cycle' => 'monthly',
            
            // Infrastructure Schedule
            'infrastructure_schedule' => [
                'servers' => [
                    ['name' => 'Web Server', 'type' => 'production', 'specs' => '8GB RAM, 4 CPU'],
                    ['name' => 'Database Server', 'type' => 'production', 'specs' => '16GB RAM, 8 CPU']
                ],
                'network' => [
                    'bandwidth' => '1Gbps',
                    'redundancy' => 'dual',
                    'monitoring' => true
                ],
                'backup' => [
                    'frequency' => 'daily',
                    'retention' => '30 days',
                    'offsite' => true
                ]
            ],
            
            // Pricing Schedule
            'pricing_schedule' => [
                'monthly_fee' => 2500.00,
                'setup_fee' => 1000.00,
                'hourly_rate' => 150.00,
                'discount' => 10,
                'billing_terms' => 'Net 30',
                'services' => [
                    ['name' => 'Managed IT Support', 'price' => 1500.00, 'quantity' => 1],
                    ['name' => 'Cloud Backup', 'price' => 500.00, 'quantity' => 1],
                    ['name' => 'Security Monitoring', 'price' => 500.00, 'quantity' => 1]
                ]
            ],
            
            // Telecom Schedule
            'telecom_schedule' => [
                'phone_system' => [
                    'type' => 'VoIP',
                    'lines' => 25,
                    'features' => ['call_forwarding', 'voicemail', 'conference']
                ],
                'internet' => [
                    'speed' => '500/100 Mbps',
                    'type' => 'fiber',
                    'provider' => 'Business ISP'
                ],
                'mobile' => [
                    'devices' => 10,
                    'plan' => 'unlimited',
                    'management' => 'MDM included'
                ]
            ],
            
            // Hardware Schedule
            'hardware_schedule' => [
                'workstations' => [
                    ['type' => 'Desktop', 'count' => 15, 'specs' => 'Intel i7, 16GB RAM, SSD'],
                    ['type' => 'Laptop', 'count' => 10, 'specs' => 'Intel i5, 8GB RAM, SSD']
                ],
                'networking' => [
                    ['type' => 'Switch', 'count' => 3, 'model' => 'Cisco Catalyst 2960'],
                    ['type' => 'Firewall', 'count' => 1, 'model' => 'SonicWall TZ570']
                ],
                'peripherals' => [
                    ['type' => 'Monitor', 'count' => 25, 'specs' => '24" 1080p'],
                    ['type' => 'Printer', 'count' => 3, 'model' => 'HP LaserJet Pro']
                ]
            ],
            
            // Compliance Schedule
            'compliance_schedule' => [
                'frameworks' => ['SOC2', 'HIPAA', 'PCI-DSS'],
                'audits' => [
                    'frequency' => 'quarterly',
                    'type' => 'internal',
                    'reporting' => 'comprehensive'
                ],
                'training' => [
                    'security_awareness' => 'monthly',
                    'compliance_updates' => 'quarterly',
                    'incident_response' => 'bi-annual'
                ],
                'documentation' => [
                    'policies' => 'up_to_date',
                    'procedures' => 'documented',
                    'evidence' => 'maintained'
                ]
            ]
        ];
    }

    private function validateScheduleRecords(Contract $contract): void
    {
        $schedules = ContractSchedule::where('contract_id', $contract->id)->get();
        
        $this->info("   Found {$schedules->count()} schedule records");
        
        $expectedTypes = ['infrastructure', 'pricing', 'telecom', 'hardware', 'compliance'];
        $foundTypes = $schedules->pluck('schedule_type')->toArray();
        
        foreach ($expectedTypes as $type) {
            if (in_array($type, $foundTypes)) {
                $this->info("   ✓ {$type} schedule created");
            } else {
                throw new \Exception("Missing {$type} schedule record");
            }
        }
        
        if ($schedules->count() !== count($expectedTypes)) {
            throw new \Exception("Expected " . count($expectedTypes) . " schedules, found {$schedules->count()}");
        }
    }

    private function validateScheduleDataIntegrity(Contract $contract): void
    {
        $schedules = ContractSchedule::where('contract_id', $contract->id)->get();
        
        foreach ($schedules as $schedule) {
            // Validate JSON data is properly stored
            $scheduleData = $schedule->schedule_data;
            if (empty($scheduleData)) {
                throw new \Exception("Schedule data is empty for {$schedule->schedule_type}");
            }
            
            // Validate JSON is properly formatted
            if (!is_array($scheduleData)) {
                throw new \Exception("Schedule data is not properly formatted as array for {$schedule->schedule_type}");
            }
            
            $this->info("   ✓ {$schedule->schedule_type} schedule data integrity validated");
        }
    }

    private function validateScheduleContent(Contract $contract): void
    {
        $schedules = ContractSchedule::where('contract_id', $contract->id)->get()->keyBy('schedule_type');
        
        // Validate Infrastructure Schedule
        $infra = $schedules['infrastructure'];
        if (!isset($infra->schedule_data['servers']) || count($infra->schedule_data['servers']) !== 2) {
            throw new \Exception("Infrastructure schedule missing expected server data");
        }
        $this->info("   ✓ Infrastructure schedule content validated");
        
        // Validate Pricing Schedule
        $pricing = $schedules['pricing'];
        if (!isset($pricing->schedule_data['monthly_fee']) || $pricing->schedule_data['monthly_fee'] !== 2500.0) {
            throw new \Exception("Pricing schedule missing expected pricing data");
        }
        $this->info("   ✓ Pricing schedule content validated");
        
        // Validate Telecom Schedule
        $telecom = $schedules['telecom'];
        if (!isset($telecom->schedule_data['phone_system']['lines']) || $telecom->schedule_data['phone_system']['lines'] !== 25) {
            throw new \Exception("Telecom schedule missing expected phone system data");
        }
        $this->info("   ✓ Telecom schedule content validated");
        
        // Validate Hardware Schedule
        $hardware = $schedules['hardware'];
        if (!isset($hardware->schedule_data['workstations']) || count($hardware->schedule_data['workstations']) !== 2) {
            throw new \Exception("Hardware schedule missing expected workstation data");
        }
        $this->info("   ✓ Hardware schedule content validated");
        
        // Validate Compliance Schedule
        $compliance = $schedules['compliance'];
        if (!isset($compliance->schedule_data['frameworks']) || count($compliance->schedule_data['frameworks']) !== 3) {
            throw new \Exception("Compliance schedule missing expected framework data");
        }
        $this->info("   ✓ Compliance schedule content validated");
    }

    private function cleanupTestData(): void
    {
        $this->info('Cleaning up previous test data...');
        
        // Delete test contracts and their schedules
        $testContracts = Contract::where('title', 'LIKE', 'Test Contract - Schedule Validation%')->get();
        
        foreach ($testContracts as $contract) {
            ContractSchedule::where('contract_id', $contract->id)->delete();
            $contract->delete();
        }
        
        $this->info("   Cleaned up {$testContracts->count()} test contracts");
        $this->newLine();
    }
}