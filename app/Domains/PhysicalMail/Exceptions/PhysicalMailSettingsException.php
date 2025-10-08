<?php

namespace App\Domains\PhysicalMail\Exceptions;

class PhysicalMailSettingsException extends PostGridException
{
    public static function notFound(?int $companyId = null): self
    {
        $message = $companyId
            ? "No physical mail settings found for company ID: {$companyId}"
            : 'No physical mail settings found for company';

        return new self($message, 404, 'settings_not_found');
    }

    public static function notConfigured(?int $companyId = null): self
    {
        $message = $companyId
            ? "Physical mail is not configured for company ID: {$companyId}"
            : 'Physical mail is not configured for this company';

        return new self($message, 0, 'settings_not_configured');
    }
}
