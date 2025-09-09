# Flux UI Pro Migration Plan - Complete Blade Template Conversion Guide

## For Gemini AI Assistant - Complete Instructions for 797 Template Migration

### Context and Overview
This document provides comprehensive instructions for migrating the Nestogy ERP application from Bootstrap/Alpine.js to Flux UI Pro. The application contains **797 Blade templates** that need conversion.

**Current State:**
- 30 templates already partially use Flux UI
- 34 templates use Bootstrap button classes
- Many templates use Alpine.js for interactivity
- Mixed UI framework usage causing inconsistency

**Target State:**
- 100% Flux UI Pro components
- Zero Alpine.js code (all replaced with Livewire)
- Consistent modern UI across all 797 templates
- Server-side reactivity via Livewire

---

## CRITICAL REQUIREMENTS

### 1. NO ALPINE.JS
- **Remove ALL** `x-data`, `x-show`, `x-if`, `@click`, `:class` Alpine directives
- Replace with Livewire properties and methods
- Use `wire:model`, `wire:click`, `wire:loading` instead

### 2. NO BOOTSTRAP
- Remove all Bootstrap CSS classes
- Remove all `data-bs-*` attributes
- Remove Bootstrap JavaScript dependencies
- Convert to Flux UI Pro components

### 3. USE FLUX UI PRO EXCLUSIVELY
- The application has Flux UI Pro v1.1.6 installed and licensed
- Use `flux:` prefixed components only
- Reference: https://fluxui.dev/docs/components

---

## Component Conversion Mappings

### Buttons
```blade
<!-- OLD Bootstrap -->
<button class="btn btn-primary">Save</button>
<button class="btn btn-secondary">Cancel</button>
<button class="btn btn-danger">Delete</button>
<button class="btn btn-success">Approve</button>
<button class="btn btn-warning">Warning</button>
<button class="btn btn-info">Info</button>
<button class="btn btn-link">Link</button>
<button class="btn btn-outline-primary">Outline</button>
<button class="btn btn-sm btn-primary">Small</button>
<button class="btn btn-lg btn-primary">Large</button>

<!-- NEW Flux UI Pro -->
<flux:button variant="primary">Save</flux:button>
<flux:button variant="ghost">Cancel</flux:button>
<flux:button variant="danger">Delete</flux:button>
<flux:button variant="primary" color="green">Approve</flux:button>
<flux:button variant="warning">Warning</flux:button>
<flux:button variant="primary" color="blue">Info</flux:button>
<flux:button variant="ghost">Link</flux:button>
<flux:button variant="outline">Outline</flux:button>
<flux:button variant="primary" size="sm">Small</flux:button>
<flux:button variant="primary" size="lg">Large</flux:button>
```

### Forms
```blade
<!-- OLD Bootstrap -->
<div class="form-group">
    <label for="name" class="form-label">Name</label>
    <input type="text" class="form-control" id="name" name="name">
    <small class="form-text text-muted">Enter your name</small>
</div>

<select class="form-select" name="status">
    <option>Active</option>
    <option>Inactive</option>
</select>

<textarea class="form-control" rows="3"></textarea>

<div class="form-check">
    <input class="form-check-input" type="checkbox" id="agree">
    <label class="form-check-label" for="agree">I agree</label>
</div>

<!-- NEW Flux UI Pro -->
<flux:field>
    <flux:label>Name</flux:label>
    <flux:input type="text" wire:model="name" />
    <flux:description>Enter your name</flux:description>
    <flux:error name="name" />
</flux:field>

<flux:select wire:model="status">
    <flux:option value="active">Active</flux:option>
    <flux:option value="inactive">Inactive</flux:option>
</flux:select>

<flux:textarea wire:model="description" rows="3" />

<flux:checkbox wire:model="agree">
    I agree
</flux:checkbox>
```

### Cards
```blade
<!-- OLD Bootstrap -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title">Title</h5>
    </div>
    <div class="card-body">
        Content here
    </div>
    <div class="card-footer">
        Footer content
    </div>
</div>

<!-- NEW Flux UI Pro -->
<flux:card>
    <flux:card.header>
        <flux:heading size="lg">Title</flux:heading>
    </flux:card.header>
    <flux:card.body>
        Content here
    </flux:card.body>
    <flux:card.footer>
        Footer content
    </flux:card.footer>
</flux:card>
```

### Modals
```blade
<!-- OLD Bootstrap with Alpine -->
<div x-data="{ open: false }">
    <button @click="open = true" class="btn btn-primary">Open Modal</button>
    
    <div class="modal" x-show="open" x-cloak>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modal Title</h5>
                    <button @click="open = false" class="btn-close"></button>
                </div>
                <div class="modal-body">
                    Content
                </div>
                <div class="modal-footer">
                    <button @click="open = false" class="btn btn-secondary">Close</button>
                    <button class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- NEW Flux UI Pro with Livewire -->
<flux:button wire:click="$set('showModal', true)">Open Modal</flux:button>

<flux:modal wire:model="showModal" name="example-modal">
    <flux:modal.header>
        <flux:heading>Modal Title</flux:heading>
    </flux:modal.header>
    
    <flux:modal.body>
        Content
    </flux:modal.body>
    
    <flux:modal.footer>
        <flux:button variant="ghost" wire:click="$set('showModal', false)">Close</flux:button>
        <flux:button variant="primary" wire:click="save">Save</flux:button>
    </flux:modal.footer>
</flux:modal>
```

### Tables
```blade
<!-- OLD Bootstrap -->
<table class="table table-striped table-hover">
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($users as $user)
        <tr>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>
                <button class="btn btn-sm btn-primary">Edit</button>
                <button class="btn btn-sm btn-danger">Delete</button>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<!-- NEW Flux UI Pro -->
<flux:table>
    <flux:table.head>
        <flux:table.row>
            <flux:table.heading>Name</flux:table.heading>
            <flux:table.heading>Email</flux:table.heading>
            <flux:table.heading>Actions</flux:table.heading>
        </flux:table.row>
    </flux:table.head>
    <flux:table.body>
        @foreach($users as $user)
        <flux:table.row>
            <flux:table.cell>{{ $user->name }}</flux:table.cell>
            <flux:table.cell>{{ $user->email }}</flux:table.cell>
            <flux:table.cell>
                <flux:button size="sm" variant="primary">Edit</flux:button>
                <flux:button size="sm" variant="danger">Delete</flux:button>
            </flux:table.cell>
        </flux:table.row>
        @endforeach
    </flux:table.body>
</flux:table>
```

### Alerts/Toasts
```blade
<!-- OLD Bootstrap -->
<div class="alert alert-success alert-dismissible fade show">
    <strong>Success!</strong> Your changes have been saved.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>

<div class="alert alert-danger">
    <strong>Error!</strong> Something went wrong.
</div>

<div class="alert alert-warning">
    <strong>Warning!</strong> Please review your input.
</div>

<!-- NEW Flux UI Pro -->
<flux:toast variant="success" dismissible>
    <strong>Success!</strong> Your changes have been saved.
</flux:toast>

<flux:toast variant="danger">
    <strong>Error!</strong> Something went wrong.
</flux:toast>

<flux:toast variant="warning">
    <strong>Warning!</strong> Please review your input.
</flux:toast>
```

### Dropdowns
```blade
<!-- OLD Bootstrap -->
<div class="dropdown">
    <button class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
        Options
    </button>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="#">Edit</a></li>
        <li><a class="dropdown-item" href="#">Delete</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="#">Archive</a></li>
    </ul>
</div>

<!-- NEW Flux UI Pro -->
<flux:dropdown>
    <flux:button variant="ghost" icon:trailing="chevron-down">
        Options
    </flux:button>
    
    <flux:menu>
        <flux:menu.item icon="pencil" href="#">Edit</flux:menu.item>
        <flux:menu.item icon="trash" href="#">Delete</flux:menu.item>
        <flux:menu.separator />
        <flux:menu.item icon="archive-box" href="#">Archive</flux:menu.item>
    </flux:menu>
</flux:dropdown>
```

### Navigation
```blade
<!-- OLD Bootstrap -->
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Logo</a>
        <div class="navbar-nav">
            <a class="nav-link active" href="#">Home</a>
            <a class="nav-link" href="#">Features</a>
            <a class="nav-link" href="#">Pricing</a>
        </div>
    </div>
</nav>

<!-- NEW Flux UI Pro -->
<flux:header>
    <flux:brand href="#" name="Logo" />
    <flux:navbar>
        <flux:navbar.item href="#" current>Home</flux:navbar.item>
        <flux:navbar.item href="#">Features</flux:navbar.item>
        <flux:navbar.item href="#">Pricing</flux:navbar.item>
    </flux:navbar>
</flux:header>
```

### Tabs
```blade
<!-- OLD Bootstrap -->
<ul class="nav nav-tabs">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#general">General</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#security">Security</a>
    </li>
</ul>
<div class="tab-content">
    <div class="tab-pane fade show active" id="general">
        General content
    </div>
    <div class="tab-pane fade" id="security">
        Security content
    </div>
</div>

<!-- NEW Flux UI Pro -->
<flux:tabs>
    <flux:tab.list>
        <flux:tab>General</flux:tab>
        <flux:tab>Security</flux:tab>
    </flux:tab.list>
    
    <flux:tab.panel>
        General content
    </flux:tab.panel>
    
    <flux:tab.panel>
        Security content
    </flux:tab.panel>
</flux:tabs>
```

---

## Migration Priority and File List

### PHASE 1: Core Layouts (5 files) - DO THESE FIRST
**Location:** `/resources/views/layouts/`

1. **guest.blade.php** - Public/guest layout
2. **auth-standalone.blade.php** - Authentication pages layout
3. **settings.blade.php** - Settings pages layout
4. **settings-lazy.blade.php** - Lazy-loaded settings layout
5. **setup.blade.php** - Setup wizard layout

**Key Changes:**
- Remove all Bootstrap CSS/JS includes
- Remove Alpine.js initialization
- Add Flux UI Pro imports
- Convert navigation to flux:navbar
- Convert sidebars to flux:sidebar

### PHASE 2: Authentication (7 files)
**Location:** `/resources/views/auth/`

1. login.blade.php
2. register.blade.php
3. forgot-password.blade.php
4. reset-password.blade.php
5. verify-email.blade.php
6. confirm-password.blade.php
7. two-factor-challenge.blade.php

**Key Changes:**
- Convert forms to flux:field components
- Replace Bootstrap cards with flux:card
- Update buttons to flux:button
- Add wire:submit to forms

### PHASE 3: Client Module (60 files)
**Location:** `/resources/views/clients/`

**Main Templates:**
- create.blade.php
- edit.blade.php
- show.blade.php
- index.blade.php
- dashboard.blade.php

**Sub-modules to convert:**
- `/contacts/` (5 files)
- `/locations/` (5 files)
- `/documents/` (2 files)
- `/quotes/` (5 files)
- `/licenses/` (5 files)
- `/domains/` (2 files)
- `/it-documentation/` (4 files)
- `/recurring-invoices/` (5 files)
- `/calendar-events/` (5 files)
- `/certificates/` (5 files)

### PHASE 4: Financial Module (35 files)
**Location:** `/resources/views/financial/`

**Critical Templates:**
- invoices/create.blade.php
- invoices/edit.blade.php
- invoices/show.blade.php
- invoices/index.blade.php
- quotes/create.blade.php
- quotes/edit.blade.php
- payments/index.blade.php
- recurring/index.blade.php

### PHASE 5: Tickets Module (12 files)
**Location:** `/resources/views/tickets/`

- index.blade.php
- create.blade.php
- edit.blade.php
- show.blade.php
- priority-queue/index.blade.php
- calendar/index.blade.php
- templates/index.blade.php

### PHASE 6: Settings Module (65 files)
**Location:** `/resources/views/settings/`

**Main Settings Pages:**
- index.blade.php
- general.blade.php
- security.blade.php
- email.blade.php
- backup-recovery.blade.php
- rmm-monitoring.blade.php
- ticketing-service-desk.blade.php

**Partials to convert:**
- `/partials/tabs/` (multiple tab components)
- `/partials/forms/` (form components)

### PHASE 7: Components (All custom components)
**Location:** `/resources/views/components/`

**Priority Components:**
1. **page-header.blade.php** - Convert to flux:header
2. **content-card.blade.php** - Convert to flux:card
3. **form-input.blade.php** - Convert to flux:input
4. **form-select.blade.php** - Convert to flux:select
5. **action-button.blade.php** - Convert to flux:button
6. **data-table.blade.php** - Convert to flux:table
7. **modal.blade.php** - Convert to flux:modal
8. **alert.blade.php** - Convert to flux:toast

---

## Livewire Component Creation Guide

### When to Create Livewire Components

Create a new Livewire component when:
1. The template has Alpine.js interactivity
2. Forms need validation and submission
3. Tables need sorting/filtering
4. Modal interactions are required
5. Real-time updates are needed

### Livewire Component Template

```php
<?php
// app/Livewire/[Domain]/[ComponentName].php

namespace App\Livewire\[Domain];

use Livewire\Component;
use Livewire\WithPagination;

class [ComponentName] extends Component
{
    use WithPagination;
    
    // Properties
    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $showModal = false;
    
    // Model binding
    public $name = '';
    public $email = '';
    
    // Validation rules
    protected $rules = [
        'name' => 'required|min:3',
        'email' => 'required|email',
    ];
    
    // Real-time validation
    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }
    
    // Actions
    public function save()
    {
        $this->validate();
        
        // Save logic here
        
        session()->flash('message', 'Saved successfully!');
        $this->reset();
    }
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
    
    public function render()
    {
        return view('livewire.[domain].[component-name]', [
            'items' => Model::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', '%'.$this->search.'%'))
                ->orderBy($this->sortField, $this->sortDirection)
                ->paginate(10)
        ]);
    }
}
```

### Corresponding Blade View

```blade
{{-- resources/views/livewire/[domain]/[component-name].blade.php --}}
<div>
    {{-- Search Bar --}}
    <flux:input 
        type="search" 
        wire:model.live.debounce.300ms="search" 
        placeholder="Search..."
        icon="magnifying-glass"
    />
    
    {{-- Data Table --}}
    <flux:table>
        <flux:table.head>
            <flux:table.row>
                <flux:table.heading 
                    wire:click="sortBy('name')"
                    class="cursor-pointer"
                >
                    Name
                    @if($sortField === 'name')
                        <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="w-3 h-3" />
                    @endif
                </flux:table.heading>
                <flux:table.heading>Actions</flux:table.heading>
            </flux:table.row>
        </flux:table.head>
        <flux:table.body>
            @foreach($items as $item)
            <flux:table.row wire:key="item-{{ $item->id }}">
                <flux:table.cell>{{ $item->name }}</flux:table.cell>
                <flux:table.cell>
                    <flux:button size="sm" wire:click="edit({{ $item->id }})">Edit</flux:button>
                </flux:table.cell>
            </flux:table.row>
            @endforeach
        </flux:table.body>
    </flux:table>
    
    {{-- Pagination --}}
    {{ $items->links() }}
    
    {{-- Modal --}}
    <flux:modal wire:model="showModal">
        <flux:modal.header>
            <flux:heading>Edit Item</flux:heading>
        </flux:modal.header>
        
        <flux:modal.body>
            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="name" />
                <flux:error name="name" />
            </flux:field>
        </flux:modal.body>
        
        <flux:modal.footer>
            <flux:button variant="ghost" wire:click="$set('showModal', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="save">Save</flux:button>
        </flux:modal.footer>
    </flux:modal>
</div>
```

---

## Special Conversion Cases

### 1. DataTables to Flux Tables
If using DataTables jQuery plugin, convert to Livewire component with:
- Server-side pagination
- Column sorting via wire:click
- Search via wire:model.live
- Filters via Livewire properties

### 2. Select2/Chosen to Flux Select
Replace enhanced selects with:
```blade
<flux:select wire:model="selected" searchable multiple>
    @foreach($options as $option)
        <flux:option value="{{ $option->id }}">{{ $option->name }}</flux:option>
    @endforeach
</flux:select>
```

### 3. Date Pickers
Replace bootstrap-datepicker or flatpickr with:
```blade
<flux:datepicker wire:model="date" />
<flux:datetimepicker wire:model="datetime" />
```

### 4. File Uploads
Replace custom file inputs with:
```blade
<flux:file wire:model="file" accept="image/*" />
```

### 5. Rich Text Editors
For TinyMCE or CKEditor, use:
```blade
<flux:editor wire:model="content" />
```

---

## Testing Each Conversion

After converting each template:

1. **Visual Check:**
   - All elements render correctly
   - Dark mode works
   - Responsive design intact
   - No console errors

2. **Functionality Check:**
   - Forms submit correctly
   - Validation displays properly
   - Modals open/close
   - Dropdowns work
   - Tables sort/filter

3. **Livewire Check:**
   - wire:model bindings work
   - wire:click events fire
   - Loading states display
   - Real-time updates work

4. **Performance Check:**
   - No Alpine.js in browser DevTools
   - No Bootstrap JS loaded
   - Livewire requests optimized
   - Lazy loading works

---

## Common Pitfalls to Avoid

1. **Don't mix frameworks** - Use ONLY Flux UI Pro
2. **Don't use x-data** - Replace with Livewire component
3. **Don't use onclick** - Use wire:click
4. **Don't use Bootstrap modals** - Use flux:modal with wire:model
5. **Don't use jQuery** - Use Livewire for interactivity
6. **Don't forget wire:key** - Add to looped items
7. **Don't forget validation** - Add flux:error components
8. **Don't hardcode classes** - Use Flux component props

---

## File Organization

### Livewire Components Structure
```
app/Livewire/
├── Clients/
│   ├── ClientTable.php
│   ├── ClientForm.php
│   └── ClientSearch.php
├── Financial/
│   ├── InvoiceTable.php
│   ├── InvoiceForm.php
│   └── PaymentForm.php
├── Tickets/
│   ├── TicketTable.php
│   ├── TicketForm.php
│   └── TicketTimeline.php
└── Settings/
    ├── GeneralSettings.php
    ├── SecuritySettings.php
    └── EmailSettings.php
```

### Blade Views Structure
```
resources/views/
├── livewire/
│   ├── clients/
│   │   ├── client-table.blade.php
│   │   ├── client-form.blade.php
│   │   └── client-search.blade.php
│   ├── financial/
│   └── tickets/
├── clients/
│   ├── index.blade.php (uses <livewire:clients.client-table />)
│   ├── create.blade.php (uses <livewire:clients.client-form />)
│   └── edit.blade.php (uses <livewire:clients.client-form :client="$client" />)
```

---

## Gemini-Specific Instructions

### For Each File:

1. **Read the entire file first**
2. **Identify all Bootstrap classes and Alpine.js code**
3. **Check if a Livewire component is needed**
4. **If yes, create the Livewire component first**
5. **Convert the Blade template using Flux components**
6. **Test the conversion mentally for completeness**
7. **Document any special cases or issues**

### Conversion Order:
1. Start with layouts (they affect everything)
2. Then do auth (commonly used)
3. Focus on one module at a time
4. Convert components as you encounter them
5. Test each module before moving on

### Output Format:
For each file, provide:
```
FILE: [path/to/file.blade.php]
STATUS: [NEEDS_LIVEWIRE | SIMPLE_CONVERSION]
COMPONENTS_NEEDED: [list of Flux components]
SPECIAL_NOTES: [any special considerations]

[Converted code here]
```

---

## Success Criteria

The migration is complete when:
1. ✅ All 797 templates use Flux UI Pro
2. ✅ Zero Alpine.js code remains
3. ✅ Zero Bootstrap classes remain
4. ✅ All forms use Livewire
5. ✅ All tables have sorting/filtering
6. ✅ All modals use wire:model
7. ✅ Dark mode works everywhere
8. ✅ Mobile responsive everywhere
9. ✅ No console errors
10. ✅ Consistent UI/UX throughout

---

## Additional Resources

- Flux UI Pro Docs: https://fluxui.dev/docs
- Livewire Docs: https://livewire.laravel.com/docs
- Laravel Docs: https://laravel.com/docs

---

## Start Migration Now

Begin with Phase 1 layouts, as they're the foundation for all other templates. Each successful conversion brings the application closer to a modern, consistent, and maintainable codebase.

**Total Templates to Convert: 797**
**Estimated Time per Template: 5-15 minutes**
**Total Estimated Time: ~100 hours**

With Gemini's 2M context window, you can process multiple files in parallel. Focus on accuracy over speed to ensure a bug-free migration.

Good luck with the migration!