<div>
    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Campaign Overview --}}
            <flux:card>
                <flux:fieldset>
                    <flux:legend>Campaign Details</flux:legend>
                    
                    <div class="space-y-4">
                        <div>
                            <flux:subheading>Name</flux:subheading>
                            <div class="text-sm text-zinc-700 dark:text-zinc-300">{{ $campaign->name }}</div>
                        </div>

                        @if($campaign->description)
                        <div>
                            <flux:subheading>Description</flux:subheading>
                            <div class="text-sm text-zinc-700 dark:text-zinc-300">{{ $campaign->description }}</div>
                        </div>
                        @endif

                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <flux:subheading>Type</flux:subheading>
                                <flux:badge>{{ ucfirst($campaign->type) }}</flux:badge>
                            </div>

                            <div>
                                <flux:subheading>Status</flux:subheading>
                                <flux:badge 
                                    :color="match($campaign->status) {
                                        'active' => 'green',
                                        'scheduled' => 'blue',
                                        'paused' => 'yellow',
                                        'completed' => 'gray',
                                        'archived' => 'gray',
                                        default => 'zinc'
                                    }"
                                >
                                    {{ ucfirst($campaign->status) }}
                                </flux:badge>
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <flux:subheading>Start Date</flux:subheading>
                                <div class="text-sm text-zinc-700 dark:text-zinc-300">
                                    {{ $campaign->start_date?->format('M d, Y g:i A') ?? 'Not set' }}
                                </div>
                            </div>

                            <div>
                                <flux:subheading>End Date</flux:subheading>
                                <div class="text-sm text-zinc-700 dark:text-zinc-300">
                                    {{ $campaign->end_date?->format('M d, Y g:i A') ?? 'Not set' }}
                                </div>
                            </div>
                        </div>

                        <div>
                            <flux:subheading>Auto-enroll New Leads</flux:subheading>
                            <div class="text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $campaign->auto_enroll ? 'Yes' : 'No' }}
                            </div>
                        </div>

                        @if($campaign->created_by_user_id)
                        <div>
                            <flux:subheading>Created By</flux:subheading>
                            <div class="text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $campaign->createdBy?->name ?? 'Unknown' }}
                            </div>
                        </div>
                        @endif
                    </div>
                </flux:fieldset>
            </flux:card>

            {{-- Performance Metrics --}}
            <flux:card>
                <flux:fieldset>
                    <flux:legend>Performance Metrics</flux:legend>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                            <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                                {{ number_format($metrics['total_sent'] ?? 0) }}
                            </div>
                            <div class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">Total Sent</div>
                        </div>

                        <div class="text-center p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                            <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                                {{ number_format($metrics['total_delivered'] ?? 0) }}
                            </div>
                            <div class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">Delivered</div>
                        </div>

                        <div class="text-center p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                            <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                                {{ number_format($metrics['total_opens'] ?? 0) }}
                            </div>
                            <div class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">Opens</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-500 mt-1">
                                {{ number_format($metrics['open_rate'] ?? 0, 1) }}%
                            </div>
                        </div>

                        <div class="text-center p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                            <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                                {{ number_format($metrics['total_clicks'] ?? 0) }}
                            </div>
                            <div class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">Clicks</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-500 mt-1">
                                {{ number_format($metrics['click_rate'] ?? 0, 1) }}%
                            </div>
                        </div>

                        <div class="text-center p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                            <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                                {{ number_format($metrics['total_bounces'] ?? 0) }}
                            </div>
                            <div class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">Bounces</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-500 mt-1">
                                {{ number_format($metrics['bounce_rate'] ?? 0, 1) }}%
                            </div>
                        </div>

                        <div class="text-center p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                            <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                                {{ number_format($metrics['total_unsubscribes'] ?? 0) }}
                            </div>
                            <div class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">Unsubscribes</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-500 mt-1">
                                {{ number_format($metrics['unsubscribe_rate'] ?? 0, 1) }}%
                            </div>
                        </div>
                    </div>
                </flux:fieldset>
            </flux:card>

            {{-- Campaign Sequences --}}
            @if($campaign->sequences && $campaign->sequences->count() > 0)
            <flux:card>
                <flux:fieldset>
                    <flux:legend>Email Sequences ({{ $campaign->sequences->count() }})</flux:legend>
                    
                    <div class="space-y-3">
                        @foreach($campaign->sequences as $sequence)
                        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <flux:badge size="sm">Step {{ $sequence->step_number }}</flux:badge>
                                        <span class="font-medium text-zinc-900 dark:text-white">
                                            {{ $sequence->subject_line }}
                                        </span>
                                    </div>
                                    @if($sequence->delay_amount)
                                    <div class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">
                                        Delay: {{ $sequence->delay_amount }} {{ $sequence->delay_unit }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </flux:fieldset>
            </flux:card>
            @endif

            {{-- Campaign Enrollments --}}
            @if($campaign->enrollments && $campaign->enrollments->count() > 0)
            <flux:card>
                <flux:fieldset>
                    <flux:legend>Recent Enrollments ({{ $campaign->enrollments->count() }})</flux:legend>
                    
                    <div class="space-y-2">
                        @foreach($campaign->enrollments->take(10) as $enrollment)
                        <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                            <div>
                                <div class="text-sm font-medium text-zinc-900 dark:text-white">
                                    @if($enrollment->lead)
                                        {{ $enrollment->lead->name }} (Lead)
                                    @elseif($enrollment->contact)
                                        {{ $enrollment->contact->name }} (Contact)
                                    @else
                                        Unknown
                                    @endif
                                </div>
                                <div class="text-xs text-zinc-600 dark:text-zinc-400">
                                    Enrolled {{ $enrollment->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <flux:badge 
                                :color="match($enrollment->status) {
                                    'active' => 'green',
                                    'completed' => 'blue',
                                    'paused' => 'yellow',
                                    'unsubscribed' => 'red',
                                    default => 'zinc'
                                }"
                                size="sm"
                            >
                                {{ ucfirst($enrollment->status) }}
                            </flux:badge>
                        </div>
                        @endforeach
                    </div>
                </flux:fieldset>
            </flux:card>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Actions --}}
            <flux:card>
                <flux:fieldset>
                    <flux:legend>Actions</flux:legend>
                    
                    <div class="space-y-2">
                        <flux:button variant="primary" href="{{ route('marketing.campaigns.edit', $campaign) }}" class="w-full">
                            Edit Campaign
                        </flux:button>
                        
                        <flux:button variant="ghost" href="{{ route('marketing.campaigns.index') }}" class="w-full">
                            Back to Campaigns
                        </flux:button>
                    </div>
                </flux:fieldset>
            </flux:card>

            {{-- Target Criteria --}}
            @if($campaign->target_criteria && count($campaign->target_criteria) > 0)
            <flux:card>
                <flux:fieldset>
                    <flux:legend>Target Criteria</flux:legend>
                    
                    <div class="space-y-3 text-sm">
                        @if(isset($campaign->target_criteria['min_lead_score']) || isset($campaign->target_criteria['max_lead_score']))
                        <div>
                            <flux:subheading>Lead Score Range</flux:subheading>
                            <div class="text-zinc-700 dark:text-zinc-300">
                                {{ $campaign->target_criteria['min_lead_score'] ?? 0 }} - {{ $campaign->target_criteria['max_lead_score'] ?? 100 }}
                            </div>
                        </div>
                        @endif

                        @if(isset($campaign->target_criteria['lead_statuses']) && count($campaign->target_criteria['lead_statuses']) > 0)
                        <div>
                            <flux:subheading>Lead Statuses</flux:subheading>
                            <div class="flex flex-wrap gap-1 mt-1">
                                @foreach($campaign->target_criteria['lead_statuses'] as $status)
                                <flux:badge size="sm">{{ ucfirst($status) }}</flux:badge>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </flux:fieldset>
            </flux:card>
            @endif

            {{-- Quick Stats --}}
            <flux:card>
                <flux:fieldset>
                    <flux:legend>Quick Stats</flux:legend>
                    
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-zinc-600 dark:text-zinc-400">Total Recipients</span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ number_format($campaign->total_recipients ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-600 dark:text-zinc-400">Total Sent</span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ number_format($campaign->total_sent ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-600 dark:text-zinc-400">Total Delivered</span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ number_format($campaign->total_delivered ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-600 dark:text-zinc-400">Total Opened</span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ number_format($campaign->total_opened ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-600 dark:text-zinc-400">Total Clicked</span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ number_format($campaign->total_clicked ?? 0) }}</span>
                        </div>
                    </div>
                </flux:fieldset>
            </flux:card>
        </div>
    </div>
</div>
