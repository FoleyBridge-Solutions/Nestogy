<?php

namespace App\Domains\Project\Services;

use App\Domains\Project\Models\Project;

class ProjectHealthService
{
    public function __construct(
        protected ProjectBudgetService $budgetService
    ) {}

    public function getHealthMetrics(Project $project): array
    {
        $health = $project->getHealthStatus();
        $risks = $this->identifyRisks($project);
        $score = $this->calculateHealthScore($project);

        return [
            'overall_status' => $health['status'],
            'health_score' => $score,
            'indicators' => [
                'schedule' => $this->assessScheduleHealth($project),
                'budget' => $this->assessBudgetHealth($project),
                'scope' => $this->assessScopeHealth($project),
                'team' => $this->assessTeamHealth($project),
                'quality' => $this->assessQualityHealth($project),
            ],
            'risks' => $risks,
            'recommendations' => $this->generateRecommendations($project, $health, $risks),
        ];
    }

    public function getRiskAssessment(Project $project): array
    {
        $risks = [];

        if ($project->due && $project->due->isPast() && $project->status !== Project::STATUS_COMPLETED) {
            $risks[] = [
                'type' => 'schedule',
                'severity' => 'high',
                'title' => 'Project Overdue',
                'description' => "Project is {$project->due->diffInDays(now())} days overdue",
                'mitigation' => 'Review timeline and reallocate resources or adjust deadline',
            ];
        }

        $budgetMetrics = $this->budgetService->getBudgetMetrics($project);
        if ($budgetMetrics['budget_utilization'] > 90) {
            $risks[] = [
                'type' => 'budget',
                'severity' => $budgetMetrics['budget_utilization'] > 100 ? 'critical' : 'high',
                'title' => 'Budget Risk',
                'description' => "Budget utilization at {$budgetMetrics['budget_utilization']}%",
                'mitigation' => 'Review expenses and consider budget reallocation',
            ];
        }

        $teamMembers = $project->members()->active()->count();
        if ($teamMembers < 2) {
            $risks[] = [
                'type' => 'resource',
                'severity' => 'medium',
                'title' => 'Limited Resources',
                'description' => 'Project has minimal team members',
                'mitigation' => 'Consider adding team members for critical tasks',
            ];
        }

        $overdueTasks = $project->tasks->filter(fn ($task) => $task->isOverdue())->count();
        if ($overdueTasks > 5) {
            $risks[] = [
                'type' => 'execution',
                'severity' => 'high',
                'title' => 'Multiple Overdue Tasks',
                'description' => "{$overdueTasks} tasks are overdue",
                'mitigation' => 'Prioritize overdue tasks and reassign if needed',
            ];
        }

        return $risks;
    }

    protected function identifyRisks(Project $project): array
    {
        return $this->getRiskAssessment($project);
    }

    protected function calculateHealthScore(Project $project): int
    {
        $score = 100;

        if ($project->due && $project->due->isPast() && $project->status !== Project::STATUS_COMPLETED) {
            $score -= 30;
        }

        $budgetMetrics = $this->budgetService->getBudgetMetrics($project);
        if ($budgetMetrics['budget_utilization'] > 100) {
            $score -= 25;
        } elseif ($budgetMetrics['budget_utilization'] > 90) {
            $score -= 15;
        }

        $overdueTasks = $project->tasks->filter(fn ($task) => $task->isOverdue())->count();
        if ($overdueTasks > 0) {
            $score -= min(20, $overdueTasks * 2);
        }

        $overdueMilestones = $project->milestones->filter(fn ($m) => ! $m->isCompleted() && $m->due_date?->isPast())->count();
        if ($overdueMilestones > 0) {
            $score -= min(15, $overdueMilestones * 5);
        }

        return max(0, $score);
    }

    protected function assessScheduleHealth(Project $project): array
    {
        $progress = $project->getCalculatedProgress();
        $expected = $project->getExpectedProgress();
        $variance = $progress - $expected;

        return [
            'status' => $variance >= -5 ? 'good' : ($variance >= -15 ? 'warning' : 'critical'),
            'variance' => $variance,
            'message' => $variance >= 0 ? 'On schedule' : "{$variance}% behind schedule",
        ];
    }

    protected function assessBudgetHealth(Project $project): array
    {
        $metrics = $this->budgetService->getBudgetMetrics($project);
        $utilization = $metrics['budget_utilization'];

        return [
            'status' => $utilization <= 80 ? 'good' : ($utilization <= 95 ? 'warning' : 'critical'),
            'utilization' => $utilization,
            'message' => $utilization <= 100 ? "{$utilization}% of budget used" : ($utilization - 100).'% over budget',
        ];
    }

    protected function assessScopeHealth(Project $project): array
    {
        $tasks = $project->tasks;
        $completedTasks = $tasks->where('status', \App\Domains\Project\Models\Task::STATUS_COMPLETED)->count();
        $completionRate = $tasks->count() > 0 ? round(($completedTasks / $tasks->count()) * 100) : 0;

        return [
            'status' => $completionRate >= 70 ? 'good' : ($completionRate >= 50 ? 'warning' : 'critical'),
            'completion_rate' => $completionRate,
            'message' => "{$completionRate}% of tasks completed",
        ];
    }

    protected function assessTeamHealth(Project $project): array
    {
        $members = $project->members()->active()->get();
        if ($members->isEmpty()) {
            $utilization = 0;
        } else {
            $totalUtilization = 0;
            foreach ($members as $member) {
                $assignedTasks = $project->tasks()
                    ->where('assigned_to', $member->user_id)
                    ->whereNotIn('status', [\App\Domains\Project\Models\Task::STATUS_COMPLETED, \App\Domains\Project\Models\Task::STATUS_CANCELLED])
                    ->count();

                $totalUtilization += min(100, ($assignedTasks * 20));
            }
            $utilization = round($totalUtilization / $members->count());
        }

        return [
            'status' => $utilization <= 80 ? 'good' : ($utilization <= 95 ? 'warning' : 'critical'),
            'utilization' => $utilization,
            'message' => $utilization <= 80 ? 'Team capacity healthy' : 'Team may be overloaded',
        ];
    }

    protected function assessQualityHealth(Project $project): array
    {
        $tasks = $project->tasks()->where('status', \App\Domains\Project\Models\Task::STATUS_COMPLETED)->get();

        $totalEstimated = $tasks->sum('estimated_hours');
        $totalActual = $tasks->sum('actual_hours');

        if ($totalEstimated == 0 || $totalActual == 0) {
            $efficiency = 100;
        } else {
            $efficiency = round(($totalEstimated / $totalActual) * 100);
        }

        return [
            'status' => $efficiency >= 80 ? 'good' : ($efficiency >= 60 ? 'warning' : 'critical'),
            'efficiency' => $efficiency,
            'message' => "{$efficiency}% efficiency rating",
        ];
    }

    protected function generateRecommendations(Project $project, array $health, array $risks): array
    {
        $recommendations = [];

        foreach ($risks as $risk) {
            if ($risk['severity'] === 'critical' || $risk['severity'] === 'high') {
                $recommendations[] = $risk['mitigation'];
            }
        }

        if ($health['status'] === 'warning' || $health['status'] === 'critical') {
            $recommendations[] = 'Schedule a project review meeting with stakeholders';
        }

        return array_unique($recommendations);
    }
}
