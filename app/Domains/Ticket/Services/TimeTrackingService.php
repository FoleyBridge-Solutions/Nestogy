<?php

namespace App\Domains\Ticket\Services;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketTimeEntry;
use App\Models\User;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Contract;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TimeTrackingService - Comprehensive time tracking for MSP billing
 * 
 * Handles automated time tracking, billable hours management, approval workflows,
 * and integration with invoice generation. Critical for accurate MSP billing.
 */
class TimeTrackingService
{
    /**
     * Billing rate tiers
     */
    const RATE_STANDARD = 'standard';
    const RATE_AFTER_HOURS = 'after_hours';
    const RATE_EMERGENCY = 'emergency';
    const RATE_WEEKEND = 'weekend';
    const RATE_HOLIDAY = 'holiday';

    /**
     * Time entry statuses
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_APPROVED = 'approved';
    const STATUS_INVOICED = 'invoiced';
    const STATUS_REJECTED = 'rejected';

    /**
     * Default billing rates (can be overridden by contract)
     */
    protected array $defaultRates = [
        self::RATE_STANDARD => 150.00,
        self::RATE_AFTER_HOURS => 225.00,
        self::RATE_EMERGENCY => 300.00,
        self::RATE_WEEKEND => 200.00,
        self::RATE_HOLIDAY => 250.00,
    ];

    /**
     * Start time tracking for a ticket
     * 
     * @param Ticket $ticket
     * @param User $technician
     * @param array $options
     * @return TicketTimeEntry
     */
    public function startTracking(Ticket $ticket, User $technician, array $options = []): TicketTimeEntry
    {
        // Check if there's already an active timer
        $activeEntry = $this->getActiveTimer($technician);
        if ($activeEntry) {
            throw new \Exception('Technician already has an active timer. Please stop it first.');
        }

        $entry = new TicketTimeEntry();
        $entry->ticket_id = $ticket->id;
        $entry->company_id = $ticket->company_id;
        $entry->user_id = $technician->id;
        $entry->start_time = $options['start_time'] ?? now();
        $entry->status = self::STATUS_DRAFT;
        $entry->billable = $this->determineBillability($ticket, $options);
        $entry->rate_type = $this->determineRateType($entry->start_time);
        $entry->hourly_rate = $this->getHourlyRate($ticket, $entry->rate_type);
        $entry->work_type = $options['work_type'] ?? 'general_support';
        $entry->description = $options['description'] ?? null;
        $entry->metadata = [
            'auto_started' => $options['auto_start'] ?? false,
            'location' => $options['location'] ?? 'remote',
            'client_visible' => $options['client_visible'] ?? true,
        ];
        $entry->save();

        Log::info('Time tracking started', [
            'entry_id' => $entry->id,
            'ticket_id' => $ticket->id,
            'technician_id' => $technician->id,
            'billable' => $entry->billable,
        ]);

        return $entry;
    }

    /**
     * Stop time tracking
     * 
     * @param TicketTimeEntry $entry
     * @param array $options
     * @return TicketTimeEntry
     */
    public function stopTracking(TicketTimeEntry $entry, array $options = []): TicketTimeEntry
    {
        if ($entry->end_time) {
            throw new \Exception('Time entry has already been stopped.');
        }

        DB::beginTransaction();

        try {
            $entry->end_time = $options['end_time'] ?? now();
            
            // Calculate duration
            $duration = $this->calculateDuration($entry->start_time, $entry->end_time);
            $entry->hours_worked = $duration['hours'];
            $entry->minutes_worked = $duration['minutes'];
            
            // Apply rounding rules
            $entry->hours_billed = $this->applyBillingRules($duration, $entry);
            
            // Calculate amount
            $entry->amount = $entry->billable ? ($entry->hours_billed * $entry->hourly_rate) : 0;
            
            // Update description if provided
            if (isset($options['description'])) {
                $entry->description = $options['description'];
            }
            
            // Update work performed if provided
            if (isset($options['work_performed'])) {
                $entry->work_performed = $options['work_performed'];
            }
            
            // Set status based on approval requirements
            $entry->status = $this->requiresApproval($entry) ? self::STATUS_SUBMITTED : self::STATUS_APPROVED;
            
            $entry->save();

            // Update ticket totals
            $this->updateTicketTotals($entry->ticket);

            DB::commit();

            Log::info('Time tracking stopped', [
                'entry_id' => $entry->id,
                'duration' => $entry->hours_worked,
                'billed' => $entry->hours_billed,
                'amount' => $entry->amount,
            ]);

            return $entry;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to stop time tracking', [
                'entry_id' => $entry->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Pause time tracking
     * 
     * @param TicketTimeEntry $entry
     * @param string $reason
     * @return TicketTimeEntry
     */
    public function pauseTracking(TicketTimeEntry $entry, string $reason = null): TicketTimeEntry
    {
        if ($entry->end_time) {
            throw new \Exception('Cannot pause a completed time entry.');
        }

        $metadata = $entry->metadata ?? [];
        
        if (!isset($metadata['pauses'])) {
            $metadata['pauses'] = [];
        }

        $metadata['pauses'][] = [
            'paused_at' => now()->toDateTimeString(),
            'reason' => $reason,
        ];

        $metadata['is_paused'] = true;
        $entry->metadata = $metadata;
        $entry->save();

        Log::info('Time tracking paused', [
            'entry_id' => $entry->id,
            'reason' => $reason,
        ]);

        return $entry;
    }

    /**
     * Resume time tracking
     * 
     * @param TicketTimeEntry $entry
     * @return TicketTimeEntry
     */
    public function resumeTracking(TicketTimeEntry $entry): TicketTimeEntry
    {
        $metadata = $entry->metadata ?? [];
        
        if (!($metadata['is_paused'] ?? false)) {
            throw new \Exception('Time entry is not paused.');
        }

        $lastPause = end($metadata['pauses']);
        $pauseDuration = now()->diffInMinutes($lastPause['paused_at']);
        
        // Adjust start time to account for pause
        $entry->start_time = $entry->start_time->addMinutes($pauseDuration);
        
        // Update pause record
        $metadata['pauses'][count($metadata['pauses']) - 1]['resumed_at'] = now()->toDateTimeString();
        $metadata['pauses'][count($metadata['pauses']) - 1]['duration_minutes'] = $pauseDuration;
        $metadata['is_paused'] = false;
        $metadata['total_pause_minutes'] = ($metadata['total_pause_minutes'] ?? 0) + $pauseDuration;
        
        $entry->metadata = $metadata;
        $entry->save();

        Log::info('Time tracking resumed', [
            'entry_id' => $entry->id,
            'pause_duration' => $pauseDuration,
        ]);

        return $entry;
    }

    /**
     * Submit time entries for approval
     * 
     * @param Collection|array $entries
     * @param User $submitter
     * @return array
     */
    public function submitForApproval($entries, User $submitter): array
    {
        $results = [
            'submitted' => [],
            'errors' => [],
        ];

        $entries = is_array($entries) ? collect($entries) : $entries;

        foreach ($entries as $entry) {
            try {
                if ($entry->status !== self::STATUS_DRAFT) {
                    $results['errors'][] = [
                        'entry_id' => $entry->id,
                        'error' => 'Entry is not in draft status',
                    ];
                    continue;
                }

                $entry->status = self::STATUS_SUBMITTED;
                $entry->submitted_at = now();
                $entry->submitted_by = $submitter->id;
                $entry->save();

                $results['submitted'][] = $entry->id;

                // Send approval notification
                // $this->notificationService->notifyTimeEntrySubmitted($entry);

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'entry_id' => $entry->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Time entries submitted for approval', $results);

        return $results;
    }

    /**
     * Approve time entries
     * 
     * @param Collection|array $entries
     * @param User $approver
     * @param string|null $notes
     * @return array
     */
    public function approveEntries($entries, User $approver, ?string $notes = null): array
    {
        $results = [
            'approved' => [],
            'errors' => [],
        ];

        $entries = is_array($entries) ? collect($entries) : $entries;

        DB::beginTransaction();

        try {
            foreach ($entries as $entry) {
                if ($entry->status !== self::STATUS_SUBMITTED) {
                    $results['errors'][] = [
                        'entry_id' => $entry->id,
                        'error' => 'Entry is not pending approval',
                    ];
                    continue;
                }

                $entry->status = self::STATUS_APPROVED;
                $entry->approved_at = now();
                $entry->approved_by = $approver->id;
                $entry->approval_notes = $notes;
                $entry->save();

                $results['approved'][] = $entry->id;
            }

            DB::commit();

            Log::info('Time entries approved', $results);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve time entries', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $results;
    }

    /**
     * Generate invoice items from approved time entries
     * 
     * @param Invoice $invoice
     * @param Collection $entries
     * @return array
     */
    public function generateInvoiceItems(Invoice $invoice, Collection $entries): array
    {
        $items = [];
        $totalAmount = 0;

        // Group entries by work type and rate
        $grouped = $entries->groupBy(function ($entry) {
            return $entry->work_type . '_' . $entry->hourly_rate;
        });

        foreach ($grouped as $key => $group) {
            $totalHours = $group->sum('hours_billed');
            $rate = $group->first()->hourly_rate;
            $workType = $group->first()->work_type;
            $amount = $totalHours * $rate;

            // Create invoice item
            $item = InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $this->generateItemDescription($group, $workType),
                'quantity' => $totalHours,
                'rate' => $rate,
                'amount' => $amount,
                'tax_rate' => 0,
                'is_taxable' => true,
                'category' => 'time_tracking',
                'metadata' => [
                    'time_entry_ids' => $group->pluck('id')->toArray(),
                    'work_type' => $workType,
                    'period' => [
                        'start' => $group->min('start_time'),
                        'end' => $group->max('end_time'),
                    ],
                ],
            ]);

            $items[] = $item;
            $totalAmount += $amount;

            // Mark entries as invoiced
            $group->each(function ($entry) use ($invoice) {
                $entry->status = self::STATUS_INVOICED;
                $entry->invoice_id = $invoice->id;
                $entry->invoiced_at = now();
                $entry->save();
            });
        }

        Log::info('Invoice items generated from time entries', [
            'invoice_id' => $invoice->id,
            'items_created' => count($items),
            'total_amount' => $totalAmount,
        ]);

        return [
            'items' => $items,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * Get active timer for technician
     * 
     * @param User $technician
     * @return TicketTimeEntry|null
     */
    public function getActiveTimer(User $technician): ?TicketTimeEntry
    {
        return TicketTimeEntry::where('user_id', $technician->id)
            ->whereNull('end_time')
            ->first();
    }

    /**
     * Get time entry summary for period
     * 
     * @param int $companyId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $filters
     * @return array
     */
    public function getTimeSummary(int $companyId, Carbon $startDate, Carbon $endDate, array $filters = []): array
    {
        $query = TicketTimeEntry::where('company_id', $companyId)
            ->whereBetween('start_time', [$startDate, $endDate]);

        // Apply filters
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['client_id'])) {
            $query->whereHas('ticket', function ($q) use ($filters) {
                $q->where('client_id', $filters['client_id']);
            });
        }

        if (isset($filters['billable'])) {
            $query->where('billable', $filters['billable']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $entries = $query->with(['ticket', 'ticket.client', 'user'])->get();

        // Calculate summary statistics
        $summary = [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'total_entries' => $entries->count(),
            'total_hours_worked' => round($entries->sum('hours_worked'), 2),
            'total_hours_billed' => round($entries->sum('hours_billed'), 2),
            'billable_hours' => round($entries->where('billable', true)->sum('hours_billed'), 2),
            'non_billable_hours' => round($entries->where('billable', false)->sum('hours_worked'), 2),
            'total_amount' => round($entries->sum('amount'), 2),
            'utilization_rate' => $this->calculateUtilizationRate($entries, $startDate, $endDate),
            'realization_rate' => $this->calculateRealizationRate($entries),
            'by_status' => $this->groupByStatus($entries),
            'by_technician' => $this->groupByTechnician($entries),
            'by_client' => $this->groupByClient($entries),
            'by_work_type' => $this->groupByWorkType($entries),
        ];

        return $summary;
    }

    /**
     * Determine if time entry is billable
     * 
     * @param Ticket $ticket
     * @param array $options
     * @return bool
     */
    protected function determineBillability(Ticket $ticket, array $options = []): bool
    {
        // Check if explicitly set
        if (isset($options['billable'])) {
            return $options['billable'];
        }

        // Check contract terms
        if ($ticket->client && $ticket->client->activeContract) {
            $contract = $ticket->client->activeContract;
            
            // Check if support is included in contract
            if ($contract->pricing_structure && 
                isset($contract->pricing_structure['included_hours'])) {
                
                $includedHours = $contract->pricing_structure['included_hours'];
                $usedHours = $this->getContractHoursUsed($contract, now()->startOfMonth(), now()->endOfMonth());
                
                if ($usedHours < $includedHours) {
                    return false; // Within included hours, not billable
                }
            }
        }

        // Check ticket type
        $nonBillableTypes = config('nestogy.time_tracking.non_billable_ticket_types', [
            'warranty',
            'internal',
            'training',
        ]);

        if (in_array($ticket->type, $nonBillableTypes)) {
            return false;
        }

        // Default to billable
        return true;
    }

    /**
     * Determine rate type based on time
     * 
     * @param Carbon $time
     * @return string
     */
    protected function determineRateType(Carbon $time): string
    {
        // Check if holiday
        if ($this->isHoliday($time)) {
            return self::RATE_HOLIDAY;
        }

        // Check if weekend
        if ($time->isWeekend()) {
            return self::RATE_WEEKEND;
        }

        // Check if after hours (before 8am or after 6pm)
        $hour = $time->hour;
        if ($hour < 8 || $hour >= 18) {
            return self::RATE_AFTER_HOURS;
        }

        return self::RATE_STANDARD;
    }

    /**
     * Get hourly rate for ticket
     * 
     * @param Ticket $ticket
     * @param string $rateType
     * @return float
     */
    protected function getHourlyRate(Ticket $ticket, string $rateType): float
    {
        // Check contract rates
        if ($ticket->client && $ticket->client->activeContract) {
            $contract = $ticket->client->activeContract;
            
            if ($contract->pricing_structure && 
                isset($contract->pricing_structure['hourly_rates'][$rateType])) {
                return $contract->pricing_structure['hourly_rates'][$rateType];
            }
        }

        // Use default rates
        return $this->defaultRates[$rateType] ?? $this->defaultRates[self::RATE_STANDARD];
    }

    /**
     * Calculate duration between times
     * 
     * @param Carbon $start
     * @param Carbon $end
     * @return array
     */
    protected function calculateDuration(Carbon $start, Carbon $end): array
    {
        $totalMinutes = $start->diffInMinutes($end);
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return [
            'hours' => $hours + ($minutes / 60),
            'minutes' => $totalMinutes,
            'display' => sprintf('%d:%02d', $hours, $minutes),
        ];
    }

    /**
     * Apply billing rules for rounding
     * 
     * @param array $duration
     * @param TicketTimeEntry $entry
     * @return float
     */
    protected function applyBillingRules(array $duration, TicketTimeEntry $entry): float
    {
        $hours = $duration['hours'];
        
        // Get rounding rules from config or contract
        $roundingRule = 'quarter'; // Can be: none, quarter, half, hour
        $minimumBilling = 0.25; // Minimum billable time

        // Apply minimum billing
        if ($hours < $minimumBilling) {
            return $minimumBilling;
        }

        // Apply rounding
        switch ($roundingRule) {
            case 'quarter':
                return ceil($hours * 4) / 4;
                
            case 'half':
                return ceil($hours * 2) / 2;
                
            case 'hour':
                return ceil($hours);
                
            default:
                return round($hours, 2);
        }
    }

    /**
     * Check if approval is required
     * 
     * @param TicketTimeEntry $entry
     * @return bool
     */
    protected function requiresApproval(TicketTimeEntry $entry): bool
    {
        // Approval required if over 8 hours
        if ($entry->hours_worked > 8) {
            return true;
        }

        // Approval required for emergency rates
        if ($entry->rate_type === self::RATE_EMERGENCY) {
            return true;
        }

        // Check company settings
        $requireApproval = config('nestogy.time_tracking.require_approval', false);

        return $requireApproval;
    }

    /**
     * Update ticket totals
     * 
     * @param Ticket $ticket
     * @return void
     */
    protected function updateTicketTotals(Ticket $ticket): void
    {
        $totals = TicketTimeEntry::where('ticket_id', $ticket->id)
            ->selectRaw('
                SUM(hours_worked) as total_hours_worked,
                SUM(CASE WHEN billable = 1 THEN hours_billed ELSE 0 END) as total_billable_hours,
                SUM(CASE WHEN billable = 1 THEN amount ELSE 0 END) as total_amount
            ')
            ->first();

        $ticket->total_time_spent = $totals->total_hours_worked ?? 0;
        $ticket->billable_hours = $totals->total_billable_hours ?? 0;
        $ticket->time_tracking_amount = $totals->total_amount ?? 0;
        $ticket->save();
    }

    /**
     * Get contract hours used
     * 
     * @param Contract $contract
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    protected function getContractHoursUsed(Contract $contract, Carbon $startDate, Carbon $endDate): float
    {
        return TicketTimeEntry::whereHas('ticket', function ($q) use ($contract) {
                $q->where('client_id', $contract->client_id);
            })
            ->whereBetween('start_time', [$startDate, $endDate])
            ->where('billable', false) // Non-billable means using contract hours
            ->sum('hours_worked');
    }

    /**
     * Check if date is a holiday
     * 
     * @param Carbon $date
     * @return bool
     */
    protected function isHoliday(Carbon $date): bool
    {
        // This would check against configured holidays
        $holidays = config('nestogy.time_tracking.holidays', []);
        
        return in_array($date->format('Y-m-d'), $holidays);
    }

    /**
     * Generate item description for invoice
     * 
     * @param Collection $entries
     * @param string $workType
     * @return string
     */
    protected function generateItemDescription(Collection $entries, string $workType): string
    {
        $startDate = $entries->min('start_time');
        $endDate = $entries->max('end_time');
        
        $workTypeLabels = [
            'general_support' => 'General Support',
            'troubleshooting' => 'Troubleshooting',
            'maintenance' => 'Maintenance',
            'consultation' => 'Consultation',
            'project_work' => 'Project Work',
            'emergency_support' => 'Emergency Support',
        ];

        $label = $workTypeLabels[$workType] ?? 'Support Services';
        
        return sprintf(
            '%s - %s to %s (%d entries)',
            $label,
            Carbon::parse($startDate)->format('M d'),
            Carbon::parse($endDate)->format('M d, Y'),
            $entries->count()
        );
    }

    /**
     * Calculate utilization rate
     * 
     * @param Collection $entries
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    protected function calculateUtilizationRate(Collection $entries, Carbon $startDate, Carbon $endDate): float
    {
        $workDays = $startDate->diffInDaysFiltered(function (Carbon $date) {
            return $date->isWeekday();
        }, $endDate);

        $availableHours = $workDays * 8; // 8 hours per day
        
        if ($availableHours == 0) {
            return 0;
        }

        $billableHours = $entries->where('billable', true)->sum('hours_worked');
        
        return round(($billableHours / $availableHours) * 100, 2);
    }

    /**
     * Calculate realization rate
     * 
     * @param Collection $entries
     * @return float
     */
    protected function calculateRealizationRate(Collection $entries): float
    {
        $workedHours = $entries->sum('hours_worked');
        
        if ($workedHours == 0) {
            return 0;
        }

        $billedHours = $entries->sum('hours_billed');
        
        return round(($billedHours / $workedHours) * 100, 2);
    }

    /**
     * Group entries by status
     * 
     * @param Collection $entries
     * @return array
     */
    protected function groupByStatus(Collection $entries): array
    {
        return $entries->groupBy('status')->map(function ($group, $status) {
            return [
                'count' => $group->count(),
                'hours' => round($group->sum('hours_worked'), 2),
                'amount' => round($group->sum('amount'), 2),
            ];
        })->toArray();
    }

    /**
     * Group entries by technician
     * 
     * @param Collection $entries
     * @return array
     */
    protected function groupByTechnician(Collection $entries): array
    {
        return $entries->groupBy('user_id')->map(function ($group) {
            $user = $group->first()->user;
            return [
                'name' => $user->name ?? 'Unknown',
                'entries' => $group->count(),
                'hours_worked' => round($group->sum('hours_worked'), 2),
                'hours_billed' => round($group->sum('hours_billed'), 2),
                'amount' => round($group->sum('amount'), 2),
                'utilization' => round(($group->where('billable', true)->sum('hours_worked') / $group->sum('hours_worked')) * 100, 2),
            ];
        })->toArray();
    }

    /**
     * Group entries by client
     * 
     * @param Collection $entries
     * @return array
     */
    protected function groupByClient(Collection $entries): array
    {
        return $entries->groupBy(function ($entry) {
            return $entry->ticket->client_id ?? 0;
        })->map(function ($group) {
            $client = $group->first()->ticket->client ?? null;
            return [
                'name' => $client->name ?? 'Internal',
                'entries' => $group->count(),
                'hours' => round($group->sum('hours_worked'), 2),
                'amount' => round($group->sum('amount'), 2),
            ];
        })->toArray();
    }

    /**
     * Group entries by work type
     * 
     * @param Collection $entries
     * @return array
     */
    protected function groupByWorkType(Collection $entries): array
    {
        return $entries->groupBy('work_type')->map(function ($group, $type) {
            return [
                'count' => $group->count(),
                'hours' => round($group->sum('hours_worked'), 2),
                'amount' => round($group->sum('amount'), 2),
            ];
        })->toArray();
    }
}