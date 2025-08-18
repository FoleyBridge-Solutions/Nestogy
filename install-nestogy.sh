#!/bin/bash

# ====================================================================
# 🚀 NESTOGY ERP - COMPLETE INSTALLATION SCRIPT v2.0
# ====================================================================
# All-in-One Production Installation with Performance Optimizations
# Supports: Ubuntu 20.04+, Debian 11+
# Database: PostgreSQL 17 (recommended) or MariaDB
# Web Server: Apache with Event MPM + PHP-FPM
# Features: Redis, Queue Workers, SSL, Security Hardening
# ====================================================================

set -e  # Exit on any error

# Color codes for beautiful output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
NC='\033[0m' # No Color

# Configuration - Modify these as needed
APP_NAME="Nestogy ERP"
APP_DIR="/var/www/html/Nestogy"
DB_NAME="nestogy_erp"
DB_USER="nestogy_user"
PHP_VERSION="8.4"
NODE_VERSION="20"
REPO_URL="https://github.com/FoleyBridge-Solutions/Nestogy.git"

# Performance Settings
PHP_MEMORY_LIMIT="256M"
PHP_FPM_MAX_CHILDREN="20"
PHP_FPM_START_SERVERS="4"
PHP_FPM_MIN_SPARE="2"
PHP_FPM_MAX_SPARE="6"

# Global variables
DB_TYPE=""
DB_PASS=""
ADMIN_EMAIL=""
ADMIN_PASSWORD=""
SERVER_IP=""
DOMAIN_NAME=""

# ====================================================================
# UTILITY FUNCTIONS
# ====================================================================

print_message() {
    echo -e "${2}${1}${NC}"
}

print_banner() {
    clear
    echo -e "${CYAN}"
    cat << "EOF"
    ███╗   ██╗███████╗███████╗████████╗ ██████╗  ██████╗██╗   ██╗
    ████╗  ██║██╔════╝██╔════╝╚══██╔══╝██╔═══██╗██╔════╝╚██╗ ██╔╝
    ██╔██╗ ██║█████╗  ███████╗   ██║   ██║   ██║██║  ███╗╚████╔╝ 
    ██║╚██╗██║██╔══╝  ╚════██║   ██║   ██║   ██║██║   ██║ ╚██╔╝  
    ██║ ╚████║███████╗███████║   ██║   ╚██████╔╝╚██████╔╝  ██║   
    ╚═╝  ╚═══╝╚══════╝╚══════╝   ╚═╝    ╚═════╝  ╚═════╝   ╚═╝   
EOF
    echo -e "${NC}"
    print_message "🚀 Complete Production Installation Script v2.0" "$YELLOW"
    print_message "⚡ High-Performance • 🔒 Secure • 📈 Scalable" "$WHITE"
    echo
}

print_section() {
    echo
    print_message "================================================================" "$CYAN"
    print_message "  $1" "$CYAN"
    print_message "================================================================" "$CYAN"
}

check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_message "❌ This script must be run as root or with sudo" "$RED"
        exit 1
    fi
}

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
    
    if [[ "$OS" != "ubuntu" && "$OS" != "debian" ]]; then
        print_message "❌ This script supports Ubuntu 20.04+ and Debian 11+ only" "$RED"
        exit 1
    fi
}

get_server_ip() {
    SERVER_IP=$(curl -s http://checkip.amazonaws.com/ 2>/dev/null || wget -qO- http://ipecho.net/plain 2>/dev/null || hostname -I | awk '{print $1}')
    if [[ -z "$SERVER_IP" ]]; then
        read -p "🌐 Could not detect server IP. Please enter it manually: " SERVER_IP
    fi
    print_message "🌐 Server IP: $SERVER_IP" "$GREEN"
}

# ====================================================================
# SYSTEM REQUIREMENTS CHECK
# ====================================================================

check_system_requirements() {
    print_section "System Requirements Check"
    
    local requirements_met=true
    
    # Check RAM
    local total_ram=$(free -m | awk 'NR==2{printf "%.0f", $2}')
    if [[ $total_ram -lt 1024 ]]; then
        print_message "❌ Insufficient RAM: ${total_ram}MB (minimum 1GB required)" "$RED"
        requirements_met=false
    elif [[ $total_ram -lt 2048 ]]; then
        print_message "⚠️  RAM: ${total_ram}MB (2GB+ recommended for production)" "$YELLOW"
    else
        print_message "✅ RAM: ${total_ram}MB" "$GREEN"
    fi
    
    # Check disk space
    local disk_space=$(df / | awk 'NR==2{print $4}')
    local disk_space_gb=$((disk_space / 1024 / 1024))
    if [[ $disk_space_gb -lt 5 ]]; then
        print_message "❌ Insufficient disk space: ${disk_space_gb}GB (minimum 5GB required)" "$RED"
        requirements_met=false
    else
        print_message "✅ Disk space: ${disk_space_gb}GB available" "$GREEN"
    fi
    
    # Check internet
    if ping -c 1 8.8.8.8 &> /dev/null; then
        print_message "✅ Internet connection: Active" "$GREEN"
    else
        print_message "❌ No internet connection" "$RED"
        requirements_met=false
    fi
    
    if [[ "$requirements_met" != "true" ]]; then
        print_message "❌ System requirements not met. Please fix the issues above." "$RED"
        exit 1
    fi
}

# ====================================================================
# USER INPUT COLLECTION
# ====================================================================

collect_user_input() {
    print_section "Installation Configuration"
    
    # Database selection
    echo
    print_message "📊 Choose your database:" "$CYAN"
    print_message "1) PostgreSQL 17 (Recommended for new installations)" "$WHITE"
    print_message "2) MariaDB (Good for MySQL compatibility)" "$WHITE"
    echo
    while true; do
        read -p "🤔 Select database (1 or 2): " db_choice
        case $db_choice in
            1)
                DB_TYPE="postgresql"
                print_message "✅ PostgreSQL 17 selected" "$GREEN"
                break
                ;;
            2)
                DB_TYPE="mariadb"
                print_message "✅ MariaDB selected" "$GREEN"
                break
                ;;
            *)
                print_message "❌ Please enter 1 or 2" "$RED"
                ;;
        esac
    done
    
    # Repository URL
    echo
    print_message "📦 Repository Configuration:" "$CYAN"
    print_message "Default: $REPO_URL" "$WHITE"
    read -p "🔗 Custom repository URL (or press Enter for default): " custom_repo
    if [[ -n "$custom_repo" ]]; then
        REPO_URL="$custom_repo"
    fi
    
    # Admin user details
    echo
    print_message "👤 Admin User Configuration:" "$CYAN"
    read -p "📧 Admin Email: " ADMIN_EMAIL
    read -p "👤 Admin Name: " ADMIN_NAME
    
    while true; do
        read -sp "🔒 Admin Password: " ADMIN_PASSWORD
        echo
        read -sp "🔒 Confirm Password: " ADMIN_PASSWORD_CONFIRM
        echo
        
        if [[ "$ADMIN_PASSWORD" == "$ADMIN_PASSWORD_CONFIRM" ]]; then
            if [[ ${#ADMIN_PASSWORD} -lt 8 ]]; then
                print_message "❌ Password must be at least 8 characters" "$RED"
            else
                break
            fi
        else
            print_message "❌ Passwords do not match" "$RED"
        fi
    done
    
    # SSL Configuration
    echo
    print_message "🔒 SSL Configuration:" "$CYAN"
    read -p "🌐 Domain name for SSL (or press Enter to skip): " DOMAIN_NAME
    
    # Installation confirmation
    echo
    print_message "📋 Installation Summary:" "$YELLOW"
    print_message "Database: $DB_TYPE" "$WHITE"
    print_message "Repository: $REPO_URL" "$WHITE"
    print_message "Admin Email: $ADMIN_EMAIL" "$WHITE"
    if [[ -n "$DOMAIN_NAME" ]]; then
        print_message "Domain: $DOMAIN_NAME" "$WHITE"
        print_message "SSL: Will be configured" "$WHITE"
    else
        print_message "SSL: Will be skipped" "$WHITE"
    fi
    echo
    
    read -p "🚀 Proceed with installation? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_message "❌ Installation cancelled" "$RED"
        exit 1
    fi
}

# ====================================================================
# SYSTEM DEPENDENCIES INSTALLATION
# ====================================================================

install_system_dependencies() {
    print_section "Installing System Dependencies"
    
    print_message "📦 Updating package repositories..." "$YELLOW"
    apt-get update -q
    
    print_message "🔧 Installing essential packages..." "$YELLOW"
    apt-get install -y -q \
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
        vim \
        ufw \
        fail2ban \
        supervisor \
        redis-server \
        certbot \
        python3-certbot-apache \
        zip \
        rsync \
        cron
    
    # Add PHP repository
    print_message "📦 Adding PHP ${PHP_VERSION} repository..." "$YELLOW"
    if [[ "$OS" == "ubuntu" ]]; then
        add-apt-repository -y ppa:ondrej/php > /dev/null 2>&1
    else
        # Debian
        wget -qO /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
        echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list
    fi
    
    # Add Node.js repository
    print_message "📦 Adding Node.js ${NODE_VERSION} repository..." "$YELLOW"
    curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash - > /dev/null 2>&1
    
    apt-get update -q
    
    print_message "✅ System dependencies configured" "$GREEN"
}

# ====================================================================
# DATABASE INSTALLATION
# ====================================================================

install_database() {
    print_section "Installing and Configuring Database"
    
    # Generate secure password
    DB_PASS=$(openssl rand -base64 20 | tr -d "=+/" | cut -c1-16)
    
    if [[ "$DB_TYPE" == "postgresql" ]]; then
        install_postgresql
    else
        install_mariadb
    fi
}

install_postgresql() {
    print_message "🐘 Installing PostgreSQL 17..." "$YELLOW"
    
    # Add PostgreSQL repository
    wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add -
    echo "deb http://apt.postgresql.org/pub/repos/apt/ $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list
    apt-get update -q
    
    # Install PostgreSQL
    apt-get install -y -q postgresql-17 postgresql-client-17 postgresql-contrib-17
    
    # Start and enable PostgreSQL
    systemctl start postgresql
    systemctl enable postgresql
    
    # Configure PostgreSQL
    print_message "🔧 Configuring PostgreSQL..." "$YELLOW"
    
    # Create database and user
    sudo -u postgres psql -c "CREATE DATABASE ${DB_NAME};"
    sudo -u postgres psql -c "CREATE USER ${DB_USER} WITH ENCRYPTED PASSWORD '${DB_PASS}';"
    sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE ${DB_NAME} TO ${DB_USER};"
    sudo -u postgres psql -c "ALTER USER ${DB_USER} CREATEDB;"
    
    # Configure PostgreSQL for performance
    local pg_version="17"
    local pg_config="/etc/postgresql/${pg_version}/main/postgresql.conf"
    local pg_hba="/etc/postgresql/${pg_version}/main/pg_hba.conf"
    
    # Performance optimizations
    cp "$pg_config" "${pg_config}.backup"
    
    cat >> "$pg_config" <<EOF

# Nestogy ERP Performance Optimizations
shared_buffers = 256MB
effective_cache_size = 1GB
maintenance_work_mem = 64MB
checkpoint_completion_target = 0.9
wal_buffers = 16MB
default_statistics_target = 100
random_page_cost = 1.1
effective_io_concurrency = 200
work_mem = 4MB
min_wal_size = 1GB
max_wal_size = 4GB
max_connections = 100
EOF
    
    # Restart PostgreSQL
    systemctl restart postgresql
    
    print_message "✅ PostgreSQL 17 configured" "$GREEN"
}

install_mariadb() {
    print_message "🗄️  Installing MariaDB..." "$YELLOW"
    
    apt-get install -y -q mariadb-server mariadb-client
    
    systemctl start mariadb
    systemctl enable mariadb
    
    # Secure installation
    print_message "🔒 Securing MariaDB..." "$YELLOW"
    mysql -e "UPDATE mysql.user SET Password=PASSWORD('${DB_PASS}') WHERE User='root';" 2>/dev/null || \
    mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '${DB_PASS}';"
    mysql -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
    mysql -e "DELETE FROM mysql.user WHERE User='';"
    mysql -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
    mysql -e "DROP DATABASE IF EXISTS test;"
    mysql -e "FLUSH PRIVILEGES;"
    
    # Create application database
    mysql -u root -p"${DB_PASS}" -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    mysql -u root -p"${DB_PASS}" -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
    mysql -u root -p"${DB_PASS}" -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
    mysql -u root -p"${DB_PASS}" -e "FLUSH PRIVILEGES;"
    
    # Performance optimizations
    local mysql_config="/etc/mysql/mariadb.conf.d/50-server.cnf"
    cp "$mysql_config" "${mysql_config}.backup"
    
    cat >> "$mysql_config" <<EOF

# Nestogy ERP Performance Optimizations
max_connections = 100
thread_cache_size = 16
key_buffer_size = 64M
innodb_buffer_pool_size = 512M
innodb_log_file_size = 64M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
innodb_file_per_table = 1
query_cache_type = 1
query_cache_size = 16M
skip-log-bin
slow_query_log = 1
long_query_time = 2
EOF
    
    systemctl restart mariadb
    
    print_message "✅ MariaDB configured" "$GREEN"
}

# ====================================================================
# WEB SERVER INSTALLATION
# ====================================================================

install_apache_php() {
    print_section "Installing Apache & PHP with Performance Optimizations"
    
    # Install Apache
    print_message "🌐 Installing Apache2..." "$YELLOW"
    apt-get install -y -q apache2
    
    # Install PHP and extensions
    print_message "🐘 Installing PHP ${PHP_VERSION}..." "$YELLOW"
    apt-get install -y -q \
        php${PHP_VERSION} \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-cli \
        php${PHP_VERSION}-common \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-gd \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-zip \
        php${PHP_VERSION}-bcmath \
        php${PHP_VERSION}-intl \
        php${PHP_VERSION}-opcache \
        php${PHP_VERSION}-redis \
        php${PHP_VERSION}-soap \
        php${PHP_VERSION}-tokenizer \
        php${PHP_VERSION}-calendar \
        php${PHP_VERSION}-ctype \
        php${PHP_VERSION}-exif \
        php${PHP_VERSION}-fileinfo \
        php${PHP_VERSION}-filter \
        php${PHP_VERSION}-ftp \
        php${PHP_VERSION}-iconv \
        php${PHP_VERSION}-json \
        php${PHP_VERSION}-ldap \
        php${PHP_VERSION}-pcntl \
        php${PHP_VERSION}-pdo \
        php${PHP_VERSION}-phar \
        php${PHP_VERSION}-posix \
        php${PHP_VERSION}-readline \
        php${PHP_VERSION}-shmop \
        php${PHP_VERSION}-simplexml \
        php${PHP_VERSION}-sockets \
        php${PHP_VERSION}-sodium \
        php${PHP_VERSION}-sqlite3 \
        php${PHP_VERSION}-sysvmsg \
        php${PHP_VERSION}-sysvsem \
        php${PHP_VERSION}-sysvshm \
        php${PHP_VERSION}-xmlreader \
        php${PHP_VERSION}-xmlwriter \
        php${PHP_VERSION}-xsl
    
    # Add database-specific PHP extensions
    if [[ "$DB_TYPE" == "postgresql" ]]; then
        apt-get install -y -q php${PHP_VERSION}-pgsql
    else
        apt-get install -y -q php${PHP_VERSION}-mysql
    fi
    
    # Configure Apache for Event MPM + PHP-FPM
    print_message "⚡ Configuring Apache Event MPM..." "$YELLOW"
    
    # Disable prefork and PHP module
    a2dismod mpm_prefork php${PHP_VERSION} 2>/dev/null || true
    
    # Enable Event MPM and required modules
    a2enmod mpm_event
    a2enmod proxy
    a2enmod proxy_fcgi
    a2enmod setenvif
    a2enmod rewrite
    a2enmod headers
    a2enmod ssl
    a2enmod expires
    a2enmod deflate
    
    # Configure Event MPM
    cat > /etc/apache2/mods-available/mpm_event.conf <<EOF
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
    
    # Enable PHP-FPM configuration
    a2enconf php${PHP_VERSION}-fpm
    
    # Configure PHP-FPM pool
    print_message "🔧 Optimizing PHP-FPM..." "$YELLOW"
    
    local fpm_config="/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf"
    cp "$fpm_config" "${fpm_config}.backup"
    
    cat > "$fpm_config" <<EOF
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

pm.status_path = /fpm-status
ping.path = /fpm-ping

php_admin_value[error_log] = /var/log/fpm-php.www.log
php_admin_flag[log_errors] = on
php_value[session.save_handler] = files
php_value[session.save_path] = /var/lib/php/sessions

# Security
php_admin_value[disable_functions] = exec,passthru,shell_exec,system,proc_open,popen
EOF
    
    # Optimize PHP configuration
    print_message "⚙️  Optimizing PHP settings..." "$YELLOW"
    
    # FPM PHP config
    local php_fpm_ini="/etc/php/${PHP_VERSION}/fpm/php.ini"
    sed -i "s/memory_limit = .*/memory_limit = ${PHP_MEMORY_LIMIT}/" "$php_fpm_ini"
    sed -i "s/max_execution_time = .*/max_execution_time = 300/" "$php_fpm_ini"
    sed -i "s/max_input_vars = .*/max_input_vars = 3000/" "$php_fpm_ini"
    sed -i "s/upload_max_filesize = .*/upload_max_filesize = 64M/" "$php_fpm_ini"
    sed -i "s/post_max_size = .*/post_max_size = 64M/" "$php_fpm_ini"
    sed -i "s/;date.timezone =.*/date.timezone = UTC/" "$php_fpm_ini"
    
    # CLI PHP config
    local php_cli_ini="/etc/php/${PHP_VERSION}/cli/php.ini"
    sed -i "s/memory_limit = .*/memory_limit = 512M/" "$php_cli_ini"
    sed -i "s/max_execution_time = .*/max_execution_time = 0/" "$php_cli_ini"
    
    # Start services
    systemctl enable php${PHP_VERSION}-fpm apache2
    systemctl start php${PHP_VERSION}-fpm
    
    print_message "✅ Apache & PHP configured with Event MPM" "$GREEN"
}

# ====================================================================
# ADDITIONAL TOOLS INSTALLATION
# ====================================================================

install_additional_tools() {
    print_section "Installing Additional Tools"
    
    # Install Node.js
    print_message "📦 Installing Node.js ${NODE_VERSION}..." "$YELLOW"
    apt-get install -y -q nodejs
    
    # Install Composer
    print_message "🎼 Installing Composer..." "$YELLOW"
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    chmod +x /usr/local/bin/composer
    
    # Configure Redis
    print_message "🔧 Configuring Redis..." "$YELLOW"
    sed -i 's/# maxmemory <bytes>/maxmemory 256mb/' /etc/redis/redis.conf
    sed -i 's/# maxmemory-policy noeviction/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf
    sed -i 's/bind 127.0.0.1 ::1/bind 127.0.0.1/' /etc/redis/redis.conf
    
    systemctl enable redis-server
    systemctl start redis-server
    
    print_message "✅ Additional tools configured" "$GREEN"
}

# ====================================================================
# APPLICATION INSTALLATION
# ====================================================================

install_application() {
    print_section "Installing Nestogy ERP Application"
    
    # Remove existing directory if it exists
    if [[ -d "$APP_DIR" ]]; then
        print_message "🗑️  Removing existing installation..." "$YELLOW"
        rm -rf "$APP_DIR"
    fi
    
    # Create parent directory
    mkdir -p "$(dirname "$APP_DIR")"
    
    # Clone repository
    print_message "📥 Cloning repository..." "$YELLOW"
    git clone "$REPO_URL" "$APP_DIR"
    
    cd "$APP_DIR"
    
    # Install PHP dependencies
    print_message "🎼 Installing PHP dependencies..." "$YELLOW"
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction
    
    # Install Node.js dependencies
    print_message "📦 Installing Node.js dependencies..." "$YELLOW"
    npm install --silent
    
    # Build assets
    print_message "🏗️  Building production assets..." "$YELLOW"
    npm run build
    
    print_message "✅ Application installed" "$GREEN"
}

# ====================================================================
# APPLICATION CONFIGURATION
# ====================================================================

configure_application() {
    print_section "Configuring Application Environment"
    
    cd "$APP_DIR"
    
    # Copy environment file
    cp .env.example .env
    
    # Configure environment variables
    print_message "⚙️  Configuring environment..." "$YELLOW"
    
    # Basic configuration
    sed -i "s/APP_NAME=.*/APP_NAME=\"${APP_NAME}\"/" .env
    sed -i "s/APP_ENV=.*/APP_ENV=production/" .env
    sed -i "s/APP_DEBUG=.*/APP_DEBUG=false/" .env
    
    if [[ -n "$DOMAIN_NAME" ]]; then
        sed -i "s|APP_URL=.*|APP_URL=https://${DOMAIN_NAME}|" .env
    else
        sed -i "s|APP_URL=.*|APP_URL=http://${SERVER_IP}|" .env
    fi
    
    # Database configuration
    if [[ "$DB_TYPE" == "postgresql" ]]; then
        sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=pgsql/" .env
        sed -i "s/DB_PORT=.*/DB_PORT=5432/" .env
    else
        sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=mysql/" .env
        sed -i "s/DB_PORT=.*/DB_PORT=3306/" .env
    fi
    
    sed -i "s/DB_HOST=.*/DB_HOST=127.0.0.1/" .env
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=${DB_NAME}/" .env
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=${DB_USER}/" .env
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=${DB_PASS}/" .env
    
    # Cache and session configuration for Redis
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
    
    # Create admin user
    print_message "👤 Creating admin user..." "$YELLOW"
    php artisan tinker --execute="
        \$user = new App\Models\User();
        \$user->name = '$ADMIN_NAME';
        \$user->email = '$ADMIN_EMAIL';
        \$user->password = Hash::make('$ADMIN_PASSWORD');
        \$user->email_verified_at = now();
        \$user->save();
        echo 'Admin user created successfully\\n';
    "
    
    # Cache for production
    print_message "🚀 Optimizing for production..." "$YELLOW"
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    
    print_message "✅ Application configured" "$GREEN"
}

# ====================================================================
# PERMISSIONS & SECURITY
# ====================================================================

set_permissions() {
    print_section "Setting Permissions & Security"
    
    cd "$APP_DIR"
    
    print_message "🔒 Setting file permissions..." "$YELLOW"
    
    # Set ownership
    chown -R www-data:www-data .
    
    # Set permissions
    find . -type d -exec chmod 755 {} \\;
    find . -type f -exec chmod 644 {} \\;
    
    # Executable permissions
    chmod +x artisan
    
    # Writable directories
    chmod -R 775 storage bootstrap/cache
    
    # Secure environment file
    chmod 600 .env
    
    # Create required directories
    mkdir -p storage/app/public/uploads storage/app/backups storage/logs
    mkdir -p bootstrap/cache storage/framework/{sessions,views,cache}
    
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R 775 storage bootstrap/cache
    
    print_message "✅ Permissions configured" "$GREEN"
}

configure_security() {
    print_section "Configuring Security & Firewall"
    
    # UFW Firewall
    print_message "🔥 Configuring UFW firewall..." "$YELLOW"
    
    ufw --force reset > /dev/null 2>&1
    ufw default deny incoming
    ufw default allow outgoing
    ufw allow 22/tcp
    ufw allow 80/tcp
    ufw allow 443/tcp
    echo "y" | ufw enable > /dev/null 2>&1
    
    # Fail2ban
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
EOF
    
    systemctl enable fail2ban
    systemctl restart fail2ban
    
    print_message "✅ Security configured" "$GREEN"
}

# ====================================================================
# WEB SERVER CONFIGURATION
# ====================================================================

configure_apache_vhost() {
    print_section "Configuring Apache Virtual Host"
    
    # Create virtual host
    print_message "🌐 Creating Apache virtual host..." "$YELLOW"
    
    local server_name="${DOMAIN_NAME:-$SERVER_IP}"
    
    cat > /etc/apache2/sites-available/nestogy.conf <<EOF
<VirtualHost *:80>
    ServerName ${server_name}
    DocumentRoot ${APP_DIR}/public
    
    <Directory ${APP_DIR}/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Security headers
        Header always set X-Content-Type-Options nosniff
        Header always set X-Frame-Options DENY
        Header always set X-XSS-Protection "1; mode=block"
        Header always set Referrer-Policy strict-origin-when-cross-origin
    </Directory>
    
    # PHP-FPM
    <FilesMatch \\.php$>
        SetHandler "proxy:unix:/run/php/php${PHP_VERSION}-fpm.sock|fcgi://localhost"
    </FilesMatch>
    
    # Compression
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
    </IfModule>
    
    # Caching
    <IfModule mod_expires.c>
        ExpiresActive On
        ExpiresByType text/css "access plus 1 month"
        ExpiresByType application/javascript "access plus 1 month"
        ExpiresByType image/png "access plus 1 year"
        ExpiresByType image/jpg "access plus 1 year"
        ExpiresByType image/jpeg "access plus 1 year"
        ExpiresByType image/gif "access plus 1 year"
    </IfModule>
    
    ErrorLog \${APACHE_LOG_DIR}/nestogy-error.log
    CustomLog \${APACHE_LOG_DIR}/nestogy-access.log combined
EOF

    # If domain is set, add HTTPS redirect
    if [[ -n "$DOMAIN_NAME" ]]; then
        cat >> /etc/apache2/sites-available/nestogy.conf <<EOF
    
    # Redirect to HTTPS (will be enabled after SSL setup)
    # RewriteEngine On
    # RewriteCond %{HTTPS} off
    # RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
EOF
    fi
    
    cat >> /etc/apache2/sites-available/nestogy.conf <<EOF
</VirtualHost>
EOF
    
    # Enable site
    a2ensite nestogy.conf
    a2dissite 000-default.conf 2>/dev/null || true
    
    # Test and restart Apache
    apache2ctl configtest
    systemctl restart apache2
    
    print_message "✅ Apache virtual host configured" "$GREEN"
}

# ====================================================================
# BACKGROUND SERVICES
# ====================================================================

setup_background_services() {
    print_section "Setting Up Background Services"
    
    # Supervisor configuration for queue workers
    print_message "👷 Configuring queue workers..." "$YELLOW"
    
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
    
    # Start supervisor services
    supervisorctl reread
    supervisorctl update
    supervisorctl start nestogy-worker:*
    supervisorctl start nestogy-scheduler
    
    # Add cron job as backup
    print_message "⏰ Setting up cron job..." "$YELLOW"
    (crontab -u www-data -l 2>/dev/null; echo "* * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1") | crontab -u www-data -
    
    print_message "✅ Background services configured" "$GREEN"
}

# ====================================================================
# SSL CERTIFICATE
# ====================================================================

setup_ssl() {
    if [[ -z "$DOMAIN_NAME" ]]; then
        return 0
    fi
    
    print_section "Setting Up SSL Certificate"
    
    print_message "🔒 Installing SSL certificate for $DOMAIN_NAME..." "$YELLOW"
    
    # Obtain certificate
    certbot --apache --non-interactive --agree-tos --email "$ADMIN_EMAIL" -d "$DOMAIN_NAME" --redirect
    
    # Set up auto-renewal
    echo "0 12 * * * /usr/bin/certbot renew --quiet" | crontab -
    
    # Update .env with HTTPS URL
    cd "$APP_DIR"
    sed -i "s|APP_URL=.*|APP_URL=https://${DOMAIN_NAME}|" .env
    php artisan config:cache
    
    print_message "✅ SSL certificate configured" "$GREEN"
}

# ====================================================================
# FINAL OPTIMIZATIONS
# ====================================================================

final_optimizations() {
    print_section "Final Optimizations & Cleanup"
    
    cd "$APP_DIR"
    
    print_message "🚀 Applying final optimizations..." "$YELLOW"
    
    # Clear all caches
    php artisan cache:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    
    # Optimize Composer autoloader
    COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize
    
    # Set final permissions
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R 775 storage bootstrap/cache
    
    # Enable all services
    systemctl enable apache2 php${PHP_VERSION}-fpm redis-server supervisor
    if [[ "$DB_TYPE" == "postgresql" ]]; then
        systemctl enable postgresql
    else
        systemctl enable mariadb
    fi
    
    # Restart all services
    systemctl restart apache2 php${PHP_VERSION}-fpm redis-server supervisor
    
    print_message "✅ Optimizations complete" "$GREEN"
}

# ====================================================================
# INSTALLATION SUMMARY
# ====================================================================

display_summary() {
    print_section "🎉 Installation Complete!"
    
    local app_url="${DOMAIN_NAME:+https://$DOMAIN_NAME}"
    app_url="${app_url:-http://$SERVER_IP}"
    
    echo
    print_message "╔════════════════════════════════════════════════════════════════╗" "$CYAN"
    print_message "║                    🚀 NESTOGY ERP INSTALLED 🚀                 ║" "$CYAN"
    print_message "╚════════════════════════════════════════════════════════════════╝" "$CYAN"
    echo
    
    print_message "📋 INSTALLATION SUMMARY:" "$BLUE"
    print_message "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" "$BLUE"
    echo
    
    print_message "🌐 Application URL: $app_url" "$GREEN"
    print_message "👤 Admin Email: $ADMIN_EMAIL" "$GREEN"
    print_message "🗄️  Database: $DB_TYPE" "$GREEN"
    print_message "🐘 PHP Version: $PHP_VERSION" "$GREEN"
    print_message "📦 Node.js Version: $(node -v)" "$GREEN"
    echo
    
    print_message "⚡ PERFORMANCE FEATURES:" "$BLUE"
    print_message "━━━━━━━━━━━━━━━━━━━━━━━━━━━━" "$BLUE"
    print_message "✅ Apache Event MPM (high concurrency)" "$WHITE"
    print_message "✅ PHP-FPM with ${PHP_FPM_MAX_CHILDREN} workers" "$WHITE"
    print_message "✅ Redis caching enabled" "$WHITE"
    print_message "✅ Database optimizations applied" "$WHITE"
    print_message "✅ Queue workers running (4 processes)" "$WHITE"
    print_message "✅ Asset compression & caching" "$WHITE"
    echo
    
    print_message "🛡️  SECURITY FEATURES:" "$BLUE"
    print_message "━━━━━━━━━━━━━━━━━━━━━━━━━━" "$BLUE"
    print_message "✅ UFW firewall configured" "$WHITE"
    print_message "✅ Fail2ban protection active" "$WHITE"
    print_message "✅ Security headers enabled" "$WHITE"
    print_message "✅ File permissions secured" "$WHITE"
    if [[ -n "$DOMAIN_NAME" ]]; then
        print_message "✅ SSL certificate installed" "$WHITE"
    fi
    echo
    
    print_message "📁 IMPORTANT PATHS:" "$BLUE"
    print_message "━━━━━━━━━━━━━━━━━━━━━━━" "$BLUE"
    print_message "Application: $APP_DIR" "$WHITE"
    print_message "Apache Config: /etc/apache2/sites-available/nestogy.conf" "$WHITE"
    print_message "PHP-FPM Config: /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf" "$WHITE"
    print_message "Error Logs: /var/log/apache2/nestogy-error.log" "$WHITE"
    print_message "App Logs: $APP_DIR/storage/logs/" "$WHITE"
    echo
    
    print_message "🔧 USEFUL COMMANDS:" "$BLUE"
    print_message "━━━━━━━━━━━━━━━━━━━━━━" "$BLUE"
    print_message "Check logs: sudo tail -f /var/log/apache2/nestogy-error.log" "$WHITE"
    print_message "Restart services: sudo systemctl restart apache2 php${PHP_VERSION}-fpm" "$WHITE"
    print_message "Queue status: sudo supervisorctl status" "$WHITE"
    print_message "Clear cache: cd $APP_DIR && php artisan cache:clear" "$WHITE"
    echo
    
    print_message "📋 NEXT STEPS:" "$PURPLE"
    print_message "━━━━━━━━━━━━━━━━" "$PURPLE"
    print_message "1. 🌐 Visit $app_url to access your Nestogy ERP" "$YELLOW"
    print_message "2. 🔑 Login with your admin credentials" "$YELLOW"
    print_message "3. ⚙️  Complete the setup wizard" "$YELLOW"
    print_message "4. 📧 Configure email settings" "$YELLOW"
    print_message "5. 💾 Set up automated backups" "$YELLOW"
    print_message "6. 📊 Configure monitoring (optional)" "$YELLOW"
    echo
    
    print_message "🆘 NEED HELP?" "$BLUE"
    print_message "━━━━━━━━━━━━━━━" "$BLUE"
    print_message "GitHub: https://github.com/FoleyBridge-Solutions/Nestogy" "$WHITE"
    print_message "Issues: https://github.com/FoleyBridge-Solutions/Nestogy/issues" "$WHITE"
    print_message "Documentation: Check the repository README" "$WHITE"
    echo
    
    print_message "🎊 Thank you for using Nestogy ERP!" "$GREEN"
    print_message "Your high-performance MSP management system is ready! 🚀" "$GREEN"
    echo
}

# ====================================================================
# ERROR HANDLING
# ====================================================================

cleanup_on_error() {
    print_message "❌ Installation failed. Cleaning up..." "$RED"
    
    # Stop services that might have been started
    systemctl stop apache2 2>/dev/null || true
    systemctl stop php${PHP_VERSION}-fpm 2>/dev/null || true
    systemctl stop redis-server 2>/dev/null || true
    systemctl stop supervisor 2>/dev/null || true
    
    if [[ "$DB_TYPE" == "postgresql" ]]; then
        systemctl stop postgresql 2>/dev/null || true
    else
        systemctl stop mariadb 2>/dev/null || true
    fi
    
    print_message "Please check the error messages above and try again." "$YELLOW"
    print_message "For support, visit: https://github.com/FoleyBridge-Solutions/Nestogy/issues" "$YELLOW"
    
    exit 1
}

# ====================================================================
# MAIN INSTALLATION FLOW
# ====================================================================

main() {
    # Set error trap
    trap cleanup_on_error ERR
    
    # Display banner
    print_banner
    
    # Pre-flight checks
    check_root
    detect_os
    get_server_ip
    check_system_requirements
    
    # Collect user input
    collect_user_input
    
    # Start installation
    print_message "🚀 Starting Nestogy ERP installation..." "$GREEN"
    sleep 2
    
    # Installation steps
    install_system_dependencies
    install_database
    install_apache_php
    install_additional_tools
    install_application
    configure_application
    set_permissions
    configure_security
    configure_apache_vhost
    setup_background_services
    setup_ssl
    final_optimizations
    
    # Display results
    display_summary
}

# ====================================================================
# SCRIPT EXECUTION
# ====================================================================

# Check if script is being sourced or executed
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi

exit 0