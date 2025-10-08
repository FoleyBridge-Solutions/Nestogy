<?php

namespace App\Domains\PhysicalMail\Exceptions;

use App\Exceptions\BusinessException;

class PhysicalMailStatusException extends BusinessException
{
    protected function getDefaultUserMessage(): string
    {
        return 'The mail order cannot be modified in its current status.';
    }
}
