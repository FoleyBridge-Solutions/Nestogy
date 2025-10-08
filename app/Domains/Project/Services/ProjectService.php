<?php

namespace App\Domains\Project\Services;

use App\Domains\Project\Models\Project;
use App\Domains\Project\Repositories\ProjectRepository;
use Illuminate\Support\Facades\Cache;

class ProjectService
{
    public function __construct(
        protected ProjectRepository $repository,
        protected ProjectMetricsService $metricsService,
        protected ProjectHealthService $healthService,
        protected ProjectBudgetService $budgetService,
        protected ProjectTeamService $teamService,
        protected ProjectActivityService $activityService
    ) {}

    public function getProjectDashboard(Project $project): array
    {
        return Cache::remember("project_dashboard_{$project->id}", 300, function () use ($project) {
            return [
                'overview' => $this->metricsService->getProjectOverview($project),
                'statistics' => $this->metricsService->getProjectStatistics($project),
                'timeline' => $this->metricsService->getProjectTimeline($project),
                'team' => $this->teamService->getTeamMetrics($project),
                'budget' => $this->budgetService->getBudgetMetrics($project),
                'health' => $this->healthService->getHealthMetrics($project),
                'risks' => $this->healthService->getRiskAssessment($project),
                'milestones' => $this->metricsService->getMilestoneProgress($project),
                'tasks' => $this->metricsService->getTaskMetrics($project),
                'activity' => $this->activityService->getRecentActivity($project),
            ];
        });
    }

    public function getProjectOverview(Project $project): array
    {
        return $this->metricsService->getProjectOverview($project);
    }

    public function getProjectStatistics(Project $project): array
    {
        return $this->metricsService->getProjectStatistics($project);
    }

    public function getProjectTimeline(Project $project): array
    {
        return $this->metricsService->getProjectTimeline($project);
    }

    public function getTeamMetrics(Project $project): array
    {
        return $this->teamService->getTeamMetrics($project);
    }

    public function getBudgetMetrics(Project $project): array
    {
        return $this->budgetService->getBudgetMetrics($project);
    }

    public function getHealthMetrics(Project $project): array
    {
        return $this->healthService->getHealthMetrics($project);
    }

    public function getRiskAssessment(Project $project): array
    {
        return $this->healthService->getRiskAssessment($project);
    }

    public function getMilestoneProgress(Project $project): array
    {
        return $this->metricsService->getMilestoneProgress($project);
    }

    public function getTaskMetrics(Project $project): array
    {
        return $this->metricsService->getTaskMetrics($project);
    }

    public function getRecentActivity(Project $project, int $limit = 20): array
    {
        return $this->activityService->getRecentActivity($project, $limit);
    }
}
