<div x-data="{
    isDragging: false,
    draggedIndex: null,
    showSearch: false,
    autoSaveInterval: null,
}" x-init="
    // Auto-save every 30 seconds
    if (@js($autoSaveEnabled)) {
        autoSaveInterval = setInterval(() => {
            @this.autoSave();
        }, 30000);
    }
    
    // Cleanup on destroy
    $watch('autoSaveEnabled', value => {
        if (!value && autoSaveInterval) {
            clearInterval(autoSaveInterval);
        }
    });
" class="flex flex-col h-full">
    
    {{-- Header Bar --}}
    <div class="sticky top-0 z-10 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center justify-between px-6 py-4">
            {{-- Left: Mode Selector --}}
            <div class="flex items-center gap-2">
                <flux:badge size="sm" :color="$hasChanges ? 'yellow' : 'green'">
                    {{ $hasChanges ? 'Unsaved Changes' : 'Saved' }}
                </flux:badge>
                
                @if($lastSaved)
                    <flux:text size="sm" class="text-gray-500">
                        Last saved {{ $lastSaved->diffForHumans() }}
                    </flux:text>
                @endif
                
                @if(count($undoStack) > 1)
                    <flux:badge size="sm" color="zinc">
                        {{ count($undoStack) - 1 }} actions in history
                    </flux:badge>
                @endif
            </div>
            
            {{-- Right: Actions --}}
            <div class="flex items-center gap-2">
                {{-- Undo/Redo --}}
                <flux:button 
                    wire:click="undo" 
                    size="sm" 
                    variant="ghost"
                    :disabled="count($undoStack) <= 1"
                    icon="arrow-uturn-left"
                    tooltip="Undo (Ctrl+Z)"
                />
                
                <flux:button 
                    wire:click="redo" 
                    size="sm" 
                    variant="ghost"
                    :disabled="empty($redoStack)"
                    icon="arrow-uturn-right"
                    tooltip="Redo (Ctrl+Y)"
                />
                
                <flux:separator vertical />
                
                {{-- Search --}}
                <flux:button 
                    @click="showSearch = !showSearch" 
                    size="sm" 
                    variant="ghost"
                    icon="magnifying-glass"
                    tooltip="Search & Replace (Ctrl+F)"
                />
                
                {{-- Variable Validation --}}
                <flux:button 
                    wire:click="validateVariables" 
                    size="sm" 
                    variant="ghost"
                    icon="check-badge"
                    tooltip="Validate Variables"
                />
                
                <flux:separator vertical />
                
                {{-- Auto-save Toggle --}}
                <flux:switch 
                    wire:model.live="autoSaveEnabled" 
                    label="Auto-save"
                    size="sm"
                />
                
                <flux:separator vertical />
                
                {{-- Save --}}
                <flux:button 
                    wire:click="save" 
                    variant="primary"
                    :disabled="!$hasChanges || !$canEdit"
                >
                    Save Changes
                </flux:button>
            </div>
        </div>
        
        {{-- Search Bar --}}
        <div x-show="showSearch" 
             x-transition
             class="px-6 py-3 bg-gray-50 border-t border-gray-200 dark:bg-gray-900 dark:border-gray-700">
            <div class="flex items-center gap-2">
                <flux:input 
                    wire:model.live.debounce.300ms="searchQuery"
                    placeholder="Search..."
                    class="flex-1"
                />
                
                <flux:input 
                    wire:model="replaceQuery"
                    placeholder="Replace with..."
                    class="flex-1"
                />
                
                <flux:checkbox wire:model.live="caseSensitive" label="Case sensitive" />
                <flux:checkbox wire:model.live="useRegex" label="Regex" />
                
                @if(!empty($searchResults))
                    <flux:text size="sm" class="text-gray-600">
                        {{ $currentSearchIndex + 1 }} / {{ count($searchResults) }}
                    </flux:text>
                    
                    <flux:button 
                        wire:click="previousSearchResult" 
                        size="sm" 
                        variant="ghost"
                        icon="chevron-up"
                    />
                    
                    <flux:button 
                        wire:click="nextSearchResult" 
                        size="sm" 
                        variant="ghost"
                        icon="chevron-down"
                    />
                @endif
                
                <flux:button 
                    wire:click="replaceOne" 
                    size="sm"
                    :disabled="empty($searchResults)"
                >
                    Replace
                </flux:button>
                
                <flux:button 
                    wire:click="replaceAll" 
                    size="sm"
                    :disabled="empty($searchResults)"
                >
                    Replace All
                </flux:button>
                
                <flux:button 
                    @click="showSearch = false" 
                    size="sm" 
                    variant="ghost"
                    icon="x-mark"
                />
            </div>
        </div>
    </div>
    
    {{-- Mode Tabs --}}
    <flux:tabs wire:model.live="editorMode" class="border-b border-gray-200 dark:border-gray-700">
        <flux:tab name="preview" icon="eye">Preview</flux:tab>
        
        @if($contract->template_id)
            <flux:tab name="clauses" icon="document-duplicate">Clauses</flux:tab>
        @endif
        
        <flux:tab name="raw" icon="code-bracket">Editor</flux:tab>
        
        @if($contract->template_id)
            <flux:tab name="variables" icon="variable">Variables</flux:tab>
        @endif
        
        <flux:tab name="comments" icon="chat-bubble-left-right">
            Comments
            @if(count(array_filter($comments, fn($c) => !$c['resolved'])) > 0)
                <flux:badge size="sm" color="red">
                    {{ count(array_filter($comments, fn($c) => !$c['resolved'])) }}
                </flux:badge>
            @endif
        </flux:tab>
        
        <flux:tab name="compare" icon="arrows-right-left">Compare</flux:tab>
    </flux:tabs>
    
    {{-- Content Area --}}
    <div class="flex-1 overflow-auto">
        {{-- Preview Mode --}}
        @if($editorMode === 'preview')
            <div class="max-w-4xl mx-auto px-6 py-8">
                @if(empty($previewContent) && empty($content))
                    <flux:callout icon="information-circle" variant="info">
                        <div class="space-y-2">
                            <flux:heading size="lg">No Content Yet</flux:heading>
                            <flux:text>
                                This contract doesn't have any content yet. 
                                @if($contract->template_id)
                                    Click "Regenerate from Template" to generate content from the template.
                                @else
                                    Switch to the "Editor" tab to start writing.
                                @endif
                            </flux:text>
                            @if($contract->template_id)
                                <flux:button wire:click="regenerateFromTemplate" variant="primary" class="mt-4">
                                    Regenerate from Template
                                </flux:button>
                            @endif
                        </div>
                    </flux:callout>
                @else
                    <div class="prose prose-sm max-w-none dark:prose-invert">
                        {!! \Illuminate\Support\Str::markdown($previewContent ?: $content) !!}
                    </div>
                @endif
            </div>
        @endif
        
        {{-- Clauses Mode --}}
        @if($editorMode === 'clauses' && $contract->template_id)
            <div class="max-w-6xl mx-auto px-6 py-8">
                <div class="flex items-center justify-between mb-6">
                    <flux:heading size="lg">Manage Clauses</flux:heading>
                    <flux:button wire:click="regenerateFromTemplate" variant="ghost" icon="arrow-path">
                        Reset to Template
                    </flux:button>
                </div>
                
                @if(empty($includedClauses))
                    <flux:callout icon="information-circle" variant="warning">
                        No clauses found. Click "Reset to Template" to load template clauses.
                    </flux:callout>
                @else
                    {{-- Group clauses by category --}}
                    @php
                        $groupedClauses = collect($includedClauses)->groupBy('category');
                        $categories = \App\Domains\Contract\Models\ContractClause::getAvailableCategories();
                    @endphp
                    
                    @foreach($groupedClauses as $category => $clauses)
                        <div class="mb-8">
                            <flux:heading size="base" class="mb-4">
                                {{ $categories[$category] ?? ucfirst($category) }}
                            </flux:heading>
                            
                            <div class="space-y-3" 
                                 x-data="{ 
                                     items: @js($clauses->pluck('id')->toArray()),
                                     dragging: null 
                                 }"
                                 x-on:dragover.prevent
                                 x-on:drop.prevent="
                                     const draggedId = dragging;
                                     const droppedOnId = $event.target.closest('[data-clause-id]')?.dataset.clauseId;
                                     if (draggedId && droppedOnId && draggedId !== droppedOnId) {
                                         const draggedIndex = items.indexOf(draggedId);
                                         const droppedIndex = items.indexOf(droppedOnId);
                                         items.splice(draggedIndex, 1);
                                         items.splice(droppedIndex, 0, draggedId);
                                         @this.reorderClauses(items);
                                     }
                                     dragging = null;
                                 ">
                                @foreach($clauses as $index => $clause)
                                    <flux:card 
                                        :data-clause-id="$clause['id']"
                                        draggable="true"
                                        x-on:dragstart="dragging = '{{ $clause['id'] }}'; $el.classList.add('opacity-50')"
                                        x-on:dragend="dragging = null; $el.classList.remove('opacity-50')"
                                        class="cursor-move hover:shadow-md transition-shadow">
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-start gap-3 flex-1">
                                                <flux:icon.bars-3 class="size-5 text-gray-400 mt-1" />
                                                
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <flux:heading size="sm">{{ $clause['name'] }}</flux:heading>
                                                        
                                                        @if($clause['is_required'])
                                                            <flux:badge size="xs" color="red">Required</flux:badge>
                                                        @endif
                                                        
                                                        <flux:badge size="xs" color="zinc">
                                                            {{ $clause['sort_order'] + 1 }}
                                                        </flux:badge>
                                                    </div>
                                                    
                                                    {{-- Clause Preview --}}
                                                    @if($selectedClauseId === $clause['id'] && isset($clause['preview']))
                                                        <div class="mt-3 p-3 bg-gray-50 rounded-lg dark:bg-gray-900">
                                                            <div class="prose prose-sm max-w-none dark:prose-invert">
                                                                {!! \Illuminate\Support\Str::markdown($clause['preview']) !!}
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-center gap-2">
                                                @if(!$clause['is_required'])
                                                    <flux:switch 
                                                        :checked="$clause['is_enabled']"
                                                        wire:click="toggleClause({{ $clause['id'] }})"
                                                        size="sm"
                                                    />
                                                @endif
                                                
                                                <flux:button 
                                                    wire:click="previewClause({{ $clause['id'] }})"
                                                    size="sm"
                                                    variant="ghost"
                                                    icon="eye"
                                                    tooltip="Preview"
                                                />
                                            </div>
                                        </div>
                                    </flux:card>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        @endif
        
        {{-- Raw Editor Mode --}}
        @if($editorMode === 'raw')
            <div class="max-w-6xl mx-auto px-6 py-8">
                <flux:editor 
                    wire:model.live.debounce.500ms="content"
                    label="Contract Content"
                    description="Edit the contract content directly. Changes are auto-saved every 30 seconds."
                    toolbar="heading | bold italic underline strike | bullet ordered blockquote | link | align ~ undo redo"
                    class="**:data-[slot=content]:min-h-[600px]!"
                >
                    <flux:editor.toolbar>
                        <flux:editor.heading />
                        <flux:editor.separator />
                        <flux:editor.bold />
                        <flux:editor.italic />
                        <flux:editor.underline />
                        <flux:editor.strike />
                        <flux:editor.separator />
                        <flux:editor.bullet />
                        <flux:editor.ordered />
                        <flux:editor.blockquote />
                        <flux:editor.separator />
                        <flux:editor.link />
                        <flux:editor.separator />
                        <flux:editor.align />
                        <flux:editor.spacer />
                        <flux:editor.undo />
                        <flux:editor.redo />
                    </flux:editor.toolbar>
                    <flux:editor.content />
                </flux:editor>
            </div>
        @endif
        
        {{-- Variables Mode --}}
        @if($editorMode === 'variables' && $contract->template_id)
            <div class="max-w-4xl mx-auto px-6 py-8">
                <div class="flex items-center justify-between mb-6">
                    <flux:heading size="lg">Template Variables</flux:heading>
                    <flux:button wire:click="validateVariables" variant="ghost" icon="check-badge">
                        Validate All
                    </flux:button>
                </div>
                
                @if(empty($variables))
                    <flux:callout icon="information-circle" variant="info">
                        No variables defined. Variables will be generated when you create content from the template.
                    </flux:callout>
                @else
                    <div class="space-y-4">
                        @foreach($variables as $key => $value)
                            <flux:field>
                                <flux:label>
                                    {{ str_replace('_', ' ', ucfirst($key)) }}
                                </flux:label>
                                
                                @if(is_array($value))
                                    <flux:textarea 
                                        wire:model.blur="variables.{{ $key }}"
                                        rows="3"
                                        placeholder="Enter {{ str_replace('_', ' ', $key) }}"
                                    >{{ json_encode($value, JSON_PRETTY_PRINT) }}</flux:textarea>
                                @elseif(strlen($value ?? '') > 100)
                                    <flux:textarea 
                                        wire:model.blur="variables.{{ $key }}"
                                        rows="3"
                                        placeholder="Enter {{ str_replace('_', ' ', $key) }}"
                                    />
                                @else
                                    <flux:input 
                                        wire:model.blur="variables.{{ $key }}"
                                        placeholder="Enter {{ str_replace('_', ' ', $key) }}"
                                    />
                                @endif
                                
                                <flux:description>
                                    Variable name: <code>@{{ {{ $key }} }}</code>
                                </flux:description>
                            </flux:field>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
        
        {{-- Comments Mode --}}
        @if($editorMode === 'comments')
            <div class="max-w-4xl mx-auto px-6 py-8">
                <flux:heading size="lg" class="mb-6">Comments & Feedback</flux:heading>
                
                {{-- Add Comment Form --}}
                <flux:card class="mb-6">
                    <flux:field>
                        <flux:label>Add Comment</flux:label>
                        <flux:textarea 
                            wire:model="newComment"
                            rows="3"
                            placeholder="Type your comment or feedback..."
                        />
                    </flux:field>
                    
                    <flux:button wire:click="addComment" variant="primary" class="mt-3">
                        Add Comment
                    </flux:button>
                </flux:card>
                
                {{-- Comments List --}}
                @if(empty($comments))
                    <flux:callout icon="information-circle" variant="info">
                        No comments yet. Add a comment above to start a discussion.
                    </flux:callout>
                @else
                    <div class="space-y-4">
                        @foreach($comments as $comment)
                            <flux:card :class="$comment['resolved'] ? 'opacity-60' : ''">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <flux:heading size="sm">{{ $comment['author'] }}</flux:heading>
                                        <flux:text size="sm" class="text-gray-500">
                                            {{ \Carbon\Carbon::parse($comment['created_at'])->diffForHumans() }}
                                        </flux:text>
                                    </div>
                                    
                                    <div class="flex items-center gap-2">
                                        @if($comment['resolved'])
                                            <flux:badge size="sm" color="green">Resolved</flux:badge>
                                        @else
                                            <flux:button 
                                                wire:click="resolveComment('{{ $comment['id'] }}')"
                                                size="sm"
                                                variant="ghost"
                                            >
                                                Resolve
                                            </flux:button>
                                        @endif
                                        
                                        <flux:button 
                                            wire:click="deleteComment('{{ $comment['id'] }}')"
                                            size="sm"
                                            variant="ghost"
                                            icon="trash"
                                        />
                                    </div>
                                </div>
                                
                                <flux:text>{{ $comment['content'] }}</flux:text>
                                
                                @if($comment['resolved'] && isset($comment['resolved_by']))
                                    <flux:text size="sm" class="text-gray-500 mt-2">
                                        Resolved by {{ $comment['resolved_by'] }} 
                                        {{ \Carbon\Carbon::parse($comment['resolved_at'])->diffForHumans() }}
                                    </flux:text>
                                @endif
                            </flux:card>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
        
        {{-- Compare Mode --}}
        @if($editorMode === 'compare')
            <div class="max-w-6xl mx-auto px-6 py-8">
                <flux:heading size="lg" class="mb-6">Version Comparison</flux:heading>
                
                <flux:callout icon="information-circle" variant="info" class="mb-6">
                    Version comparison feature coming soon. This will allow you to compare the current contract with previous versions and see a visual diff of changes.
                </flux:callout>
                
                {{-- Placeholder for version comparison UI --}}
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <flux:heading size="base" class="mb-3">Current Version</flux:heading>
                        <div class="prose prose-sm max-w-none dark:prose-invert p-4 bg-gray-50 rounded-lg dark:bg-gray-900">
                            {!! \Illuminate\Support\Str::markdown(substr($content ?: $previewContent, 0, 500)) !!}
                            @if(strlen($content ?: $previewContent) > 500)
                                <p class="text-gray-500">... (truncated)</p>
                            @endif
                        </div>
                    </div>
                    
                    <div>
                        <flux:heading size="base" class="mb-3">Compare With</flux:heading>
                        <flux:select placeholder="Select a version to compare...">
                            <option>Version 1.0 - 2 days ago</option>
                            <option>Version 0.9 - 1 week ago</option>
                            <option>Original - 2 weeks ago</option>
                        </flux:select>
                    </div>
                </div>
            </div>
        @endif
    </div>
    
    {{-- Keyboard Shortcuts --}}
    <div x-on:keydown.ctrl.z.prevent="@this.undo()"
         x-on:keydown.ctrl.y.prevent="@this.redo()"
         x-on:keydown.ctrl.f.prevent="showSearch = true"
         x-on:keydown.ctrl.s.prevent="@this.save()">
    </div>
</div>
