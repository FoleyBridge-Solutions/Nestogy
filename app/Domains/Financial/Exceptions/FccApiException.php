<?php

namespace App\Domains\Financial\Exceptions;

class FccApiException extends FinancialException
{
    protected $errorType = 'fcc_api_error';

    public function toArray(): array
    {
        return [
            'error_type' => $this->errorType,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->context,
        ];
    }
}
