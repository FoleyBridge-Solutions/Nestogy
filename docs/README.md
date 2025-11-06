# Nestogy Documentation

> MSP-focused ERP system built with Laravel, Livewire, and Flux

## Quick Links

- **[Quickstart Guide](QUICKSTART.md)** - Get up and running
- **[Development Guide](DEVELOPMENT.md)** - Dev environment setup
- **[Configuration](CONFIGURATION.md)** - System configuration
- **[Deployment](DEPLOYMENT.md)** - Production deployment
- **[Testing](TESTING.md)** - Test suite guide
- **[Code Quality Tracking](antipatterns.md)** - Antipatterns and fixes
- **[VoIP Tax System](voip-tax-system.md)** - VoIP tax calculation (domain-specific)

## System Overview

Nestogy is a comprehensive ERP system designed for Managed Service Providers (MSPs).

### Core Features
- **Client Management** - Contacts, locations, contracts, portal access
- **Ticketing** - SLA tracking, time entries, workflows, sentiment analysis
- **Financial** - Invoicing, payments, quotes, recurring billing, VoIP tax
- **Asset Management** - Hardware/software tracking, depreciation, warranties
- **Projects** - Project planning, tasks, time tracking
- **Integrations** - RMM tools, email sync, payment gateways, physical mail

### Tech Stack
- **Backend:** Laravel 12.36, PHP 8.4
- **Frontend:** Livewire 3.6, Flux/Flux Pro v2.6, Alpine.js 3.14, Tailwind CSS 4
- **Database:** PostgreSQL 13+ (primary), MySQL 8.0+/MariaDB 10.5+ (supported)
- **Cache/Queue:** Redis 6+
- **Real-time:** Laravel Reverb 1.6
- **Testing:** PHPUnit 11.5, Paratest 7.8

## Getting Started

1. **[Quickstart](QUICKSTART.md)** - Get running in 5 minutes
2. **[Development Setup](DEVELOPMENT.md)** - Full dev environment
3. **[Configuration](CONFIGURATION.md)** - Configure the system
4. **[Run Tests](TESTING.md)** - Ensure everything works

## Support

- **Issues:** GitHub Issues
- **Code Quality:** Check [antipatterns.md](antipatterns.md) for refactoring priorities

## License

Proprietary - All rights reserved by FoleyBridge Solutions