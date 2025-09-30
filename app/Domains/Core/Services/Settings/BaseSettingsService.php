<?php

namespace App\Domains\Core\Services\Settings;

use App\Models\SettingsConfiguration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

abstract class BaseSettingsService
{
    protected string $domain;

    protected ?int $companyId;

    public function __construct(?int $companyId = null)
    {
        // Don't try to get company_id in constructor, defer until needed
        $this->companyId = $companyId;
    }

    /**
     * Get the company ID, either from constructor or current user
     */
    protected function getCompanyId(): int
    {
        if ($this->companyId === null) {
            $user = auth()->user();
            if (! $user) {
                throw new \Exception('No authenticated user found');
            }
            $this->companyId = $user->company_id;
        }

        return $this->companyId;
    }

    /**
     * Get settings for a specific category
     */
    public function getSettings(string $category): array
    {
        return SettingsConfiguration::getSettings($this->getCompanyId(), $this->domain, $category);
    }

    /**
     * Save settings for a specific category
     */
    public function saveSettings(string $category, array $data): SettingsConfiguration
    {
        // Validate the data
        $validated = $this->validateSettings($category, $data);

        // Process before saving (encryption, etc.)
        $processed = $this->processBeforeSave($category, $validated);

        // Save to database
        $config = SettingsConfiguration::saveSettings(
            $this->getCompanyId(),
            $this->domain,
            $category,
            $processed
        );

        // Post-save actions (clear cache, etc.)
        $this->afterSave($category, $config);

        return $config;
    }

    /**
     * Validate settings data
     */
    protected function validateSettings(string $category, array $data): array
    {
        $rules = $this->getValidationRules($category);

        if (empty($rules)) {
            Log::debug('No validation rules for category', ['category' => $category]);
            return $data;
        }

        Log::debug('Validating settings', [
            'category' => $category,
            'rules' => $rules,
            'data' => $data,
        ]);

        $validator = Validator::make($data, $rules, $this->getValidationMessages($category));

        if ($validator->fails()) {
            Log::error('Settings validation failed', [
                'category' => $category,
                'errors' => $validator->errors()->toArray(),
            ]);
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Get validation rules for a category
     */
    abstract protected function getValidationRules(string $category): array;

    /**
     * Get validation messages
     */
    protected function getValidationMessages(string $category): array
    {
        return [];
    }

    /**
     * Process data before saving (e.g., encryption)
     */
    protected function processBeforeSave(string $category, array $data): array
    {
        return $data;
    }

    /**
     * Actions to perform after saving
     */
    protected function afterSave(string $category, SettingsConfiguration $config): void
    {
        // Override in child classes if needed
    }

    /**
     * Test configuration (override in child classes)
     */
    public function testConfiguration(string $category, array $data): array
    {
        return [
            'success' => true,
            'message' => 'Configuration is valid',
        ];
    }

    /**
     * Get default settings for a category
     */
    abstract public function getDefaultSettings(string $category): array;

    /**
     * Get category metadata
     */
    abstract public function getCategoryMetadata(string $category): array;
}
