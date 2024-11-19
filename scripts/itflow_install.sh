#!/bin/bash

# Version
VERSION="1.0.0"

# Log file
LOG_FILE="/var/log/itflow_install.log"

# Add logging function
log() {
    local level="$1"
    local message="$2"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[${timestamp}] [${level}] ${message}" | tee -a "$LOG_FILE"
}

# Test mode function
run_tests() {
    # Disable terminal UI in test mode
    TERM_UI=false
    TEST_MODE=true
    
    echo "=== ITFlow-NG Installation Test ==="
    echo "Version: ${VERSION}"
    echo "Domain: ${domain}"
    echo "Running tests..."
    echo
    
    local failed=0
    local tests=(
        check_version
        verify_script
        check_root
        check_os
        get_domain
        generate_passwords
        install_packages
        modify_php_ini
        setup_webroot
        setup_apache
        clone_nestogy
        setup_cronjobs
        generate_cronkey_file
        setup_mysql
    )
    
    for test in "${tests[@]}"; do
        echo -e "\nRunning test: ${test}"
        if ! $test; then
            echo "✗ Test failed: ${test}"
            failed=$((failed + 1))
        else
            echo "✓ Test passed: ${test}"
        fi
    done
    
    echo
    if [ $failed -eq 0 ]; then
        echo "✓ All tests completed successfully"
        return 0
    else
        echo "✗ ${failed} test(s) failed"
        return 1
    fi
}

# Colors and formatting
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Global variables
TERM_WIDTH=$(tput cols)
TERM_HEIGHT=$(tput lines)
CONTENT_START=6
CONTENT_WIDTH=$((TERM_WIDTH - 8))
TOTAL_STEPS=12
CURRENT_STEP=0

# Update show_progress to simple output
show_progress() {
    local step="$1"
    local message="$2"
    log "INFO" "Step ${step}/${TOTAL_STEPS}: ${message}"
}

# Installation Functions
check_version() {
    if [ "$TEST_MODE" = true ]; then
        echo "Testing version check..."
        
        # Test if we can access the version file
        if ! curl -s -f "https://raw.githubusercontent.com/o-psi/nestogy_install/refs/heads/main/version.txt" >/dev/null; then
            echo "Cannot access version file"
            return 1
        fi
        
        echo "✓ Version check passed"
        return 0
    fi
    
    # Real version check code
    show_progress "$((++CURRENT_STEP))" "Checking version"
    
    LATEST_VERSION=$(curl -sSL https://raw.githubusercontent.com/o-psi/nestogy_install/refs/heads/main/version.txt)
    if [[ "$VERSION" != "$LATEST_VERSION" ]]; then
        echo -e "${RED}A newer version ($LATEST_VERSION) is available"
        echo -e "Please update to the latest version${NC}"
        return 1
    fi
    
    echo -e "${GREEN}✓${NC} Version check passed"
    return 0
}

verify_script() {
    URL="https://raw.githubusercontent.com/o-psi/nestogy_install/refs/heads/main/i.sh.sha256"
    
    if [ "$TEST_MODE" = true ]; then
        log "INFO" "Testing script verification..."
        
        if ! command -v sha256sum >/dev/null 2>&1; then
            log "ERROR" "Required tool 'sha256sum' not found"
            return 1
        fi
        log "INFO" "sha256sum available"
        
        if ! curl -s -f "$URL" >/dev/null; then
            log "ERROR" "Cannot access hash file"
            return 1
        fi
        log "INFO" "Hash file accessible"
        
        log "INFO" "Script verification test complete"
        return 0
    fi
    
    show_progress "$((++CURRENT_STEP))" "Verifying script"
    
    remote_hash=$(curl -sSL "$URL")
    if [ -z "$remote_hash" ]; then
        log "ERROR" "Failed to download verification hash"
        exit 1
    fi
    
    local_hash=$(sha256sum "$0" | cut -d' ' -f1)
    
    if [ "$remote_hash" != "$local_hash" ]; then
        log "ERROR" "Script verification failed"
        log "ERROR" "Expected hash: $remote_hash"
        log "ERROR" "Got hash:      $local_hash"
        exit 1
    fi
    
    log "INFO" "Script verified successfully"
    return 0
}

check_root() {
    show_progress "$((++CURRENT_STEP))" "Checking permissions"
    
    if [[ $EUID -ne 0 ]]; then
        echo -e "${RED}Root privileges required"
        echo -e "Please run with sudo or as root${NC}"
        read -n 1 -p "Press any key to exit..."
        exit 1
    fi
    echo -e "${GREEN}✓${NC} Root privileges confirmed"
}

check_os() {
    if [ "$TEST_MODE" = true ]; then
        echo "Testing OS compatibility..."
        
        # For GitHub Actions, we'll check for Ubuntu in general
        if ! grep -E "Ubuntu" "/etc/"*"release" &>/dev/null; then
            echo "System is not Ubuntu"
            return 1
        fi
        
        echo "✓ OS compatibility check passed"
        return 0
    fi
    
    # Real OS check code
    show_progress "$((++CURRENT_STEP))" "Checking system compatibility"
    
    if ! grep -E "24.04" "/etc/"*"release" &>/dev/null; then
        echo -e "${RED}Unsupported OS detected"
        echo -e "Ubuntu 24.04 is required${NC}"
        return 1
    fi
    
    echo -e "${GREEN}✓${NC} System compatible"
    return 0
}

get_domain() {
    show_progress "$((++CURRENT_STEP))" "Configuring domain"
    
    while [[ $domain != *[.]* ]]; do
        echo -e "${YELLOW}Please enter your domain (e.g., domain.com):${NC}"
        echo -ne "→ "
        read domain
    done
    echo -e "${GREEN}✓${NC} Domain set to: ${BLUE}${domain}${NC}"
}

generate_passwords() {
    if [ "$TEST_MODE" = true ]; then
        echo "Testing password generation..."
        
        # Check for required tools
        if ! command -v tr >/dev/null 2>&1; then
            echo "Required tool 'tr' not found"
            return 1
        fi
        
        if ! command -v head >/dev/null 2>&1; then
            echo "Required tool 'head' not found"
            return 1
        fi
        
        # Test /dev/urandom access
        if [ ! -r "/dev/urandom" ]; then
            echo "Cannot access /dev/urandom"
            return 1
        fi
        
        echo "✓ Password generation prerequisites verified"
        return 0
    fi
    
    show_progress "$((++CURRENT_STEP))" "Generating secure passwords"
    
    mariadbpwd=$(tr -dc 'A-Za-z0-9' < /dev/urandom | fold -w 20 | head -n 1)
    cronkey=$(tr -dc 'A-Za-z0-9' < /dev/urandom | fold -w 20 | head -n 1)
    echo -e "${GREEN}✓${NC} Passwords generated"
}

# Add error handling function
handle_error() {
    local message="$1"
    log "ERROR" "$message"
    log "ERROR" "Installation failed. Check /var/log/itflow_install.log for details"
    exit 1
}

# Update install_packages()
install_packages() {
    show_progress "$((++CURRENT_STEP))" "Installing required packages"
    
    # Define required packages
    local packages=(
        apache2
        libapache2-mod-php
        php
        php-cli
        php-common
        php-intl
        php-mysqli
        php-curl
        php-imap
        php-mailparse
        php-xml
        php-mbstring
        php-zip
        mariadb-server
        mariadb-client
        certbot
        python3-certbot-apache
        git
        curl
        unzip
        cron
        php-json      # Added for Composer
        php-tokenizer # Added for Composer
    )
    
    log "INFO" "Updating package lists..."
    apt-get update || handle_error "Failed to update package lists"
    
    log "INFO" "Installing packages: ${packages[*]}"
    apt-get install -y "${packages[@]}" || handle_error "Failed to install required packages"
    
    # Verify critical commands exist
    local required_commands=(php apache2 mysql certbot git)
    for cmd in "${required_commands[@]}"; do
        if ! command -v "$cmd" >/dev/null 2>&1; then
            handle_error "Required command '$cmd' not found after installation"
        fi
    done
    
    log "INFO" "All packages installed successfully"
    return 0
}

# Update modify_php_ini()
modify_php_ini() {
    show_progress "$((++CURRENT_STEP))" "Configuring PHP"
    
    # Get PHP version
    PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;') || handle_error "Failed to determine PHP version"
    PHP_INI_PATH="/etc/php/${PHP_VERSION}/apache2/php.ini"
    
    if [ ! -f "$PHP_INI_PATH" ]; then
        handle_error "PHP configuration file not found at $PHP_INI_PATH"
    fi
    
    log "INFO" "Modifying PHP settings in $PHP_INI_PATH"
    
    # Backup original file
    cp "$PHP_INI_PATH" "${PHP_INI_PATH}.bak" || handle_error "Failed to backup PHP configuration"
    
    # Update settings
    sed -i 's/^upload_max_filesize.*/upload_max_filesize = 5000M/' "$PHP_INI_PATH" || handle_error "Failed to modify upload_max_filesize"
    sed -i 's/^post_max_size.*/post_max_size = 5000M/' "$PHP_INI_PATH" || handle_error "Failed to modify post_max_size"
    
    log "INFO" "PHP configuration updated successfully"
    return 0
}

setup_webroot() {
    show_progress "$((++CURRENT_STEP))" "Setting up webroot"
    
    if [ "$TEST_MODE" = true ]; then
        echo -e "${BLUE}[TEST] Would create: /var/www/${domain}${NC}"
        return 0
    fi
    
    if ! mkdir -p "/var/www/${domain}"; then
        echo -e "${RED}Failed to create web directory${NC}"
        return 1
    fi
    
    if ! chown -R www-data:www-data "/var/www/${domain}"; then
        echo -e "${RED}Failed to set permissions${NC}"
        return 1
    fi
    
    echo -e "${GREEN}✓${NC} Webroot configured"
}

# Update setup_apache()
setup_apache() {
    show_progress "$((++CURRENT_STEP))" "Configuring Apache"
    
    # Verify Apache installation
    if ! command -v apache2 >/dev/null 2>&1; then
        handle_error "Apache2 not installed"
    fi
    
    # Enable required modules
    local modules=(rewrite ssl headers)
    for mod in "${modules[@]}"; do
        log "INFO" "Enabling Apache module: $mod"
        a2enmod "$mod" || handle_error "Failed to enable Apache module: $mod"
    done
    
    # Create Apache configuration
    local config_file="/etc/apache2/sites-available/${domain}.conf"
    cat > "$config_file" << EOF || handle_error "Failed to create Apache config"
<VirtualHost *:80>
    ServerName ${domain}
    ServerAlias www.${domain}
    DocumentRoot /var/www/${domain}/public
    
    <Directory /var/www/${domain}/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/${domain}_error.log
    CustomLog \${APACHE_LOG_DIR}/${domain}_access.log combined
</VirtualHost>
EOF
    
    # Create public directory if it doesn't exist
    mkdir -p "/var/www/${domain}/public" || handle_error "Failed to create public directory"
    
    # Move index.php to public directory
    if [ -f "/var/www/${domain}/index.php" ]; then
        mv "/var/www/${domain}/index.php" "/var/www/${domain}/public/" || handle_error "Failed to move index.php"
    fi
    
    # Enable the new site and disable the default
    a2ensite "${domain}.conf" || handle_error "Failed to enable site"
    a2dissite 000-default.conf || handle_error "Failed to disable default site"
    
    # Test Apache configuration
    apache2ctl configtest || handle_error "Apache configuration test failed"
    
    # Restart Apache
    systemctl restart apache2 || handle_error "Failed to restart Apache"
    
    log "INFO" "Apache configured successfully"
    return 0
}

# Update setup_mysql()
setup_mysql() {
    show_progress "$((++CURRENT_STEP))" "Setting up database"
    
    # Check if MySQL/MariaDB is installed
    if ! command -v mysql >/dev/null 2>&1; then
        handle_error "MySQL/MariaDB not installed"
    fi
    
    # Check if service is running
    if ! systemctl is-active --quiet mysql; then
        log "INFO" "Starting MySQL service..."
        systemctl start mysql || handle_error "Failed to start MySQL service"
    fi
    
    # Rest of setup_mysql() function...
}

# Update clone_nestogy()
clone_nestogy() {
    show_progress "$((++CURRENT_STEP))" "Setting up ITFlow-NG"
    
    local repo_url="https://github.com/twetech/itflow-ng.git"
    local target_dir="/var/www/${domain}"
    
    # Verify git is installed
    if ! command -v git >/dev/null 2>&1; then
        handle_error "Git not installed"
    fi
    
    # Verify composer is installed, if not install it
    if ! command -v composer >/dev/null 2>&1; then
        log "INFO" "Installing Composer..."
        curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer || handle_error "Failed to install Composer"
    fi
    
    # Remove target directory if it exists
    if [ -d "$target_dir" ]; then
        rm -rf "$target_dir" || handle_error "Failed to remove existing directory"
    fi
    
    # Clone repository
    git clone "$repo_url" "$target_dir" || handle_error "Failed to clone repository"
    
    # Create config directory and file
    mkdir -p "$target_dir/config/${domain}" || handle_error "Failed to create config directory"
    
    # Create config file
    cat > "$target_dir/config/${domain}/config.php" << EOF || handle_error "Failed to create config file"
<?php
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'nestogy',
        'username' => 'nestogy',
        'password' => '${mariadbpwd}'
    ],
    'locale' => 'en_US',
    'timezone' => 'America/New_York',
    'cron_key' => '${cronkey}'
];
EOF
    
    # Install Composer dependencies
    log "INFO" "Installing Composer dependencies..."
    cd "$target_dir" || handle_error "Failed to change to project directory"
    composer install --no-dev --optimize-autoloader || handle_error "Failed to install Composer dependencies"
    
    # Set proper permissions
    chown -R www-data:www-data "$target_dir" || handle_error "Failed to set directory permissions"
    chmod 750 "$target_dir/config" || handle_error "Failed to set config directory permissions"
    chmod 640 "$target_dir/config/${domain}/config.php" || handle_error "Failed to set config file permissions"
    
    log "INFO" "Repository cloned and dependencies installed successfully"
    return 0
}

setup_cronjobs() {
    show_progress "$((++CURRENT_STEP))" "Setting up automated tasks"
    
    if [ "$TEST_MODE" = true ]; then
        echo -e "${BLUE}[TEST] Would configure cron jobs${NC}"
        return 0
    fi
    
    local steps=2
    local current=0
    
    # Create cron job
    ((current++))
    local cron_line="*/5 * * * * curl -s https://${domain}/cron.php?key=${cronkey} >/dev/null 2>&1"
    if ! (crontab -l 2>/dev/null | grep -Fq "$cron_line" || echo "$cron_line" | crontab -); then
        echo -e "${RED}Failed to create cron job${NC}"
        return 1
    fi
    
    # Verify cron job
    ((current++))
    if ! crontab -l | grep -Fq "$cron_line"; then
        echo -e "${RED}Failed to verify cron job${NC}"
        return 1
    fi
    
    echo -e "${GREEN}✓${NC} Cron jobs configured"
}

generate_cronkey_file() {
    show_progress "$((++CURRENT_STEP))" "Generating cron key"
    
    if [ "$TEST_MODE" = true ]; then
        echo -e "${BLUE}[TEST] Would generate cron key file${NC}"
        return 0
    fi
    
    local steps=2
    local current=0
    
    # Create key file
    ((current++))
    if ! echo "<?php define('CRON_KEY', '${cronkey}');" > "/var/www/${domain}/includes/cronkey.php"; then
        echo -e "${RED}Failed to create cron key file${NC}"
        return 1
    fi
    
    # Set permissions
    ((current++))
    if ! chmod 640 "/var/www/${domain}/includes/cronkey.php"; then
        echo -e "${RED}Failed to set cron key file permissions${NC}"
        return 1
    fi
    
    echo -e "${GREEN}✓${NC} Cron key generated"
}

setup_ssl() {
    show_progress "$((++CURRENT_STEP))" "Setting up SSL"
    
    # Check if certbot is installed
    if ! command -v certbot >/dev/null 2>&1; then
        handle_error "Certbot not installed"
    }
    
    log "INFO" "Obtaining SSL certificate..."
    
    # Get SSL certificate
    certbot --apache \
        --non-interactive \
        --agree-tos \
        --email "admin@${domain}" \
        --domains "${domain},www.${domain}" \
        --redirect || handle_error "Failed to obtain SSL certificate"
    
    log "INFO" "SSL certificate obtained successfully"
    return 0
}

print_final_instructions() {
    echo -e "\n📋 Next Steps:"
    echo -e "\n1. Set up SSL Certificate:"
    echo -e "   Run this command to get your DNS challenge:"
    echo -e "   ${YELLOW}sudo certbot certonly --manual --preferred-challenges dns --agree-tos --domains *.${domain}${NC}"
    
    echo -e "\n2. Complete Setup:"
    echo -e "   Visit: ${GREEN}https://${domain}${NC}"
    
    echo -e "\n⚠️  Important: Save these credentials in a secure location!"
    echo -e "\nFor support, visit: https://github.com/twetech/itflow-ng/issues"
    
    read -n 1 -p "Press any key to exit..."
}

# Parse command line arguments
parse_args() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            --test)
                TEST_MODE=true
                shift
                ;;
            --test-function)
                TEST_MODE=true
                TEST_FUNCTION="$2"
                shift 2
                ;;
            --domain)
                domain="$2"
                shift 2
                ;;
            *)
                echo "Unknown parameter: $1"
                exit 1
                ;;
        esac
    done
}

# Add test function handler
run_test_function() {
    local function_name="$1"
    
    # Check if function exists
    if [ "$(type -t $function_name)" != "function" ]; then
        echo "Error: Function '$function_name' not found"
        exit 1
    fi
    
    # Run the function in test mode
    if $function_name; then
        return 0
    else
        return 1
    fi
}

# Main execution
main() {
    # Create log file
    touch "$LOG_FILE" 2>/dev/null || {
        echo "ERROR: Cannot create log file. Please run as root."
        exit 1
    }
    
    log "INFO" "Starting ITFlow-NG installation"
    log "INFO" "Version: ${VERSION}"
    
    parse_args "$@"
    
    if [ -n "$TEST_FUNCTION" ]; then
        log "INFO" "Running test function: ${TEST_FUNCTION}"
        run_test_function "$TEST_FUNCTION"
        exit $?
    elif [ "$TEST_MODE" = true ]; then
        log "INFO" "Running in test mode"
        run_tests
        exit $?
    fi
    
    # Run installation steps
    check_version
    verify_script
    check_root
    check_os
    get_domain
    generate_passwords
    install_packages
    modify_php_ini
    setup_webroot
    setup_apache
    setup_ssl
    setup_mysql
    clone_nestogy
    setup_cronjobs
    generate_cronkey_file
    
    log "INFO" "Installation completed successfully"
    
    # Show final instructions
    cat << EOF

Installation Complete! 🎉

Next Steps:
1. Set up SSL Certificate:
   Run this command to get your DNS challenge:
   sudo certbot certonly --manual --preferred-challenges dns --agree-tos --domains *.${domain}

2. Complete Setup:
   Visit: https://${domain}

Credentials:
Database User:     nestogy
Database Name:     nestogy
Database Password: ${mariadbpwd}

⚠️  Important: Save these credentials in a secure location!

For support, visit: https://github.com/twetech/itflow-ng/issues
Installation log available at: ${LOG_FILE}
EOF
}

# Remove all trap handlers for terminal restoration
# Just keep basic error handling
trap 'log "ERROR" "Installation failed with error on line $LINENO"' ERR

# Start installation
main "$@"