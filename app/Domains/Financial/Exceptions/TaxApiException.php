<?php

namespace App\Domains\Financial\Exceptions;

use Throwable;

class TaxApiException extends FinancialException
{
    protected $provider;

    protected $operation;

    protected $errorType = 'tax_api_error';

    public function __construct(
        string $message = 'Tax API request failed',
        ?string $provider = null,
        ?string $operation = null,
        int $code = 502,
        ?Throwable $previous = null
    ) {
        $this->provider = $provider;
        $this->operation = $operation;
        parent::__construct($message, $code, $previous);
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function getOperation(): ?string
    {
        return $this->operation;
    }

    public static function requestFailed(string $provider, string $operation, string $error): self
    {
        return new static(
            "{$provider} {$operation} failed: {$error}",
            $provider,
            $operation,
            502
        );
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'provider' => $this->provider,
            'operation' => $this->operation,
            'context' => $this->getContext(),
            'type' => $this->getErrorType(),
            'code' => $this->getCode(),
        ];
    }
}
