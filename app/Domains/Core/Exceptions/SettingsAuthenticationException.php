<?php

namespace App\Domains\Core\Exceptions;

use Exception;

class SettingsAuthenticationException extends Exception
{
    protected $message = 'User must be authenticated to access settings.';
}
