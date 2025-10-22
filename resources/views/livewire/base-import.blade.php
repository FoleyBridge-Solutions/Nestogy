<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
        
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="xl">{{ $title }}</flux:heading>
                    <flux:subheading>{{ $description }}</flux:subheading>
                </div>
                <div class="flex gap-3">
                    <flux:button 
                        :href="$templateRoute" 
                        variant="ghost" 
                        icon="arrow-down-tray">
                        Download Template
                    </flux:button>
                    <flux:button 
                        :href="$indexRoute" 
                        variant="ghost" 
                        icon="arrow-left">
                        Back
                    </flux:button>
                </div>
            </div>
        </flux:card>

        <flux:callout icon="information-circle" color="blue" class="mt-6">
            <flux:callout.heading>CSV Format Requirements</flux:callout.heading>
            <flux:callout.text>
                @foreach($importInstructions as $instruction)
                    <div class="mt-2">
                        <strong>{{ $instruction['title'] }}:</strong> {{ $instruction['content'] }}
                    </div>
                @endforeach
                <p class="mt-3"><strong>Tip:</strong> Download the template above to see the exact format required.</p>
            </flux:callout.text>
        </flux:callout>

        <flux:card class="mt-6">
            <form wire:submit.prevent="import">
                <div class="space-y-6">
                    
                    <div>
                        <flux:label>CSV File</flux:label>
                        <flux:subheading>Upload a CSV or TXT file (max {{ $this->getMaxFileSize() }}MB)</flux:subheading>
                        
                        <div class="mt-2">
                            <flux:file-upload wire:model="file" accept=".csv,.txt">
                                <flux:file-upload.dropzone 
                                    heading="Drop CSV file here or click to browse"
                                    text="CSV or TXT files up to {{ $this->getMaxFileSize() }}MB"
                                    :disabled="$importing" />
                            </flux:file-upload>
                        </div>

                        @if($file)
                            <div class="mt-3">
                                <flux:file-item 
                                    :heading="$file->getClientOriginalName()" 
                                    :size="$file->getSize()">
                                    <x-slot name="actions">
                                        <flux:file-item.remove wire:click="removeFile" :disabled="$importing" />
                                    </x-slot>
                                </flux:file-item>
                            </div>
                        @endif

                        @error('file')
                            <flux:error class="mt-2">{{ $message }}</flux:error>
                        @enderror
                    </div>

                    @if(count($importSettings) > 0)
                        <flux:separator />
                        
                        <div>
                            <flux:heading size="lg">Import Settings</flux:heading>
                            <flux:subheading>Configure how your data should be imported</flux:subheading>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($importSettings as $setting)
                                @if($setting['type'] === 'select')
                                    <flux:field>
                                        <flux:label :badge="($setting['required'] ?? false) ? 'Required' : null">
                                            {{ $setting['label'] }}
                                        </flux:label>
                                        <flux:select 
                                            wire:model="{{ $setting['model'] }}"
                                            :placeholder="$setting['placeholder'] ?? 'Select an option'"
                                            :required="$setting['required'] ?? false"
                                            :disabled="$importing">
                                            @foreach($setting['options'] as $value => $label)
                                                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </flux:field>
                                    
                                @elseif($setting['type'] === 'checkbox')
                                    <div class="flex items-center">
                                        <flux:checkbox 
                                            wire:model="{{ $setting['model'] }}"
                                            :label="$setting['label']"
                                            :disabled="$importing" />
                                    </div>
                                    @if(isset($setting['description']))
                                        <flux:subheading class="mt-1">{{ $setting['description'] }}</flux:subheading>
                                    @endif
                                    
                                @elseif($setting['type'] === 'textarea')
                                    <div class="md:col-span-2">
                                        <flux:textarea
                                            wire:model="{{ $setting['model'] }}"
                                            :label="$setting['label']"
                                            :placeholder="$setting['placeholder'] ?? ''"
                                            :rows="$setting['rows'] ?? 3"
                                            :disabled="$importing" />
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    @if($importing)
                        <div>
                            <flux:subheading>Importing... {{ $importProgress }}%</flux:subheading>
                            <div class="mt-2 w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" 
                                     style="width: {{ $importProgress }}%"></div>
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end gap-3">
                        <flux:button 
                            :href="$indexRoute" 
                            variant="ghost"
                            :disabled="$importing">
                            Cancel
                        </flux:button>
                        <flux:button 
                            type="submit" 
                            variant="primary"
                            icon="arrow-up-tray"
                            wire:loading.attr="disabled"
                            wire:target="import">
                            <span wire:loading.remove wire:target="import">Import</span>
                            <span wire:loading wire:target="import">Importing...</span>
                        </flux:button>
                    </div>
                </div>
            </form>
        </flux:card>

        @if($importResults)
            <flux:card class="mt-6">
                <flux:heading size="lg">Import Results</flux:heading>
                
                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                            {{ $importResults['success'] }}
                        </div>
                        <div class="text-sm text-green-800 dark:text-green-300 mt-1">Successful</div>
                    </div>
                    <div class="text-center p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                        <div class="text-3xl font-bold text-red-600 dark:text-red-400">
                            {{ $importResults['errors'] }}
                        </div>
                        <div class="text-sm text-red-800 dark:text-red-300 mt-1">Errors</div>
                    </div>
                    <div class="text-center p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                        <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                            {{ $importResults['skipped'] }}
                        </div>
                        <div class="text-sm text-yellow-800 dark:text-yellow-300 mt-1">Skipped</div>
                    </div>
                </div>

                @if(count($importResults['details'] ?? []) > 0)
                    <flux:separator class="my-4" />
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 max-h-96 overflow-y-auto">
                        @foreach($importResults['details'] as $detail)
                            <div class="text-sm mb-1 font-mono
                                {{ str_contains(strtolower($detail), 'error') ? 'text-red-600 dark:text-red-400' : '' }}
                                {{ str_contains(strtolower($detail), 'skipped') ? 'text-yellow-600 dark:text-yellow-400' : '' }}
                                {{ str_contains(strtolower($detail), 'success') ? 'text-green-600 dark:text-green-400' : '' }}">
                                {{ $detail }}
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="mt-4 flex justify-end">
                    <flux:button :href="$indexRoute" variant="primary" icon="arrow-right">
                        View Imported Records
                    </flux:button>
                </div>
            </flux:card>
        @endif

    </div>
</div>
