<?php

namespace App\Console\Commands;

use App\Domains\Core\Services\DistributedSchedulerService;
use Illuminate\Console\Command;

class CleanupSchedulerCoordination extends Command
{
    protected $signature = 'scheduler:cleanup';
    protected $description = 'Clean up old scheduler coordination records';

    public function handle(DistributedSchedulerService $scheduler)
    {
        $this->info('Cleaning up old scheduler coordination records...');

        $scheduler->cleanup();

        $this->info('Cleanup completed successfully');
        return 0;
    }
}
