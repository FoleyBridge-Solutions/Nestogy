<?php

namespace App\Domains\Core\Services\Settings;

use App\Domains\Core\Models\SettingsConfiguration;
use App\Domains\Integration\Models\RmmIntegration;
use Illuminate\Support\Facades\Auth;

class IntegrationsSettingsService extends BaseSettingsService
{
    protected string $domain = SettingsConfiguration::DOMAIN_INTEGRATIONS;

    protected function getValidationRules(string $category): array
    {
        $rules = [
            'rmm' => [
                'auto_sync_enabled' => 'boolean',
                'sync_interval_minutes' => 'nullable|integer|min:5|max:1440',
                'sync_agents_enabled' => 'boolean',
                'sync_alerts_enabled' => 'boolean',
                'alert_severity_filter' => 'nullable|array',
                'alert_severity_filter.*' => 'string|in:critical,high,medium,low,info',
                'create_tickets_from_alerts' => 'boolean',
                'ticket_priority_mapping' => 'nullable|array',
            ],
            'accounting' => [
                'provider' => 'nullable|string|in:quickbooks,xero,sage',
                'auto_sync_enabled' => 'boolean',
                'sync_interval_hours' => 'nullable|integer|min:1|max:24',
            ],
            'apis' => [
                'rate_limit_per_minute' => 'integer|min:10|max:1000',
                'webhook_retry_enabled' => 'boolean',
                'webhook_max_retries' => 'integer|min:0|max:10',
            ],
        ];

        return $rules[$category] ?? [];
    }

    public function getDefaultSettings(string $category): array
    {
        $defaults = [];

        switch ($category) {
            case 'rmm':
                $defaults = [
                    'auto_sync_enabled' => true,
                    'sync_interval_minutes' => 60,
                    'sync_agents_enabled' => true,
                    'sync_alerts_enabled' => true,
                    'alert_severity_filter' => ['critical', 'high', 'medium'],
                    'create_tickets_from_alerts' => false,
                    'ticket_priority_mapping' => [
                        'critical' => 'urgent',
                        'high' => 'high',
                        'medium' => 'normal',
                        'low' => 'low',
                    ],
                ];
                break;

            case 'accounting':
                $defaults = [
                    'provider' => null,
                    'auto_sync_enabled' => false,
                    'sync_interval_hours' => 24,
                ];
                break;

            case 'apis':
                $defaults = [
                    'rate_limit_per_minute' => 100,
                    'webhook_retry_enabled' => true,
                    'webhook_max_retries' => 3,
                ];
                break;

            case 'overview':
                // Get all configured integrations for the company
                $company = Auth::user()?->company;
                $rmmIntegrations = $company ? RmmIntegration::where('company_id', $company->id)->get() : collect();
                
                $defaults = [
                    'rmm_count' => $rmmIntegrations->count(),
                    'rmm_active_count' => $rmmIntegrations->where('is_active', true)->count(),
                    'accounting_configured' => false, // Will be updated when accounting integrations are implemented
                    'webhooks_configured' => false, // Will be updated when webhooks are implemented
                ];
                break;
        }

        return $defaults;
    }

    public function getCategoryMetadata(string $category): array
    {
        $metadata = [
            'rmm' => [
                'name' => 'RMM Integration',
                'description' => 'Configure Remote Monitoring and Management system integrations',
                'icon' => 'server-stack',
                'requires_setup' => true,
            ],
            'accounting' => [
                'name' => 'Accounting',
                'description' => 'Integrate with accounting software like QuickBooks or Xero',
                'icon' => 'calculator',
                'requires_setup' => true,
            ],
            'apis' => [
                'name' => 'API & Webhooks',
                'description' => 'Configure API access and webhook settings',
                'icon' => 'code-bracket',
            ],
            'overview' => [
                'name' => 'Integrations Overview',
                'description' => 'View all configured integrations',
                'icon' => 'puzzle-piece',
            ],
        ];

        return $metadata[$category] ?? [];
    }

    /**
     * Get settings for RMM category - includes integration list
     */
    public function getSettings(string $category): array
    {
        // For RMM, we want to include a list of configured integrations
        if ($category === 'rmm') {
            $settings = parent::getSettings($category);
            
            // If no settings exist, use defaults
            if (empty($settings)) {
                $settings = $this->getDefaultSettings($category);
            }
            
            // Get RMM integrations for the company
            $company = Auth::user()?->company;
            if ($company) {
                $integrations = RmmIntegration::where('company_id', $company->id)
                    ->select(['id', 'name', 'rmm_type', 'is_active', 'last_sync_at', 'total_agents'])
                    ->get()
                    ->map(function ($integration) {
                        return [
                            'id' => $integration->id,
                            'name' => $integration->name,
                            'type' => $integration->rmm_type,
                            'is_active' => $integration->is_active,
                            'last_sync' => $integration->last_sync_at?->diffForHumans(),
                            'total_agents' => $integration->total_agents,
                        ];
                    });
                
                $settings['_integrations'] = $integrations;
            }
            
            return $settings;
        }
        
        return parent::getSettings($category);
    }
}
