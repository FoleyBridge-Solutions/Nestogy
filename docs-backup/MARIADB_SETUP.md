# MariaDB Setup Guide for Nestogy MSP Platform

This guide provides step-by-step instructions for setting up MariaDB for the Nestogy MSP Platform.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Automated Setup](#automated-setup)
3. [Manual Setup](#manual-setup)
4. [Configuration](#configuration)
5. [Troubleshooting](#troubleshooting)
6. [Security Best Practices](#security-best-practices)

## Prerequisites

- Ubuntu 20.04+ / Debian 10+ / RHEL 8+ / CentOS 8+
- Root or sudo access
- PHP 8.1 or higher
- Composer installed
- Git installed

## Automated Setup

The easiest way to set up MariaDB for the Nestogy MSP Platform is using our automated setup script.

### Using the Setup Script

1. Navigate to the scripts directory:
   ```bash
   cd nestogy-laravel/scripts
   ```

2. Make the script executable:
   ```bash
   chmod +x setup-mariadb.sh
   ```

3. Run the setup script:
   ```bash
   ./setup-mariadb.sh
   ```

The script will:
- Check if MariaDB is installed (install if needed)
- Secure the MariaDB installation
- Create the database and user
- Update your `.env` file
- Optionally run migrations

### Quick Database Setup (MariaDB Already Installed)

If MariaDB is already installed and you just need to set up the database:

```bash
cd nestogy-laravel/scripts
chmod +x quick-db-setup.sh
./quick-db-setup.sh
```

## Manual Setup

If you prefer to set up MariaDB manually, follow these steps:

### 1. Install MariaDB

#### Ubuntu/Debian:
```bash
sudo apt update
sudo apt install mariadb-server mariadb-client
```

#### RHEL/CentOS/Fedora:
```bash
sudo yum install mariadb-server mariadb
```

#### Arch Linux:
```bash
sudo pacman -S mariadb
```

### 2. Initialize and Start MariaDB

```bash
sudo mysql_install_db --user=mysql --basedir=/usr --datadir=/var/lib/mysql
sudo systemctl start mariadb
sudo systemctl enable mariadb
```

### 3. Secure MariaDB Installation

Run the security script:
```bash
sudo mysql_secure_installation
```

Follow the prompts to:
- Set root password
- Remove anonymous users
- Disallow root login remotely
- Remove test database
- Reload privilege tables

### 4. Create Database and User

Log into MariaDB as root:
```bash
sudo mysql -u root -p
```

Create the database and user:
```sql
-- Create database with UTF8MB4 support
CREATE DATABASE nestogy_msp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user
CREATE USER 'nestogy_user'@'localhost' IDENTIFIED BY 'your_secure_password_here';

-- Grant privileges
GRANT ALL PRIVILEGES ON nestogy_msp.* TO 'nestogy_user'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;

-- Exit
EXIT;
```

### 5. Configure Laravel Environment

Update your `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nestogy_msp
DB_USERNAME=nestogy_user
DB_PASSWORD=your_secure_password_here
```

### 6. Run Migrations

```bash
cd nestogy-laravel
php artisan migrate
```

### 7. Seed the Database (Optional)

To create an admin user and demo data:
```bash
php artisan db:seed --class=AdminUserSeeder
```

Default admin credentials:
- Email: admin@nestogy.com
- Password: Admin@123456

**Important:** Change the admin password after first login!

## Configuration

### MariaDB Configuration File

Edit `/etc/mysql/mariadb.conf.d/50-server.cnf` (location may vary):

```ini
[mysqld]
# Character set
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# Performance
innodb_buffer_pool_size = 256M
max_connections = 200
query_cache_size = 16M
query_cache_limit = 2M

# Logging
log_error = /var/log/mysql/error.log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2

# Binary logging (for replication/backup)
log_bin = /var/log/mysql/mysql-bin.log
expire_logs_days = 10
max_binlog_size = 100M
```

Restart MariaDB after changes:
```bash
sudo systemctl restart mariadb
```

### PHP Configuration

Ensure PHP has the MySQL extension:
```bash
sudo apt install php-mysql  # Ubuntu/Debian
sudo yum install php-mysqlnd  # RHEL/CentOS
```

## Troubleshooting

### Common Issues

#### 1. Access Denied Error

If you get "Access denied for user" error:
```bash
# Check user exists
sudo mysql -e "SELECT User, Host FROM mysql.user WHERE User='nestogy_user';"

# Reset password if needed
sudo mysql -e "ALTER USER 'nestogy_user'@'localhost' IDENTIFIED BY 'new_password';"
```

#### 2. Cannot Connect to MariaDB

Check if MariaDB is running:
```bash
sudo systemctl status mariadb
```

Check if it's listening on the correct port:
```bash
sudo netstat -tlnp | grep 3306
```

#### 3. Character Set Issues

If you see character encoding issues:
```sql
-- Check database character set
SHOW CREATE DATABASE nestogy_msp;

-- Convert if needed
ALTER DATABASE nestogy_msp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### 4. Migration Errors

If migrations fail:
```bash
# Check connection
php artisan db:show

# Try fresh migration (WARNING: This drops all tables)
php artisan migrate:fresh
```

### Checking Logs

MariaDB logs location:
- Error log: `/var/log/mysql/error.log`
- Slow query log: `/var/log/mysql/slow.log`

Laravel logs:
- Application log: `storage/logs/laravel.log`

## Security Best Practices

### 1. Strong Passwords

Use strong passwords for all database users:
```bash
# Generate secure password
openssl rand -base64 32
```

### 2. Limit User Privileges

Create specific users for different purposes:
```sql
-- Read-only user for reporting
CREATE USER 'nestogy_read'@'localhost' IDENTIFIED BY 'password';
GRANT SELECT ON nestogy_msp.* TO 'nestogy_read'@'localhost';

-- Backup user
CREATE USER 'nestogy_backup'@'localhost' IDENTIFIED BY 'password';
GRANT SELECT, LOCK TABLES ON nestogy_msp.* TO 'nestogy_backup'@'localhost';
```

### 3. Enable SSL/TLS

For production environments, enable SSL:
```sql
-- Check SSL status
SHOW VARIABLES LIKE '%ssl%';

-- Configure in .env
DB_SSLMODE=required
DB_SSLCERT=/path/to/client-cert.pem
DB_SSLKEY=/path/to/client-key.pem
DB_SSLCA=/path/to/ca-cert.pem
```

### 4. Regular Backups

Set up automated backups:
```bash
# Create backup script
cat > /usr/local/bin/backup-nestogy-db.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/var/backups/nestogy"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR
mysqldump -u nestogy_backup -p'password' nestogy_msp | gzip > $BACKUP_DIR/nestogy_$DATE.sql.gz
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete
EOF

chmod +x /usr/local/bin/backup-nestogy-db.sh

# Add to crontab
echo "0 2 * * * /usr/local/bin/backup-nestogy-db.sh" | sudo crontab -
```

### 5. Firewall Configuration

If MariaDB needs remote access:
```bash
# Allow only specific IPs
sudo ufw allow from 192.168.1.100 to any port 3306
```

## Performance Optimization

### 1. Enable Query Cache

In MariaDB configuration:
```ini
query_cache_type = 1
query_cache_size = 64M
query_cache_limit = 2M
```

### 2. Optimize Tables

Run periodically:
```bash
mysqlcheck -u root -p --optimize nestogy_msp
```

### 3. Monitor Performance

Use built-in tools:
```sql
-- Show running queries
SHOW PROCESSLIST;

-- Show table status
SHOW TABLE STATUS FROM nestogy_msp;

-- Show variables
SHOW VARIABLES LIKE 'innodb%';
```

## Maintenance

### Regular Tasks

1. **Weekly**: Check slow query log
2. **Monthly**: Optimize tables
3. **Quarterly**: Review and update passwords
4. **Yearly**: Review and update MariaDB version

### Monitoring Script

Create a monitoring script:
```bash
#!/bin/bash
# Check database size
mysql -u root -p -e "SELECT table_schema AS 'Database', 
  ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)' 
  FROM information_schema.TABLES 
  WHERE table_schema = 'nestogy_msp'
  GROUP BY table_schema;"

# Check connections
mysql -u root -p -e "SHOW STATUS LIKE 'Threads_connected';"

# Check uptime
mysql -u root -p -e "SHOW STATUS LIKE 'Uptime';"
```

## Additional Resources

- [MariaDB Documentation](https://mariadb.com/kb/en/documentation/)
- [Laravel Database Documentation](https://laravel.com/docs/database)
- [MariaDB Performance Tuning](https://mariadb.com/kb/en/optimization-and-tuning/)

## Support

If you encounter issues not covered in this guide:

1. Check Laravel logs: `tail -f storage/logs/laravel.log`
2. Check MariaDB logs: `sudo tail -f /var/log/mysql/error.log`
3. Run Laravel database diagnostics: `php artisan db:show`
4. Test connection: `php artisan tinker` then `DB::connection()->getPdo();`

Remember to always backup your database before making significant changes!

---

**Version**: 1.0.0 | **Last Updated**: January 2024 | **Platform**: Laravel 11 + PHP 8.2+