# Documentation Domain - Comprehensive Implementation Plan

## 📋 Overview

**Purpose**: Create a fully Livewire-idiomatic, publicly accessible documentation system for Nestogy ERP users.

**Version**: 1.0.0  
**Created**: November 2025  
**Platform**: Laravel 12.36 + Livewire 3.6.4 + Flux Pro 2.6  
**License**: Proprietary (FoleyBridge Solutions)

---

## 🎯 Goals

1. **Public Access**: No authentication required - accessible to all users, prospects, and trial accounts
2. **Modern UX**: SPA-like experience using Livewire 3 with wire:navigate
3. **Comprehensive**: Cover all major Nestogy features with step-by-step guides
4. **Searchable**: Full-text search across all documentation
5. **Professional**: Clean, minimal design using Flux Pro 2.6 components
6. **Analytics**: Track popular pages to identify documentation gaps

---

## 🏗️ Architecture Analysis

### Current Nestogy Patterns
Based on codebase analysis:

- **Livewire 3.6.4** - Primary interactive component framework
- **Flux Pro 2.6** - UI component library (Livewire-based)
- **BaseIndexComponent** - Base class for index pages
- **Minimal Controllers** - Routes → Livewire components
- **Domain-Driven Design** - Organized by business domain

### Documentation Domain Structure
```
app/Domains/Documentation/
├── Livewire/
│   ├── BaseDocumentationComponent.php       [Base component for all doc pages]
│   ├── DocumentationIndex.php               [Home page component]
│   ├── DocumentationShow.php                [Individual page component]
│   ├── DocumentationSearch.php              [Search component]
│   └── DocumentationNavigation.php          [Sidebar navigation component]
├── Services/
│   ├── DocumentationService.php             [Content management]
│   └── DocumentationSearchService.php       [Search functionality]
├── Models/
│   ├── DocumentationPage.php                [Optional: DB-driven content]
│   └── DocumentationView.php                [Analytics tracking]
└── routes.php                                [Public routes]
```

---

## 🚀 Phase 1: Domain Infrastructure

### 1.1 Create Domain Directory Structure
```bash
mkdir -p app/Domains/Documentation/Livewire
mkdir -p app/Domains/Documentation/Services
mkdir -p app/Domains/Documentation/Models
mkdir -p resources/views/documentation
mkdir -p resources/views/livewire/documentation
mkdir -p resources/views/livewire/documentation/partials
mkdir -p resources/views/layouts
```

### 1.2 Base Documentation Component
**File**: `app/Domains/Documentation/Livewire/BaseDocumentationComponent.php`

```php
<?php

namespace App\Domains\Documentation\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.documentation')]
abstract class BaseDocumentationComponent extends Component
{
    public $searchQuery = '';
    public $activeSection = '';
    
    // No authentication required - public component
    // No company_id filtering needed
    
    protected function getPageTitle(): string
    {
        return 'Documentation';
    }
    
    protected function getPageDescription(): string
    {
        return 'Nestogy ERP User Documentation';
    }
}
```

### 1.3 Documentation Service
**File**: `app/Domains/Documentation/Services/DocumentationService.php`

```php
<?php

namespace App\Domains\Documentation\Services;

class DocumentationService
{
    protected array $pages = [
        'getting-started' => [
            'title' => 'Getting Started',
            'description' => 'Learn the basics of Nestogy ERP',
            'category' => 'Basics',
            'icon' => 'rocket',
            'order' => 1,
        ],
        'dashboard' => [
            'title' => 'Dashboard & Navigation',
            'description' => 'Understanding the Nestogy interface',
            'category' => 'Basics',
            'icon' => 'home',
            'order' => 2,
        ],
        'clients' => [
            'title' => 'Client Management',
            'description' => 'Managing clients, contacts, and locations',
            'category' => 'Core Features',
            'icon' => 'users',
            'order' => 3,
        ],
        'tickets' => [
            'title' => 'Ticket System',
            'description' => 'Creating and managing support tickets',
            'category' => 'Core Features',
            'icon' => 'ticket',
            'order' => 4,
        ],
        'invoices' => [
            'title' => 'Invoice & Billing',
            'description' => 'Creating invoices and processing payments',
            'category' => 'Core Features',
            'icon' => 'currency-dollar',
            'order' => 5,
        ],
        'contracts' => [
            'title' => 'Contract Management',
            'description' => 'Managing service agreements and contracts',
            'category' => 'Features',
            'icon' => 'document-text',
            'order' => 6,
        ],
        'assets' => [
            'title' => 'Asset Management',
            'description' => 'Tracking equipment and inventory',
            'category' => 'Features',
            'icon' => 'server',
            'order' => 7,
        ],
        'projects' => [
            'title' => 'Project Management',
            'description' => 'Managing projects and tasks',
            'category' => 'Features',
            'icon' => 'clipboard-list',
            'order' => 8,
        ],
        'email' => [
            'title' => 'Email System',
            'description' => 'Managing email accounts and messages',
            'category' => 'Features',
            'icon' => 'mail',
            'order' => 9,
        ],
        'time-tracking' => [
            'title' => 'Time Tracking',
            'description' => 'Tracking time and managing timesheets',
            'category' => 'Features',
            'icon' => 'clock',
            'order' => 10,
        ],
        'reports' => [
            'title' => 'Reports & Analytics',
            'description' => 'Generating reports and analyzing data',
            'category' => 'Features',
            'icon' => 'chart-bar',
            'order' => 11,
        ],
        'client-portal' => [
            'title' => 'Client Portal',
            'description' => 'Setting up and managing the client portal',
            'category' => 'Advanced',
            'icon' => 'user-group',
            'order' => 12,
        ],
        'settings' => [
            'title' => 'Settings & Preferences',
            'description' => 'Configuring your Nestogy account',
            'category' => 'Advanced',
            'icon' => 'cog',
            'order' => 13,
        ],
        'faq' => [
            'title' => 'FAQ',
            'description' => 'Frequently asked questions',
            'category' => 'Help',
            'icon' => 'question-mark-circle',
            'order' => 14,
        ],
    ];
    
    protected array $navigation = [
        'Basics' => ['getting-started', 'dashboard'],
        'Core Features' => ['clients', 'tickets', 'invoices'],
        'Features' => ['contracts', 'assets', 'projects', 'email', 'time-tracking', 'reports'],
        'Advanced' => ['client-portal', 'settings'],
        'Help' => ['faq'],
    ];
    
    public function getAllPages(): array
    {
        return collect($this->pages)
            ->sortBy('order')
            ->all();
    }
    
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
    
    public function getPageTitle(string $slug): string
    {
        return $this->pages[$slug]['title'] ?? 'Documentation';
    }
    
    public function getNavigation(): array
    {
        return $this->navigation;
    }
    
    protected function getPreviousPage(string $slug): ?string
    {
        $pages = array_keys($this->pages);
        $currentIndex = array_search($slug, $pages);
        
        return $currentIndex > 0 ? $pages[$currentIndex - 1] : null;
    }
    
    protected function getNextPage(string $slug): ?string
    {
        $pages = array_keys($this->pages);
        $currentIndex = array_search($slug, $pages);
        
        return $currentIndex < count($pages) - 1 ? $pages[$currentIndex + 1] : null;
    }
    
    public function trackPageView(string $slug): void
    {
        // TODO: Implement analytics tracking
        // Could store in DocumentationView model
    }
}
```

### 1.4 Routes Configuration
**File**: `app/Domains/Documentation/routes.php`

```php
<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Documentation Domain Routes
|--------------------------------------------------------------------------
|
| Public documentation routes - NO authentication required.
| Accessible to all users, prospects, and trial accounts.
|
*/

Route::middleware('web')->prefix('docs')->name('docs.')->group(function () {
    // Documentation home page
    Route::get('/', function () {
        return view('documentation.index-livewire');
    })->name('index');
    
    // Individual documentation pages
    Route::get('/{page}', function ($page) {
        return view('documentation.show-livewire', ['page' => $page]);
    })->name('show');
});
```

---

## 🎨 Phase 2: Livewire Components

### 2.1 Documentation Index Component
**File**: `app/Domains/Documentation/Livewire/DocumentationIndex.php`

```php
<?php

namespace App\Domains\Documentation\Livewire;

use App\Domains\Documentation\Services\DocumentationService;
use Livewire\Attributes\Title;

#[Title('Nestogy Documentation - User Guide & Help Center')]
class DocumentationIndex extends BaseDocumentationComponent
{
    public array $popularPages = [];
    public array $categories = [];
    
    protected DocumentationService $documentationService;
    
    public function boot(DocumentationService $documentationService)
    {
        $this->documentationService = $documentationService;
    }
    
    public function mount()
    {
        $this->loadPopularPages();
        $this->loadCategories();
    }
    
    protected function loadPopularPages(): void
    {
        // Top 4 most commonly accessed pages
        $this->popularPages = [
            'getting-started',
            'clients',
            'tickets',
            'invoices',
        ];
    }
    
    protected function loadCategories(): void
    {
        $navigation = $this->documentationService->getNavigation();
        $allPages = $this->documentationService->getAllPages();
        
        foreach ($navigation as $category => $slugs) {
            $this->categories[$category] = array_map(
                fn($slug) => array_merge(['slug' => $slug], $allPages[$slug]),
                $slugs
            );
        }
    }
    
    public function render()
    {
        return view('livewire.documentation.index');
    }
}
```

### 2.2 Documentation Show Component
**File**: `app/Domains/Documentation/Livewire/DocumentationShow.php`

```php
<?php

namespace App\Domains\Documentation\Livewire;

use App\Domains\Documentation\Services\DocumentationService;
use Livewire\Attributes\Computed;

class DocumentationShow extends BaseDocumentationComponent
{
    public string $page;
    public ?array $metadata = null;
    
    protected DocumentationService $documentationService;
    
    public function boot(DocumentationService $documentationService)
    {
        $this->documentationService = $documentationService;
    }
    
    public function mount($page)
    {
        $this->page = $page;
        $this->metadata = $this->documentationService->getPageMetadata($page);
        
        if (!$this->metadata) {
            abort(404, 'Documentation page not found');
        }
    }
    
    #[Computed]
    public function pageTitle()
    {
        return $this->metadata['title'] ?? 'Documentation';
    }
    
    #[Computed]
    public function previousPage()
    {
        return $this->metadata['previous'] ?? null;
    }
    
    #[Computed]
    public function nextPage()
    {
        return $this->metadata['next'] ?? null;
    }
    
    public function trackView()
    {
        $this->documentationService->trackPageView($this->page);
    }
    
    public function render()
    {
        $this->trackView();
        
        return view('livewire.documentation.show')
            ->title($this->pageTitle . ' | Nestogy Documentation');
    }
}
```

### 2.3 Documentation Search Component
**File**: `app/Domains/Documentation/Livewire/DocumentationSearch.php`

```php
<?php

namespace App\Domains\Documentation\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;

class DocumentationSearch extends Component
{
    public string $query = '';
    public array $results = [];
    public bool $showResults = false;
    
    public function updatedQuery()
    {
        if (strlen($this->query) < 2) {
            $this->results = [];
            $this->showResults = false;
            return;
        }
        
        // TODO: Implement actual search
        $this->results = $this->performSearch();
        $this->showResults = true;
    }
    
    protected function performSearch(): array
    {
        // Placeholder - will be implemented with DocumentationSearchService
        return [];
    }
    
    #[On('close-search')]
    public function closeSearch()
    {
        $this->showResults = false;
        $this->query = '';
    }
    
    public function render()
    {
        return view('livewire.documentation.search');
    }
}
```

### 2.4 Documentation Navigation Component
**File**: `app/Domains/Documentation/Livewire/DocumentationNavigation.php`

```php
<?php

namespace App\Domains\Documentation\Livewire;

use App\Domains\Documentation\Services\DocumentationService;
use Livewire\Component;

class DocumentationNavigation extends Component
{
    public ?string $currentPage = null;
    public array $navigation = [];
    
    protected DocumentationService $documentationService;
    
    public function boot(DocumentationService $documentationService)
    {
        $this->documentationService = $documentationService;
    }
    
    public function mount(?string $currentPage = null)
    {
        $this->currentPage = $currentPage;
        $this->loadNavigation();
    }
    
    protected function loadNavigation(): void
    {
        $nav = $this->documentationService->getNavigation();
        $allPages = $this->documentationService->getAllPages();
        
        foreach ($nav as $category => $slugs) {
            $this->navigation[$category] = array_map(
                fn($slug) => array_merge(['slug' => $slug], $allPages[$slug]),
                $slugs
            );
        }
    }
    
    public function render()
    {
        return view('livewire.documentation.navigation');
    }
}
```

---

## 🖼️ Phase 3: View Templates

### 3.1 Documentation Layout
**File**: `resources/views/layouts/documentation.blade.php`

```blade
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ $title ?? 'Nestogy Documentation' }}</title>
    <meta name="description" content="Comprehensive user documentation for Nestogy ERP - MSP management platform">
    
    {{-- SEO --}}
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">
    
    {{-- Open Graph --}}
    <meta property="og:title" content="{{ $title ?? 'Nestogy Documentation' }}">
    <meta property="og:description" content="Comprehensive user documentation for Nestogy ERP">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    
    {{-- Favicon --}}
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    
    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    {{-- Scripts --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @fluxAppearance
</head>
<body class="h-full font-sans antialiased bg-white dark:bg-zinc-950">
    
    {{-- Header --}}
    <header class="sticky top-0 z-50 border-b border-zinc-200 dark:border-zinc-800 bg-white/95 dark:bg-zinc-900/95 backdrop-blur supports-[backdrop-filter]:bg-white/60 dark:supports-[backdrop-filter]:bg-zinc-900/60">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            {{-- Logo --}}
            <div class="flex items-center gap-4">
                <a href="{{ route('docs.index') }}" wire:navigate class="flex items-center gap-2 text-xl font-bold text-zinc-900 dark:text-white">
                    <flux:icon.book-open class="size-6" />
                    <span>Nestogy Docs</span>
                </a>
            </div>
            
            {{-- Search --}}
            <div class="flex-1 max-w-md mx-4">
                <livewire:documentation.documentation-search />
            </div>
            
            {{-- Auth Links --}}
            <div class="flex items-center gap-4">
                @auth
                    <a href="{{ route('dashboard') }}" wire:navigate>
                        <flux:button variant="ghost" size="sm">
                            <flux:icon.arrow-left class="size-4" />
                            Back to App
                        </flux:button>
                    </a>
                @else
                    <a href="{{ route('login') }}">
                        <flux:button variant="primary" size="sm">
                            Login
                        </flux:button>
                    </a>
                @endauth
            </div>
        </div>
    </header>
    
    {{-- Main Content --}}
    <div class="flex">
        {{-- Sidebar Navigation --}}
        <aside class="hidden lg:block w-64 border-r border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 fixed h-[calc(100vh-4rem)] overflow-y-auto">
            <livewire:documentation.documentation-navigation :current-page="$page ?? null" />
        </aside>
        
        {{-- Content --}}
        <main class="flex-1 lg:ml-64">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-12">
                @yield('content')
            </div>
        </main>
    </div>
    
    @livewireScripts
</body>
</html>
```

### 3.2 Index Page Wrapper
**File**: `resources/views/documentation/index-livewire.blade.php`

```blade
@extends('layouts.documentation')

@section('content')
    <livewire:documentation.documentation-index />
@endsection
```

### 3.3 Show Page Wrapper
**File**: `resources/views/documentation/show-livewire.blade.php`

```blade
@extends('layouts.documentation')

@section('content')
    <livewire:documentation.documentation-show :page="$page" />
@endsection
```

---

## 📄 Phase 4: Content Pages

### Content Structure
Each documentation page will be created as:
**File**: `resources/views/livewire/documentation/pages/{slug}.blade.php`

Template structure:
```blade
{{-- Page Header --}}
<div class="mb-8">
    <flux:heading size="xl">{{ $title }}</flux:heading>
    <flux:subheading class="mt-2">{{ $description }}</flux:subheading>
</div>

{{-- Introduction --}}
<flux:separator class="my-8" />

<div class="prose dark:prose-invert max-w-none">
    {{-- Content goes here --}}
</div>

{{-- Navigation --}}
<div class="mt-12 pt-8 border-t border-zinc-200 dark:border-zinc-800">
    <div class="flex justify-between items-center">
        @if($this->previousPage)
            <a href="{{ route('docs.show', $this->previousPage) }}" wire:navigate>
                <flux:button variant="ghost">
                    <flux:icon.arrow-left class="size-4" />
                    Previous
                </flux:button>
            </a>
        @else
            <div></div>
        @endif
        
        @if($this->nextPage)
            <a href="{{ route('docs.show', $this->nextPage) }}" wire:navigate>
                <flux:button variant="ghost">
                    Next
                    <flux:icon.arrow-right class="size-4" />
                </flux:button>
            </a>
        @endif
    </div>
</div>
```

---

## 🚀 Implementation Schedule

### Week 1: Infrastructure (Nov 6-12)
- [x] Create domain structure
- [ ] Implement base components
- [ ] Create services
- [ ] Set up routes
- [ ] Create documentation layout

### Week 2: Core Components (Nov 13-19)
- [ ] Build DocumentationIndex component
- [ ] Build DocumentationShow component
- [ ] Build DocumentationSearch component
- [ ] Build DocumentationNavigation component
- [ ] Test all components

### Week 3: Content - High Priority (Nov 20-26)
- [ ] Getting Started guide
- [ ] Dashboard & Navigation
- [ ] Client Management
- [ ] Ticket System
- [ ] Invoice & Billing

### Week 4: Content - Medium Priority (Nov 27-Dec 3)
- [ ] Contract Management
- [ ] Asset Management
- [ ] Project Management
- [ ] Email System
- [ ] Time Tracking
- [ ] Reports & Analytics

### Week 5: Polish & Launch (Dec 4-10)
- [ ] Client Portal guide
- [ ] Settings guide
- [ ] FAQ page
- [ ] Search implementation
- [ ] Analytics tracking
- [ ] Final testing
- [ ] Launch

---

## ✅ Success Criteria

### Technical
- [ ] All routes publicly accessible (no auth)
- [ ] SPA navigation with wire:navigate working
- [ ] Search functionality operational
- [ ] Mobile responsive
- [ ] Page load < 2 seconds
- [ ] SEO optimized (meta tags, sitemap)

### Content
- [ ] All 14 documentation pages complete
- [ ] Screenshots added to key sections
- [ ] Step-by-step instructions clear
- [ ] Common issues documented
- [ ] FAQ comprehensive

### User Experience
- [ ] Easy navigation between pages
- [ ] Search finds relevant content
- [ ] Print-friendly layout
- [ ] Dark mode support
- [ ] Keyboard shortcuts work

---

## 📊 Analytics & Tracking

### Metrics to Track
1. **Page Views**: Most/least viewed pages
2. **Search Queries**: What users search for
3. **User Paths**: Common navigation patterns
4. **Time on Page**: Engagement metrics
5. **Bounce Rate**: Content effectiveness

### Implementation
```php
// DocumentationView Model
Schema::create('documentation_views', function (Blueprint $table) {
    $table->id();
    $table->string('page_slug');
    $table->string('referrer')->nullable();
    $table->string('ip_address')->nullable();
    $table->string('user_agent')->nullable();
    $table->timestamps();
});
```

---

## 🔧 Maintenance

### Content Updates
- Review documentation monthly
- Update screenshots when UI changes
- Add new pages for new features
- Archive outdated content

### Performance
- Monitor page load times
- Optimize images
- Cache rendered content
- CDN for static assets

### SEO
- Submit sitemap to search engines
- Monitor search rankings
- Update meta descriptions
- Add structured data

---

## 📝 Notes

### Design Decisions
1. **Livewire-First**: Matches existing Nestogy architecture
2. **Public Access**: No auth barrier for documentation
3. **Flux Components**: Consistent with main app UI
4. **SPA Navigation**: wire:navigate for instant transitions
5. **File-Based Content**: Blade templates for easy editing

### Future Enhancements
- [ ] Video tutorials
- [ ] Interactive demos
- [ ] API documentation
- [ ] Changelog page
- [ ] Community forum integration
- [ ] Multi-language support

---

**Document Version**: 1.0.0  
**Last Updated**: November 6, 2025  
**Maintained By**: FoleyBridge Solutions Development Team
