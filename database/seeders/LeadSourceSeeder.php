<?php

namespace Database\Seeders;

use App\Domains\Lead\Models\LeadSource;
use App\Models\Company;
use Illuminate\Database\Seeder;

class LeadSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all companies to create lead sources for each
        $companies = Company::all();

        if ($companies->isEmpty()) {
            // If no companies exist, create for the default demo company
            $companies = collect([['id' => 1]]);
        }

        $leadSources = [
            [
                'name' => 'Website Contact Form',
                'type' => 'inbound',
                'description' => 'Leads from main website contact form',
                'is_active' => true,
            ],
            [
                'name' => 'Google Ads',
                'type' => 'paid',
                'description' => 'Leads from Google Ads campaigns',
                'is_active' => true,
            ],
            [
                'name' => 'LinkedIn Ads',
                'type' => 'paid',
                'description' => 'Leads from LinkedIn advertising campaigns',
                'is_active' => true,
            ],
            [
                'name' => 'Facebook Ads',
                'type' => 'paid',
                'description' => 'Leads from Facebook and Instagram advertising',
                'is_active' => true,
            ],
            [
                'name' => 'Referral',
                'type' => 'referral',
                'description' => 'Leads referred by existing clients',
                'is_active' => true,
            ],
            [
                'name' => 'Partner Referral',
                'type' => 'referral',
                'description' => 'Leads referred by business partners',
                'is_active' => true,
            ],
            [
                'name' => 'Cold Email',
                'type' => 'outbound',
                'description' => 'Leads from cold email outreach campaigns',
                'is_active' => true,
            ],
            [
                'name' => 'Cold Calling',
                'type' => 'outbound',
                'description' => 'Leads from cold calling efforts',
                'is_active' => true,
            ],
            [
                'name' => 'LinkedIn Outreach',
                'type' => 'outbound',
                'description' => 'Leads from LinkedIn direct messaging',
                'is_active' => true,
            ],
            [
                'name' => 'Trade Show',
                'type' => 'event',
                'description' => 'Leads captured at trade shows and conferences',
                'is_active' => true,
            ],
            [
                'name' => 'Webinar',
                'type' => 'event',
                'description' => 'Leads from educational webinars',
                'is_active' => true,
            ],
            [
                'name' => 'Local Business Event',
                'type' => 'event',
                'description' => 'Leads from local networking events',
                'is_active' => true,
            ],
            [
                'name' => 'Content Download',
                'type' => 'inbound',
                'description' => 'Leads from downloading whitepapers, guides, etc.',
                'is_active' => true,
            ],
            [
                'name' => 'Blog/SEO',
                'type' => 'inbound',
                'description' => 'Organic leads from search engines and blog content',
                'is_active' => true,
            ],
            [
                'name' => 'Directory Listing',
                'type' => 'inbound',
                'description' => 'Leads from business directory listings',
                'is_active' => true,
            ],
            [
                'name' => 'Quote Request',
                'type' => 'inbound',
                'description' => 'Leads requesting service quotes',
                'is_active' => true,
            ],
            [
                'name' => 'Support Inquiry',
                'type' => 'inbound',
                'description' => 'Leads from IT support inquiries',
                'is_active' => true,
            ],
            [
                'name' => 'Business Assessment',
                'type' => 'inbound',
                'description' => 'Leads requesting IT assessments or audits',
                'is_active' => true,
            ],
            [
                'name' => 'Employee Referral',
                'type' => 'referral',
                'description' => 'Leads referred by company employees',
                'is_active' => true,
            ],
            [
                'name' => 'Walk-in',
                'type' => 'inbound',
                'description' => 'Leads who visited office in person',
                'is_active' => true,
            ],
            [
                'name' => 'Direct Phone',
                'type' => 'inbound',
                'description' => 'Leads who called directly',
                'is_active' => true,
            ],
            [
                'name' => 'Chat Widget',
                'type' => 'inbound',
                'description' => 'Leads from website chat widget',
                'is_active' => true,
            ],
            [
                'name' => 'Previous Client',
                'type' => 'existing',
                'description' => 'Previous clients seeking services again',
                'is_active' => true,
            ],
            [
                'name' => 'Other',
                'type' => 'other',
                'description' => 'Other lead sources not listed above',
                'is_active' => true,
            ],
        ];

        // Create lead sources for each company
        foreach ($companies as $company) {
            $companyId = is_array($company) ? $company['id'] : $company->id;

            foreach ($leadSources as $sourceData) {
                // Add company_id to the source data
                $sourceData['company_id'] = $companyId;

                // Only create if it doesn't exist to avoid duplicates when reseeding
                LeadSource::firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'name' => $sourceData['name'],
                    ],
                    $sourceData
                );
            }
        }
    }
}
