# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Environment
This codebase runs in a Linux container (Ubuntu 24.04) hosted on Arch Linux (kernel 6.16.4-arch1-1).

## Project Overview
Nestogy ERP is an enterprise-grade Laravel application for Managed Service Providers (MSPs), built with Laravel 12, Livewire 3, and Tailwind CSS. It follows Domain-Driven Design principles with distinct bounded contexts in `app/Domains/`.

## Development Commands

### Initial Setup
```bash
composer install              # Install PHP dependencies
npm install                   # Install JavaScript dependencies
php artisan key:generate      # Generate application key
php artisan migrate          # Run database migrations
npm run build                # Build frontend assets
```

### Development Server
```bash
php artisan serve            # Start Laravel server (http://localhost:8000)
npm run dev                  # Start Vite dev server with hot-reloading
composer run dev             # Run all services concurrently (server, queue, logs, vite)
```

### Testing
```bash
php artisan test             # Run all tests
php artisan test --filter TestName  # Run specific test
php artisan test tests/Unit         # Run unit tests only
php artisan test tests/Feature      # Run feature tests only
composer test                # Clear config and run tests
```

### Code Quality
```bash
./vendor/bin/pint            # Laravel code formatter (PSR-12)
php artisan pint             # Alternative Pint command
```

### Database
```bash
php artisan migrate:fresh --seed    # Reset database with seeders
php artisan migrate:rollback        # Rollback last migration
php artisan migrate:status          # Check migration status
php artisan db:seed                 # Run database seeders
```

### Cache Management
```bash
php artisan config:clear     # Clear config cache
php artisan cache:clear      # Clear application cache
php artisan route:clear      # Clear route cache
php artisan view:clear       # Clear compiled views
php artisan optimize:clear   # Clear all caches
```

### Queue Management
```bash
php artisan queue:listen     # Process queue jobs
php artisan queue:work       # Process queue jobs (production)
php artisan queue:failed     # List failed jobs
php artisan queue:retry all  # Retry all failed jobs
```

## Architecture

### Domain-Driven Design Structure
The application uses DDD with bounded contexts in `app/Domains/`:
- **Asset**: Equipment and inventory management
- **Client**: Customer relationship management
- **Contract**: Service agreements and SLAs
- **Financial**: Billing, invoicing, payments
- **Integration**: Third-party service connectors
- **Knowledge**: Documentation and knowledge base
- **Lead**: Sales pipeline management
- **Marketing**: Campaign and communication tools
- **Product**: Service catalog management
- **Project**: Project and task management
- **Report**: Analytics and reporting
- **Security**: Authentication and authorization
- **Ticket**: Help desk and support tickets

Each domain typically contains:
- `Models/` - Eloquent models
- `Controllers/` - HTTP controllers
- `Services/` - Business logic
- `Repositories/` - Data access layer
- `Events/` and `Listeners/` - Domain events

### Key Technologies
- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: Livewire 3, Alpine.js, Tailwind CSS 4
- **Database**: MySQL/MariaDB or PostgreSQL
- **Assets**: Vite for bundling
- **UI Components**: Livewire Flux Pro v2.0
- **Authentication**: Laravel Fortify with 2FA support
- **Authorization**: Silber Bouncer for roles/permissions

### Service Layer Pattern
Business logic resides in service classes within each domain. Controllers should be thin, delegating to services:
```php
// app/Domains/Ticket/Services/TicketService.php
class TicketService {
    public function createTicket(array $data): Ticket { }
    public function assignTechnician(Ticket $ticket, User $tech): void { }
}
```

### Repository Pattern
Data access is abstracted through repositories:
```php
// app/Domains/Client/Repositories/ClientRepository.php
class ClientRepository {
    public function findActive(): Collection { }
    public function getWithContracts(int $id): Client { }
}
```

## Database Considerations
- Testing uses MySQL with database: `nestogy_testing`
- Migrations should be reversible
- Use database transactions for critical operations
- Models use UUID primary keys where applicable

## Frontend Development
- Components use Livewire 3 for reactivity
- **Flux UI Pro v2.0** for UI components - DO NOT create local overrides!
- Tailwind CSS 4 for styling (no Bootstrap)
- Alpine.js for lightweight JavaScript interactions
- Chart.js for data visualization
- Tom Select for enhanced dropdowns
- Flatpickr for date/time pickers

### Important: Flux UI Components
- **NEVER create local Flux component overrides** in `resources/views/flux/`
- Always use the official Flux UI Pro v2.0 components from vendor
- Use MCP (Model Context Protocol) for Flux UI documentation via `mcp__fluxui-server__` functions
- Flux UI supports advanced features like `hover` prop on dropdowns and `flux:popover` components

## API Integrations
The system integrates with numerous third-party services:
- **RMM**: ConnectWise Automate, Datto RMM, NinjaOne
- **Documentation**: IT Glue, Hudu, Confluence
- **Billing**: Stripe, Square, PayPal, Plaid
- **Cloud**: AWS, Azure, GCP billing APIs
- **VoIP**: FusionPBX, 3CX, RingCentral
- **Monitoring**: Auvik, PRTG

## Client Selection Architecture

### Session-Based Client Context (IMPORTANT - Do Not Revert)
The application uses **session-based client selection** instead of GET/POST parameters for client context:

- **Client Selection**: `ClientSwitcher` component stores selected client ID in session via `NavigationService::setSelectedClient()`
- **Context Retrieval**: Controllers use `NavigationService::getSelectedClient()` to get current client from session
- **Clean URLs**: No `?client=123` parameters in URLs - all client context comes from session
- **Automatic Filtering**: Tickets, assets, invoices, etc. are automatically filtered by session-selected client

### Implementation Details:
- **NavigationService**: Provides centralized session management for client selection
- **UsesSelectedClient Trait**: Helper trait for controllers that need client context
- **ClientSwitcher**: Livewire component that handles client switching via session (no URL redirects with parameters)
- **Controllers**: Use session-based filtering instead of checking GET/POST parameters

### Why Session-Based Over GET Parameters:
- **Clean URLs**: Better user experience and SEO
- **Consistent Context**: Client context maintained across all navigation
- **Security**: Prevents URL manipulation of client context
- **Performance**: Session lookups more efficient than parameter parsing
- **Maintainability**: Centralized client context management

**DO NOT revert to GET parameter-based client selection** - this was a deliberate architectural decision to improve UX and maintainability.

## Security Best Practices
- Never commit `.env` files or credentials
- Use Laravel's built-in CSRF protection
- Validate all user input
- Use policies for authorization
- Encrypt sensitive data
- Enable 2FA for production

## Debugging Blade/Flux Template Issues

### Common ParseError: "syntax error, unexpected end of file"
This usually indicates mismatched HTML/Blade/Flux tags. To debug:

1. **Check Flux component tag balance**:
```bash
# Count opening flux tags
grep -n "<flux:" file.blade.php | wc -l

# Count closing flux tags  
grep -n "</flux:" file.blade.php | wc -l

# Count self-closing tags
grep -n "/>" file.blade.php | wc -l

# Opening tags should equal closing tags + self-closing tags
```

2. **Identify unclosed components**:
```bash
# List components with opening tags (not self-closing)
grep -n "<flux:" file.blade.php | grep -v "/>" | sed 's/.*<flux://' | sed 's/[> ].*//' | sort | uniq -c

# List components with closing tags
grep -n "</flux:" file.blade.php | sed 's/.*<\/flux://' | sed 's/>.*//' | sort | uniq -c

# Compare counts to find mismatches
```

3. **Common issues**:
- Flux components closed with HTML tags: `<flux:button>Text</button>` should be `<flux:button>Text</flux:button>`
- Missing closing tags for Flux components
- Mismatched @if/@endif, @foreach/@endforeach directives

4. **Clear compiled views after fixes**:
```bash
php artisan view:clear
```