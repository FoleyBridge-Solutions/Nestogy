<?php

namespace App\Domains\PhysicalMail\Exceptions;

use Exception;

class MissingContactException extends Exception
{
    protected string $contactType;

    public function __construct(string $contactType = 'contact', ?Exception $previous = null)
    {
        $this->contactType = $contactType;
        $message = ucfirst($contactType).' not found';
        parent::__construct($message, 0, $previous);
    }

    public function getContactType(): string
    {
        return $this->contactType;
    }
}
