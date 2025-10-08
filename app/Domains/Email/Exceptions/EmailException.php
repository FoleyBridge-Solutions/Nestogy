<?php

namespace App\Domains\Email\Exceptions;

use App\Exceptions\BaseException;
use App\Exceptions\PermissionException as BasePermissionException;

class EmailException extends BaseException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An error occurred while processing your email request.';
    }
}

class EmailAccountPermissionException extends BasePermissionException
{
    public function __construct(string $action, array $context = [])
    {
        parent::__construct($action, 'Email Account', $context);
    }
}
