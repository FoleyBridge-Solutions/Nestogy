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

class EmailPermissionException extends BasePermissionException
{
    public function __construct(string $action, array $context = [])
    {
        parent::__construct($action, 'Email', $context);
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
        return 'An email business rule violation occurred.';
    }
}

class EmailAccountException extends EmailException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An error occurred with the email account.';
    }
}

class EmailSyncException extends EmailException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An error occurred while syncing email.';
    }
}

class EmailConnectionException extends EmailException
{
    protected function getDefaultUserMessage(): string
    {
        return 'Failed to connect to email server.';
    }
}

class OAuthTokenException extends EmailException
{
    protected function getDefaultUserMessage(): string
    {
        return 'OAuth token error occurred.';
    }
}
