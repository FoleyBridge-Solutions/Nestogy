<?php

namespace App\Exceptions;

use Exception;

class TimeEntryInvoiceException extends Exception
{
    public static function noUninvoicedEntries(): self
    {
        return new self('No uninvoiced time entries found for the specified IDs.');
    }

    public static function multipleClients(): self
    {
        return new self('All time entries must belong to the same client.');
    }
}
