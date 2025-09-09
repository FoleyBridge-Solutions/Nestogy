@props([
    'status',
    'size' => 'sm',
    'showIcon' => true
])

@php
$statusConfig = [
    'draft' => [
        'classes' => 'bg-gray-100 text-gray-800 border-gray-300',
        'icon' => 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z',
        'label' => 'Draft'
    ],
    'pending_review' => [
        'classes' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
        'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'label' => 'Pending Review'
    ],
    'under_negotiation' => [
        'classes' => 'bg-blue-100 text-blue-800 border-blue-300',
        'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
        'label' => 'Under Negotiation'
    ],
    'pending_signature' => [
        'classes' => 'bg-orange-100 text-orange-800 border-orange-300',
        'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
        'label' => 'Pending Signature'
    ],
    'signed' => [
        'classes' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
        'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'label' => 'Signed'
    ],
    'active' => [
        'classes' => 'bg-green-100 text-green-800 border-green-300',
        'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'label' => 'Active'
    ],
    'suspended' => [
        'classes' => 'bg-red-100 text-red-800 border-red-300',
        'icon' => 'M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'label' => 'Suspended'
    ],
    'terminated' => [
        'classes' => 'bg-gray-900 text-white border-gray-700',
        'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
        'label' => 'Terminated'
    ],
    'expired' => [
        'classes' => 'bg-red-100 text-red-800 border-red-300',
        'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'label' => 'Expired'
    ],
    'cancelled' => [
        'classes' => 'bg-gray-100 text-gray-800 border-gray-300',
        'icon' => 'M6 18L18 6M6 6l12 12',
        'label' => 'Cancelled'
    ]
];

$config = $statusConfig[$status] ?? $statusConfig['draft'];

$sizeClasses = [
    'xs' => 'px-2 py-1 text-xs',
    'sm' => 'px-2.5 py-1.5 text-xs',
    'md' => 'px-3 py-2 text-sm',
    'lg' => 'px-4 py-2 text-base'
];

$iconSizes = [
    'xs' => 'w-3 h-3',
    'sm' => 'w-3 h-3',
    'md' => 'w-4 h-4',
    'lg' => 'w-5 h-5'
];
@endphp

<span class="inline-flex items-center {{ $sizeClasses[$size] }} font-medium rounded-full border {{ $config['classes'] }} transition-colors">
    @if($showIcon)
        <svg class="{{ $iconSizes[$size] }} mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $config['icon'] }}"/>
        </svg>
    @endif
    {{ $config['label'] }}
</span>
