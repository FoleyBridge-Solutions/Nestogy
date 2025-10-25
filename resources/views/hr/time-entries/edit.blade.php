@php
    $sidebarContext = 'hr';
    $breadcrumbs = [
        ['name' => 'HR', 'route' => 'hr.dashboard'],
        ['name' => 'Time Entries', 'route' => 'hr.time-entries.index'],
        ['name' => 'Edit', 'active' => true]
    ];
@endphp

<x-layouts.app>
    <div class="max-w-3xl mx-auto">
        <flux:card>
            <form method="POST" action="{{ route('hr.time-entries.update', $entry) }}">
                @csrf
                @method('PUT')
                
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">Edit Time Entry</flux:heading>
                        <flux:subheading>Modify clock in/out times and break duration</flux:subheading>
                    </div>

                    <flux:separator />

                    <flux:callout icon="exclamation-triangle" variant="warning">
                        This entry will be marked as "Adjusted" when saved
                    </flux:callout>

                    <div>
                        <flux:field>
                            <flux:label>Employee</flux:label>
                            <flux:input disabled value="{{ $entry->user->name }}" />
                        </flux:field>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Clock In</flux:label>
                            <flux:input 
                                type="datetime-local" 
                                name="clock_in"
                                value="{{ $entry->clock_in->timezone(auth()->user()->company->getTimezone())->format('Y-m-d\TH:i') }}"
                                required />
                            <flux:error name="clock_in" />
                        </flux:field>
                        
                        <flux:field>
                            <flux:label>Clock Out</flux:label>
                            <flux:input 
                                type="datetime-local" 
                                name="clock_out"
                                value="{{ $entry->clock_out?->timezone(auth()->user()->company->getTimezone())->format('Y-m-d\TH:i') }}"
                                required />
                            <flux:error name="clock_out" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>Break Minutes</flux:label>
                        <flux:input 
                            type="number" 
                            name="break_minutes"
                            value="{{ $entry->break_minutes ?? 0 }}"
                            min="0"
                            max="480"
                            placeholder="0" />
                        <flux:description>Enter break time in minutes (max 480)</flux:description>
                        <flux:error name="break_minutes" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Notes</flux:label>
                        <flux:textarea 
                            name="notes"
                            rows="3"
                            placeholder="Add notes about this time entry...">{{ $entry->notes }}</flux:textarea>
                        <flux:error name="notes" />
                    </flux:field>

                    <flux:separator />

                    <div class="flex gap-3">
                        <flux:spacer />
                        <flux:button variant="ghost" href="{{ route('hr.time-entries.index') }}">Cancel</flux:button>
                        <flux:button 
                            type="submit" 
                            variant="primary"
                            icon="check">
                            Save Changes
                        </flux:button>
                    </div>
                </div>
            </form>
        </flux:card>
    </div>
</x-layouts.app>
