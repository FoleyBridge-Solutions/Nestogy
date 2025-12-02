<?php

namespace App\Livewire\Knowledge;

use App\Domains\Knowledge\Models\KbArticle;
use App\Traits\HasAutomaticAI;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class KbArticleShow extends Component
{
    use HasAutomaticAI;

    public KbArticle $article;

    public $showEditModal = false;
    public $showDeleteModal = false;

    public function mount(KbArticle $article)
    {
        $this->article = $article;
        
        $this->initializeAI($article);
        
        $this->article->load([
            'category',
            'author',
            'views',
            'feedback',
            'relatedArticles',
            'clients',
        ]);
        
        $this->article->incrementViewCount();
    }

    public function markAsHelpful()
    {
        $this->article->feedback()->create([
            'user_id' => Auth::id(),
            'is_helpful' => true,
        ]);
        
        $this->article->increment('helpful_count');
        $this->article->refresh();
    }

    public function markAsNotHelpful()
    {
        $this->article->feedback()->create([
            'user_id' => Auth::id(),
            'is_helpful' => false,
        ]);
        
        $this->article->increment('not_helpful_count');
        $this->article->refresh();
    }

    public function togglePublish()
    {
        if ($this->article->status === KbArticle::STATUS_PUBLISHED) {
            $this->article->update(['status' => KbArticle::STATUS_DRAFT]);
        } else {
            $this->article->update([
                'status' => KbArticle::STATUS_PUBLISHED,
                'published_at' => now(),
            ]);
        }
        
        $this->article->refresh();
    }

    public function deleteArticle()
    {
        $this->article->delete();
        
        return redirect()->route('knowledge.articles.index');
    }

    public function render()
    {
        return view('livewire.knowledge.kb-article-show');
    }

    protected function getModel()
    {
        return $this->article;
    }

    protected function getAIAnalysisType(): string
    {
        return 'kb_article';
    }
}
