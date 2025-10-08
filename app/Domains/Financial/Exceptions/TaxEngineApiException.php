<?php

namespace App\Domains\Financial\Exceptions;

use Throwable;

class TaxEngineApiException extends FinancialException
{
    protected $apiProvider;

    protected $details = [];

    protected $errorType = 'tax_engine_api_error';

    public function __construct(
        string $message = 'Tax engine API request failed',
        ?string $apiProvider = null,
        array $details = [],
        int $code = 502,
        ?Throwable $previous = null
    ) {
        $this->apiProvider = $apiProvider;
        $this->details = $details;
        $this->context = $details;
        parent::__construct($message, $code, $previous);
    }

    public function getApiProvider(): ?string
    {
        return $this->apiProvider;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public static function apiRequestFailed(string $apiProvider, int $statusCode, ?Throwable $previous = null): self
    {
        return new static(
            "API request failed with status: {$statusCode}",
            $apiProvider,
            ['status_code' => $statusCode],
            502,
            $previous
        );
    }

    public static function invalidResponse(string $apiProvider, string $reason, ?Throwable $previous = null): self
    {
        return new static(
            "Invalid response from API: {$reason}",
            $apiProvider,
            ['reason' => $reason],
            502,
            $previous
        );
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'api_provider' => $this->apiProvider,
            'details' => $this->details,
            'context' => $this->getContext(),
            'type' => $this->getErrorType(),
            'code' => $this->getCode(),
        ];
    }
}
