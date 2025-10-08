<?php

namespace App\Domains\Financial\Exceptions;

use Throwable;

class CensusBureauApiException extends FinancialException
{
    protected $operation;

    protected $details = [];

    protected $errorType = 'census_api_error';

    public function __construct(
        string $message = 'Census Bureau API request failed',
        ?string $operation = null,
        array $details = [],
        int $code = 502,
        ?Throwable $previous = null
    ) {
        $this->operation = $operation;
        $this->details = $details;
        $this->context = $details;
        parent::__construct($message, $code, $previous);
    }

    public function getOperation(): ?string
    {
        return $this->operation;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public static function geocodingFailed(string $responseBody): self
    {
        return new static(
            'Census geocoding failed: '.$responseBody,
            'geocoding',
            ['response_body' => $responseBody],
            502
        );
    }

    public static function geographicInfoFailed(string $responseBody): self
    {
        return new static(
            'Census geographic info failed: '.$responseBody,
            'geographic_info',
            ['response_body' => $responseBody],
            502
        );
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'operation' => $this->operation,
            'details' => $this->details,
            'context' => $this->getContext(),
            'type' => $this->getErrorType(),
            'code' => $this->getCode(),
        ];
    }
}
