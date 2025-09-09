# Implementation Plan for Missing Routes

## Overview
This document provides a comprehensive implementation plan for adding all missing routes referenced in the sidebar navigation. The routes need to be added following the existing patterns in the Nestogy ERP application.

## Current Status

### ✅ Working Routes
- clients.index
- clients.contacts.index
- clients.locations.index
- clients.vendors.index ⭐ **NEW**
- clients.licenses.index ⭐ **NEW**
- clients.credentials.index ⭐ **NEW**
- clients.domains.index ⭐ **NEW**
- clients.services.index ⭐ **NEW**
- clients.documents.index ⭐ **NEW**
- clients.files.index ⭐ **NEW**
- clients.calendar-events.index ⭐ **NEW**
- clients.networks.index ⭐ **NEW**
- clients.racks.index ⭐ **NEW**
- clients.certificates.index ⭐ **NEW**
- tickets.index
- assets.index
- financial.invoices.index
- financial.payments.index
- financial.expenses.index
- financial.quotes.index
- financial.recurring-invoices.index ⭐ **NEW**
- financial.trips.index ⭐ **NEW**

### ❌ Remaining Routes (Optional Implementation)
The following routes are referenced in the sidebar but not yet implemented:

#### Client Domain Routes
1. ~~**clients.vendors.index** - Vendor management~~ ✅ **COMPLETED**
2. ~~**clients.calendar-events.index** - Calendar events~~ ✅ **COMPLETED**
3. ~~**clients.licenses.index** - License tracking~~ ✅ **COMPLETED**
4. ~~**clients.credentials.index** - Credential vault~~ ✅ **COMPLETED**
5. ~~**clients.networks.index** - Network documentation~~ ✅ **COMPLETED**
6. ~~**clients.racks.index** - Rack management~~ ✅ **COMPLETED**
7. ~~**clients.certificates.index** - SSL/Certificate tracking~~ ✅ **COMPLETED**
8. ~~**clients.domains.index** - Domain management~~ ✅ **COMPLETED**
9. ~~**clients.services.index** - Service catalog~~ ✅ **COMPLETED**
10. ~~**clients.documents.index** - Document management~~ ✅ **COMPLETED**
11. ~~**clients.files.index** - File storage~~ ✅ **COMPLETED**

#### Financial Domain Routes
12. ~~**financial.recurring-invoices.index** - Recurring invoice management~~ ✅ **COMPLETED**
13. ~~**financial.quotes.index** - Quote management~~ ✅ **COMPLETED**
14. ~~**financial.trips.index** - Trip/travel expense tracking~~ ✅ **COMPLETED**

## Implementation Pattern

### 1. Route Definition Pattern
All routes should be added to `/var/www/Nestogy/routes/web.php` within the authenticated middleware group:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    // Client routes - add within the clients prefix group
    Route::prefix('clients/{client}')->name('clients.')->group(function () {
        Route::resource('vendors', \App\Domains\Client\Controllers\VendorController::class);
        Route::resource('calendar-events', \App\Domains\Client\Controllers\CalendarEventController::class);
        // ... etc
    });
    
    // Financial routes - add within the financial prefix group
    Route::prefix('financial')->name('financial.')->group(function () {
        Route::resource('recurring-invoices', \App\Domains\Financial\Controllers\RecurringInvoiceController::class);
        Route::resource('trips', \App\Domains\Financial\Controllers\TripController::class);
        // Note: quotes already exists but may need route adjustment
    });
});
```

### 2. Controller Pattern
Controllers already exist in:
- `/var/www/Nestogy/app/Domains/Client/Controllers/`
- `/var/www/Nestogy/app/Domains/Financial/Controllers/`

Each controller should follow this pattern:
```php
namespace App\Domains\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Services\NavigationService;
use App\Traits\UsesSelectedClient;

class VendorController extends Controller
{
    use UsesSelectedClient;
    
    public function index(Request $request)
    {
        $client = $this->getSelectedClient($request);
        
        if (!$client) {
            return redirect()->route('clients.select-screen');
        }
        
        $vendors = $client->vendors()->paginate(20);
        
        return view('clients.vendors.index', compact('vendors', 'client'));
    }
}
```

### 3. View Pattern
Views should be created in:
- `/var/www/Nestogy/resources/views/clients/[resource]/index.blade.php`
- `/var/www/Nestogy/resources/views/financial/[resource]/index.blade.php`

Basic view template:
```blade
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <flux:heading size="xl" level="1">
        {{ $client->name }} - Vendors
    </flux:heading>
    
    <flux:table>
        <flux:columns>
            <flux:column>Name</flux:column>
            <flux:column>Type</flux:column>
            <flux:column>Contact</flux:column>
            <flux:column>Status</flux:column>
        </flux:columns>
        
        <flux:rows>
            @foreach($vendors as $vendor)
            <flux:row>
                <flux:cell>{{ $vendor->name }}</flux:cell>
                <flux:cell>{{ $vendor->type }}</flux:cell>
                <flux:cell>{{ $vendor->contact }}</flux:cell>
                <flux:cell>
                    <flux:badge color="{{ $vendor->is_active ? 'green' : 'gray' }}">
                        {{ $vendor->is_active ? 'Active' : 'Inactive' }}
                    </flux:badge>
                </flux:cell>
            </flux:row>
            @endforeach
        </flux:rows>
    </flux:table>
    
    {{ $vendors->links() }}
</div>
@endsection
```

## Key Technical Requirements

### Session-Based Client Selection
**IMPORTANT**: All client-related routes MUST use session-based client selection, NOT GET parameters:

```php
// CORRECT - Using NavigationService
$clientId = app(NavigationService::class)->getSelectedClient();

// WRONG - Using GET parameters
$clientId = $request->get('client');
```

### Flux UI Components
**IMPORTANT**: Use Flux UI Pro v2.0 components - DO NOT create local overrides:
- Use `flux:table`, `flux:button`, `flux:badge`, etc.
- Reference Flux documentation via MCP functions when needed
- Never create files in `/resources/views/flux/`

### Domain-Driven Design
Follow the DDD structure:
- Controllers in `app/Domains/[Domain]/Controllers/`
- Models in `app/Domains/[Domain]/Models/` or `app/Models/`
- Services in `app/Domains/[Domain]/Services/`

## Stripe Configuration Fix

The Stripe configuration error appears to be resolved after running `php artisan config:cache`. If it reappears, check:
1. `/var/www/Nestogy/config/services.php` - Stripe configuration is correctly structured
2. Environment variables in `.env` for STRIPE_KEY, STRIPE_SECRET, STRIPE_WEBHOOK_SECRET

## Testing Each Route

After implementing each route:
1. Clear route cache: `php artisan route:clear`
2. Cache routes: `php artisan route:cache`
3. Test the route: `php artisan route:list | grep [route_name]`
4. Visit the URL in browser to verify it loads without errors

## Priority Order

Implement in this order:
1. **High Priority** (Most commonly used):
    - ~~clients.vendors.index~~ ✅ **COMPLETED**
    - ~~financial.quotes.index~~ ✅ **COMPLETED**
    - ~~financial.recurring-invoices.index~~ ✅ **COMPLETED**

2. **Medium Priority**:
     - ~~clients.licenses.index~~ ✅ **COMPLETED**
     - ~~clients.credentials.index~~ ✅ **COMPLETED**
     - ~~clients.domains.index~~ ✅ **COMPLETED**
     - ~~clients.services.index~~ ✅ **COMPLETED**
     - ~~clients.documents.index~~ ✅ **COMPLETED**
     - ~~clients.files.index~~ ✅ **COMPLETED**

3. **Low Priority**:
    - clients.calendar-events.index
    - clients.networks.index
    - clients.racks.index
    - clients.certificates.index
    - financial.trips.index

## Database Considerations

Check if models and migrations exist for:
- ClientVendor
- CalendarEvent
- License
- Credential
- Network
- Rack
- Certificate
- Domain
- Service
- RecurringInvoice
- Trip

If models don't exist, they may need to be created with appropriate relationships.

## Next Steps for Implementation Agent

✅ **COMPLETED: High Priority Routes**
1. ~~Start with fixing the Stripe configuration if it's still an issue~~ (Note: Stripe config may need .env setup)
2. ~~Implement high-priority routes~~ ✅ **DONE**
     - ✅ clients.vendors.index - Route added, controller updated for session-based client selection, view created
     - ✅ financial.quotes.index - Already existed and working
     - ✅ financial.recurring-invoices.index - Route added, view created

✅ **COMPLETED: Medium Priority Routes**
3. ~~Implement medium-priority routes~~ ✅ **DONE**
     - ✅ clients.licenses.index - Route existed, controller verified, view existed
     - ✅ clients.credentials.index - Route existed, controller verified, view existed
     - ✅ clients.domains.index - Route existed, controller verified, view existed
     - ✅ clients.services.index - Route existed, controller verified, view existed
     - ✅ clients.documents.index - Route existed, controller verified, view existed
     - ✅ clients.files.index - Route existed, controller verified, view existed

## Remaining Tasks
4. ~~**Low Priority Routes**~~ ✅ **ALL COMPLETED**
     - ~~clients.calendar-events.index~~ ✅ **COMPLETED**
     - ~~clients.networks.index~~ ✅ **COMPLETED**
     - ~~clients.racks.index~~ ✅ **COMPLETED**
     - ~~clients.certificates.index~~ ✅ **COMPLETED**
     - ~~financial.trips.index~~ ✅ **COMPLETED**

4. For each remaining route:
    - Add route definition in web.php
    - Verify controller exists and has index method
    - Create basic index view
    - Test the route works
5. Run tests after each implementation
6. Verify sidebar navigation works correctly

## Important Notes

- **DO NOT** revert to GET parameter-based client selection
- **DO NOT** create local Flux component overrides
- **DO** use session-based client selection via NavigationService
- **DO** follow existing patterns in the codebase
- **DO** test each route after implementation