<?php

namespace App\Livewire\Marketing\Analytics;

use App\Livewire\BaseAnalyticsComponent;
use App\Domains\Marketing\Models\MarketingCampaign;
use App\Domains\Marketing\Models\EmailTracking;

class CampaignPerformance extends BaseAnalyticsComponent
{
    protected function getStats(): array
    {
        $campaignQuery = MarketingCampaign::where('company_id', $this->companyId);
        
        if (!empty($this->dateRange)) {
            $campaignQuery->whereBetween('created_at', $this->dateRange);
        }
        
        $totalCampaigns = $campaignQuery->clone()->count();
        $activeCampaigns = $campaignQuery->clone()->where('status', 'active')->count();
        
        $emailQuery = EmailTracking::where('company_id', $this->companyId)
            ->whereNotNull('campaign_id');
            
        if (!empty($this->dateRange)) {
            $emailQuery->whereBetween('sent_at', $this->dateRange);
        }
        
        $emailStats = $emailQuery->selectRaw('
                COUNT(*) as total_sent,
                SUM(CASE WHEN open_count > 0 THEN 1 ELSE 0 END) as total_opened,
                SUM(CASE WHEN click_count > 0 THEN 1 ELSE 0 END) as total_clicked
            ')
            ->first();

        $totalSent = $emailStats->total_sent ?? 0;
        $totalOpened = $emailStats->total_opened ?? 0;
        $totalClicked = $emailStats->total_clicked ?? 0;
        
        $avgOpenRate = $totalSent > 0 ? round(($totalOpened / $totalSent) * 100, 1) : 0;
        $avgClickRate = $totalSent > 0 ? round(($totalClicked / $totalSent) * 100, 1) : 0;

        return [
            [
                'label' => 'Total Campaigns',
                'value' => number_format($totalCampaigns),
                'icon' => 'megaphone',
                'color' => 'blue',
            ],
            [
                'label' => 'Active Campaigns',
                'value' => number_format($activeCampaigns),
                'icon' => 'play',
                'color' => 'green',
            ],
            [
                'label' => 'Total Emails Sent',
                'value' => number_format($totalSent),
                'icon' => 'paper-airplane',
                'color' => 'purple',
            ],
            [
                'label' => 'Avg Open Rate',
                'value' => $avgOpenRate . '%',
                'icon' => 'envelope-open',
                'color' => 'yellow',
            ],
            [
                'label' => 'Avg Click Rate',
                'value' => $avgClickRate . '%',
                'icon' => 'cursor-arrow-rays',
                'color' => 'emerald',
            ],
        ];
    }

    protected function getCharts(): array
    {
        return [
            'performance' => [
                'title' => 'Campaign Activity',
                'description' => 'Total campaigns and completion trends over time',
                'type' => 'area',
                'xAxis' => 'date',
                'fields' => [
                    [
                        'key' => 'campaigns',
                        'label' => 'Total Campaigns',
                        'areaClass' => 'text-blue-200/50 dark:text-blue-400/30',
                        'lineClass' => 'text-blue-500 dark:text-blue-400',
                        'indicatorClass' => 'bg-blue-400',
                    ],
                    [
                        'key' => 'completed',
                        'label' => 'Completed',
                        'lineClass' => 'text-green-500 dark:text-green-400',
                        'indicatorClass' => 'bg-green-400',
                    ],
                ],
                'data' => $this->getPerformanceChartData(),
                'emptyMessage' => 'No campaign data available yet',
            ],
            'engagement' => [
                'title' => 'Engagement Rates by Campaign',
                'description' => 'Open and click rates for recent campaigns',
                'type' => 'line',
                'xAxis' => 'campaign',
                'yFormat' => [
                    'style' => 'percent',
                    'minimumFractionDigits' => 0,
                    'maximumFractionDigits' => 1,
                ],
                'fields' => [
                    [
                        'key' => 'open_rate',
                        'label' => 'Open Rate',
                        'class' => 'text-purple-500 dark:text-purple-400',
                        'indicatorClass' => 'bg-purple-400',
                        'format' => ['style' => 'percent', 'maximumFractionDigits' => 1],
                    ],
                    [
                        'key' => 'click_rate',
                        'label' => 'Click Rate',
                        'class' => 'text-orange-500 dark:text-orange-400',
                        'indicatorClass' => 'bg-orange-400',
                        'format' => ['style' => 'percent', 'maximumFractionDigits' => 1],
                    ],
                ],
                'data' => $this->getEngagementChartData(),
                'emptyMessage' => 'No engagement data available yet',
            ],
        ];
    }

    protected function getPerformanceChartData(): array
    {
        $query = MarketingCampaign::where('company_id', $this->companyId)
            ->whereNotNull('created_at');
            
        if (!empty($this->dateRange)) {
            $query->whereBetween('created_at', $this->dateRange);
        }
        
        $data = $query->selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as campaigns')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        if ($data->isEmpty()) {
            return [];
        }

        return $data->map(function ($item) {
            return [
                'date' => $item->date,
                'campaigns' => (int)$item->campaigns,
                'completed' => (int)$item->completed,
            ];
        })->toArray();
    }

    protected function getEngagementChartData(): array
    {
        $query = MarketingCampaign::where('company_id', $this->companyId);
        
        if (!empty($this->dateRange)) {
            $query->whereBetween('created_at', $this->dateRange);
        }
        
        $campaigns = $query->select('marketing_campaigns.*')
            ->selectSub(
                'SELECT COALESCE(SUM(emails_sent), 0) FROM campaign_enrollments WHERE campaign_enrollments.campaign_id = marketing_campaigns.id',
                'total_emails_sent'
            )
            ->selectSub(
                'SELECT COALESCE(SUM(emails_opened), 0) FROM campaign_enrollments WHERE campaign_enrollments.campaign_id = marketing_campaigns.id',
                'total_emails_opened'
            )
            ->selectSub(
                'SELECT COALESCE(SUM(emails_clicked), 0) FROM campaign_enrollments WHERE campaign_enrollments.campaign_id = marketing_campaigns.id',
                'total_emails_clicked'
            )
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($campaigns->isEmpty()) {
            return [];
        }

        return $campaigns->reverse()->map(function ($campaign) {
            $sent = $campaign->total_emails_sent ?? 0;
            $opened = $campaign->total_emails_opened ?? 0;
            $clicked = $campaign->total_emails_clicked ?? 0;
            
            $openRate = $sent > 0 ? ($opened / $sent) : 0;
            $clickRate = $sent > 0 ? ($clicked / $sent) : 0;

            return [
                'campaign' => substr($campaign->name ?? 'Untitled', 0, 20),
                'open_rate' => $openRate,
                'click_rate' => $clickRate,
            ];
        })->values()->toArray();
    }

    protected function getEmptyState(): array
    {
        return [
            'icon' => 'chart-bar',
            'title' => 'No campaign data',
            'message' => 'Campaign performance metrics will appear here once campaigns are active.',
            'action' => route('marketing.campaigns.index'),
            'actionLabel' => 'View Campaigns',
        ];
    }
}
