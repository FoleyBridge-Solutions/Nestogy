# Nestogy Deployment Scripts

## Installation Scripts

### Primary Installation
- **`install.sh`** - Main development installation script for Ubuntu/Debian systems
- **`install-production.sh`** - Production deployment with performance optimizations

### Database Setup
- **`setup-mariadb.sh`** - Install and configure MariaDB server
- **`create-database.sh`** - Create database, user, and run migrations
- **`quick-db-setup.sh`** - Quick database setup (assumes MariaDB installed)

### Configuration
- **`generate-env.sh`** - Interactive .env file generator
- **`setup-permissions.sh`** - Set correct Laravel file permissions

### Runtime & Maintenance
- **`start-queue-worker.sh`** - Start Laravel queue worker for background jobs
- **`verify-installation.sh`** - Verify installation completeness
- **`verify-config-cache.sh`** - Verify configuration caching works

## Usage Guide

### Fresh Development Installation
```bash
./scripts/install.sh
```

### Production Deployment
```bash
./scripts/install-production.sh
```

### Database Only Setup
```bash
# If MariaDB not installed:
./scripts/setup-mariadb.sh

# Quick setup (MariaDB already installed):
./scripts/quick-db-setup.sh
```

### Post-Installation
```bash
# Fix permissions
./scripts/setup-permissions.sh

# Start background workers
./scripts/start-queue-worker.sh

# Verify everything works
./scripts/verify-installation.sh
```

## Script Purposes

| Script | Purpose | When to Use |
|--------|---------|-------------|
| install.sh | Complete dev setup | New development environment |
| install-production.sh | Production setup with optimizations | Production deployment |
| setup-mariadb.sh | Install MariaDB from scratch | When MariaDB not installed |
| create-database.sh | Create DB and run migrations | After MariaDB installed |
| quick-db-setup.sh | Fast DB setup | When MariaDB already configured |
| generate-env.sh | Configure environment | Initial setup or reconfiguration |
| setup-permissions.sh | Fix file permissions | After deployment or updates |
| start-queue-worker.sh | Start background jobs | Production runtime |
| verify-installation.sh | Check installation | After any installation |
| verify-config-cache.sh | Test config caching | Before production deployment |