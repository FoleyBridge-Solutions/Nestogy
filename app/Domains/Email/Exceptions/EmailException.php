<?php

namespace App\Domains\Email\Exceptions;

use App\Exceptions\BaseException;
use App\Exceptions\PermissionException;

class EmailException extends BaseException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An email error occurred.';
    }
}

class EmailAccountNotFoundException extends EmailException
{
    public function __construct(string $message = 'No account selected for deletion', array $context = [])
    {
        parent::__construct(
            $message,
            0,
            null,
            $context,
            $message,
            404
        );
    }

    protected function getDefaultUserMessage(): string
    {
        return 'The email account could not be found.';
    }
}

class EmailAccountUnauthorizedException extends PermissionException
{
    public function __construct(string $action = 'access', ?string $resource = 'email account', array $context = [])
    {
        parent::__construct($action, $resource, $context);
    }

    protected function getDefaultUserMessage(): string
    {
        return 'Unauthorized';
    }
}
