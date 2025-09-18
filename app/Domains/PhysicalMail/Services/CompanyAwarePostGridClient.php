<?php

namespace App\Domains\PhysicalMail\Services;

use App\Models\PhysicalMailSettings;

class CompanyAwarePostGridClient extends PostGridClient
{
    protected ?PhysicalMailSettings $settings;
    
    public function __construct(?int $companyId = null)
    {
        // Get company settings
        $this->settings = PhysicalMailSettings::forCompany($companyId);
        
        if (!$this->settings) {
            throw new \Exception('No physical mail settings found for company');
        }
        
        if (!$this->settings->isConfigured()) {
            throw new \Exception('Physical mail is not configured for this company');
        }
        
        // Initialize parent with company settings
        parent::__construct(
            testMode: $this->settings->shouldUseTestMode(),
            apiKey: $this->settings->getActiveApiKey()
        );
    }
    
    /**
     * Get company settings
     */
    public function getSettings(): PhysicalMailSettings
    {
        return $this->settings;
    }
    
    /**
     * Get default from address
     */
    public function getDefaultFromAddress(): array
    {
        return $this->settings->getFromAddress();
    }
    
    /**
     * Get default mail options
     */
    public function getDefaultOptions(): array
    {
        return [
            'color' => $this->settings->default_color_printing,
            'doubleSided' => $this->settings->default_double_sided,
            'mailingClass' => $this->settings->default_mailing_class,
            'addressPlacement' => $this->settings->default_address_placement,
            'size' => $this->settings->default_size,
        ];
    }
}