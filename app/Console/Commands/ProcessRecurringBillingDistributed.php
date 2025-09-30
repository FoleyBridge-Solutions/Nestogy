<?php

namespace App\Console\Commands;

use App\Domains\Core\Services\DistributedSchedulerService;
use Illuminate\Console\Command;

class ProcessRecurringBillingDistributed extends Command
{
    protected $signature = 'billing:process-recurring-distributed';
    protected $description = 'Process recurring billing with distributed coordination';

    public function handle(DistributedSchedulerService $scheduler)
    {
        $jobName = 'recurring-billing-daily';

        $result = $scheduler->executeIfNotRunning($jobName, function() {
            // Your existing recurring billing logic here
            $this->info('Processing recurring billing...');

            // Example: Call existing command or service
            $this->call('invoices:generate-recurring');

            $this->info('Recurring billing completed successfully');
        });

        if (!$result) {
            $this->info('Recurring billing already running on another server - skipping');
        }

        return $result ? 0 : 1;
    }
}
