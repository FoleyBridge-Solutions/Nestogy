<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = [
            [
                'company_id' => 1,
                'name' => 'Acme Corporation',
                'company_name' => 'Acme Corp',
                'type' => 'company',
                'email' => 'contact@acmecorp.com',
                'phone' => '555-0101',
                'address' => '123 Business Ave',
                'city' => 'New York',
                'state' => 'NY',
                'zip_code' => '10001',
                'country' => 'United States',
                'website' => 'https://acmecorp.example.com',
                'status' => 'active',
                'lead' => false,
                'hourly_rate' => 150.00,
                'notes' => 'Premium client with 24/7 support contract',
            ],
            [
                'company_id' => 1,
                'name' => 'Tech Startup Inc',
                'company_name' => 'Tech Startup Inc',
                'type' => 'company',
                'type' => 'company',
                'email' => 'info@techstartup.com',
                'phone' => '555-0102',
                'address' => '456 Innovation Drive',
                'city' => 'San Francisco',
                'state' => 'CA',
                'zip_code' => '94105',
                'country' => 'United States',
                'website' => 'https://techstart.example.com',
                'status' => 'active',
                'lead' => false,
                'hourly_rate' => 125.00,
                'notes' => 'Growing startup, monthly maintenance',
            ],
            [
                'company_id' => 1,
                'name' => 'Global Logistics Co',
                'company_name' => 'Global Logistics',
                'type' => 'company',
                'email' => 'support@globallogistics.com',
                'phone' => '555-0103',
                'address' => '789 Shipping Blvd',
                'city' => 'Chicago',
                'state' => 'IL',
                'zip_code' => '60601',
                'country' => 'United States',
                'website' => 'https://globallogistics.example.com',
                'status' => 'active',
                'lead' => false,
                'hourly_rate' => 100.00,
                'notes' => 'Large enterprise client',
            ],
            [
                'company_id' => 1,
                'name' => 'Healthcare Partners',
                'company_name' => 'Healthcare Partners LLC',
                'type' => 'company',
                'email' => 'it@healthcarepartners.com',
                'phone' => '555-0104',
                'address' => '321 Medical Plaza',
                'city' => 'Boston',
                'state' => 'MA',
                'zip_code' => '02108',
                'country' => 'United States',
                'status' => 'active',
                'lead' => false,
                'hourly_rate' => 175.00,
                'notes' => 'HIPAA compliance required',
            ],
            [
                'company_id' => 1,
                'name' => 'Retail Solutions Ltd',
                'company_name' => 'Retail Solutions',
                'type' => 'company',
                'email' => 'admin@retailsolutions.com',
                'phone' => '555-0105',
                'address' => '555 Commerce Street',
                'city' => 'Dallas',
                'state' => 'TX',
                'zip_code' => '75201',
                'country' => 'United States',
                'status' => 'inactive',
                'lead' => false,
                'hourly_rate' => 90.00,
                'notes' => 'Currently on hold',
            ],
            [
                'company_id' => 1,
                'name' => 'Future Tech Innovations',
                'company_name' => 'Future Tech',
                'type' => 'company',
                'email' => 'contact@futuretech.com',
                'phone' => '555-0106',
                'city' => 'Austin',
                'state' => 'TX',
                'zip_code' => '78701',
                'country' => 'United States',
                'status' => 'active',
                'lead' => true, // This is a lead, not a customer yet
                'notes' => 'Potential client - in negotiation',
            ],
        ];

        foreach ($clients as $clientData) {
            Client::firstOrCreate(
                ['email' => $clientData['email']],
                $clientData
            );
        }
    }
}