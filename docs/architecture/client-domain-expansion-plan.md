# Client Domain Expansion Architecture Plan

## Executive Summary

This document outlines the comprehensive architectural plan for expanding the Client domain to support all required MSP (Managed Service Provider) ERP features. The expansion will transform the current basic Client domain into a comprehensive client management system supporting contacts, locations, support, documentation, and billing features.

## Current State Analysis

### Existing Architecture
- **Domain Structure**: Domain-Driven Design with Laravel 11
- **Multi-tenancy**: Company-based isolation using `tenant_id` (mapped to `company_id`)
- **Current Models**: Client, ClientContact, ClientAddress
- **Security**: Role-based access control (Admin=3, Tech=2, Accountant=1)
- **Database**: Comprehensive schema with existing tables for core MSP functionality

### Current Client Domain Structure
```
app/Domains/Client/
├── Models/
│   ├── Client.php
│   ├── ClientContact.php
│   └── ClientAddress.php
└── (Controllers, Services, etc. - to be created)
```

### Existing Database Tables (Client-Related)
- `clients` - Core client information
- `client_contacts` - New contact management (recently added)
- `client_addresses` - New address management (recently added)
- `contacts` - Legacy contact system (to be migrated)
- `locations` - Legacy location system (to be migrated)

## Target Architecture Overview

### Feature Categories

#### 1. OVERVIEW
- Client dashboard and overview

#### 2. CONTACTS & LOCATIONS
- Contacts management (✓ Partially implemented)
- Locations management (✓ Legacy system exists)

#### 3. SUPPORT
- Tickets (✓ Existing system)
- Recurring Tickets (automated ticket creation)
- Projects (✓ Existing system)
- Vendors (✓ Existing system)
- Calendar (scheduling and appointments)

#### 4. DOCUMENTATION
- Assets (✓ Existing system)
- Licenses (software/hardware licenses)
- Credentials (login credentials storage)
- Networks (✓ Existing system)
- Racks (server rack management)
- Certificates (SSL/security certificates)
- Domains (domain name management)
- Services (service documentation)
- Documents (file document management)
- Files (general file management)

#### 5. BILLING
- Invoices (✓ Existing system)
- Recurring Invoices (✓ Existing system)
- Quotes (✓ Existing system)
- Payments (✓ Existing system)
- Trips (travel/trip billing)

## Architectural Decisions

### 1. Domain Organization Strategy

**Decision**: Expand the Client domain to include client-specific features while maintaining clear boundaries with other domains.

**Rationale**: 
- Client domain should own client-specific data and business logic
- Cross-domain features (like tickets, invoices) should remain in their respective domains but have strong relationships with Client domain
- This maintains separation of concerns while enabling rich client management

### 2. Model Relationship Strategy

**Decision**: Use polymorphic relationships and clear foreign key relationships to connect client features.

**Structure**:
```
Client (Core)
├── ClientContact (1:N)
├── ClientAddress (1:N) 
├── ClientLocation (1:N) - New, migrated from locations
├── ClientAsset (1:N) - Relationship to existing assets
├── ClientLicense (1:N) - New
├── ClientCredential (1:N) - New
├── ClientRack (1:N) - New
├── ClientCertificate (1:N) - New
├── ClientDomain (1:N) - New
├── ClientService (1:N) - New
├── ClientDocument (1:N) - New
├── ClientFile (1:N) - New
├── ClientCalendarEvent (1:N) - New
├── ClientTrip (1:N) - New
└── ClientRecurringTicket (1:N) - New
```

### 3. Integration Strategy

**Decision**: Use event-driven architecture and service layer patterns for cross-domain integration.

**Integration Points**:
- **Ticket Domain**: Tickets belong to clients, events for ticket creation/updates
- **Asset Domain**: Assets belong to clients, shared through relationships
- **Financial Domain**: Invoices, payments, quotes belong to clients
- **Project Domain**: Projects belong to clients
- **User Domain**: User permissions and access control

## Detailed Architecture Design

### Model Architecture

#### Core Client Models (Existing - Enhanced)

##### Client Model Enhancements
```php
// Additional relationships to be added
public function licenses() { return $this->hasMany(ClientLicense::class); }
public function credentials() { return $this->hasMany(ClientCredential::class); }
public function racks() { return $this->hasMany(ClientRack::class); }
public function certificates() { return $this->hasMany(ClientCertificate::class); }
public function domains() { return $this->hasMany(ClientDomain::class); }
public function services() { return $this->hasMany(ClientService::class); }
public function documents() { return $this->hasMany(ClientDocument::class); }
public function files() { return $this->hasMany(ClientFile::class); }
public function calendarEvents() { return $this->hasMany(ClientCalendarEvent::class); }
public function trips() { return $this->hasMany(ClientTrip::class); }
public function recurringTickets() { return $this->hasMany(ClientRecurringTicket::class); }

// Cross-domain relationships (existing)
public function tickets() { return $this->hasMany(\App\Domains\Ticket\Models\Ticket::class); }
public function assets() { return $this->hasMany(\App\Domains\Asset\Models\Asset::class); }
public function invoices() { return $this->hasMany(\App\Domains\Financial\Models\Invoice::class); }
public function projects() { return $this->hasMany(\App\Domains\Project\Models\Project::class); }
```

#### New Client Domain Models

##### ClientLocation Model
```php
// Migrated and enhanced from existing locations table
class ClientLocation extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;
    
    protected $fillable = [
        'tenant_id', 'client_id', 'name', 'description', 'address_line_1', 
        'address_line_2', 'city', 'state', 'zip_code', 'country', 'phone', 
        'hours', 'photo', 'primary', 'notes', 'contact_id'
    ];
    
    // Relationships
    public function client() { return $this->belongsTo(Client::class); }
    public function contact() { return $this->belongsTo(ClientContact::class, 'contact_id'); }
    public function assets() { return $this->hasMany(\App\Domains\Asset\Models\Asset::class, 'location_id'); }
    public function networks() { return $this->hasMany(ClientNetwork::class); }
}
```

##### ClientLicense Model
```php
class ClientLicense extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;
    
    protected $fillable = [
        'tenant_id', 'client_id', 'name', 'type', 'vendor', 'license_key', 
        'seats', 'cost', 'purchase_date', 'expiry_date', 'renewal_date',
        'auto_renewal', 'notes', 'contact_id', 'location_id'
    ];
    
    protected $casts = [
        'purchase_date' => 'date',
        'expiry_date' => 'date', 
        'renewal_date' => 'date',
        'auto_renewal' => 'boolean',
        'cost' => 'decimal:2'
    ];
}
```

##### ClientCredential Model
```php
class ClientCredential extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;
    
    protected $fillable = [
        'tenant_id', 'client_id', 'name', 'type', 'username', 'password',
        'url', 'notes', 'contact_id', 'location_id', 'asset_id'
    ];
    
    protected $hidden = ['password'];
    
    // Encrypted password storage
    public function setPasswordAttribute($value) {
        $this->attributes['password'] = encrypt($value);
    }
    
    public function getPasswordAttribute($value) {
        return decrypt($value);
    }
}
```

##### ClientRack Model
```php
class ClientRack extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;
    
    protected $fillable = [
        'tenant_id', 'client_id', 'name', 'location_id', 'units', 'depth',
        'power_specs', 'cooling_specs', 'notes'
    ];
    
    public function rackItems() { return $this->hasMany(ClientRackItem::class); }
}
```

##### ClientCertificate Model
```php
class ClientCertificate extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;
    
    protected $fillable = [
        'tenant_id', 'client_id', 'name', 'type', 'domain', 'issuer',
        'issue_date', 'expiry_date', 'auto_renewal', 'cost', 'notes'
    ];
    
    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'auto_renewal' => 'boolean',
        'cost' => 'decimal:2'
    ];
}
```

##### ClientDomain Model
```php
class ClientDomain extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;
    
    protected $fillable = [
        'tenant_id', 'client_id', 'domain_name', 'registrar', 'registration_date',
        'expiry_date', 'auto_renewal', 'cost', 'nameservers', 'notes'
    ];
    
    protected $casts = [
        'registration_date' => 'date',
        'expiry_date' => 'date',
        'auto_renewal' => 'boolean',
        'cost' => 'decimal:2',
        'nameservers' => 'array'
    ];
}
```

##### ClientService Model
```php
class ClientService extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;
    
    protected $fillable = [
        'tenant_id', 'client_id', 'name', 'description', 'type', 'status',
        'port', 'protocol', 'location_id', 'asset_id', 'notes'
    ];
}
```

##### ClientDocument Model
```php
class ClientDocument extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, HasMedia;
    
    protected $fillable = [
        'tenant_id', 'client_id', 'name', 'description', 'category',
        'tags', 'contact_id', 'location_id'
    ];
    
    protected $casts = ['tags' => 'array'];
}
```

##### ClientFile Model
```php
class ClientFile extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, HasMedia;
    
    protected $fillable = [
        'tenant_id', 'client_id', 'name', 'description', 'category',
        'file_path', 'file_size', 'mime_type', 'contact_id', 'location_id'
    ];
}
```

##### ClientCalendarEvent Model
```php
class ClientCalendarEvent extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;
    
    protected $fillable = [
        'tenant_id', 'client_id', 'title', 'description', 'start_date',
        'end_date', 'all_day', 'location', 'contact_id', 'user_id', 'type'
    ];
    
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'all_day' => 'boolean'
    ];
}
```

##### ClientTrip Model
```php
class ClientTrip extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;
    
    protected $fillable = [
        'tenant_id', 'client_id', 'purpose', 'start_date', 'end_date',
        'mileage', 'rate_per_mile', 'total_cost', 'billable', 'user_id', 'notes'
    ];
    
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'rate_per_mile' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'billable' => 'boolean'
    ];
}
```

##### ClientRecurringTicket Model
```php
class ClientRecurringTicket extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;
    
    protected $fillable = [
        'tenant_id', 'client_id', 'name', 'description', 'frequency',
        'next_run_date', 'last_run_date', 'active', 'template_data'
    ];
    
    protected $casts = [
        'next_run_date' => 'datetime',
        'last_run_date' => 'datetime',
        'active' => 'boolean',
        'template_data' => 'array'
    ];
}
```

### Controller Architecture

#### Client Controllers Structure
```
app/Domains/Client/Controllers/
├── ClientController.php (Main client CRUD)
├── ClientDashboardController.php (Overview & dashboard)
├── ClientContactController.php (Contact management)
├── ClientLocationController.php (Location management)
├── ClientAssetController.php (Asset relationships)
├── ClientLicenseController.php (License management)
├── ClientCredentialController.php (Credential management)
├── ClientRackController.php (Rack management)
├── ClientCertificateController.php (Certificate management)
├── ClientDomainController.php (Domain management)
├── ClientServiceController.php (Service management)
├── ClientDocumentController.php (Document management)
├── ClientFileController.php (File management)
├── ClientCalendarController.php (Calendar management)
├── ClientTripController.php (Trip management)
├── ClientRecurringTicketController.php (Recurring ticket management)
└── ClientReportController.php (Client-specific reports)
```

#### Controller Patterns

Each controller will follow these patterns:

1. **Resource Controllers**: Standard CRUD operations
2. **Nested Resource Controllers**: For client sub-resources
3. **API Controllers**: For AJAX/API endpoints
4. **Bulk Operations**: For mass actions
5. **Export/Import**: For data management

Example Controller Structure:
```php
class ClientLicenseController extends Controller
{
    public function __construct(
        private ClientLicenseService $licenseService,
        private ClientService $clientService
    ) {}
    
    public function index(Client $client) { /* List licenses */ }
    public function create(Client $client) { /* Show create form */ }
    public function store(Client $client, StoreLicenseRequest $request) { /* Create license */ }
    public function show(Client $client, ClientLicense $license) { /* Show license */ }
    public function edit(Client $client, ClientLicense $license) { /* Show edit form */ }
    public function update(Client $client, ClientLicense $license, UpdateLicenseRequest $request) { /* Update license */ }
    public function destroy(Client $client, ClientLicense $license) { /* Delete license */ }
    
    // Additional methods
    public function expiring(Client $client) { /* Show expiring licenses */ }
    public function renew(Client $client, ClientLicense $license) { /* Renew license */ }
    public function export(Client $client) { /* Export licenses */ }
}
```

### Service Layer Architecture

#### Service Structure
```
app/Domains/Client/Services/
├── ClientService.php (Core client operations)
├── ClientDashboardService.php (Dashboard data aggregation)
├── ClientContactService.php (Contact operations)
├── ClientLocationService.php (Location operations)
├── ClientLicenseService.php (License operations)
├── ClientCredentialService.php (Credential operations)
├── ClientRackService.php (Rack operations)
├── ClientCertificateService.php (Certificate operations)
├── ClientDomainService.php (Domain operations)
├── ClientServiceService.php (Service operations)
├── ClientDocumentService.php (Document operations)
├── ClientFileService.php (File operations)
├── ClientCalendarService.php (Calendar operations)
├── ClientTripService.php (Trip operations)
├── ClientRecurringTicketService.php (Recurring ticket operations)
├── ClientReportService.php (Reporting operations)
├── ClientExportService.php (Export operations)
├── ClientImportService.php (Import operations)
└── ClientNotificationService.php (Notification operations)
```

#### Service Patterns

1. **Business Logic Encapsulation**: Complex operations in services
2. **Cross-Domain Integration**: Services handle domain interactions
3. **Event Dispatching**: Services dispatch domain events
4. **Caching**: Services implement caching strategies
5. **Validation**: Business rule validation

Example Service:
```php
class ClientLicenseService
{
    public function __construct(
        private ClientLicense $licenseModel,
        private ClientNotificationService $notificationService,
        private EventDispatcher $eventDispatcher
    ) {}
    
    public function createLicense(Client $client, array $data): ClientLicense
    {
        $license = $client->licenses()->create($data);
        
        // Dispatch event
        $this->eventDispatcher->dispatch(new LicenseCreated($license));
        
        // Set up expiry notifications
        if ($license->expiry_date) {
            $this->notificationService->scheduleExpiryNotification($license);
        }
        
        return $license;
    }
    
    public function getExpiringLicenses(Client $client, int $days = 30): Collection
    {
        return $client->licenses()
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>=', now())
            ->get();
    }
}
```

## Database Schema Requirements

### New Tables Required

#### client_locations
```sql
CREATE TABLE client_locations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    address_line_1 VARCHAR(255),
    address_line_2 VARCHAR(255),
    city VARCHAR(255),
    state VARCHAR(255),
    zip_code VARCHAR(255),
    country VARCHAR(255) DEFAULT 'US',
    phone VARCHAR(255),
    hours VARCHAR(255),
    photo VARCHAR(255),
    primary BOOLEAN DEFAULT FALSE,
    notes TEXT,
    contact_id BIGINT UNSIGNED,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    accessed_at TIMESTAMP,
    
    INDEX(tenant_id),
    INDEX(client_id),
    INDEX(name),
    INDEX(primary),
    INDEX(tenant_id, client_id),
    INDEX(client_id, primary),
    
    FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES client_contacts(id) ON DELETE SET NULL
);
```

#### client_licenses
```sql
CREATE TABLE client_licenses (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(255),
    vendor VARCHAR(255),
    license_key TEXT,
    seats INTEGER,
    cost DECIMAL(15,2),
    purchase_date DATE,
    expiry_date DATE,
    renewal_date DATE,
    auto_renewal BOOLEAN DEFAULT FALSE,
    notes TEXT,
    contact_id BIGINT UNSIGNED,
    location_id BIGINT UNSIGNED,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    accessed_at TIMESTAMP,
    
    INDEX(tenant_id),
    INDEX(client_id),
    INDEX(name),
    INDEX(type),
    INDEX(vendor),
    INDEX(expiry_date),
    INDEX(renewal_date),
    INDEX(tenant_id, client_id),
    
    FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES client_contacts(id) ON DELETE SET NULL,
    FOREIGN KEY (location_id) REFERENCES client_locations(id) ON DELETE SET NULL
);
```

#### client_credentials
```sql
CREATE TABLE client_credentials (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(255),
    username VARCHAR(255),
    password TEXT, -- Encrypted
    url VARCHAR(500),
    notes TEXT,
    contact_id BIGINT UNSIGNED,
    location_id BIGINT UNSIGNED,
    asset_id BIGINT UNSIGNED,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    accessed_at TIMESTAMP,
    
    INDEX(tenant_id),
    INDEX(client_id),
    INDEX(name),
    INDEX(type),
    INDEX(tenant_id, client_id),
    
    FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES client_contacts(id) ON DELETE SET NULL,
    FOREIGN KEY (location_id) REFERENCES client_locations(id) ON DELETE SET NULL,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE SET NULL
);
```

#### client_racks
```sql
CREATE TABLE client_racks (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    location_id BIGINT UNSIGNED,
    units INTEGER DEFAULT 42,
    depth VARCHAR(255),
    power_specs TEXT,
    cooling_specs TEXT,
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    accessed_at TIMESTAMP,
    
    INDEX(tenant_id),
    INDEX(client_id),
    INDEX(location_id),
    INDEX(tenant_id, client_id),
    
    FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES client_locations(id) ON DELETE SET NULL
);
```

#### client_certificates
```sql
CREATE TABLE client_certificates (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(255),
    domain VARCHAR(255),
    issuer VARCHAR(255),
    issue_date DATE,
    expiry_date DATE,
    auto_renewal BOOLEAN DEFAULT FALSE,
    cost DECIMAL(15,2),
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    accessed_at TIMESTAMP,
    
    INDEX(tenant_id),
    INDEX(client_id),
    INDEX(domain),
    INDEX(expiry_date),
    INDEX(tenant_id, client_id),
    
    FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);
```

#### client_domains
```sql
CREATE TABLE client_domains (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    domain_name VARCHAR(255) NOT NULL,
    registrar VARCHAR(255),
    registration_date DATE,
    expiry_date DATE,
    auto_renewal BOOLEAN DEFAULT FALSE,
    cost DECIMAL(15,2),
    nameservers JSON,
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    accessed_at TIMESTAMP,
    
    INDEX(tenant_id),
    INDEX(client_id),
    INDEX(domain_name),
    INDEX(expiry_date),
    INDEX(tenant_id, client_id),
    
    FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);
```

#### client_services
```sql
CREATE TABLE client_services (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type VARCHAR(255),
    status VARCHAR(255),
    port INTEGER,
    protocol VARCHAR(255),
    location_id BIGINT UNSIGNED,
    asset_id BIGINT UNSIGNED,
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    accessed_at TIMESTAMP,
    
    INDEX(tenant_id),
    INDEX(client_id),
    INDEX(name),
    INDEX(type),
    INDEX(status),
    INDEX(tenant_id, client_id),
    
    FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES client_locations(id) ON DELETE SET NULL,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE SET NULL
);
```

#### client_documents
```sql
CREATE TABLE client_documents (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(255),
    tags JSON,
    contact_id BIGINT UNSIGNED,
    location_id BIGINT UNSIGNED,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    accessed_at TIMESTAMP,
    
    INDEX(tenant_id),
    INDEX(client_id),
    INDEX(name),
    INDEX(category),
    INDEX(tenant_id, client_id),
    
    FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES client_contacts(id) ON DELETE SET NULL,
    FOREIGN KEY (location_id) REFERENCES client_locations(id) ON DELETE SET NULL
);
```

#### client_files
```sql
CREATE TABLE client_files (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(255),
    file_path VARCHAR(500),
    file_size BIGINT,
    mime_type VARCHAR(255),
    contact_id BIGINT UNSIGNED,
    location_id BIGINT UNSIGNED,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    accessed_at TIMESTAMP,
    
    INDEX(tenant_id),
    INDEX(client_id),
    INDEX(name),
    INDEX(category),
    INDEX(mime_type),
    INDEX(tenant_id, client_id),
    
    FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES client_contacts(id) ON DELETE SET NULL,
    FOREIGN KEY (location_id) REFERENCES client_locations(id) ON DELETE SET NULL
);
```

#### client_calendar_events
```sql
CREATE TABLE client_calendar_events (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATETIME NOT NULL,
    end_date DATETIME,
    all_day BOOLEAN DEFAULT FALSE,
    location VARCHAR(255),
    contact_id BIGINT UNSIGNED,
    user_id BIGINT UNSIGNED,
    type VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    
    INDEX(tenant_id),
    INDEX(client_id),
    INDEX(start_date),
    INDEX(end_date),
    INDEX(user_id),
    INDEX(type),
    INDEX(tenant_id, client_id),
    
    FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES client_contacts(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

#### client_trips
```sql
CREATE TABLE client_trips (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    purpose TEXT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    mileage DECIMAL(10,2),
    rate_per_mile DECIMAL(5,2),
    total_cost DECIMAL(15,2),
    billable BOOLEAN DEFAULT TRUE,
    user_id BIGINT UNSIGNED,
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    
    INDEX(tenant_id),
    INDEX(client_id),
    INDEX(start_date),
    INDEX(user_id),
    INDEX(billable),
    INDEX(tenant_id, client_id),
    
    FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

#### client_recurring_tickets
```sql
CREATE TABLE client_recurring_tickets (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    frequency VARCHAR(255) NOT NULL, -- daily, weekly, monthly, quarterly, yearly
    next_run_date DATETIME NOT NULL,
    last_run_date DATETIME,
    active BOOLEAN DEFAULT TRUE,
    template_data JSON, -- Ticket template data
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    
    INDEX(tenant_id),
    INDEX(client_id),
    INDEX(next_run_date),
    INDEX(active),
    INDEX(frequency),
    INDEX(tenant_id, client_id),
    INDEX(active, next_run_date),
    
    FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (