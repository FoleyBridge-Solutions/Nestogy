<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('taxes')->insert([
            [
                'id' => 1,
                'company_id' => 1,
                'name' => 'Sales Tax',
                'percent' => 8.25,
                'created_at' => now(),
                'updated_at' => now(),
                'archived_at' => null,
            ],
            [
                'id' => 2,
                'company_id' => 1,
                'name' => 'VAT',
                'percent' => 20.00,
                'created_at' => now(),
                'updated_at' => now(),
                'archived_at' => null,
            ],
            [
                'id' => 3,
                'company_id' => 1,
                'name' => 'GST',
                'percent' => 10.00,
                'created_at' => now(),
                'updated_at' => now(),
                'archived_at' => null,
            ],
            [
                'id' => 4,
                'company_id' => 1,
                'name' => 'State Tax',
                'percent' => 6.00,
                'created_at' => now(),
                'updated_at' => now(),
                'archived_at' => null,
            ],
            [
                'id' => 5,
                'company_id' => 1,
                'name' => 'Tax Exempt',
                'percent' => 0.00,
                'created_at' => now(),
                'updated_at' => now(),
                'archived_at' => null,
            ],
        ]);
    }
}
