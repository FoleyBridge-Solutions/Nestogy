<?php

namespace App\Domains\Core\Services\Settings;

use App\Models\Company;
use App\Models\SettingsConfiguration;
use Illuminate\Support\Facades\Auth;

class CompanySettingsService extends BaseSettingsService
{
    protected string $domain = SettingsConfiguration::DOMAIN_COMPANY;

    private const VALIDATION_NULLABLE_STRING_255 = 'nullable|string|max:255';

    /**
     * Get validation rules for each category
     */
    public function getSettings(string $category): array
    {
        $company = Auth::user()->company;
        
        // For general settings, pull from Company model + company_info JSON
        if ($category === 'general') {
            $companyInfo = $company->company_info ?? [];
            $socialLinks = $company->social_links ?? [];
            
            return [
                'company_name' => $company->name ?? '',
                'legal_name' => $companyInfo['legal_name'] ?? '',
                'business_type' => $companyInfo['business_type'] ?? '',
                'tax_id' => $companyInfo['tax_id'] ?? '',
                'website' => $company->website ?? '',
                'email' => $company->email ?? '',
                'phone' => $company->phone ?? '',
                'address_line1' => $companyInfo['address_line1'] ?? '',
                'address_line2' => $companyInfo['address_line2'] ?? '',
                'city' => $company->city ?? '',
                'state' => $company->state ?? '',
                'postal_code' => $companyInfo['postal_code'] ?? '',
                'country' => $company->country ?? '',
                'linkedin_url' => $socialLinks['linkedin'] ?? '',
                'twitter_url' => $socialLinks['twitter'] ?? '',
                'facebook_url' => $socialLinks['facebook'] ?? '',
            ];
        }
        
        // For branding, pull from branding JSON column
        if ($category === 'branding') {
            $branding = $company->branding ?? [];
            
            return [
                'logo_url' => $branding['logo_url'] ?? '',
                'logo_dark_url' => $branding['logo_dark_url'] ?? '',
                'favicon_url' => $branding['favicon_url'] ?? '',
                'accent_color' => $branding['accent_color'] ?? '#3b82f6',
                'accent_content_color' => $branding['accent_content_color'] ?? '#2563eb',
                'accent_foreground_color' => $branding['accent_foreground_color'] ?? '#ffffff',
                'base_color_scheme' => $branding['base_color_scheme'] ?? 'zinc',
                'default_theme' => $branding['default_theme'] ?? 'light',
                'allow_theme_switching' => $branding['allow_theme_switching'] ?? true,
            ];
        }
        
        // Fall back to parent implementation
        return parent::getSettings($category);
    }

    public function saveSettings(string $category, array $data): SettingsConfiguration
    {
        $company = Auth::user()->company;
        
        \Log::info('CompanySettingsService::saveSettings called', [
            'category' => $category,
            'company_id' => $company->id,
            'data' => $data,
        ]);
        
        // For general settings, save to Company model + JSON columns
        if ($category === 'general') {
            $companyInfo = $company->company_info ?? [];
            $socialLinks = $company->social_links ?? [];
            
            // Update company_info JSON
            $companyInfo['legal_name'] = $data['legal_name'] ?? null;
            $companyInfo['business_type'] = $data['business_type'] ?? null;
            $companyInfo['tax_id'] = $data['tax_id'] ?? null;
            $companyInfo['address_line1'] = $data['address_line1'] ?? null;
            $companyInfo['address_line2'] = $data['address_line2'] ?? null;
            $companyInfo['postal_code'] = $data['postal_code'] ?? null;
            
            // Update social_links JSON
            $socialLinks['linkedin'] = $data['linkedin_url'] ?? null;
            $socialLinks['twitter'] = $data['twitter_url'] ?? null;
            $socialLinks['facebook'] = $data['facebook_url'] ?? null;
            
            // Update main company fields
            $company->update([
                'name' => $data['company_name'] ?? $company->name,
                'website' => $data['website'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'country' => $data['country'] ?? null,
                'company_info' => $companyInfo,
                'social_links' => $socialLinks,
            ]);
        } 
        
        // For branding settings, save to branding JSON column
        elseif ($category === 'branding') {
            $branding = $company->branding ?? [];
            
            $branding['logo_url'] = $data['logo_url'] ?? null;
            $branding['logo_dark_url'] = $data['logo_dark_url'] ?? null;
            $branding['favicon_url'] = $data['favicon_url'] ?? null;
            $branding['accent_color'] = $data['accent_color'] ?? '#3b82f6';
            $branding['accent_content_color'] = $data['accent_content_color'] ?? '#2563eb';
            $branding['accent_foreground_color'] = $data['accent_foreground_color'] ?? '#ffffff';
            $branding['base_color_scheme'] = $data['base_color_scheme'] ?? 'zinc';
            $branding['default_theme'] = $data['default_theme'] ?? 'light';
            $branding['allow_theme_switching'] = isset($data['allow_theme_switching']);
            
            $company->update(['branding' => $branding]);
        }
        
        // Return a dummy SettingsConfiguration since we saved to Company model
        if ($category === 'general' || $category === 'branding') {
            return SettingsConfiguration::firstOrCreate([
                'company_id' => $company->id,
                'domain' => $this->domain,
                'category' => $category,
            ], [
                'settings' => [],
                'is_active' => true,
            ]);
        }
        
        // Fall back to parent implementation
        return parent::saveSettings($category, $data);
    }

    protected function getValidationRules(string $category): array
    {
        switch ($category) {
            case 'general':
                return [
                    'company_name' => 'required|string|max:255',
                    'legal_name' => self::VALIDATION_NULLABLE_STRING_255,
                    'tax_id' => 'nullable|string|max:50',
                    'website' => 'nullable|url',
                    'phone' => 'nullable|string|max:20',
                    'email' => 'nullable|email',
                    'address_line1' => self::VALIDATION_NULLABLE_STRING_255,
                    'address_line2' => self::VALIDATION_NULLABLE_STRING_255,
                    'city' => 'nullable|string|max:100',
                    'state' => 'nullable|string|max:100',
                    'postal_code' => 'nullable|string|max:20',
                    'country' => 'nullable|string|max:2',
                ];

            case 'branding':
                return [
                    'logo_url' => 'nullable|url',
                    'logo_dark_url' => 'nullable|url',
                    'favicon_url' => 'nullable|url',
                    'accent_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                    'accent_content_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                    'accent_foreground_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                    'base_color_scheme' => 'nullable|in:zinc,slate,gray,neutral,stone',
                    'default_theme' => 'nullable|in:light,dark,auto',
                    'allow_theme_switching' => 'boolean',
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

            case 'users':
                return [
                    'name' => 'Users',
                    'description' => 'Manage user accounts and permissions',
                    'icon' => 'users',
                ];

            case 'subsidiaries':
                return [
                    'name' => 'Subsidiaries',
                    'description' => 'Manage subsidiary companies',
                    'icon' => 'building-office-2',
                ];

            default:
                return [];
        }
    }
}
