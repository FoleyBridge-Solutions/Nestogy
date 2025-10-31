<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\CustomQuickAction;
use App\Domains\Core\Models\QuickActionFavorite;
use App\Domains\Core\Models\User;
use Illuminate\Database\Seeder;

class QuickActionFavoriteSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Quick Action Favorite Seeder...');

        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $users = User::where('company_id', $company->id)->get();
            $actions = CustomQuickAction::where('company_id', $company->id)->pluck('id')->toArray();

            if ($users->isEmpty() || empty($actions)) {
                continue;
            }

            // 50% of users favorite 2-5 quick actions
            $userCount = (int) ($users->count() * 0.5);
            $selectedUsers = $users->random(min($userCount, $users->count()));

            foreach ($selectedUsers as $user) {
                $favoriteCount = rand(2, min(5, count($actions)));
                $favoriteActions = fake()->randomElements($actions, $favoriteCount);

                $position = 1;
                foreach ($favoriteActions as $actionId) {
                    $customAction = CustomQuickAction::find($actionId);
                    QuickActionFavorite::factory()
                        ->for($user)
                        ->for($customAction, 'customQuickAction')
                        ->create(['position' => $position++]);
                }
            }
        }

        $this->command->info('Quick Action Favorite Seeder completed!');
    }
}
