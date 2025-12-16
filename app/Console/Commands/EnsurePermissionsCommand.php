<?php

namespace App\Console\Commands;

use App\Domains\Security\Services\TenantRoleService;
use Illuminate\Console\Command;
use Silber\Bouncer\BouncerFacade as Bouncer;

/**
 * Ensure all permissions are discovered and role templates are valid
 *
 * This command should be run during deployment to ensure:
 * 1. All permissions from policies are discovered and synced
 * 2. Role templates reference valid permissions
 * 3. System is ready for new company onboarding
 */
class EnsurePermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'permissions:ensure
                            {--sync : Sync discovered permissions to database}
                            {--validate-only : Only validate templates without syncing}';

    /**
     * The console command description.
     */
    protected $description = 'Ensure all permissions are discovered and role templates are valid';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Ensuring permissions system integrity...');
        $this->newLine();

        // Step 1: Discover permissions from policies
        if (! $this->option('validate-only')) {
            $this->info('Step 1: Discovering permissions from policies...');

            $exitCode = $this->call('permissions:discover', [
                '--sync' => $this->option('sync'),
            ]);

            if ($exitCode !== 0) {
                $this->error('❌ Permission discovery failed');

                return $exitCode;
            }

            $this->newLine();
        }

        // Step 2: Validate role templates
        $this->info('Step 2: Validating role templates...');

        $service = app(TenantRoleService::class);
        $missing = $service->validateTemplates();

        if (! empty($missing)) {
            $this->warn('⚠️  Missing permissions in role templates:');
            $this->newLine();

            foreach ($missing as $role => $permissions) {
                $this->line("  <fg=yellow>{$role}</>:");
                foreach ($permissions as $permission) {
                    $this->line("    - {$permission}");
                }
            }

            $this->newLine();

            if ($this->option('sync')) {
                $this->error('❌ Some permissions in role templates do not exist in the database.');
                $this->error('   These permissions need to be created or removed from config/role-templates.php');

                return 1;
            } else {
                $this->warn('💡 Run with --sync to create missing permissions');

                return 1;
            }
        }

        $this->info('✅ All role template permissions exist');
        $this->newLine();

        // Step 3: Show summary
        $this->info('Step 3: System summary...');

        $totalAbilities = Bouncer::ability()->count();
        $templates = config('role-templates', []);
        $totalRoles = count($templates);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Abilities (Permissions)', $totalAbilities],
                ['Role Templates', $totalRoles],
            ]
        );

        $this->newLine();
        $this->info('✅ Permissions system is ready');

        if (! $this->option('sync') && ! $this->option('validate-only')) {
            $this->newLine();
            $this->comment('💡 Run with --sync to save discovered permissions to database');
        }

        return 0;
    }
}
