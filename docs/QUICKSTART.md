# Nestogy MSP Platform - Quick Start Guide

Get the Nestogy MSP Platform up and running quickly with this streamlined guide. The platform uses Laravel 12 with modern base class architecture for 45% code reduction and 2-3x faster development.

For detailed instructions, see [DEPLOYMENT.md](DEPLOYMENT.md).

## Prerequisites

- Ubuntu/Debian server with sudo access
- Apache2 web server
- MySQL 8.0+ database server
- PHP 8.2+ with required extensions
- Laravel 12 requirements
- Composer installed
- Node.js 18+ and npm installed

## Quick Installation (5 Minutes)

### 1. Clone and Navigate to Project

```bash
cd /var/www/html
sudo git clone https://github.com/your-repo/nestogy-laravel.git
cd nestogy-laravel
```

### 2. Run Quick Install Script

```bash
# Make installation script executable
sudo chmod +x scripts/install.sh

# Run installation (follow prompts)
sudo ./scripts/install.sh
```

### 3. Manual Quick Setup (If Not Using Script)

```bash
# Install dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run build

# Configure environment
cp .env.example .env
nano .env  # Edit database and app settings

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Set permissions
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo chmod -R 775 storage bootstrap/cache

# Configure Apache
sudo cp docs/apache/nestogy.conf /etc/apache2/sites-available/
sudo a2ensite nestogy.conf
sudo a2enmod rewrite headers
sudo systemctl restart apache2
```

## Essential Configuration

### Database Setup

```sql
CREATE DATABASE nestogy_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'nestogy_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON nestogy_erp.* TO 'nestogy_user'@'localhost';
FLUSH PRIVILEGES;
```

### Environment Variables (.env)

```env
APP_NAME="Nestogy MSP Platform"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-server-ip

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nestogy_erp
DB_USERNAME=nestogy_user
DB_PASSWORD=your_secure_password

# Optional: Configure email
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

## First Run

1. **Access the Application**
   - Navigate to: `http://your-server-ip`
   - First user registration becomes admin

2. **Initial Setup**
   - Complete the setup wizard
   - Configure company information (critical for multi-tenancy)
   - Set up initial users

3. **Test Core Features**
   - Create a test client (tests base controller pattern)
   - Create a test ticket (tests client relationship scoping)
   - Upload a test document (tests file handling)
   - Verify multi-tenant isolation (different company data is separate)

## Common Issues & Quick Fixes

### Permission Errors

```bash
sudo ./scripts/setup-permissions.sh
```

### 500 Internal Server Error

```bash
# Check logs
tail -f storage/logs/laravel.log

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Regenerate caches
php artisan config:cache
php artisan route:cache
```

### Database Connection Failed

```bash
# Test MySQL connection
mysql -u nestogy_user -p

# Verify .env settings
grep DB_ .env

# Check MySQL service
sudo systemctl status mysql
```

### Blank Page / Assets Not Loading

```bash
# Create storage link
php artisan storage:link

# Check Apache mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2

# Rebuild assets
npm run build
```

### Email Not Working

1. Check `.env` mail settings
2. For Gmail: Use App Password, not regular password
3. Test with tinker:

```bash
php artisan tinker
>>> Mail::raw('Test', function ($m) { $m->to('test@example.com')->subject('Test'); });
```

## Quick Commands Reference

### Cache Management

```bash
# Clear all caches
php artisan optimize:clear

# Optimize for production
php artisan optimize
```

### Database

```bash
# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Fresh migration (CAUTION: Drops all tables)
php artisan migrate:fresh
```

### Maintenance Mode

```bash
# Enable maintenance
php artisan down

# Disable maintenance
php artisan up

# Maintenance with message
php artisan down --message="Upgrading database"
```

### Queue Management

```bash
# Process queue manually
php artisan queue:work

# Process failed jobs
php artisan queue:retry all
```

## Security Checklist

- [ ] Change default passwords
- [ ] Enable HTTPS/SSL
- [ ] Configure firewall
- [ ] Disable debug mode in production
- [ ] Set up regular backups
- [ ] Review file permissions

## Next Steps

1. **SSL Certificate**: Set up HTTPS using Let's Encrypt
   ```bash
   sudo apt install certbot python3-certbot-apache
   sudo certbot --apache -d your-domain.com
   ```

2. **Configure Backups**: Set up automated backups
   ```bash
   sudo crontab -e
   # Add: 0 2 * * * /var/www/html/nestogy-laravel/scripts/backup.sh
   ```

3. **Monitor Performance**: Install monitoring tools
   ```bash
   # Install htop for resource monitoring
   sudo apt install htop
   ```

4. **Set Up Email**: Configure proper email service for notifications

5. **Review Security**: Run security audit
   ```bash
   # Check for vulnerabilities
   composer audit
   npm audit
   ```

6. **Learn Modern Architecture**: Review base class patterns
   - Study BaseResourceController for standardized CRUD
   - Understand domain-specific base services
   - Learn multi-tenancy requirements
   - See [DEVELOPMENT.md](DEVELOPMENT.md) for detailed patterns

## Useful Resources

- **Full Documentation**: [DEPLOYMENT.md](DEPLOYMENT.md)
- **Development Guide**: [DEVELOPMENT.md](DEVELOPMENT.md) - Modern architecture patterns
- **Testing Guide**: [TESTING.md](TESTING.md) - Base class testing
- **Apache Config**: [docs/apache/nestogy.conf](apache/nestogy.conf)
- **Scripts Directory**: [scripts/](../scripts/)
- **Laravel 12 Docs**: https://laravel.com/docs/12.x
- **Troubleshooting**: See [DEPLOYMENT.md#troubleshooting](DEPLOYMENT.md#troubleshooting)

## Support

- **Issues**: Report bugs via GitHub Issues
- **Email**: support@nestogy.com
- **Documentation**: Check `/docs` directory

---

**Version**: 2.0.0
**Last Updated**: August 2024
**Platform**: Laravel 12 + Modern Base Class Architecture