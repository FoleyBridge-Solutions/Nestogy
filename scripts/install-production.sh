#!/bin/bash

# ===================================================================
# Nestogy ERP Production Installation Script v2.0
# Optimized for high-performance production deployments
# Includes all performance optimizations and security enhancements
# ===================================================================

set -e  # Exit on error

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Configuration variables
APP_NAME="Nestogy ERP"
APP_DIR="/var/www/html/Nestogy"
DB_NAME="nestogy_erp"
DB_USER="nestogy_user"
APACHE_CONF="nestogy.conf"
PHP_VERSION="8.4"
NODE_VERSION="20"

# Performance configuration
PHP_MEMORY_LIMIT="256M"
PHP_FPM_MAX_CHILDREN="20"
PHP_FPM_START_SERVERS="4"
PHP_FPM_MIN_SPARE="2"
PHP_FPM_MAX_SPARE="6"
MYSQL_BUFFER_POOL="512M"

# Function to print colored output
print_message() {
    echo -e "${2}${1}${NC}"
}

# Function to print section headers
print_section() {
    echo
    print_message "================================================================" "$CYAN"
    print_message "  $1" "$CYAN"
    print_message "================================================================" "$CYAN"
}

# Function to check if running as root
check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_message "❌ This script must be run as root or with sudo" "$RED"
        exit 1
    fi
}

# Function to detect OS and version
detect_os() {
    if [[ -f /etc/os-release ]]; then
        . /etc/os-release
        OS=$ID
        VER=$VERSION_ID
        print_message "✅ Detected OS: $PRETTY_NAME" "$GREEN"
    else
        print_message "❌ Cannot detect OS version" "$RED"
        exit 1
    fi
    
    # Check if supported OS
    if [[ "$OS" != "ubuntu" && "$OS" != "debian" ]]; then
        print_message "❌ This script supports Ubuntu and Debian only" "$RED"
        exit 1
    fi
}

# Function to check system requirements
check_system_requirements() {
    print_section "System Requirements Check"
    
    # Check minimum RAM
    TOTAL_RAM=$(free -m | awk 'NR==2{printf "%.0f", $2}')
    if [[ $TOTAL_RAM -lt 2048 ]]; then
        print_message "⚠️  Warning: System has ${TOTAL_RAM}MB RAM. Minimum 2GB recommended" "$YELLOW"
    else
        print_message "✅ RAM: ${TOTAL_RAM}MB (Good)" "$GREEN"
    fi
    
    # Check disk space
    DISK_SPACE=$(df / | awk 'NR==2{print $4}')
    DISK_SPACE_GB=$((DISK_SPACE / 1024 / 1024))
    if [[ $DISK_SPACE_GB -lt 10 ]]; then
        print_message "❌ Insufficient disk space. Minimum 10GB required" "$RED"
        exit 1
    else
        print_message "✅ Disk space: ${DISK_SPACE_GB}GB available" "$GREEN"
    fi
    
    # Check internet connection
    if ping -c 1 google.com &> /dev/null; then
        print_message "✅ Internet connection: Active" "$GREEN"
    else
        print_message "❌ No internet connection available" "$RED"
        exit 1
    fi
}

# Function to install system dependencies
install_system_dependencies() {
    print_section "Installing System Dependencies"
    
    # Update package list
    print_message "📦 Updating package repositories..." "$YELLOW"
    apt-get update
    
    # Install basic tools
    print_message "🔧 Installing essential tools..." "$YELLOW"
    apt-get install -y \
        software-properties-common \
        apt-transport-https \
        ca-certificates \
        curl \
        wget \
        gnupg \
        lsb-release \
        unzip \
        git \
        htop \
        nano \
        ufw \
        fail2ban
    
    # Add PHP repository
    print_message "📦 Adding PHP ${PHP_VERSION} repository..." "$YELLOW"
    add-apt-repository -y ppa:ondrej/php
    apt-get update
    
    # Add Node.js repository
    print_message "📦 Adding Node.js ${NODE_VERSION} repository..." "$YELLOW"
    curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash -
    
    print_message "✅ System dependencies configured" "$GREEN"
}

# Function to install and configure Apache with Event MPM
install_configure_apache() {
    print_section "Installing and Configuring Apache with Event MPM"
    
    # Install Apache
    print_message "🌐 Installing Apache2..." "$YELLOW"
    apt-get install -y apache2
    
    # Stop Apache for configuration
    systemctl stop apache2
    
    # Switch to Event MPM (already done in our optimization)
    print_message "⚡ Configuring Apache Event MPM..." "$YELLOW"
    a2dismod mpm_prefork php${PHP_VERSION} 2>/dev/null || true
    a2enmod mpm_event
    a2enmod proxy_fcgi
    a2enmod setenvif
    a2enmod rewrite
    a2enmod headers
    a2enmod ssl
    
    # Configure Event MPM
    cat > /etc/apache2/mods-available/mpm_event.conf <<EOF
# Event MPM Configuration - Optimized for high concurrency
<IfModule mpm_event_module>
    StartServers             3
    MinSpareThreads          25
    MaxSpareThreads          75
    ThreadLimit              64
    ThreadsPerChild          25
    MaxRequestWorkers        400
    MaxConnectionsPerChild   0
</IfModule>
EOF
    
    # Enable modules needed for PHP-FPM
    a2enmod proxy
    a2enmod proxy_fcgi
    a2enconf php${PHP_VERSION}-fpm
    
    print_message "✅ Apache configured with Event MPM" "$GREEN"
}

# Function to install and configure PHP-FPM
install_configure_php() {
    print_section "Installing and Configuring PHP ${PHP_VERSION} with FPM"
    
    # Install PHP and extensions
    print_message "🐘 Installing PHP ${PHP_VERSION} and extensions..." "$YELLOW"
    apt-get install -y \
        php${PHP_VERSION} \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-cli \
        php${PHP_VERSION}-common \
        php${PHP_VERSION}-mysql \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-xmlrpc \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-gd \
        php${PHP_VERSION}-imagick \
        php${PHP_VERSION}-dev \
        php${PHP_VERSION}-imap \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-opcache \
        php${PHP_VERSION}-redis \
        php${PHP_VERSION}-soap \
        php${PHP_VERSION}-zip \
        php${PHP_VERSION}-intl \
        php${PHP_VERSION}-bcmath \
        php${PHP_VERSION}-calendar \
        php${PHP_VERSION}-exif \
        php${PHP_VERSION}-ftp \
        php${PHP_VERSION}-ldap \
        php${PHP_VERSION}-sockets \
        php${PHP_VERSION}-sysvmsg \
        php${PHP_VERSION}-sysvsem \
        php${PHP_VERSION}-sysvshm \
        php${PHP_VERSION}-tokenizer \
        php${PHP_VERSION}-bz2 \
        php${PHP_VERSION}-pdo \
        php${PHP_VERSION}-sqlite3 \
        php${PHP_VERSION}-pgsql
    
    # Configure PHP-FPM pool with optimizations
    print_message "⚙️  Configuring PHP-FPM pool..." "$YELLOW"
    
    # Backup original config
    cp /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf.backup
    
    # Apply optimized PHP-FPM configuration
    cat > /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf <<EOF
[www]
user = www-data
group = www-data
listen = /run/php/php${PHP_VERSION}-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = ${PHP_FPM_MAX_CHILDREN}
pm.start_servers = ${PHP_FPM_START_SERVERS}
pm.min_spare_servers = ${PHP_FPM_MIN_SPARE}
pm.max_spare_servers = ${PHP_FPM_MAX_SPARE}
pm.max_requests = 500

; Performance monitoring
pm.status_path = /fpm-status
ping.path = /fpm-ping

; Logging
php_admin_value[error_log] = /var/log/fpm-php.www.log
php_admin_flag[log_errors] = on

; Session path
php_value[session.save_handler] = files
php_value[session.save_path] = /var/lib/php/sessions

; Security
php_admin_value[disable_functions] = exec,passthru,shell_exec,system,proc_open,popen
EOF
    
    # Configure PHP settings
    print_message "🔧 Optimizing PHP configuration..." "$YELLOW"
    
    # Apache PHP config
    sed -i "s/memory_limit = .*/memory_limit = ${PHP_MEMORY_LIMIT}/" /etc/php/${PHP_VERSION}/apache2/php.ini
    sed -i "s/max_execution_time = .*/max_execution_time = 300/" /etc/php/${PHP_VERSION}/apache2/php.ini
    sed -i "s/max_input_vars = .*/max_input_vars = 3000/" /etc/php/${PHP_VERSION}/apache2/php.ini
    sed -i "s/upload_max_filesize = .*/upload_max_filesize = 64M/" /etc/php/${PHP_VERSION}/apache2/php.ini
    sed -i "s/post_max_size = .*/post_max_size = 64M/" /etc/php/${PHP_VERSION}/apache2/php.ini
    
    # FPM PHP config
    sed -i "s/memory_limit = .*/memory_limit = ${PHP_MEMORY_LIMIT}/" /etc/php/${PHP_VERSION}/fpm/php.ini
    sed -i "s/max_execution_time = .*/max_execution_time = 300/" /etc/php/${PHP_VERSION}/fpm/php.ini
    sed -i "s/max_input_vars = .*/max_input_vars = 3000/" /etc/php/${PHP_VERSION}/fpm/php.ini
    sed -i "s/upload_max_filesize = .*/upload_max_filesize = 64M/" /etc/php/${PHP_VERSION}/fpm/php.ini
    sed -i "s/post_max_size = .*/post_max_size = 64M/" /etc/php/${PHP_VERSION}/fpm/php.ini
    
    # CLI PHP config
    sed -i "s/memory_limit = .*/memory_limit = 512M/" /etc/php/${PHP_VERSION}/cli/php.ini
    
    # Enable and start PHP-FPM
    systemctl enable php${PHP_VERSION}-fpm
    systemctl start php${PHP_VERSION}-fpm
    
    print_message "✅ PHP ${PHP_VERSION} with FPM configured" "$GREEN"
}

# Function to install and configure MariaDB
install_configure_mariadb() {
    print_section "Installing and Configuring MariaDB"
    
    # Install MariaDB
    print_message "🗄️  Installing MariaDB..." "$YELLOW"
    apt-get install -y mariadb-server mariadb-client
    
    # Start MariaDB
    systemctl start mariadb
    systemctl enable mariadb
    
    # Generate secure random password
    DB_PASS=$(openssl rand -base64 20 | tr -d "=+/" | cut -c1-16)
    
    # Secure MariaDB installation programmatically
    print_message "🔒 Securing MariaDB installation..." "$YELLOW"
    mysql -e "UPDATE mysql.user SET Password=PASSWORD('${DB_PASS}') WHERE User='root';"
    mysql -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
    mysql -e "DELETE FROM mysql.user WHERE User='';"
    mysql -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
    mysql -e "DROP DATABASE IF EXISTS test;"
    mysql -e "FLUSH PRIVILEGES;"
    
    # Create application database and user
    print_message "📊 Creating application database..." "$YELLOW"
    mysql -u root -p${DB_PASS} -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    mysql -u root -p${DB_PASS} -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
    mysql -u root -p${DB_PASS} -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
    mysql -u root -p${DB_PASS} -e "FLUSH PRIVILEGES;"
    
    # Optimize MariaDB configuration
    print_message "⚡ Optimizing MariaDB configuration..." "$YELLOW"
    
    # Backup original config
    cp /etc/mysql/mariadb.conf.d/50-server.cnf /etc/mysql/mariadb.conf.d/50-server.cnf.backup
    
    # Apply optimizations for limited memory
    cat >> /etc/mysql/mariadb.conf.d/50-server.cnf <<EOF

# Nestogy ERP Optimizations
max_connections = 100
thread_cache_size = 16
key_buffer_size = 64M

# InnoDB optimizations for limited memory
innodb_buffer_pool_size = ${MYSQL_BUFFER_POOL}
innodb_log_file_size = 64M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
innodb_file_per_table = 1
innodb_log_buffer_size = 8M

# Query cache (deprecated in newer versions, but useful for read-heavy apps)
query_cache_type = 1
query_cache_size = 16M
query_cache_limit = 1M

# Binary logging (disable for single server setup)
skip-log-bin

# Slow query log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/mysql-slow.log
long_query_time = 2
EOF
    
    # Restart MariaDB to apply settings
    systemctl restart mariadb
    
    # Save credentials
    echo "DB_DATABASE=${DB_NAME}" > /tmp/nestogy_db_credentials.txt
    echo "DB_USERNAME=${DB_USER}" >> /tmp/nestogy_db_credentials.txt
    echo "DB_PASSWORD=${DB_PASS}" >> /tmp/nestogy_db_credentials.txt
    echo "DB_ROOT_PASSWORD=${DB_PASS}" >> /tmp/nestogy_db_credentials.txt
    
    print_message "✅ MariaDB configured with optimizations" "$GREEN"
}

# Function to install Redis and additional tools
install_additional_tools() {
    print_section "Installing Additional Tools"
    
    print_message "📦 Installing Redis, Supervisor, and other tools..." "$YELLOW"
    apt-get install -y \
        redis-server \
        supervisor \
        nodejs \
        certbot \
        python3-certbot-apache \
        zip \
        unzip \
        rsync
    
    # Configure Redis
    print_message "🔧 Configuring Redis..." "$YELLOW"
    sed -i 's/# maxmemory <bytes>/maxmemory 128mb/' /etc/redis/redis.conf
    sed -i 's/# maxmemory-policy noeviction/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf
    
    # Enable and start Redis
    systemctl enable redis-server
    systemctl start redis-server
    
    # Install Composer
    print_message "🎼 Installing Composer..." "$YELLOW"
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    chmod +x /usr/local/bin/composer
    
    print_message "✅ Additional tools installed" "$GREEN"
}

# Function to clone and setup application
setup_application() {
    print_section "Setting Up Nestogy ERP Application"
    
    # Get repository URL
    print_message "📥 Please provide the Nestogy ERP repository details:" "$YELLOW"
    read -p "Git Repository URL (or press Enter to use current directory): " REPO_URL
    
    if [[ -n "$REPO_URL" ]]; then
        # Clone from repository
        print_message "📦 Cloning repository..." "$YELLOW"
        if [[ -d "$APP_DIR" ]]; then
            print_message "⚠️  Backing up existing installation..." "$YELLOW"
            mv "$APP_DIR" "${APP_DIR}_backup_$(date +%Y%m%d_%H%M%S)"
        fi
        
        git clone "$REPO_URL" "$APP_DIR"
    else
        # Use current directory if it contains Laravel app
        if [[ -f "$(pwd)/artisan" && -f "$(pwd)/composer.json" ]]; then
            print_message "📁 Using current directory..." "$YELLOW"
            APP_DIR=$(pwd)
        else
            print_message "❌ Current directory is not a Laravel application" "$RED"
            exit 1
        fi
    fi
    
    cd "$APP_DIR"
    
    # Install PHP dependencies
    print_message "🎼 Installing PHP dependencies..." "$YELLOW"
    composer install --no-dev --optimize-autoloader --no-interaction
    
    # Install Node.js dependencies
    print_message "📦 Installing Node.js dependencies..." "$YELLOW"
    npm install --production
    
    # Build assets
    print_message "🏗️  Building production assets..." "$YELLOW"
    npm run build
    
    print_message "✅ Application setup complete" "$GREEN"
}

# Function to configure environment
configure_environment() {
    print_section "Configuring Application Environment"
    
    cd "$APP_DIR"
    
    # Copy environment file
    if [[ ! -f .env ]]; then
        cp .env.example .env
    fi
    
    # Load database credentials
    source /tmp/nestogy_db_credentials.txt
    
    # Get server IP
    SERVER_IP=$(curl -s http://checkip.amazonaws.com/ || hostname -I | awk '{print $1}')
    
    # Configure environment variables
    print_message "⚙️  Configuring environment variables..." "$YELLOW"
    
    # Basic app configuration
    sed -i "s/APP_NAME=.*/APP_NAME=\"${APP_NAME}\"/" .env
    sed -i "s/APP_ENV=.*/APP_ENV=production/" .env
    sed -i "s/APP_DEBUG=.*/APP_DEBUG=false/" .env
    sed -i "s|APP_URL=.*|APP_URL=http://${SERVER_IP}|" .env
    
    # Database configuration
    sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=mysql/" .env
    sed -i "s/DB_HOST=.*/DB_HOST=127.0.0.1/" .env
    sed -i "s/DB_PORT=.*/DB_PORT=3306/" .env
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=${DB_DATABASE}/" .env
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=${DB_USERNAME}/" .env
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" .env
    
    # Cache and session configuration
    sed -i "s/CACHE_STORE=.*/CACHE_STORE=redis/" .env
    sed -i "s/SESSION_DRIVER=.*/SESSION_DRIVER=redis/" .env
    sed -i "s/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=database/" .env
    
    # Redis configuration
    sed -i "s/REDIS_HOST=.*/REDIS_HOST=127.0.0.1/" .env
    sed -i "s/REDIS_PASSWORD=.*/REDIS_PASSWORD=null/" .env
    sed -i "s/REDIS_PORT=.*/REDIS_PORT=6379/" .env
    
    # Generate application key
    print_message "🔑 Generating application key..." "$YELLOW"
    php artisan key:generate --force
    
    # Run database migrations
    print_message "🗄️  Running database migrations..." "$YELLOW"
    php artisan migrate --force
    
    # Create storage link
    print_message "🔗 Creating storage link..." "$YELLOW"
    php artisan storage:link
    
    # Cache configuration for production
    print_message "🚀 Optimizing for production..." "$YELLOW"
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    
    print_message "✅ Environment configured" "$GREEN"
}

# Function to set proper permissions
set_permissions() {
    print_section "Setting File Permissions"
    
    cd "$APP_DIR"
    
    print_message "🔒 Setting ownership and permissions..." "$YELLOW"
    
    # Set ownership to web server user
    chown -R www-data:www-data .
    
    # Set directory permissions
    find . -type d -exec chmod 755 {} \\;
    
    # Set file permissions
    find . -type f -exec chmod 644 {} \\;
    
    # Set executable permissions for scripts
    chmod +x artisan
    
    # Set writable permissions for storage and cache
    chmod -R 775 storage
    chmod -R 775 bootstrap/cache
    
    # Secure environment file
    chmod 600 .env
    
    # Create session directory if it doesn't exist
    mkdir -p storage/framework/sessions
    chmod -R 775 storage/framework/sessions
    chown -R www-data:www-data storage/framework/sessions
    
    print_message "✅ Permissions configured" "$GREEN"
}

# Function to configure Apache virtual host
configure_apache_vhost() {
    print_section "Configuring Apache Virtual Host"
    
    # Create Apache virtual host configuration
    print_message "🌐 Creating Apache virtual host..." "$YELLOW"
    
    cat > /etc/apache2/sites-available/nestogy.conf <<EOF
<VirtualHost *:80>
    ServerName ${SERVER_IP}
    ServerAlias www.${SERVER_IP}
    DocumentRoot ${APP_DIR}/public
    
    <Directory ${APP_DIR}/public>
        Options -Indexes +FollowSymLinks +MultiViews
        AllowOverride All
        Require all granted
        
        # Security headers
        Header always set X-Content-Type-Options nosniff
        Header always set X-Frame-Options DENY
        Header always set X-XSS-Protection "1; mode=block"
        Header always set Referrer-Policy strict-origin-when-cross-origin
        Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
    </Directory>
    
    # PHP-FPM Configuration
    <FilesMatch \\.php$>
        SetHandler "proxy:unix:/run/php/php${PHP_VERSION}-fpm.sock|fcgi://localhost"
    </FilesMatch>
    
    # Gzip compression
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
    </IfModule>
    
    # Browser caching
    <IfModule mod_expires.c>
        ExpiresActive On
        ExpiresByType text/css "access plus 1 month"
        ExpiresByType application/javascript "access plus 1 month"
        ExpiresByType image/png "access plus 1 year"
        ExpiresByType image/jpg "access plus 1 year"
        ExpiresByType image/jpeg "access plus 1 year"
        ExpiresByType image/gif "access plus 1 year"
        ExpiresByType image/ico "access plus 1 year"
        ExpiresByType image/svg+xml "access plus 1 month"
    </IfModule>
    
    # Security: Hide server information
    ServerTokens Prod
    ServerSignature Off
    
    # Logs
    ErrorLog \${APACHE_LOG_DIR}/nestogy-error.log
    CustomLog \${APACHE_LOG_DIR}/nestogy-access.log combined
    
    # Optional: Redirect to HTTPS (uncomment after SSL setup)
    # RewriteEngine On
    # RewriteCond %{HTTPS} off
    # RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</VirtualHost>

# HTTPS Virtual Host (uncomment after SSL certificate installation)
# <VirtualHost *:443>
#     ServerName ${SERVER_IP}
#     DocumentRoot ${APP_DIR}/public
#     
#     SSLEngine on
#     SSLCertificateFile /etc/ssl/certs/ssl-cert-snakeoil.pem
#     SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key
#     
#     # Include all the same configuration as above
# </VirtualHost>
EOF
    
    # Enable site and required modules
    a2ensite nestogy.conf
    a2dissite 000-default.conf 2>/dev/null || true
    a2enmod expires deflate headers
    
    # Test Apache configuration
    apache2ctl configtest
    
    # Start Apache
    systemctl enable apache2
    systemctl start apache2
    
    print_message "✅ Apache virtual host configured" "$GREEN"
}

# Function to setup supervisor for queue workers
setup_supervisor() {
    print_section "Setting Up Queue Workers with Supervisor"
    
    print_message "👷 Configuring queue workers..." "$YELLOW"
    
    # Create supervisor configuration for Laravel queue workers
    cat > /etc/supervisor/conf.d/nestogy-workers.conf <<EOF
[program:nestogy-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${APP_DIR}/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
directory=${APP_DIR}
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/worker.log
stopwaitsecs=3600

[program:nestogy-scheduler]
process_name=%(program_name)s
command=/bin/bash -c 'while true; do php ${APP_DIR}/artisan schedule:run; sleep 60; done'
directory=${APP_DIR}
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/scheduler.log
EOF
    
    # Reload supervisor configuration
    supervisorctl reread
    supervisorctl update
    supervisorctl start nestogy-worker:*
    supervisorctl start nestogy-scheduler
    
    print_message "✅ Queue workers configured" "$GREEN"
}

# Function to configure firewall and security
configure_security() {
    print_section "Configuring Security and Firewall"
    
    print_message "🔥 Configuring UFW firewall..." "$YELLOW"
    
    # Configure UFW
    ufw --force reset
    ufw default deny incoming
    ufw default allow outgoing
    
    # Allow SSH, HTTP, and HTTPS
    ufw allow 22/tcp
    ufw allow 80/tcp
    ufw allow 443/tcp
    
    # Enable firewall
    echo "y" | ufw enable
    
    # Configure fail2ban
    print_message "🛡️  Configuring fail2ban..." "$YELLOW"
    
    cat > /etc/fail2ban/jail.local <<EOF
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3

[sshd]
enabled = true
port = ssh
filter = sshd
logpath = /var/log/auth.log
maxretry = 3

[apache-auth]
enabled = true
port = http,https
filter = apache-auth
logpath = /var/log/apache2/nestogy-error.log
maxretry = 3

[apache-badbots]
enabled = true
port = http,https
filter = apache-badbots
logpath = /var/log/apache2/nestogy-access.log
maxretry = 2
EOF
    
    # Start fail2ban
    systemctl enable fail2ban
    systemctl restart fail2ban
    
    print_message "✅ Security configured" "$GREEN"
}

# Function to create admin user
create_admin_user() {
    print_section "Creating Admin User"
    
    cd "$APP_DIR"
    
    print_message "👤 Creating admin user account..." "$YELLOW"
    print_message "Please provide admin user details:" "$CYAN"
    
    read -p "Admin Name: " ADMIN_NAME
    read -p "Admin Email: " ADMIN_EMAIL
    
    while true; do
        read -sp "Admin Password: " ADMIN_PASSWORD
        echo
        read -sp "Confirm Password: " ADMIN_PASSWORD_CONFIRM
        echo
        
        if [[ "$ADMIN_PASSWORD" == "$ADMIN_PASSWORD_CONFIRM" ]]; then
            break
        else
            print_message "❌ Passwords do not match. Please try again." "$RED"
        fi
    done
    
    # Create admin user
    php artisan tinker --execute="
        \$user = new App\Models\User();
        \$user->name = '$ADMIN_NAME';
        \$user->email = '$ADMIN_EMAIL';
        \$user->password = Hash::make('$ADMIN_PASSWORD');
        \$user->email_verified_at = now();
        \$user->save();
        echo 'Admin user created successfully\\n';
    "
    
    # Save admin credentials
    echo "ADMIN_EMAIL=${ADMIN_EMAIL}" >> /tmp/nestogy_db_credentials.txt
    
    print_message "✅ Admin user created successfully" "$GREEN"
}

# Function to setup SSL certificate
setup_ssl() {
    print_section "SSL Certificate Setup (Optional)"
    
    print_message "🔒 Do you want to setup SSL certificate with Let's Encrypt?" "$YELLOW"
    read -p "Enter domain name (or press Enter to skip): " DOMAIN_NAME
    
    if [[ -n "$DOMAIN_NAME" ]]; then
        print_message "🔐 Setting up SSL certificate for $DOMAIN_NAME..." "$YELLOW"
        
        # Update Apache configuration with domain name
        sed -i "s/ServerName ${SERVER_IP}/ServerName ${DOMAIN_NAME}/" /etc/apache2/sites-available/nestogy.conf
        sed -i "s|APP_URL=http://${SERVER_IP}|APP_URL=https://${DOMAIN_NAME}|" "$APP_DIR/.env"
        
        # Restart Apache
        systemctl reload apache2
        
        # Obtain SSL certificate
        certbot --apache --non-interactive --agree-tos --email "${ADMIN_EMAIL}" -d "${DOMAIN_NAME}"
        
        # Setup auto-renewal
        echo "0 12 * * * /usr/bin/certbot renew --quiet" | crontab -
        
        print_message "✅ SSL certificate configured for $DOMAIN_NAME" "$GREEN"
    else
        print_message "⏭️  Skipping SSL setup" "$YELLOW"
    fi
}

# Function to run post-installation tasks
post_installation() {
    print_section "Post-Installation Tasks"
    
    cd "$APP_DIR"
    
    print_message "🔧 Running post-installation optimizations..." "$YELLOW"
    
    # Clear all caches
    php artisan cache:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    # Generate APP_KEY if not exists
    if ! grep -q "APP_KEY=base64:" .env; then
        php artisan key:generate --force
    fi
    
    # Run any pending migrations
    php artisan migrate --force
    
    # Create required directories
    mkdir -p storage/app/public/uploads
    mkdir -p storage/app/backups
    mkdir -p storage/logs
    
    # Set final permissions
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R 775 storage bootstrap/cache
    
    # Clean up
    rm -f /tmp/nestogy_db_credentials.txt
    
    print_message "✅ Post-installation tasks completed" "$GREEN"
}

# Function to display installation summary
display_summary() {
    print_section "Installation Complete!"
    
    # Load saved credentials
    source /tmp/nestogy_db_credentials.txt 2>/dev/null || true
    
    print_message "🎉 Nestogy ERP has been successfully installed!" "$GREEN"
    echo
    print_message "📋 INSTALLATION SUMMARY" "$CYAN"
    print_message "════════════════════════" "$CYAN"
    echo
    print_message "🌐 Application URL: http://${SERVER_IP}" "$YELLOW"
    if [[ -n "$DOMAIN_NAME" ]]; then
        print_message "🔒 Secure URL: https://${DOMAIN_NAME}" "$YELLOW"
    fi
    echo
    print_message "👤 Admin Login:" "$BLUE"
    print_message "   Email: ${ADMIN_EMAIL}" "$YELLOW"
    print_message "   Password: [as entered during setup]" "$YELLOW"
    echo
    print_message "🗄️  Database Information:" "$BLUE"
    print_message "   Database: ${DB_NAME}" "$YELLOW"
    print_message "   Username: ${DB_USER}" "$YELLOW"
    print_message "   Password: [saved in .env file]" "$YELLOW"
    echo
    print_message "📁 Important Paths:" "$BLUE"
    print_message "   Application: ${APP_DIR}" "$YELLOW"
    print_message "   Apache Config: /etc/apache2/sites-available/nestogy.conf" "$YELLOW"
    print_message "   PHP-FPM Config: /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf" "$YELLOW"
    print_message "   Error Logs: /var/log/apache2/nestogy-error.log" "$YELLOW"
    print_message "   Application Logs: ${APP_DIR}/storage/logs/" "$YELLOW"
    echo
    print_message "⚡ Performance Optimizations Applied:" "$BLUE"
    print_message "   ✅ Apache Event MPM (high concurrency)" "$GREEN"
    print_message "   ✅ PHP-FPM with ${PHP_FPM_MAX_CHILDREN} workers" "$GREEN"
    print_message "   ✅ PHP memory limit: ${PHP_MEMORY_LIMIT}" "$GREEN"
    print_message "   ✅ MariaDB optimized (${MYSQL_BUFFER_POOL} buffer pool)" "$GREEN"
    print_message "   ✅ Redis caching enabled" "$GREEN"
    print_message "   ✅ Queue workers configured" "$GREEN"
    echo
    print_message "🛡️  Security Features:" "$BLUE"
    print_message "   ✅ UFW firewall configured" "$GREEN"
    print_message "   ✅ Fail2ban protection active" "$GREEN"
    print_message "   ✅ Security headers enabled" "$GREEN"
    print_message "   ✅ File permissions secured" "$GREEN"
    echo
    print_message "📋 Next Steps:" "$PURPLE"
    print_message "   1. Access your Nestogy ERP at the URL above" "$YELLOW"
    print_message "   2. Complete the initial setup wizard" "$YELLOW"
    print_message "   3. Configure email settings in admin panel" "$YELLOW"
    print_message "   4. Set up regular backups" "$YELLOW"
    print_message "   5. Configure monitoring (optional)" "$YELLOW"
    if [[ -z "$DOMAIN_NAME" ]]; then
        print_message "   6. Set up SSL certificate for production use" "$YELLOW"
    fi
    echo
    print_message "📖 Documentation:" "$BLUE"
    print_message "   GitHub: https://github.com/FoleyBridge-Solutions/Nestogy" "$YELLOW"
    print_message "   Issues: https://github.com/FoleyBridge-Solutions/Nestogy/issues" "$YELLOW"
    echo
    print_message "🆘 Need Help?" "$BLUE"
    print_message "   Check logs: sudo tail -f /var/log/apache2/nestogy-error.log" "$YELLOW"
    print_message "   Restart services: sudo systemctl restart apache2 php${PHP_VERSION}-fpm" "$YELLOW"
    print_message "   Queue status: sudo supervisorctl status" "$YELLOW"
    echo
    print_message "Thank you for using Nestogy ERP! 🚀" "$GREEN"
}

# Main installation function
main() {
    clear
    print_message "██████╗ ██╗███████╗████████╗ ██████╗  ██████╗██╗   ██╗" "$CYAN"
    print_message "██╔══██╗██║██╔════╝╚══██╔══╝██╔═══██╗██╔════╝╚██╗ ██╔╝" "$CYAN"
    print_message "██████╔╝██║█████╗     ██║   ██║   ██║██║  ███╗╚████╔╝ " "$CYAN"
    print_message "██╔══██╗██║██╔══╝     ██║   ██║   ██║██║   ██║ ╚██╔╝  " "$CYAN"
    print_message "██║  ██║██║███████╗   ██║   ╚██████╔╝╚██████╔╝  ██║   " "$CYAN"
    print_message "╚═╝  ╚═╝╚═╝╚══════╝   ╚═╝    ╚═════╝  ╚═════╝   ╚═╝   " "$CYAN"
    echo
    print_message "Nestogy ERP Production Installation Script v2.0" "$BLUE"
    print_message "High-Performance Installation with Optimizations" "$YELLOW"
    echo
    
    # Confirmation
    print_message "⚠️  This script will install and configure:" "$YELLOW"
    print_message "   • Apache with Event MPM" "$WHITE"
    print_message "   • PHP ${PHP_VERSION} with FPM" "$WHITE"
    print_message "   • MariaDB with optimizations" "$WHITE"
    print_message "   • Redis caching" "$WHITE"
    print_message "   • Security hardening" "$WHITE"
    print_message "   • Queue workers" "$WHITE"
    echo
    read -p "🤔 Do you want to continue? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_message "Installation cancelled by user." "$RED"
        exit 1
    fi
    
    # Start installation
    check_root
    detect_os
    check_system_requirements
    install_system_dependencies
    install_configure_apache
    install_configure_php
    install_configure_mariadb
    install_additional_tools
    setup_application
    configure_environment
    set_permissions
    configure_apache_vhost
    setup_supervisor
    configure_security
    create_admin_user
    setup_ssl
    post_installation
    display_summary
}

# Error handling
trap 'echo -e "\n${RED}❌ Installation failed. Check the error above.${NC}"; exit 1' ERR

# Run main function
main "$@"

exit 0