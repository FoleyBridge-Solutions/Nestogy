<?php

namespace App\Domains\Email\Exceptions;

use App\Exceptions\BaseException;
use App\Exceptions\BusinessException;
use App\Exceptions\NotFoundException as BaseNotFoundException;
use App\Exceptions\PermissionException as BasePermissionException;
use App\Exceptions\ServiceException;
use App\Exceptions\ValidationException as BaseValidationException;

class EmailException extends BaseException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An error occurred while processing your email request.';
    }
}

class EmailValidationException extends BaseValidationException
{
    protected function getDefaultUserMessage(): string
    {
        return 'The email data provided is invalid.';
    }
}

class EmailNotFoundException extends BaseNotFoundException
{
    public function __construct(mixed $emailId = null, array $context = [])
    {
        parent::__construct('Email', $emailId, $context);
    }
}

class EmailAccountPermissionException extends BasePermissionException
{
    public function __construct(string $action, array $context = [])
    {
        parent::__construct($action, 'Email Account', $context);
    }
}

class EmailServiceException extends ServiceException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An email service error occurred. Please try again later.';
    }
}

class EmailBusinessException extends BusinessException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An email business rule was violated.';
    }
}
