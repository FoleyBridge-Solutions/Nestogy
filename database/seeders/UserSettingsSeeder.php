<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('user_settings')->insert([
            [
                'id' => 1,
                'user_id' => 1,
                'company_id' => 1,
                'role' => 3, // Admin
                'remember_me_token' => null,
                'force_mfa' => false,
                'records_per_page' => 25,
                'dashboard_financial_enable' => true,
                'dashboard_technical_enable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'user_id' => 2,
                'company_id' => 1,
                'role' => 2, // Tech
                'remember_me_token' => null,
                'force_mfa' => false,
                'records_per_page' => 20,
                'dashboard_financial_enable' => false,
                'dashboard_technical_enable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'user_id' => 3,
                'company_id' => 1,
                'role' => 1, // Accountant
                'remember_me_token' => null,
                'force_mfa' => false,
                'records_per_page' => 15,
                'dashboard_financial_enable' => true,
                'dashboard_technical_enable' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'user_id' => 4,
                'company_id' => 1,
                'role' => 2, // Tech
                'remember_me_token' => null,
                'force_mfa' => false,
                'records_per_page' => 10,
                'dashboard_financial_enable' => false,
                'dashboard_technical_enable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
