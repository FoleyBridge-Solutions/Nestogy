<?php

namespace App\Domains\Financial\Exceptions;

use Throwable;

class GeocodingApiException extends FinancialException
{
    protected $errorType = 'geocoding_api_error';

    protected $provider;

    protected $operation;

    protected $details = [];

    public function __construct(
        string $message,
        string $provider,
        string $operation,
        array $details = [],
        int $code = 502,
        ?Throwable $previous = null
    ) {
        $this->provider = $provider;
        $this->operation = $operation;
        $this->details = $details;
        $this->context = $details;
        parent::__construct($message, $code, $previous);
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'provider' => $this->provider,
            'operation' => $this->operation,
            'details' => $this->details,
            'context' => $this->getContext(),
            'type' => $this->getErrorType(),
            'code' => $this->getCode(),
        ];
    }
}
