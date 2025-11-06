<?php

namespace App\Livewire\Documentation;

use App\Domains\Documentation\Services\DocumentationService;
use Livewire\Component;
use Livewire\Attributes\On;

/**
 * Documentation Search Component
 * 
 * Provides real-time search functionality across all documentation pages.
 * Can be embedded anywhere in the documentation layout.
 */
class DocumentationSearch extends Component
{
    public string $query = '';
    public array $results = [];
    public bool $showResults = false;
    
    protected DocumentationService $documentationService;
    
    /**
     * Boot method - inject DocumentationService
     */
    public function boot(DocumentationService $documentationService)
    {
        $this->documentationService = $documentationService;
    }
    
    /**
     * Handle query updates - perform search in real-time
     */
    public function updatedQuery()
    {
        if (strlen($this->query) < 2) {
            $this->results = [];
            $this->showResults = false;
            return;
        }
        
        $this->results = $this->performSearch();
        $this->showResults = true;
    }
    
    /**
     * Perform search across all pages
     */
    protected function performSearch(): array
    {
        $allPages = $this->documentationService->getAllPages();
        $query = strtolower($this->query);
        
        $results = collect($allPages)
            ->filter(function ($page, $slug) use ($query) {
                // Search in title and description
                $titleMatch = str_contains(strtolower($page['title']), $query);
                $descMatch = str_contains(strtolower($page['description']), $query);
                
                return $titleMatch || $descMatch;
            })
            ->map(function ($page, $slug) {
                return array_merge(['slug' => $slug], $page);
            })
            ->take(5) // Limit to top 5 results
            ->values()
            ->all();
        
        return $results;
    }
    
    /**
     * Close search results
     */
    #[On('close-search')]
    public function closeSearch()
    {
        $this->showResults = false;
        $this->query = '';
    }
    
    /**
     * Clear search
     */
    public function clear()
    {
        $this->query = '';
        $this->results = [];
        $this->showResults = false;
    }
    
    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.documentation.search');
    }
}
