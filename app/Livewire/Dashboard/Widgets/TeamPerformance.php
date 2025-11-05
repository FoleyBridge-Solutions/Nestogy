<?php

namespace App\Livewire\Dashboard\Widgets;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Core\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class TeamPerformance extends Component
{
    public Collection $teamMembers;

    public Collection $allTeamMembers;

    public bool $loading = true;

    public string $period = 'week'; // week, month, quarter

    public string $view = 'top'; // top, needs_improvement

    public string $sortBy = 'performance_score';

    public string $sortDirection = 'desc';

    public int $limit = 3;

    public int $loadCount = 0;

    public ?array $selectedMemberDetails = null;

    public bool $showScoreModal = false;

    public function mount()
    {
        $this->teamMembers = collect();
        $this->allTeamMembers = collect();
        $this->loadTeamPerformance();
    }

    #[On('refresh-team-performance')]
    public function loadTeamPerformance()
    {
        $this->loading = true;
        $companyId = Auth::user()->company_id;

        // Cache key based on company, period, and view
        $cacheKey = "team_performance_{$companyId}_{$this->period}_{$this->view}";

        // Use cache for team data (5 minute cache)
        $teamData = Cache::remember($cacheKey, 300, function () use ($companyId) {
            return $this->calculateTeamPerformance($companyId);
        });

        // Sort team members
        $teamData = $teamData->sortBy($this->sortBy, SORT_REGULAR, $this->sortDirection === 'desc');

        $this->allTeamMembers = $teamData;
        $this->teamMembers = $teamData->take($this->limit);
        $this->loading = false;
    }

    protected function calculateTeamPerformance($companyId)
    {
        $now = Carbon::now();
        $periodStart = match ($this->period) {
            'week' => $now->copy()->subDays(7),
            'month' => $now->copy()->subDays(30),
            'quarter' => $now->copy()->subDays(90),
            default => $now->copy()->subDays(7)
        };

        // Get all users with their roles in one query
        $users = User::where('company_id', $companyId)
            ->where('status', true)
            ->where(function ($query) {
                $query->whereHas('roles', function ($q) {
                    $q->whereIn('name', ['tech', 'technician', 'manager', 'admin', 'support']);
                })
                    ->orWhereHas('assignedTickets');
            })
            ->with(['roles'])
            ->get();

        // Batch load all tickets for all users to avoid N+1
        $userIds = $users->pluck('id');

        // Get ticket metrics for all users in one query
        $ticketMetrics = DB::table('tickets')
            ->select(
                'assigned_to',
                DB::raw('COUNT(*) as total_tickets'),
                DB::raw('SUM(CASE WHEN LOWER(status) = \'resolved\' THEN 1 ELSE 0 END) as resolved_tickets'),
                DB::raw('SUM(CASE WHEN LOWER(status) = \'closed\' THEN 1 ELSE 0 END) as closed_tickets'),
                DB::raw('SUM(CASE WHEN LOWER(status) IN (\'open\', \'in progress\', \'in-progress\', \'waiting\') THEN 1 ELSE 0 END) as open_tickets'),
                DB::raw('SUM(CASE WHEN LOWER(priority) = \'critical\' AND LOWER(status) IN (\'open\', \'in progress\', \'in-progress\') THEN 1 ELSE 0 END) as critical_tickets'),
                DB::raw('AVG(CASE WHEN resolved_at IS NOT NULL THEN EXTRACT(epoch FROM (resolved_at - created_at)) / 3600 ELSE NULL END) as avg_resolution_hours')
            )
            ->whereIn('assigned_to', $userIds)
            ->where('company_id', $companyId)
            ->whereNull('archived_at')
            ->where(function ($query) use ($periodStart) {
                $query->where('created_at', '>=', $periodStart)
                    ->orWhere('updated_at', '>=', $periodStart);
            })
            ->groupBy('assigned_to')
            ->get()
            ->keyBy('assigned_to');

        // Get time entry metrics for all users in one query
        $timeMetrics = DB::table('time_entries')
            ->select(
                'user_id',
                DB::raw('SUM(hours) as total_hours'),
                DB::raw('SUM(CASE WHEN billable = true THEN hours ELSE 0 END) as billable_hours')
            )
            ->whereIn('user_id', $userIds)
            ->where('company_id', $companyId)
            ->where('date', '>=', $periodStart->format('Y-m-d'))
            ->whereNull('deleted_at')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        // Calculate metrics for each user
        return $users->map(function ($user) use ($ticketMetrics, $timeMetrics) {
            $userId = $user->id;

            // Get ticket metrics (or defaults)
            $userTicketMetrics = $ticketMetrics->get($userId);
            $totalTickets = $userTicketMetrics->total_tickets ?? 0;
            $resolvedTickets = ($userTicketMetrics->resolved_tickets ?? 0) + ($userTicketMetrics->closed_tickets ?? 0);
            $openTickets = $userTicketMetrics->open_tickets ?? 0;
            $criticalTickets = $userTicketMetrics->critical_tickets ?? 0;
            $avgResolutionHours = $userTicketMetrics->avg_resolution_hours ?? 0;

            // Resolution rate
            $resolutionRate = $totalTickets > 0 ?
                min(100, round(($resolvedTickets / $totalTickets) * 100, 1)) : 0;

            // Get time metrics
            $userTimeMetrics = $timeMetrics->get($userId);
            if ($userTimeMetrics) {
                $totalHours = round($userTimeMetrics->total_hours, 1);
                $billableHours = round($userTimeMetrics->billable_hours, 1);
            } else {
                $totalHours = 0;
                $billableHours = 0;
            }

            $utilizationRate = $totalHours > 0 ?
                round(($billableHours / $totalHours) * 100, 1) : 0;

            // Calculate revenue (simplified)
            $revenueGenerated = $this->calculateRevenue($billableHours, $user->roles->pluck('name')->first());

            // Calculate performance score (no fake customer satisfaction)
            $performanceScore = $this->calculatePerformanceScore(
                $resolutionRate,
                $utilizationRate,
                $avgResolutionHours,
                0,
                $totalHours,
                $totalTickets,
                $openTickets,
                $resolvedTickets,
                $userId
            );
            $customerSat = 0;

            // Determine performance level
            $performanceLevel = match (true) {
                $performanceScore >= 85 => 'excellent',
                $performanceScore >= 70 => 'good',
                $performanceScore >= 55 => 'average',
                default => 'needs_improvement'
            };

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar_url,
                'role' => $user->roles->pluck('name')->first(),
                'performance_score' => $performanceScore,
                'performance_level' => $performanceLevel,
                'trend' => 'stable',
                'total_tickets' => $totalTickets,
                'resolved_tickets' => $resolvedTickets,
                'open_tickets' => $openTickets,
                'critical_tickets' => $criticalTickets,
                'resolution_rate' => $resolutionRate,
                'avg_resolution_time' => round($avgResolutionHours, 1),
                'total_hours' => $totalHours,
                'billable_hours' => $billableHours,
                'utilization_rate' => $utilizationRate,
                'revenue_generated' => $revenueGenerated,
                'customer_satisfaction' => $customerSat,
                'last_active' => $user->last_active_at ?? $user->updated_at,
            ];
        });
    }



    protected function calculateRevenue($billableHours, $role)
    {
        $role = strtolower($role ?? 'tech');
        $hourlyRate = match (true) {
            str_contains($role, 'admin') => 95,
            str_contains($role, 'manager') => 85,
            str_contains($role, 'lead') => 80,
            str_contains($role, 'senior') || str_contains($role, 'sr') => 75,
            str_contains($role, 'specialist') => 70,
            str_contains($role, 'junior') || str_contains($role, 'jr') => 60,
            default => 65
        };

        return round($billableHours * $hourlyRate, 2);
    }



    protected function calculatePerformanceScore(
        $resolutionRate,
        $utilizationRate,
        $avgResolutionHours,
        $customerSat,
        $totalHours,
        $totalTickets,
        $openTickets,
        $resolvedTickets,
        $userId
    ) {
        $performanceScore = 0;

        $hasActiveWork = $openTickets > 0 || $totalTickets > 0;
        $hasResolvedWork = $resolvedTickets > 0;

        if ($hasActiveWork && ! $hasResolvedWork) {
            // For users with work in progress but nothing resolved yet
            $periodDays = $this->getPeriodDays();
            $engagementScore = min(100, ($totalTickets / max(1, $periodDays)) * 100);
            $performanceScore += $engagementScore * 0.4;

            $workloadScore = $openTickets <= 5 ? 100 : max(0, 100 - (($openTickets - 5) * 10));
            $performanceScore += $workloadScore * 0.3;

            $performanceScore += $utilizationRate * 0.2;

            $performanceScore += $customerSat / 5 * 100 * 0.1;
        } else {
            // Standard scoring
            $performanceScore += $resolutionRate * 0.3;
            $performanceScore += $utilizationRate * 0.25;

            $responseScore = $avgResolutionHours > 0 ?
                max(0, 100 - ($avgResolutionHours / 24 * 50)) : 0;
            $performanceScore += $responseScore * 0.2;

            $performanceScore += ($customerSat / 5) * 100 * 0.15;

            $performanceScore += $totalHours > 0 ? min(100, $totalHours / max(1, $totalHours) * 100) * 0.1 : 0;
        }

        return round(max(0, min(100, $performanceScore)), 1);
    }

    protected function getPeriodDays()
    {
        return match ($this->period) {
            'week' => 7,
            'month' => 30,
            'quarter' => 90,
            default => 7
        };
    }

    public function updatedPeriod($value)
    {
        if (in_array($value, ['week', 'month', 'quarter'])) {
            // Clear cache when period changes
            Cache::forget('team_performance_'.Auth::user()->company_id.'_*');
            $this->loadTeamPerformance();
        }
    }

    public function updatedView($value)
    {
        if (in_array($value, ['top', 'needs_improvement'])) {
            $this->sortDirection = $value === 'top' ? 'desc' : 'asc';
            $this->loadTeamPerformance();
        }
    }

    public function sort($field)
    {
        if ($field === 'performance_score') {
            $this->sortDirection = $this->view === 'top' ? 'desc' : 'asc';
        } else {
            if ($this->sortBy === $field) {
                $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                $this->sortBy = $field;
                $this->sortDirection = 'desc';
            }
        }

        $this->sortBy = $field;
        $this->loadTeamPerformance();
    }

    public function loadMore()
    {
        $this->loadCount++;

        if ($this->loadCount === 1) {
            $this->limit = 10;
        } elseif ($this->loadCount === 2) {
            $this->limit = 20;
        } else {
            $this->limit += 10;
        }

        $this->loadTeamPerformance();
    }

    public function showScoreDetails($memberId)
    {
        $member = $this->allTeamMembers->firstWhere('id', $memberId);
        if ($member) {
            $this->selectedMemberDetails = $this->getDetailedScoreBreakdown($member);
            $this->showScoreModal = true;
        }
    }

    public function closeScoreModal()
    {
        $this->showScoreModal = false;
        $this->selectedMemberDetails = null;
    }

    protected function getDetailedScoreBreakdown($memberData)
    {
        $breakdown = $this->buildBaseBreakdown($memberData);

        $hasActiveWork = $memberData['open_tickets'] > 0 || $memberData['total_tickets'] > 0;
        $hasResolvedWork = $memberData['resolved_tickets'] > 0;

        if ($hasActiveWork && ! $hasResolvedWork) {
            $breakdown['scoring_mode'] = 'in_progress';
            $breakdown['components'] = $this->buildInProgressComponents($memberData);
        } else {
            $breakdown['scoring_mode'] = 'standard';
            $breakdown['components'] = $this->buildStandardComponents($memberData);
        }

        $breakdown['total_calculated'] = $memberData['performance_score'];

        return $breakdown;
    }

    protected function buildBaseBreakdown($memberData)
    {
        return [
            'member_name' => $memberData['name'],
            'role' => $memberData['role'],
            'total_score' => $memberData['performance_score'],
            'performance_level' => $memberData['performance_level'],
            'period' => $this->period,
            'components' => [],
            'metrics' => [
                'total_tickets' => $memberData['total_tickets'],
                'resolved_tickets' => $memberData['resolved_tickets'],
                'open_tickets' => $memberData['open_tickets'],
                'critical_tickets' => $memberData['critical_tickets'],
                'resolution_rate' => $memberData['resolution_rate'],
                'avg_resolution_time' => $memberData['avg_resolution_time'],
                'total_hours' => $memberData['total_hours'],
                'billable_hours' => $memberData['billable_hours'],
                'utilization_rate' => $memberData['utilization_rate'],
                'customer_satisfaction' => $memberData['customer_satisfaction'],
            ],
        ];
    }

    protected function buildInProgressComponents($memberData)
    {
        $components = [];

        $engagementScore = min(100, ($memberData['total_tickets'] / max(1, $this->getPeriodDays())) * 100);
        $components[] = $this->buildScoreComponent(
            'Activity & Engagement',
            'Based on ticket activity in the period',
            '40%',
            $engagementScore,
            0.4,
            'lightning-bolt'
        );

        $workloadScore = $memberData['open_tickets'] <= 5 ? 100 : max(0, 100 - (($memberData['open_tickets'] - 5) * 10));
        $components[] = $this->buildScoreComponent(
            'Workload Management',
            'Ability to manage open tickets effectively',
            '30%',
            $workloadScore,
            0.3,
            'briefcase'
        );

        $components[] = $this->buildScoreComponent(
            'Time Utilization',
            'Billable hours vs total hours',
            '20%',
            $memberData['utilization_rate'],
            0.2,
            'clock'
        );

        $satScore = ($memberData['customer_satisfaction'] > 0 ? $memberData['customer_satisfaction'] : 3.0) / 5 * 100;
        $components[] = $this->buildScoreComponent(
            'Customer Satisfaction',
            'Average customer satisfaction rating',
            '10%',
            $satScore,
            0.1,
            'star'
        );

        return $components;
    }

    protected function buildStandardComponents($memberData)
    {
        $components = [];

        $cappedResolutionRate = min(100, $memberData['resolution_rate']);
        $components[] = $this->buildScoreComponent(
            'Resolution Rate',
            'Percentage of tickets resolved',
            '30%',
            $cappedResolutionRate,
            0.3,
            'check-circle'
        );

        $components[] = $this->buildScoreComponent(
            'Utilization Rate',
            'Billable hours efficiency',
            '25%',
            $memberData['utilization_rate'],
            0.25,
            'trending-up'
        );

        $responseScore = $memberData['avg_resolution_time'] > 0 ?
            max(0, 100 - ($memberData['avg_resolution_time'] / 24 * 50)) : 100;
        $components[] = $this->buildScoreComponent(
            'Response Time',
            'Average ticket resolution speed',
            '20%',
            $responseScore,
            0.2,
            'clock'
        );

        $satScore = ($memberData['customer_satisfaction'] / 5) * 100;
        $components[] = $this->buildScoreComponent(
            'Customer Satisfaction',
            'Average customer ratings',
            '15%',
            $satScore,
            0.15,
            'star'
        );

        $expectedHours = $this->getPeriodDays() * 6;
        $activityScore = min(100, ($memberData['total_hours'] / max(1, $expectedHours)) * 100);
        $components[] = $this->buildScoreComponent(
            'Activity Level',
            'Overall work activity',
            '10%',
            $activityScore,
            0.1,
            'activity'
        );

        return $components;
    }

    protected function buildScoreComponent($name, $description, $weight, $rawScore, $weightMultiplier, $icon)
    {
        $roundedScore = round($rawScore, 1);

        return [
            'name' => $name,
            'description' => $description,
            'weight' => $weight,
            'raw_score' => $roundedScore,
            'weighted_score' => round($rawScore * $weightMultiplier, 1),
            'icon' => $icon,
            'color' => $this->determineScoreColor($roundedScore),
        ];
    }

    protected function determineScoreColor($score)
    {
        if ($score >= 70) {
            return 'green';
        }

        if ($score >= 40) {
            return 'yellow';
        }

        return 'red';
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.team-performance');
    }
}
