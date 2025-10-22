@props(['item'])

@php
    $totalSteps = $item->campaign->sequences_count ?? 0;
    $currentStep = $item->current_step ?? 0;
    $progress = $totalSteps > 0 ? ($currentStep / $totalSteps) * 100 : 0;
@endphp

<div>
    <div class="text-sm text-gray-700 dark:text-gray-300 mb-1">
        Step {{ $currentStep }} of {{ $totalSteps }}
    </div>
    <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-2 w-32">
        <div 
            class="bg-blue-500 h-2 rounded-full transition-all"
            style="width: {{ $progress }}%"
        ></div>
    </div>
</div>
