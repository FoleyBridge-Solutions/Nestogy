<?php

namespace App\Livewire\Contracts\Traits;

trait HandlesUndoRedo
{
    protected function pushToUndoStack()
    {
        $state = [
            'content' => $this->content,
            'variables' => $this->variables,
            'includedClauses' => $this->includedClauses,
            'timestamp' => now(),
        ];

        array_push($this->undoStack, $state);

        if (count($this->undoStack) > $this->maxUndoSteps) {
            array_shift($this->undoStack);
        }

        $this->redoStack = [];
    }

    public function undo()
    {
        if (count($this->undoStack) <= 1) {
            session()->flash('warning', 'Nothing to undo.');

            return;
        }

        $currentState = array_pop($this->undoStack);
        array_push($this->redoStack, $currentState);

        $previousState = end($this->undoStack);
        $this->content = $previousState['content'];
        $this->variables = $previousState['variables'];
        $this->includedClauses = $previousState['includedClauses'];

        $this->hasChanges = true;
        $this->generatePreview();

        session()->flash('success', 'Undone successfully.');
    }

    public function redo()
    {
        if (empty($this->redoStack)) {
            session()->flash('warning', 'Nothing to redo.');

            return;
        }

        $nextState = array_pop($this->redoStack);
        array_push($this->undoStack, $nextState);

        $this->content = $nextState['content'];
        $this->variables = $nextState['variables'];
        $this->includedClauses = $nextState['includedClauses'];

        $this->hasChanges = true;
        $this->generatePreview();

        session()->flash('success', 'Redone successfully.');
    }
}
