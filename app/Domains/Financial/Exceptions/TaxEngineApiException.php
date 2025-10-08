<?php

namespace App\Domains\Financial\Exceptions;

use Throwable;

class TaxEngineApiException extends FinancialException
{
    protected $apiProvider;

    protected $operation;

    protected $details = [];

    protected $errorType = 'tax_engine_api_error';

    public function __construct(
        string $message = 'Tax engine API operation failed',
        ?string $apiProvider = null,
        ?string $operation = null,
        array $details = [],
        int $code = 502,
        ?Throwable $previous = null
    ) {
        $this->apiProvider = $apiProvider;
        $this->operation = $operation;
        $this->details = $details;
        $this->context = $details;
        parent::__construct($message, $code, $previous);
    }

    public function getApiProvider(): ?string
    {
        return $this->apiProvider;
    }

    public function getOperation(): ?string
    {
        return $this->operation;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public static function nominatimGeocodingFailed(string $error): self
    {
        return new static(
            'Nominatim geocoding failed: '.$error,
            'nominatim',
            'geocoding',
            ['error' => $error],
            502
        );
    }

    public static function nominatimReverseGeocodingFailed(string $error): self
    {
        return new static(
            'Nominatim reverse geocoding failed: '.$error,
            'nominatim',
            'reverse_geocoding',
            ['error' => $error],
            502
        );
    }

    public static function nominatimPlaceSearchFailed(string $error): self
    {
        return new static(
            'Nominatim place search failed: '.$error,
            'nominatim',
            'place_search',
            ['error' => $error],
            502
        );
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'api_provider' => $this->apiProvider,
            'operation' => $this->operation,
            'details' => $this->details,
            'context' => $this->getContext(),
            'type' => $this->getErrorType(),
            'code' => $this->getCode(),
        ];
    }
}
