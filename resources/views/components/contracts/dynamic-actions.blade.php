@props([
    'contract',
    'layout' => 'horizontal', // horizontal, vertical, dropdown
    'size' => 'sm', // sm, md, lg
    'showLabels' => true,
    'maxVisible' => 3 // For dropdown layout
])

@php
    $actionService = new \App\Domains\Contract\Services\DynamicActionButtonService(auth()->user()->company_id);
    $buttons = $actionService->getActionButtonsForContract($contract);
    
    if ($buttons->isEmpty()) {
        return;
    }
    
    $groupedButtons = $actionService->getGroupedActionButtons($contract);
@endphp

<div class="contract-actions" data-contract-id="{{ $contract->id }}" 
     data-status-change-url="{{ route('contracts.update-status', $contract) }}">
     
    @if($layout === 'dropdown')
        <div class="px-6 py-2 font-medium rounded-md transition-colors-group">
            {{-- Show primary actions directly --}}
            @foreach($groupedButtons['primary']->take($maxVisible) as $button)
                {!! $actionService->renderSingleButton($button, $contract) !!}
            @endforeach
            
            @if($buttons->count() > $maxVisible)
                <button class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-secondary dropdown-toggle" type="button" 
                         aria-expanded="false">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                    @foreach($buttons->skip($maxVisible) as $button)
                        <li>
                            {!! str_replace('btn ', 'dropdown-item ', $actionService->renderSingleButton($button, $contract)) !!}
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    
    @elseif($layout === 'vertical')
        <div class="px-6 py-2 font-medium rounded-md transition-colors-group-vertical grid gap-1" role="group">
            @foreach($buttons as $button)
                {!! $actionService->renderSingleButton($button, $contract) !!}
            @endforeach
        </div>
    
    @else
        {{-- Horizontal layout --}}
        <div class="px-6 py-2 font-medium rounded-md transition-colors-toolbar gap-2" role="toolbar">
            @if($groupedButtons['primary']->isNotEmpty())
                <div class="px-6 py-2 font-medium rounded-md transition-colors-group" role="group">
                    @foreach($groupedButtons['primary'] as $button)
                        {!! $actionService->renderSingleButton($button, $contract) !!}
                    @endforeach
                </div>
            @endif
            
            @if($groupedButtons['secondary']->isNotEmpty())
                <div class="px-6 py-2 font-medium rounded-md transition-colors-group" role="group">
                    @foreach($groupedButtons['secondary'] as $button)
                        {!! $actionService->renderSingleButton($button, $contract) !!}
                    @endforeach
                </div>
            @endif
            
            @if($groupedButtons['danger']->isNotEmpty())
                <div class="px-6 py-2 font-medium rounded-md transition-colors-group" role="group">
                    @foreach($groupedButtons['danger'] as $button)
                        {!! $actionService->renderSingleButton($button, $contract) !!}
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>

{{-- Include the JavaScript handler once per page --}}
@once
    {!! $actionService->generateActionHandlerScript() !!}
@endonce

{{-- Add CSS for better button styling --}}
@push('styles')
<style>
.contract-actions .btn {
    white-space: nowrap;
}

.contract-actions . .btn:not(:last-child) {
    border-right: 1px solid rgba(255,255,255,0.2);
}

.contract-actions . . {
    margin-right: 0.5rem;
}

.contract-actions .dropdown-menu {
    min-width: 160px;
}

.contract-actions .dropdown-item {
    display: flex;
    align-items: center;
    padding: 0.375rem 1rem;
}

.contract-actions .dropdown-item i {
    margin-right: 0.5rem;
    width: 1rem;
    text-align: center;
}

@media (max-width: 768px) {
    .contract-actions . {
        flex-direction: column;
        align-items: stretch;
    }
    
    .contract-actions . . {
        margin-bottom: 0.5rem;
    }
    
    .contract-actions . . {
        margin-bottom: 0;
    }
}
</style>
@endpush
