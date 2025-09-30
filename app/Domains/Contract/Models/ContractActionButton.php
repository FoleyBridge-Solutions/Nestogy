<?php

namespace App\Domains\Contract\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractActionButton extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'label',
        'slug',
        'icon',
        'button_class',
        'action_type',
        'action_config',
        'visibility_conditions',
        'permissions',
        'confirmation_message',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'action_config' => 'array',
        'visibility_conditions' => 'array',
        'permissions' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Scope for active buttons
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordered by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('label');
    }

    /**
     * Check if button should be visible for given contract
     */
    public function isVisibleForContract($contract): bool
    {
        if (! $this->is_active) {
            return false;
        }

        // Check visibility conditions
        if ($this->visibility_conditions) {
            foreach ($this->visibility_conditions as $condition) {
                if (! $this->evaluateCondition($condition, $contract)) {
                    return false;
                }
            }
        }

        // Check permissions if user is available
        if ($this->permissions && auth()->check()) {
            foreach ($this->permissions as $permission) {
                if (! auth()->user()->can($permission, $contract)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Evaluate a single visibility condition
     */
    protected function evaluateCondition(array $condition, $contract): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'] ?? null;

        if (! $field) {
            return true;
        }

        $contractValue = data_get($contract, $field);

        switch ($operator) {
            case '=':
                return $contractValue == $value;
            case '!=':
                return $contractValue != $value;
            case 'in':
                return is_array($value) && in_array($contractValue, $value);
            case 'not_in':
                return is_array($value) && ! in_array($contractValue, $value);
            case '>':
                return $contractValue > $value;
            case '>=':
                return $contractValue >= $value;
            case '<':
                return $contractValue < $value;
            case '<=':
                return $contractValue <= $value;
            case 'exists':
                return ! empty($contractValue);
            case 'not_exists':
                return empty($contractValue);
            default:
                return true;
        }
    }

    /**
     * Generate action URL for the button
     */
    public function generateActionUrl($contract): ?string
    {
        if (! $this->action_config) {
            return null;
        }

        switch ($this->action_type) {
            case 'route':
                $routeName = $this->action_config['route'] ?? null;
                $parameters = $this->action_config['parameters'] ?? [];

                // Replace contract placeholders in parameters
                foreach ($parameters as $key => $param) {
                    if (is_string($param) && str_contains($param, '{contract.')) {
                        $field = str_replace(['{contract.', '}'], '', $param);
                        $parameters[$key] = data_get($contract, $field);
                    }
                }

                try {
                    return route($routeName, $parameters);
                } catch (\Exception $e) {
                    return null;
                }

            case 'ajax':
                return $this->action_config['url'] ?? null;

            case 'download':
                return $this->action_config['download_url'] ?? null;

            default:
                return null;
        }
    }

    /**
     * Get JavaScript action configuration
     */
    public function getJavaScriptConfig($contract): array
    {
        $config = [
            'type' => $this->action_type,
            'url' => $this->generateActionUrl($contract),
            'confirmation' => $this->confirmation_message,
        ];

        if ($this->action_type === 'modal') {
            $config['modal'] = $this->action_config['modal'] ?? [];
        }

        if ($this->action_type === 'ajax') {
            $config['method'] = $this->action_config['method'] ?? 'POST';
            $config['data'] = $this->action_config['data'] ?? [];
        }

        if ($this->action_type === 'status_change') {
            $config['new_status'] = $this->action_config['status'] ?? null;
        }

        return $config;
    }

    /**
     * Get default action buttons for a company
     */
    public static function getDefaultButtons(): array
    {
        return [
            [
                'label' => 'View Details',
                'slug' => 'view-details',
                'icon' => 'fas fa-eye',
                'button_class' => 'btn btn-outline-primary btn-sm',
                'action_type' => 'route',
                'action_config' => [
                    'route' => 'contracts.show',
                    'parameters' => ['{contract.id}'],
                ],
                'sort_order' => 10,
            ],
            [
                'label' => 'Edit Contract',
                'slug' => 'edit-contract',
                'icon' => 'fas fa-edit',
                'button_class' => 'btn btn-outline-secondary btn-sm',
                'action_type' => 'route',
                'action_config' => [
                    'route' => 'contracts.edit',
                    'parameters' => ['{contract.id}'],
                ],
                'visibility_conditions' => [
                    ['field' => 'status', 'operator' => 'in', 'value' => ['draft', 'pending_review']],
                ],
                'permissions' => ['update'],
                'sort_order' => 20,
            ],
            [
                'label' => 'Send for Signature',
                'slug' => 'send-signature',
                'icon' => 'fas fa-signature',
                'button_class' => 'btn btn-primary btn-sm',
                'action_type' => 'ajax',
                'action_config' => [
                    'url' => '/contracts/{contract.id}/send-signature',
                    'method' => 'POST',
                ],
                'visibility_conditions' => [
                    ['field' => 'status', 'operator' => '=', 'value' => 'pending_signature'],
                ],
                'confirmation_message' => 'Are you sure you want to send this contract for signature?',
                'permissions' => ['update'],
                'sort_order' => 30,
            ],
            [
                'label' => 'Activate Contract',
                'slug' => 'activate-contract',
                'icon' => 'fas fa-play',
                'button_class' => 'btn btn-success btn-sm',
                'action_type' => 'status_change',
                'action_config' => [
                    'status' => 'active',
                ],
                'visibility_conditions' => [
                    ['field' => 'status', 'operator' => '=', 'value' => 'signed'],
                ],
                'confirmation_message' => 'Are you sure you want to activate this contract?',
                'permissions' => ['update'],
                'sort_order' => 40,
            ],
            [
                'label' => 'Terminate Contract',
                'slug' => 'terminate-contract',
                'icon' => 'fas fa-stop',
                'button_class' => 'btn btn-danger btn-sm',
                'action_type' => 'modal',
                'action_config' => [
                    'modal' => [
                        'title' => 'Terminate Contract',
                        'size' => 'md',
                        'form_action' => '/contracts/{contract.id}/terminate',
                        'fields' => [
                            [
                                'name' => 'termination_reason',
                                'type' => 'textarea',
                                'label' => 'Termination Reason',
                                'required' => true,
                            ],
                            [
                                'name' => 'effective_date',
                                'type' => 'date',
                                'label' => 'Effective Date',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
                'visibility_conditions' => [
                    ['field' => 'status', 'operator' => '=', 'value' => 'active'],
                ],
                'permissions' => ['delete'],
                'sort_order' => 50,
            ],
            [
                'label' => 'Download PDF',
                'slug' => 'download-pdf',
                'icon' => 'fas fa-download',
                'button_class' => 'btn btn-outline-info btn-sm',
                'action_type' => 'download',
                'action_config' => [
                    'download_url' => '/contracts/{contract.id}/download',
                ],
                'sort_order' => 60,
            ],
        ];
    }
}
