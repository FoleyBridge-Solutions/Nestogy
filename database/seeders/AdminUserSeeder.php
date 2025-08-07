<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use Carbon\Carbon;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create admin user with only the fields that exist in the users table
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@nestogy.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('Admin@123456'),
                'status' => true,
                'email_verified_at' => Carbon::now(),
                'remember_token' => Str::random(10),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        );

        $this->command->info('Admin user seeded successfully!');
        $this->command->info('Email: admin@nestogy.com');
        $this->command->info('Password: Admin@123456');
        $this->command->warn('Please change the admin password after first login!');
    }
}