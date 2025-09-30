<?php

namespace App\Domains\Contract\Services;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractActionButton;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DynamicActionButtonService
{
    protected int $companyId;

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
    }

    /**
     * Get all action buttons for a contract
     */
    public function getActionButtonsForContract(Contract $contract): Collection
    {
        return ContractActionButton::where('company_id', $this->companyId)
            ->active()
            ->ordered()
            ->get()
            ->filter(function ($button) use ($contract) {
                return $button->isVisibleForContract($contract);
            });
    }

    /**
     * Get action buttons grouped by context
     */
    public function getGroupedActionButtons(Contract $contract): array
    {
        $buttons = $this->getActionButtonsForContract($contract);

        $groups = [
            'primary' => collect(),
            'secondary' => collect(),
            'danger' => collect(),
        ];

        foreach ($buttons as $button) {
            $group = $this->determineButtonGroup($button);
            $groups[$group]->push($button);
        }

        return $groups;
    }

    /**
     * Determine which group a button belongs to
     */
    protected function determineButtonGroup(ContractActionButton $button): string
    {
        if (str_contains($button->button_class, 'btn-danger')) {
            return 'danger';
        }

        if (str_contains($button->button_class, 'btn-primary') ||
            str_contains($button->button_class, 'btn-success')) {
            return 'primary';
        }

        return 'secondary';
    }

    /**
     * Render action buttons as HTML
     */
    public function renderActionButtons(Contract $contract, string $layout = 'horizontal'): string
    {
        $buttons = $this->getActionButtonsForContract($contract);

        if ($buttons->isEmpty()) {
            return '';
        }

        $html = '';
        $containerClass = $layout === 'vertical' ? 'btn-group-vertical' : 'btn-group';

        $html .= "<div class=\"{$containerClass}\" role=\"group\">";

        foreach ($buttons as $button) {
            $html .= $this->renderSingleButton($button, $contract);
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render a single action button
     */
    public function renderSingleButton(ContractActionButton $button, Contract $contract): string
    {
        $url = $button->generateActionUrl($contract);
        $jsConfig = json_encode($button->getJavaScriptConfig($contract));

        $icon = $button->icon ? "<i class=\"{$button->icon}\"></i> " : '';

        $attributes = [
            'class' => $button->button_class,
            'data-action-config' => htmlspecialchars($jsConfig),
            'data-button-id' => $button->id,
        ];

        if ($button->action_type === 'route' && $url) {
            $attributes['href'] = $url;
            $tag = 'a';
        } else {
            $attributes['type'] = 'button';
            $tag = 'button';
        }

        $attributeString = collect($attributes)
            ->map(fn ($value, $key) => "{$key}=\"{$value}\"")
            ->implode(' ');

        return "<{$tag} {$attributeString}>{$icon}{$button->label}</{$tag}>";
    }

    /**
     * Generate JavaScript for dynamic action handling
     */
    public function generateActionHandlerScript(): string
    {
        return <<<'JS'
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle dynamic action buttons
    document.addEventListener('click', function(e) {
        const button = e.target.closest('[data-action-config]');
        if (!button) return;

        e.preventDefault();
        
        const config = JSON.parse(button.dataset.actionConfig);
        handleActionClick(config, button);
    });

    function handleActionClick(config, button) {
        // Show confirmation if required
        if (config.confirmation) {
            if (!confirm(config.confirmation)) {
                return;
            }
        }

        switch (config.type) {
            case 'ajax':
                handleAjaxAction(config, button);
                break;
            case 'modal':
                handleModalAction(config, button);
                break;
            case 'status_change':
                handleStatusChangeAction(config, button);
                break;
            case 'download':
                handleDownloadAction(config, button);
                break;
            case 'route':
                if (config.url) {
                    window.location.href = config.url;
                }
                break;
        }
    }

    function handleAjaxAction(config, button) {
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

        fetch(config.url, {
            method: config.method || 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: config.data ? JSON.stringify(config.data) : null
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message || 'Action completed successfully', 'success');
                if (data.reload) {
                    setTimeout(() => location.reload(), 1000);
                }
            } else {
                showAlert(data.message || 'Action failed', 'error');
            }
        })
        .catch(error => {
            console.error('Ajax action error:', error);
            showAlert('An error occurred while processing the action', 'error');
        })
        .finally(() => {
            button.disabled = false;
            button.innerHTML = originalText;
        });
    }

    function handleModalAction(config, button) {
        if (!config.modal) return;

        const modalId = 'dynamic-action-modal-' + Date.now();
        const modal = createActionModal(modalId, config.modal);
        document.body.appendChild(modal);

        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();

        modal.addEventListener('hidden.bs.modal', function() {
            modal.remove();
        });
    }

    function handleStatusChangeAction(config, button) {
        if (!config.new_status) return;

        handleAjaxAction({
            url: button.closest('[data-contract-id]')?.dataset.statusChangeUrl || '/contracts/update-status',
            method: 'POST',
            data: { status: config.new_status }
        }, button);
    }

    function handleDownloadAction(config, button) {
        if (config.url) {
            const link = document.createElement('a');
            link.href = config.url;
            link.download = '';
            link.click();
        }
    }

    function createActionModal(modalId, modalConfig) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = modalId;
        modal.innerHTML = `
            <div class="modal-dialog modal-${modalConfig.size || 'md'}">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${modalConfig.title || 'Action'}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="${modalConfig.form_action || '#'}">
                        <div class="modal-body">
                            ${generateModalFields(modalConfig.fields || [])}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Confirm</button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        return modal;
    }

    function generateModalFields(fields) {
        return fields.map(field => {
            const required = field.required ? 'required' : '';
            const fieldId = 'field-' + field.name;
            
            let input = '';
            switch (field.type) {
                case 'textarea':
                    input = `<textarea class="form-control" id="${fieldId}" name="${field.name}" ${required}></textarea>`;
                    break;
                case 'date':
                    input = `<input type="date" class="form-control" id="${fieldId}" name="${field.name}" ${required}>`;
                    break;
                case 'select':
                    const options = (field.options || []).map(opt => 
                        `<option value="${opt.value}">${opt.label}</option>`
                    ).join('');
                    input = `<select class="form-control" id="${fieldId}" name="${field.name}" ${required}>${options}</select>`;
                    break;
                default:
                    input = `<input type="${field.type || 'text'}" class="form-control" id="${fieldId}" name="${field.name}" ${required}>`;
            }

            return `
                <div class="mb-3">
                    <label for="${fieldId}" class="form-label">${field.label}</label>
                    ${input}
                </div>
            `;
        }).join('');
    }

    function showAlert(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alert = document.createElement('div');
        alert.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
        alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(alert);
        
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }
});
</script>
JS;
    }

    /**
     * Create default action buttons for a company
     */
    public function createDefaultButtons(): int
    {
        $defaultButtons = ContractActionButton::getDefaultButtons();
        $created = 0;

        foreach ($defaultButtons as $buttonData) {
            $buttonData['company_id'] = $this->companyId;

            // Check if button already exists
            $exists = ContractActionButton::where('company_id', $this->companyId)
                ->where('slug', $buttonData['slug'])
                ->exists();

            if (! $exists) {
                ContractActionButton::create($buttonData);
                $created++;
            }
        }

        return $created;
    }

    /**
     * Update action button configuration
     */
    public function updateButton(int $buttonId, array $data): bool
    {
        try {
            $button = ContractActionButton::where('company_id', $this->companyId)
                ->findOrFail($buttonId);

            $button->update($data);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update action button', [
                'button_id' => $buttonId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete action button
     */
    public function deleteButton(int $buttonId): bool
    {
        try {
            ContractActionButton::where('company_id', $this->companyId)
                ->where('id', $buttonId)
                ->delete();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete action button', [
                'button_id' => $buttonId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Reorder action buttons
     */
    public function reorderButtons(array $buttonOrder): bool
    {
        try {
            foreach ($buttonOrder as $index => $buttonId) {
                ContractActionButton::where('company_id', $this->companyId)
                    ->where('id', $buttonId)
                    ->update(['sort_order' => ($index + 1) * 10]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to reorder action buttons', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get available action types
     */
    public function getAvailableActionTypes(): array
    {
        return [
            'route' => [
                'label' => 'Navigate to Route',
                'description' => 'Navigate to another page',
                'config_fields' => ['route', 'parameters'],
            ],
            'ajax' => [
                'label' => 'AJAX Request',
                'description' => 'Perform an AJAX request',
                'config_fields' => ['url', 'method', 'data'],
            ],
            'modal' => [
                'label' => 'Show Modal',
                'description' => 'Display a modal dialog',
                'config_fields' => ['modal'],
            ],
            'status_change' => [
                'label' => 'Change Status',
                'description' => 'Change contract status',
                'config_fields' => ['status'],
            ],
            'download' => [
                'label' => 'Download File',
                'description' => 'Download a file',
                'config_fields' => ['download_url'],
            ],
        ];
    }
}
