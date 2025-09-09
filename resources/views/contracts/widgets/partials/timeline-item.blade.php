@php
    $eventType = $event['type'] ?? 'general';
    $user = $event['user'] ?? null;
    $createdAt = \Carbon\Carbon::parse($event['created_at']);
@endphp

<div class="timeline-item" data-event-id="{{ $event['id'] ?? '' }}" data-event-type="{{ $eventType }}">
    <div class="timeline-content">
        <div class="timeline-header">
            <div class="timeline-user">
                @if($showAvatars && $user)
                    <img src="{{ $user['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&size=24' }}" 
                         class="timeline-avatar" alt="{{ $user['name'] }}">
                @endif
                @if($showIcons)
                    <i class="{{ $this->getEventIcon($eventType) }} mr-1"></i>
                @endif
                <span>{{ $user['name'] ?? 'System' }}</span>
            </div>
            <div class="timeline-time" title="{{ $createdAt->format('M j, Y \a\t g:i A') }}">
                {{ $createdAt->diffForHumans() }}
            </div>
        </div>
        
        <div class="timeline-body">
            {{ $event['message'] ?? $this->getEventMessage($event) }}
        </div>
        
        @if(isset($event['metadata']) && !empty($event['metadata']))
            <div class="timeline-meta">
                @foreach($event['metadata'] as $key => $value)
                    @if($key === 'changes' && is_array($value))
                        <div class="changes-summary">
                            <strong>Changes:</strong>
                            @foreach($value as $field => $change)
                                <span class="change-item">
                                    {{ ucfirst(str_replace('_', ' ', $field)) }}: 
                                    <span class="text-red-600 dark:text-red-400">{{ $change['from'] ?? 'empty' }}</span> 
                                    → 
                                    <span class="text-green-600 dark:text-green-400">{{ $change['to'] ?? 'empty' }}</span>
                                </span>
                                @if(!$loop->last), @endif
                            @endforeach
                        </div>
                    @elseif($key === 'attachments' && is_array($value))
                        <div class="attachments">
                            <strong>Attachments:</strong>
                            @foreach($value as $attachment)
                                <a href="{{ $attachment['url'] }}" class="mr-2">
                                    <i class="fas fa-paperclip"></i> {{ $attachment['name'] }}
                                </a>
                            @endforeach
                        </div>
                    @elseif($key === 'related_contracts' && is_array($value))
                        <div class="related-contracts">
                            <strong>Related:</strong>
                            @foreach($value as $contract)
                                <a href="{{ route('contracts.show', $contract['id']) }}" class="mr-2">
                                    {{ $contract['title'] }}
                                </a>
                            @endforeach
                        </div>
                    @else
                        <span class="meta-item">
                            <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $value }}
                        </span>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>

@php
    function getEventIcon($type) {
        $icons = [
            'created' => 'fas fa-plus-circle text-green-600 dark:text-green-400',
            'updated' => 'fas fa-edit text-cyan-600 dark:text-cyan-400',
            'status_change' => 'fas fa-exchange-alt text-yellow-600 dark:text-yellow-400',
            'comment' => 'fas fa-comment text-purple',
            'milestone' => 'fas fa-flag text-orange',
            'signature' => 'fas fa-signature text-blue-600 dark:text-blue-400',
            'approval' => 'fas fa-check-circle text-green-600 dark:text-green-400',
            'rejection' => 'fas fa-times-circle text-red-600 dark:text-red-400',
            'renewal' => 'fas fa-refresh text-cyan-600 dark:text-cyan-400',
            'expiration' => 'fas fa-exclamation-triangle text-red-600 dark:text-red-400',
            'payment' => 'fas fa-dollar-sign text-green-600 dark:text-green-400',
            'invoice' => 'fas fa-file-invoice text-blue-600 dark:text-blue-400',
            'document' => 'fas fa-file-alt text-gray-600 dark:text-gray-400',
            'email' => 'fas fa-envelope text-cyan-600 dark:text-cyan-400',
            'reminder' => 'fas fa-bell text-yellow-600 dark:text-yellow-400',
            'general' => 'fas fa-info-circle text-gray-600 dark:text-gray-400'
        ];
        
        return $icons[$type] ?? $icons['general'];
    }

    function getEventMessage($event) {
        $type = $event['type'] ?? 'general';
        $user = $event['user']['name'] ?? 'System';
        
        $messages = [
            'created' => "{$user} created this contract",
            'updated' => "{$user} updated the contract details",
            'status_change' => "{$user} changed the contract status",
            'comment' => "{$user} added a comment",
            'milestone' => "{$user} updated a milestone",
            'signature' => "{$user} added their signature",
            'approval' => "{$user} approved this contract",
            'rejection' => "{$user} rejected this contract",
            'renewal' => "{$user} initiated contract renewal",
            'expiration' => "Contract expiration notice",
            'payment' => "{$user} processed a payment",
            'invoice' => "{$user} generated an invoice",
            'document' => "{$user} uploaded a document",
            'email' => "{$user} sent an email",
            'reminder' => "Automated reminder sent",
            'general' => $event['description'] ?? 'Activity occurred'
        ];
        
        return $messages[$type] ?? $messages['general'];
    }
@endphp
