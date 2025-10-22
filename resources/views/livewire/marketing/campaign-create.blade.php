<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <form wire:submit="save">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content - 2/3 width -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Header Card -->
                <flux:card>
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:heading size="xl">Create New Campaign</flux:heading>
                            <flux:subheading>Set up a new marketing campaign to engage your leads and contacts.</flux:subheading>
                        </div>
                        <flux:button 
                            href="{{ route('marketing.campaigns.index') }}" 
                            variant="ghost" 
                            icon="arrow-left">
                            Back
                        </flux:button>
                    </div>
                </flux:card>

                <!-- Campaign Information -->
                <flux:card>
                    <div>
                        <flux:heading size="lg">Campaign Information</flux:heading>
                        <flux:subheading>Core details about your campaign</flux:subheading>
                    </div>

                    <div class="space-y-6">
                        <!-- Campaign Name -->
                        <flux:input
                            wire:model="name"
                            label="Campaign Name"
                            placeholder="e.g., Welcome Series, MSP Services Nurture, Q1 Promotion"
                            required />

                        <!-- Campaign Type & Auto Enroll -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <flux:field>
                                <flux:label badge="Required">Campaign Type</flux:label>
                                <flux:select wire:model="type" placeholder="Select a type" required>
                                    @foreach($campaignTypes as $value => $label)
                                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </flux:field>

                            <div class="flex items-end pb-2">
                                <flux:switch 
                                    wire:model="auto_enroll"
                                    label="Auto-enroll matching leads"
                                    description="Automatically add leads that match target criteria" />
                            </div>
                        </div>

                        <!-- Description -->
                        <flux:textarea
                            wire:model="description"
                            label="Description"
                            placeholder="Describe the purpose and goals of this campaign..."
                            rows="4" />

                        <flux:callout icon="light-bulb" color="amber" variant="secondary">
                            <flux:callout.text>
                                Choose a descriptive name that clearly identifies the campaign's purpose and target audience
                            </flux:callout.text>
                        </flux:callout>
                    </div>
                </flux:card>

                <!-- Scheduling -->
                <flux:card>
                    <flux:fieldset>
                        <flux:legend>Scheduling</flux:legend>
                        <flux:description>Define when your campaign runs</flux:description>
                        
                        <flux:callout icon="calendar" variant="secondary" class="mt-4">
                            <flux:callout.text>
                                Leave dates blank to start and manage the campaign manually
                            </flux:callout.text>
                        </flux:callout>
                        
                        <flux:subheading class="mt-6">Start Date & Time</flux:subheading>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                            <flux:field>
                                <flux:label>Date</flux:label>
                                <flux:date-picker 
                                    wire:model="start_date"
                                    placeholder="Select start date" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Time</flux:label>
                                <flux:time-picker 
                                    wire:model="start_time"
                                    placeholder="Select time"
                                    time-format="12-hour" />
                            </flux:field>
                        </div>

                        <flux:separator class="my-6" />

                        <flux:subheading>End Date & Time</flux:subheading>
                        <flux:description>Optional automatic end date</flux:description>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                            <flux:field>
                                <flux:label>Date</flux:label>
                                <flux:date-picker 
                                    wire:model="end_date"
                                    placeholder="Select end date" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Time</flux:label>
                                <flux:time-picker 
                                    wire:model="end_time"
                                    placeholder="Select time"
                                    time-format="12-hour" />
                            </flux:field>
                        </div>
                    </flux:fieldset>
                </flux:card>

                <!-- Target Criteria -->
                <flux:card>
                    <flux:fieldset>
                        <flux:legend>Target Criteria</flux:legend>
                        <flux:description>Define who should receive this campaign based on score and status</flux:description>
                        
                        <flux:callout icon="users" variant="secondary" class="mt-4">
                            <flux:callout.text>
                                You can manually enroll leads after creating the campaign, or enable auto-enrollment to automatically add matching leads
                            </flux:callout.text>
                        </flux:callout>
                        
                        <flux:subheading class="mt-6">Lead Score Range</flux:subheading>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                            <flux:input
                                wire:model="min_score"
                                label="Minimum Score"
                                type="number"
                                min="0"
                                max="100" />

                            <flux:input
                                wire:model="max_score"
                                label="Maximum Score"
                                type="number"
                                min="0"
                                max="100" />
                        </div>

                        <flux:separator class="my-6" />

                        <flux:subheading>Target Lead Statuses</flux:subheading>
                        
                        <flux:checkbox.group wire:model="target_statuses" class="mt-2">
                            @foreach($availableStatuses as $status)
                                <flux:checkbox 
                                    value="{{ $status }}" 
                                    label="{{ ucfirst($status) }}" 
                                    :checked="in_array($status, ['new', 'contacted'])" />
                            @endforeach
                        </flux:checkbox.group>
                    </flux:fieldset>
                </flux:card>
            </div>

            <!-- Sidebar - 1/3 width -->
            <div class="space-y-6">
                <!-- Actions -->
                <flux:card>
                    <div class="space-y-4">
                        <flux:button
                            type="submit"
                            variant="primary"
                            class="w-full justify-center"
                            icon="check"
                            wire:loading.attr="disabled"
                            wire:target="save">
                            <span wire:loading.remove wire:target="save">Create Campaign</span>
                            <span wire:loading wire:target="save">Creating...</span>
                        </flux:button>

                        <flux:button
                            href="{{ route('marketing.campaigns.index') }}"
                            variant="ghost"
                            class="w-full justify-center"
                            icon="x-mark">
                            Cancel
                        </flux:button>
                    </div>
                </flux:card>

                <!-- Help Text -->
                <flux:callout icon="information-circle" color="blue">
                    <flux:callout.heading>Next Steps</flux:callout.heading>
                    <flux:callout.text>
                        After creating your campaign, you'll be able to:
                        <ul class="mt-2 ml-4 list-disc space-y-1">
                            <li>Add email sequences and templates</li>
                            <li>Enroll leads and contacts</li>
                            <li>Set up automation rules</li>
                            <li>Monitor performance metrics</li>
                        </ul>
                    </flux:callout.text>
                </flux:callout>
            </div>
        </form>
    </div>
</div>
