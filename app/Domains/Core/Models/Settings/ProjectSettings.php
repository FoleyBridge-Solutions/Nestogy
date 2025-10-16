<?php

namespace App\Domains\Core\Models\Settings;

class ProjectSettings extends SettingCategory
{
    public function getCategory(): string
    {
        return 'project';
    }

    public function getAttributes(): array
    {
        return [
            'project_templates',
            'project_standardization_settings',
            'resource_allocation_settings',
            'capacity_planning_settings',
            'project_time_tracking_enabled',
            'project_billing_settings',
            'milestone_settings',
            'deliverable_settings',
            'gantt_chart_settings',
            'budget_management_settings',
            'profitability_tracking_settings',
            'change_request_workflows',
            'project_collaboration_settings',
            'document_management_settings',
        ];
    }

    public function getProjectTemplates(): ?array
    {
        return $this->get('project_templates');
    }

    public function setProjectTemplates(?array $templates): self
    {
        $this->set('project_templates', $templates);
        return $this;
    }

    public function isProjectTimeTrackingEnabled(): bool
    {
        return (bool) $this->get('project_time_tracking_enabled', false);
    }

    public function setProjectTimeTrackingEnabled(bool $enabled): self
    {
        $this->set('project_time_tracking_enabled', $enabled);
        return $this;
    }

    public function getProjectBillingSettings(): ?array
    {
        return $this->get('project_billing_settings');
    }

    public function setProjectBillingSettings(?array $settings): self
    {
        $this->set('project_billing_settings', $settings);
        return $this;
    }

    public function getMilestoneSettings(): ?array
    {
        return $this->get('milestone_settings');
    }

    public function setMilestoneSettings(?array $settings): self
    {
        $this->set('milestone_settings', $settings);
        return $this;
    }

    public function getBudgetManagementSettings(): ?array
    {
        return $this->get('budget_management_settings');
    }

    public function setBudgetManagementSettings(?array $settings): self
    {
        $this->set('budget_management_settings', $settings);
        return $this;
    }
}
