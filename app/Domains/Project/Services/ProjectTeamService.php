<?php

namespace App\Domains\Project\Services;

use App\Domains\Project\Models\Project;
use App\Domains\Project\Models\ProjectMember;
use App\Domains\Project\Models\Task;

class ProjectTeamService
{
    public function getTeamMetrics(Project $project): array
    {
        $members = $project->members()->with('user')->active()->get();

        $teamData = [];
        foreach ($members as $member) {
            $userTasks = $project->tasks()->where('assigned_to', $member->user_id)->get();
            $completedTasks = $userTasks->where('status', Task::STATUS_COMPLETED)->count();
            $totalHours = 0;

            $teamData[] = [
                'id' => $member->user_id,
                'name' => $member->user->name,
                'role' => $member->role,
                'avatar' => $member->user->avatar_url ?? null,
                'joined_date' => $member->joined_at?->format('Y-m-d'),
                'tasks' => [
                    'total' => $userTasks->count(),
                    'completed' => $completedTasks,
                    'in_progress' => $userTasks->where('status', Task::STATUS_IN_PROGRESS)->count(),
                    'overdue' => $userTasks->filter(fn ($task) => $task->isOverdue())->count(),
                ],
                'hours' => [
                    'logged' => $totalHours,
                    'estimated' => $userTasks->sum('estimated_hours'),
                    'remaining' => $userTasks->where('status', '!=', Task::STATUS_COMPLETED)->sum('estimated_hours'),
                ],
                'productivity' => [
                    'completion_rate' => $userTasks->count() > 0
                        ? round(($completedTasks / $userTasks->count()) * 100)
                        : 0,
                    'efficiency' => $this->calculateMemberEfficiency($member, $userTasks),
                ],
                'availability' => $member->availability_percentage ?? 100,
                'permissions' => [
                    'can_edit' => $member->can_edit,
                    'can_manage_tasks' => $member->can_manage_tasks,
                    'can_view_reports' => $member->can_view_reports,
                ],
            ];
        }

        $memberCount = count($teamData);

        return [
            'members' => $teamData,
            'summary' => [
                'total_members' => $memberCount,
                'total_capacity' => $memberCount > 0
                    ? array_sum(array_column($teamData, 'availability')) / $memberCount
                    : 0,
                'average_productivity' => $memberCount > 0
                    ? array_sum(array_column(array_column($teamData, 'productivity'), 'completion_rate')) / $memberCount
                    : 0,
            ],
        ];
    }

    protected function calculateMemberEfficiency(ProjectMember $member, $tasks): float
    {
        $completedTasks = $tasks->where('status', Task::STATUS_COMPLETED);

        $estimatedHours = $completedTasks->sum('estimated_hours');
        $actualHours = $completedTasks->sum('actual_hours');

        if ($estimatedHours == 0 || $actualHours == 0) {
            return 100;
        }

        return round(($estimatedHours / $actualHours) * 100);
    }
}
