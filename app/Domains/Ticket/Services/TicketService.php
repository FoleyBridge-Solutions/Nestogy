<?php

namespace App\Domains\Ticket\Services;

use App\Domains\Core\Services\NotificationService;
use App\Domains\Ticket\Models\SLA;
use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketAssignment;
use App\Domains\Ticket\Models\TicketPriorityQueue;
use App\Models\Client;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TicketService - Core service for managing support tickets in the MSP platform
 *
 * Handles SLA calculations, automated assignment, escalations, bulk operations,
 * and integration with notification systems.
 */
class TicketService
{
    protected NotificationService $notificationService;

    protected SLAService $slaService;

    /**
     * Constructor
     */
    public function __construct(NotificationService $notificationService, SLAService $slaService)
    {
        $this->notificationService = $notificationService;
        $this->slaService = $slaService;
    }

    /**
     * Calculate SLA deadlines based on priority and client SLA
     */
    public function calculateSlaDeadlines(Ticket $ticket): array
    {
        $priority = strtolower($ticket->priority);
        $client = $ticket->client;

        if (! $client) {
            return $this->getDefaultSlaDeadlines($ticket);
        }

        // Calculate deadlines using SLA service
        $responseDeadline = $this->slaService->calculateResponseDeadline($client, $priority, $ticket->created_at);
        $resolutionDeadline = $this->slaService->calculateResolutionDeadline($client, $priority, $ticket->created_at);

        // Get the SLA for additional information
        $sla = $this->slaService->getClientSLA($client);

        if (! $responseDeadline || ! $resolutionDeadline || ! $sla) {
            return $this->getDefaultSlaDeadlines($ticket);
        }

        // Store in priority queue
        $this->updatePriorityQueue($ticket, $responseDeadline, $resolutionDeadline);

        return [
            'response_deadline' => $responseDeadline,
            'resolution_deadline' => $resolutionDeadline,
            'response_minutes' => $sla->getResponseTimeMinutes($priority),
            'resolution_minutes' => $sla->getResolutionTimeMinutes($priority),
            'sla_id' => $sla->id,
            'sla_name' => $sla->name,
            'is_custom_sla' => ! $sla->is_default,
        ];
    }

    /**
     * Get default SLA deadlines when no SLA is available
     */
    protected function getDefaultSlaDeadlines(Ticket $ticket): array
    {
        $priority = strtolower($ticket->priority);

        // Default fallback SLA times in minutes
        $defaultResponseTimes = [
            'critical' => 60,
            'high' => 240,
            'medium' => 480,
            'low' => 1440,
        ];

        $defaultResolutionTimes = [
            'critical' => 240,
            'high' => 1440,
            'medium' => 4320,
            'low' => 10080,
        ];

        $responseMinutes = $defaultResponseTimes[$priority] ?? 1440;
        $resolutionMinutes = $defaultResolutionTimes[$priority] ?? 10080;

        $responseDeadline = $ticket->created_at->addMinutes($responseMinutes);
        $resolutionDeadline = $ticket->created_at->addMinutes($resolutionMinutes);

        // Store in priority queue
        $this->updatePriorityQueue($ticket, $responseDeadline, $resolutionDeadline);

        return [
            'response_deadline' => $responseDeadline,
            'resolution_deadline' => $resolutionDeadline,
            'response_minutes' => $responseMinutes,
            'resolution_minutes' => $resolutionMinutes,
            'sla_id' => null,
            'sla_name' => 'Default SLA',
            'is_custom_sla' => false,
        ];
    }

    /**
     * Update or create priority queue entry for ticket
     */
    protected function updatePriorityQueue(Ticket $ticket, Carbon $responseDeadline, Carbon $resolutionDeadline): TicketPriorityQueue
    {
        return TicketPriorityQueue::updateOrCreate(
            ['ticket_id' => $ticket->id],
            [
                'company_id' => $ticket->company_id,
                'priority_score' => $ticket->calculatePriorityScore(),
                'sla_deadline' => $resolutionDeadline,
                'response_deadline' => $responseDeadline,
                'is_escalated' => false,
                'last_calculated_at' => now(),
            ]
        );
    }

    /**
     * Automatically assign ticket based on technician skills and availability
     */
    public function autoAssignTicket(Ticket $ticket, array $options = []): ?User
    {
        try {
            DB::beginTransaction();

            // Get available technicians
            $technicians = $this->getAvailableTechnicians($ticket, $options);

            if ($technicians->isEmpty()) {
                Log::warning('No available technicians for ticket', ['ticket_id' => $ticket->id]);
                DB::commit();

                return null;
            }

            // Select best technician based on scoring
            $selectedTechnician = $this->selectBestTechnician($technicians, $ticket);

            if (! $selectedTechnician) {
                DB::commit();

                return null;
            }

            // Assign the ticket
            $ticket->assigned_to = $selectedTechnician->id;
            $ticket->save();

            // Create assignment record for tracking
            TicketAssignment::create([
                'ticket_id' => $ticket->id,
                'user_id' => $selectedTechnician->id,
                'assigned_at' => now(),
                'assigned_by' => auth()->id() ?? null,
                'assignment_type' => 'auto',
                'reason' => $this->getAssignmentReason($selectedTechnician, $ticket),
            ]);

            // Send notification
            $this->notificationService->notifyTicketAssigned($ticket, $selectedTechnician);

            DB::commit();

            Log::info('Ticket auto-assigned', [
                'ticket_id' => $ticket->id,
                'technician_id' => $selectedTechnician->id,
            ]);

            return $selectedTechnician;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to auto-assign ticket', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get available technicians for ticket assignment
     */
    protected function getAvailableTechnicians(Ticket $ticket, array $options = []): Collection
    {
        $query = User::where('company_id', $ticket->company_id)
            ->where('is_active', true);

        // Filter by role/permissions
        if (! isset($options['skip_permission_check']) || ! $options['skip_permission_check']) {
            $query->whereHas('roles', function ($q) {
                $q->whereIn('name', ['technician', 'admin', 'manager']);
            });
        }

        // Filter by category expertise if specified
        if ($ticket->category && ! empty($options['match_expertise'])) {
            $query->where(function ($q) use ($ticket) {
                $q->whereJsonContains('skills', $ticket->category)
                    ->orWhereJsonContains('expertise_areas', $ticket->category);
            });
        }

        // Filter by availability
        if (! isset($options['include_busy']) || ! $options['include_busy']) {
            $query->whereDoesntHave('tickets', function ($q) {
                $q->whereIn('status', [Ticket::STATUS_IN_PROGRESS])
                    ->where('priority', Ticket::PRIORITY_CRITICAL);
            });
        }

        return $query->get();
    }

    /**
     * Select the best technician based on scoring algorithm
     */
    protected function selectBestTechnician(Collection $technicians, Ticket $ticket): ?User
    {
        $scores = [];

        foreach ($technicians as $technician) {
            $score = 0;

            // Current workload (lower is better)
            $activeTickets = Ticket::where('assigned_to', $technician->id)
                ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS])
                ->count();
            $score -= ($activeTickets * 10);

            // Priority tickets count (penalize if already handling critical tickets)
            $criticalTickets = Ticket::where('assigned_to', $technician->id)
                ->where('priority', Ticket::PRIORITY_CRITICAL)
                ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS])
                ->count();
            $score -= ($criticalTickets * 20);

            // Client familiarity (bonus if worked with client before)
            if ($ticket->client_id) {
                $previousTickets = Ticket::where('assigned_to', $technician->id)
                    ->where('client_id', $ticket->client_id)
                    ->where('status', Ticket::STATUS_CLOSED)
                    ->count();
                $score += min($previousTickets * 5, 25); // Cap at 25 points
            }

            // Category expertise (bonus for matching skills)
            if ($ticket->category) {
                $skills = is_array($technician->skills) ? $technician->skills : json_decode($technician->skills ?? '[]', true);
                if (in_array($ticket->category, $skills)) {
                    $score += 30;
                }
            }

            // Recent response time (bonus for quick responders)
            $avgResponseTime = $this->getAverageResponseTime($technician, 30); // Last 30 days
            if ($avgResponseTime && $avgResponseTime < 2) { // Less than 2 hours average
                $score += 15;
            }

            $scores[$technician->id] = $score;
        }

        // Return technician with highest score
        if (empty($scores)) {
            return null;
        }

        arsort($scores);
        $bestTechnicianId = array_key_first($scores);

        return $technicians->firstWhere('id', $bestTechnicianId);
    }

    /**
     * Get average response time for technician
     */
    protected function getAverageResponseTime(User $technician, int $days = 30): ?float
    {
        $cacheKey = "technician_avg_response_{$technician->id}_{$days}";

        return Cache::remember($cacheKey, 3600, function () use ($technician, $days) {
            $tickets = Ticket::where('assigned_to', $technician->id)
                ->where('created_at', '>=', now()->subDays($days))
                ->whereNotNull('first_response_at')
                ->get();

            if ($tickets->isEmpty()) {
                return null;
            }

            $totalResponseTime = 0;
            foreach ($tickets as $ticket) {
                $totalResponseTime += $ticket->created_at->diffInHours($ticket->first_response_at);
            }

            return round($totalResponseTime / $tickets->count(), 2);
        });
    }

    /**
     * Get assignment reason for audit trail
     */
    protected function getAssignmentReason(User $technician, Ticket $ticket): string
    {
        $reasons = [];

        // Check workload
        $activeTickets = Ticket::where('assigned_to', $technician->id)
            ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS])
            ->count();

        if ($activeTickets < 5) {
            $reasons[] = 'Low current workload';
        }

        // Check expertise
        $skills = is_array($technician->skills) ? $technician->skills : json_decode($technician->skills ?? '[]', true);
        if ($ticket->category && in_array($ticket->category, $skills)) {
            $reasons[] = "Expertise in {$ticket->category}";
        }

        // Check client familiarity
        if ($ticket->client_id) {
            $previousTickets = Ticket::where('assigned_to', $technician->id)
                ->where('client_id', $ticket->client_id)
                ->count();

            if ($previousTickets > 0) {
                $reasons[] = 'Previous experience with client';
            }
        }

        return ! empty($reasons) ? implode('; ', $reasons) : 'Best available match';
    }

    /**
     * Check and trigger escalations for SLA breaches
     *
     * @param  array  $options
     * @return Collection
     */

    /**
     * Check if ticket response SLA is breached
     */
    public function isResponseSlaBreached(Ticket $ticket): bool
    {
        if (! $ticket->client) {
            return false;
        }

        $priority = strtolower($ticket->priority);

        return $this->slaService->isResponseBreached($ticket->client, $priority, $ticket->created_at);
    }

    /**
     * Check if ticket resolution SLA is breached
     */
    public function isResolutionSlaBreached(Ticket $ticket): bool
    {
        if (! $ticket->client) {
            return false;
        }

        $priority = strtolower($ticket->priority);
        $resolvedAt = $ticket->status === 'Resolved' ? $ticket->updated_at : null;

        return $this->slaService->isResolutionBreached($ticket->client, $priority, $ticket->created_at, $resolvedAt);
    }

    /**
     * Check if ticket SLA is approaching breach
     *
     * @param  string  $type  - 'response' or 'resolution'
     */
    public function isSlaApproachingBreach(Ticket $ticket, string $type = 'response'): bool
    {
        if (! $ticket->client) {
            return false;
        }

        $priority = strtolower($ticket->priority);

        return $this->slaService->isApproachingBreach($ticket->client, $priority, $ticket->created_at, $type);
    }

    /**
     * Get SLA information for a ticket
     */
    public function getTicketSlaInfo(Ticket $ticket): array
    {
        if (! $ticket->client) {
            return [];
        }

        $sla = $this->slaService->getClientSLA($ticket->client);

        if (! $sla) {
            return [];
        }

        $priority = strtolower($ticket->priority);

        return [
            'sla' => $sla,
            'response_deadline' => $sla->calculateResponseDeadline($ticket->created_at, $priority),
            'resolution_deadline' => $sla->calculateResolutionDeadline($ticket->created_at, $priority),
            'is_response_breached' => $this->isResponseSlaBreached($ticket),
            'is_resolution_breached' => $this->isResolutionSlaBreached($ticket),
            'is_approaching_response_breach' => $this->isSlaApproachingBreach($ticket, 'response'),
            'is_approaching_resolution_breach' => $this->isSlaApproachingBreach($ticket, 'resolution'),
        ];
    }

    public function checkAndTriggerEscalations(array $options = []): Collection
    {
        $escalatedTickets = collect();

        try {
            // Get tickets approaching or past SLA deadlines
            $ticketsToCheck = TicketPriorityQueue::with('ticket')
                ->where('is_escalated', false)
                ->where(function ($query) {
                    $query->where('response_deadline', '<=', now()->addHours(1))
                        ->orWhere('sla_deadline', '<=', now()->addHours(2));
                })
                ->get();

            foreach ($ticketsToCheck as $queueItem) {
                $ticket = $queueItem->ticket;

                if (! $ticket || $ticket->isClosed()) {
                    continue;
                }

                $shouldEscalate = false;
                $escalationReason = '';

                // Check response SLA breach
                if (! $ticket->first_response_at && $queueItem->response_deadline <= now()) {
                    $shouldEscalate = true;
                    $escalationReason = 'Response SLA breached';
                } elseif (! $ticket->first_response_at && $queueItem->response_deadline <= now()->addMinutes(30)) {
                    $shouldEscalate = true;
                    $escalationReason = 'Response SLA at risk (30 minutes remaining)';
                }

                // Check resolution SLA breach
                if (! $ticket->isClosed() && $queueItem->sla_deadline <= now()) {
                    $shouldEscalate = true;
                    $escalationReason = 'Resolution SLA breached';
                } elseif (! $ticket->isClosed() && $queueItem->sla_deadline <= now()->addHours(1)) {
                    $shouldEscalate = true;
                    $escalationReason = 'Resolution SLA at risk (1 hour remaining)';
                }

                if ($shouldEscalate) {
                    $this->escalateTicket($ticket, $escalationReason);
                    $escalatedTickets->push($ticket);

                    // Mark as escalated to avoid repeated escalations
                    $queueItem->is_escalated = true;
                    $queueItem->escalated_at = now();
                    $queueItem->escalation_reason = $escalationReason;
                    $queueItem->save();
                }
            }

            Log::info('Escalation check completed', [
                'tickets_checked' => $ticketsToCheck->count(),
                'tickets_escalated' => $escalatedTickets->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error during escalation check', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $escalatedTickets;
    }

    /**
     * Escalate a ticket
     */
    protected function escalateTicket(Ticket $ticket, string $reason): void
    {
        try {
            // Update priority if not already critical
            if ($ticket->priority !== Ticket::PRIORITY_CRITICAL) {
                $ticket->priority = Ticket::PRIORITY_HIGH;
                $ticket->save();
            }

            // Notify management
            $this->notificationService->notifyEscalation($ticket, $reason);

            // Re-assign to senior technician if needed
            if ($ticket->assigned_to) {
                $seniorTechnician = $this->findSeniorTechnician($ticket);
                if ($seniorTechnician && $seniorTechnician->id !== $ticket->assigned_to) {
                    $ticket->assigned_to = $seniorTechnician->id;
                    $ticket->save();

                    $this->notificationService->notifyTicketAssigned($ticket, $seniorTechnician);
                }
            }

            Log::warning('Ticket escalated', [
                'ticket_id' => $ticket->id,
                'reason' => $reason,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to escalate ticket', [
                'ticket_id' => $ticket->id,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Find a senior technician for escalated tickets
     */
    protected function findSeniorTechnician(Ticket $ticket): ?User
    {
        return User::where('company_id', $ticket->company_id)
            ->where('is_active', true)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['manager', 'senior_technician', 'admin']);
            })
            ->whereDoesntHave('tickets', function ($q) {
                $q->where('priority', Ticket::PRIORITY_CRITICAL)
                    ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS]);
            })
            ->first();
    }

    /**
     * Bulk assign tickets to technicians
     */
    public function bulkAssignTickets(array $ticketIds, int $technicianId, array $options = []): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'skipped' => [],
        ];

        try {
            DB::beginTransaction();

            $technician = User::findOrFail($technicianId);
            $tickets = Ticket::whereIn('id', $ticketIds)->get();

            foreach ($tickets as $ticket) {
                try {
                    // Skip if already assigned to same technician
                    if ($ticket->assigned_to === $technicianId) {
                        $results['skipped'][] = $ticket->id;

                        continue;
                    }

                    // Skip closed tickets unless forced
                    if ($ticket->isClosed() && ! ($options['force'] ?? false)) {
                        $results['skipped'][] = $ticket->id;

                        continue;
                    }

                    // Update assignment
                    $ticket->assigned_to = $technicianId;
                    $ticket->save();

                    // Create assignment record
                    TicketAssignment::create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $technicianId,
                        'assigned_at' => now(),
                        'assigned_by' => auth()->id(),
                        'assignment_type' => 'bulk',
                        'reason' => $options['reason'] ?? 'Bulk assignment',
                    ]);

                    $results['success'][] = $ticket->id;

                } catch (\Exception $e) {
                    $results['failed'][] = [
                        'ticket_id' => $ticket->id,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // Send bulk notification if configured
            if (! empty($results['success']) && ! ($options['skip_notification'] ?? false)) {
                $this->notificationService->notifyBulkAssignment($technician, $results['success']);
            }

            DB::commit();

            Log::info('Bulk ticket assignment completed', [
                'technician_id' => $technicianId,
                'total' => count($ticketIds),
                'success' => count($results['success']),
                'failed' => count($results['failed']),
                'skipped' => count($results['skipped']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk assignment failed', [
                'error' => $e->getMessage(),
                'ticket_ids' => $ticketIds,
            ]);
            throw $e;
        }

        return $results;
    }

    /**
     * Bulk update ticket status
     */
    public function bulkUpdateStatus(array $ticketIds, string $status, array $options = []): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'skipped' => [],
        ];

        try {
            DB::beginTransaction();

            $tickets = Ticket::whereIn('id', $ticketIds)->get();

            foreach ($tickets as $ticket) {
                try {
                    // Check if transition is allowed
                    if (! $ticket->canTransitionTo($status)) {
                        $results['skipped'][] = [
                            'ticket_id' => $ticket->id,
                            'reason' => 'Status transition not allowed',
                        ];

                        continue;
                    }

                    // Update status
                    $oldStatus = $ticket->status;
                    $ticket->status = $status;

                    // Set closed_at timestamp if closing
                    if (in_array($status, [Ticket::STATUS_CLOSED, Ticket::STATUS_RESOLVED])) {
                        $ticket->closed_at = now();
                        $ticket->closed_by = auth()->id();
                    }

                    $ticket->save();

                    // Log status change
                    activity()
                        ->performedOn($ticket)
                        ->causedBy(auth()->user())
                        ->withProperties([
                            'old_status' => $oldStatus,
                            'new_status' => $status,
                            'bulk_update' => true,
                        ])
                        ->log('Ticket status updated via bulk operation');

                    $results['success'][] = $ticket->id;

                } catch (\Exception $e) {
                    $results['failed'][] = [
                        'ticket_id' => $ticket->id,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // Send notifications if configured
            if (! empty($results['success']) && ! ($options['skip_notification'] ?? false)) {
                $this->notificationService->notifyBulkStatusUpdate($results['success'], $status);
            }

            DB::commit();

            Log::info('Bulk status update completed', [
                'status' => $status,
                'total' => count($ticketIds),
                'success' => count($results['success']),
                'failed' => count($results['failed']),
                'skipped' => count($results['skipped']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk status update failed', [
                'error' => $e->getMessage(),
                'ticket_ids' => $ticketIds,
            ]);
            throw $e;
        }

        return $results;
    }

    /**
     * Track response time for a ticket
     */
    public function trackResponseTime(Ticket $ticket): void
    {
        if (! $ticket->first_response_at) {
            $ticket->first_response_at = now();
            $ticket->response_time_hours = $ticket->created_at->diffInHours(now());
            $ticket->save();

            // Check if response met SLA
            $priorityQueue = $ticket->priorityQueue;
            if ($priorityQueue && $priorityQueue->response_deadline) {
                $priorityQueue->response_met_sla = now() <= $priorityQueue->response_deadline;
                $priorityQueue->actual_response_at = now();
                $priorityQueue->save();
            }

            Log::info('Response time tracked', [
                'ticket_id' => $ticket->id,
                'response_hours' => $ticket->response_time_hours,
            ]);
        }
    }

    /**
     * Calculate resolution time for a closed ticket
     */
    public function calculateResolutionTime(Ticket $ticket): float
    {
        if (! $ticket->closed_at) {
            return 0;
        }

        $resolutionHours = $ticket->created_at->diffInHours($ticket->closed_at);

        // Update ticket with resolution time
        $ticket->resolution_time_hours = $resolutionHours;
        $ticket->save();

        // Check if resolution met SLA
        $priorityQueue = $ticket->priorityQueue;
        if ($priorityQueue && $priorityQueue->sla_deadline) {
            $priorityQueue->resolution_met_sla = $ticket->closed_at <= $priorityQueue->sla_deadline;
            $priorityQueue->actual_resolution_at = $ticket->closed_at;
            $priorityQueue->save();
        }

        Log::info('Resolution time calculated', [
            'ticket_id' => $ticket->id,
            'resolution_hours' => $resolutionHours,
        ]);

        return $resolutionHours;
    }

    /**
     * Route ticket based on category and expertise
     */
    public function routeTicketByCategory(Ticket $ticket): ?User
    {
        if (! $ticket->category) {
            return null;
        }

        // Define category routing rules
        $categoryRouting = [
            'Network' => ['network_admin', 'senior_technician'],
            'Hardware' => ['hardware_specialist', 'technician'],
            'Software' => ['software_engineer', 'developer'],
            'Security' => ['security_analyst', 'senior_technician'],
            'VoIP' => ['voip_specialist', 'telecom_engineer'],
            'Email' => ['email_admin', 'technician'],
            'Backup' => ['backup_admin', 'technician'],
            'Cloud' => ['cloud_architect', 'devops_engineer'],
        ];

        $requiredRoles = $categoryRouting[$ticket->category] ?? ['technician'];

        // Find best match based on expertise and availability
        $technician = User::where('company_id', $ticket->company_id)
            ->where('is_active', true)
            ->whereJsonContains('expertise_areas', $ticket->category)
            ->whereHas('roles', function ($q) use ($requiredRoles) {
                $q->whereIn('name', $requiredRoles);
            })
            ->withCount(['tickets as active_tickets_count' => function ($q) {
                $q->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS]);
            }])
            ->orderBy('active_tickets_count')
            ->first();

        if ($technician) {
            $ticket->assigned_to = $technician->id;
            $ticket->save();

            Log::info('Ticket routed by category', [
                'ticket_id' => $ticket->id,
                'category' => $ticket->category,
                'technician_id' => $technician->id,
            ]);

            return $technician;
        }

        // Fallback to general auto-assignment
        return $this->autoAssignTicket($ticket);
    }

    /**
     * Get tickets requiring immediate attention
     */
    public function getUrgentTickets(int $companyId): Collection
    {
        return Ticket::where('company_id', $companyId)
            ->whereIn('priority', [Ticket::PRIORITY_CRITICAL, Ticket::PRIORITY_HIGH])
            ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS])
            ->with(['client', 'assignee', 'priorityQueue'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Generate SLA performance report
     */
    public function generateSlaPerformanceReport(int $companyId, Carbon $startDate, Carbon $endDate): array
    {
        $tickets = TicketPriorityQueue::whereHas('ticket', function ($q) use ($companyId, $startDate, $endDate) {
            $q->where('company_id', $companyId)
                ->whereBetween('created_at', [$startDate, $endDate]);
        })
            ->with('ticket')
            ->get();

        $totalTickets = $tickets->count();
        $responseMet = $tickets->where('response_met_sla', true)->count();
        $resolutionMet = $tickets->where('resolution_met_sla', true)->count();

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'total_tickets' => $totalTickets,
            'response_sla' => [
                'met' => $responseMet,
                'breached' => $totalTickets - $responseMet,
                'percentage' => $totalTickets > 0 ? round(($responseMet / $totalTickets) * 100, 2) : 0,
            ],
            'resolution_sla' => [
                'met' => $resolutionMet,
                'breached' => $totalTickets - $resolutionMet,
                'percentage' => $totalTickets > 0 ? round(($resolutionMet / $totalTickets) * 100, 2) : 0,
            ],
            'by_priority' => $this->getSlaByPriority($tickets),
        ];
    }

    /**
     * Get SLA performance by priority
     */
    protected function getSlaByPriority(Collection $tickets): array
    {
        $priorities = [
            Ticket::PRIORITY_CRITICAL,
            Ticket::PRIORITY_HIGH,
            Ticket::PRIORITY_MEDIUM,
            Ticket::PRIORITY_LOW,
        ];

        $result = [];

        foreach ($priorities as $priority) {
            $priorityTickets = $tickets->filter(function ($item) use ($priority) {
                return $item->ticket && $item->ticket->priority === $priority;
            });

            $total = $priorityTickets->count();
            $responseMet = $priorityTickets->where('response_met_sla', true)->count();
            $resolutionMet = $priorityTickets->where('resolution_met_sla', true)->count();

            $result[$priority] = [
                'total' => $total,
                'response_met' => $responseMet,
                'response_percentage' => $total > 0 ? round(($responseMet / $total) * 100, 2) : 0,
                'resolution_met' => $resolutionMet,
                'resolution_percentage' => $total > 0 ? round(($resolutionMet / $total) * 100, 2) : 0,
            ];
        }

        return $result;
    }
}
