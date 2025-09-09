<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Domains\Asset\Models\AssetWarranty;
use App\Models\Asset;
use App\Models\Vendor;
use Carbon\Carbon;
use Faker\Factory as Faker;

class AssetWarrantySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting AssetWarranty Seeder...');
        $faker = Faker::create();

        DB::transaction(function () use ($faker) {
            // Get all assets
            $assets = Asset::with(['company', 'vendor'])->get();
            $totalAssets = $assets->count();
            $warrantiesToCreate = (int) ($totalAssets * 0.6); // 60% of assets
            
            $this->command->info("Creating warranties for {$warrantiesToCreate} out of {$totalAssets} assets (60%)");
            
            // Randomly select 60% of assets
            $assetsWithWarranties = $assets->random($warrantiesToCreate);
            
            foreach ($assetsWithWarranties as $asset) {
                // Get vendors for this company
                $vendors = Vendor::where('company_id', $asset->company_id)->pluck('id')->toArray();
                
                // Determine warranty status based on asset age
                $status = $this->determineWarrantyStatus($asset, $faker);
                
                // Create warranty record
                $this->createWarranty($asset, $vendors, $status, $faker);
            }
            
            $this->command->info("Created {$warrantiesToCreate} warranty records");
        });

        $this->command->info('AssetWarranty Seeder completed!');
    }

    /**
     * Determine warranty status based on asset purchase date
     */
    private function determineWarrantyStatus($asset, $faker)
    {
        if (!$asset->warranty_expire) {
            return 'expired';
        }
        
        $daysUntilExpiry = now()->diffInDays($asset->warranty_expire, false);
        
        if ($daysUntilExpiry < 0) {
            // Warranty already expired
            return 'expired';
        } elseif ($daysUntilExpiry <= 90) {
            // Expiring soon (within 3 months)
            return 'active'; // But will be flagged as expiring soon
        } else {
            // Active warranty
            return 'active';
        }
    }

    /**
     * Create warranty record for an asset
     */
    private function createWarranty($asset, $vendors, $status, $faker)
    {
        // Determine warranty type based on asset type and age
        $warrantyType = $this->determineWarrantyType($asset, $faker);
        
        // Calculate warranty dates based on asset purchase date
        $warrantyStartDate = $asset->purchase_date ?? Carbon::now()->subYears(2);
        $warrantyEndDate = $asset->warranty_expire ?? Carbon::instance($warrantyStartDate)->addYears($faker->randomElement([1, 2, 3]));
        
        // Adjust status if warranty has expired
        if ($warrantyEndDate < now()) {
            $status = 'expired';
        }
        
        // Determine warranty provider
        $warrantyProviders = [
            'manufacturer' => [
                'Dell ProSupport', 'HP Care Pack', 'Lenovo Premier Support', 
                'Apple Care+', 'Microsoft Complete', 'Cisco SmartNet'
            ],
            'extended' => [
                'SquareTrade', 'Asurion', 'AllState Protection Plan',
                'Best Buy Total Tech', 'Upsie', 'Worth Ave. Group'
            ],
            'third_party' => [
                'Service Master', 'Tech Support Plus', 'IT Warranty Services',
                'Global Warranty Group', 'AmTrust Warranty'
            ],
            'service_contract' => [
                'Managed Services Agreement', 'Break-Fix Contract', 
                'Preventive Maintenance Agreement', 'On-Site Support Contract'
            ]
        ];
        
        $provider = $faker->randomElement($warrantyProviders[$warrantyType] ?? ['Generic Warranty Provider']);
        
        // Calculate costs based on asset type and warranty type
        $cost = $this->calculateWarrantyCost($asset->type, $warrantyType, $faker);
        $renewalCost = $cost * $faker->randomFloat(2, 0.8, 1.2); // Renewal cost is 80-120% of original
        
        // Determine if warranty has been used for claims
        $claimCount = 0;
        $lastClaimDate = null;
        
        if ($status === 'active' || $status === 'expired') {
            // 20% chance of having claims
            if ($faker->boolean(20)) {
                $claimCount = $faker->numberBetween(1, 3);
                $lastClaimDate = $faker->dateTimeBetween($warrantyStartDate, min($warrantyEndDate, now()));
            }
        }
        
        // Create the warranty record
        AssetWarranty::create([
            'company_id' => $asset->company_id,
            'asset_id' => $asset->id,
            'warranty_start_date' => $warrantyStartDate,
            'warranty_end_date' => $warrantyEndDate,
            'warranty_provider' => $provider,
            'warranty_type' => $warrantyType,
            'terms' => $this->generateWarrantyTerms($warrantyType, $faker),
            'coverage_details' => $this->generateCoverageDetails($asset->type, $warrantyType, $faker),
            'vendor_id' => !empty($vendors) ? $faker->randomElement($vendors) : $asset->vendor_id,
            'cost' => $cost,
            'renewal_cost' => $renewalCost,
            'auto_renewal' => $faker->boolean(30), // 30% have auto-renewal
            'contact_email' => $faker->companyEmail(),
            'contact_phone' => $faker->phoneNumber(),
            'reference_number' => strtoupper($faker->bothify('WRT-####-??????')),
            'notes' => $faker->optional(0.2)->sentence(),
            'status' => $status,
            'claim_count' => $claimCount,
            'last_claim_date' => $lastClaimDate,
            'renewal_reminder_sent' => ($status === 'active' && $warrantyEndDate->diffInDays(now()) <= 30) ? $faker->boolean(50) : false,
        ]);
    }

    /**
     * Determine warranty type based on asset
     */
    private function determineWarrantyType($asset, $faker)
    {
        // Newer assets more likely to have manufacturer warranty
        $ageInYears = $asset->purchase_date ? $asset->purchase_date->diffInYears(now()) : 2;
        
        if ($ageInYears <= 1) {
            // New assets - mostly manufacturer warranties
            $weights = [
                'manufacturer' => 70,
                'extended' => 20,
                'third_party' => 5,
                'service_contract' => 5
            ];
        } elseif ($ageInYears <= 3) {
            // Mid-age assets - mix of extended and manufacturer
            $weights = [
                'manufacturer' => 30,
                'extended' => 40,
                'third_party' => 20,
                'service_contract' => 10
            ];
        } else {
            // Older assets - mostly third-party and service contracts
            $weights = [
                'manufacturer' => 5,
                'extended' => 15,
                'third_party' => 40,
                'service_contract' => 40
            ];
        }
        
        return $this->weightedRandom($weights);
    }

    /**
     * Calculate warranty cost based on asset type
     */
    private function calculateWarrantyCost($assetType, $warrantyType, $faker)
    {
        $baseCosts = [
            'Server' => ['min' => 500, 'max' => 5000],
            'Desktop' => ['min' => 100, 'max' => 500],
            'Laptop' => ['min' => 150, 'max' => 600],
            'Printer' => ['min' => 50, 'max' => 300],
            'Firewall' => ['min' => 300, 'max' => 2000],
            'Router' => ['min' => 200, 'max' => 1000],
            'Switch' => ['min' => 200, 'max' => 1500],
            'Storage' => ['min' => 400, 'max' => 3000],
            'Access Point' => ['min' => 50, 'max' => 200],
            'Other' => ['min' => 50, 'max' => 500]
        ];
        
        $cost = $baseCosts[$assetType] ?? ['min' => 50, 'max' => 500];
        
        // Adjust cost based on warranty type
        $multipliers = [
            'manufacturer' => 1.0,
            'extended' => 1.2,
            'third_party' => 0.8,
            'service_contract' => 1.5
        ];
        
        $multiplier = $multipliers[$warrantyType] ?? 1.0;
        
        return $faker->randomFloat(2, $cost['min'] * $multiplier, $cost['max'] * $multiplier);
    }

    /**
     * Generate warranty terms
     */
    private function generateWarrantyTerms($warrantyType, $faker)
    {
        $terms = [
            'manufacturer' => [
                'Parts and labor included',
                'Next business day on-site service',
                'Phone and online support included',
                'Firmware updates included',
                'Normal wear and tear excluded'
            ],
            'extended' => [
                'Coverage begins after manufacturer warranty expires',
                'Parts and labor included',
                'Depot repair service',
                'No lemon policy - replacement after 3 repairs',
                'Accidental damage not covered'
            ],
            'third_party' => [
                'Certified technician support',
                'Parts covered up to original purchase price',
                'Remote support included',
                'On-site service available for additional fee',
                'Pre-existing conditions not covered'
            ],
            'service_contract' => [
                'Unlimited service calls',
                'Preventive maintenance included',
                'Priority response time',
                'Replacement parts included',
                'Software support included'
            ]
        ];
        
        $selectedTerms = $terms[$warrantyType] ?? $terms['manufacturer'];
        return implode('; ', $faker->randomElements($selectedTerms, $faker->numberBetween(3, 5)));
    }

    /**
     * Generate coverage details
     */
    private function generateCoverageDetails($assetType, $warrantyType, $faker)
    {
        $details = [];
        
        // Base coverage for all warranties
        $details[] = 'Hardware defects and failures';
        
        // Add type-specific coverage
        if (in_array($assetType, ['Server', 'Desktop', 'Laptop'])) {
            $details[] = 'Motherboard, CPU, RAM, and storage components';
            $details[] = 'Power supply and cooling systems';
        }
        
        if (in_array($assetType, ['Firewall', 'Router', 'Switch'])) {
            $details[] = 'Network ports and interfaces';
            $details[] = 'Firmware corruption recovery';
        }
        
        if ($assetType === 'Printer') {
            $details[] = 'Print head and mechanical components';
            $details[] = 'Paper feed mechanisms';
        }
        
        // Add warranty-type specific coverage
        if ($warrantyType === 'extended' || $warrantyType === 'service_contract') {
            $details[] = 'Preventive maintenance visits';
            $details[] = 'Software troubleshooting';
        }
        
        if ($warrantyType === 'manufacturer') {
            $details[] = 'Factory defects';
            $details[] = 'Original manufacturer parts';
        }
        
        // Add exclusions
        $exclusions = [
            'Damage from misuse or neglect',
            'Cosmetic damage',
            'Consumable parts',
            'Third-party modifications'
        ];
        
        $details[] = 'Excludes: ' . implode(', ', $faker->randomElements($exclusions, 2));
        
        return implode('; ', $details);
    }

    /**
     * Weighted random selection
     */
    private function weightedRandom($weights)
    {
        $rand = rand(1, array_sum($weights));
        $cumulative = 0;
        
        foreach ($weights as $key => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $key;
            }
        }
        
        return array_key_first($weights);
    }
}