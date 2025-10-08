<?php

namespace App\Livewire\Project\Concerns;

trait ManagesTimelineItems
{
    public function createTask()
    {
        $this->dispatch('open-task-modal', ['project_id' => $this->project->id]);
    }

    public function editTask(int $taskId)
    {
        $this->dispatch('open-task-modal', ['task_id' => $taskId]);
    }

    public function createMilestone()
    {
        $this->dispatch('open-milestone-modal', ['project_id' => $this->project->id]);
    }

    public function editMilestone(int $milestoneId)
    {
        $this->dispatch('open-milestone-modal', ['milestone_id' => $milestoneId]);
    }

    public function updateTaskDates(int $taskId, string $startDate, string $endDate)
    {
        $task = $this->project->tasks()->find($taskId);
        if ($task) {
            $task->update([
                'start_date' => $startDate,
                'due_date' => $endDate,
            ]);

            $this->dispatch('timeline-updated');
        }
    }
}
