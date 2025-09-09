#!/bin/bash

# Nestogy ERP - Environment Configuration Generator
# This script helps generate and configure the .env file

set -e  # Exit on error

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Function to print colored output
print_message() {
    echo -e "${2}${1}${NC}"
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

# Function to generate random key
generate_random_key() {
    if command -v openssl &> /dev/null; then
        openssl rand -base64 32
    else
        < /dev/urandom tr -dc 'A-Za-z0-9!@#$%^&*()_+=' | head -c 32
    fi
}

# Function to detect server IP
detect_server_ip() {
    # Try to get public IP first
    PUBLIC_IP=$(curl -s https://api.ipify.org 2>/dev/null || echo "")
    
    # Get local IP
    LOCAL_IP=$(hostname -I | awk '{print $1}')
    
    if [[ -n "$PUBLIC_IP" ]]; then
        echo "$PUBLIC_IP"
    else
        echo "$LOCAL_IP"
    fi
}

# Function to check existing .env
check_existing_env() {
    cd $APP_DIR
    
    if [[ -f ".env" ]]; then
        print_message "\n⚠ Existing .env file found!" "$YELLOW"
        print_message "1. Backup and create new .env" "$YELLOW"
        print_message "2. Update existing .env" "$YELLOW"
        print_message "3. Exit without changes" "$YELLOW"
        
        read -p "Choose option [1]: " OPTION
        OPTION=${OPTION:-1}
        
        case $OPTION in
            1)
                BACKUP_FILE=".env.backup.$(date +%Y%m%d_%H%M%S)"
                cp .env "$BACKUP_FILE"
                print_message "✓ Backed up to $BACKUP_FILE" "$GREEN"
                rm .env
                ;;
            2)
                return 0
                ;;
            3)
                print_message "Exiting without changes." "$YELLOW"
                exit 0
                ;;
        esac
    fi
    
    # Create from example if doesn't exist
    if [[ ! -f ".env" && -f ".env.example" ]]; then
        cp .env.example .env
        print_message "✓ Created .env from .env.example" "$GREEN"
    elif [[ ! -f ".env" ]]; then
        print_message "Creating new .env file..." "$YELLOW"
        touch .env
    fi
}

# Function to configure app settings
configure_app_settings() {
    print_message "\n=== Application Settings ===" "$BLUE"
    
    # App name
    read -p "Application Name [Nestogy ERP]: " APP_NAME
    APP_NAME=${APP_NAME:-"Nestogy ERP"}
    
    # Environment
    print_message "\nEnvironment Options:" "$YELLOW"
    print_message "1. production (recommended for live servers)" "$YELLOW"
    print_message "2. staging" "$YELLOW"
    print_message "3. local (for development)" "$YELLOW"
    read -p "Choose environment [1]: " ENV_OPTION
    
    case ${ENV_OPTION:-1} in
        1) APP_ENV="production" ;;
        2) APP_ENV="staging" ;;
        3) APP_ENV="local" ;;
        *) APP_ENV="production" ;;
    esac
    
    # Debug mode
    if [[ "$APP_ENV" == "production" ]]; then
        APP_DEBUG="false"
        print_message "Debug mode: disabled (production)" "$YELLOW"
    else
        read -p "Enable debug mode? (y/n) [n]: " DEBUG_OPTION
        if [[ "$DEBUG_OPTION" =~ ^[Yy]$ ]]; then
            APP_DEBUG="true"
        else
            APP_DEBUG="false"
        fi
    fi
    
    # App URL
    SERVER_IP=$(detect_server_ip)
    print_message "\nDetected server IP: $SERVER_IP" "$YELLOW"
    read -p "Application URL [http://$SERVER_IP]: " APP_URL
    APP_URL=${APP_URL:-"http://$SERVER_IP"}
    
    # Generate app key
    APP_KEY="base64:$(generate_random_key)"
}

# Function to configure database
configure_database() {
    print_message "\n=== Database Configuration ===" "$BLUE"
    
    # Database type
    print_message "Database Type:" "$YELLOW"
    print_message "1. MySQL (recommended)" "$YELLOW"
    print_message "2. PostgreSQL" "$YELLOW"
    print_message "3. SQLite" "$YELLOW"
    read -p "Choose database [1]: " DB_OPTION
    
    case ${DB_OPTION:-1} in
        1) DB_CONNECTION="mysql" ;;
        2) DB_CONNECTION="pgsql" ;;
        3) DB_CONNECTION="sqlite" ;;
        *) DB_CONNECTION="mysql" ;;
    esac
    
    if [[ "$DB_CONNECTION" == "sqlite" ]]; then
        DB_DATABASE="database/database.sqlite"
        touch "$APP_DIR/database/database.sqlite"
    else
        # Database host
        read -p "Database Host [127.0.0.1]: " DB_HOST
        DB_HOST=${DB_HOST:-"127.0.0.1"}
        
        # Database port
        if [[ "$DB_CONNECTION" == "mysql" ]]; then
            DEFAULT_PORT="3306"
        else
            DEFAULT_PORT="5432"
        fi
        read -p "Database Port [$DEFAULT_PORT]: " DB_PORT
        DB_PORT=${DB_PORT:-$DEFAULT_PORT}
        
        # Database name
        read -p "Database Name [nestogy_erp]: " DB_DATABASE
        DB_DATABASE=${DB_DATABASE:-"nestogy_erp"}
        
        # Database user
        read -p "Database Username [nestogy_user]: " DB_USERNAME
        DB_USERNAME=${DB_USERNAME:-"nestogy_user"}
        
        # Database password
        print_message "\nDatabase Password Options:" "$YELLOW"
        print_message "1. Generate secure password" "$YELLOW"
        print_message "2. Enter custom password" "$YELLOW"
        read -p "Choose option [1]: " PASS_OPTION
        
        if [[ "${PASS_OPTION:-1}" == "2" ]]; then
            read -sp "Enter password: " DB_PASSWORD
            echo
        else
            DB_PASSWORD=$(generate_random_key | cut -c1-16)
            print_message "\nGenerated password: $DB_PASSWORD" "$GREEN"
            print_message "Please save this password!" "$YELLOW"
        fi
    fi
}

# Function to configure cache and session
configure_cache_session() {
    print_message "\n=== Cache & Session Configuration ===" "$BLUE"
    
    print_message "Cache Driver Options:" "$YELLOW"
    print_message "1. file (default, no setup required)" "$YELLOW"
    print_message "2. redis (recommended for production)" "$YELLOW"
    print_message "3. memcached" "$YELLOW"
    print_message "4. database" "$YELLOW"
    read -p "Choose cache driver [1]: " CACHE_OPTION
    
    case ${CACHE_OPTION:-1} in
        1) 
            CACHE_DRIVER="file"
            SESSION_DRIVER="file"
            QUEUE_CONNECTION="sync"
            ;;
        2) 
            CACHE_DRIVER="redis"
            SESSION_DRIVER="redis"
            QUEUE_CONNECTION="redis"
            
            # Redis configuration
            read -p "Redis Host [127.0.0.1]: " REDIS_HOST
            REDIS_HOST=${REDIS_HOST:-"127.0.0.1"}
            
            read -p "Redis Port [6379]: " REDIS_PORT
            REDIS_PORT=${REDIS_PORT:-"6379"}
            
            read -p "Redis Password (leave empty if none): " REDIS_PASSWORD
            ;;
        3) 
            CACHE_DRIVER="memcached"
            SESSION_DRIVER="memcached"
            QUEUE_CONNECTION="sync"
            
            read -p "Memcached Host [127.0.0.1]: " MEMCACHED_HOST
            MEMCACHED_HOST=${MEMCACHED_HOST:-"127.0.0.1"}
            ;;
        4) 
            CACHE_DRIVER="database"
            SESSION_DRIVER="database"
            QUEUE_CONNECTION="database"
            ;;
        *) 
            CACHE_DRIVER="file"
            SESSION_DRIVER="file"
            QUEUE_CONNECTION="sync"
            ;;
    esac
}

# Function to configure mail
configure_mail() {
    print_message "\n=== Email Configuration ===" "$BLUE"
    
    print_message "Email Driver Options:" "$YELLOW"
    print_message "1. smtp (recommended)" "$YELLOW"
    print_message "2. sendmail" "$YELLOW"
    print_message "3. mailgun" "$YELLOW"
    print_message "4. ses (Amazon SES)" "$YELLOW"
    print_message "5. log (for testing)" "$YELLOW"
    print_message "6. Skip email configuration" "$YELLOW"
    read -p "Choose email driver [1]: " MAIL_OPTION
    
    case ${MAIL_OPTION:-1} in
        1)
            MAIL_MAILER="smtp"
            
            # Common SMTP presets
            print_message "\nSMTP Presets:" "$YELLOW"
            print_message "1. Gmail" "$YELLOW"
            print_message "2. Outlook/Office365" "$YELLOW"
            print_message "3. Yahoo" "$YELLOW"
            print_message "4. Custom SMTP" "$YELLOW"
            read -p "Choose preset [4]: " SMTP_PRESET
            
            case ${SMTP_PRESET:-4} in
                1)
                    MAIL_HOST="smtp.gmail.com"
                    MAIL_PORT="587"
                    MAIL_ENCRYPTION="tls"
                    print_message "\nNote: Use App Password for Gmail, not regular password" "$YELLOW"
                    ;;
                2)
                    MAIL_HOST="smtp.office365.com"
                    MAIL_PORT="587"
                    MAIL_ENCRYPTION="tls"
                    ;;
                3)
                    MAIL_HOST="smtp.mail.yahoo.com"
                    MAIL_PORT="587"
                    MAIL_ENCRYPTION="tls"
                    ;;
                *)
                    read -p "SMTP Host: " MAIL_HOST
                    read -p "SMTP Port [587]: " MAIL_PORT
                    MAIL_PORT=${MAIL_PORT:-"587"}
                    
                    print_message "Encryption:" "$YELLOW"
                    print_message "1. tls (recommended)" "$YELLOW"
                    print_message "2. ssl" "$YELLOW"
                    print_message "3. none" "$YELLOW"
                    read -p "Choose encryption [1]: " ENC_OPTION
                    
                    case ${ENC_OPTION:-1} in
                        1) MAIL_ENCRYPTION="tls" ;;
                        2) MAIL_ENCRYPTION="ssl" ;;
                        3) MAIL_ENCRYPTION="null" ;;
                        *) MAIL_ENCRYPTION="tls" ;;
                    esac
                    ;;
            esac
            
            read -p "SMTP Username: " MAIL_USERNAME
            read -sp "SMTP Password: " MAIL_PASSWORD
            echo
            ;;
        2)
            MAIL_MAILER="sendmail"
            ;;
        3)
            MAIL_MAILER="mailgun"
            read -p "Mailgun Domain: " MAILGUN_DOMAIN
            read -p "Mailgun Secret: " MAILGUN_SECRET
            ;;
        4)
            MAIL_MAILER="ses"
            read -p "AWS Access Key ID: " AWS_ACCESS_KEY_ID
            read -sp "AWS Secret Access Key: " AWS_SECRET_ACCESS_KEY
            echo
            read -p "AWS Region [us-east-1]: " AWS_DEFAULT_REGION
            AWS_DEFAULT_REGION=${AWS_DEFAULT_REGION:-"us-east-1"}
            ;;
        5)
            MAIL_MAILER="log"
            ;;
        6)
            MAIL_MAILER="log"
            print_message "Skipping email configuration. Emails will be logged." "$YELLOW"
            ;;
    esac
    
    if [[ "$MAIL_MAILER" != "log" && "$MAIL_OPTION" != "6" ]]; then
        read -p "From Email Address: " MAIL_FROM_ADDRESS
        read -p "From Name [$APP_NAME]: " MAIL_FROM_NAME
        MAIL_FROM_NAME=${MAIL_FROM_NAME:-$APP_NAME}
    else
        MAIL_FROM_ADDRESS="noreply@example.com"
        MAIL_FROM_NAME=$APP_NAME
    fi
}

# Function to configure additional services
configure_additional_services() {
    print_message "\n=== Additional Services ===" "$BLUE"
    
    # Pusher/Broadcasting
    read -p "Configure real-time broadcasting (Pusher)? (y/n) [n]: " BROADCAST_OPTION
    if [[ "$BROADCAST_OPTION" =~ ^[Yy]$ ]]; then
        BROADCAST_DRIVER="pusher"
        read -p "Pusher App ID: " PUSHER_APP_ID
        read -p "Pusher App Key: " PUSHER_APP_KEY
        read -sp "Pusher App Secret: " PUSHER_APP_SECRET
        echo
        read -p "Pusher App Cluster [mt1]: " PUSHER_APP_CLUSTER
        PUSHER_APP_CLUSTER=${PUSHER_APP_CLUSTER:-"mt1"}
    else
        BROADCAST_DRIVER="log"
    fi
    
    # AWS S3 for file storage
    read -p "Configure AWS S3 for file storage? (y/n) [n]: " S3_OPTION
    if [[ "$S3_OPTION" =~ ^[Yy]$ ]]; then
        FILESYSTEM_DISK="s3"
        if [[ -z "$AWS_ACCESS_KEY_ID" ]]; then
            read -p "AWS Access Key ID: " AWS_ACCESS_KEY_ID
            read -sp "AWS Secret Access Key: " AWS_SECRET_ACCESS_KEY
            echo
            read -p "AWS Region [us-east-1]: " AWS_DEFAULT_REGION
            AWS_DEFAULT_REGION=${AWS_DEFAULT_REGION:-"us-east-1"}
        fi
        read -p "S3 Bucket Name: " AWS_BUCKET
    else
        FILESYSTEM_DISK="local"
    fi
}

# Function to write .env file
write_env_file() {
    print_message "\n=== Writing Configuration ===" "$BLUE"
    
    cd $APP_DIR
    
    # Create .env content
    cat > .env << EOF
# Application Settings
APP_NAME="$APP_NAME"
APP_ENV=$APP_ENV
APP_KEY=$APP_KEY
APP_DEBUG=$APP_DEBUG
APP_URL=$APP_URL

# Logging
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# Database Configuration
DB_CONNECTION=$DB_CONNECTION
EOF

    if [[ "$DB_CONNECTION" != "sqlite" ]]; then
        cat >> .env << EOF
DB_HOST=$DB_HOST
DB_PORT=$DB_PORT
DB_DATABASE=$DB_DATABASE
DB_USERNAME=$DB_USERNAME
DB_PASSWORD=$DB_PASSWORD
EOF
    else
        cat >> .env << EOF
DB_DATABASE=$DB_DATABASE
EOF
    fi

    cat >> .env << EOF

# Cache & Session
BROADCAST_DRIVER=${BROADCAST_DRIVER:-log}
CACHE_DRIVER=$CACHE_DRIVER
FILESYSTEM_DISK=${FILESYSTEM_DISK:-local}
QUEUE_CONNECTION=$QUEUE_CONNECTION
SESSION_DRIVER=$SESSION_DRIVER
SESSION_LIFETIME=120
EOF

    if [[ "$CACHE_DRIVER" == "memcached" ]]; then
        cat >> .env << EOF

MEMCACHED_HOST=$MEMCACHED_HOST
EOF
    fi

    if [[ "$CACHE_DRIVER" == "redis" || "$SESSION_DRIVER" == "redis" ]]; then
        cat >> .env << EOF

# Redis Configuration
REDIS_HOST=${REDIS_HOST:-127.0.0.1}
REDIS_PASSWORD=${REDIS_PASSWORD:-null}
REDIS_PORT=${REDIS_PORT:-6379}
EOF
    fi

    cat >> .env << EOF

# Mail Configuration
MAIL_MAILER=$MAIL_MAILER
EOF

    if [[ "$MAIL_MAILER" == "smtp" ]]; then
        cat >> .env << EOF
MAIL_HOST=$MAIL_HOST
MAIL_PORT=$MAIL_PORT
MAIL_USERNAME=$MAIL_USERNAME
MAIL_PASSWORD=$MAIL_PASSWORD
MAIL_ENCRYPTION=$MAIL_ENCRYPTION
EOF
    fi

    cat >> .env << EOF
MAIL_FROM_ADDRESS="$MAIL_FROM_ADDRESS"
MAIL_FROM_NAME="$MAIL_FROM_NAME"
EOF

    # AWS Configuration
    if [[ -n "$AWS_ACCESS_KEY_ID" ]]; then
        cat >> .env << EOF

# AWS Configuration
AWS_ACCESS_KEY_ID=$AWS_ACCESS_KEY_ID
AWS_SECRET_ACCESS_KEY=$AWS_SECRET_ACCESS_KEY
AWS_DEFAULT_REGION=$AWS_DEFAULT_REGION
AWS_BUCKET=${AWS_BUCKET:-}
AWS_USE_PATH_STYLE_ENDPOINT=false
EOF
    fi

    # Pusher Configuration
    if [[ "$BROADCAST_DRIVER" == "pusher" ]]; then
        cat >> .env << EOF

# Pusher Configuration
PUSHER_APP_ID=$PUSHER_APP_ID
PUSHER_APP_KEY=$PUSHER_APP_KEY
PUSHER_APP_SECRET=$PUSHER_APP_SECRET
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=$PUSHER_APP_CLUSTER
EOF
    fi

    # Additional default configurations
    cat >> .env << EOF

# Additional Settings
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY:-}"
VITE_PUSHER_HOST="${PUSHER_HOST:-}"
VITE_PUSHER_PORT="${PUSHER_PORT:-443}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME:-https}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER:-mt1}"

# Security
BCRYPT_ROUNDS=10

# Nestogy Specific Settings
TICKET_PREFIX="TKT"
INVOICE_PREFIX="INV"
PROJECT_PREFIX="PRJ"
ASSET_PREFIX="AST"

# File Upload Settings
MAX_FILE_UPLOAD_SIZE=10485760
ALLOWED_FILE_TYPES="pdf,doc,docx,xls,xlsx,png,jpg,jpeg,gif,zip,rar"

# System Settings
TIMEZONE="UTC"
LOCALE="en"
CURRENCY="USD"
CURRENCY_SYMBOL="$"
EOF

    # Set proper permissions
    chmod 600 .env
    
    print_message "✓ Configuration written to .env" "$GREEN"
}

# Function to validate configuration
validate_configuration() {
    print_message "\n=== Validating Configuration ===" "$BLUE"
    
    cd $APP_DIR
    
    # Test database connection
    if [[ "$DB_CONNECTION" != "sqlite" ]]; then
        print_message "Testing database connection..." "$YELLOW"
        
        php artisan tinker --execute="
        try {
            DB::connection()->getPdo();
            echo 'Database connection successful';
        } catch (\Exception \$e) {
            echo 'Database connection failed: ' . \$e->getMessage();
        }
        " 2>/dev/null || print_message "⚠ Could not test database connection" "$YELLOW"
    fi
    
    # Generate application key if needed
    if [[ ! "$APP_KEY" =~ ^base64: ]]; then
        php artisan key:generate
        print_message "✓ Generated application key" "$GREEN"
    fi
    
    # Clear configuration cache
    php artisan config:clear
    print_message "✓ Configuration cache cleared" "$GREEN"
}

# Function to display summary
display_summary() {
    print_message "\n=== Configuration Summary ===" "$GREEN"
    
    print_message "\nApplication:" "$BLUE"
    print_message "• Name: $APP_NAME" "$CYAN"
    print_message "• Environment: $APP_ENV" "$CYAN"
    print_message "• URL: $APP_URL" "$CYAN"
    print_message "• Debug: $APP_DEBUG" "$CYAN"
    
    print_message "\nDatabase:" "$BLUE"
    print_message "• Type: $DB_CONNECTION" "$CYAN"
    if [[ "$DB_CONNECTION" != "sqlite" ]]; then
        print_message "• Host: $DB_HOST:$DB_PORT" "$CYAN"
        print_message "• Database: $DB_DATABASE" "$CYAN"
        print_message "• Username: $DB_USERNAME" "$CYAN"
    fi
    
    print_message "\nCache/Session:" "$BLUE"
    print_message "• Cache: $CACHE_DRIVER" "$CYAN"
    print_message "• Session: $SESSION_DRIVER" "$CYAN"
    print_message "• Queue: $QUEUE_CONNECTION" "$CYAN"
    
    print_message "\nEmail:" "$BLUE"
    print_message "• Driver: $MAIL_MAILER" "$CYAN"
    if [[ "$MAIL_MAILER" == "smtp" ]]; then
        print_message "• Host: $MAIL_HOST:$MAIL_PORT" "$CYAN"
    fi
    print_message "• From: $MAIL_FROM_ADDRESS" "$CYAN"
    
    print_message "\nNext Steps:" "$BLUE"
    print_message "1. Run database migrations: php artisan migrate" "$YELLOW"
    print_message "2. Set file permissions: sudo ./scripts/setup-permissions.sh" "$YELLOW"
    print_message "3. Configure web server" "$YELLOW"
    print_message "4. Set up SSL certificate" "$YELLOW"
    
    if [[ -n "$DB_PASSWORD" && "$DB_CONNECTION" != "sqlite" ]]; then
        print_message "\n⚠ Important: Save your database password securely!" "$RED"
        print_message "Database Password: $DB_PASSWORD" "$YELLOW"
    fi
}

# Main function
main() {
    print_message "==========================================" "$BLUE"
    print_message "  Nestogy ERP - Environment Generator     " "$BLUE"
    print_message "==========================================" "$BLUE"
    
    find_app_dir
    check_existing_env
    
    print_message "\nThis wizard will help you configure your .env file." "$YELLOW"
    print_message "Press Enter to use default values shown in brackets." "$YELLOW"
    
    configure_app_settings
    configure_database
    configure_cache_session
    configure_mail
    configure_additional_services
    
    write_env_file
    validate_configuration
    display_summary
}

# Run main function
main

exit 0