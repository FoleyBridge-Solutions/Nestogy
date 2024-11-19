#!/bin/bash

# Version
VERSION="1.0.0"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Progress indicator
show_progress() {
    echo -e "\n${BLUE}[$1/9]${NC} ${GREEN}$2...${NC}"
}

# Version check with styled output
check_version() {
    echo -e "\n${BLUE}[•]${NC} Checking for latest version..."
    LATEST_VERSION=$(curl -sSL https://raw.githubusercontent.com/twetech/itflow-install-script/refs/heads/main/version.txt)
    if [[ "$LATEST_VERSION" == "404: Not Found" ]]; then
        echo -e "${GREEN}✓${NC} Version check skipped - using local version: $VERSION"
        return 0
    elif [ "$VERSION" != "$LATEST_VERSION" ]; then
        echo -e "${RED}╔════════════════════════════════════════╗${NC}"
        echo -e "${RED}║ A newer version ($LATEST_VERSION) is available! ║${NC}"
        echo -e "${RED}║ Please run the latest installer.        ║${NC}"
        echo -e "${RED}╚════════════════════════════════════════╝${NC}"
        exit 1
    fi
    echo -e "${GREEN}✓${NC} Running latest version"
} 

# Script verification with styled output
verify_script() {
    echo -e "\n${BLUE}[•]${NC} Verifying script integrity..."
    SCRIPT_HASH=$(curl -sSL https://raw.githubusercontent.com/twetech/itflow-install-script/refs/heads/main/itflow_install.sh.sha256)
    if ! echo "$SCRIPT_HASH *-" | sha256sum -c - >/dev/null 2>&1; then
        echo -e "${RED}╔════════════════════════════════════════╗${NC}"
        echo -e "${RED}║        Verification Failed!            ║${NC}"
        echo -e "${RED}║ Script may have been tampered with.    ║${NC}"
        echo -e "${RED}║ SHA256: $SCRIPT_HASH                   ║${NC}"
        echo -e "${RED}╚════════════════════════════════════════╝${NC}"
        exit 1
    fi
    echo -e "${GREEN}✓${NC} Script verified"
}

# Root check with styled output
check_root() {
    echo -e "\n${BLUE}[•]${NC} Checking permissions..."
    if [[ $EUID -ne 0 ]]; then
        echo -e "${RED}╔════════════════════════════════════════╗${NC}"
        echo -e "${RED}║    Error: Root privileges required     ║${NC}"
        echo -e "${RED}║    Please run with sudo or as root     ║${NC}"
        echo -e "${RED}╚════════════════════════════════════════╝${NC}"
        exit 1
    fi
    echo -e "${GREEN}✓${NC} Root privileges confirmed"
}

# OS check with styled output
check_os() {
    echo -e "\n${BLUE}[•]${NC} Checking system compatibility..."
    if ! grep -E "24.04" "/etc/"*"release" &>/dev/null; then
        echo -e "${RED}╔════════════════════════════════════════╗${NC}"
        echo -e "${RED}║    Error: Unsupported OS detected      ║${NC}"
        echo -e "${RED}║    Ubuntu 24.04 is required            ║${NC}"
        echo -e "${RED}╚════════════════════════════════════════╝${NC}"
        exit 1
    fi
    echo -e "${GREEN}✓${NC} System compatible"
}

# Get domain with styled input
get_domain() {
    echo -e "\n${BLUE}[•]${NC} Domain Configuration"
    while [[ $domain != *[.]* ]]; do
        echo -e "${YELLOW}Please enter your domain (e.g., domain.com):${NC}"
        echo -ne "→ "
        read domain
    done
    echo -e "${GREEN}✓${NC} Domain set to: ${BLUE}${domain}${NC}"
}

# Add at the start of the script
LOG_FILE="/tmp/itflow-install.log"

# Modified install_packages function
install_packages() {
    show_progress "1" "Installing system packages"
    
    # Update package lists
    echo -e "${BLUE}[•]${NC} Updating package lists..."
    if ! apt-get update > "$LOG_FILE" 2>&1; then
        echo -e "${RED}Failed to update package lists${NC}"
        echo -e "${YELLOW}Check $LOG_FILE for details${NC}"
        exit 1
    fi
    echo -e "${GREEN}✓${NC} Package lists updated"

    # Upgrade existing packages
    echo -e "${BLUE}[•]${NC} Upgrading existing packages..."
    if ! DEBIAN_FRONTEND=noninteractive apt-get -y upgrade >> "$LOG_FILE" 2>&1; then
        echo -e "${RED}Failed to upgrade packages${NC}"
        echo -e "${YELLOW}Check $LOG_FILE for details${NC}"
        exit 1
    fi
    echo -e "${GREEN}✓${NC} Packages upgraded"

    # Install required packages one by one with progress
    echo -e "${BLUE}[•]${NC} Installing required packages..."
    
    packages=(
        "apache2"
        "mariadb-server"
        "php"
        "libapache2-mod-php"
        "php-intl"
        "php-mysqli"
        "php-curl"
        "php-imap"
        "php-mailparse"
        "libapache2-mod-md"
        "certbot"
        "python3-certbot-apache"
        "git"
        "sudo"
    )

    for package in "${packages[@]}"; do
        echo -ne "  ${YELLOW}Installing ${package}...${NC}\r"
        if ! DEBIAN_FRONTEND=noninteractive apt-get install -y "$package" >> "$LOG_FILE" 2>&1; then
            echo -e "\n${RED}Failed to install ${package}${NC}"
            echo -e "${YELLOW}Check $LOG_FILE for details${NC}"
            exit 1
        fi
        echo -e "  ${GREEN}✓${NC} Installed ${package}     "
    done

    echo -e "${GREEN}✓${NC} All packages installed successfully"
}

generate_passwords() {
    mariadbpwd=$(tr -dc 'A-Za-z0-9' < /dev/urandom | fold -w 20 | head -n 1)
    cronkey=$(tr -dc 'A-Za-z0-9' < /dev/urandom | fold -w 20 | head -n 1)
}

modify_php_ini() {
    # Get the PHP version
    PHP_VERSION=$(php -v | head -n 1 | awk '{print $2}' | cut -d '.' -f 1,2)
    
    # Set the PHP_INI_PATH
    PHP_INI_PATH="/etc/php/${PHP_VERSION}/apache2/php.ini"

    sed -i 's/^;\?upload_max_filesize =.*/upload_max_filesize = 5000M/' $PHP_INI_PATH
    sed -i 's/^;\?post_max_size =.*/post_max_size = 5000M/' $PHP_INI_PATH
}

setup_webroot() {
    mkdir -p /var/www/${domain}
    chown -R www-data:www-data /var/www/
}

setup_apache() {
    apache2_conf="<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    ServerName ${domain}
    DocumentRoot /var/www/${domain}
    ErrorLog /\${APACHE_LOG_DIR}/error.log
    CustomLog /\${APACHE_LOG_DIR}/access.log combined
</VirtualHost>"

    echo "${apache2_conf}" > /etc/apache2/sites-available/${domain}.conf

    a2ensite ${domain}.conf
    a2dissite 000-default.conf
    systemctl restart apache2
}

clone_nestogy() {
    git clone https://github.com/twetech/itflow-ng.git /var/www/${domain}
}

setup_cronjobs() {
    (crontab -l 2>/dev/null; echo "0 2 * * * sudo -u www-data php /var/www/${domain}/cron.php ${cronkey}") | crontab -
    (crontab -l 2>/dev/null; echo "* * * * * sudo -u www-data php /var/www/${domain}/cron_ticket_email_parser.php ${cronkey}") | crontab -
    (crontab -l 2>/dev/null; echo "* * * * * sudo -u www-data php /var/www/${domain}/cron_mail_queue.php ${cronkey}") | crontab -
}

generate_cronkey_file() {
    mkdir -p /var/www/${domain}/uploads/tmp
    echo "<?php" > /var/www/${domain}/uploads/tmp/cronkey.php
    echo "\$nestogy_install_script_generated_cronkey = \"${cronkey}\";" >> /var/www/${domain}/uploads/tmp/cronkey.php
    echo "?>" >> /var/www/${domain}/uploads/tmp/cronkey.php
    chown -R www-data:www-data /var/www/
}

setup_mysql() {
    mysql -e "CREATE DATABASE nestogy /*\!40100 DEFAULT CHARACTER SET utf8 */;"
    mysql -e "CREATE USER nestogy@localhost IDENTIFIED BY '${mariadbpwd}';"
    mysql -e "GRANT ALL PRIVILEGES ON nestogy.* TO 'nestogy'@'localhost';"
    mysql -e "FLUSH PRIVILEGES;"
}

# Welcome message with styled output
show_welcome_message() {
    clear
    cat << "EOF"
╔═══════════════════════════════════════════════════════════════╗
║                                                               ║
║                   ITFlow-NG Installation                      ║
║                                                               ║
╚═══════════════════════════════════════════════════════════════╝

                     Version: ${VERSION}

This script will:
 • Install required system packages
 • Configure Apache and PHP
 • Set up MariaDB database
 • Configure SSL certificates
 • Set up automated tasks

EOF

    echo -e "\n${YELLOW}Requirements:${NC}"
    echo -e " ${BLUE}•${NC} Ubuntu 24.04"
    echo -e " ${BLUE}•${NC} Root privileges"
    echo -e " ${BLUE}•${NC} Domain name pointed to this server"
    
    echo -e "\n${YELLOW}Press ENTER to begin installation, or CTRL+C to exit...${NC}"
    read
    clear
}

# Final instructions with styled output
print_final_instructions() {
    clear
    cat << EOF
╔════════════════════════════════════════════════════════════════╗
║                 Installation Complete! 🎉                       ║
╚════════════════════════════════════════════════════════════════╝

📋 Next Steps:

1. Set up SSL Certificate:
   Run this command to get your DNS challenge:
   ${YELLOW}sudo certbot certonly --manual --preferred-challenges dns --agree-tos --domains *.${domain}${NC}

2. Complete Setup:
   Visit: ${GREEN}https://${domain}${NC}

3. Database Credentials:
   ┌─────────────────────────────────────────────┐
   │ Database User:     ${GREEN}nestogy${NC}
   │ Database Name:     ${GREEN}nestogy${NC}
   │ Database Password: ${GREEN}${mariadbpwd}${NC}
   └─────────────────────────────────────────────┘

⚠️  Important: Save these credentials in a secure location!

For support, visit: https://github.com/twetech/itflow-ng/issues

EOF
}

# Main execution flow
show_welcome_message
check_version
verify_script
check_root
check_os
get_domain
generate_passwords

# Execute installation steps with progress tracking
install_packages
modify_php_ini
setup_webroot
setup_apache
clone_nestogy
setup_cronjobs
generate_cronkey_file
setup_mysql

# Show final instructions
print_final_instructions