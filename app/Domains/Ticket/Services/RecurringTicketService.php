<?php

namespace App\Domains\Ticket\Services;

use App\Domains\Ticket\Models\RecurringTicket;
use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Recurring Ticket Service
 * 
 * Handles automated ticket generation, schedule management, and recurring
 * ticket business logic for complex frequency patterns and date calculations.
 */
class RecurringTicketService
{
    /**
     * Generate tickets for all active recurring schedules that are due
     */
    public function generateDueTickets(): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $dueRecurringTickets = RecurringTicket::where('is_active', true)
                                            ->where('next_due_date', '<=', now())
                                            ->where(function($query) {
                                                $query->whereNull('end_date')
                                                      ->orWhere('end_date', '>=', now());
                                            })
                                            ->with(['template', 'client'])
                                            ->get();

        foreach ($dueRecurringTickets as $recurringTicket) {
            try {
                $this->generateTicketFromRecurring($recurringTicket);
                $results['success']++;
                
                Log::info('Recurring ticket generated', [
                    'recurring_ticket_id' => $recurringTicket->id,
                    'next_due_date' => $recurringTicket->next_due_date
                ]);
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'recurring_ticket_id' => $recurringTicket->id,
                    'error' => $e->getMessage()
                ];
                
                Log::error('Failed to generate recurring ticket', [
                    'recurring_ticket_id' => $recurringTicket->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Generate a single ticket from a recurring schedule
     */
    public function generateTicketFromRecurring(RecurringTicket $recurringTicket, array $overrides = []): Ticket
    {
        if (!$recurringTicket->is_active) {
            throw new \Exception('Cannot generate tickets from inactive recurring schedule');
        }

        if (!$recurringTicket->template) {
            throw new \Exception('Recurring ticket must have a template');
        }

        // Process template variables
        $variables = array_merge($recurringTicket->template_variables ?? [], $overrides);
        $processedTemplate = $this->processTemplateVariables($recurringTicket->template, $variables);

        // Generate unique ticket number
        $ticketNumber = $this->generateTicketNumber($recurringTicket->tenant_id);

        // Create the ticket
        $ticket = Ticket::create([
            'tenant_id' => $recurringTicket->tenant_id,
            'ticket_number' => $ticketNumber,
            'client_id' => $recurringTicket->client_id,
            'subject' => $processedTemplate['subject'],
            'description' => $processedTemplate['description'],
            'priority' => $recurringTicket->template->priority ?? 'Medium',
            'status' => 'new',
            'assigned_to' => $recurringTicket->assigned_to,
            'created_by' => 1, // System user
            'recurring_ticket_id' => $recurringTicket->id,
            'template_id' => $recurringTicket->template_id,
            'estimated_hours' => $recurringTicket->template->estimated_hours,
            'custom_fields' => $recurringTicket->template->custom_fields ?? [],
            'tags' => array_merge(['recurring'], $recurringTicket->template->tags ?? []),
        ]);

        // Update recurring ticket statistics
        $recurringTicket->increment('tickets_generated');
        $recurringTicket->update([
            'last_generated_at' => now(),
            'next_due_date' => $this->calculateNextDueDate($recurringTicket),
        ]);

        // Add generation note to ticket
        $ticket->addNote(
            "Generated from recurring schedule: {$recurringTicket->name}",
            'recurring'
        );

        return $ticket;
    }

    /**
     * Calculate the next due date for a recurring ticket
     */
    public function calculateNextDueDate(RecurringTicket $recurringTicket): ?Carbon
    {
        $currentDue = $recurringTicket->next_due_date ? 
            Carbon::parse($recurringTicket->next_due_date) : 
            Carbon::parse($recurringTicket->start_date);

        // Check if we've reached the end date
        if ($recurringTicket->end_date && $currentDue->gte(Carbon::parse($recurringTicket->end_date))) {
            return null;
        }

        $nextDue = $currentDue->copy();

        switch ($recurringTicket->frequency) {
            case 'daily':
                $nextDue->addDays($recurringTicket->interval_value);
                break;

            case 'weekly':
                $nextDue = $this->calculateWeeklyNextDue($nextDue, $recurringTicket);
                break;

            case 'monthly':
                $nextDue = $this->calculateMonthlyNextDue($nextDue, $recurringTicket);
                break;

            case 'yearly':
                $nextDue = $this->calculateYearlyNextDue($nextDue, $recurringTicket);
                break;

            default:
                throw new \Exception("Unsupported frequency: {$recurringTicket->frequency}");
        }

        // Apply time of day if specified
        if ($recurringTicket->time_of_day) {
            $time = Carbon::parse($recurringTicket->time_of_day);
            $nextDue->setTime($time->hour, $time->minute);
        }

        return $nextDue;
    }

    /**
     * Get upcoming scheduled dates for a recurring ticket
     */
    public function getUpcomingDates(RecurringTicket $recurringTicket, int $count = 10): Collection
    {
        $dates = collect();
        $currentDate = $recurringTicket->next_due_date ? 
            Carbon::parse($recurringTicket->next_due_date) : 
            Carbon::parse($recurringTicket->start_date);

        $maxIterations = $count * 2; // Safety limit
        $iterations = 0;

        while ($dates->count() < $count && $iterations < $maxIterations) {
            // Check if we've reached the end date
            if ($recurringTicket->end_date && $currentDate->gte(Carbon::parse($recurringTicket->end_date))) {
                break;
            }

            $dates->push($currentDate->copy());
            
            // Calculate next date
            $tempRecurring = $recurringTicket->replicate();
            $tempRecurring->next_due_date = $currentDate;
            $currentDate = $this->calculateNextDueDate($tempRecurring);
            
            if (!$currentDate) {
                break;
            }

            $iterations++;
        }

        return $dates;
    }

    /**
     * Preview ticket generation without creating actual tickets
     */
    public function previewTicketGeneration(RecurringTicket $recurringTicket, array $variables = []): array
    {
        if (!$recurringTicket->template) {
            throw new \Exception('Recurring ticket must have a template');
        }

        // Process template with variables
        $processedTemplate = $this->processTemplateVariables($recurringTicket->template, $variables);

        return [
            'subject' => $processedTemplate['subject'],
            'description' => $processedTemplate['description'],
            'priority' => $recurringTicket->template->priority ?? 'Medium',
            'assigned_to' => $recurringTicket->assignee?->name ?? 'Unassigned',
            'client' => $recurringTicket->client->name,
            'estimated_hours' => $recurringTicket->template->estimated_hours,
            'tags' => array_merge(['recurring'], $recurringTicket->template->tags ?? []),
            'next_generation' => $recurringTicket->next_due_date?->format('M j, Y \a\t g:i A'),
        ];
    }

    /**
     * Bulk update recurring ticket schedules
     */
    public function bulkUpdateSchedules(array $recurringTicketIds, array $updates): array
    {
        $results = [
            'updated' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $recurringTickets = RecurringTicket::whereIn('id', $recurringTicketIds)->get();

        foreach ($recurringTickets as $recurringTicket) {
            try {
                $oldNextDue = $recurringTicket->next_due_date;
                
                $recurringTicket->update($updates);
                
                // Recalculate next due date if frequency changed
                if (isset($updates['frequency']) || isset($updates['interval_value']) || isset($updates['frequency_config'])) {
                    $recurringTicket->update([
                        'next_due_date' => $this->calculateNextDueDate($recurringTicket)
                    ]);
                }
                
                $results['updated']++;
                
                Log::info('Recurring ticket schedule updated', [
                    'recurring_ticket_id' => $recurringTicket->id,
                    'old_next_due' => $oldNextDue,
                    'new_next_due' => $recurringTicket->next_due_date
                ]);
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'recurring_ticket_id' => $recurringTicket->id,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Calculate weekly next due date with weekday constraints
     */
    private function calculateWeeklyNextDue(Carbon $current, RecurringTicket $recurringTicket): Carbon
    {
        $config = $recurringTicket->frequency_config ?? [];
        $weekdays = $config['weekdays'] ?? [];

        if (empty($weekdays)) {
            // Simple weekly interval
            return $current->addWeeks($recurringTicket->interval_value);
        }

        // Find next occurrence on specified weekdays
        $daysOfWeek = [
            'sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
            'thursday' => 4, 'friday' => 5, 'saturday' => 6
        ];

        $targetDays = array_map(fn($day) => $daysOfWeek[$day], $weekdays);
        sort($targetDays);

        $nextDue = $current->copy()->addDay(); // Start from next day
        $weeksAdded = 0;
        $maxIterations = 14; // Safety limit

        for ($i = 0; $i < $maxIterations; $i++) {
            if (in_array($nextDue->dayOfWeek, $targetDays)) {
                if ($weeksAdded >= $recurringTicket->interval_value - 1) {
                    break;
                }
            }

            $nextDue->addDay();
            
            // Check if we've completed a week
            if ($nextDue->dayOfWeek === $current->dayOfWeek) {
                $weeksAdded++;
            }
        }

        return $nextDue;
    }

    /**
     * Calculate monthly next due date with day/week constraints
     */
    private function calculateMonthlyNextDue(Carbon $current, RecurringTicket $recurringTicket): Carbon
    {
        $config = $recurringTicket->frequency_config ?? [];
        $nextDue = $current->copy();

        if (isset($config['day'])) {
            // Specific day of month
            $nextDue->addMonths($recurringTicket->interval_value);
            $nextDue->day(min($config['day'], $nextDue->daysInMonth));
        } elseif (isset($config['week']) && isset($config['weekday'])) {
            // Specific week and weekday of month
            $nextDue->addMonths($recurringTicket->interval_value);
            $nextDue = $this->getNthWeekdayOfMonth($nextDue, $config['weekday'], $config['week']);
        } else {
            // Same day next month
            $nextDue->addMonths($recurringTicket->interval_value);
        }

        return $nextDue;
    }

    /**
     * Calculate yearly next due date
     */
    private function calculateYearlyNextDue(Carbon $current, RecurringTicket $recurringTicket): Carbon
    {
        $config = $recurringTicket->frequency_config ?? [];
        $nextDue = $current->copy();

        if (isset($config['month']) && isset($config['day'])) {
            // Specific month and day
            $nextDue->addYears($recurringTicket->interval_value);
            $nextDue->month($config['month']);
            $nextDue->day(min($config['day'], $nextDue->daysInMonth));
        } else {
            // Same date next year
            $nextDue->addYears($recurringTicket->interval_value);
        }

        return $nextDue;
    }

    /**
     * Get the Nth weekday of a month
     */
    private function getNthWeekdayOfMonth(Carbon $date, string $weekday, int $week): Carbon
    {
        $daysOfWeek = [
            'sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
            'thursday' => 4, 'friday' => 5, 'saturday' => 6
        ];

        $targetDay = $daysOfWeek[$weekday];
        $firstOfMonth = $date->copy()->startOfMonth();
        
        // Find first occurrence of the weekday
        while ($firstOfMonth->dayOfWeek !== $targetDay) {
            $firstOfMonth->addDay();
        }

        // Add weeks to get to the Nth occurrence
        $nthOccurrence = $firstOfMonth->addWeeks($week - 1);

        // If we've gone past the month, get the last occurrence
        if ($nthOccurrence->month !== $date->month) {
            $nthOccurrence->subWeek();
        }

        return $nthOccurrence;
    }

    /**
     * Process template variables in template content
     */
    private function processTemplateVariables(TicketTemplate $template, array $variables): array
    {
        $subject = $template->subject_template;
        $description = $template->body_template;

        // Add default variables
        $defaultVariables = [
            'date' => now()->format('Y-m-d'),
            'time' => now()->format('H:i'),
            'datetime' => now()->format('Y-m-d H:i:s'),
            'client_name' => $template->client->name ?? '',
        ];

        $allVariables = array_merge($defaultVariables, $variables);

        // Replace variables in subject and description
        foreach ($allVariables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $subject = str_replace($placeholder, $value, $subject);
            $description = str_replace($placeholder, $value, $description);
        }

        return [
            'subject' => $subject,
            'description' => $description,
        ];
    }

    /**
     * Generate unique ticket number
     */
    private function generateTicketNumber(int $tenantId): string
    {
        $prefix = 'REC';
        $year = date('Y');
        
        // Get the last ticket number for this year
        $lastTicket = Ticket::where('tenant_id', $tenantId)
                           ->where('ticket_number', 'like', "{$prefix}-{$year}-%")
                           ->orderBy('ticket_number', 'desc')
                           ->first();

        if ($lastTicket) {
            // Extract sequence number and increment
            $parts = explode('-', $lastTicket->ticket_number);
            $sequence = intval(end($parts)) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s-%06d', $prefix, $year, $sequence);
    }
}