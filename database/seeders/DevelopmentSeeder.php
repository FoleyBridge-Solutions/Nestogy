<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    /**
     * Seed the database with test/development data.
     */
    public function run(): void
    {
        $this->call([
            // Production essentials first
            ProductionDatabaseSeeder::class,

            // Development/test data
            CompanySeeder::class,
            UserSeeder::class,
            UserSettingsSeeder::class,
        ]);

        // Assign roles to test users
        $this->assignTestUserRoles();
    }

    private function assignTestUserRoles(): void
    {
        // Assign roles to test users if they exist
        if (class_exists(\Silber\Bouncer\BouncerFacade::class)) {
            $company = \App\Models\Company::find(1);
            if ($company) {
                \Bouncer::scope()->to($company->id);

                if ($user = \App\Models\User::find(1)) {
                    $user->assign('super-admin');
                }
                if ($user = \App\Models\User::find(2)) {
                    $user->assign('tech');
                }
                if ($user = \App\Models\User::find(3)) {
                    $user->assign('accountant');
                }
                if ($user = \App\Models\User::find(4)) {
                    $user->assign('tech');
                }
            }
        }
    }
}