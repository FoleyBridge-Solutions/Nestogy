<?php

namespace App\Livewire\Documentation;

use Livewire\Component;
use Livewire\Attributes\Layout;

/**
 * Base Documentation Component
 * 
 * Base class for all documentation Livewire components.
 * Uses the documentation layout and provides common functionality.
 * 
 * NO authentication required - all documentation is publicly accessible.
 */
#[Layout('layouts.documentation')]
abstract class BaseDocumentationComponent extends Component
{
    public string $searchQuery = '';
    public string $activeSection = '';
    
    /**
     * Get the page title for SEO and browser title
     */
    protected function getPageTitle(): string
    {
        return 'Documentation';
    }
    
    /**
     * Get the page description for SEO
     */
    protected function getPageDescription(): string
    {
        return 'Nestogy ERP User Documentation - Learn how to use Nestogy for managing your MSP business';
    }
}
