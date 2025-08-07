<p align="center">
  <img src="https://via.placeholder.com/400x100/2563eb/ffffff?text=NESTOGY" alt="Nestogy Logo" width="400">
</p>

<p align="center">
  <strong>Enterprise-grade MSP Management Platform</strong><br>
  Modern Laravel-based ERP system designed for Managed Service Providers
</p>

<p align="center">
  <a href="https://github.com/foleybridge/nestogy-erp/actions"><img src="https://img.shields.io/github/actions/workflow/status/foleybridge/nestogy-erp/tests.yml?branch=main" alt="Build Status"></a>
  <a href="https://github.com/foleybridge/nestogy-erp/releases"><img src="https://img.shields.io/github/v/release/foleybridge/nestogy-erp" alt="Latest Release"></a>
  <a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="License"></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-11.x-red.svg" alt="Laravel Version"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.2+-purple.svg" alt="PHP Version"></a>
</p>

---

## 🎯 About Nestogy

**Nestogy** is a comprehensive, multi-tenant Enterprise Resource Planning (ERP) system built specifically for Managed Service Providers (MSPs). Designed with modern Laravel architecture and domain-driven design principles, Nestogy streamlines MSP operations from client management to service delivery.

### Why Nestogy?

- **🏢 Multi-Tenant Architecture** - Complete data isolation with unlimited MSP company support
- **📋 Comprehensive MSP Tools** - Everything from ticketing to asset management in one platform
- **🔒 Enterprise Security** - Role-based access control, audit logging, and compliance features
- **🚀 Modern Technology Stack** - Laravel 11, PHP 8.2+, Vue.js 3, and cutting-edge tooling
- **📈 Scalable Design** - Domain-driven architecture that grows with your business

## ✨ Key Features

### Core MSP Capabilities
- **Client Management** - Complete CRM with contacts, locations, and relationship tracking
- **Support Ticketing** - Full-featured helpdesk with SLA management and automation
- **Asset Tracking** - IT asset lifecycle management with maintenance scheduling
- **Financial Operations** - Invoicing, payment processing, and comprehensive reporting
- **User Management** - Multi-tenant security with granular role-based permissions

### Advanced Features
- **Multi-Factor Authentication** - Enhanced security with 2FA support
- **Email Integration** - Automatic ticket creation from email
- **PDF Generation** - Professional invoices and reports
- **Data Import/Export** - Excel integration for bulk operations
- **Activity Logging** - Comprehensive audit trails for compliance
- **Automated Backups** - Built-in backup and recovery systems

## 🚀 Quick Start

### Prerequisites

- **PHP** 8.2 or higher
- **Composer** 2.0+
- **Node.js** 18.0+ & npm
- **MySQL** 8.0+ or **MariaDB** 10.5+
- **Web Server** (Apache/Nginx)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/foleybridge/nestogy-erp.git
   cd nestogy-erp
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

4. **Configure your environment**
   Edit `.env` with your database and mail settings:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=nestogy
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Build assets**
   ```bash
   npm run build
   ```

7. **Start the application**
   ```bash
   php artisan serve
   ```

Visit `http://localhost:8000` and register your first user (becomes admin automatically).

## 🛠️ Technology Stack

### Backend
- **Framework**: Laravel 11.x
- **Language**: PHP 8.2+
- **Database**: MySQL 8.0+ / MariaDB 10.5+
- **Authentication**: Laravel Sanctum + 2FA
- **File Storage**: Laravel Filesystem with cloud support

### Frontend
- **JavaScript Framework**: Alpine.js 3.x
- **CSS Framework**: Tailwind CSS 3.x
- **UI Components**: Bootstrap 5.3
- **Build Tool**: Vite 5.x
- **Charts**: Chart.js with date adapters

### Key Packages
- **Permissions**: Spatie Laravel Permission
- **PDF Generation**: Spatie Laravel PDF / DomPDF
- **Excel Integration**: Maatwebsite Excel
- **Media Management**: Spatie Media Library
- **Email Processing**: Webklex Laravel IMAP
- **Backup System**: Spatie Laravel Backup

## 📚 Documentation

For comprehensive documentation, visit our **[docs](./docs/README.md)** directory:

### 🚀 Getting Started
- **[Quick Start Guide](./docs/QUICKSTART.md)** - Get running in 5 minutes
- **[Deployment Guide](./docs/DEPLOYMENT.md)** - Production setup instructions
- **[Configuration Guide](./docs/CONFIGURATION.md)** - System configuration

### 🏗️ Architecture
- **[Architecture Overview](./docs/architecture/README.md)** - System design and principles
- **[Domain Documentation](./docs/architecture/)** - Detailed domain specifications
- **[API Documentation](./docs/api/)** - RESTful API reference *(planned)*

### 🔧 Development
- **[Development Setup](./docs/DEVELOPMENT.md)** - Local development guide
- **[Contributing Guidelines](./docs/CONTRIBUTING.md)** - How to contribute
- **[Testing Guide](./docs/TESTING.md)** - Testing procedures

## 🧪 Development

### Development Server
```bash
# Start all development services
composer run dev

# Or start services individually
php artisan serve      # Laravel development server
npm run dev           # Vite asset compilation
php artisan queue:work # Background job processing
```

### Testing
```bash
# Run the test suite
composer run test

# Run with coverage
php artisan test --coverage
```

### Code Quality
```bash
# Format code with Laravel Pint
./vendor/bin/pint

# Run static analysis
./vendor/bin/phpstan analyse
```

## 🤝 Contributing

We welcome contributions from the community! Whether you're fixing bugs, adding features, or improving documentation, your help makes Nestogy better.

### How to Contribute
1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/amazing-feature`)
3. **Commit** your changes (`git commit -m 'Add amazing feature'`)
4. **Push** to the branch (`git push origin feature/amazing-feature`)
5. **Open** a Pull Request

### Development Guidelines
- Follow PSR-12 coding standards
- Write comprehensive tests for new features
- Update documentation for any API changes
- Ensure all tests pass before submitting

## 🔒 Security

Security is a top priority for Nestogy. The platform includes:

- **Multi-factor authentication** support
- **Role-based access control** with granular permissions
- **Comprehensive audit logging** for all user actions
- **Data encryption** at rest and in transit
- **CSRF protection** on all forms
- **SQL injection prevention** through Eloquent ORM

### Reporting Security Issues
If you discover a security vulnerability, please email security@foleybridge.solutions instead of using the issue tracker.

## 📋 System Requirements

### Minimum Production Requirements
- **OS**: Ubuntu 20.04+ / Debian 11+ / RHEL 8+ / Windows Server 2019+
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: 8.2+ with required extensions
- **Database**: MySQL 8.0+ or MariaDB 10.5+
- **Memory**: 2GB RAM minimum, 4GB recommended
- **Storage**: 20GB minimum, SSD recommended

### Recommended Production Setup
- **Memory**: 8GB+ RAM for optimal performance
- **CPU**: 4+ cores for concurrent user support
- **Storage**: SSD with automated backups
- **Environment**: Load-balanced multi-server setup for high availability

## 📄 License

Nestogy is open-sourced software licensed under the [MIT License](LICENSE).

## 🙏 Credits

Built with ❤️ by the [Foley Bridge](https://foleybridge.solutions) team using these amazing open-source projects:

- [Laravel Framework](https://laravel.com) - The foundation of our application
- [Alpine.js](https://alpinejs.dev) - Reactive frontend framework
- [Tailwind CSS](https://tailwindcss.com) - Utility-first CSS framework
- [Spatie Packages](https://spatie.be/open-source) - Essential Laravel packages

---


**Ready to revolutionize your MSP operations?**

[Get Started](./docs/QUICKSTART.md) • [Documentation](./docs/README.md) • [Support](https://github.com/foleybridge/nestogy-erp/issues)


