#!/bin/bash

# Nestogy MariaDB Setup Script
# This script sets up MariaDB for the Nestogy Laravel application

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
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

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to check if MariaDB is installed
check_mariadb() {
    if command_exists mariadb || command_exists mysql; then
        return 0
    else
        return 1
    fi
}

# Function to install MariaDB
install_mariadb() {
    print_message $YELLOW "MariaDB is not installed. Installing MariaDB..."
    
    # Detect OS
    if [[ -f /etc/debian_version ]]; then
        # Debian/Ubuntu
        sudo apt-get update
        sudo apt-get install -y mariadb-server mariadb-client
    elif [[ -f /etc/redhat-release ]]; then
        # RHEL/CentOS/Fedora
        sudo yum install -y mariadb-server mariadb
    elif [[ -f /etc/arch-release ]]; then
        # Arch Linux
        sudo pacman -S --noconfirm mariadb
    else
        print_message $RED "Unsupported operating system. Please install MariaDB manually."
        exit 1
    fi
    
    # Start and enable MariaDB
    sudo systemctl start mariadb
    sudo systemctl enable mariadb
    
    print_message $GREEN "MariaDB installed successfully!"
}

# Function to secure MariaDB installation
secure_mariadb() {
    print_message $YELLOW "Securing MariaDB installation..."
    
    # Generate root password
    ROOT_PASSWORD=$(generate_password)
    
    # Secure installation
    sudo mysql -e "UPDATE mysql.user SET Password=PASSWORD('${ROOT_PASSWORD}') WHERE User='root';"
    sudo mysql -e "DELETE FROM mysql.user WHERE User='';"
    sudo mysql -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
    sudo mysql -e "DROP DATABASE IF EXISTS test;"
    sudo mysql -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
    sudo mysql -e "FLUSH PRIVILEGES;"
    
    # Save root password
    echo "MariaDB root password: ${ROOT_PASSWORD}" > ~/.mariadb_root_password
    chmod 600 ~/.mariadb_root_password
    
    print_message $GREEN "MariaDB secured. Root password saved in ~/.mariadb_root_password"
}

# Function to create database and user
create_database() {
    print_message $YELLOW "Creating database and user..."
    
    # Generate user password
    DB_PASSWORD=$(generate_password)
    
    # Get root password
    if [[ -f ~/.mariadb_root_password ]]; then
        ROOT_PASSWORD=$(grep "password:" ~/.mariadb_root_password | cut -d' ' -f3)
    else
        print_message $YELLOW "Enter MariaDB root password:"
        read -s ROOT_PASSWORD
    fi
    
    # Create database and user
    mysql -u root -p"${ROOT_PASSWORD}" <<EOF
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET ${DB_CHARSET} COLLATE ${DB_COLLATION};
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF
    
    print_message $GREEN "Database and user created successfully!"
    
    # Return password for .env update
    echo "${DB_PASSWORD}"
}

# Function to update .env file
update_env_file() {
    local db_password=$1
    local env_file="../.env"
    
    print_message $YELLOW "Updating .env file..."
    
    # Check if .env exists
    if [[ ! -f $env_file ]]; then
        print_message $YELLOW ".env file not found. Creating from .env.example..."
        if [[ -f "../.env.example" ]]; then
            cp "../.env.example" "$env_file"
        else
            print_message $RED ".env.example not found. Please create .env manually."
            return 1
        fi
    fi
    
    # Update database configuration
    sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=mysql/" "$env_file"
    sed -i "s/DB_HOST=.*/DB_HOST=127.0.0.1/" "$env_file"
    sed -i "s/DB_PORT=.*/DB_PORT=3306/" "$env_file"
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=${DB_NAME}/" "$env_file"
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=${DB_USER}/" "$env_file"
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=${db_password}/" "$env_file"
    
    print_message $GREEN ".env file updated successfully!"
}

# Function to run migrations
run_migrations() {
    print_message $YELLOW "Running database migrations..."
    
    cd ..
    
    # Check if composer is installed
    if ! command_exists composer; then
        print_message $RED "Composer is not installed. Please install Composer first."
        return 1
    fi
    
    # Install dependencies if needed
    if [[ ! -d vendor ]]; then
        print_message $YELLOW "Installing Composer dependencies..."
        composer install --no-interaction --prefer-dist --optimize-autoloader
    fi
    
    # Generate application key if needed
    if ! grep -q "APP_KEY=base64:" .env; then
        php artisan key:generate
    fi
    
    # Run migrations
    php artisan migrate --force
    
    print_message $GREEN "Migrations completed successfully!"
}

# Main script
main() {
    print_message $GREEN "=== Nestogy MariaDB Setup Script ==="
    
    # Check if running from scripts directory
    if [[ ! -f "setup-mariadb.sh" ]]; then
        print_message $RED "Please run this script from the scripts directory."
        exit 1
    fi
    
    # Check and install MariaDB if needed
    if ! check_mariadb; then
        install_mariadb
        secure_mariadb
    else
        print_message $GREEN "MariaDB is already installed."
    fi
    
    # Create database and user
    DB_PASSWORD=$(create_database)
    
    if [[ -z $DB_PASSWORD ]]; then
        print_message $RED "Failed to create database. Exiting."
        exit 1
    fi
    
    # Update .env file
    update_env_file "$DB_PASSWORD"
    
    # Run migrations
    read -p "Do you want to run database migrations now? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        run_migrations
    fi
    
    print_message $GREEN "=== Setup Complete ==="
    print_message $GREEN "Database Name: ${DB_NAME}"
    print_message $GREEN "Database User: ${DB_USER}"
    print_message $GREEN "Database Password: Saved in .env file"
    print_message $YELLOW "Note: MariaDB root password is saved in ~/.mariadb_root_password"
}

# Run main function
main