<?php

namespace App\Models\Settings;

use App\Models\Setting;

class SettingsAggregator
{
    protected Setting $model;
    protected array $categories = [];

    public function __construct(Setting $model)
    {
        $this->model = $model;
        $this->initializeCategories();
    }

    protected function initializeCategories(): void
    {
        $this->categories = [
            'company' => new CompanySettings($this->model),
            'security' => new SecuritySettings($this->model),
            'email' => new EmailSettings($this->model),
            'financial' => new FinancialSettings($this->model),
            'integration' => new IntegrationSettings($this->model),
            'ticketing' => new TicketingSettings($this->model),
            'project' => new ProjectSettings($this->model),
            'asset' => new AssetSettings($this->model),
            'portal' => new PortalSettings($this->model),
            'compliance' => new ComplianceSettings($this->model),
        ];
    }

    public function company(): CompanySettings
    {
        return $this->categories['company'];
    }

    public function security(): SecuritySettings
    {
        return $this->categories['security'];
    }

    public function email(): EmailSettings
    {
        return $this->categories['email'];
    }

    public function financial(): FinancialSettings
    {
        return $this->categories['financial'];
    }

    public function integration(): IntegrationSettings
    {
        return $this->categories['integration'];
    }

    public function ticketing(): TicketingSettings
    {
        return $this->categories['ticketing'];
    }

    public function project(): ProjectSettings
    {
        return $this->categories['project'];
    }

    public function asset(): AssetSettings
    {
        return $this->categories['asset'];
    }

    public function portal(): PortalSettings
    {
        return $this->categories['portal'];
    }

    public function compliance(): ComplianceSettings
    {
        return $this->categories['compliance'];
    }

    public function getCategory(string $category): ?SettingCategory
    {
        return $this->categories[$category] ?? null;
    }

    public function getAllCategories(): array
    {
        return $this->categories;
    }

    public function toArray(): array
    {
        $result = [];
        foreach ($this->categories as $category => $instance) {
            $result[$category] = $instance->toArray();
        }
        return $result;
    }
}
