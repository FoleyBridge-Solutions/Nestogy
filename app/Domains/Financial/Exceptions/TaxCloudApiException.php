<?php

namespace App\Domains\Financial\Exceptions;

use Throwable;

class TaxCloudApiException extends FinancialException
{
    protected $errorType = 'taxcloud_api_error';

    protected $apiVersion;

    protected $endpoint;

    public function __construct(
        string $message = 'TaxCloud API request failed',
        ?string $apiVersion = null,
        ?string $endpoint = null,
        int $code = 502,
        ?Throwable $previous = null
    ) {
        $this->apiVersion = $apiVersion;
        $this->endpoint = $endpoint;
        parent::__construct($message, $code, $previous);
    }

    public function getApiVersion(): ?string
    {
        return $this->apiVersion;
    }

    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'api_version' => $this->apiVersion,
            'endpoint' => $this->endpoint,
            'context' => $this->getContext(),
            'type' => $this->getErrorType(),
            'code' => $this->getCode(),
        ];
    }
}
