<?php

namespace App\Domains\Financial\Exceptions;

use Throwable;

class CensusBureauApiException extends FinancialException
{
    protected string $operation;

    protected array $parameters;

    protected $errorType = 'census_api_error';

    public function __construct(
        string $message,
        string $operation,
        array $parameters = [],
        int $code = 502,
        ?Throwable $previous = null
    ) {
        $this->operation = $operation;
        $this->parameters = $parameters;
        $this->context = [
            'operation' => $operation,
            'parameters' => $parameters,
        ];

        parent::__construct($message, $code, $previous);
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public static function geocodingFailed(string $responseBody, array $parameters = []): self
    {
        return new self(
            'Census geocoding failed: '.$responseBody,
            'geocoding',
            $parameters,
            502
        );
    }

    public static function geographicInfoFailed(string $responseBody, array $parameters = []): self
    {
        return new self(
            'Census geographic info failed: '.$responseBody,
            'geographic_info',
            $parameters,
            502
        );
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'operation' => $this->operation,
            'parameters' => $this->parameters,
            'context' => $this->getContext(),
            'type' => $this->getErrorType(),
            'code' => $this->getCode(),
        ];
    }
}
