<?php

namespace App\Livewire\Project\Concerns;

use App\Domains\Project\Services\TimelineService;

trait CalculatesTimelinePositions
{
    public function calculateDatePosition(string $date, array $bounds, array $timeAxis, string $zoomLevel): float
    {
        $targetDate = \Carbon\Carbon::parse($date);
        $startDate = $bounds['start'];
        $endDate = $bounds['end'];

        $totalDays = $startDate->diffInDays($endDate);
        $daysSinceStart = $startDate->diffInDays($targetDate);

        $slotWidth = match ($zoomLevel) {
            TimelineService::ZOOM_DAY => 60,
            TimelineService::ZOOM_WEEK => 100,
            TimelineService::ZOOM_MONTH => 120,
            TimelineService::ZOOM_QUARTER => 120,
            default => 100,
        };

        return ($daysSinceStart / $totalDays) * (count($timeAxis) * $slotWidth);
    }

    public function calculateBarPosition(string $startDate, string $endDate, array $bounds, array $timeAxis, string $zoomLevel): array
    {
        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);
        $boundsStart = $bounds['start'];
        $boundsEnd = $bounds['end'];

        $totalDays = $boundsStart->diffInDays($boundsEnd);
        $daysSinceStart = $boundsStart->diffInDays($start);
        $duration = $start->diffInDays($end);

        $slotWidth = match ($zoomLevel) {
            TimelineService::ZOOM_DAY => 60,
            TimelineService::ZOOM_WEEK => 100,
            TimelineService::ZOOM_MONTH => 120,
            TimelineService::ZOOM_QUARTER => 120,
            default => 100,
        };

        $totalWidth = count($timeAxis) * $slotWidth;

        return [
            'left' => ($daysSinceStart / $totalDays) * $totalWidth,
            'width' => max(($duration / $totalDays) * $totalWidth, 20),
        ];
    }
}
