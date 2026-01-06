<?php

namespace App\Livewire\Settings;

use Flux\Flux;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class TicketBillingSettings extends Component
{
    // Configuration properties
    public bool $enabled;

    public bool $autoBillOnClose;

    public bool $autoBillOnResolve;

    public string $defaultStrategy;

    public float $minBillableHours;

    public float $roundHoursTo;

    public int $invoiceDueDays;

    public bool $requireApproval;

    public bool $skipZeroInvoices;

    public bool $autoSend;

    public int $batchSize;

    // Statistics
    public int $pendingTicketsCount = 0;

    public int $billingQueueCount = 0;

    // Processing
    public bool $processing = false;

    public ?string $processingResult = null;

    protected $rules = [
        'enabled' => 'boolean',
        'autoBillOnClose' => 'boolean',
        'autoBillOnResolve' => 'boolean',
        'defaultStrategy' => 'required|in:time_based,per_ticket,mixed',
        'minBillableHours' => 'required|numeric|min:0|max:24',
        'roundHoursTo' => 'required|numeric|in:0.25,0.5,1.0',
        'invoiceDueDays' => 'required|integer|min:1|max:365',
        'requireApproval' => 'boolean',
        'skipZeroInvoices' => 'boolean',
        'autoSend' => 'boolean',
        'batchSize' => 'required|integer|min:1|max:1000',
    ];

    public function mount()
    {
        // Authorization check
        $this->authorize('ticket-billing.view-settings');

        $this->loadConfiguration();
        $this->loadStatistics();
    }

    protected function loadConfiguration()
    {
        $this->enabled = config('billing.ticket.enabled', true);
        $this->autoBillOnClose = config('billing.ticket.auto_bill_on_close', false);
        $this->autoBillOnResolve = config('billing.ticket.auto_bill_on_resolve', false);
        $this->defaultStrategy = config('billing.ticket.default_strategy', 'time_based');
        $this->minBillableHours = config('billing.ticket.min_billable_hours', 0.25);
        $this->roundHoursTo = config('billing.ticket.round_hours_to', 0.25);
        $this->invoiceDueDays = config('billing.ticket.invoice_due_days', 30);
        $this->requireApproval = config('billing.ticket.require_approval', true);
        $this->skipZeroInvoices = config('billing.ticket.skip_zero_invoices', true);
        $this->autoSend = config('billing.ticket.auto_send', false);
        $this->batchSize = config('billing.ticket.batch_size', 100);
    }

    protected function loadStatistics()
    {
        try {
            // Count pending tickets (closed/resolved, billable, not yet invoiced, with unbilled time entries)
            $this->pendingTicketsCount = \App\Domains\Ticket\Models\Ticket::query()
                ->where('company_id', auth()->user()->company_id)
                ->whereIn('status', ['closed', 'resolved'])
                ->where('billable', true)
                ->whereNotNull('client_id')
                ->where(function ($query) {
                    // Has unbilled time entries
                    $query->whereHas('timeEntries', function ($q) {
                        $q->where('billable', true)->where('is_billed', false);
                    })
                    // OR has active contract (for per-ticket billing)
                    ->orWhereHas('client.contracts', function ($q) {
                        $q->whereIn('status', ['active', 'signed']);
                    });
                })
                ->count();

            // Count billing queue jobs (on default queue)
            $this->billingQueueCount = \DB::table('jobs')
                ->where('queue', 'default')
                ->count();
        } catch (\Exception $e) {
            Log::error('Failed to load ticket billing statistics', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function save()
    {
        // Authorization check
        $this->authorize('ticket-billing.manage-settings');

        $this->validate();

        try {
            // Update .env file
            $this->updateEnvFile([
                'TICKET_BILLING_ENABLED' => $this->enabled ? 'true' : 'false',
                'AUTO_BILL_ON_CLOSE' => $this->autoBillOnClose ? 'true' : 'false',
                'AUTO_BILL_ON_RESOLVE' => $this->autoBillOnResolve ? 'true' : 'false',
                'BILLING_STRATEGY_DEFAULT' => $this->defaultStrategy,
                'BILLING_MIN_HOURS' => (string) $this->minBillableHours,
                'BILLING_ROUND_HOURS_TO' => (string) $this->roundHoursTo,
                'BILLING_INVOICE_DUE_DAYS' => (string) $this->invoiceDueDays,
                'BILLING_REQUIRE_APPROVAL' => $this->requireApproval ? 'true' : 'false',
                'BILLING_SKIP_ZERO_INVOICES' => $this->skipZeroInvoices ? 'true' : 'false',
                'BILLING_AUTO_SEND' => $this->autoSend ? 'true' : 'false',
                'BILLING_BATCH_SIZE' => (string) $this->batchSize,
            ]);

            // Clear config cache
            Artisan::call('config:clear');

            Flux::toast(
                variant: 'success',
                text: 'Billing settings saved successfully!',
                duration: 3000
            );

            Log::info('Ticket billing settings updated', [
                'user_id' => auth()->id(),
                'enabled' => $this->enabled,
                'auto_bill_on_close' => $this->autoBillOnClose,
            ]);

        } catch (\Exception $e) {
            Flux::toast(
                variant: 'danger',
                text: 'Failed to save settings: '.$e->getMessage(),
                duration: 5000
            );

            Log::error('Failed to save ticket billing settings', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
        }
    }

    public function processPendingTickets($limit = null)
    {
        // Authorization check
        $this->authorize('ticket-billing.process-pending');

        $this->processing = true;
        $this->processingResult = null;

        try {
            $limit = $limit ?? $this->batchSize;

            $exitCode = Artisan::call('billing:process-pending-tickets', [
                '--limit' => $limit,
            ]);

            $output = Artisan::output();

            if ($exitCode === 0) {
                $this->processingResult = 'success';
                Flux::toast(
                    variant: 'success',
                    text: "Successfully queued billing for up to {$limit} tickets!",
                    duration: 3000
                );
            } else {
                $this->processingResult = 'error';
                Flux::toast(
                    variant: 'danger',
                    text: 'Some tickets failed to process. Check logs for details.',
                    duration: 5000
                );
            }

            $this->loadStatistics();

        } catch (\Exception $e) {
            $this->processingResult = 'error';
            Flux::toast(
                variant: 'danger',
                text: 'Error processing tickets: '.$e->getMessage(),
                duration: 5000
            );
        } finally {
            $this->processing = false;
        }
    }

    public function testDryRun()
    {
        // Authorization check
        $this->authorize('ticket-billing.dry-run');

        try {
            Artisan::call('billing:process-pending-tickets', [
                '--dry-run' => true,
                '--limit' => 10,
            ]);

            $output = Artisan::output();

            Flux::toast(
                variant: 'info',
                text: 'Dry run complete! Check the logs for details.',
                duration: 3000
            );

        } catch (\Exception $e) {
            Flux::toast(
                variant: 'danger',
                text: 'Dry run failed: '.$e->getMessage(),
                duration: 5000
            );
        }
    }

    protected function updateEnvFile(array $data)
    {
        $envFile = base_path('.env');

        if (! file_exists($envFile)) {
            throw new \Exception('.env file not found');
        }

        $content = file_get_contents($envFile);

        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";

            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $replacement, $content);
            } else {
                $content .= "\n{$replacement}";
            }
        }

        file_put_contents($envFile, $content);
    }

    public function render()
    {
        return view('livewire.settings.ticket-billing-settings');
    }
}
