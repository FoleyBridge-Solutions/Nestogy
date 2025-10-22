<?php

namespace App\Livewire\Marketing;

use App\Livewire\BaseIndexComponent;
use App\Domains\Marketing\Models\CampaignEnrollment;
use Illuminate\Database\Eloquent\Builder;

class EnrollmentIndex extends BaseIndexComponent
{
    protected function getDefaultSort(): array
    {
        return ['field' => 'enrolled_at', 'direction' => 'desc'];
    }

    protected function getSearchFields(): array
    {
        return ['lead.first_name', 'lead.last_name', 'lead.email', 'contact.name', 'contact.email'];
    }

    protected function getColumns(): array
    {
        return [
            'recipient' => [
                'label' => 'Recipient',
                'sortable' => false,
                'filterable' => false,
                'component' => 'marketing.enrollments.cells.recipient',
            ],
            'campaign.name' => [
                'label' => 'Campaign',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'dynamic_options' => true,
                'component' => 'marketing.enrollments.cells.campaign',
            ],
            'status' => [
                'label' => 'Status',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => [
                    CampaignEnrollment::STATUS_ENROLLED => 'Enrolled',
                    CampaignEnrollment::STATUS_ACTIVE => 'Active',
                    CampaignEnrollment::STATUS_COMPLETED => 'Completed',
                    CampaignEnrollment::STATUS_PAUSED => 'Paused',
                    CampaignEnrollment::STATUS_UNSUBSCRIBED => 'Unsubscribed',
                    CampaignEnrollment::STATUS_BOUNCED => 'Bounced',
                ],
                'component' => 'marketing.enrollments.cells.status',
            ],
            'current_step' => [
                'label' => 'Progress',
                'sortable' => true,
                'filterable' => false,
                'component' => 'marketing.enrollments.cells.progress',
            ],
            'engagement' => [
                'label' => 'Engagement',
                'sortable' => false,
                'filterable' => false,
                'component' => 'marketing.enrollments.cells.engagement',
            ],
            'enrolled_at' => [
                'label' => 'Enrolled',
                'sortable' => true,
                'filterable' => true,
                'type' => 'date',
            ],
            'next_send_at' => [
                'label' => 'Next Send',
                'sortable' => true,
                'type' => 'date',
            ],
        ];
    }

    protected function getStats(): array
    {
        $query = CampaignEnrollment::query()
            ->whereHas('campaign', function ($q) {
                $q->where('company_id', $this->companyId);
            });

        $total = $query->clone()->count();
        $active = $query->clone()->where('status', CampaignEnrollment::STATUS_ACTIVE)->count();
        $completed = $query->clone()->where('status', CampaignEnrollment::STATUS_COMPLETED)->count();
        $converted = $query->clone()->where('converted', true)->count();
        
        $avgOpenRate = $query->clone()
            ->where('emails_sent', '>', 0)
            ->selectRaw('AVG(emails_opened / emails_sent * 100) as avg_rate')
            ->value('avg_rate') ?? 0;

        return [
            [
                'label' => 'Total Enrollments',
                'value' => number_format($total),
                'icon' => 'users',
                'color' => 'blue',
            ],
            [
                'label' => 'Active',
                'value' => number_format($active),
                'icon' => 'play',
                'color' => 'green',
            ],
            [
                'label' => 'Completion Rate',
                'value' => $total > 0 ? round(($completed / $total) * 100, 1) . '%' : '0%',
                'icon' => 'check-circle',
                'color' => 'purple',
            ],
            [
                'label' => 'Avg Open Rate',
                'value' => round($avgOpenRate, 1) . '%',
                'icon' => 'envelope-open',
                'color' => 'yellow',
            ],
            [
                'label' => 'Conversions',
                'value' => number_format($converted),
                'icon' => 'sparkles',
                'color' => 'emerald',
            ],
        ];
    }

    protected function getEmptyState(): array
    {
        return [
            'icon' => 'bolt',
            'title' => 'No enrollments found',
            'message' => 'Campaign enrollments will appear here once contacts or leads are added to campaigns.',
            'action' => route('marketing.campaigns.index'),
            'actionLabel' => 'View Campaigns',
        ];
    }

    protected function getBaseQuery(): Builder
    {
        return CampaignEnrollment::query()
            ->with(['campaign', 'lead', 'contact'])
            ->whereHas('campaign', function ($q) {
                $q->where('marketing_campaigns.company_id', $this->companyId);
            });
    }

    protected function buildQuery(): Builder
    {
        $query = $this->getBaseQuery();

        $query = $this->applyArchiveFilter($query);
        $query = $this->applySearch($query);
        $query = $this->applyColumnFilters($query);
        $query = $this->applyCustomFilters($query);
        $query = $this->applySorting($query);

        return $query;
    }

    protected function getRowActions($item): array
    {
        $actions = [];

        if ($item->status === CampaignEnrollment::STATUS_ACTIVE) {
            $actions[] = [
                'label' => 'Pause',
                'wire:click' => 'pauseEnrollment(' . $item->id . ')',
                'icon' => 'pause',
            ];
        }

        if ($item->status === CampaignEnrollment::STATUS_PAUSED) {
            $actions[] = [
                'label' => 'Resume',
                'wire:click' => 'resumeEnrollment(' . $item->id . ')',
                'icon' => 'play',
            ];
        }

        $actions[] = [
            'label' => 'View Details',
            'href' => route('marketing.campaigns.show', $item->campaign_id) . '#enrollment-' . $item->id,
            'icon' => 'eye',
        ];

        if (!in_array($item->status, [CampaignEnrollment::STATUS_UNSUBSCRIBED, CampaignEnrollment::STATUS_BOUNCED])) {
            $actions[] = [
                'label' => 'Unsubscribe',
                'wire:click' => 'unsubscribeEnrollment(' . $item->id . ')',
                'wire:confirm' => 'Are you sure you want to unsubscribe this recipient?',
                'icon' => 'x-circle',
                'variant' => 'danger',
            ];
        }

        return $actions;
    }

    public function getBulkActions(): array
    {
        return [
            [
                'label' => 'Pause Selected',
                'method' => 'bulkPause',
                'variant' => 'ghost',
            ],
            [
                'label' => 'Resume Selected',
                'method' => 'bulkResume',
                'variant' => 'ghost',
            ],
            [
                'label' => 'Unsubscribe Selected',
                'method' => 'bulkUnsubscribe',
                'variant' => 'danger',
                'confirm' => 'Are you sure you want to unsubscribe the selected enrollments?',
            ],
        ];
    }

    public function pauseEnrollment($enrollmentId)
    {
        $enrollment = CampaignEnrollment::whereHas('campaign', function ($q) {
            $q->where('marketing_campaigns.company_id', $this->companyId);
        })->findOrFail($enrollmentId);

        $enrollment->update(['status' => CampaignEnrollment::STATUS_PAUSED]);

        $this->dispatch('alert', message: 'Enrollment paused successfully', type: 'success');
    }

    public function resumeEnrollment($enrollmentId)
    {
        $enrollment = CampaignEnrollment::whereHas('campaign', function ($q) {
            $q->where('marketing_campaigns.company_id', $this->companyId);
        })->findOrFail($enrollmentId);

        $enrollment->update(['status' => CampaignEnrollment::STATUS_ACTIVE]);

        $this->dispatch('alert', message: 'Enrollment resumed successfully', type: 'success');
    }

    public function unsubscribeEnrollment($enrollmentId)
    {
        $enrollment = CampaignEnrollment::whereHas('campaign', function ($q) {
            $q->where('marketing_campaigns.company_id', $this->companyId);
        })->findOrFail($enrollmentId);

        $enrollment->update(['status' => CampaignEnrollment::STATUS_UNSUBSCRIBED]);

        $this->dispatch('alert', message: 'Recipient unsubscribed successfully', type: 'success');
    }

    public function bulkPause()
    {
        if (empty($this->selected)) {
            return;
        }

        CampaignEnrollment::whereIn('id', $this->selected)
            ->whereHas('campaign', function ($q) {
                $q->where('marketing_campaigns.company_id', $this->companyId);
            })
            ->update(['status' => CampaignEnrollment::STATUS_PAUSED]);

        $count = count($this->selected);
        $this->selected = [];
        $this->dispatch('alert', message: $count . ' enrollments paused', type: 'success');
    }

    public function bulkResume()
    {
        if (empty($this->selected)) {
            return;
        }

        CampaignEnrollment::whereIn('id', $this->selected)
            ->whereHas('campaign', function ($q) {
                $q->where('marketing_campaigns.company_id', $this->companyId);
            })
            ->update(['status' => CampaignEnrollment::STATUS_ACTIVE]);

        $count = count($this->selected);
        $this->selected = [];
        $this->dispatch('alert', message: $count . ' enrollments resumed', type: 'success');
    }

    public function bulkUnsubscribe()
    {
        if (empty($this->selected)) {
            return;
        }

        CampaignEnrollment::whereIn('id', $this->selected)
            ->whereHas('campaign', function ($q) {
                $q->where('marketing_campaigns.company_id', $this->companyId);
            })
            ->update(['status' => CampaignEnrollment::STATUS_UNSUBSCRIBED]);

        $count = count($this->selected);
        $this->selected = [];
        $this->dispatch('alert', message: $count . ' recipients unsubscribed', type: 'success');
    }

    public function getFilterOptions(string $columnKey): array
    {
        $columns = $this->getColumns();
        $columnConfig = $columns[$columnKey] ?? null;

        if (!$columnConfig || !($columnConfig['filterable'] ?? false)) {
            return [];
        }

        if (isset($columnConfig['options']) && !($columnConfig['dynamic_options'] ?? false)) {
            return $columnConfig['options'];
        }

        $query = $this->getBaseQuery();
        $query = $this->applyArchiveFilter($query);

        if (str_contains($columnKey, '.')) {
            [$relation, $field] = explode('.', $columnKey);
            $values = $query->with($relation)
                ->get()
                ->pluck("{$relation}.{$field}")
                ->filter()
                ->unique()
                ->sort()
                ->values();
        } else {
            $values = $query->distinct()
                ->pluck($columnKey)
                ->filter()
                ->unique()
                ->sort()
                ->values();
        }

        $options = [];
        foreach ($values as $value) {
            $label = $columnConfig['option_label_callback'] ?? null;
            $options[$value] = $label ? $label($value) : $this->formatOptionLabel($value);
        }

        return $options;
    }

    protected function formatOptionLabel(string $value): string
    {
        return str($value)->replace('_', ' ')->title()->toString();
    }
}
