<?php

namespace App\Domains\Financial\Exceptions;

use App\Exceptions\BusinessException;

class TimeEntryInvoiceException extends BusinessException
{
    public static function noUninvoicedEntries(): self
    {
        return new self(
            'No uninvoiced time entries found for the specified IDs.',
            0,
            null,
            [],
            'No uninvoiced time entries were found for the selected items.',
            400
        );
    }

    public static function multipleClients(): self
    {
        return new self(
            'All time entries must belong to the same client.',
            0,
            null,
            [],
            'All selected time entries must belong to the same client.',
            400
        );
    }

    protected function getDefaultUserMessage(): string
    {
        return 'An error occurred while processing time entries for invoicing.';
    }
}
