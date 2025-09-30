<?php

namespace App\Policies;

use App\Domains\Project\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('projects.view') || $user->can('projects.*');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Project $project): bool
    {
        if (! $user->can('projects.view') && ! $user->can('projects.*')) {
            return false;
        }

        // Check if user belongs to same company
        if (! $this->sameCompany($user, $project)) {
            return false;
        }

        // Project managers can view their projects
        if ($project->manager_id === $user->id) {
            return true;
        }

        // Check if user is a project member
        if ($this->isProjectMember($user, $project)) {
            return true;
        }

        // Users with manage permission can view all projects
        return $user->can('projects.manage') || $user->can('projects.*');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('projects.create') || $user->can('projects.*');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        if (! $user->can('projects.edit') && ! $user->can('projects.*')) {
            return false;
        }

        if (! $this->sameCompany($user, $project)) {
            return false;
        }

        // Project managers can edit their projects
        if ($project->manager_id === $user->id) {
            return true;
        }

        // Users with manage permission can edit all projects
        return $this->hasProjectPermission($user, 'projects.manage');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        if (! $user->can('projects.delete') && ! $user->can('projects.*')) {
            return false;
        }

        if (! $this->sameCompany($user, $project)) {
            return false;
        }

        // Project managers can delete their projects
        if ($project->manager_id === $user->id) {
            return true;
        }

        // Users with manage permission can delete all projects
        return $this->hasProjectPermission($user, 'projects.manage');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        return $this->hasProjectPermission($user, 'projects.manage') && $this->sameCompany($user, $project);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return $this->hasProjectPermission($user, 'projects.manage') && $this->sameCompany($user, $project);
    }

    /**
     * Determine whether the user can export projects.
     */
    public function export(User $user): bool
    {
        return $this->hasProjectPermission($user, 'projects.export');
    }

    // Task-related permissions
    /**
     * Determine whether the user can view project tasks.
     */
    public function viewTasks(User $user, Project $project): bool
    {
        if (! $user->can('projects.tasks.view')) {
            return false;
        }

        return $this->view($user, $project);
    }

    /**
     * Determine whether the user can manage project tasks.
     */
    public function manageTasks(User $user, Project $project): bool
    {
        if (! $user->can('projects.tasks.manage')) {
            return false;
        }

        if (! $this->sameCompany($user, $project)) {
            return false;
        }

        // Project managers can manage tasks
        if ($project->manager_id === $user->id) {
            return true;
        }

        // Project members with task permissions can manage tasks
        if ($this->isProjectMember($user, $project)) {
            return true; // Could be refined with specific member permissions
        }

        // Users with general manage permission
        return $user->can('projects.manage');
    }

    /**
     * Determine whether the user can export project tasks.
     */
    public function exportTasks(User $user): bool
    {
        return $user->can('projects.tasks.export');
    }

    // Member-related permissions
    /**
     * Determine whether the user can view project members.
     */
    public function viewMembers(User $user, Project $project): bool
    {
        if (! $user->can('projects.members.view')) {
            return false;
        }

        return $this->view($user, $project);
    }

    /**
     * Determine whether the user can manage project members.
     */
    public function manageMembers(User $user, Project $project): bool
    {
        if (! $user->can('projects.members.manage')) {
            return false;
        }

        if (! $this->sameCompany($user, $project)) {
            return false;
        }

        // Project managers can manage members
        if ($project->manager_id === $user->id) {
            return true;
        }

        // Users with general manage permission
        return $user->can('projects.manage');
    }

    /**
     * Determine whether the user can add members to project.
     */
    public function addMembers(User $user, Project $project): bool
    {
        return $this->manageMembers($user, $project);
    }

    /**
     * Determine whether the user can remove members from project.
     */
    public function removeMembers(User $user, Project $project): bool
    {
        return $this->manageMembers($user, $project);
    }

    // Template-related permissions
    /**
     * Determine whether the user can view project templates.
     */
    public function viewTemplates(User $user): bool
    {
        return $user->can('projects.templates.view');
    }

    /**
     * Determine whether the user can manage project templates.
     */
    public function manageTemplates(User $user): bool
    {
        return $user->can('projects.templates.manage');
    }

    /**
     * Determine whether the user can create project from template.
     */
    public function createFromTemplate(User $user): bool
    {
        return $user->canAny(['projects.create', 'projects.templates.manage']);
    }

    // Advanced permissions
    /**
     * Determine whether the user can manage project settings.
     */
    public function manageSettings(User $user, Project $project): bool
    {
        if (! $this->sameCompany($user, $project)) {
            return false;
        }

        // Project managers can manage settings
        if ($project->manager_id === $user->id) {
            return true;
        }

        // Users with general manage permission
        return $user->can('projects.manage');
    }

    /**
     * Determine whether the user can assign project manager.
     */
    public function assignManager(User $user, Project $project): bool
    {
        return $user->can('projects.manage') && $this->sameCompany($user, $project);
    }

    /**
     * Determine whether the user can archive project.
     */
    public function archive(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    /**
     * Determine whether the user can view project reports.
     */
    public function viewReports(User $user, Project $project): bool
    {
        if (! $user->canAny(['projects.view', 'reports.projects'])) {
            return false;
        }

        return $this->view($user, $project);
    }

    /**
     * Check if user and project belong to same company.
     */
    private function sameCompany(User $user, Project $project): bool
    {
        return $user->company_id === $project->company_id;
    }

    /**
     * Check if user has a specific project permission or wildcard.
     */
    private function hasProjectPermission(User $user, string $permission): bool
    {
        // Check for specific permission or wildcard
        return $user->can($permission) || $user->can('projects.*');
    }

    /**
     * Check if user is a project member.
     */
    private function isProjectMember(User $user, Project $project): bool
    {
        // This would check the project_members table
        // For now, we'll assume there's a members relationship
        if (method_exists($project, 'members')) {
            return $project->members()->where('user_id', $user->id)->exists();
        }

        return false;
    }
}
