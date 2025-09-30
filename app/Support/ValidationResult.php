<?php

namespace App\Support;

/**
 * Validation result container for plugin validation
 */
class ValidationResult
{
    protected bool $valid;

    protected array $errors;

    protected array $warnings;

    protected array $metadata;

    public function __construct(bool $valid = true, array $errors = [], array $warnings = [], array $metadata = [])
    {
        $this->valid = $valid;
        $this->errors = $errors;
        $this->warnings = $warnings;
        $this->metadata = $metadata;
    }

    /**
     * Create a successful validation result
     */
    public static function success(array $metadata = []): self
    {
        return new self(true, [], [], $metadata);
    }

    /**
     * Create a failed validation result
     */
    public static function failure(array $errors, array $warnings = [], array $metadata = []): self
    {
        return new self(false, $errors, $warnings, $metadata);
    }

    /**
     * Check if validation passed
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * Check if validation failed
     */
    public function isInvalid(): bool
    {
        return ! $this->valid;
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get validation warnings
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Get metadata
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Add error
     */
    public function addError(string $error): self
    {
        $this->errors[] = $error;
        $this->valid = false;

        return $this;
    }

    /**
     * Add warning
     */
    public function addWarning(string $warning): self
    {
        $this->warnings[] = $warning;

        return $this;
    }

    /**
     * Add metadata
     */
    public function addMetadata(string $key, $value): self
    {
        $this->metadata[$key] = $value;

        return $this;
    }

    /**
     * Merge with another validation result
     */
    public function merge(ValidationResult $other): self
    {
        $this->errors = array_merge($this->errors, $other->getErrors());
        $this->warnings = array_merge($this->warnings, $other->getWarnings());
        $this->metadata = array_merge($this->metadata, $other->getMetadata());

        if (! $other->isValid()) {
            $this->valid = false;
        }

        return $this;
    }

    /**
     * Check if there are any errors
     */
    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    /**
     * Check if there are any warnings
     */
    public function hasWarnings(): bool
    {
        return ! empty($this->warnings);
    }

    /**
     * Get first error
     */
    public function getFirstError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Convert to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['valid'] ?? true,
            $data['errors'] ?? [],
            $data['warnings'] ?? [],
            $data['metadata'] ?? []
        );
    }
}
