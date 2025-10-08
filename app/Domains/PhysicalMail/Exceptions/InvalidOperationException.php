<?php

namespace App\Domains\PhysicalMail\Exceptions;

use Exception;

class InvalidOperationException extends Exception
{
    public static function testOrdersOnly(): self
    {
        return new self('Can only progress test orders');
    }

    public static function orderNotSent(): self
    {
        return new self('Order has not been sent to PostGrid yet');
    }

    public static function cannotBeCancelled(string $status): self
    {
        return new self("Order cannot be cancelled in status: {$status}");
    }

    public static function templateNotFound(string $templateName): self
    {
        return new self("Template not found: {$templateName}");
    }
}
