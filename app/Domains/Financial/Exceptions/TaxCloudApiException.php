<?php

namespace App\Domains\Financial\Exceptions;

use Throwable;

class TaxCloudApiException extends FinancialException
{
    protected $errorType = 'taxcloud_api_error';

    protected ?string $cartId;

    protected ?string $orderId;

    protected ?array $responseData;

    public function __construct(
        string $message = 'TaxCloud API operation failed',
        ?string $cartId = null,
        ?string $orderId = null,
        ?array $responseData = null,
        int $code = 502,
        ?Throwable $previous = null
    ) {
        $this->cartId = $cartId;
        $this->orderId = $orderId;
        $this->responseData = $responseData;

        $this->context = array_filter([
            'cart_id' => $cartId,
            'order_id' => $orderId,
            'response_data' => $responseData,
        ]);

        parent::__construct($message, $code, $previous);
    }

    public function getCartId(): ?string
    {
        return $this->cartId;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function getResponseData(): ?array
    {
        return $this->responseData;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'cart_id' => $this->cartId,
            'order_id' => $this->orderId,
            'response_data' => $this->responseData,
            'context' => $this->getContext(),
            'type' => $this->getErrorType(),
            'code' => $this->getCode(),
        ];
    }
}
