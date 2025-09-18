<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserSetting;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;
use Silber\Bouncer\BouncerFacade as Bouncer;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating users for each company...');

        $companies = Company::all();

        foreach ($companies as $company) {
            if ($company->id == 1) {
                $this->createPlatformUsers($company);
            } else {
                $this->createMSPUsers($company);
            }
        }

        $this->command->info('Users created successfully.');
    }

    /**
     * Create users for the platform operator (Company 1)
     */
    private function createPlatformUsers(Company $company): void
    {
        $this->command->info("  Creating users for {$company->name}...");

        // Super Admin
        $superAdmin = User::updateOrCreate(
            ['email' => 'super@nestogy.com'],
            [
                'company_id' => $company->id,
                'name' => 'Super Administrator',
                'password' => Hash::make('password123'),
                'status' => true,
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assign('super-admin');
        $this->createUserSettings($superAdmin, User::ROLE_SUPER_ADMIN);
        $this->command->info("    ✓ Created super admin: super@nestogy.com");

        // Regular Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@nestogy.com'],
            [
                'company_id' => $company->id,
                'name' => 'Platform Administrator',
                'password' => Hash::make('password123'),
                'status' => true,
                'email_verified_at' => now(),
            ]
        );
        $admin->assign('admin');
        $this->createUserSettings($admin, User::ROLE_ADMIN);
        $this->command->info("    ✓ Created admin: admin@nestogy.com");
    }

    /**
     * Create users for MSP companies
     */
    private function createMSPUsers(Company $company): void
    {
        $this->command->info("  Creating enhanced user dataset for {$company->name}...");

        // Extract domain from company email
        $emailDomain = str_replace(['info@', 'www.'], '', $company->email);
        
        $userCount = 0;
        
        // Admin users (3-5)
        $adminCount = rand(3, 5);
        for ($i = 1; $i <= $adminCount; $i++) {
            $email = $i == 1 ? "admin@{$emailDomain}" : "admin{$i}@{$emailDomain}";
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'company_id' => $company->id,
                    'name' => fake()->name() . " (Admin)",
                    'password' => Hash::make('password123'),
                    'status' => true,
                    'email_verified_at' => now(),
                    'phone' => fake()->phoneNumber(),
                    'created_at' => fake()->dateTimeBetween('-2 years', '-1 month'),
                    'updated_at' => fake()->dateTimeBetween('-1 month', 'now'),
                ]
            );
            $user->assign('admin');
            $this->createUserSettings($user, User::ROLE_ADMIN);
            $userCount++;
        }
        $this->command->info("    ✓ Created {$adminCount} administrators");

        // Technician users (10-20)
        $techCount = rand(10, 20);
        $techLevels = ['Jr. Tech', 'Tech', 'Sr. Tech', 'Lead Tech'];
        for ($i = 1; $i <= $techCount; $i++) {
            $email = "tech{$i}@{$emailDomain}";
            $level = fake()->randomElement($techLevels);
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'company_id' => $company->id,
                    'name' => fake()->name() . " ({$level})",
                    'password' => Hash::make('password123'),
                    'status' => fake()->randomElement([true, true, true, true, false]), // 80% active
                    'email_verified_at' => now(),
                    'phone' => fake()->phoneNumber(),
                    'title' => $level,
                    'department' => 'Technical Support',
                    'created_at' => fake()->dateTimeBetween('-2 years', '-1 week'),
                    'updated_at' => fake()->dateTimeBetween('-1 week', 'now'),
                ]
            );
            $user->assign('tech');
            $this->createUserSettings($user, User::ROLE_TECH);
            $userCount++;
        }
        $this->command->info("    ✓ Created {$techCount} technicians");

        // Accounting/Finance users (3-5)
        $accountingCount = rand(3, 5);
        $accountingTitles = ['Billing Specialist', 'Accountant', 'Finance Manager', 'AR Specialist'];
        for ($i = 1; $i <= $accountingCount; $i++) {
            $email = $i == 1 ? "accounting@{$emailDomain}" : "accounting{$i}@{$emailDomain}";
            $title = fake()->randomElement($accountingTitles);
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'company_id' => $company->id,
                    'name' => fake()->name() . " ({$title})",
                    'password' => Hash::make('password123'),
                    'status' => true,
                    'email_verified_at' => now(),
                    'phone' => fake()->phoneNumber(),
                    'title' => $title,
                    'department' => 'Finance',
                    'created_at' => fake()->dateTimeBetween('-2 years', '-1 month'),
                    'updated_at' => fake()->dateTimeBetween('-1 month', 'now'),
                ]
            );
            $user->assign('accountant');
            $this->createUserSettings($user, User::ROLE_ACCOUNTANT);
            $userCount++;
        }
        $this->command->info("    ✓ Created {$accountingCount} accounting staff");

        // Sales team (2-4)
        $salesCount = rand(2, 4);
        $salesTitles = ['Sales Representative', 'Account Manager', 'Business Development', 'Sales Manager'];
        for ($i = 1; $i <= $salesCount; $i++) {
            $email = $i == 1 ? "sales@{$emailDomain}" : "sales{$i}@{$emailDomain}";
            $title = fake()->randomElement($salesTitles);
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'company_id' => $company->id,
                    'name' => fake()->name() . " ({$title})",
                    'password' => Hash::make('password123'),
                    'status' => true,
                    'email_verified_at' => now(),
                    'phone' => fake()->phoneNumber(),
                    'title' => $title,
                    'department' => 'Sales',
                    'created_at' => fake()->dateTimeBetween('-2 years', '-1 month'),
                    'updated_at' => fake()->dateTimeBetween('-1 month', 'now'),
                ]
            );
            $user->assign('sales');
            $this->createUserSettings($user, User::ROLE_TECH);
            $userCount++;
        }
        $this->command->info("    ✓ Created {$salesCount} sales staff");

        // Marketing team (1-2)
        $marketingCount = rand(1, 2);
        $marketingTitles = ['Marketing Specialist', 'Marketing Manager'];
        for ($i = 1; $i <= $marketingCount; $i++) {
            $email = $i == 1 ? "marketing@{$emailDomain}" : "marketing{$i}@{$emailDomain}";
            $title = fake()->randomElement($marketingTitles);
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'company_id' => $company->id,
                    'name' => fake()->name() . " ({$title})",
                    'password' => Hash::make('password123'),
                    'status' => true,
                    'email_verified_at' => now(),
                    'phone' => fake()->phoneNumber(),
                    'title' => $title,
                    'department' => 'Marketing',
                    'created_at' => fake()->dateTimeBetween('-2 years', '-1 month'),
                    'updated_at' => fake()->dateTimeBetween('-1 month', 'now'),
                ]
            );
            $user->assign('marketing');
            $this->createUserSettings($user, User::ROLE_TECH);
            $userCount++;
        }
        $this->command->info("    ✓ Created {$marketingCount} marketing staff");
        
        // Project Managers (2-3)
        $pmCount = rand(2, 3);
        for ($i = 1; $i <= $pmCount; $i++) {
            $email = "pm{$i}@{$emailDomain}";
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'company_id' => $company->id,
                    'name' => fake()->name() . " (Project Manager)",
                    'password' => Hash::make('password123'),
                    'status' => true,
                    'email_verified_at' => now(),
                    'phone' => fake()->phoneNumber(),
                    'title' => 'Project Manager',
                    'department' => 'Operations',
                    'created_at' => fake()->dateTimeBetween('-2 years', '-1 month'),
                    'updated_at' => fake()->dateTimeBetween('-1 month', 'now'),
                ]
            );
            $user->assign('tech');
            $this->createUserSettings($user, User::ROLE_TECH);
            $userCount++;
        }
        $this->command->info("    ✓ Created {$pmCount} project managers");
        
        $this->command->info("    ✓ Total users created for {$company->name}: {$userCount}");
    }

    /**
     * Create user settings
     */
    private function createUserSettings(User $user, int $role): void
    {
        UserSetting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'company_id' => $user->company_id,
                'role' => $role,
                'force_mfa' => false,
                'records_per_page' => 25,
                'dashboard_financial_enable' => in_array($role, [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN, User::ROLE_ACCOUNTANT]),
                'dashboard_technical_enable' => in_array($role, [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN, User::ROLE_TECH]),
                'theme' => 'light',
            ]
        );
    }
}