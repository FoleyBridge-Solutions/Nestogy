# Client Domain Architecture Diagrams

## 1. Client Domain Model Relationships

```mermaid
erDiagram
    Client {
        id bigint PK
        tenant_id bigint FK
        name string
        company_name string
        email string
        phone string
        status enum
        hourly_rate decimal
        contract_start_date datetime
        contract_end_date datetime
        lead boolean
        type string
        created_at timestamp
        updated_at timestamp
        deleted_at timestamp
    }
    
    ClientContact {
        id bigint PK
        tenant_id bigint FK
        client_id bigint FK
        name string
        title string
        email string
        phone string
        primary boolean
        billing boolean
        technical boolean
        important boolean
        department string
        created_at timestamp
        updated_at timestamp
        deleted_at timestamp
    }
    
    ClientAddress {
        id bigint PK
        tenant_id bigint FK
        client_id bigint FK
        contact_id bigint FK
        name string
        address_line_1 string
        address_line_2 string
        city string
        state string
        zip_code string
        country string
        primary boolean
        created_at timestamp
        updated_at timestamp
        deleted_at timestamp
    }
    
    ClientLocation {
        id bigint PK
        tenant_id bigint FK
        client_id bigint FK
        contact_id bigint FK
        name string
        description text
        address_line_1 string
        city string
        state string
        zip_code string
        phone string
        hours string
        primary boolean
        notes text
        created_at timestamp
        updated_at timestamp
        deleted_at timestamp
    }
    
    ClientLicense {
        id bigint PK
        tenant_id bigint FK
        client_id bigint FK
        contact_id bigint FK
        location_id bigint FK
        name string
        type string
        vendor string
        license_key text
        seats integer
        cost decimal
        purchase_date date
        expiry_date date
        renewal_date date
        auto_renewal boolean
        notes text
        created_at timestamp
        updated_at timestamp
        deleted_at timestamp
    }
    
    ClientCredential {
        id bigint PK
        tenant_id bigint FK
        client_id bigint FK
        contact_id bigint FK
        location_id bigint FK
        asset_id bigint FK
        name string
        type string
        username string
        password text
        url string
        notes text
        created_at timestamp
        updated_at timestamp
        deleted_at timestamp
    }
    
    ClientRack {
        id bigint PK
        tenant_id bigint FK
        client_id bigint FK
        location_id bigint FK
        name string
        units integer
        depth string
        power_specs text
        cooling_specs text
        notes text
        created_at timestamp
        updated_at timestamp
        deleted_at timestamp
    }
    
    ClientCertificate {
        id bigint PK
        tenant_id bigint FK
        client_id bigint FK
        name string
        type string
        domain string
        issuer string
        issue_date date
        expiry_date date
        auto_renewal boolean
        cost decimal
        notes text
        created_at timestamp
        updated_at timestamp
        deleted_at timestamp
    }
    
    ClientDomain {
        id bigint PK
        tenant_id bigint FK
        client_id bigint FK
        domain_name string
        registrar string
        registration_date date
        expiry_date date
        auto_renewal boolean
        cost decimal
        nameservers json
        notes text
        created_at timestamp
        updated_at timestamp
        deleted_at timestamp
    }
    
    ClientService {
        id bigint PK
        tenant_id bigint FK
        client_id bigint FK
        location_id bigint FK
        asset_id bigint FK
        name string
        description text
        type string
        status string
        port integer
        protocol string
        notes text
        created_at timestamp
        updated_at timestamp
        deleted_at timestamp
    }
    
    ClientDocument {
        id bigint PK
        tenant_id bigint FK
        client_id bigint FK
        contact_id bigint FK
        location_id bigint FK
        name string
        description text
        category string
        tags json
        created_at timestamp
        updated_at timestamp
        deleted_at timestamp
    }
    
    ClientFile {
        id bigint PK
        tenant_id bigint FK
        client_id bigint FK
        contact_id bigint FK
        location_id bigint FK
        name string
        description text
        category string
        file_path string
        file_size bigint
        mime_type string
        created_at timestamp
        updated_at timestamp
        deleted_at timestamp
    }
    
    ClientCalendarEvent {
        id bigint PK
        tenant_id bigint FK
        client_id bigint FK
        contact_id bigint FK
        user_id bigint FK
        title string
        description text
        start_date datetime
        end_date datetime
        all_day boolean
        location string
        type string
        created_at timestamp
        updated_at timestamp
        deleted_at timestamp
    }
    
    ClientTrip {
        id bigint PK
        tenant_id bigint FK
        client_id bigint FK
        user_id bigint FK
        purpose text
        start_date date
        end_date date
        mileage decimal
        rate_per_mile decimal
        total_cost decimal
        billable boolean
        notes text
        created_at timestamp
        updated_at timestamp
        deleted_at timestamp
    }
    
    ClientRecurringTicket {
        id bigint PK
        tenant_id bigint FK
        client_id bigint FK
        name string
        description text
        frequency string
        next_run_date datetime
        last_run_date datetime
        active boolean
        template_data json
        created_at timestamp
        updated_at timestamp
        deleted_at timestamp
    }
    
    %% Relationships
    Client ||--o{ ClientContact : has
    Client ||--o{ ClientAddress : has
    Client ||--o{ ClientLocation : has
    Client ||--o{ ClientLicense : has
    Client ||--o{ ClientCredential : has
    Client ||--o{ ClientRack : has
    Client ||--o{ ClientCertificate : has
    Client ||--o{ ClientDomain : has
    Client ||--o{ ClientService : has
    Client ||--o{ ClientDocument : has
    Client ||--o{ ClientFile : has
    Client ||--o{ ClientCalendarEvent : has
    Client ||--o{ ClientTrip : has
    Client ||--o{ ClientRecurringTicket : has
    
    ClientContact ||--o{ ClientAddress : can_have
    ClientContact ||--o{ ClientLocation : can_manage
    ClientContact ||--o{ ClientLicense : can_manage
    ClientContact ||--o{ ClientCredential : can_access
    ClientContact ||--o{ ClientDocument : can_access
    ClientContact ||--o{ ClientFile : can_access
    ClientContact ||--o{ ClientCalendarEvent : participates_in
    
    ClientLocation ||--o{ ClientLicense : located_at
    ClientLocation ||--o{ ClientCredential : located_at
    ClientLocation ||--o{ ClientRack : contains
    ClientLocation ||--o{ ClientService : hosts
    ClientLocation ||--o{ ClientDocument : stored_at
    ClientLocation ||--o{ ClientFile : stored_at
```

## 2. Cross-Domain Integration Architecture

```mermaid
graph TB
    subgraph "Client Domain"
        Client[Client]
        ClientContact[ClientContact]
        ClientLocation[ClientLocation]
        ClientLicense[ClientLicense]
        ClientCredential[ClientCredential]
        ClientDocument[ClientDocument]
        ClientCalendarEvent[ClientCalendarEvent]
        ClientTrip[ClientTrip]
        ClientRecurringTicket[ClientRecurringTicket]
    end
    
    subgraph "Ticket Domain"
        Ticket[Ticket]
        TicketReply[TicketReply]
    end
    
    subgraph "Asset Domain"
        Asset[Asset]
        Network[Network]
    end
    
    subgraph "Financial Domain"
        Invoice[Invoice]
        Payment[Payment]
        Quote[Quote]
        Recurring[Recurring]
        Expense[Expense]
    end
    
    subgraph "Project Domain"
        Project[Project]
    end
    
    subgraph "User Domain"
        User[User]
        Company[Company]
    end
    
    %% Client Domain Relationships
    Client --> ClientContact
    Client --> ClientLocation
    Client --> ClientLicense
    Client --> ClientCredential
    Client --> ClientDocument
    Client --> ClientCalendarEvent
    Client --> ClientTrip
    Client --> ClientRecurringTicket
    
    %% Cross-Domain Relationships
    Client --> Ticket : "has many"
    Client --> Asset : "owns"
    Client --> Invoice : "receives"
    Client --> Payment : "makes"
    Client --> Quote : "receives"
    Client --> Recurring : "subscribed to"
    Client --> Expense : "related to"
    Client --> Project : "has"
    
    %% Tenant Relationships
    Company --> Client : "tenant isolation"
    User --> ClientCalendarEvent : "schedules"
    User --> ClientTrip : "takes"
    
    %% Asset Integration
    Asset --> ClientLocation : "located at"
    Asset --> ClientCredential : "has credentials"
    Network --> ClientLocation : "deployed at"
    
    %% Ticket Integration
    Ticket --> ClientContact : "reported by"
    Ticket --> ClientLocation : "at location"
    Ticket --> Asset : "for asset"
    ClientRecurringTicket --> Ticket : "generates"
```

## 3. Service Layer Architecture

```mermaid
graph TB
    subgraph "Client Controllers"
        ClientController[ClientController]
        ClientDashboardController[ClientDashboardController]
        ClientContactController[ClientContactController]
        ClientLocationController[ClientLocationController]
        ClientLicenseController[ClientLicenseController]
        ClientCredentialController[ClientCredentialController]
        ClientDocumentController[ClientDocumentController]
        ClientCalendarController[ClientCalendarController]
        ClientTripController[ClientTripController]
        ClientRecurringTicketController[ClientRecurringTicketController]
    end
    
    subgraph "Client Services"
        ClientService[ClientService]
        ClientDashboardService[ClientDashboardService]
        ClientContactService[ClientContactService]
        ClientLocationService[ClientLocationService]
        ClientLicenseService[ClientLicenseService]
        ClientCredentialService[ClientCredentialService]
        ClientDocumentService[ClientDocumentService]
        ClientCalendarService[ClientCalendarService]
        ClientTripService[ClientTripService]
        ClientRecurringTicketService[ClientRecurringTicketService]
        ClientNotificationService[ClientNotificationService]
        ClientReportService[ClientReportService]
        ClientExportService[ClientExportService]
        ClientImportService[ClientImportService]
    end
    
    subgraph "Cross-Domain Services"
        TicketService[TicketService]
        AssetService[AssetService]
        FinancialService[FinancialService]
        ProjectService[ProjectService]
        NotificationService[NotificationService]
    end
    
    subgraph "Infrastructure"
        EventDispatcher[EventDispatcher]
        Cache[Cache]
        Queue[Queue]
        Storage[Storage]
        Mail[Mail]
    end
    
    %% Controller to Service relationships
    ClientController --> ClientService
    ClientDashboardController --> ClientDashboardService
    ClientContactController --> ClientContactService
    ClientLocationController --> ClientLocationService
    ClientLicenseController --> ClientLicenseService
    ClientCredentialController --> ClientCredentialService
    ClientDocumentController --> ClientDocumentService
    ClientCalendarController --> ClientCalendarService
    ClientTripController --> ClientTripService
    ClientRecurringTicketController --> ClientRecurringTicketService
    
    %% Service to Service relationships
    ClientDashboardService --> ClientService
    ClientDashboardService --> FinancialService
    ClientDashboardService --> AssetService
    ClientDashboardService --> ProjectService
    
    ClientRecurringTicketService --> TicketService
    ClientNotificationService --> NotificationService
    ClientLicenseService --> ClientNotificationService
    
    %% Infrastructure relationships
    ClientService --> EventDispatcher
    ClientService --> Cache
    ClientNotificationService --> Mail
    ClientRecurringTicketService --> Queue
    ClientDocumentService --> Storage
```

## 4. Security and Permission Flow

```mermaid
graph TB
    subgraph "Authentication Layer"
        User[User]
        Company[Company/Tenant]
        Role[Role Level]
    end
    
    subgraph "Authorization Layer"
        BelongsToTenant[BelongsToTenant Trait]
        ClientPolicy[ClientPolicy]
        ClientCredentialPolicy[ClientCredentialPolicy]
        ClientFeatureMiddleware[ClientFeatureMiddleware]
    end
    
    subgraph "Data Access Layer"
        TenantScope[Tenant Global Scope]
        EncryptedFields[Encrypted Fields]
        AuditLog[Audit Logging]
    end
    
    subgraph "Client Domain Models"
        ClientModel[Client Models]
        SensitiveData[Sensitive Data]
    end
    
    %% Authentication Flow
    User --> Role
    User --> Company
    
    %% Authorization Flow
    User --> ClientPolicy
    Role --> ClientFeatureMiddleware
    ClientPolicy --> ClientCredentialPolicy
    
    %% Data Access Flow
    Company --> BelongsToTenant
    BelongsToTenant --> TenantScope
    TenantScope --> ClientModel
    
    %% Security Features
    SensitiveData --> EncryptedFields
    ClientModel --> AuditLog
    
    %% Permission Levels
    Role -.->|"Role 1: Accountant"| FinancialAccess[Financial Access]
    Role -.->|"Role 2: Tech"| TechnicalAccess[Technical Access]
    Role -.->|"Role 3: Admin"| FullAccess[Full Access]
    
    FinancialAccess --> ClientModel
    TechnicalAccess --> SensitiveData
    FullAccess --> ClientModel
    FullAccess --> SensitiveData
```

## 5. Data Flow and Event Architecture

```mermaid
sequenceDiagram
    participant User
    participant Controller
    participant Service
    participant Model
    participant EventDispatcher
    participant Queue
    participant ExternalService
    
    %% Create License Example
    User->>Controller: POST /clients/1/licenses
    Controller->>Service: createLicense(client, data)
    Service->>Model: create(data)
    Model-->>Service: license
    Service->>EventDispatcher: dispatch(LicenseCreated)
    Service-->>Controller: license
    Controller-->>User: 201 Created
    
    %% Event Processing
    EventDispatcher->>Queue: queue notification job
    Queue->>ExternalService: send expiry notification
    
    %% Recurring Ticket Processing
    Note over Queue: Scheduled Job
    Queue->>Service: processRecurringTickets()
    Service->>Model: find due tickets
    Model-->>Service: recurring tickets
    Service->>ExternalService: create tickets
    Service->>Model: update next run date
```

## 6. Database Schema Overview

```mermaid
erDiagram
    companies {
        id bigint PK
        name string
        email string
        created_at timestamp
    }
    
    users {
        id bigint PK
        company_id bigint FK
        name string
        email string
        role integer
        created_at timestamp
    }
    
    clients {
        id bigint PK
        tenant_id bigint FK
        name string
        company_name string
        email string
        status enum
        created_at timestamp
    }
    
    %% New Client Domain Tables
    client_contacts {
        id bigint PK
        tenant_id bigint FK
        client_id bigint FK
        name string
        email string
        primary boolean
        billing boolean
        technical boolean
    }
    
    client_locations {
        id bigint PK
        tenant_id bigint FK
        client_id bigint FK
        name string
        address_line_1 string
        city string
        state string
        primary boolean
    }
    
    client_licenses {
        id bigint PK
        tenant_id bigint FK
        client_id bigint FK
        name string
        vendor string
        expiry_date date
        cost decimal
    }
    
    client_credentials {
        id bigint PK
        tenant_id bigint FK
        client_id bigint FK
        name string
        username string
        password text
        url string
    }
    
    %% Cross-Domain Tables
    tickets {
        id bigint PK
        company_id bigint FK
        client_id bigint FK
        subject string
        status string
        created_at timestamp
    }
    
    assets {
        id bigint PK
        company_id bigint FK
        client_id bigint FK
        name string
        type string
        status string
    }
    
    invoices {
        id bigint PK
        company_id bigint FK
        client_id bigint FK
        amount decimal
        status string
        due_date date
    }
    
    %% Relationships
    companies ||--o{ users : employs
    companies ||--o{ clients : serves
    clients ||--o{ client_contacts : has
    clients ||--o{ client_locations : has
    clients ||--o{ client_licenses : owns
    clients ||--o{ client_credentials : stores
    clients ||--o{ tickets : receives_support
    clients ||--o{ assets : owns
    clients ||--o{ invoices : receives
```

## 7. Implementation Phases

```mermaid
gantt
    title Client Domain Expansion Implementation
    dateFormat  YYYY-MM-DD
    section Phase 1: Foundation
    Database Migrations    :p1-db, 2025-01-01, 1w
    Core Models           :p1-models, after p1-db, 1w
    Basic CRUD Operations :p1-crud, after p1-models, 1w
    
    section Phase 2: Integration
    Cross-Domain Integration :p2-integration, after p1-crud, 1w
    Security Implementation  :p2-security, after p2-integration, 1w
    API Development         :p2-api, after p2-security, 1w
    
    section Phase 3: Advanced Features
    Dashboard & Reporting   :p3-dashboard, after p2-api, 1w
    Automation Features     :p3-automation, after p3-dashboard, 1w
    Import/Export          :p3-import, after p3-automation, 1w
    
    section Phase 4: UI/UX & Testing
    Frontend Development   :p4-frontend, after p3-import, 1w
    Testing               :p4-testing, after p4-frontend, 1w
    Documentation         :p4-docs, after p4-testing, 1w
```

This comprehensive set of diagrams provides a visual representation of the entire Client domain expansion architecture, showing relationships, data flow, security considerations, and implementation phases. The diagrams help stakeholders understand the complexity and scope of the expansion while providing clear guidance for implementation.