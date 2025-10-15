# Client Domain Expansion Architecture Plan - Part 2

## Database Schema Requirements (Continued)

### Completing client_recurring_tickets table
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
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);
```

### Migration Strategy for Existing Tables

#### Migrating from Legacy `locations` to `client_locations`
```php
// Migration to move data from locations to client_locations
public function up()
{
    // Create new client_locations table
    Schema::create('client_locations', function (Blueprint $table) {
        // ... table definition
    });
    
    // Migrate data
    DB::statement('
        INSERT INTO client_locations (
            tenant_id, client_id, name, description, address_line_1, 
            city, state, zip_code, country, phone, hours, photo, 
            primary, notes, contact_id, created_at, updated_at, accessed_at
        )
        SELECT 
            company_id as tenant_id, client_id, name, description, address,
            city, state, zip, country, phone, hours, photo,
            primary, notes, contact_id, created_at, updated_at, accessed_at
        FROM locations
        WHERE client_id IS NOT NULL
    ');
    
    // Update foreign key references in other tables
    // Update assets table to reference client_locations
    Schema::table('assets', function (Blueprint $table) {
        $table->renameColumn('location_id', 'legacy_location_id');
        $table->unsignedBigInteger('client_location_id')->nullable();
    });
    
    // Migrate location references
    DB::statement('
        UPDATE assets a
        JOIN client_locations cl ON a.legacy_location_id = cl.id
        SET a.client_location_id = cl.id
        WHERE a.legacy_location_id IS NOT NULL
    ');
}
```

## Integration Points with Other Domains

### 1. Ticket Domain Integration

#### Events and Listeners
```php
// Client Domain Events
namespace App\Domains\Client\Events;

class ClientCreated extends Event
{
    public function __construct(public Client $client) {}
}

class ClientUpdated extends Event
{
    public function __construct(public Client $client, public array $changes) {}
}

class ClientDeleted extends Event
{
    public function __construct(public int $clientId, public int $tenantId) {}
}

// Ticket Domain Listeners
namespace App\Domains\Ticket\Listeners;

class CreateWelcomeTicket
{
    public function handle(ClientCreated $event)
    {
        // Create welcome/onboarding ticket for new client
        $this->ticketService->createWelcomeTicket($event->client);
    }
}
```

#### Recurring Ticket Integration
```php
// Service for managing recurring tickets
class ClientRecurringTicketService
{
    public function processRecurringTickets()
    {
        $dueTickets = ClientRecurringTicket::where('active', true)
            ->where('next_run_date', '<=', now())
            ->get();
            
        foreach ($dueTickets as $recurringTicket) {
            $this->createTicketFromTemplate($recurringTicket);
            $this->updateNextRunDate($recurringTicket);
        }
    }
    
    private function createTicketFromTemplate(ClientRecurringTicket $recurringTicket)
    {
        $ticketData = array_merge($recurringTicket->template_data, [
            'client_id' => $recurringTicket->client_id,
            'created_by' => 1, // System user
            'source' => 'Recurring',
        ]);
        
        // Use Ticket domain service to create ticket
        app(TicketService::class)->createTicket($ticketData);
    }
}
```

### 2. Asset Domain Integration

#### Asset-Client Relationships
```php
// Enhanced Asset model relationships
class Asset extends Model
{
    public function client()
    {
        return $this->belongsTo(\App\Domains\Client\Models\Client::class);
    }
    
    public function clientLocation()
    {
        return $this->belongsTo(\App\Domains\Client\Models\ClientLocation::class);
    }
    
    public function clientContact()
    {
        return $this->belongsTo(\App\Domains\Client\Models\ClientContact::class, 'contact_id');
    }
}

// Client Asset Service
class ClientAssetService
{
    public function getClientAssetSummary(Client $client): array
    {
        return [
            'total_assets' => $client->assets()->count(),
            'by_type' => $client->assets()->groupBy('type')->map->count(),
            'by_status' => $client->assets()->groupBy('status')->map->count(),
            'warranty_expiring' => $client->assets()
                ->where('warranty_expire', '<=', now()->addDays(30))
                ->count(),
        ];
    }
}
```

### 3. Financial Domain Integration

#### Invoice-Client Relationships
```php
// Enhanced Invoice model
class Invoice extends Model
{
    public function client()
    {
        return $this->belongsTo(\App\Domains\Client\Models\Client::class);
    }
    
    public function clientContact()
    {
        return $this->belongsTo(\App\Domains\Client\Models\ClientContact::class, 'contact_id');
    }
}

// Client Financial Service
class ClientFinancialService
{
    public function getClientFinancialSummary(Client $client): array
    {
        return [
            'total_invoiced' => $client->invoices()->sum('amount'),
            'total_paid' => $client->payments()->sum('amount'),
            'outstanding_balance' => $client->invoices()
                ->whereIn('status', ['sent', 'overdue'])
                ->sum('amount'),
            'overdue_amount' => $client->invoices()
                ->where('status', 'overdue')
                ->sum('amount'),
        ];
    }
}
```

### 4. Project Domain Integration

#### Project-Client Relationships
```php
// Enhanced Project model
class Project extends Model
{
    public function client()
    {
        return $this->belongsTo(\App\Domains\Client\Models\Client::class);
    }
    
    public function clientContacts()
    {
        return $this->belongsToMany(\App\Domains\Client\Models\ClientContact::class, 'project_contacts');
    }
}

// Client Project Service
class ClientProjectService
{
    public function getClientProjectSummary(Client $client): array
    {
        return [
            'active_projects' => $client->projects()->whereNull('completed_at')->count(),
            'completed_projects' => $client->projects()->whereNotNull('completed_at')->count(),
            'overdue_projects' => $client->projects()
                ->whereNull('completed_at')
                ->where('due', '<', now())
                ->count(),
        ];
    }
}
```

## Multi-Tenant Security and Permissions Strategy

### 1. Tenant Isolation

#### Enhanced BelongsToTenant Trait
```php
trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        // Automatically set tenant_id when creating
        static::creating(function ($model) {
            if (empty($model->tenant_id) && auth()->check()) {
                $model->tenant_id = auth()->user()->company_id;
            }
        });

        // Add global scope to filter by tenant
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', auth()->user()->company_id);
            }
        });
    }
    
    // Enhanced scope for cross-tenant operations (admin only)
    public function scopeWithoutTenantScope($query)
    {
        return $query->withoutGlobalScope('tenant');
    }
    
    // Scope for specific tenant (admin only)
    public function scopeForTenant($query, $tenantId = null)
    {
        if ($tenantId === null) {
            $tenantId = auth()->check() ? auth()->user()->company_id : null;
        }
        
        if ($tenantId) {
            return $query->withoutGlobalScope('tenant')->where('tenant_id', $tenantId);
        }
        
        return $query;
    }
}
```

### 2. Role-Based Access Control

#### Client Domain Policies
```php
// ClientPolicy
class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role >= 1; // All roles can view clients
    }
    
    public function view(User $user, Client $client): bool
    {
        return $user->company_id === $client->tenant_id && $user->role >= 1;
    }
    
    public function create(User $user): bool
    {
        return $user->role >= 2; // Tech and Admin can create
    }
    
    public function update(User $user, Client $client): bool
    {
        return $user->company_id === $client->tenant_id && $user->role >= 2;
    }
    
    public function delete(User $user, Client $client): bool
    {
        return $user->company_id === $client->tenant_id && $user->role >= 3; // Admin only
    }
    
    // Sensitive data access
    public function viewCredentials(User $user, Client $client): bool
    {
        return $user->company_id === $client->tenant_id && $user->role >= 2;
    }
    
    public function viewFinancials(User $user, Client $client): bool
    {
        return $user->company_id === $client->tenant_id && 
               ($user->role >= 3 || $user->role === 1); // Admin or Accountant
    }
}

// ClientCredentialPolicy
class ClientCredentialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role >= 2; // Tech and Admin only
    }
    
    public function view(User $user, ClientCredential $credential): bool
    {
        return $user->company_id === $credential->tenant_id && $user->role >= 2;
    }
    
    public function create(User $user): bool
    {
        return $user->role >= 2;
    }
    
    public function update(User $user, ClientCredential $credential): bool
    {
        return $user->company_id === $credential->tenant_id && $user->role >= 2;
    }
    
    public function delete(User $user, ClientCredential $credential): bool
    {
        return $user->company_id === $credential->tenant_id && $user->role >= 3;
    }
}
```

#### Middleware for Feature Access
```php
// ClientFeatureAccessMiddleware
class ClientFeatureAccessMiddleware
{
    public function handle(Request $request, Closure $next, string $feature)
    {
        $user = auth()->user();
        
        $featurePermissions = [
            'credentials' => 2, // Tech and Admin
            'financials' => [1, 3], // Accountant and Admin
            'assets' => 2, // Tech and Admin
            'documents' => 1, // All roles
            'calendar' => 1, // All roles
        ];
        
        $requiredRole = $featurePermissions[$feature] ?? 1;
        
        if (is_array($requiredRole)) {
            if (!in_array($user->role, $requiredRole)) {
                abort(403, 'Insufficient permissions for this feature');
            }
        } else {
            if ($user->role < $requiredRole) {
                abort(403, 'Insufficient permissions for this feature');
            }
        }
        
        return $next($request);
    }
}
```

### 3. Data Encryption and Security

#### Sensitive Data Handling
```php
// Encrypted fields trait
trait HasEncryptedFields
{
    protected $encryptedFields = [];
    
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);
        
        if (in_array($key, $this->encryptedFields) && !is_null($value)) {
            try {
                return decrypt($value);
            } catch (DecryptException $e) {
                return $value; // Return as-is if decryption fails
            }
        }
        
        return $value;
    }
    
    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->encryptedFields) && !is_null($value)) {
            $value = encrypt($value);
        }
        
        return parent::setAttribute($key, $value);
    }
}

// Usage in ClientCredential model
class ClientCredential extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, HasEncryptedFields;
    
    protected $encryptedFields = ['password', 'license_key'];
}
```

## Implementation Roadmap

### Phase 1: Foundation (Weeks 1-2)
1. **Database Migrations**
   - Create all new client domain tables
   - Migrate data from legacy tables
   - Update foreign key relationships

2. **Core Models**
   - Implement all new client domain models
   - Add relationships to existing Client model
   - Implement traits and base functionality

3. **Basic CRUD Operations**
   - Create basic controllers for each feature
   - Implement basic service layer
   - Create form requests and resources

### Phase 2: Integration (Weeks 3-4)
1. **Cross-Domain Integration**
   - Implement event system
   - Create domain service integrations
   - Update existing domain models with client relationships

2. **Security Implementation**
   - Implement policies for all new models
   - Create feature-based middleware
   - Implement data encryption

3. **API Development**
   - Create API endpoints for all features
   - Implement API resources and transformers
   - Add API documentation

### Phase 3: Advanced Features (Weeks 5-6)
1. **Dashboard and Reporting**
   - Implement client dashboard
   - Create reporting services
   - Add data visualization components

2. **Automation Features**
   - Implement recurring ticket system
   - Create notification system
   - Add automated workflows

3. **Import/Export**
   - Create data import/export services
   - Implement bulk operations
   - Add data validation and cleanup

### Phase 4: UI/UX and Testing (Weeks 7-8)
1. **Frontend Development**
   - Create Vue.js components for all features
   - Implement responsive design
   - Add interactive features

2. **Testing**
   - Write unit tests for all services
   - Create integration tests
   - Implement feature tests

3. **Documentation**
   - Create user documentation
   - Write API documentation
   - Create deployment guides

## Performance Considerations

### 1. Database Optimization

#### Indexing Strategy
```sql
-- Critical indexes for performance
CREATE INDEX idx_client_tenant_status ON clients(tenant_id, status);
CREATE INDEX idx_client_contacts_client_primary ON client_contacts(client_id, primary);
CREATE INDEX idx_client_licenses_expiry ON client_licenses(expiry_date) WHERE expiry_date IS NOT NULL;
CREATE INDEX idx_client_certificates_expiry ON client_certificates(expiry_date) WHERE expiry_date IS NOT NULL;
CREATE INDEX idx_client_domains_expiry ON client_domains(expiry_date) WHERE expiry_date IS NOT NULL;
CREATE INDEX idx_client_calendar_events_date_range ON client_calendar_events(client_id, start_date, end_date);
CREATE INDEX idx_client_recurring_tickets_next_run ON client_recurring_tickets(active, next_run_date);
```

#### Query Optimization
```php
// Eager loading relationships
class ClientService
{
    public function getClientWithRelations(int $clientId): Client
    {
        return Client::with([
            'contacts' => function ($query) {
                $query->where('primary', true)->orWhere('important', true);
            },
            'addresses' => function ($query) {
                $query->where('primary', true);
            },
            'licenses' => function ($query) {
                $query->where('expiry_date', '>', now());
            }
        ])->findOrFail($clientId);
    }
}
```

### 2. Caching Strategy

#### Model Caching
```php
// Cacheable trait for models
trait Cacheable
{
    protected $cachePrefix = '';
    protected $cacheTags = [];
    
    public function getCacheKey(string $suffix = ''): string
    {
        return $this->cachePrefix . $this->getTable() . ':' . $this->id . ($suffix ? ':' . $suffix : '');
    }
    
    public function remember(string $key, callable $callback, int $ttl = 3600)
    {
        return Cache::tags($this->cacheTags)->remember($key, $ttl, $callback);
    }
    
    public function forgetCache(): void
    {
        Cache::tags($this->cacheTags)->flush();
    }
}

// Usage in Client model
class Client extends Model
{
    use Cacheable;
    
    protected $cachePrefix = 'client:';
    protected $cacheTags = ['clients'];
    
    public function getFinancialSummaryAttribute(): array
    {
        return $this->remember(
            $this->getCacheKey('financial_summary'),
            fn() => app(ClientFinancialService::class)->getClientFinancialSummary($this),
            1800 // 30 minutes
        );
    }
}
```

### 3. Background Job Processing

#### Recurring Ticket Processing
```php
// Job for processing recurring tickets
class ProcessRecurringTicketsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function handle(ClientRecurringTicketService $service): void
    {
        $service->processRecurringTickets();
    }
}

// Schedule in Kernel.php
protected function schedule(Schedule $schedule): void
{
    $schedule->job(new ProcessRecurringTicketsJob)->hourly();
}
```

#### Notification Processing
```php
// Job for sending expiry notifications
class SendExpiryNotificationsJob implements ShouldQueue
{
    public function handle(): void
    {
        // Send license expiry notifications
        $expiringLicenses = ClientLicense::where('expiry_date', '<=', now()->addDays(30))
            ->where('expiry_date', '>=', now())
            ->with('client')
            ->get();
            
        foreach ($expiringLicenses as $license) {
            Mail::to($license->client->email)
                ->send(new LicenseExpiryNotification($license));
        }
        
        // Send certificate expiry notifications
        $expiringCertificates = ClientCertificate::where('expiry_date', '<=', now()->addDays(30))
            ->where('expiry_date', '>=', now())
            ->with('client')
            ->get();
            
        foreach ($expiringCertificates as $certificate) {
            Mail::to($certificate->client->email)
                ->send(new CertificateExpiryNotification($certificate));
        }
    }
}
```

## Validation and Testing Strategy

### 1. Model Validation

#### Form Requests
```php
// StoreLicenseRequest
class StoreLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ClientLicense::class);
    }
    
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'vendor' => 'nullable|string|max:255',
            'license_key' => 'nullable|string',
            'seats' => 'nullable|integer|min:1',
            'cost' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:purchase_date',
            'renewal_date' => 'nullable|date',
            'auto_renewal' => 'boolean',
            'notes' => 'nullable|string',
            'contact_id' => 'nullable|exists:client_contacts,id',
            'location_id' => 'nullable|exists:client_locations,id',
        ];
    }
}
```

### 2. Unit Testing

#### Service Tests
```php
// ClientLicenseServiceTest
class ClientLicenseServiceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_create_license_for_client()
    {
        $client = Client::factory()->create();
        $licenseData = [
            'name' => 'Microsoft Office 365',
            'type' => 'Software',
            'vendor' => 'Microsoft',
            'seats' => 10,
            'cost' => 120.00,
            'expiry_date' => now()->addYear(),
        ];
        
        $service = app(ClientLicenseService::class);
        $license = $service->createLicense($client, $licenseData);
        
        $this->assertInstanceOf(ClientLicense::class, $license);
        $this->assertEquals($client->id, $license->client_id);
        $this->assertEquals('Microsoft Office 365', $license->name);
    }
    
    public function test_can_get_expiring_licenses()
    {
        $client = Client::factory()->create();
        
        // Create expiring license
        ClientLicense::factory()->create([
            'client_id' => $client->id,
            'expiry_date' => now()->addDays(15),
        ]);
        
        // Create non-expiring license
        ClientLicense::factory()->create([
            'client_id' => $client->id,
            'expiry_date' => now()->addYear(),
        ]);
        
        $service = app(ClientLicenseService::class);
        $expiringLicenses = $service->getExpiringLicenses($client, 30);
        
        $this->assertCount(1, $expiringLicenses);
    }
}
```

### 3. Integration Testing

#### API Tests
```php
// ClientLicenseApiTest
class ClientLicenseApiTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_list_client_licenses()
    {
        $user = User::factory()->create(['role' => 2]); // Tech role
        $client = Client::factory()->create(['tenant_id' => $user->company_id]);
        ClientLicense::factory()->count(3)->create(['client_id' => $client->id]);
        
        $response = $this->actingAs($user)
            ->getJson("/api/clients/{$client->id}/licenses");
            
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
    
    public function test_cannot_access_other_tenant_licenses()
    {
        $user = User::factory()->create(['role' => 2]);
        $otherClient = Client::factory()->create(['tenant_id' => 999]);
        
        $response = $this->actingAs($user)
            ->getJson("/api/clients/{$otherClient->id}/licenses");
            
        $response->assertStatus(404);
    }
}
```

## Conclusion

This comprehensive architectural plan provides a robust foundation for expanding the Client domain to support all required MSP ERP features. The design emphasizes:

1. **Scalability**: Modular architecture that can grow with business needs
2. **Security**: Multi-tenant isolation and role-based access control
3. **Performance**: Optimized database design and caching strategies
4. **Maintainability**: Clean code architecture with clear separation of concerns
5. **Integration**: Seamless integration with existing domains
6. **Flexibility**: Extensible design that can accommodate future requirements

The phased implementation approach ensures manageable development cycles while delivering value incrementally. The comprehensive testing strategy ensures reliability and maintainability of the expanded system.

This architecture will transform the basic Client domain into a comprehensive client management system that meets all MSP ERP requirements while maintaining the high standards of the existing codebase.