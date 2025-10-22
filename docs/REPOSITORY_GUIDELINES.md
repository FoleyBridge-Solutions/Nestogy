# Repository Guidelines

## Project Structure & Module Organization

Laravel 12 with domain-driven modules in `app/Domains/*` (Asset, Financial, PhysicalMail, etc.), shared services in `app/Services`, Livewire in `app/Livewire`. Front-end in `resources/{js,css,views}`, compiled to `public/`. Database files in `database/`, ops scripts in `scripts/`.

## Build, Test, and Development Commands

- `composer install && npm install` – install dependencies
- `composer dev` – run PHP server, queue worker, logs, Vite dev
- `php artisan migrate --seed` – run migrations and seeds
- `npm run build` – build production assets
- `composer test` or `php artisan test` – run PHPUnit

## Coding Style & Naming Conventions

PSR-12, 4-space indent, run `./vendor/bin/pint` pre-commit. Livewire: StudlyCase classes (`App\Livewire\<Context>`), kebab-case blades. Use event-oriented names (e.g., `syncMailboxes`), constructor injection over facades. Tailwind utilities per `tailwind.config.js`, no inline styles.

## Testing Guidelines

Tests in `tests/`: Feature (domain), Unit (logic), Performance (regression). Name classes `<Thing>Test` matching subject namespace. Use `php artisan test --filter=Financial`, `tests/run-financial-accuracy-tests.php` for finance. Prefer factories.

**Minimum 75% coverage required. Use pvoc. Run pvoc before PRs.**

### Test Execution Best Practices

**CRITICAL: Redirect output to files:**

```bash
php vendor/bin/phpunit > test-output.txt 2>&1
php artisan test > artisan-test.txt 2>&1
```

Benefits:
- Review errors/stack traces
- Analyze failure patterns
- Compare before/after
- Share with team
- Debug intermittent failures

## Planning Guidelines

When asked to plan changes, provide a concise, actionable list without fluff:

- **Files to create:** List new files with brief description
- **Files to modify:** List existing files to change
- **Common code changes:** What's moving/being extracted
- **Domain-specific preservation:** What stays unique per component

**NO** risk mitigation, benefits, phases, or marketing copy. Just the technical changes needed.

## Commit & Pull Request Guidelines

Imperative commits (e.g., "Add company sizes to seeder"), <72 char subjects, contextual bullets. Reference affected domain. PRs: link issues, note schema/seed changes, include UI screenshots, list post-deploy jobs/config.

## Security & Configuration Tips

Never commit `.env` or credentials. Use `php artisan key:generate` post-clone, vault secrets. Config overrides in `.env` match `config/*.php` keys. Validate uploads, store in `storage/app`, verify S3 credentials before enabling.

---

## Standardized Page Headers

Use header system in `resources/views/layouts/app.blade.php`. Pass variables to layout, **don't create custom headers**.

### Required Variables

- `$pageTitle` – heading (string)

### Optional Variables

- `$pageSubtitle` – description (string)
- `$pageActions` – button array: `[['label' => '', 'href' => '', 'icon' => '', 'variant' => '']]`

### Example Usage

```blade
@extends('layouts.app')
@section('title', 'Page Title')

@php
$pageTitle = 'Product Name';
$pageSubtitle = 'SKU: ABC123 • Category: Electronics';
$pageActions = [
    ['label' => 'Edit', 'href' => route('products.edit', $product), 'icon' => 'pencil', 'variant' => 'ghost']
];
@endphp

@section('content')
    @livewire('products.product-index')
@endsection
```

**Don't wrap `@section('content')` in spacing divs—layout handles spacing.**

### Action Button Properties

- `label` (required) – text
- `href` (required) – route/URL
- `icon` (optional) – FluxUI name (no `flux:icon.` prefix)
- `variant` (optional) – button style (default: 'ghost')

**Use standardized headers only, no custom headers.**

---

## Creating Index Pages (Tables)

**All index pages use `BaseIndexComponent` for consistency. DO NOT create custom index implementations.**

### 1. Create Livewire Component

Extend `BaseIndexComponent` and implement required abstract methods:

```php
<?php

namespace App\Livewire\Financial;

use App\Livewire\BaseIndexComponent;
use App\Domains\Financial\Models\Quote;
use Illuminate\Database\Eloquent\Builder;

class QuoteIndex extends BaseIndexComponent
{
    // Custom filters (optional)
    public $statusFilter = '';
    public $dateFrom = '';
    public $dateTo = '';

    protected function getDefaultSort(): array
    {
        return ['field' => 'created_at', 'direction' => 'desc'];
    }

    protected function getSearchFields(): array
    {
        return ['quote_number', 'title', 'notes'];
    }

    protected function getColumns(): array
    {
        return [
            'quote_number' => [
                'label' => 'Quote #',
                'sortable' => true,
                'filterable' => false,
            ],
            'client.name' => [
                'label' => 'Client',
                'sortable' => true,
                'filterable' => false,
            ],
            'status' => [
                'label' => 'Status',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => Quote::getStatuses(),
            ],
            'total' => [
                'label' => 'Total',
                'sortable' => true,
                'type' => 'currency',
            ],
            'created_at' => [
                'label' => 'Created',
                'sortable' => true,
                'type' => 'date',
            ],
        ];
    }

    protected function getStats(): array
    {
        $baseQuery = Quote::where('company_id', $this->companyId);
        
        return [
            ['label' => 'Total Quotes', 'value' => $baseQuery->count(), 'icon' => 'document-text', 'iconBg' => 'bg-blue-500'],
            ['label' => 'Pending', 'value' => $baseQuery->clone()->where('status', 'pending')->count(), 'icon' => 'clock', 'iconBg' => 'bg-yellow-500'],
            ['label' => 'Approved', 'value' => $baseQuery->clone()->where('status', 'approved')->count(), 'icon' => 'check-circle', 'iconBg' => 'bg-green-500'],
        ];
    }

    protected function getEmptyState(): array
    {
        return [
            'title' => 'No Quotes',
            'message' => 'Create your first quote to get started.',
            'icon' => 'document-text',
            'action' => [
                'label' => 'Create Quote',
                'href' => route('financial.quotes.create'),
            ],
        ];
    }

    protected function getBaseQuery(): Builder
    {
        return Quote::where('company_id', $this->companyId)
            ->with(['client', 'category']);
    }

    protected function applyCustomFilters($query)
    {
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }
        
        if ($this->dateFrom) {
            $query->where('created_at', '>=', $this->dateFrom);
        }
        
        if ($this->dateTo) {
            $query->where('created_at', '<=', $this->dateTo);
        }
        
        return $query;
    }

    protected function getRowActions($item)
    {
        return [
            ['label' => 'View', 'href' => route('financial.quotes.show', $item->id), 'icon' => 'eye'],
            ['label' => 'Edit', 'href' => route('financial.quotes.edit', $item->id), 'icon' => 'pencil'],
            ['label' => 'Delete', 'wire:click' => 'deleteItem('.$item->id.')', 'icon' => 'trash', 'variant' => 'danger'],
        ];
    }

    protected function getBulkActions()
    {
        return [
            ['label' => 'Delete Selected', 'method' => 'bulkDelete', 'variant' => 'danger', 'confirm' => 'Are you sure?'],
        ];
    }

    protected function getQueryStringProperties(): array
    {
        return [
            'search' => ['except' => ''],
            'statusFilter' => ['except' => ''],
            'dateFrom' => ['except' => ''],
            'dateTo' => ['except' => ''],
            'sortField' => ['except' => 'created_at'],
            'sortDirection' => ['except' => 'desc'],
        ];
    }
}
```

### 2. Inherited Features (Automatic)

From `BaseIndexComponent` you automatically get:

- **Search** (via `$search` property)
- **Sorting** (via `$sortField`, `$sortDirection`)
- **Pagination** (via `$perPage`)
- **Bulk actions** (via `$selected[]`, `$selectAll`)
- **Company scoping** (automatic)
- **Archive filtering** (automatic if `archived_at` column exists)
- **Client context** (automatic from NavigationService)

### 3. NO Custom Blade View Needed

**DO NOT create a custom blade view** for your Livewire component. The component automatically uses `livewire.base-index.blade.php` which provides:

- Stats cards display
- Filter controls
- Table/Cards view toggle
- Empty states
- Pagination

### 4. Update Controller

```php
public function index(Request $request)
{
    if ($request->wantsJson()) {
        // API logic here
        return response()->json([
            'quotes' => Quote::where('company_id', auth()->user()->company_id)->paginate()
        ]);
    }
    
    return view('financial.quotes.index-livewire');
}
```

### 5. Create Wrapper View

Create `resources/views/financial/quotes/index-livewire.blade.php`:

```blade
@extends('layouts.app')

@section('title', 'Quotes')

@php
$pageTitle = 'Quotes';
$pageSubtitle = 'Manage sales quotes and proposals';
$pageActions = [
    ['label' => 'Create Quote', 'href' => route('financial.quotes.create'), 'icon' => 'plus', 'variant' => 'primary']
];
@endphp

@section('content')
    <div class="container-fluid">
        @livewire('financial.quote-index')
    </div>
@endsection
```

### Variable Names (CRITICAL)

**ALWAYS use these exact variable names from traits:**

- `$sortField` (NOT `$sortBy`)
- `$sortDirection` (NOT `$sortOrder`, `$sortDir`)
- `$search`
- `$perPage`
- `$selected` (for bulk actions)

### DO NOT

- ❌ Create custom pagination logic
- ❌ Implement sorting manually
- ❌ Add `company_id` filters manually (automatic via `BaseIndexComponent`)
- ❌ Create custom search implementations
- ❌ Use different variable names than the traits provide
- ❌ Create custom blade views for the Livewire component (use `base-index.blade.php`)
- ❌ Override the `render()` method unless absolutely necessary

---

**Version**: 1.0.0 | **Last Updated**: October 2024 | **Platform**: Laravel 12
