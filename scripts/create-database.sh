#!/bin/bash

# Nestogy ERP - Database Setup Script
# This script creates the database, user, and runs migrations

set -e  # Exit on error

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default values
DEFAULT_DB_HOST="localhost"
DEFAULT_DB_PORT="3306"
DEFAULT_DB_NAME="nestogy_erp"
DEFAULT_DB_USER="nestogy_user"

# Function to print colored output
print_message() {
    echo -e "${2}${1}${NC}"
}

# Function to check if running as root
check_root() {
    if [[ $EUID -eq 0 ]]; then
        print_message "Warning: Running as root. Database operations will use root MySQL user." "$YELLOW"
    fi
}

# Function to find application directory
find_app_dir() {
    # Check if we're in the application directory
    if [[ -f "artisan" && -f "composer.json" ]]; then
        APP_DIR=$(pwd)
    # Check if we're in the scripts directory
    elif [[ -f "../artisan" && -f "../composer.json" ]]; then
        APP_DIR=$(dirname $(pwd))
    # Check default location
    elif [[ -d "/var/www/html/nestogy-laravel" ]]; then
        APP_DIR="/var/www/html/nestogy-laravel"
    else
        print_message "Cannot find Nestogy application directory!" "$RED"
        print_message "Please run this script from the application root or scripts directory." "$YELLOW"
        exit 1
    fi
    
    print_message "Application directory: $APP_DIR" "$BLUE"
}

# Function to check MySQL installation
check_mysql() {
    if ! command -v mysql &> /dev/null; then
        print_message "MySQL client not found!" "$RED"
        print_message "Please install MySQL: sudo apt-get install mysql-client" "$YELLOW"
        exit 1
    fi
    
    # Check if MySQL server is running
    if ! systemctl is-active --quiet mysql && ! systemctl is-active --quiet mariadb; then
        print_message "MySQL/MariaDB server is not running!" "$RED"
        print_message "Please start the service: sudo systemctl start mysql" "$YELLOW"
        exit 1
    fi
    
    print_message "✓ MySQL is installed and running" "$GREEN"
}

# Function to generate secure password
generate_password() {
    # Generate a secure random password
    if command -v openssl &> /dev/null; then
        openssl rand -base64 16
    else
        # Fallback to /dev/urandom
        < /dev/urandom tr -dc 'A-Za-z0-9!@#$%^&*()_+=' | head -c 16
    fi
}

# Function to get database configuration
get_db_config() {
    print_message "\n=== Database Configuration ===" "$BLUE"
    
    # Check if .env file exists and read current values
    if [[ -f "$APP_DIR/.env" ]]; then
        print_message "Reading configuration from .env file..." "$YELLOW"
        
        # Read values from .env
        DB_HOST=$(grep "^DB_HOST=" "$APP_DIR/.env" | cut -d'=' -f2 | tr -d '"' || echo "$DEFAULT_DB_HOST")
        DB_PORT=$(grep "^DB_PORT=" "$APP_DIR/.env" | cut -d'=' -f2 | tr -d '"' || echo "$DEFAULT_DB_PORT")
        DB_NAME=$(grep "^DB_DATABASE=" "$APP_DIR/.env" | cut -d'=' -f2 | tr -d '"' || echo "$DEFAULT_DB_NAME")
        DB_USER=$(grep "^DB_USERNAME=" "$APP_DIR/.env" | cut -d'=' -f2 | tr -d '"' || echo "$DEFAULT_DB_USER")
        DB_PASS=$(grep "^DB_PASSWORD=" "$APP_DIR/.env" | cut -d'=' -f2 | tr -d '"' || echo "")
    else
        DB_HOST=$DEFAULT_DB_HOST
        DB_PORT=$DEFAULT_DB_PORT
        DB_NAME=$DEFAULT_DB_NAME
        DB_USER=$DEFAULT_DB_USER
        DB_PASS=""
    fi
    
    # Interactive configuration
    print_message "\nEnter database configuration (press Enter for defaults):" "$YELLOW"
    
    read -p "Database Host [$DB_HOST]: " INPUT_HOST
    DB_HOST=${INPUT_HOST:-$DB_HOST}
    
    read -p "Database Port [$DB_PORT]: " INPUT_PORT
    DB_PORT=${INPUT_PORT:-$DB_PORT}
    
    read -p "Database Name [$DB_NAME]: " INPUT_NAME
    DB_NAME=${INPUT_NAME:-$DB_NAME}
    
    read -p "Database User [$DB_USER]: " INPUT_USER
    DB_USER=${INPUT_USER:-$DB_USER}
    
    # Password handling
    if [[ -z "$DB_PASS" ]]; then
        print_message "\nPassword options:" "$YELLOW"
        print_message "1. Generate secure password (recommended)" "$YELLOW"
        print_message "2. Enter custom password" "$YELLOW"
        read -p "Choose option [1]: " PASS_OPTION
        
        if [[ "$PASS_OPTION" == "2" ]]; then
            read -sp "Enter password: " DB_PASS
            echo
        else
            DB_PASS=$(generate_password)
            print_message "\nGenerated password: $DB_PASS" "$GREEN"
            print_message "Please save this password securely!" "$YELLOW"
        fi
    else
        print_message "Using existing password from .env file" "$YELLOW"
    fi
    
    # MySQL root credentials
    print_message "\nMySQL root access required to create database and user." "$YELLOW"
    read -p "MySQL root user [root]: " MYSQL_ROOT_USER
    MYSQL_ROOT_USER=${MYSQL_ROOT_USER:-root}
    
    read -sp "MySQL root password: " MYSQL_ROOT_PASS
    echo
}

# Function to test MySQL connection
test_mysql_connection() {
    print_message "\n=== Testing MySQL Connection ===" "$BLUE"
    
    if mysql -h"$DB_HOST" -P"$DB_PORT" -u"$MYSQL_ROOT_USER" -p"$MYSQL_ROOT_PASS" -e "SELECT 1" &>/dev/null; then
        print_message "✓ Successfully connected to MySQL" "$GREEN"
        return 0
    else
        print_message "✗ Failed to connect to MySQL" "$RED"
        print_message "Please check your credentials and try again." "$YELLOW"
        return 1
    fi
}

# Function to create database
create_database() {
    print_message "\n=== Creating Database ===" "$BLUE"
    
    # Create database
    print_message "Creating database: $DB_NAME" "$YELLOW"
    mysql -h"$DB_HOST" -P"$DB_PORT" -u"$MYSQL_ROOT_USER" -p"$MYSQL_ROOT_PASS" <<EOF
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;
EOF
    
    if [[ $? -eq 0 ]]; then
        print_message "✓ Database created successfully" "$GREEN"
    else
        print_message "✗ Failed to create database" "$RED"
        exit 1
    fi
}

# Function to create user and grant privileges
create_user() {
    print_message "\n=== Creating Database User ===" "$BLUE"
    
    print_message "Creating user: $DB_USER" "$YELLOW"
    
    # Create user and grant privileges
    mysql -h"$DB_HOST" -P"$DB_PORT" -u"$MYSQL_ROOT_USER" -p"$MYSQL_ROOT_PASS" <<EOF
-- Drop user if exists (for re-runs)
DROP USER IF EXISTS '$DB_USER'@'%';
DROP USER IF EXISTS '$DB_USER'@'localhost';

-- Create user
CREATE USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
CREATE USER '$DB_USER'@'%' IDENTIFIED BY '$DB_PASS';

-- Grant privileges
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'%';

-- Apply changes
FLUSH PRIVILEGES;
EOF
    
    if [[ $? -eq 0 ]]; then
        print_message "✓ User created and privileges granted" "$GREEN"
    else
        print_message "✗ Failed to create user" "$RED"
        exit 1
    fi
}

# Function to test user connection
test_user_connection() {
    print_message "\n=== Testing User Connection ===" "$BLUE"
    
    if mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT 1" &>/dev/null; then
        print_message "✓ User can connect to database successfully" "$GREEN"
        return 0
    else
        print_message "✗ User cannot connect to database" "$RED"
        return 1
    fi
}

# Function to update .env file
update_env_file() {
    print_message "\n=== Updating .env File ===" "$BLUE"
    
    cd $APP_DIR
    
    # Create .env from .env.example if it doesn't exist
    if [[ ! -f ".env" ]]; then
        if [[ -f ".env.example" ]]; then
            cp .env.example .env
            print_message "Created .env from .env.example" "$YELLOW"
        else
            print_message "✗ No .env or .env.example file found!" "$RED"
            return 1
        fi
    fi
    
    # Backup current .env
    cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
    print_message "Backed up .env file" "$YELLOW"
    
    # Update database configuration
    sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=mysql/" .env
    sed -i "s/^DB_HOST=.*/DB_HOST=$DB_HOST/" .env
    sed -i "s/^DB_PORT=.*/DB_PORT=$DB_PORT/" .env
    sed -i "s/^DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" .env
    sed -i "s/^DB_USERNAME=.*/DB_USERNAME=$DB_USER/" .env
    sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=$DB_PASS/" .env
    
    print_message "✓ Updated .env file with database configuration" "$GREEN"
}

# Function to run migrations
run_migrations() {
    print_message "\n=== Running Database Migrations ===" "$BLUE"
    
    cd $APP_DIR
    
    # Check if artisan exists
    if [[ ! -f "artisan" ]]; then
        print_message "✗ artisan file not found!" "$RED"
        return 1
    fi
    
    # Generate application key if needed
    if ! grep -q "^APP_KEY=base64:" .env; then
        print_message "Generating application key..." "$YELLOW"
        php artisan key:generate
    fi
    
    # Clear cache
    print_message "Clearing cache..." "$YELLOW"
    php artisan config:clear
    php artisan cache:clear
    
    # Run migrations
    print_message "Running migrations..." "$YELLOW"
    php artisan migrate --force
    
    if [[ $? -eq 0 ]]; then
        print_message "✓ Migrations completed successfully" "$GREEN"
    else
        print_message "✗ Migration failed" "$RED"
        return 1
    fi
}

# Function to run seeders
run_seeders() {
    print_message "\n=== Running Database Seeders ===" "$BLUE"
    
    cd $APP_DIR
    
    read -p "Do you want to run database seeders? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        print_message "Running seeders..." "$YELLOW"
        php artisan db:seed --force
        
        if [[ $? -eq 0 ]]; then
            print_message "✓ Seeders completed successfully" "$GREEN"
        else
            print_message "✗ Seeding failed" "$RED"
        fi
    fi
}

# Function to display summary
display_summary() {
    print_message "\n=== Database Setup Complete ===" "$GREEN"
    
    print_message "\nDatabase Configuration:" "$BLUE"
    print_message "• Host: $DB_HOST" "$YELLOW"
    print_message "• Port: $DB_PORT" "$YELLOW"
    print_message "• Database: $DB_NAME" "$YELLOW"
    print_message "• Username: $DB_USER" "$YELLOW"
    print_message "• Password: [Saved in .env]" "$YELLOW"
    
    print_message "\nConnection String:" "$BLUE"
    print_message "mysql -h$DB_HOST -P$DB_PORT -u$DB_USER -p $DB_NAME" "$YELLOW"
    
    print_message "\nNext Steps:" "$BLUE"
    print_message "1. Verify application can connect to database" "$YELLOW"
    print_message "2. Set up regular database backups" "$YELLOW"
    print_message "3. Configure database optimization" "$YELLOW"
    print_message "4. Review security settings" "$YELLOW"
}

# Function to perform database backup
backup_database() {
    print_message "\n=== Creating Database Backup ===" "$BLUE"
    
    BACKUP_DIR="$APP_DIR/storage/app/backups"
    mkdir -p "$BACKUP_DIR"
    
    BACKUP_FILE="$BACKUP_DIR/db_backup_$(date +%Y%m%d_%H%M%S).sql"
    
    print_message "Creating backup: $BACKUP_FILE" "$YELLOW"
    
    mysqldump -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_FILE"
    
    if [[ $? -eq 0 ]]; then
        gzip "$BACKUP_FILE"
        print_message "✓ Backup created: ${BACKUP_FILE}.gz" "$GREEN"
    else
        print_message "✗ Backup failed" "$RED"
    fi
}

# Function for advanced options
advanced_options() {
    print_message "\n=== Advanced Database Options ===" "$BLUE"
    
    print_message "1. Drop and recreate database (CAUTION: Data loss!)" "$YELLOW"
    print_message "2. Import SQL file" "$YELLOW"
    print_message "3. Export database" "$YELLOW"
    print_message "4. Check database tables" "$YELLOW"
    print_message "5. Optimize database" "$YELLOW"
    print_message "6. Skip advanced options" "$YELLOW"
    
    read -p "Choose option [6]: " OPTION
    OPTION=${OPTION:-6}
    
    case $OPTION in
        1)
            print_message "\nWARNING: This will delete all data!" "$RED"
            read -p "Are you sure? Type 'yes' to confirm: " CONFIRM
            if [[ "$CONFIRM" == "yes" ]]; then
                mysql -h"$DB_HOST" -P"$DB_PORT" -u"$MYSQL_ROOT_USER" -p"$MYSQL_ROOT_PASS" -e "DROP DATABASE IF EXISTS \`$DB_NAME\`;"
                create_database
                run_migrations
            fi
            ;;
        2)
            read -p "Enter SQL file path: " SQL_FILE
            if [[ -f "$SQL_FILE" ]]; then
                mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$SQL_FILE"
                print_message "✓ SQL file imported" "$GREEN"
            else
                print_message "✗ File not found" "$RED"
            fi
            ;;
        3)
            backup_database
            ;;
        4)
            mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW TABLES;"
            ;;
        5)
            mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW TABLES;" | grep -v Tables_in | while read table; do
                mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "OPTIMIZE TABLE \`$table\`;"
            done
            print_message "✓ Database optimized" "$GREEN"
            ;;
    esac
}

# Main function
main() {
    print_message "=====================================" "$BLUE"
    print_message "  Nestogy ERP - Database Setup Tool  " "$BLUE"
    print_message "=====================================" "$BLUE"
    
    check_root
    find_app_dir
    check_mysql
    get_db_config
    
    # Test connection
    if ! test_mysql_connection; then
        exit 1
    fi
    
    # Create database and user
    create_database
    create_user
    
    # Test user connection
    if ! test_user_connection; then
        print_message "Failed to verify user connection. Please check the configuration." "$RED"
        exit 1
    fi
    
    # Update .env file
    update_env_file
    
    # Run migrations
    run_migrations
    
    # Run seeders (optional)
    run_seeders
    
    # Advanced options
    advanced_options
    
    # Display summary
    display_summary
}

# Run main function
main

exit 0