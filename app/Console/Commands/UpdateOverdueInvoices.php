<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;

class UpdateOverdueInvoices extends Command
{
    protected $signature = 'invoices:update-overdue';

    protected $description = 'Update status of overdue invoices';

    public function handle()
    {
        $updated = Invoice::where('status', Invoice::STATUS_SENT)
            ->where('due_date', '<', now())
            ->update(['status' => Invoice::STATUS_OVERDUE]);

        $this->info("Updated {$updated} overdue invoices");

        return 0;
    }
}
