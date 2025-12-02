<div class="space-y-6">
    {{-- Header --}}
    <flux:card>
        <div class="p-6">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h1 class="text-2xl font-bold">{{ $lead->first_name }} {{ $lead->last_name }}</h1>
                    @if($lead->company_name)
                        <p class="text-lg text-gray-600 mt-1">{{ $lead->company_name }}</p>
                    @endif
                    <div class="flex items-center gap-4 mt-3 text-sm text-gray-600">
                        <span><strong>Email:</strong> {{ $lead->email }}</span>
                        @if($lead->phone)
                            <flux:separator vertical class="h-4" />
                            <span><strong>Phone:</strong> {{ $lead->phone }}</span>
                        @endif
                        @if($lead->title)
                            <flux:separator vertical class="h-4" />
                            <span><strong>Title:</strong> {{ $lead->title }}</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 mt-3">
                        <flux:badge variant="{{
                            $lead->status === 'new' ? 'info' :
                            ($lead->status === 'contacted' ? 'warning' :
                            ($lead->status === 'qualified' ? 'success' :
                            ($lead->status === 'converted' ? 'success' : 'danger')))
                        }}">
                            {{ ucfirst($lead->status) }}
                        </flux:badge>
                        @if($lead->priority)
                            <flux:badge variant="{{
                                $lead->priority === 'high' ? 'danger' :
                                ($lead->priority === 'medium' ? 'warning' : 'info')
                            }}">
                                {{ ucfirst($lead->priority) }} Priority
                            </flux:badge>
                        @endif
                        @if($lead->estimated_value)
                            <flux:badge variant="outline">
                                ${{ number_format($lead->estimated_value, 2) }}
                            </flux:badge>
                        @endif
                    </div>
                </div>
                
                <div class="flex items-center gap-2">
                    @if($lead->status !== 'converted' && Auth::user()->can('convert', $lead))
                        <flux:button variant="primary" size="sm" wire:click="$set('showConvertModal', true)">
                            <flux:icon.check class="mr-2" />
                            Convert to Client
                        </flux:button>
                    @endif
                    
                    <flux:button variant="outline" size="sm" href="{{ route('leads.edit', $lead) }}">
                        <flux:icon.pencil class="mr-2" />
                        Edit
                    </flux:button>
                    
                    <flux:dropdown>
                        <flux:button variant="ghost" size="sm">
                            <flux:icon.ellipsis-horizontal />
                        </flux:button>
                        <flux:menu>
                            <flux:menu.item href="{{ route('leads.edit', $lead) }}">
                                <flux:icon.pencil class="mr-2" />
                                Edit Lead
                            </flux:menu.item>
                            <flux:menu.item href="{{ route('leads.activities', $lead) }}">
                                <flux:icon.list-bullet class="mr-2" />
                                Activity History
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item variant="danger" wire:click="$set('showDeleteModal', true)">
                                <flux:icon.trash class="mr-2" />
                                Delete Lead
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>
        </div>
    </flux:card>

    {{-- AI Insights Widget --}}
    <x-ai-insights 
        :enabled="$aiEnabled"
        :loading="$aiLoading"
        :insights="$aiInsights"
    />

    {{-- Lead Details --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Contact Information --}}
        <flux:card>
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Contact Information</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Name:</span>
                        <span class="font-medium">{{ $lead->first_name }} {{ $lead->last_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Email:</span>
                        <span class="font-medium">{{ $lead->email }}</span>
                    </div>
                    @if($lead->phone)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Phone:</span>
                            <span class="font-medium">{{ $lead->phone }}</span>
                        </div>
                    @endif
                    @if($lead->title)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Title:</span>
                            <span class="font-medium">{{ $lead->title }}</span>
                        </div>
                    @endif
                    @if($lead->company_name)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Company:</span>
                            <span class="font-medium">{{ $lead->company_name }}</span>
                        </div>
                    @endif
                    @if($lead->website)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Website:</span>
                            <a href="{{ $lead->website }}" target="_blank" class="font-medium text-blue-600 hover:underline">
                                {{ $lead->website }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </flux:card>

        {{-- Lead Information --}}
        <flux:card>
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Lead Information</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="font-medium">{{ ucfirst($lead->status) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Priority:</span>
                        <span class="font-medium">{{ ucfirst($lead->priority) }}</span>
                    </div>
                    @if($lead->leadSource)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Source:</span>
                            <span class="font-medium">{{ $lead->leadSource->name }}</span>
                        </div>
                    @endif
                    @if($lead->industry)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Industry:</span>
                            <span class="font-medium">{{ $lead->industry }}</span>
                        </div>
                    @endif
                    @if($lead->company_size)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Company Size:</span>
                            <span class="font-medium">{{ $lead->company_size }} employees</span>
                        </div>
                    @endif
                    @if($lead->estimated_value)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Estimated Value:</span>
                            <span class="font-medium">${{ number_format($lead->estimated_value, 2) }}</span>
                        </div>
                    @endif
                    @if($lead->assignedUser)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Assigned To:</span>
                            <span class="font-medium">{{ $lead->assignedUser->name }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </flux:card>
    </div>

    {{-- Notes --}}
    @if($lead->notes)
        <flux:card>
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Notes</h3>
                <div class="prose max-w-none">
                    {{ $lead->notes }}
                </div>
            </div>
        </flux:card>
    @endif
</div>
