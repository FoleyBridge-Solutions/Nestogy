<?php

namespace App\Livewire\Marketing\Analytics;

use App\Livewire\BaseIndexComponent;
use App\Domains\Marketing\Models\EmailTracking;
use Illuminate\Database\Eloquent\Builder;

class EmailPerformance extends BaseIndexComponent
{
    protected function getDefaultSort(): array
    {
        return ['field' => 'sent_at', 'direction' => 'desc'];
    }

    protected function getSearchFields(): array
    {
        return ['recipient_email', 'subject_line'];
    }

    protected function getColumns(): array
    {
        return [
            'recipient_email' => [
                'label' => 'Recipient',
                'sortable' => true,
                'filterable' => false,
            ],
            'subject_line' => [
                'label' => 'Subject',
                'sortable' => false,
                'filterable' => false,
            ],
            'campaign.name' => [
                'label' => 'Campaign',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'dynamic_options' => true,
            ],
            'status' => [
                'label' => 'Status',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => [
                    EmailTracking::STATUS_SENT => 'Sent',
                    EmailTracking::STATUS_DELIVERED => 'Delivered',
                    EmailTracking::STATUS_BOUNCED => 'Bounced',
                    EmailTracking::STATUS_FAILED => 'Failed',
                ],
                'type' => 'badge',
                'badgeColor' => function ($item) {
                    return match ($item->status) {
                        EmailTracking::STATUS_DELIVERED => 'green',
                        EmailTracking::STATUS_SENT => 'blue',
                        EmailTracking::STATUS_BOUNCED, EmailTracking::STATUS_FAILED => 'red',
                        default => 'zinc',
                    };
                },
            ],
            'sent_at' => [
                'label' => 'Sent At',
                'sortable' => true,
                'filterable' => true,
                'type' => 'date',
            ],
            'first_opened_at' => [
                'label' => 'First Opened',
                'sortable' => true,
                'type' => 'date',
            ],
            'open_count' => [
                'label' => 'Opens',
                'sortable' => true,
            ],
            'click_count' => [
                'label' => 'Clicks',
                'sortable' => true,
            ],
        ];
    }

    protected function getStats(): array
    {
        $baseQuery = EmailTracking::where('company_id', $this->companyId);

        $totalSent = $baseQuery->clone()->count();
        $delivered = $baseQuery->clone()->where('status', EmailTracking::STATUS_DELIVERED)->count();
        $bounced = $baseQuery->clone()->where('status', EmailTracking::STATUS_BOUNCED)->count();
        $opened = $baseQuery->clone()->where('open_count', '>', 0)->count();
        
        $deliveryRate = $totalSent > 0 ? round(($delivered / $totalSent) * 100, 1) : 0;
        $bounceRate = $totalSent > 0 ? round(($bounced / $totalSent) * 100, 1) : 0;
        $openRate = $totalSent > 0 ? round(($opened / $totalSent) * 100, 1) : 0;

        return [
            [
                'label' => 'Total Sent',
                'value' => number_format($totalSent),
                'icon' => 'paper-airplane',
                'color' => 'blue',
            ],
            [
                'label' => 'Delivery Rate',
                'value' => $deliveryRate . '%',
                'icon' => 'check-circle',
                'color' => 'green',
            ],
            [
                'label' => 'Bounce Rate',
                'value' => $bounceRate . '%',
                'icon' => 'x-circle',
                'color' => 'red',
            ],
            [
                'label' => 'Open Rate',
                'value' => $openRate . '%',
                'icon' => 'envelope-open',
                'color' => 'yellow',
            ],
        ];
    }

    protected function getEmptyState(): array
    {
        return [
            'icon' => 'envelope',
            'title' => 'No email tracking data',
            'message' => 'Email performance metrics will appear here once emails are sent.',
            'action' => route('marketing.campaigns.index'),
            'actionLabel' => 'View Campaigns',
        ];
    }

    protected function getBaseQuery(): Builder
    {
        return EmailTracking::query()
            ->with(['campaign', 'lead', 'contact']);
    }
}
