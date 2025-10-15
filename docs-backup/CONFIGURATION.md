# Nestogy MSP Platform Configuration Guide

This document provides a comprehensive guide to the Nestogy MSP Platform configuration system.

## Overview

The Nestogy MSP Platform uses Laravel's configuration system with custom configuration files organized by functionality. All configuration values can be set through environment variables, making deployment and environment-specific settings easy to manage.

## Configuration Files

### 1. Main Configuration (`config/nestogy.php`)

The main configuration file contains core MSP platform settings:

- **Company Settings**: Default currency, timezone, country, language, and date/time formats
- **Module Configuration**: Enable/disable specific modules (tickets, invoices, assets, projects, etc.)
- **System Limits**: File upload limits, session timeouts, API rate limits, export limits
- **Module-Specific Settings**: Detailed configuration for tickets, invoices, assets, and projects
- **System Features**: Toggle various system features (multi-company, API access, 2FA, etc.)
- **Maintenance Mode**: Control maintenance mode and allowed IPs

### 2. Integration Configuration (`config/integrations.php`)

External service integrations:

- **Payment Gateways**: Stripe configuration
- **Financial Services**: Plaid integration
- **Email Services**: IMAP configuration for email-to-ticket
- **Communication**: Twilio (SMS/Voice), Slack, Microsoft Teams
- **Cloud Services**: Google Workspace, Microsoft 365, AWS
- **Other Integrations**: Zapier, QuickBooks, Mailchimp
- **Webhook Settings**: General webhook configuration
- **API Rate Limits**: Per-service rate limiting

### 3. Upload Configuration (`config/uploads.php`)

File upload and storage settings:

- **Upload Limits**: Maximum file sizes and counts
- **Allowed File Types**: Categorized by type (images, documents, archives, etc.)
- **Storage Paths**: Organized paths for different upload types
- **Image Processing**: Thumbnail generation, optimization settings
- **Virus Scanning**: Optional virus scanning configuration
- **CDN Configuration**: Content delivery network settings

### 4. Security Configuration (`config/security.php`)

Comprehensive security settings:

- **Password Policy**: Length, complexity requirements, expiration
- **Session Security**: Timeouts, cookie settings, single session enforcement
- **Two-Factor Authentication**: Methods, enforcement, backup codes
- **Rate Limiting**: Login attempts, API requests, file uploads
- **IP Security**: Whitelisting, blacklisting, geo-blocking
- **Encryption**: Algorithm settings, key rotation
- **Security Headers**: HTTP security headers configuration
- **CORS**: Cross-origin resource sharing settings
- **Audit Logging**: Comprehensive audit trail configuration

### 5. Notification Configuration (`config/notifications.php`)

Notification system settings:

- **Channels**: Email, SMS, Slack, push notifications, webhooks
- **Templates**: Email template mappings
- **Preferences**: Per-module notification settings
- **Scheduling**: Digest emails, quiet hours, reminders
- **Batching**: Prevent notification spam
- **Localization**: Multi-language support
- **Emergency Notifications**: Critical alert settings

## Environment Variables

All configuration values can be overridden using environment variables. The `.env.example` file contains all available variables with descriptions.

### Key Environment Variables

```env
# Application
APP_NAME="Nestogy MSP Platform"
APP_ENV=production
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=nestogy_erp

# Nestogy Core
NESTOGY_DEFAULT_CURRENCY=USD
NESTOGY_DEFAULT_TIMEZONE=America/New_York
NESTOGY_MODULE_TICKETS=true
NESTOGY_MODULE_INVOICES=true

# Security
PASSWORD_MIN_LENGTH=8
2FA_ENABLED=true
RATE_LIMIT_LOGIN=5

# Integrations
STRIPE_ENABLED=false
STRIPE_PUBLIC_KEY=
STRIPE_SECRET_KEY=

# Uploads
UPLOAD_MAX_SIZE=10240
VIRUS_SCANNING_ENABLED=false

# Notifications
NOTIFICATIONS_EMAIL=true
NOTIFICATION_QUIET_HOURS=true
```

## Configuration Helper

Use the `ConfigHelper` class for easy access to configuration values:

```php
use App\Helpers\ConfigHelper;

// Get configuration values
$currency = ConfigHelper::getDefaultCurrency();
$maxFileSize = ConfigHelper::getMaxFileSize();
$isTicketsEnabled = ConfigHelper::isModuleEnabled('tickets');

// Check features
if (ConfigHelper::isFeatureEnabled('two_factor_auth')) {
    // Enable 2FA
}

// Get upload paths
$ticketPath = ConfigHelper::getUploadPath('tickets');

// Format invoice numbers
$invoiceNumber = ConfigHelper::formatInvoiceNumber(1001);
```

## Configuration Validation

The system includes automatic configuration validation:

### Command Line Validation

```bash
# Validate all configuration
php artisan config:validate

# Show warnings
php artisan config:validate --show-warnings

# Check missing configurations only
php artisan config:validate --check-missing

# Output as JSON
php artisan config:validate --json
```

### Automatic Validation

Configuration is automatically validated on application boot in non-production environments. Check the logs for any configuration errors.

### Programmatic Validation

```php
use App\Services\ConfigurationValidationService;

$validator = app(ConfigurationValidationService::class);
$isValid = $validator->validate();

if (!$isValid) {
    $errors = $validator->getErrors();
    $warnings = $validator->getWarnings();
}
```

## Best Practices

1. **Environment Variables**: Always use environment variables for sensitive data
2. **Default Values**: Provide sensible defaults in configuration files
3. **Type Safety**: Use the ConfigHelper methods that return proper types
4. **Validation**: Run configuration validation before deployment
5. **Documentation**: Document any custom configuration values
6. **Security**: Never commit `.env` files to version control

## Module-Specific Configuration

### Tickets Module

```php
// config/nestogy.php
'tickets' => [
    'auto_assign' => env('NESTOGY_TICKETS_AUTO_ASSIGN', false),
    'default_priority' => env('NESTOGY_TICKETS_DEFAULT_PRIORITY', 'medium'),
    'default_status' => env('NESTOGY_TICKETS_DEFAULT_STATUS', 'open'),
    'auto_close_days' => env('NESTOGY_TICKETS_AUTO_CLOSE_DAYS', 30),
]
```

### Invoices Module

```php
// config/nestogy.php
'invoices' => [
    'number_format' => env('NESTOGY_INVOICE_NUMBER_FORMAT', 'INV-{YEAR}-{NUMBER}'),
    'starting_number' => env('NESTOGY_INVOICE_STARTING_NUMBER', 1000),
    'default_payment_terms' => env('NESTOGY_INVOICE_PAYMENT_TERMS', 30),
]
```

### Assets Module

```php
// config/nestogy.php
'assets' => [
    'depreciation_enabled' => env('NESTOGY_ASSETS_DEPRECIATION', true),
    'qr_code_enabled' => env('NESTOGY_ASSETS_QR_CODE', true),
    'auto_generate_asset_tag' => env('NESTOGY_ASSETS_AUTO_TAG', true),
]
```

## Troubleshooting

### Common Issues

1. **Missing Configuration Error**
   - Run `php artisan config:validate --check-missing`
   - Check `.env` file for missing variables
   - Ensure `.env` is properly loaded

2. **Invalid Configuration Values**
   - Check data types match expected values
   - Verify boolean values are `true`/`false`
   - Check numeric values are within valid ranges

3. **Configuration Not Loading**
   - Clear configuration cache: `php artisan config:clear`
   - Rebuild cache: `php artisan config:cache`
   - Check file permissions on config directory

### Debug Commands

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Show current configuration
php artisan tinker
>>> config('nestogy')
>>> config('integrations.stripe')
```

## Security Considerations

1. **Sensitive Data**: Never hardcode API keys, passwords, or secrets
2. **Environment Files**: Add `.env` to `.gitignore`
3. **Production Settings**: Ensure debug mode is disabled in production
4. **HTTPS**: Force HTTPS in production environments
5. **Headers**: Configure security headers appropriately

## Performance Optimization

1. **Configuration Caching**: Use `php artisan config:cache` in production
2. **Minimize Config Calls**: Cache configuration values in services
3. **Lazy Loading**: Only load configuration when needed
4. **Environment Detection**: Minimize environment checks in hot paths

## Extending Configuration

To add new configuration:

1. Create or update configuration file in `config/`
2. Add corresponding environment variables to `.env.example`
3. Update `ConfigurationValidationService` if validation needed
4. Add helper methods to `ConfigHelper` if frequently accessed
5. Document new configuration in this guide

## Migration from Legacy System

When migrating from the old system:

1. Identify hardcoded values in legacy code
2. Map to new configuration structure
3. Update code to use `config()` helper or `ConfigHelper`
4. Test thoroughly with different configurations
5. Update deployment scripts for new environment variables

---

**Version**: 1.0.0 | **Last Updated**: January 2024 | **Platform**: Laravel 11 + PHP 8.2+