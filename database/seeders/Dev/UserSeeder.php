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
     * Create users for MSP companies (Companies 2-5)
     */
    private function createMSPUsers(Company $company): void
    {
        $this->command->info("  Creating users for {$company->name}...");

        // Extract domain from company email
        $emailDomain = str_replace(['info@', 'www.'], '', $company->email);
        if (strpos($emailDomain, '@') === false) {
            $emailDomain = $emailDomain;
        }

        // Admin users (2)
        for ($i = 1; $i <= 2; $i++) {
            $email = $i == 1 ? "admin@{$emailDomain}" : "admin{$i}@{$emailDomain}";
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'company_id' => $company->id,
                    'name' => "Administrator {$i}",
                    'password' => Hash::make('password123'),
                    'status' => true,
                    'email_verified_at' => now(),
                ]
            );
            $user->assign('admin');
            $this->createUserSettings($user, User::ROLE_ADMIN);
            $this->command->info("    ✓ Created admin: {$email}");
        }

        // Technician users (5)
        for ($i = 1; $i <= 5; $i++) {
            $email = "tech{$i}@{$emailDomain}";
            $names = ['John Smith', 'Sarah Johnson', 'Mike Davis', 'Emily Wilson', 'David Brown'];
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'company_id' => $company->id,
                    'name' => $names[$i - 1] . " (Tech)",
                    'password' => Hash::make('password123'),
                    'status' => true,
                    'email_verified_at' => now(),
                ]
            );
            $user->assign('tech');
            $this->createUserSettings($user, User::ROLE_TECH);
        }
        $this->command->info("    ✓ Created 5 technicians");

        // Accounting users (2)
        for ($i = 1; $i <= 2; $i++) {
            $email = $i == 1 ? "accounting@{$emailDomain}" : "accounting{$i}@{$emailDomain}";
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'company_id' => $company->id,
                    'name' => "Accountant {$i}",
                    'password' => Hash::make('password123'),
                    'status' => true,
                    'email_verified_at' => now(),
                ]
            );
            $user->assign('accountant');
            $this->createUserSettings($user, User::ROLE_ACCOUNTANT);
        }
        $this->command->info("    ✓ Created 2 accountants");

        // Sales user
        $salesEmail = "sales@{$emailDomain}";
        $salesUser = User::updateOrCreate(
            ['email' => $salesEmail],
            [
                'company_id' => $company->id,
                'name' => 'Sales Representative',
                'password' => Hash::make('password123'),
                'status' => true,
                'email_verified_at' => now(),
            ]
        );
        $salesUser->assign('sales');
        $this->createUserSettings($salesUser, User::ROLE_TECH); // Using TECH role for sales
        $this->command->info("    ✓ Created sales: {$salesEmail}");

        // Marketing user
        $marketingEmail = "marketing@{$emailDomain}";
        $marketingUser = User::updateOrCreate(
            ['email' => $marketingEmail],
            [
                'company_id' => $company->id,
                'name' => 'Marketing Specialist',
                'password' => Hash::make('password123'),
                'status' => true,
                'email_verified_at' => now(),
            ]
        );
        $marketingUser->assign('marketing');
        $this->createUserSettings($marketingUser, User::ROLE_TECH); // Using TECH role for marketing
        $this->command->info("    ✓ Created marketing: {$marketingEmail}");
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