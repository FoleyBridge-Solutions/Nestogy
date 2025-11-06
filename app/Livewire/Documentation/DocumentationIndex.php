<?php

namespace App\Livewire\Documentation;

use App\Domains\Documentation\Services\DocumentationService;
use Livewire\Attributes\Title;

/**
 * Documentation Index Component
 * 
 * Home page for the documentation system.
 * Shows popular pages, categories, and quick links.
 */
#[Title('Nestogy Documentation - User Guide & Help Center')]
class DocumentationIndex extends BaseDocumentationComponent
{
    public array $popularPages = [];
    public array $categories = [];
    
    protected DocumentationService $documentationService;
    
    /**
     * Boot method - inject DocumentationService
     */
    public function boot(DocumentationService $documentationService)
    {
        $this->documentationService = $documentationService;
    }
    
    /**
     * Component initialization
     */
    public function mount()
    {
        $this->loadPopularPages();
        $this->loadCategories();
    }
    
    /**
     * Load most popular/important pages
     */
    protected function loadPopularPages(): void
    {
        $allPages = $this->documentationService->getAllPages();
        
        // Top 4 most commonly accessed pages
        $popularSlugs = ['getting-started', 'clients', 'tickets', 'invoices'];
        
        $this->popularPages = collect($popularSlugs)
            ->map(fn($slug) => array_merge(['slug' => $slug], $allPages[$slug]))
            ->all();
    }
    
    /**
     * Load all categories with their pages
     */
    protected function loadCategories(): void
    {
        $this->categories = $this->documentationService->getPagesByCategory();
    }
    
    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.documentation.index');
    }
}
