<?php

namespace App\Domains\PhysicalMail\Exceptions;

use Exception;

class PostGridException extends Exception
{
    protected string $errorType;
    
    public function __construct(string $message, int $code = 0, string $errorType = 'unknown_error', Exception $previous = null)
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
        // Determine if this error should trigger a retry
        return in_array($this->code, [500, 502, 503, 504]) || 
               in_array($this->errorType, ['rate_limit', 'timeout', 'service_unavailable']);
    }
}