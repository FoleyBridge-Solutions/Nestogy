<div>
    <flux:card class="mb-4">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading>Create New Project</flux:heading>
                <flux:text>Set up a new project for your client with tasks and milestones</flux:text>
            </div>
            <flux:button href="{{ route('projects.index') }}" 
                        variant="ghost"
                        icon="arrow-left">
                Back to Projects
            </flux:button>
        </div>
    </flux:card>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2">
            <flux:card>
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-2">
                            <button 
                                type="button"
                                wire:click="goToStep(1)"
                                class="flex items-center space-x-2 {{ $currentStep === 1 ? 'text-blue-600 font-semibold' : 'text-gray-400' }}">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full {{ $currentStep === 1 ? 'bg-blue-600 text-white' : 'bg-gray-200' }}">1</span>
                                <span class="hidden sm:inline">Basic Info</span>
                            </button>
                            <span class="text-gray-300">→</span>
                            <button 
                                type="button"
                                wire:click="goToStep(2)"
                                class="flex items-center space-x-2 {{ $currentStep === 2 ? 'text-blue-600 font-semibold' : 'text-gray-400' }}">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full {{ $currentStep === 2 ? 'bg-blue-600 text-white' : 'bg-gray-200' }}">2</span>
                                <span class="hidden sm:inline">Details</span>
                            </button>
                            <span class="text-gray-300">→</span>
                            <button 
                                type="button"
                                wire:click="goToStep(3)"
                                class="flex items-center space-x-2 {{ $currentStep === 3 ? 'text-blue-600 font-semibold' : 'text-gray-400' }}">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full {{ $currentStep === 3 ? 'bg-blue-600 text-white' : 'bg-gray-200' }}">3</span>
                                <span class="hidden sm:inline">Template</span>
                            </button>
                            <span class="text-gray-300">→</span>
                            <button 
                                type="button"
                                wire:click="goToStep(4)"
                                class="flex items-center space-x-2 {{ $currentStep === 4 ? 'text-blue-600 font-semibold' : 'text-gray-400' }}">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full {{ $currentStep === 4 ? 'bg-blue-600 text-white' : 'bg-gray-200' }}">4</span>
                                <span class="hidden sm:inline">Review</span>
                            </button>
                        </div>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all" style="width: {{ ($currentStep / 4) * 100 }}%"></div>
                    </div>
                </div>

                <form wire:submit.prevent="save">
                    @if ($currentStep === 1)
                        <div class="space-y-6">
                            <flux:heading size="lg">Basic Information</flux:heading>
                            <flux:subheading>Start by selecting a client and naming your project</flux:subheading>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <flux:field>
                                    <flux:label for="client_id" required>Client</flux:label>
                                    <flux:select wire:model.live="client_id" id="client_id" name="client_id" required>
                                        <option value="">Select a client</option>
                                        @foreach($clients as $client)
                                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                                        @endforeach
                                    </flux:select>
                                    @error('client_id')
                                        <flux:error>{{ $message }}</flux:error>
                                    @enderror
                                </flux:field>
                                
                                <flux:field>
                                    <flux:label for="prefix">Project Prefix</flux:label>
                                    <flux:input 
                                        wire:model="prefix"
                                        type="text"
                                        id="prefix" 
                                        name="prefix" 
                                        placeholder="e.g., WEB, APP (optional)" />
                                    <flux:description>Custom prefix for project numbering (e.g., WEB-0001)</flux:description>
                                    @error('prefix')
                                        <flux:error>{{ $message }}</flux:error>
                                    @enderror
                                </flux:field>
                            </div>
                            
                            <flux:field>
                                <flux:label for="name" required>Project Name</flux:label>
                                <flux:input 
                                    wire:model="name"
                                    type="text"
                                    id="name" 
                                    name="name" 
                                    placeholder="e.g., Website Redesign"
                                    required />
                                @error('name')
                                    <flux:error>{{ $message }}</flux:error>
                                @enderror
                            </flux:field>
                            
                            <flux:field>
                                <flux:label for="description">Project Description</flux:label>
                                <flux:textarea 
                                    wire:model="description"
                                    id="description" 
                                    name="description" 
                                    rows="4" 
                                    placeholder="Describe the project scope, goals, and deliverables..." />
                                @error('description')
                                    <flux:error>{{ $message }}</flux:error>
                                @enderror
                            </flux:field>
                        </div>
                    @endif

                    @if ($currentStep === 2)
                        <div class="space-y-6">
                            <flux:heading size="lg">Project Details</flux:heading>
                            <flux:subheading>Configure timeline, budget, and project settings</flux:subheading>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <flux:field>
                                    <flux:label for="manager_id">Project Manager</flux:label>
                                    <flux:select wire:model="manager_id" id="manager_id" name="manager_id">
                                        <option value="">Unassigned</option>
                                        @foreach($managers as $manager)
                                            <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                                        @endforeach
                                    </flux:select>
                                    @error('manager_id')
                                        <flux:error>{{ $message }}</flux:error>
                                    @enderror
                                </flux:field>
                                
                                <flux:field>
                                    <flux:label for="status" required>Status</flux:label>
                                    <flux:select wire:model="status" id="status" name="status" required>
                                        <option value="pending">⏳ Pending</option>
                                        <option value="active">✅ Active</option>
                                        <option value="on_hold">⏸️ On Hold</option>
                                    </flux:select>
                                    @error('status')
                                        <flux:error>{{ $message }}</flux:error>
                                    @enderror
                                </flux:field>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <flux:field>
                                    <flux:label for="start_date">Start Date</flux:label>
                                    <flux:input 
                                        wire:model="start_date"
                                        type="date"
                                        id="start_date" 
                                        name="start_date" />
                                    @error('start_date')
                                        <flux:error>{{ $message }}</flux:error>
                                    @enderror
                                </flux:field>
                                
                                <flux:field>
                                    <flux:label for="due_date">Due Date</flux:label>
                                    <flux:input 
                                        wire:model="due_date"
                                        type="date"
                                        id="due_date" 
                                        name="due_date" />
                                    @error('due_date')
                                        <flux:error>{{ $message }}</flux:error>
                                    @enderror
                                </flux:field>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <flux:field>
                                    <flux:label for="priority" required>Priority</flux:label>
                                    <flux:select wire:model="priority" id="priority" name="priority" required>
                                        <option value="low">🟢 Low</option>
                                        <option value="medium">🟡 Medium</option>
                                        <option value="high">🟠 High</option>
                                        <option value="critical">🔴 Critical</option>
                                    </flux:select>
                                    @error('priority')
                                        <flux:error>{{ $message }}</flux:error>
                                    @enderror
                                </flux:field>
                                
                                <flux:field>
                                    <flux:label for="budget">Budget</flux:label>
                                    <flux:input 
                                        wire:model="budget"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        id="budget" 
                                        name="budget" 
                                        placeholder="0.00" />
                                    <flux:description>Project budget in USD</flux:description>
                                    @error('budget')
                                        <flux:error>{{ $message }}</flux:error>
                                    @enderror
                                </flux:field>
                            </div>
                        </div>
                    @endif

                    @if ($currentStep === 3)
                        <div class="space-y-6">
                            <flux:heading size="lg">Project Template (Optional)</flux:heading>
                            <flux:subheading>Use a template to quickly set up tasks and milestones</flux:subheading>
                            
                            <flux:field>
                                <flux:label for="template_id">Select Template</flux:label>
                                <flux:select wire:model.live="template_id" id="template_id" name="template_id">
                                    <option value="">No template - Start from scratch</option>
                                    @foreach($templates as $template)
                                        <option value="{{ $template->id }}">
                                            {{ $template->name }} 
                                            @if($template->category)
                                                ({{ ucfirst($template->category) }})
                                            @endif
                                        </option>
                                    @endforeach
                                </flux:select>
                                @error('template_id')
                                    <flux:error>{{ $message }}</flux:error>
                                @enderror
                            </flux:field>
                            
                            @if($template_id && $selectedTemplate)
                                <flux:card>
                                    <flux:heading size="sm">Template Options</flux:heading>
                                    <div class="mt-4 space-y-3">
                                        <label class="flex items-center space-x-2">
                                            <input type="checkbox" wire:model="apply_template_tasks" class="rounded border-gray-300">
                                            <span class="text-sm">Create tasks from template</span>
                                        </label>
                                        <label class="flex items-center space-x-2">
                                            <input type="checkbox" wire:model="apply_template_milestones" class="rounded border-gray-300">
                                            <span class="text-sm">Create milestones from template</span>
                                        </label>
                                        <label class="flex items-center space-x-2">
                                            <input type="checkbox" wire:model="apply_template_roles" class="rounded border-gray-300">
                                            <span class="text-sm">Assign team roles from template</span>
                                        </label>
                                    </div>
                                </flux:card>
                            @else
                                <flux:callout icon="information-circle" variant="outline">
                                    You can always add tasks and milestones after creating the project.
                                </flux:callout>
                            @endif
                        </div>
                    @endif

                    @if ($currentStep === 4)
                        <div class="space-y-6">
                            <flux:heading size="lg">Review & Create</flux:heading>
                            <flux:subheading>Review your project details before creating</flux:subheading>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <flux:heading size="sm" class="mb-3">Basic Information</flux:heading>
                                    <dl class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <dt class="font-medium text-gray-600 dark:text-gray-400">Client:</dt>
                                            <dd class="text-gray-900 dark:text-white">{{ $selectedClient?->name ?? 'Not selected' }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="font-medium text-gray-600 dark:text-gray-400">Project Name:</dt>
                                            <dd class="text-gray-900 dark:text-white">{{ $name ?: 'Not set' }}</dd>
                                        </div>
                                        @if($prefix)
                                        <div class="flex justify-between">
                                            <dt class="font-medium text-gray-600 dark:text-gray-400">Prefix:</dt>
                                            <dd class="text-gray-900 dark:text-white">{{ $prefix }}</dd>
                                        </div>
                                        @endif
                                    </dl>
                                </div>
                                
                                <div>
                                    <flux:heading size="sm" class="mb-3">Project Details</flux:heading>
                                    <dl class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <dt class="font-medium text-gray-600 dark:text-gray-400">Manager:</dt>
                                            <dd class="text-gray-900 dark:text-white">{{ $managers->firstWhere('id', $manager_id)?->name ?? 'Unassigned' }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="font-medium text-gray-600 dark:text-gray-400">Priority:</dt>
                                            <dd class="text-gray-900 dark:text-white">{{ ucfirst($priority) }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="font-medium text-gray-600 dark:text-gray-400">Status:</dt>
                                            <dd class="text-gray-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $status)) }}</dd>
                                        </div>
                                        @if($budget)
                                        <div class="flex justify-between">
                                            <dt class="font-medium text-gray-600 dark:text-gray-400">Budget:</dt>
                                            <dd class="text-gray-900 dark:text-white">${{ number_format($budget, 2) }}</dd>
                                        </div>
                                        @endif
                                    </dl>
                                </div>
                            </div>
                            
                            @if($description)
                            <div>
                                <flux:heading size="sm" class="mb-2">Description</flux:heading>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $description }}</p>
                            </div>
                            @endif
                            
                            @if($template_id)
                            <flux:callout icon="check-circle" variant="outline">
                                This project will be created using the "{{ $selectedTemplate?->name }}" template.
                            </flux:callout>
                            @endif
                        </div>
                    @endif

                    <div class="mt-8 flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                        <div>
                            @if ($currentStep > 1)
                                <flux:button 
                                    type="button"
                                    wire:click="previousStep"
                                    variant="ghost"
                                    icon="arrow-left">
                                    Previous
                                </flux:button>
                            @endif
                        </div>
                        
                        <div class="flex items-center space-x-3">
                            <flux:button 
                                type="button"
                                href="{{ route('projects.index') }}"
                                variant="ghost">
                                Cancel
                            </flux:button>
                            
                            @if ($currentStep < 4)
                                <flux:button 
                                    type="button"
                                    wire:click="nextStep"
                                    variant="primary"
                                    icon-trailing="arrow-right">
                                    Next Step
                                </flux:button>
                            @else
                                <flux:button 
                                    type="submit"
                                    variant="primary"
                                    icon="check">
                                    Create Project
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </form>
            </flux:card>
        </div>

        <div class="space-y-4">
            @if($selectedClient)
                <flux:card>
                    <flux:heading size="sm" class="mb-3">👤 Selected Client</flux:heading>
                    <div class="space-y-2">
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $selectedClient->name }}</div>
                        @if($selectedClient->email)
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ $selectedClient->email }}</div>
                        @endif
                        @if($selectedClient->phone)
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ $selectedClient->phone }}</div>
                        @endif
                    </div>
                </flux:card>
            @endif
            
            @if($template_id && $selectedTemplate)
                <flux:card>
                    <flux:heading size="sm" class="mb-3">📋 Template Preview</flux:heading>
                    <div class="space-y-3">
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">{{ $selectedTemplate->name }}</div>
                            @if($selectedTemplate->category)
                                <div class="text-xs text-gray-500">Category: {{ ucfirst($selectedTemplate->category) }}</div>
                            @endif
                        </div>
                        
                        @if($selectedTemplate->description)
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $selectedTemplate->description }}</p>
                        @endif
                        
                        <div class="space-y-1 text-sm">
                            @if($selectedTemplate->estimated_duration_days)
                                <div>⏱️ Duration: {{ $selectedTemplate->estimated_duration_days }} days</div>
                            @endif
                            @if($selectedTemplate->estimated_budget)
                                <div>💰 Est. Budget: ${{ number_format($selectedTemplate->estimated_budget, 2) }}</div>
                            @endif
                        </div>
                    </div>
                </flux:card>
            @endif
            
            <flux:card>
                <flux:heading size="sm" class="mb-3">📊 Project Summary</flux:heading>
                <div class="space-y-2 text-sm">
                    @if($start_date || $due_date)
                        <div>
                            <span class="font-medium">Timeline:</span>
                            <div class="text-gray-600 dark:text-gray-400">
                                {{ $start_date ? \Carbon\Carbon::parse($start_date)->format('M j, Y') : 'Not set' }}
                                @if($due_date)
                                    → {{ \Carbon\Carbon::parse($due_date)->format('M j, Y') }}
                                    @if($start_date)
                                        ({{ \Carbon\Carbon::parse($start_date)->diffInDays(\Carbon\Carbon::parse($due_date)) }} days)
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endif
                    
                    @if($budget)
                        <div>
                            <span class="font-medium">Budget:</span>
                            <span class="text-gray-600 dark:text-gray-400">${{ number_format($budget, 2) }}</span>
                        </div>
                    @endif
                    
                    <div>
                        <span class="font-medium">Priority:</span>
                        <span class="text-gray-600 dark:text-gray-400">
                            @switch($priority)
                                @case('low') 🟢 Low @break
                                @case('medium') 🟡 Medium @break
                                @case('high') 🟠 High @break
                                @case('critical') 🔴 Critical @break
                            @endswitch
                        </span>
                    </div>
                    
                    <div>
                        <span class="font-medium">Status:</span>
                        <span class="text-gray-600 dark:text-gray-400">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                    </div>
                </div>
            </flux:card>
        </div>
    </div>
</div>
