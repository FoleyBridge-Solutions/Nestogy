<?php

namespace App\Livewire\Contracts\Traits;

trait HandlesComments
{
    public function addComment($section = null)
    {
        if (empty($this->newComment)) {
            return;
        }
        
        $comment = [
            'id' => uniqid(),
            'section' => $section,
            'content' => $this->newComment,
            'author' => auth()->user()->name,
            'created_at' => now()->toISOString(),
            'resolved' => false,
        ];
        
        array_push($this->comments, $comment);
        $this->newComment = '';
        $this->hasChanges = true;
        
        session()->flash('success', 'Comment added.');
    }
    
    public function resolveComment($commentId)
    {
        foreach ($this->comments as &$comment) {
            if ($comment['id'] === $commentId) {
                $comment['resolved'] = true;
                $comment['resolved_by'] = auth()->user()->name;
                $comment['resolved_at'] = now()->toISOString();
                $this->hasChanges = true;
                break;
            }
        }
        
        session()->flash('success', 'Comment resolved.');
    }
    
    public function deleteComment($commentId)
    {
        $this->comments = array_filter($this->comments, fn($c) => $c['id'] !== $commentId);
        $this->hasChanges = true;
        
        session()->flash('success', 'Comment deleted.');
    }
}
