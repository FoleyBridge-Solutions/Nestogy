<?php

namespace App\Domains\Security\Console\Commands;

use App\Domains\Security\Scanners\PolicyScanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Silber\Bouncer\BouncerFacade as Bouncer;

class DiscoverPermissionsCommand extends Command
{
    protected $signature = 'permissions:discover 
                          {--sync : Sync discovered permissions to database}
                          {--dry-run : Show what would be synced without making changes}
                          {--report : Show detailed discovery report}';

    protected $description = 'Auto-discover permissions from Policy files';

    public function handle()
    {
        $this->info('🔍 Scanning codebase for permissions...');
        $this->newLine();

        // Run PolicyScanner
        $scanner = app(PolicyScanner::class);
        $discovered = $scanner->scan();
        $grouped = $scanner->scanGrouped();

        // Show statistics
        $this->displayStatistics($discovered, $grouped);

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

    private function displayStatistics(array $discovered, array $grouped): void
    {
        $this->info('📊 Discovery Statistics:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Permissions', count($discovered)],
                ['Categories', count($grouped)],
                ['Source', 'Policy Files'],
            ]
        );
        $this->newLine();
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
