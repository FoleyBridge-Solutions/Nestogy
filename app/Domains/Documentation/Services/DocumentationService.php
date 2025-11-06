<?php

namespace App\Domains\Documentation\Services;

/**
 * Documentation Service
 * 
 * Manages documentation pages, navigation structure, and metadata.
 * Provides content organization and navigation helpers.
 */
class DocumentationService
{
    /**
     * All documentation pages with metadata
     */
    protected array $pages = [
        'getting-started' => [
            'title' => 'Getting Started',
            'description' => 'Learn the basics of Nestogy ERP and get up to speed quickly',
            'category' => 'Basics',
            'icon' => 'rocket-launch',
            'order' => 1,
        ],
        'dashboard' => [
            'title' => 'Dashboard & Navigation',
            'description' => 'Understanding the Nestogy interface, navigation, and command palette',
            'category' => 'Basics',
            'icon' => 'home',
            'order' => 2,
        ],
        'clients' => [
            'title' => 'Client Management',
            'description' => 'Managing clients, contacts, locations, and communication logs',
            'category' => 'Core Features',
            'icon' => 'users',
            'order' => 3,
        ],
        'tickets' => [
            'title' => 'Ticket System',
            'description' => 'Creating and managing support tickets, time tracking, and SLAs',
            'category' => 'Core Features',
            'icon' => 'ticket',
            'order' => 4,
        ],
        'invoices' => [
            'title' => 'Invoice & Billing',
            'description' => 'Creating invoices, processing payments, and managing recurring billing',
            'category' => 'Core Features',
            'icon' => 'currency-dollar',
            'order' => 5,
        ],
        'contracts' => [
            'title' => 'Contract Management',
            'description' => 'Managing service agreements, SLAs, and contract lifecycle',
            'category' => 'Features',
            'icon' => 'document-text',
            'order' => 6,
        ],
        'assets' => [
            'title' => 'Asset Management',
            'description' => 'Tracking equipment, inventory, warranties, and RMM integration',
            'category' => 'Features',
            'icon' => 'server',
            'order' => 7,
        ],
        'projects' => [
            'title' => 'Project Management',
            'description' => 'Managing projects, tasks, milestones, and resource allocation',
            'category' => 'Features',
            'icon' => 'clipboard-document-list',
            'order' => 8,
        ],
        'email' => [
            'title' => 'Email System',
            'description' => 'Managing email accounts, inbox, and email-to-ticket conversion',
            'category' => 'Features',
            'icon' => 'envelope',
            'order' => 9,
        ],
        'time-tracking' => [
            'title' => 'Time Tracking',
            'description' => 'Tracking time entries, breaks, timesheets, and overtime',
            'category' => 'Features',
            'icon' => 'clock',
            'order' => 10,
        ],
        'reports' => [
            'title' => 'Reports & Analytics',
            'description' => 'Generating reports, analyzing data, and custom dashboards',
            'category' => 'Features',
            'icon' => 'chart-bar',
            'order' => 11,
        ],
        'client-portal' => [
            'title' => 'Client Portal',
            'description' => 'Setting up and managing the client portal for your customers',
            'category' => 'Advanced',
            'icon' => 'user-group',
            'order' => 12,
        ],
        'settings' => [
            'title' => 'Settings & Preferences',
            'description' => 'Configuring your account, notifications, and preferences',
            'category' => 'Advanced',
            'icon' => 'cog-6-tooth',
            'order' => 13,
        ],
        'faq' => [
            'title' => 'Frequently Asked Questions',
            'description' => 'Common questions and answers about using Nestogy',
            'category' => 'Help',
            'icon' => 'question-mark-circle',
            'order' => 14,
        ],
    ];
    
    /**
     * Navigation structure (categories and their pages)
     */
    protected array $navigation = [
        'Basics' => ['getting-started', 'dashboard'],
        'Core Features' => ['clients', 'tickets', 'invoices'],
        'Features' => ['contracts', 'assets', 'projects', 'email', 'time-tracking', 'reports'],
        'Advanced' => ['client-portal', 'settings'],
        'Help' => ['faq'],
    ];
    
    /**
     * Get all pages sorted by order
     */
    public function getAllPages(): array
    {
        return collect($this->pages)
            ->sortBy('order')
            ->all();
    }
    
    /**
     * Get metadata for a specific page
     */
    public function getPageMetadata(string $slug): ?array
    {
        if (!isset($this->pages[$slug])) {
            return null;
        }
        
        $metadata = $this->pages[$slug];
        $metadata['slug'] = $slug;
        $metadata['previous'] = $this->getPreviousPage($slug);
        $metadata['next'] = $this->getNextPage($slug);
        
        return $metadata;
    }
    
    /**
     * Get page title by slug
     */
    public function getPageTitle(string $slug): string
    {
        return $this->pages[$slug]['title'] ?? 'Documentation';
    }
    
    /**
     * Get navigation structure
     */
    public function getNavigation(): array
    {
        return $this->navigation;
    }
    
    /**
     * Get pages by category
     */
    public function getPagesByCategory(): array
    {
        $result = [];
        $allPages = $this->getAllPages();
        
        foreach ($this->navigation as $category => $slugs) {
            $result[$category] = array_map(
                fn($slug) => array_merge(['slug' => $slug], $allPages[$slug]),
                $slugs
            );
        }
        
        return $result;
    }
    
    /**
     * Get previous page slug
     */
    protected function getPreviousPage(string $slug): ?string
    {
        $pages = array_keys($this->pages);
        $currentIndex = array_search($slug, $pages);
        
        return $currentIndex > 0 ? $pages[$currentIndex - 1] : null;
    }
    
    /**
     * Get next page slug
     */
    protected function getNextPage(string $slug): ?string
    {
        $pages = array_keys($this->pages);
        $currentIndex = array_search($slug, $pages);
        
        return $currentIndex < count($pages) - 1 ? $pages[$currentIndex + 1] : null;
    }
    
    /**
     * Track page view for analytics
     */
    public function trackPageView(string $slug): void
    {
        // TODO: Implement analytics tracking
        // Could store in DocumentationView model or use external analytics
    }
    
    /**
     * Check if a page exists
     */
    public function pageExists(string $slug): bool
    {
        return isset($this->pages[$slug]);
    }
    
    /**
     * Get content for a page
     * Loads markdown file from storage/documentation
     */
    public function getPageContent(string $slug): ?string
    {
        $path = storage_path("documentation/{$slug}.md");
        
        if (!file_exists($path)) {
            return null;
        }
        
        return file_get_contents($path);
    }
}
