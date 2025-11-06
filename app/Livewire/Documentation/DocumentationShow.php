<?php

namespace App\Livewire\Documentation;

use App\Domains\Documentation\Services\DocumentationService;
use Livewire\Attributes\Computed;

/**
 * Documentation Show Component
 * 
 * Displays individual documentation pages with navigation.
 * Handles dynamic content loading and page metadata.
 */
class DocumentationShow extends BaseDocumentationComponent
{
    public string $page;
    public ?array $metadata = null;
    
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
    public function mount($page)
    {
        $this->page = $page;
        $this->metadata = $this->documentationService->getPageMetadata($page);
        
        // Show 404 if page doesn't exist
        if (!$this->metadata) {
            abort(404, 'Documentation page not found');
        }
    }
    
    /**
     * Get page title (computed property)
     */
    #[Computed]
    public function pageTitle()
    {
        return $this->metadata['title'] ?? 'Documentation';
    }
    
    /**
     * Get page description (computed property)
     */
    #[Computed]
    public function pageDescription()
    {
        return $this->metadata['description'] ?? '';
    }
    
    /**
     * Get previous page slug (computed property)
     */
    #[Computed]
    public function previousPage()
    {
        return $this->metadata['previous'] ?? null;
    }
    
    /**
     * Get next page slug (computed property)
     */
    #[Computed]
    public function nextPage()
    {
        return $this->metadata['next'] ?? null;
    }
    
    /**
     * Get previous page metadata
     */
    #[Computed]
    public function previousPageData()
    {
        if (!$this->previousPage) {
            return null;
        }
        
        return $this->documentationService->getPageMetadata($this->previousPage);
    }
    
    /**
     * Get next page metadata
     */
    #[Computed]
    public function nextPageData()
    {
        if (!$this->nextPage) {
            return null;
        }
        
        return $this->documentationService->getPageMetadata($this->nextPage);
    }
    
    /**
     * Track page view for analytics
     */
    public function trackView()
    {
        $this->documentationService->trackPageView($this->page);
    }
    
    /**
     * Render the component
     */
    public function render()
    {
        $this->trackView();
        
        return view('livewire.documentation.show')
            ->title($this->pageTitle . ' | Nestogy Documentation');
    }
}
