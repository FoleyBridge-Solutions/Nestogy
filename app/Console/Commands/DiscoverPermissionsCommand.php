<?php

namespace App\Console\Commands;

use App\Domains\Security\Scanners\PolicyScanner;
use App\Domains\Security\Scanners\ControllerScanner;
use App\Domains\Security\Scanners\LivewireScanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Silber\Bouncer\BouncerFacade as Bouncer;

class DiscoverPermissionsCommand extends Command
{
    protected $signature = 'permissions:discover 
                          {--sync : Sync discovered permissions to database}
                          {--dry-run : Show what would be synced without making changes}
                          {--report : Show detailed discovery report}
                          {--policies-only : Only scan policies}
                          {--controllers-only : Only scan controllers}
                          {--livewire-only : Only scan Livewire components}';

    protected $description = 'Auto-discover permissions from Policies, Controllers, and Livewire components';

    public function handle()
    {
        $this->info('🔍 Scanning codebase for permissions...');
        $this->newLine();

        // Run scanners based on options
        $policyPerms = [];
        $controllerPerms = [];
        $livewirePerms = [];
        
        $scanAll = !$this->option('policies-only') && 
                   !$this->option('controllers-only') && 
                   !$this->option('livewire-only');

        if ($scanAll || $this->option('policies-only')) {
            $this->line('📄 Scanning policies...');
            $policyPerms = app(PolicyScanner::class)->scan();
        }
        
        if ($scanAll || $this->option('controllers-only')) {
            $this->line('🎮 Scanning controllers...');
            $controllerPerms = app(ControllerScanner::class)->scan();
        }
        
        if ($scanAll || $this->option('livewire-only')) {
            $this->line('⚡ Scanning Livewire components...');
            $livewirePerms = app(LivewireScanner::class)->scan();
        }

        // Merge and deduplicate
        $discovered = $this->mergePermissions($policyPerms, $controllerPerms, $livewirePerms);
        $grouped = $this->groupByCategory($discovered);

        // Show statistics
        $this->displayStatistics($discovered, $grouped, [
            'policies' => count($policyPerms),
            'controllers' => count($controllerPerms),
            'livewire' => count($livewirePerms),
        ]);

        // Show report if requested
        if ($this->option('report')) {
            $this->displayReport($grouped);
        }

        // Sync to database if requested
        if ($this->option('sync') && !$this->option('dry-run')) {
            $this->syncToDatabase($discovered);
        } elseif ($this->option('dry-run')) {
            $this->displayDryRun($discovered);
        } else {
            $this->newLine();
            $this->info('💡 Run with --sync to save to database');
            $this->info('💡 Run with --report to see detailed breakdown');
        }

        // Clear permission cache
        if ($this->option('sync') && !$this->option('dry-run')) {
            Cache::forget('permission_registry');
            Bouncer::refresh();
            $this->info('✅ Cache cleared');
        }

        return Command::SUCCESS;
    }

    private function displayStatistics(array $discovered, array $grouped, array $sources): void
    {
        $this->info('📊 Discovery Statistics:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Permissions', count($discovered)],
                ['From Policies', $sources['policies']],
                ['From Controllers', $sources['controllers']],
                ['From Livewire', $sources['livewire']],
                ['Categories', count($grouped)],
            ]
        );
        $this->newLine();
    }
    
    private function mergePermissions(array ...$arrays): array
    {
        $merged = [];
        
        foreach ($arrays as $permissions) {
            foreach ($permissions as $perm) {
                $name = $perm['name'];
                
                if (!isset($merged[$name])) {
                    $merged[$name] = $perm;
                    if (!isset($merged[$name]['source_files'])) {
                        $merged[$name]['source_files'] = [];
                    }
                } else {
                    // Merge source files
                    if (isset($perm['source_files'])) {
                        $merged[$name]['source_files'] = array_unique(
                            array_merge($merged[$name]['source_files'], $perm['source_files'])
                        );
                    }
                }
            }
        }
        
        return array_values($merged);
    }
    
    private function groupByCategory(array $permissions): array
    {
        $grouped = [];
        
        foreach ($permissions as $perm) {
            $category = $perm['category'];
            
            if (!isset($grouped[$category])) {
                $grouped[$category] = [
                    'title' => ucfirst($category),
                    'permissions' => [],
                ];
            }
            
            $grouped[$category]['permissions'][] = $perm;
        }
        
        ksort($grouped);
        return $grouped;
    }

    private function displayReport(array $grouped): void
    {
        $this->info('📋 Detailed Permission Report:');
        $this->newLine();

        foreach ($grouped as $category => $data) {
            $this->line("  <fg=cyan>{$data['title']}</> ({$category})");
            
            foreach ($data['permissions'] as $perm) {
                $sources = implode(', ', array_unique($perm['source_files']));
                $this->line("    • {$perm['name']}");
                $this->line("      <fg=gray>{$perm['title']}</>");
                $this->line("      <fg=gray>Source: {$sources}</>");
            }
            
            $this->newLine();
        }
    }

    private function displayDryRun(array $discovered): void
    {
        $this->newLine();
        $this->warn('🔍 DRY RUN - No changes will be made');
        $this->newLine();

        // Get existing abilities
        $existing = Bouncer::ability()->pluck('name')->toArray();
        
        $toCreate = array_filter($discovered, function($perm) use ($existing) {
            return !in_array($perm['name'], $existing);
        });

        $toUpdate = array_filter($discovered, function($perm) use ($existing) {
            return in_array($perm['name'], $existing);
        });

        $this->info("Would create {" . count($toCreate) . "} new permissions:");
        foreach (array_slice($toCreate, 0, 10) as $perm) {
            $this->line("  + {$perm['name']} - {$perm['title']}");
        }
        if (count($toCreate) > 10) {
            $this->line("  ... and " . (count($toCreate) - 10) . " more");
        }

        $this->newLine();
        $this->info("Would update {" . count($toUpdate) . "} existing permissions");
    }

    private function syncToDatabase(array $discovered): void
    {
        $this->newLine();
        $this->info('💾 Syncing permissions to database...');
        
        $created = 0;
        $updated = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar(count($discovered));
        $progressBar->start();

        foreach ($discovered as $perm) {
            try {
                $ability = Bouncer::ability()->firstOrNew(['name' => $perm['name']]);
                
                $isNew = !$ability->exists;
                
                $ability->title = $perm['title'];
                $ability->entity_type = null; // Global permissions
                $ability->only_owned = false;
                $ability->options = [
                    'category' => $perm['category'],
                    'discovered_from' => $perm['source_type'],
                ];
                
                $ability->save();

                $isNew ? $created++ : $updated++;

            } catch (\Exception $e) {
                $errors++;
                $this->error("Error syncing {$perm['name']}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Show results
        $this->info('✅ Sync complete!');
        $this->table(
            ['Action', 'Count'],
            [
                ['Created', $created],
                ['Updated', $updated],
                ['Errors', $errors],
            ]
        );

        if ($created > 0) {
            $this->newLine();
            $this->info('🎉 Your PermissionMatrix UI will now show these new permissions!');
        }
    }
}
