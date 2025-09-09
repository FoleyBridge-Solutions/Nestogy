#!/bin/bash

# Nestogy ERP - File Permissions Setup Script
# This script sets the correct file permissions for the Laravel application

set -e  # Exit on error

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

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

# Function to check web server user
check_web_user() {
    # Detect web server user
    if id "www-data" &>/dev/null; then
        WEB_USER="www-data"
        WEB_GROUP="www-data"
    elif id "apache" &>/dev/null; then
        WEB_USER="apache"
        WEB_GROUP="apache"
    elif id "nginx" &>/dev/null; then
        WEB_USER="nginx"
        WEB_GROUP="nginx"
    else
        print_message "Cannot detect web server user!" "$RED"
        read -p "Enter web server user (default: www-data): " WEB_USER
        WEB_USER=${WEB_USER:-www-data}
        WEB_GROUP=$WEB_USER
    fi
    
    print_message "Web server user: $WEB_USER:$WEB_GROUP" "$BLUE"
}

# Function to set ownership
set_ownership() {
    print_message "\n=== Setting File Ownership ===" "$BLUE"
    
    cd $APP_DIR
    
    # Set ownership for all files and directories
    print_message "Setting ownership to $WEB_USER:$WEB_GROUP..." "$YELLOW"
    chown -R $WEB_USER:$WEB_GROUP .
    
    print_message "✓ Ownership set successfully" "$GREEN"
}

# Function to set directory permissions
set_directory_permissions() {
    print_message "\n=== Setting Directory Permissions ===" "$BLUE"
    
    cd $APP_DIR
    
    # Set default directory permissions (755)
    print_message "Setting directory permissions to 755..." "$YELLOW"
    find . -type d -exec chmod 755 {} \;
    
    # Set writable directory permissions (775)
    print_message "Setting writable directory permissions to 775..." "$YELLOW"
    chmod -R 775 storage
    chmod -R 775 bootstrap/cache
    
    # Create required directories if they don't exist
    directories=(
        "storage/app/public"
        "storage/app/private"
        "storage/app/clients"
        "storage/app/tickets"
        "storage/app/assets"
        "storage/app/invoices"
        "storage/app/expenses"
        "storage/app/sops"
        "storage/app/users"
        "storage/app/backups"
        "storage/app/temp"
        "storage/framework/cache"
        "storage/framework/sessions"
        "storage/framework/testing"
        "storage/framework/views"
        "storage/logs"
    )
    
    for dir in "${directories[@]}"; do
        if [[ ! -d "$dir" ]]; then
            print_message "Creating directory: $dir" "$YELLOW"
            mkdir -p "$dir"
            chown $WEB_USER:$WEB_GROUP "$dir"
            chmod 775 "$dir"
        fi
    done
    
    print_message "✓ Directory permissions set successfully" "$GREEN"
}

# Function to set file permissions
set_file_permissions() {
    print_message "\n=== Setting File Permissions ===" "$BLUE"
    
    cd $APP_DIR
    
    # Set default file permissions (644)
    print_message "Setting file permissions to 644..." "$YELLOW"
    find . -type f -exec chmod 644 {} \;
    
    # Set executable permissions for artisan
    if [[ -f "artisan" ]]; then
        chmod +x artisan
        print_message "✓ Made artisan executable" "$GREEN"
    fi
    
    # Set executable permissions for scripts
    if [[ -d "scripts" ]]; then
        chmod +x scripts/*.sh 2>/dev/null || true
        print_message "✓ Made scripts executable" "$GREEN"
    fi
    
    print_message "✓ File permissions set successfully" "$GREEN"
}

# Function to secure sensitive files
secure_sensitive_files() {
    print_message "\n=== Securing Sensitive Files ===" "$BLUE"
    
    cd $APP_DIR
    
    # Secure .env file
    if [[ -f ".env" ]]; then
        chmod 600 .env
        chown $WEB_USER:$WEB_GROUP .env
        print_message "✓ Secured .env file (600)" "$GREEN"
    fi
    
    # Secure configuration files
    if [[ -d "config" ]]; then
        chmod -R 640 config/*.php
        print_message "✓ Secured config files (640)" "$GREEN"
    fi
    
    # Remove public access to sensitive files
    sensitive_files=(
        ".env.example"
        "composer.json"
        "composer.lock"
        "package.json"
        "package-lock.json"
        "webpack.mix.js"
        "phpunit.xml"
        ".gitignore"
        ".gitattributes"
    )
    
    for file in "${sensitive_files[@]}"; do
        if [[ -f "$file" ]]; then
            chmod 640 "$file"
        fi
    done
    
    print_message "✓ Sensitive files secured" "$GREEN"
}

# Function to set SELinux context (if enabled)
set_selinux_context() {
    if command -v getenforce &> /dev/null && [[ $(getenforce) != "Disabled" ]]; then
        print_message "\n=== Setting SELinux Context ===" "$BLUE"
        
        cd $APP_DIR
        
        # Set httpd context
        chcon -R -t httpd_sys_content_t .
        
        # Set writable context for storage and cache
        chcon -R -t httpd_sys_rw_content_t storage
        chcon -R -t httpd_sys_rw_content_t bootstrap/cache
        
        print_message "✓ SELinux context set successfully" "$GREEN"
    fi
}

# Function to verify permissions
verify_permissions() {
    print_message "\n=== Verifying Permissions ===" "$BLUE"
    
    cd $APP_DIR
    
    ERRORS=0
    
    # Check storage directory
    if [[ -w "storage" ]]; then
        print_message "✓ Storage directory is writable" "$GREEN"
    else
        print_message "✗ Storage directory is not writable" "$RED"
        ERRORS=$((ERRORS + 1))
    fi
    
    # Check bootstrap/cache directory
    if [[ -w "bootstrap/cache" ]]; then
        print_message "✓ Bootstrap cache directory is writable" "$GREEN"
    else
        print_message "✗ Bootstrap cache directory is not writable" "$RED"
        ERRORS=$((ERRORS + 1))
    fi
    
    # Check .env file
    if [[ -f ".env" ]]; then
        if [[ -r ".env" ]]; then
            print_message "✓ .env file is readable" "$GREEN"
        else
            print_message "✗ .env file is not readable" "$RED"
            ERRORS=$((ERRORS + 1))
        fi
    else
        print_message "⚠ .env file not found" "$YELLOW"
    fi
    
    # Check public directory
    if [[ -d "public" && -r "public" ]]; then
        print_message "✓ Public directory is accessible" "$GREEN"
    else
        print_message "✗ Public directory is not accessible" "$RED"
        ERRORS=$((ERRORS + 1))
    fi
    
    if [[ $ERRORS -eq 0 ]]; then
        print_message "\n✓ All permissions verified successfully!" "$GREEN"
    else
        print_message "\n✗ Found $ERRORS permission errors!" "$RED"
        print_message "Please review and fix the issues above." "$YELLOW"
    fi
}

# Function to create .htaccess files for security
create_htaccess_security() {
    print_message "\n=== Creating Security .htaccess Files ===" "$BLUE"
    
    cd $APP_DIR
    
    # Protect storage directory
    if [[ -d "storage" && ! -f "storage/.htaccess" ]]; then
        echo "Deny from all" > storage/.htaccess
        chown $WEB_USER:$WEB_GROUP storage/.htaccess
        chmod 644 storage/.htaccess
        print_message "✓ Created storage/.htaccess" "$GREEN"
    fi
    
    # Protect vendor directory
    if [[ -d "vendor" && ! -f "vendor/.htaccess" ]]; then
        echo "Deny from all" > vendor/.htaccess
        chown $WEB_USER:$WEB_GROUP vendor/.htaccess
        chmod 644 vendor/.htaccess
        print_message "✓ Created vendor/.htaccess" "$GREEN"
    fi
    
    # Protect node_modules directory
    if [[ -d "node_modules" && ! -f "node_modules/.htaccess" ]]; then
        echo "Deny from all" > node_modules/.htaccess
        chown $WEB_USER:$WEB_GROUP node_modules/.htaccess
        chmod 644 node_modules/.htaccess
        print_message "✓ Created node_modules/.htaccess" "$GREEN"
    fi
}

# Function to display summary
display_summary() {
    print_message "\n=== Permission Setup Complete ===" "$GREEN"
    print_message "\nSummary:" "$BLUE"
    print_message "• Application directory: $APP_DIR" "$YELLOW"
    print_message "• Web server user: $WEB_USER:$WEB_GROUP" "$YELLOW"
    print_message "• Standard directories: 755" "$YELLOW"
    print_message "• Standard files: 644" "$YELLOW"
    print_message "• Writable directories: 775 (storage, bootstrap/cache)" "$YELLOW"
    print_message "• Sensitive files: 600 (.env)" "$YELLOW"
    
    print_message "\nRecommendations:" "$BLUE"
    print_message "1. Run this script after any deployment or update" "$YELLOW"
    print_message "2. Regularly check file permissions for security" "$YELLOW"
    print_message "3. Monitor storage directory for unusual files" "$YELLOW"
    print_message "4. Keep sensitive files out of public directory" "$YELLOW"
}

# Function to fix common permission issues
fix_common_issues() {
    print_message "\n=== Fixing Common Permission Issues ===" "$BLUE"
    
    cd $APP_DIR
    
    # Clear various caches that might have wrong permissions
    if [[ -f "artisan" ]]; then
        print_message "Clearing application caches..." "$YELLOW"
        sudo -u $WEB_USER php artisan cache:clear 2>/dev/null || true
        sudo -u $WEB_USER php artisan config:clear 2>/dev/null || true
        sudo -u $WEB_USER php artisan route:clear 2>/dev/null || true
        sudo -u $WEB_USER php artisan view:clear 2>/dev/null || true
    fi
    
    # Remove and recreate storage link
    if [[ -L "public/storage" ]]; then
        rm public/storage
        sudo -u $WEB_USER php artisan storage:link 2>/dev/null || true
        print_message "✓ Recreated storage link" "$GREEN"
    fi
    
    # Fix log file permissions
    if [[ -d "storage/logs" ]]; then
        touch storage/logs/laravel.log
        chown $WEB_USER:$WEB_GROUP storage/logs/laravel.log
        chmod 664 storage/logs/laravel.log
        print_message "✓ Fixed log file permissions" "$GREEN"
    fi
}

# Main function
main() {
    print_message "======================================" "$BLUE"
    print_message "  Nestogy ERP - Permission Setup Tool  " "$BLUE"
    print_message "======================================" "$BLUE"
    
    check_root
    find_app_dir
    check_web_user
    
    print_message "\nThis script will set proper file permissions for Nestogy ERP." "$YELLOW"
    print_message "Current settings:" "$YELLOW"
    print_message "• Directory: $APP_DIR" "$YELLOW"
    print_message "• Web user: $WEB_USER:$WEB_GROUP" "$YELLOW"
    
    read -p "Do you want to continue? (y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_message "Operation cancelled." "$RED"
        exit 1
    fi
    
    set_ownership
    set_directory_permissions
    set_file_permissions
    secure_sensitive_files
    set_selinux_context
    create_htaccess_security
    fix_common_issues
    verify_permissions
    display_summary
}

# Run main function
main

exit 0