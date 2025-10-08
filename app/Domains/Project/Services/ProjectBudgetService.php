<?php

namespace App\Domains\Project\Services;

use App\Domains\Project\Models\Project;

class ProjectBudgetService
{
    public function getBudgetMetrics(Project $project): array
    {
        $actualCost = $project->actual_cost ?? 0;
        $budget = $project->budget ?? 0;
        $laborCost = $this->calculateLaborCost($project);
        $expensesCost = 0;

        return [
            'budget' => $budget,
            'actual_cost' => $actualCost,
            'labor_cost' => $laborCost,
            'expenses_cost' => $expensesCost,
            'total_cost' => $laborCost + $expensesCost,
            'remaining_budget' => $budget - ($laborCost + $expensesCost),
            'budget_utilization' => $budget > 0 ? round((($laborCost + $expensesCost) / $budget) * 100) : 0,
            'variance' => $budget - ($laborCost + $expensesCost),
            'variance_percentage' => $budget > 0 ? round(((($budget - ($laborCost + $expensesCost)) / $budget) * 100)) : 0,
            'burn_rate' => $this->calculateBurnRate($project),
            'projected_cost' => $this->projectFinalCost($project),
            'cost_performance_index' => $this->calculateCPI($project),
            'currency' => $project->budget_currency ?? 'USD',
        ];
    }

    protected function calculateLaborCost(Project $project): float
    {
        return $project->budget ? $project->budget * 0.6 : 0;
    }

    protected function calculateBurnRate(Project $project): float
    {
        $startDate = $project->start_date ?? $project->created_at;
        $daysElapsed = $startDate->diffInDays(now());

        if ($daysElapsed == 0) {
            return 0;
        }

        $totalSpent = $this->calculateLaborCost($project) + 0;

        return round($totalSpent / $daysElapsed, 2);
    }

    protected function projectFinalCost(Project $project): float
    {
        $burnRate = $this->calculateBurnRate($project);
        $totalDuration = $project->start_date && $project->due
            ? $project->start_date->diffInDays($project->due)
            : 30;

        return $burnRate * $totalDuration;
    }

    protected function calculateCPI(Project $project): float
    {
        $earnedValue = ($project->getCalculatedProgress() / 100) * ($project->budget ?? 0);
        $actualCost = $this->calculateLaborCost($project) + 0;

        if ($actualCost == 0) {
            return 1;
        }

        return round($earnedValue / $actualCost, 2);
    }
}
