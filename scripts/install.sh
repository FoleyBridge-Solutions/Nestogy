#!/bin/bash

# Nestogy ERP Installation Script
# This script automates the installation of Nestogy ERP on Ubuntu/Debian systems

set -e  # Exit on error

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration variables
APP_DIR="/var/www/html/nestogy-laravel"
DB_NAME="nestogy_erp"
DB_USER="nestogy_user"
APACHE_CONF="nestogy.conf"

# Function to print colored output
print_message() {
    echo -e "${2}${1}${NC}"
}

# Function to check if running as root
check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_message "This script must be run as root or with sudo" "$RED"
        exit 1
    fi
}

# Function to detect OS
detect_os() {
    if [[ -f /etc/os-release ]]; then
        . /etc/os-release
        OS=$ID
        VER=$VERSION_ID
    else
        print_message "Cannot detect OS version" "$RED"
        exit 1
    fi
}

# Function to check system requirements
check_requirements() {
    print_message "\n=== Checking System Requirements ===" "$BLUE"
    
    # Check PHP version
    if command -v php &> /dev/null; then
        PHP_VERSION=$(php -r "echo PHP_VERSION;")
        print_message "✓ PHP version: $PHP_VERSION" "$GREEN"
    else
        print_message "✗ PHP not installed" "$RED"
        INSTALL_PHP=true
    fi
    
    # Check MySQL
    if command -v mysql &> /dev/null; then
        print_message "✓ MySQL is installed" "$GREEN"
    else
        print_message "✗ MySQL not installed" "$RED"
        INSTALL_MYSQL=true
    fi
    
    # Check Apache
    if command -v apache2 &> /dev/null; then
        print_message "✓ Apache2 is installed" "$GREEN"
    else
        print_message "✗ Apache2 not installed" "$RED"
        INSTALL_APACHE=true
    fi
    
    # Check Composer
    if command -v composer &> /dev/null; then
        print_message "✓ Composer is installed" "$GREEN"
    else
        print_message "✗ Composer not installed" "$RED"
        INSTALL_COMPOSER=true
    fi
    
    # Check Node.js
    if command -v node &> /dev/null; then
        NODE_VERSION=$(node -v)
        print_message "✓ Node.js version: $NODE_VERSION" "$GREEN"
    else
        print_message "✗ Node.js not installed" "$RED"
        INSTALL_NODE=true
    fi
}

# Function to install system dependencies
install_dependencies() {
    print_message "\n=== Installing System Dependencies ===" "$BLUE"
    
    # Update package list
    apt-get update
    
    # Install Apache if needed
    if [[ $INSTALL_APACHE == true ]]; then
        print_message "Installing Apache2..." "$YELLOW"
        apt-get install -y apache2
    fi
    
    # Install MySQL if needed
    if [[ $INSTALL_MYSQL == true ]]; then
        print_message "Installing MySQL Server..." "$YELLOW"
        apt-get install -y mysql-server
    fi
    
    # Install PHP and extensions
    if [[ $INSTALL_PHP == true ]]; then
        print_message "Installing PHP 8.2 and extensions..." "$YELLOW"
        apt-get install -y software-properties-common
        add-apt-repository -y ppa:ondrej/php
        apt-get update
    fi
    
    # Install PHP extensions
    print_message "Installing PHP extensions..." "$YELLOW"
    apt-get install -y \
        php8.2 \
        php8.2-cli \
        php8.2-common \
        php8.2-mysql \
        php8.2-xml \
        php8.2-xmlrpc \
        php8.2-curl \
        php8.2-gd \
        php8.2-imagick \
        php8.2-dev \
        php8.2-imap \
        php8.2-mbstring \
        php8.2-opcache \
        php8.2-redis \
        php8.2-soap \
        php8.2-zip \
        php8.2-intl \
        php8.2-bcmath \
        php8.2-fpm \
        libapache2-mod-php8.2
    
    # Install additional tools
    print_message "Installing additional tools..." "$YELLOW"
    apt-get install -y git unzip curl wget redis-server supervisor
    
    # Install Composer if needed
    if [[ $INSTALL_COMPOSER == true ]]; then
        print_message "Installing Composer..." "$YELLOW"
        curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    fi
    
    # Install Node.js if needed
    if [[ $INSTALL_NODE == true ]]; then
        print_message "Installing Node.js 18..." "$YELLOW"
        curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
        apt-get install -y nodejs
    fi
    
    print_message "✓ All dependencies installed successfully" "$GREEN"
}

# Function to configure MySQL
configure_mysql() {
    print_message "\n=== Configuring MySQL Database ===" "$BLUE"
    
    # Generate random password
    DB_PASS=$(openssl rand -base64 12)
    
    # Create database and user
    mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
    mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
    mysql -e "FLUSH PRIVILEGES;"
    
    print_message "✓ Database configured successfully" "$GREEN"
    print_message "Database Name: ${DB_NAME}" "$YELLOW"
    print_message "Database User: ${DB_USER}" "$YELLOW"
    print_message "Database Password: ${DB_PASS}" "$YELLOW"
    
    # Save credentials for later use
    echo "DB_DATABASE=${DB_NAME}" > /tmp/nestogy_db_credentials.txt
    echo "DB_USERNAME=${DB_USER}" >> /tmp/nestogy_db_credentials.txt
    echo "DB_PASSWORD=${DB_PASS}" >> /tmp/nestogy_db_credentials.txt
}

# Function to install application
install_application() {
    print_message "\n=== Installing Nestogy ERP Application ===" "$BLUE"
    
    cd $(dirname $APP_DIR)
    
    # Check if directory already exists
    if [[ -d "$APP_DIR" ]]; then
        print_message "Application directory already exists. Backing up..." "$YELLOW"
        mv $APP_DIR ${APP_DIR}_backup_$(date +%Y%m%d_%H%M%S)
    fi
    
    # Clone repository (using current directory if it's the repo)
    if [[ -f "composer.json" && -f "artisan" ]]; then
        print_message "Using current directory as application source..." "$YELLOW"
        APP_DIR=$(pwd)
    else
        print_message "Please provide the Git repository URL:" "$YELLOW"
        read -p "Repository URL: " REPO_URL
        git clone $REPO_URL nestogy-laravel
        cd nestogy-laravel
        APP_DIR=$(pwd)
    fi
    
    # Install PHP dependencies
    print_message "Installing PHP dependencies..." "$YELLOW"
    composer install --no-dev --optimize-autoloader
    
    # Install Node dependencies and build assets
    print_message "Installing Node dependencies and building assets..." "$YELLOW"
    npm install
    npm run build
    
    print_message "✓ Application installed successfully" "$GREEN"
}

# Function to configure environment
configure_environment() {
    print_message "\n=== Configuring Environment ===" "$BLUE"
    
    cd $APP_DIR
    
    # Copy environment file
    cp .env.example .env
    
    # Load database credentials
    source /tmp/nestogy_db_credentials.txt
    
    # Update .env file
    sed -i "s/APP_NAME=.*/APP_NAME=\"Nestogy ERP\"/" .env
    sed -i "s/APP_ENV=.*/APP_ENV=production/" .env
    sed -i "s/APP_DEBUG=.*/APP_DEBUG=false/" .env
    sed -i "s|APP_URL=.*|APP_URL=http://$(hostname -I | awk '{print $1}')|" .env
    
    sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=mysql/" .env
    sed -i "s/DB_HOST=.*/DB_HOST=127.0.0.1/" .env
    sed -i "s/DB_PORT=.*/DB_PORT=3306/" .env
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=${DB_DATABASE}/" .env
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=${DB_USERNAME}/" .env
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" .env
    
    # Generate application key
    php artisan key:generate
    
    # Run migrations
    print_message "Running database migrations..." "$YELLOW"
    php artisan migrate --force
    
    # Create storage link
    php artisan storage:link
    
    # Cache configuration
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    print_message "✓ Environment configured successfully" "$GREEN"
}

# Function to set permissions
set_permissions() {
    print_message "\n=== Setting File Permissions ===" "$BLUE"
    
    cd $APP_DIR
    
    # Set ownership
    chown -R www-data:www-data .
    
    # Set directory permissions
    find . -type d -exec chmod 755 {} \;
    
    # Set file permissions
    find . -type f -exec chmod 644 {} \;
    
    # Set storage and cache permissions
    chmod -R 775 storage
    chmod -R 775 bootstrap/cache
    
    # Secure .env file
    chmod 600 .env
    
    print_message "✓ Permissions set successfully" "$GREEN"
}

# Function to configure Apache
configure_apache() {
    print_message "\n=== Configuring Apache ===" "$BLUE"
    
    # Copy Apache configuration
    if [[ -f "$APP_DIR/docs/apache/nestogy.conf" ]]; then
        cp $APP_DIR/docs/apache/nestogy.conf /etc/apache2/sites-available/
    else
        # Create basic configuration
        cat > /etc/apache2/sites-available/nestogy.conf <<EOF
<VirtualHost *:80>
    ServerName $(hostname -I | awk '{print $1}')
    DocumentRoot $APP_DIR/public
    
    <Directory $APP_DIR/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/nestogy-error.log
    CustomLog \${APACHE_LOG_DIR}/nestogy-access.log combined
</VirtualHost>
EOF
    fi
    
    # Enable required modules
    a2enmod rewrite headers ssl proxy proxy_fcgi
    
    # Enable the site
    a2ensite nestogy.conf
    
    # Disable default site
    a2dissite 000-default.conf
    
    # Test configuration
    apache2ctl configtest
    
    # Restart Apache
    systemctl restart apache2
    
    print_message "✓ Apache configured successfully" "$GREEN"
}

# Function to configure cron jobs
configure_cron() {
    print_message "\n=== Configuring Cron Jobs ===" "$BLUE"
    
    # Add Laravel scheduler cron job
    (crontab -u www-data -l 2>/dev/null; echo "* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1") | crontab -u www-data -
    
    print_message "✓ Cron jobs configured successfully" "$GREEN"
}

# Function to configure firewall
configure_firewall() {
    print_message "\n=== Configuring Firewall ===" "$BLUE"
    
    # Check if ufw is installed
    if command -v ufw &> /dev/null; then
        ufw allow 22/tcp
        ufw allow 80/tcp
        ufw allow 443/tcp
        echo "y" | ufw enable
        print_message "✓ Firewall configured successfully" "$GREEN"
    else
        print_message "UFW not installed. Skipping firewall configuration." "$YELLOW"
    fi
}

# Function to create admin user
create_admin_user() {
    print_message "\n=== Creating Admin User ===" "$BLUE"
    
    cd $APP_DIR
    
    print_message "Enter admin user details:" "$YELLOW"
    read -p "Name: " ADMIN_NAME
    read -p "Email: " ADMIN_EMAIL
    read -sp "Password: " ADMIN_PASSWORD
    echo
    
    # Create admin user using tinker
    php artisan tinker --execute="
        \$user = new App\Models\User();
        \$user->name = '$ADMIN_NAME';
        \$user->email = '$ADMIN_EMAIL';
        \$user->password = Hash::make('$ADMIN_PASSWORD');
        \$user->email_verified_at = now();
        \$user->save();
        echo 'Admin user created successfully';
    "
    
    print_message "✓ Admin user created successfully" "$GREEN"
}

# Function to display summary
display_summary() {
    print_message "\n=== Installation Complete ===" "$GREEN"
    print_message "\nApplication Details:" "$BLUE"
    print_message "URL: http://$(hostname -I | awk '{print $1}')" "$YELLOW"
    print_message "Admin Email: $ADMIN_EMAIL" "$YELLOW"
    print_message "\nDatabase Details:" "$BLUE"
    print_message "Database: $DB_NAME" "$YELLOW"
    print_message "Username: $DB_USER" "$YELLOW"
    print_message "Password: Saved in .env file" "$YELLOW"
    print_message "\nImportant Files:" "$BLUE"
    print_message "Application: $APP_DIR" "$YELLOW"
    print_message "Apache Config: /etc/apache2/sites-available/nestogy.conf" "$YELLOW"
    print_message "Error Logs: /var/log/apache2/nestogy-error.log" "$YELLOW"
    print_message "\nNext Steps:" "$BLUE"
    print_message "1. Configure SSL certificate (recommended)" "$YELLOW"
    print_message "2. Set up email configuration in .env" "$YELLOW"
    print_message "3. Configure backups" "$YELLOW"
    print_message "4. Review security settings" "$YELLOW"
    
    # Clean up
    rm -f /tmp/nestogy_db_credentials.txt
}

# Main installation flow
main() {
    print_message "==================================" "$BLUE"
    print_message "  Nestogy ERP Installation Script  " "$BLUE"
    print_message "==================================" "$BLUE"
    
    check_root
    detect_os
    check_requirements
    
    print_message "\nThis script will install Nestogy ERP on your system." "$YELLOW"
    print_message "It will install required dependencies and configure the application." "$YELLOW"
    read -p "Do you want to continue? (y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_message "Installation cancelled." "$RED"
        exit 1
    fi
    
    install_dependencies
    configure_mysql
    install_application
    configure_environment
    set_permissions
    configure_apache
    configure_cron
    configure_firewall
    create_admin_user
    display_summary
}

# Run main function
main

exit 0