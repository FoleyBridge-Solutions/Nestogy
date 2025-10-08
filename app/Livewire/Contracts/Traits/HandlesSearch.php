<?php

namespace App\Livewire\Contracts\Traits;

trait HandlesSearch
{
    public function search()
    {
        if (empty($this->searchQuery)) {
            $this->searchResults = [];
            return;
        }
        
        $content = $this->editorMode === 'raw' ? $this->content : $this->previewContent;
        $pattern = $this->useRegex 
            ? '/' . $this->searchQuery . '/' . ($this->caseSensitive ? '' : 'i')
            : '/' . preg_quote($this->searchQuery, '/') . '/' . ($this->caseSensitive ? '' : 'i');
        
        preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);
        
        $this->searchResults = $matches[0] ?? [];
        $this->currentSearchIndex = 0;
    }
    
    public function nextSearchResult()
    {
        if (!empty($this->searchResults)) {
            $this->currentSearchIndex = ($this->currentSearchIndex + 1) % count($this->searchResults);
        }
    }
    
    public function previousSearchResult()
    {
        if (!empty($this->searchResults)) {
            $this->currentSearchIndex = ($this->currentSearchIndex - 1 + count($this->searchResults)) % count($this->searchResults);
        }
    }
    
    public function replaceOne()
    {
        if (empty($this->searchResults) || !isset($this->searchResults[$this->currentSearchIndex])) {
            return;
        }
        
        $this->pushToUndoStack();
        
        $match = $this->searchResults[$this->currentSearchIndex];
        $this->content = substr_replace($this->content, $this->replaceQuery, $match[1], strlen($match[0]));
        
        $this->hasChanges = true;
        $this->search();
        
        $this->trackChange('replace_one', [
            'from' => $match[0],
            'to' => $this->replaceQuery,
            'position' => $match[1],
        ]);
    }
    
    public function replaceAll()
    {
        if (empty($this->searchQuery)) {
            return;
        }
        
        $this->pushToUndoStack();
        
        $pattern = $this->useRegex 
            ? '/' . $this->searchQuery . '/' . ($this->caseSensitive ? '' : 'i')
            : '/' . preg_quote($this->searchQuery, '/') . '/' . ($this->caseSensitive ? '' : 'i');
        
        $count = 0;
        $this->content = preg_replace($pattern, $this->replaceQuery, $this->content, -1, $count);
        
        $this->hasChanges = true;
        $this->search();
        
        $this->trackChange('replace_all', [
            'from' => $this->searchQuery,
            'to' => $this->replaceQuery,
            'count' => $count,
        ]);
        
        session()->flash('success', "Replaced {$count} occurrence(s).");
    }
}
