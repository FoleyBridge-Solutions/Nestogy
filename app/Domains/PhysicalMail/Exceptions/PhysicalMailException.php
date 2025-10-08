<?php

namespace App\Domains\PhysicalMail\Exceptions;

use App\Exceptions\BaseException;
use App\Exceptions\BusinessException;
use App\Exceptions\NotFoundException as BaseNotFoundException;
use App\Exceptions\ServiceException;

class PhysicalMailException extends BaseException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An error occurred while processing your physical mail request.';
    }
}

class PhysicalMailBusinessException extends BusinessException
{
    protected function getDefaultUserMessage(): string
    {
        return 'A physical mail business rule was violated.';
    }
}

class PhysicalMailServiceException extends ServiceException
{
    protected function getDefaultUserMessage(): string
    {
        return 'A physical mail service error occurred. Please try again later.';
    }
}

class PhysicalMailNotFoundException extends BaseNotFoundException
{
    public function __construct(mixed $identifier = null, array $context = [])
    {
        parent::__construct('Physical Mail', $identifier, $context);
    }
}

class PhysicalMailOrderStatusException extends PhysicalMailBusinessException
{
    public function __construct(string $action, string $currentStatus, array $context = [])
    {
        parent::__construct(
            "Cannot {$action} physical mail order in {$currentStatus} status",
            400,
            null,
            array_merge($context, [
                'action' => $action,
                'current_status' => $currentStatus,
            ]),
            "This action cannot be performed on an order with status: {$currentStatus}.",
            400
        );
    }
}

class PhysicalMailTemplateNotFoundException extends PhysicalMailNotFoundException
{
    public function __construct(string $templateName, array $context = [])
    {
        $message = "Template not found: {$templateName}";
        $this->message = $message;
        
        parent::__construct(
            $templateName,
            array_merge($context, ['template_name' => $templateName])
        );
    }
}

class PhysicalMailTestOrderException extends PhysicalMailBusinessException
{
    public function __construct(string $reason, array $context = [])
    {
        parent::__construct(
            "Test order operation failed: {$reason}",
            400,
            null,
            array_merge($context, ['reason' => $reason]),
            "Unable to perform test order operation: {$reason}",
            400
        );
    }
}

class PhysicalMailConfigurationException extends PhysicalMailException
{
    public function __construct(string $issue, array $context = [])
    {
        parent::__construct(
            "Physical mail configuration error: {$issue}",
            500,
            null,
            array_merge($context, ['configuration_issue' => $issue]),
            "Physical mail is not properly configured: {$issue}",
            500
        );
    }
}
