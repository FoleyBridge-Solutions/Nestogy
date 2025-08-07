<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('companies')->insert([
            [
                'id' => 1,
                'name' => 'Nestogy Demo Company',
                'address' => '123 Business Street',
                'city' => 'Business City',
                'state' => 'NY',
                'zip' => '12345',
                'country' => 'US',
                'phone' => '+1 (555) 123-4567',
                'email' => 'admin@nestogy.com',
                'website' => 'https://nestogy.com',
                'locale' => 'en_US',
                'currency' => 'USD',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Test Client Company',
                'address' => '456 Client Avenue',
                'city' => 'Client City',
                'state' => 'CA',
                'zip' => '67890',
                'country' => 'US',
                'phone' => '+1 (555) 987-6543',
                'email' => 'contact@testclient.com',
                'website' => 'https://testclient.com',
                'locale' => 'en_US',
                'currency' => 'USD',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}