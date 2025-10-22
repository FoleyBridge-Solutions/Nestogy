@props(['lead'])

<div class="p-6">
    <flux:heading size="lg" class="mb-6">Lead Score Breakdown</flux:heading>

    {{-- Total Score Circle --}}
    <div class="flex justify-center mb-8">
        <div class="relative inline-flex items-center justify-center">
            <svg class="transform -rotate-90 w-32 h-32">
                <circle
                    cx="64"
                    cy="64"
                    r="56"
                    stroke="currentColor"
                    stroke-width="8"
                    fill="transparent"
                    class="text-gray-200 dark:text-gray-700"
                />
                <circle
                    cx="64"
                    cy="64"
                    r="56"
                    stroke="currentColor"
                    stroke-width="8"
                    fill="transparent"
                    class="@if($lead->total_score >= 80) text-green-500
                          @elseif($lead->total_score >= 60) text-blue-500
                          @elseif($lead->total_score >= 40) text-yellow-500
                          @else text-red-500
                          @endif"
                    stroke-dasharray="{{ 2 * pi() * 56 }}"
                    stroke-dashoffset="{{ 2 * pi() * 56 * (1 - $lead->total_score / 100) }}"
                    stroke-linecap="round"
                />
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ $lead->total_score }}</span>
                <span class="text-sm text-gray-500 dark:text-gray-400">out of 100</span>
            </div>
        </div>
    </div>

    {{-- Score Components --}}
    <div class="space-y-6 mb-6">
        {{-- Demographic Score --}}
        <div>
            <div class="flex justify-between items-center mb-2">
                <flux:text variant="strong">Demographic Score</flux:text>
                <flux:text>{{ $lead->demographic_score ?? 0 }}/50</flux:text>
            </div>
            <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                <div 
                    class="bg-blue-500 h-3 rounded-full transition-all"
                    style="width: {{ min(100, (($lead->demographic_score ?? 0) / 50) * 100) }}%"
                ></div>
            </div>
            <flux:text size="sm" class="text-gray-600 dark:text-gray-400 mt-1">
                Based on company size, industry, geography, and contact info completeness
            </flux:text>
        </div>

        {{-- Behavioral Score --}}
        <div>
            <div class="flex justify-between items-center mb-2">
                <flux:text variant="strong">Behavioral Score</flux:text>
                <flux:text>{{ $lead->behavioral_score ?? 0 }}/50</flux:text>
            </div>
            <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                <div 
                    class="bg-green-500 h-3 rounded-full transition-all"
                    style="width: {{ min(100, (($lead->behavioral_score ?? 0) / 50) * 100) }}%"
                ></div>
            </div>
            <flux:text size="sm" class="text-gray-600 dark:text-gray-400 mt-1">
                Based on email opens, website visits, calls, and meeting activity
            </flux:text>
        </div>

        {{-- Fit Score --}}
        <div>
            <div class="flex justify-between items-center mb-2">
                <flux:text variant="strong">Fit Score</flux:text>
                <flux:text>{{ $lead->fit_score ?? 0 }}/50</flux:text>
            </div>
            <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                <div 
                    class="bg-purple-500 h-3 rounded-full transition-all"
                    style="width: {{ min(100, (($lead->fit_score ?? 0) / 50) * 100) }}%"
                ></div>
            </div>
            <flux:text size="sm" class="text-gray-600 dark:text-gray-400 mt-1">
                Based on tech stack, pain points, budget, and decision-maker presence
            </flux:text>
        </div>

        {{-- Urgency Score --}}
        <div>
            <div class="flex justify-between items-center mb-2">
                <flux:text variant="strong">Urgency Score</flux:text>
                <flux:text>{{ $lead->urgency_score ?? 0 }}/50</flux:text>
            </div>
            <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                <div 
                    class="bg-orange-500 h-3 rounded-full transition-all"
                    style="width: {{ min(100, (($lead->urgency_score ?? 0) / 50) * 100) }}%"
                ></div>
            </div>
            <flux:text size="sm" class="text-gray-600 dark:text-gray-400 mt-1">
                Based on urgency keywords and timeline indicators
            </flux:text>
        </div>
    </div>

    {{-- Recommendations --}}
    <flux:card class="mb-6">
        <flux:heading size="sm" class="mb-3">Recommendations</flux:heading>
        @if($lead->total_score >= 80)
            <flux:callout icon="check-circle" variant="success">
                <strong>High Priority Lead!</strong> This lead shows strong potential. Consider immediate follow-up with a personalized outreach.
            </flux:callout>
        @elseif($lead->total_score >= 60)
            <flux:callout icon="information-circle" variant="info">
                <strong>Qualified Lead.</strong> This lead is worth pursuing. Schedule a discovery call to understand their needs better.
            </flux:callout>
        @elseif($lead->total_score >= 40)
            <flux:callout icon="exclamation-triangle" variant="warning">
                <strong>Moderate Interest.</strong> This lead needs nurturing. Add them to a drip campaign and monitor engagement.
            </flux:callout>
        @else
            <flux:callout icon="x-circle" variant="danger">
                <strong>Low Priority.</strong> This lead may not be a good fit right now. Consider deprioritizing or adding to a long-term nurture sequence.
            </flux:callout>
        @endif
    </flux:card>

    {{-- Actions --}}
    <div class="flex gap-3 justify-end">
        <flux:button variant="ghost" wire:click="closeCellModal">Close</flux:button>
        <flux:button 
            variant="primary" 
            wire:click="recalculateScore({{ $lead->id }})"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove wire:target="recalculateScore">Recalculate Score</span>
            <span wire:loading wire:target="recalculateScore">Recalculating...</span>
        </flux:button>
    </div>
</div>
