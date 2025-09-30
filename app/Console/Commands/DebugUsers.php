<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugUsers extends Command
{
    protected $signature = 'debug:users';

    protected $description = 'Debug user authentication issues';

    public function handle()
    {
        // Security warning for production environments
        if (app()->environment('production')) {
            $this->error('❌ WARNING: This debug command should not be used in production!');
            if (! $this->confirm('Are you sure you want to continue?')) {
                return 1;
            }
        }

        $this->info('=== USER DEBUG INFO ===');

        // Check database connection
        try {
            DB::connection()->getPdo();
            $this->info('✅ Database connection: OK');
        } catch (\Exception $e) {
            $this->error('❌ Database connection failed: '.$e->getMessage());

            return;
        }

        // Check if users table exists
        try {
            $users = DB::table('users')->get();
            $this->info('✅ Users table exists with '.$users->count().' records');
        } catch (\Exception $e) {
            $this->error('❌ Users table issue: '.$e->getMessage());

            return;
        }

        // List all users
        $this->info("\n=== ALL USERS ===");
        foreach ($users as $user) {
            $this->line(sprintf(
                'ID: %d | Name: %s | Email: %s | Company ID: %d | Status: %s',
                $user->id,
                $user->name,
                $user->email,
                $user->company_id,
                $user->status ? 'Active' : 'Inactive'
            ));
        }

        // Check specific user
        $this->info("\n=== TESTING ADMIN USER ===");
        $adminEmail = 'admin@nestogy.com';
        $adminUser = DB::table('users')->where('email', $adminEmail)->first();

        if ($adminUser) {
            $this->info('✅ Admin user found:');
            $this->line('  Email: '.$adminUser->email);
            $this->line('  Status: '.($adminUser->status ? 'Active' : 'Inactive'));
            $this->line('  Company ID: '.$adminUser->company_id);
            $this->line('  Created: '.$adminUser->created_at);

            // Security note: Password validation removed to prevent credential exposure
            // To test password validation, use: php artisan tinker
            // then: Hash::check('your_password', User::find(1)->password)
            $this->line('  Password validation: Use tinker for secure password testing');
        } else {
            $this->error('❌ Admin user not found!');
        }

        // Check companies
        $this->info("\n=== COMPANIES ===");
        try {
            $companies = DB::table('companies')->get();
            foreach ($companies as $company) {
                $this->line(sprintf('ID: %d | Name: %s', $company->id, $company->name));
            }
        } catch (\Exception $e) {
            $this->error('Companies table issue: '.$e->getMessage());
        }
    }
}
