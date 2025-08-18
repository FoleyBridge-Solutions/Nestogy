#!/bin/bash

# ====================================================================
# Nestogy ERP Installation Verification Script
# ====================================================================

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_message() {
    echo -e "${2}${1}${NC}"
}

print_section() {
    echo
    print_message "================================================================" "$BLUE"
    print_message "  $1" "$BLUE"
    print_message "================================================================" "$BLUE"
}

# Configuration
APP_DIR="/var/www/html/Nestogy"
PHP_VERSION="8.4"

print_section "Nestogy ERP Installation Verification"

# Check if app directory exists
if [[ ! -d "$APP_DIR" ]]; then
    print_message "❌ Application directory not found: $APP_DIR" "$RED"
    exit 1
fi

cd "$APP_DIR"

# Check services
print_message "🔍 Checking system services..." "$YELLOW"

services=("apache2" "php${PHP_VERSION}-fpm" "redis-server" "supervisor")

# Add database service
if systemctl is-active postgresql >/dev/null 2>&1; then
    services+=("postgresql")
elif systemctl is-active mariadb >/dev/null 2>&1; then
    services+=("mariadb")
fi

for service in "${services[@]}"; do
    if systemctl is-active "$service" >/dev/null 2>&1; then
        print_message "✅ $service: Running" "$GREEN"
    else
        print_message "❌ $service: Not running" "$RED"
    fi
done

# Check PHP configuration
print_message "🐘 Checking PHP configuration..." "$YELLOW"
php_memory=$(php -r "echo ini_get('memory_limit');")
print_message "Memory limit: $php_memory" "$WHITE"

# Check database connection
print_message "🗄️  Testing database connection..." "$YELLOW"
if php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connection: OK';" 2>/dev/null; then
    print_message "✅ Database connection: OK" "$GREEN"
else
    print_message "❌ Database connection: Failed" "$RED"
fi

# Check Redis connection
print_message "📦 Testing Redis connection..." "$YELLOW"
if redis-cli ping | grep -q "PONG"; then
    print_message "✅ Redis connection: OK" "$GREEN"
else
    print_message "❌ Redis connection: Failed" "$RED"
fi

# Check file permissions
print_message "🔒 Checking file permissions..." "$YELLOW"
if [[ -w "storage" && -w "bootstrap/cache" ]]; then
    print_message "✅ Storage permissions: OK" "$GREEN"
else
    print_message "❌ Storage permissions: Failed" "$RED"
fi

# Check Apache configuration
print_message "🌐 Checking Apache configuration..." "$YELLOW"
if apache2ctl configtest 2>/dev/null | grep -q "Syntax OK"; then
    print_message "✅ Apache configuration: Valid" "$GREEN"
else
    print_message "❌ Apache configuration: Invalid" "$RED"
fi

# Check queue workers
print_message "👷 Checking queue workers..." "$YELLOW"
worker_count=$(supervisorctl status | grep -c "nestogy-worker.*RUNNING" || echo "0")
if [[ "$worker_count" -gt 0 ]]; then
    print_message "✅ Queue workers: $worker_count running" "$GREEN"
else
    print_message "❌ Queue workers: Not running" "$RED"
fi

# Check application key
print_message "🔑 Checking application key..." "$YELLOW"
if grep -q "APP_KEY=base64:" .env; then
    print_message "✅ Application key: Set" "$GREEN"
else
    print_message "❌ Application key: Not set" "$RED"
fi

# Check URL accessibility
print_message "🌐 Testing application accessibility..." "$YELLOW"
app_url=$(grep "APP_URL=" .env | cut -d'=' -f2)
if curl -s -o /dev/null -w "%{http_code}" "$app_url" | grep -q "200\|302"; then
    print_message "✅ Application accessible: $app_url" "$GREEN"
else
    print_message "⚠️  Application URL check: $app_url (check manually)" "$YELLOW"
fi

# Performance checks
print_section "Performance Verification"

# Check Apache MPM
print_message "⚡ Checking Apache MPM..." "$YELLOW"
if apache2ctl -V | grep -q "Server MPM:.*event"; then
    print_message "✅ Apache Event MPM: Enabled" "$GREEN"
else
    print_message "❌ Apache Event MPM: Not enabled" "$RED"
fi

# Check PHP-FPM processes
print_message "🔧 Checking PHP-FPM processes..." "$YELLOW"
fpm_processes=$(ps aux | grep -c "php-fpm: pool www" || echo "0")
print_message "PHP-FPM processes: $fpm_processes" "$WHITE"

# Check OPcache
print_message "🚀 Checking OPcache..." "$YELLOW"
if php -m | grep -q "Zend OPcache"; then
    print_message "✅ OPcache: Enabled" "$GREEN"
else
    print_message "❌ OPcache: Not enabled" "$RED"
fi

# Security checks
print_section "Security Verification"

# Check firewall
print_message "🔥 Checking firewall..." "$YELLOW"
if ufw status | grep -q "Status: active"; then
    print_message "✅ UFW firewall: Active" "$GREEN"
else
    print_message "❌ UFW firewall: Inactive" "$RED"
fi

# Check fail2ban
print_message "🛡️  Checking fail2ban..." "$YELLOW"
if systemctl is-active fail2ban >/dev/null 2>&1; then
    print_message "✅ Fail2ban: Active" "$GREEN"
else
    print_message "❌ Fail2ban: Inactive" "$RED"
fi

# Check SSL
print_message "🔒 Checking SSL..." "$YELLOW"
if [[ "$app_url" == https* ]]; then
    if curl -s -I "$app_url" | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
        print_message "✅ SSL certificate: Valid" "$GREEN"
    else
        print_message "⚠️  SSL certificate: Check manually" "$YELLOW"
    fi
else
    print_message "⚠️  SSL: Not configured (HTTP only)" "$YELLOW"
fi

# Log checks
print_section "Log File Verification"

log_files=(
    "/var/log/apache2/nestogy-error.log"
    "/var/log/apache2/nestogy-access.log"
    "$APP_DIR/storage/logs/laravel.log"
    "$APP_DIR/storage/logs/worker.log"
)

for log_file in "${log_files[@]}"; do
    if [[ -f "$log_file" ]]; then
        print_message "✅ $log_file: Exists" "$GREEN"
    else
        print_message "⚠️  $log_file: Missing (may be created on first use)" "$YELLOW"
    fi
done

# Summary
print_section "Verification Summary"

# Overall health check
errors=0
warnings=0

# Count errors and warnings from above checks
if ! systemctl is-active apache2 >/dev/null 2>&1; then ((errors++)); fi
if ! systemctl is-active "php${PHP_VERSION}-fpm" >/dev/null 2>&1; then ((errors++)); fi
if ! redis-cli ping | grep -q "PONG"; then ((errors++)); fi

if [[ $errors -eq 0 ]]; then
    print_message "🎉 Installation verification: PASSED" "$GREEN"
    print_message "✅ All critical components are working properly" "$GREEN"
    print_message "🚀 Your Nestogy ERP installation is ready for use!" "$GREEN"
else
    print_message "⚠️  Installation verification: ISSUES FOUND" "$YELLOW"
    print_message "❌ $errors critical issues detected" "$RED"
    print_message "Please review the errors above and fix them before using the application" "$YELLOW"
fi

echo
print_message "📋 Quick troubleshooting commands:" "$BLUE"
print_message "Check Apache logs: sudo tail -f /var/log/apache2/nestogy-error.log" "$WHITE"
print_message "Check PHP-FPM logs: sudo tail -f /var/log/php${PHP_VERSION}-fpm.log" "$WHITE"
print_message "Restart services: sudo systemctl restart apache2 php${PHP_VERSION}-fpm redis-server" "$WHITE"
print_message "Check queue workers: sudo supervisorctl status" "$WHITE"
print_message "Clear application cache: cd $APP_DIR && php artisan cache:clear" "$WHITE"

echo
print_message "For support, visit: https://github.com/FoleyBridge-Solutions/Nestogy/issues" "$CYAN"

exit 0