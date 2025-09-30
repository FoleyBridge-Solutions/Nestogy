<?php

namespace Database\Seeders\Dev;

use App\Models\Company;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating vendors for each company...');

        $companies = Company::all();

        foreach ($companies as $company) {
            $this->createVendorsForCompany($company);
        }

        $this->command->info('Vendors created successfully.');
    }

    /**
     * Create vendors for a specific company
     */
    private function createVendorsForCompany(Company $company): void
    {
        $this->command->info("  Creating vendors for {$company->name}...");

        $vendors = [
            // Major technology vendors
            [
                'name' => 'Microsoft Corporation',
                'description' => 'Software licensing and cloud services',
                'contact_name' => 'Microsoft Support',
                'phone' => '1-800-642-7676',
                'email' => 'support@microsoft.com',
                'website' => 'https://www.microsoft.com',
                'hours' => '24/7 Support',
                'sla' => '4 hour response time',
                'account_number' => 'MS-'.str_pad($company->id, 6, '0', STR_PAD_LEFT),
                'notes' => 'Primary software vendor for Office 365 and Azure services',
            ],
            [
                'name' => 'Dell Technologies',
                'description' => 'Hardware supplier - servers, desktops, laptops',
                'contact_name' => 'Dell ProSupport',
                'phone' => '1-800-999-3355',
                'email' => 'prosupport@dell.com',
                'website' => 'https://www.dell.com',
                'hours' => 'Mon-Fri 8am-6pm EST',
                'sla' => 'Next business day onsite',
                'account_number' => 'DELL-'.str_pad($company->id, 8, '0', STR_PAD_LEFT),
                'notes' => 'Preferred hardware vendor with volume discount agreement',
            ],
            [
                'name' => 'HP Inc.',
                'description' => 'Printers and workstations',
                'contact_name' => 'HP Business Support',
                'phone' => '1-888-999-4747',
                'email' => 'business@hp.com',
                'website' => 'https://www.hp.com',
                'hours' => 'Mon-Fri 8am-8pm EST',
                'sla' => 'Next business day parts',
                'account_number' => 'HP-'.str_pad($company->id, 7, '0', STR_PAD_LEFT),
                'notes' => 'Printer leasing and maintenance agreement',
            ],
            [
                'name' => 'Cisco Systems',
                'description' => 'Network infrastructure and security',
                'contact_name' => 'Cisco TAC',
                'phone' => '1-800-553-2447',
                'email' => 'tac@cisco.com',
                'website' => 'https://www.cisco.com',
                'hours' => '24/7 Support',
                'sla' => '2 hour critical response',
                'account_number' => 'CSCO-'.str_pad($company->id, 9, '0', STR_PAD_LEFT),
                'notes' => 'Enterprise networking and Meraki cloud solutions',
            ],
            [
                'name' => 'Amazon Web Services',
                'description' => 'Cloud computing and storage',
                'contact_name' => 'AWS Support',
                'phone' => '1-866-216-2210',
                'email' => 'support@aws.amazon.com',
                'website' => 'https://aws.amazon.com',
                'hours' => '24/7 Support',
                'sla' => 'Business support plan',
                'account_number' => 'AWS-'.str_pad($company->id, 10, '0', STR_PAD_LEFT),
                'notes' => 'Cloud hosting for client applications',
            ],
            [
                'name' => 'CDW Corporation',
                'description' => 'IT solutions and services reseller',
                'contact_name' => 'CDW Account Team',
                'phone' => '1-800-800-4239',
                'email' => 'customerservice@cdw.com',
                'website' => 'https://www.cdw.com',
                'hours' => 'Mon-Fri 7am-7pm CST',
                'sla' => 'Same day quote response',
                'account_number' => 'CDW-'.str_pad($company->id, 8, '0', STR_PAD_LEFT),
                'notes' => 'Primary technology reseller and procurement partner',
            ],
            [
                'name' => 'Ingram Micro',
                'description' => 'Technology distributor',
                'contact_name' => 'Customer Care',
                'phone' => '1-800-456-8000',
                'email' => 'customercare@ingrammicro.com',
                'website' => 'https://www.ingrammicro.com',
                'hours' => 'Mon-Fri 8am-8pm EST',
                'sla' => 'Next day shipping',
                'account_number' => 'IM-'.str_pad($company->id, 9, '0', STR_PAD_LEFT),
                'notes' => 'Secondary distributor for hard-to-find items',
            ],
            [
                'name' => 'Tech Data Corporation',
                'description' => 'IT products and services distributor',
                'contact_name' => 'Tech Data Support',
                'phone' => '1-800-237-8931',
                'email' => 'support@techdata.com',
                'website' => 'https://www.techdata.com',
                'hours' => 'Mon-Fri 8:30am-8pm EST',
                'sla' => 'Same day processing',
                'account_number' => 'TD-'.str_pad($company->id, 8, '0', STR_PAD_LEFT),
                'notes' => 'Specialty software and licensing distributor',
            ],
            [
                'name' => 'SHI International',
                'description' => 'Software and hardware procurement',
                'contact_name' => 'SHI Customer Service',
                'phone' => '1-888-764-8888',
                'email' => 'customerservice@shi.com',
                'website' => 'https://www.shi.com',
                'hours' => 'Mon-Fri 8am-8pm EST',
                'sla' => 'Dedicated account manager',
                'account_number' => 'SHI-'.str_pad($company->id, 7, '0', STR_PAD_LEFT),
                'notes' => 'Government and education contract pricing',
            ],
            [
                'name' => 'Verizon Business',
                'description' => 'Telecom and internet services',
                'contact_name' => 'Business Support',
                'phone' => '1-800-922-0204',
                'email' => 'business@verizon.com',
                'website' => 'https://www.verizon.com/business',
                'hours' => '24/7 Support',
                'sla' => '4 hour circuit restoration',
                'account_number' => 'VZ-'.str_pad($company->id, 10, '0', STR_PAD_LEFT),
                'notes' => 'Primary ISP and MPLS provider',
            ],
            [
                'name' => 'AT&T Business',
                'description' => 'Telecommunications and network services',
                'contact_name' => 'AT&T Business Care',
                'phone' => '1-800-321-2000',
                'email' => 'businesscare@att.com',
                'website' => 'https://www.business.att.com',
                'hours' => '24/7 Support',
                'sla' => 'Premium support tier',
                'account_number' => 'ATT-'.str_pad($company->id, 9, '0', STR_PAD_LEFT),
                'notes' => 'Backup internet and voice services',
            ],
            [
                'name' => 'Lenovo',
                'description' => 'Business computers and servers',
                'contact_name' => 'Lenovo Support',
                'phone' => '1-855-253-6686',
                'email' => 'support@lenovo.com',
                'website' => 'https://www.lenovo.com',
                'hours' => 'Mon-Fri 9am-9pm EST',
                'sla' => 'Premier support contract',
                'account_number' => 'LEN-'.str_pad($company->id, 8, '0', STR_PAD_LEFT),
                'notes' => 'ThinkPad and ThinkServer products',
            ],
        ];

        // Add some local/regional vendors based on company location
        $localVendors = $this->getLocalVendors($company);
        $vendors = array_merge($vendors, $localVendors);

        // Create vendors (randomly select 10-15 for each company)
        $selectedVendors = array_rand($vendors, rand(10, min(15, count($vendors))));
        if (! is_array($selectedVendors)) {
            $selectedVendors = [$selectedVendors];
        }

        foreach ($selectedVendors as $index) {
            $vendorData = $vendors[$index];
            $vendorData['template'] = false;
            $vendorData['company_id'] = $company->id;

            Vendor::create($vendorData);
        }

        $this->command->info('    ✓ Created '.count($selectedVendors).' vendors');
    }

    /**
     * Get local vendors based on company location
     */
    private function getLocalVendors(Company $company): array
    {
        $cityVendors = [
            'New York' => [
                [
                    'name' => 'NYC Tech Solutions',
                    'description' => 'Local IT services and support',
                    'contact_name' => 'Support Team',
                    'phone' => '(212) 555-8100',
                    'email' => 'support@nyctechsolutions.com',
                    'website' => 'https://www.nyctechsolutions.com',
                    'hours' => 'Mon-Fri 8am-6pm EST',
                    'notes' => 'Local break-fix and onsite support',
                ],
                [
                    'name' => 'Manhattan Cable Co',
                    'description' => 'Structured cabling and fiber optics',
                    'contact_name' => 'Installation Team',
                    'phone' => '(212) 555-8200',
                    'email' => 'info@manhattancable.com',
                    'website' => 'https://www.manhattancable.com',
                    'hours' => 'Mon-Sat 7am-5pm EST',
                    'notes' => 'Certified cable installation',
                ],
            ],
            'Chicago' => [
                [
                    'name' => 'Windy City IT',
                    'description' => 'Managed IT services',
                    'contact_name' => 'Service Desk',
                    'phone' => '(312) 555-9100',
                    'email' => 'help@windycityit.com',
                    'website' => 'https://www.windycityit.com',
                    'hours' => 'Mon-Fri 7am-7pm CST',
                    'notes' => 'Local MSP partner for overflow',
                ],
                [
                    'name' => 'Chicago Network Services',
                    'description' => 'Network design and implementation',
                    'contact_name' => 'Network Team',
                    'phone' => '(312) 555-9200',
                    'email' => 'support@chicagonetwork.com',
                    'website' => 'https://www.chicagonetwork.com',
                    'hours' => 'Mon-Fri 8am-6pm CST',
                    'notes' => 'Wireless and network specialists',
                ],
            ],
            'Los Angeles' => [
                [
                    'name' => 'LA Tech Partners',
                    'description' => 'Technology consulting and services',
                    'contact_name' => 'Consulting Team',
                    'phone' => '(310) 555-7100',
                    'email' => 'info@latechpartners.com',
                    'website' => 'https://www.latechpartners.com',
                    'hours' => 'Mon-Fri 8am-6pm PST',
                    'notes' => 'Strategic technology partner',
                ],
                [
                    'name' => 'Pacific Coast Computers',
                    'description' => 'Computer sales and repair',
                    'contact_name' => 'Sales Team',
                    'phone' => '(310) 555-7200',
                    'email' => 'sales@pacificcoastcomputers.com',
                    'website' => 'https://www.pacificcoastcomputers.com',
                    'hours' => 'Mon-Sat 9am-7pm PST',
                    'notes' => 'Local computer retailer',
                ],
            ],
            'Denver' => [
                [
                    'name' => 'Rocky Mountain Tech',
                    'description' => 'IT infrastructure services',
                    'contact_name' => 'Infrastructure Team',
                    'phone' => '(303) 555-6100',
                    'email' => 'support@rockymountaintech.com',
                    'website' => 'https://www.rockymountaintech.com',
                    'hours' => 'Mon-Fri 8am-6pm MST',
                    'notes' => 'Data center and cloud services',
                ],
                [
                    'name' => 'Mile High Networks',
                    'description' => 'Telecommunications provider',
                    'contact_name' => 'NOC Team',
                    'phone' => '(303) 555-6200',
                    'email' => 'noc@milehighnetworks.com',
                    'website' => 'https://www.milehighnetworks.com',
                    'hours' => '24/7 Support',
                    'notes' => 'Regional ISP and voice provider',
                ],
            ],
        ];

        // Default vendors for San Francisco or unknown cities
        $defaultVendors = [
            [
                'name' => 'Bay Area Tech Supply',
                'description' => 'Technology equipment supplier',
                'contact_name' => 'Sales Department',
                'phone' => '(415) 555-5100',
                'email' => 'sales@bayareatechsupply.com',
                'website' => 'https://www.bayareatechsupply.com',
                'hours' => 'Mon-Fri 8am-6pm PST',
                'notes' => 'Local equipment supplier',
            ],
            [
                'name' => 'Silicon Valley Services',
                'description' => 'Premium IT consulting',
                'contact_name' => 'Consulting Team',
                'phone' => '(415) 555-5200',
                'email' => 'info@siliconvalleyservices.com',
                'website' => 'https://www.siliconvalleyservices.com',
                'hours' => 'Mon-Fri 9am-6pm PST',
                'notes' => 'High-end consulting services',
            ],
        ];

        return $cityVendors[$company->city] ?? $defaultVendors;
    }
}
