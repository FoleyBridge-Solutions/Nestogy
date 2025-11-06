<?php

namespace App\Livewire\Documentation;

use App\Domains\Documentation\Services\DocumentationService;
use Livewire\Component;

/**
 * Documentation Navigation Component
 * 
 * Sidebar navigation for documentation pages.
 * Shows categorized list of all documentation pages.
 */
class DocumentationNavigation extends Component
{
    public ?string $currentPage = null;
    public array $navigation = [];
    
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
    public function mount(?string $currentPage = null)
    {
        $this->currentPage = $currentPage;
        $this->loadNavigation();
    }
    
    /**
     * Load navigation structure
     */
    protected function loadNavigation(): void
    {
        $this->navigation = $this->documentationService->getPagesByCategory();
    }
    
    /**
     * Check if a page is currently active
     */
    public function isActive(string $slug): bool
    {
        return $this->currentPage === $slug;
    }
    
    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.documentation.navigation');
    }
}
