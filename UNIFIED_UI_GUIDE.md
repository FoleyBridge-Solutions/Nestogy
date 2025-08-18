# Unified UI Component Guide

## Overview
This guide shows how to use the new unified UI components throughout the Nestogy application for consistent styling.

## Components

### 1. Page Header Component (`x-page-header`)
A compact, unified header for all pages with consistent spacing and styling.

**Props:**
- `title` (string): Main page title
- `subtitle` (string, optional): Descriptive subtitle
- `compact` (boolean): Use compact spacing (recommended: true)
- `backRoute` (string, optional): URL for back button
- `backLabel` (string): Label for back button (default: "Back")
- `actions` (slot, optional): Additional action buttons

**Example:**
```blade
<x-page-header 
    :title="'Create Client'"
    :subtitle="'Add a new client to your system'"
    :back-route="route('clients.index')"
    :back-label="'Back to Clients'"
    :compact="true"
>
    <x-slot name="actions">
        <button class="...">Additional Action</button>
    </x-slot>
</x-page-header>
```

### 2. Content Card Component (`x-content-card`)
A unified card component for content sections.

**Props:**
- `compact` (boolean): Use compact padding (recommended: true)
- `noPadding` (boolean): Remove all padding (useful for forms)

**Example:**
```blade
<x-content-card :compact="true" :no-padding="true">
    <form>
        <div class="p-4 sm:p-5">
            <!-- Form content -->
        </div>
    </form>
</x-content-card>
```

## Migration Examples

### Before (Old Style):
```blade
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Page Title</h1>
                    <p class="mt-1 text-sm text-gray-500">Description</p>
                </div>
                <div>
                    <a href="{{ route('back') }}" class="...">Back</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <!-- Content here -->
        </div>
    </div>
</div>
```

### After (New Unified Style):
```blade
<div class="space-y-4">
    <!-- Page Header -->
    <x-page-header 
        :title="'Page Title'"
        :subtitle="'Description'"
        :back-route="route('back')"
        :compact="true"
    />

    <!-- Content -->
    <x-content-card :compact="true">
        <!-- Content here -->
    </x-content-card>
</div>
```

## Benefits

1. **Reduced Visual Weight**: 
   - Smaller headers (py-2 instead of py-5)
   - Compact text sizes (text-lg/text-xs instead of text-2xl/text-sm)
   - Lighter shadows and borders

2. **Consistency**: 
   - All pages use the same header structure
   - Unified spacing and styling
   - Predictable user experience

3. **Maintainability**:
   - Changes to components affect all pages
   - Easier to update design system
   - Less duplicate code

## Implementation Checklist

To update a page to use the unified components:

1. [ ] Replace header divs with `<x-page-header>`
2. [ ] Replace content divs with `<x-content-card>`
3. [ ] Change `space-y-6` to `space-y-4` for tighter spacing
4. [ ] Update padding: `px-4 py-5 sm:p-6` → `p-4 sm:p-5`
5. [ ] Set `:compact="true"` on components
6. [ ] Test responsive behavior

## Pages to Update

### High Priority (User-facing forms):
- [x] `/clients/create`
- [x] `/clients/edit`
- [ ] `/tickets/create`
- [ ] `/tickets/edit`
- [ ] `/assets/create`
- [ ] `/financial/invoices/create`
- [ ] `/projects/create`

### Medium Priority (View pages):
- [ ] `/clients/show`
- [ ] `/tickets/show`
- [ ] `/financial/invoices/show`
- [ ] `/projects/show`

### Low Priority (Admin/Settings):
- [ ] Settings pages
- [ ] Admin pages
- [ ] Report pages

## Notes

- Always use `:compact="true"` for better information density
- The breadcrumbs in the main layout have been made more compact
- Consider using `space-y-4` instead of `space-y-6` between sections
- For Bootstrap-based pages, gradually migrate to Tailwind components