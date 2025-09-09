#!/bin/bash

# Nestogy Quick Database Setup Script
# This script assumes MariaDB is already installed and running

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DB_NAME="nestogy"
DB_USER="nestogy_user"
DB_CHARSET="utf8mb4"
DB_COLLATION="utf8mb4_unicode_ci"

# Function to print colored output
print_message() {
    local color=$1
    local message=$2
    echo -e "${color}${message}${NC}"
}

# Function to generate secure password
generate_password() {
    openssl rand -base64 32 | tr -d "=+/" | cut -c1-25
}

# Function to prompt for input with default
prompt_with_default() {
    local prompt=$1
    local default=$2
    local response

    read -p "$prompt [$default]: " response
    echo "${response:-$default}"
}

# Banner
print_message $BLUE "
╔═══════════════════════════════════════════╗
║     Nestogy Quick Database Setup          ║
║     MariaDB Database Configuration        ║
╚═══════════════════════════════════════════╝
"

# Check if running from scripts directory
if [[ ! -f "quick-db-setup.sh" ]]; then
    print_message $RED "Please run this script from the scripts directory."
    exit 1
fi

# Check if MariaDB is running
if ! systemctl is-active --quiet mariadb && ! systemctl is-active --quiet mysql; then
    print_message $RED "MariaDB/MySQL service is not running!"
    print_message $YELLOW "Please start MariaDB first: sudo systemctl start mariadb"
    exit 1
fi

print_message $GREEN "MariaDB service is running."

# Get database configuration
print_message $YELLOW "\nDatabase Configuration:"
DB_NAME=$(prompt_with_default "Database name" "$DB_NAME")
DB_USER=$(prompt_with_default "Database user" "$DB_USER")

# Ask if user wants to generate password
read -p "Generate secure password automatically? (y/n) [y]: " -n 1 -r
echo
if [[ $REPLY =~ ^[Nn]$ ]]; then
    read -s -p "Enter database password: " DB_PASSWORD
    echo
    read -s -p "Confirm database password: " DB_PASSWORD_CONFIRM
    echo
    if [[ "$DB_PASSWORD" != "$DB_PASSWORD_CONFIRM" ]]; then
        print_message $RED "Passwords do not match!"
        exit 1
    fi
else
    DB_PASSWORD=$(generate_password)
    print_message $GREEN "Generated secure password."
fi

# Get MariaDB root credentials
print_message $YELLOW "\nMariaDB Root Access:"
read -p "Enter MariaDB root user [root]: " ROOT_USER
ROOT_USER=${ROOT_USER:-root}

# Check if we can connect without password (common in development)
if mysql -u "$ROOT_USER" -e "SELECT 1" >/dev/null 2>&1; then
    ROOT_PASSWORD=""
    print_message $GREEN "Connected to MariaDB without password."
else
    read -s -p "Enter MariaDB root password: " ROOT_PASSWORD
    echo
    
    # Test connection
    if ! mysql -u "$ROOT_USER" -p"$ROOT_PASSWORD" -e "SELECT 1" >/dev/null 2>&1; then
        print_message $RED "Failed to connect to MariaDB. Please check your credentials."
        exit 1
    fi
fi

# Create database and user
print_message $YELLOW "\nCreating database and user..."

# Build MySQL command
if [[ -z "$ROOT_PASSWORD" ]]; then
    MYSQL_CMD="mysql -u $ROOT_USER"
else
    MYSQL_CMD="mysql -u $ROOT_USER -p$ROOT_PASSWORD"
fi

# Execute database setup
$MYSQL_CMD <<EOF
-- Create database
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET ${DB_CHARSET} COLLATE ${DB_COLLATION};

-- Create user (drop if exists to avoid errors)
DROP USER IF EXISTS '${DB_USER}'@'localhost';
CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';

-- Grant privileges
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';

-- Additional privileges for Laravel
GRANT CREATE, ALTER, DROP, INDEX, REFERENCES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;

-- Show confirmation
SELECT 'Database created successfully!' AS Status;
EOF

if [[ $? -eq 0 ]]; then
    print_message $GREEN "Database and user created successfully!"
else
    print_message $RED "Failed to create database. Please check the error messages above."
    exit 1
fi

# Update .env file
print_message $YELLOW "\nUpdating .env file..."

ENV_FILE="../.env"

# Check if .env exists
if [[ ! -f $ENV_FILE ]]; then
    if [[ -f "../.env.example" ]]; then
        print_message $YELLOW ".env file not found. Creating from .env.example..."
        cp "../.env.example" "$ENV_FILE"
    else
        print_message $RED ".env.example not found. Creating basic .env file..."
        cat > "$ENV_FILE" << EOF
APP_NAME=Nestogy
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASSWORD}

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"
EOF
    fi
fi

# Update database configuration in .env
sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=mysql/" "$ENV_FILE"
sed -i "s/DB_HOST=.*/DB_HOST=127.0.0.1/" "$ENV_FILE"
sed -i "s/DB_PORT=.*/DB_PORT=3306/" "$ENV_FILE"
sed -i "s/DB_DATABASE=.*/DB_DATABASE=${DB_NAME}/" "$ENV_FILE"
sed -i "s/DB_USERNAME=.*/DB_USERNAME=${DB_USER}/" "$ENV_FILE"
sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" "$ENV_FILE"

print_message $GREEN ".env file updated successfully!"

# Test database connection
print_message $YELLOW "\nTesting database connection..."

cd ..

# Check if vendor directory exists
if [[ ! -d "vendor" ]]; then
    print_message $YELLOW "Installing Composer dependencies..."
    if command -v composer >/dev/null 2>&1; then
        composer install --no-interaction --prefer-dist --optimize-autoloader
    else
        print_message $RED "Composer is not installed. Please install Composer first."
        print_message $YELLOW "Visit: https://getcomposer.org/download/"
        exit 1
    fi
fi

# Generate application key if needed
if ! grep -q "APP_KEY=base64:" .env; then
    print_message $YELLOW "Generating application key..."
    php artisan key:generate
fi

# Test connection
if php artisan db:show >/dev/null 2>&1; then
    print_message $GREEN "Database connection successful!"
else
    print_message $RED "Failed to connect to database. Please check your configuration."
    exit 1
fi

# Ask about migrations
read -p "Run database migrations now? (y/n) [y]: " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Nn]$ ]]; then
    print_message $YELLOW "Running migrations..."
    if php artisan migrate --force; then
        print_message $GREEN "Migrations completed successfully!"
    else
        print_message $RED "Migration failed. Please check the error messages."
        exit 1
    fi
fi

# Ask about seeding
read -p "Seed the database with admin user? (y/n) [y]: " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Nn]$ ]]; then
    print_message $YELLOW "Seeding database..."
    if php artisan db:seed --class=AdminUserSeeder; then
        print_message $GREEN "Database seeded successfully!"
    else
        print_message $YELLOW "Seeding failed. You can run it manually later with:"
        print_message $YELLOW "php artisan db:seed --class=AdminUserSeeder"
    fi
fi

# Summary
print_message $BLUE "
╔═══════════════════════════════════════════╗
║           Setup Complete!                 ║
╚═══════════════════════════════════════════╝
"
print_message $GREEN "Database Configuration:"
print_message $GREEN "  Database: ${DB_NAME}"
print_message $GREEN "  Username: ${DB_USER}"
print_message $GREEN "  Password: [Saved in .env]"
print_message $GREEN "  Host: 127.0.0.1"
print_message $GREEN "  Port: 3306"

if [[ ! $REPLY =~ ^[Nn]$ ]]; then
    print_message $YELLOW "\nDefault Admin Credentials:"
    print_message $YELLOW "  Email: admin@nestogy.com"
    print_message $YELLOW "  Password: Admin@123456"
    print_message $RED "\n⚠️  IMPORTANT: Change the admin password after first login!"
fi

print_message $BLUE "\nNext Steps:"
print_message $BLUE "1. Start the development server: php artisan serve"
print_message $BLUE "2. Visit http://localhost:8000"
print_message $BLUE "3. Log in with the admin credentials"
print_message $BLUE "4. Change the admin password"

# Save connection info
cat > ~/.nestogy_db_info << EOF
# Nestogy Database Connection Info
# Generated on $(date)
Database: ${DB_NAME}
Username: ${DB_USER}
Host: 127.0.0.1
Port: 3306
EOF

print_message $GREEN "\nDatabase info saved to: ~/.nestogy_db_info"