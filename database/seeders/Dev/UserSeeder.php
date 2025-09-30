<?php

namespace Database\Seeders\Dev;

use App\Models\Company;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
        $this->command->info('    ✓ Created super admin: super@nestogy.com');

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
        $this->command->info('    ✓ Created admin: admin@nestogy.com');
    }

    /**
     * Create users for MSP companies based on their size
     */
    private function createMSPUsers(Company $company): void
    {
        $companySize = $company->size ?? 'medium';
        $this->command->info("  Creating users for {$company->name} ({$companySize} company)...");

        // Extract domain from company email
        $emailDomain = str_replace(['info@', 'www.', 'dave@', 'support@'], '', $company->email);

        $userCount = 0;

        // Determine user counts based on company size
        switch ($companySize) {
            case 'solo':
                $adminCount = 1;
                $techCount = 0;
                $accountingCount = 0;
                $salesCount = 0;
                $marketingCount = 0;
                $pmCount = 0;
                break;

            case 'small': // 2-10 employees
                $totalEmployees = $company->employee_count ?? 5;
                $adminCount = 1;
                $techCount = max(1, min(3, $totalEmployees - 1)); // 1-3 techs for small
                $accountingCount = ($totalEmployees > 5) ? 1 : 0;
                $salesCount = 0;
                $marketingCount = 0;
                $pmCount = 0;
                break;

            case 'medium': // 20-40 employees (realistic mid-market)
                $totalEmployees = $company->employee_count ?? 30;
                $adminCount = rand(1, 2);
                $techCount = rand(8, 15); // Realistic: 8-15 techs for mid-market
                $accountingCount = rand(1, 2);
                $salesCount = rand(2, 3);
                $marketingCount = rand(0, 1);
                $pmCount = rand(1, 2);
                break;

            case 'medium-large': // 40-60 employees
                $totalEmployees = $company->employee_count ?? 50;
                $adminCount = rand(2, 3);
                $techCount = rand(15, 22); // 15-22 techs for larger mid-market
                $accountingCount = rand(2, 3);
                $salesCount = rand(3, 4);
                $marketingCount = 1;
                $pmCount = rand(2, 3);
                break;

            case 'large': // 60-100 employees (aspirational target)
                $totalEmployees = $company->employee_count ?? 75;
                $adminCount = rand(3, 4);
                $techCount = rand(25, 35); // 25-35 techs for upper mid-market
                $accountingCount = rand(3, 4);
                $salesCount = rand(4, 6);
                $marketingCount = rand(1, 2);
                $pmCount = rand(3, 4);
                break;

            default:
                // Default to medium for any unspecified size
                $adminCount = rand(1, 2);
                $techCount = rand(8, 15);
                $accountingCount = rand(1, 2);
                $salesCount = rand(2, 3);
                $marketingCount = rand(0, 1);
                $pmCount = rand(1, 2);
                break;
        }

        // Create Admin users
        for ($i = 1; $i <= $adminCount; $i++) {
            $email = $i == 1 ? "admin@{$emailDomain}" : "admin{$i}@{$emailDomain}";
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'company_id' => $company->id,
                    'name' => fake()->name().' (Admin)',
                    'password' => Hash::make('password123'),
                    'status' => true,
                    'email_verified_at' => now(),
                    'phone' => fake()->phoneNumber(),
                    'title' => fake()->randomElement(['IT Director', 'Operations Manager', 'Admin', 'Owner']),
                    'department' => 'Management',
                    'created_at' => fake()->dateTimeBetween('-2 years', '-1 month'),
                    'updated_at' => fake()->dateTimeBetween('-1 month', 'now'),
                ]
            );
            $user->assign('admin');
            $this->createUserSettings($user, User::ROLE_ADMIN);
            $userCount++;
        }
        if ($adminCount > 0) {
            $this->command->info("    ✓ Created {$adminCount} administrator(s)");
        }

        // Create Technician users
        if ($techCount > 0) {
            $techLevels = ['Jr. Tech', 'Tech', 'Sr. Tech', 'Lead Tech', 'Tech Specialist'];
            for ($i = 1; $i <= $techCount; $i++) {
                $email = "tech{$i}@{$emailDomain}";
                $level = fake()->randomElement($techLevels);
                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'company_id' => $company->id,
                        'name' => fake()->name()." ({$level})",
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
        }

        // Create Accounting/Finance users
        if ($accountingCount > 0) {
            $accountingTitles = ['Billing Specialist', 'Accountant', 'Finance Manager', 'AR Specialist'];
            for ($i = 1; $i <= $accountingCount; $i++) {
                $email = $i == 1 ? "accounting@{$emailDomain}" : "accounting{$i}@{$emailDomain}";
                $title = fake()->randomElement($accountingTitles);
                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'company_id' => $company->id,
                        'name' => fake()->name()." ({$title})",
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
        }

        // Create Sales team
        if ($salesCount > 0) {
            $salesTitles = ['Sales Representative', 'Account Manager', 'Business Development', 'Sales Manager'];
            for ($i = 1; $i <= $salesCount; $i++) {
                $email = $i == 1 ? "sales@{$emailDomain}" : "sales{$i}@{$emailDomain}";
                $title = fake()->randomElement($salesTitles);
                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'company_id' => $company->id,
                        'name' => fake()->name()." ({$title})",
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
        }

        // Create Marketing team
        if ($marketingCount > 0) {
            $marketingTitles = ['Marketing Specialist', 'Marketing Manager', 'Content Creator'];
            for ($i = 1; $i <= $marketingCount; $i++) {
                $email = $i == 1 ? "marketing@{$emailDomain}" : "marketing{$i}@{$emailDomain}";
                $title = fake()->randomElement($marketingTitles);
                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'company_id' => $company->id,
                        'name' => fake()->name()." ({$title})",
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
        }

        // Create Project Managers
        if ($pmCount > 0) {
            for ($i = 1; $i <= $pmCount; $i++) {
                $email = "pm{$i}@{$emailDomain}";
                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'company_id' => $company->id,
                        'name' => fake()->name().' (Project Manager)',
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
        }

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
