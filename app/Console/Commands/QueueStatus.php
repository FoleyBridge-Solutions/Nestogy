<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class QueueStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show current queue status and job counts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Queue Status ===');

        // Get job counts
        $totalJobs = DB::table('jobs')->count();
        $emailJobs = DB::table('jobs')->where('queue', 'emails')->count();
        $defaultJobs = DB::table('jobs')->where('queue', 'default')->count();
        $failedJobs = DB::table('failed_jobs')->count();

        $this->table([
            'Queue', 'Pending Jobs',
        ], [
            ['emails', $emailJobs],
            ['default', $defaultJobs],
            ['Total Pending', $totalJobs],
            ['Failed Jobs', $failedJobs],
        ]);

        if ($totalJobs > 0) {
            $this->warn("There are {$totalJobs} jobs waiting to be processed.");
            $this->info('Run: php artisan queue:work --queue=emails,default');
        } else {
            $this->info('No jobs in queue.');
        }

        if ($failedJobs > 0) {
            $this->error("There are {$failedJobs} failed jobs.");
            $this->info('Run: php artisan queue:failed to see details');
            $this->info('Run: php artisan queue:retry all to retry failed jobs');
        }

        return 0;
    }
}
