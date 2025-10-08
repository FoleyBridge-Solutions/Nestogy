<?php

namespace App\Domains\Core\Exceptions;

use App\Exceptions\BaseException;
use App\Exceptions\BusinessException;
use App\Exceptions\NotFoundException as BaseNotFoundException;
use App\Exceptions\PermissionException as BasePermissionException;
use App\Exceptions\ServiceException;
use App\Exceptions\ValidationException as BaseValidationException;

class SettingsException extends BaseException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An error occurred while processing your settings request.';
    }
}

class SettingsValidationException extends BaseValidationException
{
    protected function getDefaultUserMessage(): string
    {
        return 'The settings data provided is invalid.';
    }
}

class SettingsNotFoundException extends BaseNotFoundException
{
    public function __construct(mixed $settingId = null, array $context = [])
    {
        parent::__construct('Settings', $settingId, $context);
    }
}

class SettingsPermissionException extends BasePermissionException
{
    public function __construct(string $action, array $context = [])
    {
        parent::__construct($action, 'Settings', $context);
    }
}

class SettingsServiceException extends ServiceException
{
    protected function getDefaultUserMessage(): string
    {
        return 'A settings service error occurred. Please try again later.';
    }
}

class SettingsBusinessException extends BusinessException
{
    protected function getDefaultUserMessage(): string
    {
        return 'A settings business rule was violated.';
    }
}

class SettingsAuthenticationException extends SettingsException
{
    public function __construct(array $context = [])
    {
        parent::__construct(
            'No authenticated user found',
            401,
            null,
            $context,
            'You must be authenticated to access settings.',
            401
        );
    }
}
