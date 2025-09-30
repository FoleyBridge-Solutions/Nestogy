<?php

namespace App\Domains\Core\Services;

/**
 * Alias for backward compatibility during refactoring
 * The actual EmailService is in App\Domains\Email\Services
 */
class EmailService extends \App\Domains\Email\Services\EmailService
{
    // This is just an alias to maintain backward compatibility
    // after moving EmailService from Core to Email domain
}
