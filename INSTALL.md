# 🚀 Nestogy ERP Installation Guide

This guide provides multiple installation options for Nestogy ERP, from single-server setups to enterprise cluster deployments.

## 📋 Installation Options

### 1. **All-in-One Installation** (Single Server)
Perfect for small to medium MSPs. Includes database, web server, and application on one server.

```bash
# Download and run the installer
curl -sSL https://raw.githubusercontent.com/FoleyBridge-Solutions/Nestogy/main/install-nestogy.sh | sudo bash
```

**Or manually:**
```bash
wget https://raw.githubusercontent.com/FoleyBridge-Solutions/Nestogy/main/install-nestogy.sh
chmod +x install-nestogy.sh
sudo ./install-nestogy.sh
```

### 2. **Cluster Installation** (External Database)
For enterprise setups with dedicated database clusters.

```bash
# Download and run the cluster installer
curl -sSL https://raw.githubusercontent.com/FoleyBridge-Solutions/Nestogy/main/install-nestogy-cluster.sh | sudo bash
```

**Or manually:**
```bash
wget https://raw.githubusercontent.com/FoleyBridge-Solutions/Nestogy/main/install-nestogy-cluster.sh
chmod +x install-nestogy-cluster.sh
sudo ./install-nestogy-cluster.sh
```

## 🔧 System Requirements

### Minimum Requirements
- **OS**: Ubuntu 20.04+ or Debian 11+
- **RAM**: 1GB (2GB+ recommended)
- **Disk**: 5GB free space
- **Network**: Internet connection for downloads

### Recommended Production
- **OS**: Ubuntu 22.04 LTS
- **RAM**: 4GB+
- **CPU**: 2+ cores
- **Disk**: 20GB+ SSD
- **Network**: High-speed internet

## 🎯 Installation Features

### ⚡ Performance Optimizations
- **Apache Event MPM** - High concurrency web server
- **PHP-FPM** - FastCGI Process Manager with 20 workers
- **Redis Caching** - Session and application caching
- **OPcache** - PHP opcode caching
- **Database Optimizations** - Tuned for performance

### 🛡️ Security Features
- **UFW Firewall** - Configured and enabled
- **Fail2ban** - Intrusion prevention
- **Security Headers** - XSS, CSRF, and other protections
- **File Permissions** - Properly secured
- **SSL Support** - Let's Encrypt integration

### 🔄 Scalability Features
- **Queue Workers** - Background job processing
- **Distributed Scheduler** - Prevents duplicate jobs
- **Session Clustering** - Redis-based sessions
- **Database Clustering** - External database support

## 📊 Database Options

### PostgreSQL (Recommended)
- Better performance for complex queries
- Excellent JSON support
- Advanced indexing
- Strong ACID compliance

### MySQL/MariaDB
- Wide compatibility
- Familiar to most developers
- Good performance
- Extensive ecosystem

## 🔧 Installation Process

### What the Script Does

1. **System Check** - Verifies requirements
2. **Dependencies** - Installs PHP 8.4, Apache, Node.js
3. **Database** - Sets up PostgreSQL/MySQL (single server) or connects to cluster
4. **Application** - Clones repository and installs dependencies
5. **Configuration** - Optimizes all components
6. **Security** - Configures firewall and security measures
7. **SSL** - Optional Let's Encrypt certificate
8. **Verification** - Tests all components

### Installation Time
- **Single Server**: 10-15 minutes
- **Cluster Setup**: 5-10 minutes (excluding database setup)

## 🌐 Post-Installation

### Access Your Installation
After successful installation, access your Nestogy ERP at:
- **HTTP**: `http://your-server-ip`
- **HTTPS**: `https://your-domain.com` (if SSL configured)

### Default Admin Credentials
Use the email and password you provided during installation.

### Initial Setup
1. Complete the setup wizard
2. Configure company settings
3. Set up email configuration
4. Configure integrations (RMM, payment gateways)
5. Import existing data if needed

## 🔍 Verification

Run the verification script to check your installation:

```bash
cd /var/www/html/Nestogy
sudo ./scripts/verify-installation.sh
```

## 🛠️ Troubleshooting

### Common Issues

**Database Connection Failed**
```bash
# Check database status
sudo systemctl status mariadb  # or postgresql
# Test connection
cd /var/www/html/Nestogy && php artisan tinker --execute="DB::select('SELECT 1');"
```

**Application Not Loading**
```bash
# Check Apache status
sudo systemctl status apache2
# Check PHP-FPM
sudo systemctl status php8.4-fpm
# Check error logs
sudo tail -f /var/log/apache2/nestogy-error.log
```

**Queue Workers Not Running**
```bash
# Check supervisor status
sudo supervisorctl status
# Restart workers
sudo supervisorctl restart nestogy-worker:*
```

### Useful Commands

```bash
# Restart all services
sudo systemctl restart apache2 php8.4-fpm redis-server

# Clear application cache
cd /var/www/html/Nestogy && php artisan cache:clear

# Check queue status
sudo supervisorctl status

# View error logs
sudo tail -f /var/log/apache2/nestogy-error.log

# Check system resources
htop
df -h
free -h
```

## 🔄 Updates

### Application Updates
```bash
cd /var/www/html/Nestogy
git pull origin main
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force
php artisan config:cache
```

### System Updates
```bash
sudo apt update && sudo apt upgrade -y
sudo systemctl restart apache2 php8.4-fpm
```

## 📞 Support

### Documentation
- **GitHub**: https://github.com/FoleyBridge-Solutions/Nestogy
- **Issues**: https://github.com/FoleyBridge-Solutions/Nestogy/issues

### Community
- Report bugs via GitHub Issues
- Request features via GitHub Discussions
- Check documentation for advanced configuration

## 🚀 Production Deployment

### Before Going Live
1. ✅ Run verification script
2. ✅ Test all functionality
3. ✅ Configure SSL certificate
4. ✅ Set up automated backups
5. ✅ Configure monitoring
6. ✅ Review security settings
7. ✅ Test disaster recovery

### Performance Monitoring
- Monitor Apache/PHP-FPM processes
- Check database performance
- Monitor Redis memory usage
- Set up log rotation
- Configure health checks

### Backup Strategy
- Database backups (automated)
- Application file backups
- Configuration backups
- Test restore procedures

---

**🎉 Congratulations!** You now have a production-ready Nestogy ERP installation optimized for performance, security, and scalability.