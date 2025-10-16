<?php

namespace App\Models\Settings;

class CompanySettings extends SettingCategory
{
    public function getCategory(): string
    {
        return 'company';
    }

    public function getAttributes(): array
    {
        return [
            'company_logo',
            'company_colors',
            'company_address',
            'company_city',
            'company_state',
            'company_zip',
            'company_country',
            'company_phone',
            'company_website',
            'company_tax_id',
            'business_hours',
            'company_holidays',
            'company_language',
            'company_currency',
            'custom_fields',
            'localization_settings',
            'start_page',
            'theme',
            'timezone',
            'date_format',
        ];
    }

    public function getLogo(): ?string
    {
        return $this->get('company_logo');
    }

    public function setLogo(?string $logo): self
    {
        $this->set('company_logo', $logo);
        return $this;
    }

    public function getColors(): ?array
    {
        return $this->get('company_colors');
    }

    public function setColors(?array $colors): self
    {
        $this->set('company_colors', $colors);
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->get('company_address');
    }

    public function setAddress(?string $address): self
    {
        $this->set('company_address', $address);
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->get('company_city');
    }

    public function setCity(?string $city): self
    {
        $this->set('company_city', $city);
        return $this;
    }

    public function getState(): ?string
    {
        return $this->get('company_state');
    }

    public function setState(?string $state): self
    {
        $this->set('company_state', $state);
        return $this;
    }

    public function getZip(): ?string
    {
        return $this->get('company_zip');
    }

    public function setZip(?string $zip): self
    {
        $this->set('company_zip', $zip);
        return $this;
    }

    public function getCountry(): string
    {
        return $this->get('company_country', 'US');
    }

    public function setCountry(string $country): self
    {
        $this->set('company_country', $country);
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->get('company_phone');
    }

    public function setPhone(?string $phone): self
    {
        $this->set('company_phone', $phone);
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->get('company_website');
    }

    public function setWebsite(?string $website): self
    {
        $this->set('company_website', $website);
        return $this;
    }

    public function getTaxId(): ?string
    {
        return $this->get('company_tax_id');
    }

    public function setTaxId(?string $taxId): self
    {
        $this->set('company_tax_id', $taxId);
        return $this;
    }

    public function getBusinessHours(): ?array
    {
        return $this->get('business_hours');
    }

    public function setBusinessHours(?array $hours): self
    {
        $this->set('business_hours', $hours);
        return $this;
    }

    public function getHolidays(): ?array
    {
        return $this->get('company_holidays');
    }

    public function setHolidays(?array $holidays): self
    {
        $this->set('company_holidays', $holidays);
        return $this;
    }

    public function getLanguage(): string
    {
        return $this->get('company_language', 'en');
    }

    public function setLanguage(string $language): self
    {
        $this->set('company_language', $language);
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->get('company_currency', 'USD');
    }

    public function setCurrency(string $currency): self
    {
        $this->set('company_currency', $currency);
        return $this;
    }

    public function getCustomFields(): ?array
    {
        return $this->get('custom_fields');
    }

    public function setCustomFields(?array $fields): self
    {
        $this->set('custom_fields', $fields);
        return $this;
    }

    public function getTheme(): string
    {
        return $this->get('theme', 'blue');
    }

    public function setTheme(string $theme): self
    {
        $this->set('theme', $theme);
        return $this;
    }

    public function getTimezone(): string
    {
        return $this->get('timezone', 'UTC');
    }

    public function setTimezone(string $timezone): self
    {
        $this->set('timezone', $timezone);
        return $this;
    }

    public function getStartPage(): string
    {
        return $this->get('start_page', 'dashboard');
    }

    public function setStartPage(string $page): self
    {
        $this->set('start_page', $page);
        return $this;
    }
}
