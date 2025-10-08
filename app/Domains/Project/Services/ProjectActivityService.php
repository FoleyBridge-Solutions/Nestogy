<?php

namespace App\Domains\Project\Services;

use App\Domains\Project\Models\Project;

class ProjectActivityService
{
    public function getRecentActivity(Project $project, int $limit = 20): array
    {
        $activities = [];

        $recentTasks = $project->tasks()
            ->with('assignedUser')
            ->latest()
            ->limit(5)
            ->get();

        foreach ($recentTasks as $task) {
            $activities[] = [
                'type' => 'task',
                'action' => 'created',
                'title' => "Task created: {$task->name}",
                'user' => $task->createdBy?->name ?? 'System',
                'timestamp' => $task->created_at,
                'metadata' => [
                    'task_id' => $task->id,
                    'assignee' => $task->assignedUser?->name,
                ],
            ];
        }

        $recentComments = $project->comments()
            ->with('user')
            ->latest()
            ->limit(5)
            ->get();

        foreach ($recentComments as $comment) {
            $activities[] = [
                'type' => 'comment',
                'action' => 'added',
                'title' => 'Comment added',
                'user' => $comment->user?->name ?? 'Unknown',
                'timestamp' => $comment->created_at,
                'metadata' => [
                    'comment_preview' => \Str::limit($comment->content, 100),
                ],
            ];
        }

        $recentTimeEntries = collect([]);

        foreach ($recentTimeEntries as $entry) {
            $activities[] = [
                'type' => 'time_entry',
                'action' => 'logged',
                'title' => "{$entry->hours} hours logged",
                'user' => $entry->user?->name ?? 'Unknown',
                'timestamp' => $entry->created_at,
                'metadata' => [
                    'task' => $entry->task?->name,
                    'description' => $entry->description,
                ],
            ];
        }

        usort($activities, fn ($a, $b) => $b['timestamp']->timestamp - $a['timestamp']->timestamp);

        return array_slice($activities, 0, $limit);
    }
}
