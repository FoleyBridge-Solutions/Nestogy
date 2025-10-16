<?php

namespace App\Livewire\Contracts;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractClause;
use App\Domains\Contract\Services\ContractClauseService;
use App\Domains\Contract\Services\ContractService;
use App\Domains\Core\Services\TemplateVariableMapper;
use App\Livewire\Contracts\Traits\HandlesComments;
use App\Livewire\Contracts\Traits\HandlesSearch;
use App\Livewire\Contracts\Traits\HandlesUndoRedo;
use App\Livewire\Contracts\Traits\ValidatesVariables;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class ContractLanguageEditor extends Component
{
    use AuthorizesRequests;
    use HandlesComments;
    use HandlesSearch;
    use HandlesUndoRedo;
    use ValidatesVariables;

    public Contract $contract;

    // Editor state
    public $editorMode = 'preview'; // preview, clauses, raw, variables, compare, comments

    public $content;

    public $variables = [];

    public $includedClauses = [];

    public $selectedClauseId = null;

    public $previewContent = '';

    // UI state
    public $canEdit = true;

    public $hasChanges = false;

    public $autoSaveEnabled = true;

    public $lastSaved = null;

    // Search & Replace
    public $searchQuery = '';

    public $replaceQuery = '';

    public $searchResults = [];

    public $currentSearchIndex = 0;

    public $caseSensitive = false;

    public $useRegex = false;

    // Version comparison
    public $compareWithVersion = null;

    public $versionDiff = null;

    // Comments
    public $comments = [];

    public $newComment = '';

    public $commentSection = null;

    // Change tracking
    public $changeHistory = [];

    public $trackChanges = true;

    // Undo/Redo
    public $undoStack = [];

    public $redoStack = [];

    public $maxUndoSteps = 50;

    public function mount(Contract $contract)
    {
        $this->authorize('update', $contract);

        $this->contract = $contract;
        $this->content = $contract->content ?? '';
        $this->variables = $contract->variables ?? [];
        $this->canEdit = $contract->canBeEdited();
        $this->lastSaved = $contract->updated_at;

        // Load included clauses if contract has template
        if ($contract->template_id && $contract->template) {
            $this->loadIncludedClauses();
        } else {
            // For contracts without templates, default to raw mode
            $this->editorMode = 'raw';
        }

        // Load comments from metadata
        $this->comments = $contract->metadata['comments'] ?? [];

        // Initialize undo stack with current state
        $this->pushToUndoStack();

        $this->generatePreview();
    }

    protected function loadIncludedClauses()
    {
        $clauses = $this->contract->template->clauses()
            ->orderBy('contract_template_clauses.sort_order')
            ->get();

        $this->includedClauses = $clauses->map(function ($clause) {
            return [
                'id' => $clause->id,
                'name' => $clause->name,
                'category' => $clause->category,
                'content' => $clause->content,
                'is_required' => $clause->pivot->is_required ?? false,
                'is_enabled' => true,
                'sort_order' => $clause->pivot->sort_order ?? 0,
                'preview' => null,
            ];
        })->toArray();
    }

    public function setEditorMode($mode)
    {
        $this->editorMode = $mode;

        if ($mode === 'preview') {
            $this->generatePreview();
        }
    }

    public function updateVariable($key, $value)
    {
        if (! $this->canEdit) {
            return;
        }

        $this->variables[$key] = $value;
        $this->hasChanges = true;

        // Regenerate preview with new variables
        if ($this->editorMode === 'preview') {
            $this->generatePreview();
        }
    }

    public function toggleClause($clauseId)
    {
        if (! $this->canEdit) {
            return;
        }

        foreach ($this->includedClauses as &$clause) {
            if ($clause['id'] == $clauseId) {
                // Can't disable required clauses
                if ($clause['is_required']) {
                    session()->flash('error', 'This clause is required and cannot be disabled.');

                    return;
                }

                $clause['is_enabled'] = ! $clause['is_enabled'];
                $this->hasChanges = true;
                break;
            }
        }

        $this->generatePreview();
    }

    public function previewClause($clauseId)
    {
        $this->selectedClauseId = $clauseId;

        // Generate preview for this specific clause
        foreach ($this->includedClauses as &$clause) {
            if ($clause['id'] == $clauseId) {
                $clauseModel = ContractClause::find($clauseId);
                if ($clauseModel) {
                    $clause['preview'] = $clauseModel->processContent($this->variables);
                }
                break;
            }
        }
    }

    public function generatePreview()
    {
        try {
            if (! $this->contract->template_id) {
                // No template, just use raw content
                $this->previewContent = $this->content;

                return;
            }

            $clauseService = app(ContractClauseService::class);

            // Filter to only enabled clauses
            $enabledClauses = collect($this->includedClauses)
                ->filter(fn ($c) => $c['is_enabled'])
                ->pluck('id');

            // Get actual clause models
            $clauses = ContractClause::whereIn('id', $enabledClauses)
                ->orderBy('sort_order')
                ->get();

            // Generate content from clauses with current variables
            $this->previewContent = $clauseService->generateContractFromClauses(
                $this->contract->template,
                $this->variables
            );

        } catch (\Exception $e) {
            \Log::error('Failed to generate contract preview', [
                'contract_id' => $this->contract->id,
                'error' => $e->getMessage(),
            ]);

            $this->previewContent = 'Error generating preview: '.$e->getMessage();
        }
    }

    public function regenerateFromTemplate()
    {
        if (! $this->canEdit || ! $this->contract->template_id) {
            session()->flash('error', 'Cannot regenerate content for this contract.');

            return;
        }

        try {
            $contractService = app(ContractService::class);

            // Regenerate variables first
            $variableMapper = app(TemplateVariableMapper::class);
            $this->variables = $variableMapper->generateVariables($this->contract);

            // Regenerate content
            $contractService->generateContractContent($this->contract);

            // Reload
            $this->contract->refresh();
            $this->content = $this->contract->content;
            $this->variables = $this->contract->variables ?? [];
            $this->loadIncludedClauses();
            $this->generatePreview();

            $this->hasChanges = false;

            session()->flash('success', 'Contract content regenerated successfully!');

        } catch (\Exception $e) {
            \Log::error('Failed to regenerate contract content', [
                'contract_id' => $this->contract->id,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Failed to regenerate content: '.$e->getMessage());
        }
    }

    public function save()
    {
        if (! $this->canEdit) {
            session()->flash('error', 'This contract cannot be edited.');

            return;
        }

        try {
            $metadata = $this->contract->metadata ?? [];
            $metadata['comments'] = $this->comments;

            $this->contract->update([
                'content' => $this->editorMode === 'raw' ? $this->content : $this->previewContent,
                'variables' => $this->variables,
                'metadata' => $metadata,
            ]);

            $this->hasChanges = false;
            $this->lastSaved = now();

            session()->flash('success', 'Contract language updated successfully!');

        } catch (\Exception $e) {
            \Log::error('Failed to save contract content', [
                'contract_id' => $this->contract->id,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Failed to save: '.$e->getMessage());
        }
    }

    public function reorderClauses($newOrder)
    {
        if (! $this->canEdit) {
            return;
        }

        $this->pushToUndoStack();

        $orderedClauses = [];
        foreach ($newOrder as $index => $clauseId) {
            foreach ($this->includedClauses as $clause) {
                if ($clause['id'] == $clauseId) {
                    $clause['sort_order'] = $index;
                    $orderedClauses[] = $clause;
                    break;
                }
            }
        }

        $this->includedClauses = $orderedClauses;
        $this->hasChanges = true;
        $this->generatePreview();

        $this->trackChange('reorder_clauses', ['new_order' => $newOrder]);
    }

    protected function trackChange($action, $details = [])
    {
        if (! $this->trackChanges) {
            return;
        }

        $change = [
            'action' => $action,
            'details' => $details,
            'user' => auth()->user()->name,
            'timestamp' => now()->toISOString(),
        ];

        array_push($this->changeHistory, $change);

        // Keep last 100 changes
        if (count($this->changeHistory) > 100) {
            array_shift($this->changeHistory);
        }
    }

    public function compareWithVersion($versionId)
    {
        // This would load a previous version from database
        // For now, we'll show a placeholder
        $this->compareWithVersion = $versionId;
        $this->editorMode = 'compare';

        // Generate diff (simplified - would need proper diff library)
        $this->versionDiff = [
            'original' => $this->content,
            'current' => $this->content,
            'changes' => [],
        ];
    }

    // Auto-save
    public function autoSave()
    {
        if (! $this->autoSaveEnabled || ! $this->hasChanges) {
            return;
        }

        try {
            $metadata = $this->contract->metadata ?? [];
            $metadata['comments'] = $this->comments;
            $metadata['auto_saved_at'] = now()->toISOString();

            $this->contract->update([
                'content' => $this->editorMode === 'raw' ? $this->content : $this->previewContent,
                'variables' => $this->variables,
                'metadata' => $metadata,
            ]);

            $this->lastSaved = now();

            // Don't clear hasChanges on auto-save, only on manual save

        } catch (\Exception $e) {
            \Log::error('Auto-save failed', [
                'contract_id' => $this->contract->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.contracts.contract-language-editor');
    }
}
