<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'id' => 1,
                'company_id' => 1,
                'name' => 'System Administrator',
                'email' => 'admin@nestogy.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'status' => true,
                'token' => null,
                'avatar' => null,
                'specific_encryption_ciphertext' => null,
                'php_session' => null,
                'extension_key' => null,
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'archived_at' => null,
            ],
            [
                'id' => 2,
                'company_id' => 1,
                'name' => 'Technical Manager',
                'email' => 'tech@nestogy.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'status' => true,
                'token' => null,
                'avatar' => null,
                'specific_encryption_ciphertext' => null,
                'php_session' => null,
                'extension_key' => null,
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'archived_at' => null,
            ],
            [
                'id' => 3,
                'company_id' => 1,
                'name' => 'Accountant User',
                'email' => 'accounting@nestogy.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'status' => true,
                'token' => null,
                'avatar' => null,
                'specific_encryption_ciphertext' => null,
                'php_session' => null,
                'extension_key' => null,
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'archived_at' => null,
            ],
            [
                'id' => 4,
                'company_id' => 1,
                'name' => 'Test User',
                'email' => 'test@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'status' => true,
                'token' => null,
                'avatar' => null,
                'specific_encryption_ciphertext' => null,
                'php_session' => null,
                'extension_key' => null,
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'archived_at' => null,
            ],
        ]);
    }
}