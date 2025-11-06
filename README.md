# Nestogy ERP - MSP Management Platform

![Laravel](https://img.shields.io/badge/Laravel-12.36-red.svg)
![PHP](https://img.shields.io/badge/PHP-8.4-purple.svg)
![Livewire](https://img.shields.io/badge/Livewire-3.6-pink.svg)
![Tailwind](https://img.shields.io/badge/Tailwind-4.0-blue.svg)
![License](https://img.shields.io/badge/license-Proprietary-green.svg)

## 🎯 About Nestogy

**Nestogy ERP** is a comprehensive enterprise resource planning system specifically designed for Managed Service Providers (MSPs). Built with modern technologies and following Domain-Driven Design principles, it provides a complete suite of tools for managing clients, tickets, projects, finances, and more.

### Current Implementation Status

The platform is actively in development with the following domains implemented:

- **20 Active Domains** with 400+ domain-specific PHP classes
- **149 Database Migrations** and seeders defining comprehensive data structures  
- **Active Development** (November 2025) with continuous improvements
- **Session-Based Architecture** for clean client context management
- **Flux UI Pro v2.6** for modern, consistent UI components
- **PostgreSQL Primary** with MariaDB/MySQL support

## 🏗️ Architecture

### Domain-Driven Design Structure

The application follows DDD principles with 20 bounded contexts:

- **Asset** - Equipment and inventory management with RMM integration
- **Client** - Customer relationship management with contacts, locations, and portal access
- **Collections** - Collections management for unpaid invoices
- **Company** - Multi-tenant company management and settings
- **Contract** - Service agreements, SLAs, and contract lifecycle management
- **Core** - Core system functionality including navigation and settings
- **Email** - Full email system with accounts, messages, folders, attachments, and signatures
- **Financial** - Billing, invoicing, payments, credit notes, and analytics
- **HR** - Human resources, employee management, and break tracking
- **Integration** - Third-party service connectors (Tactical RMM, Stripe, etc.)
- **Knowledge** - Documentation and knowledge base management
- **Lead** - Sales pipeline and lead management
- **Marketing** - Campaign and communication tools
- **PhysicalMail** - Physical mail integration and tracking
- **Product** - Service catalog and product management  
- **Project** - Project management with tasks and milestones
- **Report** - Analytics, reporting, and business intelligence
- **Security** - Authentication, authorization, and access control
- **Tax** - Tax calculation and jurisdiction management
- **Ticket** - Help desk with SLA management, workflows, and time tracking

### Technology Stack

- **Backend**: Laravel 12.36.1, PHP 8.4.13
- **Frontend**: Livewire 3.6.4, Alpine.js 3.14, Tailwind CSS 4
- **UI Components**: Flux UI Pro v2.6 (Commercial License)
- **Database**: PostgreSQL 13+ (primary), MySQL 8.0+/MariaDB 10.5+ (supported)
- **Queue**: Redis, Database
- **Real-time**: Laravel Reverb 1.6 (WebSocket server)
- **Authentication**: Laravel Fortify with 2FA support (Google2FA)
- **Authorization**: Silber Bouncer v1.0 for roles and permissions
- **Session Management**: Custom NavigationService for client context
- **File Processing**: Intervention Image 3.3, Spatie Media Library 11.1
- **PDF Generation**: Spatie Laravel PDF 1.6, DomPDF 3.1
- **Testing**: PHPUnit 11.5, Paratest 7.8

## ✨ Implemented Features

### Client Management
- ✅ Company hierarchy with parent/child relationships
- ✅ Multiple locations per client with full address management
- ✅ Contact management with roles, departments, and social media profiles
- ✅ Communication logs for tracking all client interactions
- ✅ Session-based client switching for clean context management
- ✅ Client notes and activity timeline tracking
- ✅ Technician assignments with skill matching

### Ticketing System
- ✅ Comprehensive ticket management with priorities and statuses
- ✅ SLA tracking and compliance monitoring
- ✅ Workflow automation with customizable rules
- ✅ Time tracking and timer integration
- ✅ Recurring ticket templates
- ✅ Priority queue management
- ✅ Calendar integration for scheduling
- ✅ Ticket templates for common issues

### Financial Management
- ✅ Invoice generation with PDF export
- ✅ Multiple payment methods and gateways
- ✅ Contract-based billing with automated invoicing
- ✅ Tax configuration and jurisdiction management
- ✅ Financial analytics and reporting
- ✅ Integration with accounting systems
- ✅ Billing calculations and rate management
- ✅ Revenue tracking and forecasting

### Email System
- ✅ Multi-account email management
- ✅ IMAP/SMTP integration for email services
- ✅ Email folders and organization
- ✅ Attachment handling and storage
- ✅ Email signatures with templates
- ✅ Email-to-ticket conversion
- ✅ Communication log integration
- ✅ Company-wide email account management

### Contract Management
- ✅ Comprehensive contract lifecycle management
- ✅ Contract components and configurations
- ✅ Amendments and version tracking
- ✅ Approval workflows with multi-step processes
- ✅ Contract milestones and deliverables
- ✅ Asset and contact assignments
- ✅ Billing integration for contract-based invoicing
- ✅ Contract templates and clauses library

### Project Management
- ✅ Project creation and tracking
- ✅ Task management with assignments
- ✅ Project milestones and deadlines
- ✅ Resource allocation and planning
- ✅ Project templates for common workflows
- ✅ Time and budget tracking
- ✅ Project dashboard with real-time updates
- ✅ Integration with ticketing system

### Dashboard & Analytics
- ✅ Customizable dashboard widgets
- ✅ Quick actions for common tasks
- ✅ Real-time activity feeds
- ✅ Performance metrics and KPIs
- ✅ Report generation and export
- ✅ Data visualization with charts
- ✅ Activity logging and audit trails
- ✅ Custom report builder

### Additional Components

- **Authentication & Security**: Multi-factor authentication, role-based access control, audit logging
- **Command Palette**: Quick navigation and action execution
- **Client Switcher**: Session-based client context management
- **Activity Timelines**: Comprehensive activity tracking for all entities
- **Navigation Timer**: Time tracking integration throughout the application
- **Livewire Components**: 20+ interactive components for real-time updates
- **PDF Generation**: Invoice and report generation with customizable templates
- **Email Queue System**: Background email processing with retry mechanisms

## 📖 Documentation

- [Quick Start Guide](docs/QUICKSTART.md) - Get up and running quickly
- [Development Guide](docs/DEVELOPMENT.md) - Development environment and best practices
- [Configuration Guide](docs/CONFIGURATION.md) - System configuration
- [Deployment Guide](docs/DEPLOYMENT.md) - Production deployment
- [Testing Guide](docs/TESTING.md) - Testing practices and coverage
- [Repository Guidelines](docs/REPOSITORY_GUIDELINES.md)
- [Antipatterns](docs/antipatterns.md) - Code quality tracking
- [VoIP Tax System](docs/voip-tax-system.md) - Domain-specific documentation
- [Service Management](docs/SERVICE_MANAGEMENT_SYSTEM.md)

## 🚀 Getting Started

### Prerequisites

- PHP 8.4 or higher
- Composer 2.x
- Node.js 18.x or higher
- PostgreSQL 13+ (recommended) or MySQL 8.0+/MariaDB 10.5+
- Redis (recommended, for caching and queues)
- Supervisor (recommended, for queue workers)

### Installation

1. **Clone the repository**
```bash
git clone https://github.com/FoleyBridge-Solutions/Nestogy.git
cd nestogy
```

2. **Install dependencies**
```bash
composer install
npm install
```

3. **Environment setup**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Database setup**
```bash
php artisan migrate --seed
```

5. **Build assets**
```bash
npm run build
```

6. **Start development server**
```bash
php artisan serve
npm run dev  # In another terminal for hot-reloading
```

## 📚 Development Commands

### Testing
```bash
php artisan test                    # Run all tests
php artisan test --filter TestName  # Run specific test
composer test                        # Clear config and run tests
```

### Code Quality
```bash
./vendor/bin/pint    # Laravel code formatter
php artisan pint     # Alternative Pint command
```

### Database Management
```bash
php artisan migrate:fresh --seed    # Reset database with seeders
php artisan migrate:rollback        # Rollback last migration
php artisan migrate:status          # Check migration status
php artisan db:seed                 # Run database seeders
```

### Cache Management
```bash
php artisan config:clear    # Clear config cache
php artisan cache:clear     # Clear application cache
php artisan route:clear     # Clear route cache
php artisan view:clear      # Clear compiled views
php artisan optimize:clear  # Clear all caches
```

### Queue Management
```bash
php artisan queue:work --queue=emails,default  # Process queues
php artisan queue:failed                       # List failed jobs
php artisan queue:retry all                    # Retry failed jobs
```

## 🏗️ Project Structure

```
nestogy/
├── app/
│   ├── Domains/           # Domain-driven design bounded contexts
│   │   ├── Asset/
│   │   ├── Client/
│   │   ├── Contract/
│   │   ├── Email/
│   │   ├── Financial/
│   │   ├── Integration/
│   │   ├── Knowledge/
│   │   ├── Lead/
│   │   ├── Marketing/
│   │   ├── Product/
│   │   ├── Project/
│   │   ├── Report/
│   │   ├── Security/
│   │   └── Ticket/
│   ├── Livewire/          # Livewire components
│   ├── Models/            # Eloquent models
│   ├── Policies/          # Authorization policies
│   ├── Providers/         # Service providers
│   ├── Services/          # Application services
│   └── View/              # View components
├── database/
│   ├── migrations/        # 163+ database migrations
│   └── seeders/           # Database seeders
├── resources/
│   ├── views/             # Blade templates
│   ├── js/                # JavaScript assets
│   └── css/               # Stylesheets
├── routes/                # Application routes
└── tests/                 # Test suites
```

## 🔒 Security

- **Multi-tenancy**: Company-based data isolation
- **Authentication**: Laravel Fortify with 2FA
- **Authorization**: Silber Bouncer roles and permissions
- **Session Management**: Secure session-based client context
- **Audit Logging**: Comprehensive activity tracking
- **Input Validation**: Request validation classes
- **CSRF Protection**: Laravel's built-in CSRF tokens

## 🤝 Contributing

This is a proprietary project for FoleyBridge Solutions. For contribution guidelines, please contact the development team.

## 📝 License

This project is proprietary software owned by FoleyBridge Solutions. All rights reserved.

## 👥 Team

Developed and maintained by the FoleyBridge Solutions development team.

## 📞 Support

For support and questions, please contact the development team through internal channels.


