<?php

namespace App\Domains\PhysicalMail\Exceptions;

use App\Exceptions\BaseException;
use Exception;

class PostGridException extends Exception
{
    protected string $errorType;

    public function __construct(string $message, int $code = 0, string $errorType = 'unknown_error', ?Exception $previous = null)
    {
        $this->errorType = $errorType;
        parent::__construct($message, $code, $previous);
    }

    public function getErrorType(): string
    {
        return $this->errorType;
    }

    public function isRetryable(): bool
    {
        return in_array($this->code, [500, 502, 503, 504]) ||
               in_array($this->errorType, ['rate_limit', 'timeout', 'service_unavailable']);
    }
}

class PhysicalMailConfigurationException extends BaseException
{
    protected function getDefaultUserMessage(): string
    {
        return 'Physical mail is not configured properly.';
    }
}
