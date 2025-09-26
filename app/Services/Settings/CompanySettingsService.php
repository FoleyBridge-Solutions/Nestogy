<?php

namespace App\Services\Settings;

use App\Models\SettingsConfiguration;

class CompanySettingsService extends BaseSettingsService
{
    protected string $domain = SettingsConfiguration::DOMAIN_COMPANY;

    /**
     * Get validation rules for each category
     */
    protected function getValidationRules(string $category): array
    {
        switch ($category) {
            case 'general':
                return [
                    'company_name' => 'required|string|max:255',
                    'legal_name' => 'nullable|string|max:255',
                    'tax_id' => 'nullable|string|max:50',
                    'website' => 'nullable|url',
                    'phone' => 'nullable|string|max:20',
                    'email' => 'nullable|email',
                    'address_line1' => 'nullable|string|max:255',
                    'address_line2' => 'nullable|string|max:255',
                    'city' => 'nullable|string|max:100',
                    'state' => 'nullable|string|max:100',
                    'postal_code' => 'nullable|string|max:20',
                    'country' => 'nullable|string|max:2',
                ];
                
            case 'branding':
                return [
                    'primary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                    'secondary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                    'logo_url' => 'nullable|url',
                    'favicon_url' => 'nullable|url',
                    'email_logo_url' => 'nullable|url',
                    'invoice_logo_url' => 'nullable|url',
                    'portal_theme' => 'nullable|in:light,dark,auto',
                ];
                
            case 'localization':
                return [
                    'timezone' => 'required|timezone',
                    'date_format' => 'required|string',
                    'time_format' => 'required|in:12,24',
                    'currency' => 'required|string|size:3',
                    'currency_position' => 'required|in:before,after',
                    'thousand_separator' => 'nullable|string|max:1',
                    'decimal_separator' => 'nullable|string|max:1',
                    'decimal_places' => 'required|integer|between:0,4',
                    'language' => 'required|string|size:2',
                    'week_starts_on' => 'required|integer|between:0,6',
                ];
                
            default:
                return [];
        }
    }

    /**
     * Get default settings for a category
     */
    public function getDefaultSettings(string $category): array
    {
        switch ($category) {
            case 'general':
                return [
                    'company_name' => 'My Company',
                    'country' => 'US',
                ];
                
            case 'branding':
                return [
                    'primary_color' => '#3B82F6',
                    'secondary_color' => '#1E40AF',
                    'portal_theme' => 'light',
                ];
                
            case 'localization':
                return [
                    'timezone' => 'America/New_York',
                    'date_format' => 'Y-m-d',
                    'time_format' => '12',
                    'currency' => 'USD',
                    'currency_position' => 'before',
                    'thousand_separator' => ',',
                    'decimal_separator' => '.',
                    'decimal_places' => 2,
                    'language' => 'en',
                    'week_starts_on' => 0, // Sunday
                ];
                
            default:
                return [];
        }
    }

    /**
     * Get category metadata
     */
    public function getCategoryMetadata(string $category): array
    {
        switch ($category) {
            case 'general':
                return [
                    'name' => 'General Information',
                    'description' => 'Basic company information and contact details',
                    'icon' => 'information-circle',
                ];
                
            case 'branding':
                return [
                    'name' => 'Branding',
                    'description' => 'Customize colors, logos, and themes',
                    'icon' => 'paint-brush',
                ];
                
            case 'localization':
                return [
                    'name' => 'Localization',
                    'description' => 'Regional settings, timezone, and formats',
                    'icon' => 'globe-alt',
                ];
                
            default:
                return [];
        }
    }
}