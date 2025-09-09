# Nestogy MSP Platform - Complete Deployment Guide

This guide provides comprehensive instructions for deploying the Nestogy MSP Platform on a production server running Apache2.

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Pre-Installation Checklist](#pre-installation-checklist)
3. [Installation Steps](#installation-steps)
4. [Configuration](#configuration)
5. [Security Setup](#security-setup)
6. [Performance Optimization](#performance-optimization)
7. [Backup and Recovery](#backup-and-recovery)
8. [Monitoring and Maintenance](#monitoring-and-maintenance)
9. [Troubleshooting](#troubleshooting)
10. [Upgrade Guide](#upgrade-guide)

## System Requirements

### Minimum Requirements

- **Operating System**: Ubuntu 20.04 LTS or newer, Debian 11 or newer
- **Web Server**: Apache 2.4.41 or newer
- **PHP**: 8.2 or newer
- **Database**: MySQL 8.0 or newer / MariaDB 10.5 or newer
- **RAM**: 4GB minimum (8GB recommended)
- **Storage**: 20GB minimum (50GB recommended)
- **CPU**: 2 cores minimum (4 cores recommended)

### Required PHP Extensions

```bash
php8.2-cli
php8.2-common
php8.2-mysql
php8.2-xml
php8.2-xmlrpc
php8.2-curl
php8.2-gd
php8.2-imagick
php8.2-dev
php8.2-imap
php8.2-mbstring
php8.2-opcache
php8.2-redis
php8.2-soap
php8.2-zip
php8.2-intl
php8.2-bcmath
php8.2-fpm
```

### Additional Software

- **Composer**: 2.0 or newer
- **Node.js**: 18.x or newer
- **npm**: 8.x or newer
- **Git**: 2.x or newer
- **Redis**: 6.x or newer (optional, for caching)
- **Supervisor**: For queue workers (optional)

## Pre-Installation Checklist

- [ ] Server meets minimum requirements
- [ ] Root or sudo access available
- [ ] Domain name configured (DNS pointing to server)
- [ ] SSL certificate ready (or use Let's Encrypt)
- [ ] Database server accessible
- [ ] Backup of any existing data
- [ ] Firewall rules configured
- [ ] Email server credentials (for notifications)

## Installation Steps

### 1. Update System and Install Dependencies

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install Apache and MySQL
sudo apt install -y apache2 mysql-server

# Install PHP and extensions
sudo apt install -y php8.2 php8.2-cli php8.2-common php8.2-mysql \
    php8.2-xml php8.2-xmlrpc php8.2-curl php8.2-gd php8.2-imagick \
    php8.2-dev php8.2-imap php8.2-mbstring php8.2-opcache php8.2-redis \
    php8.2-soap php8.2-zip php8.2-intl php8.2-bcmath php8.2-fpm

# Install additional tools
sudo apt install -y git composer nodejs npm redis-server supervisor unzip
```

### 2. Configure MySQL

```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE nestogy_msp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'nestogy_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON nestogy_msp.* TO 'nestogy_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Clone and Install Application

```bash
# Navigate to web directory
cd /var/www/html

# Clone repository (replace with your repository URL)
sudo git clone https://github.com/your-repo/nestogy-laravel.git
cd nestogy-laravel

# Install PHP dependencies
sudo composer install --no-dev --optimize-autoloader

# Install Node dependencies and build assets
sudo npm install
sudo npm run build
```

### 4. Configure Environment

```bash
# Copy environment file
sudo cp .env.example .env

# Generate application key
sudo php artisan key:generate

# Edit environment file
sudo nano .env
```

Update the following in `.env`:

```env
APP_NAME="Nestogy MSP Platform"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nestogy_msp
DB_USERNAME=nestogy_user
DB_PASSWORD=your_database_password

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

### 5. Run Database Migrations and Seeders

```bash
# Run migrations
sudo php artisan migrate --force

# Run seeders (if any)
sudo php artisan db:seed --force

# Create storage link
sudo php artisan storage:link
```

### 6. Set Proper Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/html/nestogy-laravel

# Set directory permissions
sudo find /var/www/html/nestogy-laravel -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/html/nestogy-laravel -type f -exec chmod 644 {} \;

# Set storage and cache permissions
sudo chmod -R 775 /var/www/html/nestogy-laravel/storage
sudo chmod -R 775 /var/www/html/nestogy-laravel/bootstrap/cache
```

### 7. Configure Apache

```bash
# Copy Apache configuration
sudo cp /var/www/html/nestogy-laravel/docs/apache/nestogy.conf /etc/apache2/sites-available/

# Enable required modules
sudo a2enmod rewrite headers ssl proxy proxy_fcgi

# Enable the site
sudo a2ensite nestogy.conf

# Disable default site
sudo a2dissite 000-default.conf

# Test configuration
sudo apache2ctl configtest

# Restart Apache
sudo systemctl restart apache2
```

### 8. Configure SSL (Using Let's Encrypt)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-apache

# Obtain SSL certificate
sudo certbot --apache -d your-domain.com -d www.your-domain.com

# Test auto-renewal
sudo certbot renew --dry-run
```

### 9. Configure Queue Workers (Optional)

Create supervisor configuration:

```bash
sudo nano /etc/supervisor/conf.d/nestogy-worker.conf
```

Add the following:

```ini
[program:nestogy-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/nestogy-laravel/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/html/nestogy-laravel/storage/logs/worker.log
stopwaitsecs=3600
```

Start the workers:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start nestogy-worker:*
```

### 10. Configure Cron Jobs

```bash
# Edit crontab for www-data user
sudo crontab -u www-data -e
```

Add the following:

```cron
* * * * * cd /var/www/html/nestogy-laravel && php artisan schedule:run >> /dev/null 2>&1
```

## Configuration

### Email Configuration

1. Configure your SMTP settings in `.env`
2. Test email configuration:

```bash
sudo php artisan tinker
```

```php
Mail::raw('Test email', function ($message) {
    $message->to('test@example.com')->subject('Test Email');
});
```

### File Storage Configuration

1. For local storage, ensure proper permissions on `storage/app/public`
2. For S3 or other cloud storage:

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket
```

### Cache Configuration

```bash
# Clear all caches
sudo php artisan cache:clear
sudo php artisan config:clear
sudo php artisan route:clear
sudo php artisan view:clear

# Optimize for production
sudo php artisan config:cache
sudo php artisan route:cache
sudo php artisan view:cache
sudo php artisan optimize
```

## Security Setup

### 1. Firewall Configuration

```bash
# Install UFW
sudo apt install -y ufw

# Configure firewall
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### 2. Fail2ban Configuration

```bash
# Install fail2ban
sudo apt install -y fail2ban

# Create local configuration
sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local

# Configure for Apache
sudo nano /etc/fail2ban/jail.local
```

Add or modify:

```ini
[apache]
enabled = true
port = http,https
filter = apache-auth
logpath = /var/log/apache2/*error.log
maxretry = 3
bantime = 600

[apache-noscript]
enabled = true
port = http,https
filter = apache-noscript
logpath = /var/log/apache2/*error.log
maxretry = 3
bantime = 600
```

### 3. Security Headers

Ensure the following headers are set in your Apache configuration:

```apache
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-Content-Type-Options "nosniff"
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';"
```

### 4. File Permissions Security

```bash
# Secure .env file
chmod 600 /var/www/html/nestogy-laravel/.env

# Secure configuration files
chmod 600 /var/www/html/nestogy-laravel/config/*.php

# Remove unnecessary files
cd /var/www/html/nestogy-laravel
rm -rf .git .gitignore .gitattributes README.md
```

## Performance Optimization

### 1. PHP OPcache Configuration

Edit `/etc/php/8.2/fpm/conf.d/10-opcache.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
opcache.enable_cli=1
```

### 2. MySQL Optimization

Edit `/etc/mysql/mysql.conf.d/mysqld.cnf`:

```ini
[mysqld]
# Performance settings
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
query_cache_type = 1
query_cache_size = 128M
query_cache_limit = 2M
```

### 3. Redis Configuration

Edit `/etc/redis/redis.conf`:

```conf
maxmemory 512mb
maxmemory-policy allkeys-lru
save ""
```

### 4. Apache Performance Tuning

Enable Apache modules:

```bash
sudo a2enmod expires
sudo a2enmod deflate
sudo a2enmod http2
```

## Backup and Recovery

### 1. Automated Backup Script

Create `/usr/local/bin/nestogy-backup.sh`:

```bash
#!/bin/bash
# Nestogy MSP Platform Backup Script

BACKUP_DIR="/backup/nestogy"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="nestogy_msp"
DB_USER="nestogy_user"
DB_PASS="your_password"
APP_DIR="/var/www/html/nestogy-laravel"

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup application files
tar -czf $BACKUP_DIR/files_$DATE.tar.gz -C $APP_DIR storage .env

# Backup uploads
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz -C $APP_DIR/storage/app public

# Remove old backups (keep last 7 days)
find $BACKUP_DIR -type f -mtime +7 -delete

echo "Backup completed: $DATE"
```

Make it executable and add to cron:

```bash
sudo chmod +x /usr/local/bin/nestogy-backup.sh
sudo crontab -e
```

Add:

```cron
0 2 * * * /usr/local/bin/nestogy-backup.sh >> /var/log/nestogy-backup.log 2>&1
```

### 2. Recovery Procedure

```bash
# Restore database
gunzip < /backup/nestogy/db_YYYYMMDD_HHMMSS.sql.gz | mysql -u nestogy_user -p nestogy_msp

# Restore files
cd /var/www/html/nestogy-laravel
tar -xzf /backup/nestogy/files_YYYYMMDD_HHMMSS.tar.gz
tar -xzf /backup/nestogy/uploads_YYYYMMDD_HHMMSS.tar.gz -C storage/app

# Fix permissions
sudo chown -R www-data:www-data /var/www/html/nestogy-laravel
```

## Monitoring and Maintenance

### 1. Log Monitoring

Monitor important logs:

```bash
# Application logs
tail -f /var/www/html/nestogy-laravel/storage/logs/laravel.log

# Apache logs
tail -f /var/log/apache2/nestogy-error.log
tail -f /var/log/apache2/nestogy-access.log

# MySQL logs
tail -f /var/log/mysql/error.log
```

### 2. Health Checks

Create a health check endpoint in your application and monitor it:

```bash
# Add to crontab
*/5 * * * * curl -f https://your-domain.com/health || echo "Nestogy MSP Platform is down" | mail -s "Alert: Nestogy MSP Platform" admin@example.com
```

### 3. Regular Maintenance Tasks

Weekly:
- Check disk space: `df -h`
- Check memory usage: `free -m`
- Review error logs
- Update system packages: `sudo apt update && sudo apt upgrade`

Monthly:
- Review and optimize database: `mysqlcheck -o nestogy_msp`
- Clean old logs: `find /var/www/html/nestogy-laravel/storage/logs -name "*.log" -mtime +30 -delete`
- Review security logs
- Test backup restoration

## Troubleshooting

### Common Issues and Solutions

#### 1. 500 Internal Server Error

Check:
- Apache error logs: `sudo tail -f /var/log/apache2/error.log`
- Laravel logs: `tail -f storage/logs/laravel.log`
- File permissions: Ensure www-data owns all files
- PHP errors: Check `php -v` and installed extensions

#### 2. Database Connection Errors

- Verify credentials in `.env`
- Check MySQL is running: `sudo systemctl status mysql`
- Test connection: `mysql -u nestogy_user -p nestogy_msp`

#### 3. Email Not Sending

- Verify SMTP settings in `.env`
- Check firewall allows outbound SMTP
- Test with tinker (see Email Configuration section)

#### 4. Slow Performance

- Enable caching: `php artisan config:cache`
- Check MySQL slow query log
- Monitor server resources: `htop`
- Review Apache and PHP configurations

#### 5. File Upload Issues

- Check `upload_max_filesize` in PHP configuration
- Verify storage permissions
- Check available disk space

### Debug Mode (Development Only)

To enable debug mode temporarily:

```bash
# Edit .env file
APP_DEBUG=true

# Clear cache
php artisan config:clear
```

**WARNING**: Never leave debug mode enabled in production!

## Upgrade Guide

### 1. Pre-Upgrade Checklist

- [ ] Backup database and files
- [ ] Review release notes
- [ ] Test upgrade in staging environment
- [ ] Schedule maintenance window
- [ ] Notify users

### 2. Upgrade Process

```bash
# Enable maintenance mode
cd /var/www/html/nestogy-laravel
sudo php artisan down

# Backup current version
sudo cp -r /var/www/html/nestogy-laravel /var/www/html/nestogy-laravel-backup

# Pull latest code
sudo git pull origin main

# Update dependencies
sudo composer install --no-dev --optimize-autoloader
sudo npm install && npm run build

# Run migrations
sudo php artisan migrate --force

# Clear caches
sudo php artisan cache:clear
sudo php artisan config:cache
sudo php artisan route:cache
sudo php artisan view:cache

# Fix permissions
sudo chown -R www-data:www-data /var/www/html/nestogy-laravel

# Disable maintenance mode
sudo php artisan up
```

### 3. Post-Upgrade Verification

- Test all critical functions
- Check error logs
- Verify email notifications
- Test file uploads
- Monitor performance

### 4. Rollback Procedure

If issues occur:

```bash
# Enable maintenance mode
sudo php artisan down

# Restore backup
sudo rm -rf /var/www/html/nestogy-laravel
sudo mv /var/www/html/nestogy-laravel-backup /var/www/html/nestogy-laravel

# Restore database (if needed)
mysql -u nestogy_user -p nestogy_msp < /backup/pre-upgrade-backup.sql

# Disable maintenance mode
sudo php artisan up
```

## Support and Resources

- **Documentation**: Check the `/docs` directory
- **Issue Tracker**: Report bugs via GitHub Issues
- **Community Forum**: Join discussions at forum.nestogy.com
- **Email Support**: support@nestogy.com

---

Last Updated: January 2024
Version: 1.0.0