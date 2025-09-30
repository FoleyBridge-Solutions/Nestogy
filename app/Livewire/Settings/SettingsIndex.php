<?php

namespace App\Livewire\Settings;

use Livewire\Component;

class SettingsIndex extends Component
{
    public $activeCategory = 'general';

    public function getSettingCategories()
    {
        return [
            'general' => [
                'name' => 'General',
                'icon' => 'cog-6-tooth',
                'description' => 'Company information and basic settings',
                'route' => 'settings.general',
            ],
            'email' => [
                'name' => 'Email',
                'icon' => 'envelope',
                'description' => 'Email configuration and templates',
                'route' => 'settings.email',
            ],
            'billing' => [
                'name' => 'Billing & Financial',
                'icon' => 'banknotes',
                'description' => 'Billing, invoicing, and payment settings',
                'route' => 'settings.billing-financial',
            ],
            'integrations' => [
                'name' => 'Integrations',
                'icon' => 'puzzle-piece',
                'description' => 'Third-party service integrations',
                'route' => 'settings.integrations',
            ],
            'security' => [
                'name' => 'Security',
                'icon' => 'shield-check',
                'description' => 'Security and access control settings',
                'route' => 'settings.security',
            ],
            'tickets' => [
                'name' => 'Tickets',
                'icon' => 'ticket',
                'description' => 'Ticket system configuration',
                'route' => 'settings.ticketing-service-desk',
            ],
            'projects' => [
                'name' => 'Projects',
                'icon' => 'rectangle-group',
                'description' => 'Project management settings',
                'route' => 'settings.project-management',
            ],
            'assets' => [
                'name' => 'Assets',
                'icon' => 'computer-desktop',
                'description' => 'Asset management configuration',
                'route' => 'settings.asset-inventory',
            ],
            'contracts' => [
                'name' => 'Contracts',
                'icon' => 'document-text',
                'description' => 'Contract templates and clauses',
                'route' => 'settings.contract-templates.index',
            ],
            'automation' => [
                'name' => 'Automation',
                'icon' => 'bolt',
                'description' => 'Workflows and automation rules',
                'route' => 'settings.automation-workflows',
            ],
            'api' => [
                'name' => 'API & Webhooks',
                'icon' => 'code-bracket',
                'description' => 'API keys and webhook configuration',
                'route' => 'settings.api-webhooks',
            ],
            'data' => [
                'name' => 'Data Management',
                'icon' => 'circle-stack',
                'description' => 'Backup, export, and data retention',
                'route' => 'settings.data-management',
            ],
        ];
    }

    public function setActiveCategory($category)
    {
        $this->activeCategory = $category;
    }

    public function render()
    {
        return view('livewire.settings.settings-index', [
            'categories' => $this->getSettingCategories(),
        ]);
    }
}
