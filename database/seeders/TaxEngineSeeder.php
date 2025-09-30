<?php

namespace Database\Seeders;

use App\Models\ServiceTaxRate;
use App\Models\TaxJurisdiction;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TaxEngineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding tax jurisdictions...');
        $this->seedTaxJurisdictions();

        $this->command->info('Seeding service tax rates...');
        $this->seedServiceTaxRates();

        $this->command->info('Tax engine seeding completed!');
    }

    /**
     * Seed basic tax jurisdictions for major US states
     */
    private function seedTaxJurisdictions(): void
    {
        $jurisdictions = [
            // Federal jurisdiction
            [
                'company_id' => 1,
                'jurisdiction_type' => 'federal',
                'name' => 'United States Federal',
                'code' => 'US-FED',
                'authority_name' => 'Federal Communications Commission',
                'website' => 'https://www.fcc.gov',
                'is_active' => true,
                'priority' => 10,
            ],

            // Major state jurisdictions
            [
                'company_id' => 1,
                'jurisdiction_type' => 'state',
                'name' => 'California',
                'code' => 'US-CA',
                'state_code' => 'CA',
                'authority_name' => 'California Public Utilities Commission',
                'website' => 'https://www.cpuc.ca.gov',
                'is_active' => true,
                'priority' => 20,
            ],
            [
                'company_id' => 1,
                'jurisdiction_type' => 'state',
                'name' => 'Texas',
                'code' => 'US-TX',
                'state_code' => 'TX',
                'authority_name' => 'Public Utility Commission of Texas',
                'website' => 'https://www.puc.texas.gov',
                'is_active' => true,
                'priority' => 20,
            ],
            [
                'company_id' => 1,
                'jurisdiction_type' => 'state',
                'name' => 'New York',
                'code' => 'US-NY',
                'state_code' => 'NY',
                'authority_name' => 'New York State Public Service Commission',
                'website' => 'https://www.dps.ny.gov',
                'is_active' => true,
                'priority' => 20,
            ],
            [
                'company_id' => 1,
                'jurisdiction_type' => 'state',
                'name' => 'Florida',
                'code' => 'US-FL',
                'state_code' => 'FL',
                'authority_name' => 'Florida Public Service Commission',
                'website' => 'https://www.floridapsc.com',
                'is_active' => true,
                'priority' => 20,
            ],
            [
                'company_id' => 1,
                'jurisdiction_type' => 'state',
                'name' => 'Illinois',
                'code' => 'US-IL',
                'state_code' => 'IL',
                'authority_name' => 'Illinois Commerce Commission',
                'website' => 'https://www.icc.illinois.gov',
                'is_active' => true,
                'priority' => 20,
            ],
        ];

        foreach ($jurisdictions as $jurisdiction) {
            TaxJurisdiction::firstOrCreate(
                ['code' => $jurisdiction['code']],
                $jurisdiction
            );
        }
    }

    /**
     * Seed basic service tax rates for telecom services
     */
    private function seedServiceTaxRates(): void
    {
        // Get jurisdiction IDs
        $federalJurisdiction = TaxJurisdiction::where('code', 'US-FED')->first();
        $caJurisdiction = TaxJurisdiction::where('code', 'US-CA')->first();
        $txJurisdiction = TaxJurisdiction::where('code', 'US-TX')->first();
        $nyJurisdiction = TaxJurisdiction::where('code', 'US-NY')->first();
        $flJurisdiction = TaxJurisdiction::where('code', 'US-FL')->first();
        $ilJurisdiction = TaxJurisdiction::where('code', 'US-IL')->first();

        $effectiveDate = Carbon::now();

        $taxRates = [
            // Federal Taxes (apply to all VoIP/telecom services)
            [
                'company_id' => 1,
                'tax_jurisdiction_id' => $federalJurisdiction->id,
                'service_type' => 'voip',
                'tax_type' => 'federal',
                'tax_name' => 'Federal Excise Tax',
                'authority_name' => 'Internal Revenue Service',
                'tax_code' => 'FET-COMM',
                'description' => 'Federal excise tax on communications services',
                'regulatory_code' => null,
                'rate_type' => 'percentage',
                'percentage_rate' => 3.0000, // 3%
                'calculation_method' => 'standard',
                'is_active' => true,
                'priority' => 10,
                'effective_date' => $effectiveDate,
            ],
            [
                'company_id' => 1,
                'tax_jurisdiction_id' => $federalJurisdiction->id,
                'service_type' => 'voip',
                'tax_type' => 'regulatory',
                'tax_name' => 'Universal Service Fund',
                'authority_name' => 'Federal Communications Commission',
                'tax_code' => 'USF',
                'description' => 'Federal Universal Service Fund contribution',
                'regulatory_code' => 'usf',
                'rate_type' => 'percentage',
                'percentage_rate' => 18.4000, // 18.4% (Q4 2024 rate)
                'calculation_method' => 'standard',
                'is_active' => true,
                'priority' => 20,
                'effective_date' => $effectiveDate,
            ],

            // California State Taxes
            [
                'company_id' => 1,
                'tax_jurisdiction_id' => $caJurisdiction->id,
                'service_type' => 'voip',
                'tax_type' => 'state',
                'tax_name' => 'California State Telecommunications Tax',
                'authority_name' => 'California Public Utilities Commission',
                'tax_code' => 'CA-TELECOM',
                'description' => 'California state tax on telecommunications services',
                'rate_type' => 'percentage',
                'percentage_rate' => 6.2500, // 6.25%
                'calculation_method' => 'standard',
                'is_active' => true,
                'priority' => 30,
                'effective_date' => $effectiveDate,
            ],
            [
                'company_id' => 1,
                'tax_jurisdiction_id' => $caJurisdiction->id,
                'service_type' => 'voip',
                'tax_type' => 'regulatory',
                'tax_name' => 'E911 Service Fee',
                'authority_name' => 'California Public Utilities Commission',
                'tax_code' => 'CA-E911',
                'description' => 'Enhanced 911 emergency service fee',
                'regulatory_code' => 'e911',
                'rate_type' => 'per_line',
                'fixed_amount' => 0.7500, // $0.75 per line
                'calculation_method' => 'standard',
                'is_active' => true,
                'priority' => 40,
                'effective_date' => $effectiveDate,
            ],
            [
                'company_id' => 1,
                'tax_jurisdiction_id' => $caJurisdiction->id,
                'service_type' => 'voip',
                'tax_type' => 'regulatory',
                'tax_name' => 'California LifeLine Surcharge',
                'authority_name' => 'California Public Utilities Commission',
                'tax_code' => 'CA-LIFELINE',
                'description' => 'California LifeLine program surcharge',
                'rate_type' => 'percentage',
                'percentage_rate' => 0.1100, // 0.11%
                'calculation_method' => 'standard',
                'is_active' => true,
                'priority' => 50,
                'effective_date' => $effectiveDate,
            ],

            // Texas State Taxes
            [
                'company_id' => 1,
                'tax_jurisdiction_id' => $txJurisdiction->id,
                'service_type' => 'voip',
                'tax_type' => 'state',
                'tax_name' => 'Texas State Telecommunications Tax',
                'authority_name' => 'Public Utility Commission of Texas',
                'tax_code' => 'TX-TELECOM',
                'description' => 'Texas state tax on telecommunications services',
                'rate_type' => 'percentage',
                'percentage_rate' => 3.3000, // 3.3%
                'calculation_method' => 'standard',
                'is_active' => true,
                'priority' => 30,
                'effective_date' => $effectiveDate,
            ],
            [
                'company_id' => 1,
                'tax_jurisdiction_id' => $txJurisdiction->id,
                'service_type' => 'voip',
                'tax_type' => 'regulatory',
                'tax_name' => 'E911 Service Fee',
                'authority_name' => 'Public Utility Commission of Texas',
                'tax_code' => 'TX-E911',
                'description' => 'Enhanced 911 emergency service fee',
                'regulatory_code' => 'e911',
                'rate_type' => 'per_line',
                'fixed_amount' => 0.5000, // $0.50 per line
                'calculation_method' => 'standard',
                'is_active' => true,
                'priority' => 40,
                'effective_date' => $effectiveDate,
            ],

            // New York State Taxes
            [
                'company_id' => 1,
                'tax_jurisdiction_id' => $nyJurisdiction->id,
                'service_type' => 'voip',
                'tax_type' => 'state',
                'tax_name' => 'New York State Telecommunications Tax',
                'authority_name' => 'New York State Public Service Commission',
                'tax_code' => 'NY-TELECOM',
                'description' => 'New York state tax on telecommunications services',
                'rate_type' => 'percentage',
                'percentage_rate' => 1.6600, // 1.66%
                'calculation_method' => 'standard',
                'is_active' => true,
                'priority' => 30,
                'effective_date' => $effectiveDate,
            ],
            [
                'company_id' => 1,
                'tax_jurisdiction_id' => $nyJurisdiction->id,
                'service_type' => 'voip',
                'tax_type' => 'regulatory',
                'tax_name' => 'E911 Service Fee',
                'authority_name' => 'New York State Public Service Commission',
                'tax_code' => 'NY-E911',
                'description' => 'Enhanced 911 emergency service fee',
                'regulatory_code' => 'e911',
                'rate_type' => 'per_line',
                'fixed_amount' => 1.0000, // $1.00 per line
                'calculation_method' => 'standard',
                'is_active' => true,
                'priority' => 40,
                'effective_date' => $effectiveDate,
            ],

            // Add telecom service type variations
            [
                'company_id' => 1,
                'tax_jurisdiction_id' => $federalJurisdiction->id,
                'service_type' => 'telecom',
                'tax_type' => 'federal',
                'tax_name' => 'Federal Excise Tax',
                'authority_name' => 'Internal Revenue Service',
                'tax_code' => 'FET-COMM',
                'description' => 'Federal excise tax on communications services',
                'rate_type' => 'percentage',
                'percentage_rate' => 3.0000, // 3%
                'calculation_method' => 'standard',
                'is_active' => true,
                'priority' => 10,
                'effective_date' => $effectiveDate,
            ],
            [
                'company_id' => 1,
                'tax_jurisdiction_id' => $federalJurisdiction->id,
                'service_type' => 'telecom',
                'tax_type' => 'regulatory',
                'tax_name' => 'Universal Service Fund',
                'authority_name' => 'Federal Communications Commission',
                'tax_code' => 'USF',
                'description' => 'Federal Universal Service Fund contribution',
                'regulatory_code' => 'usf',
                'rate_type' => 'percentage',
                'percentage_rate' => 18.4000, // 18.4%
                'calculation_method' => 'standard',
                'is_active' => true,
                'priority' => 20,
                'effective_date' => $effectiveDate,
            ],
        ];

        foreach ($taxRates as $taxRate) {
            ServiceTaxRate::firstOrCreate(
                [
                    'company_id' => $taxRate['company_id'],
                    'tax_jurisdiction_id' => $taxRate['tax_jurisdiction_id'],
                    'service_type' => $taxRate['service_type'],
                    'tax_code' => $taxRate['tax_code'],
                ],
                $taxRate
            );
        }
    }
}
