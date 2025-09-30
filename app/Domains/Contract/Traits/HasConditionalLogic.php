<?php

namespace App\Domains\Contract\Traits;

/**
 * HasConditionalLogic Trait
 *
 * Provides common conditional logic evaluation for form sections and mappings.
 * Eliminates duplication of conditional visibility logic.
 */
trait HasConditionalLogic
{
    /**
     * Evaluate conditional logic array against form data
     */
    public function evaluateConditionalLogic(array $conditionalLogic, array $formData = []): bool
    {
        if (empty($conditionalLogic)) {
            return true;
        }

        foreach ($conditionalLogic as $condition) {
            if (! $this->evaluateCondition($condition, $formData)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate single condition
     */
    protected function evaluateCondition(array $condition, array $formData): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'] ?? null;

        if (! $field || ! isset($formData[$field])) {
            return true; // Skip conditions for missing fields
        }

        $fieldValue = $formData[$field];

        switch ($operator) {
            case '=':
            case '==':
                return $fieldValue == $value;

            case '!=':
                return $fieldValue != $value;

            case 'in':
                return in_array($fieldValue, (array) $value);

            case 'not_in':
                return ! in_array($fieldValue, (array) $value);

            case 'empty':
                return empty($fieldValue);

            case 'not_empty':
                return ! empty($fieldValue);

            case '>':
                return is_numeric($fieldValue) && is_numeric($value) && $fieldValue > $value;

            case '<':
                return is_numeric($fieldValue) && is_numeric($value) && $fieldValue < $value;

            case '>=':
                return is_numeric($fieldValue) && is_numeric($value) && $fieldValue >= $value;

            case '<=':
                return is_numeric($fieldValue) && is_numeric($value) && $fieldValue <= $value;

            case 'contains':
                return is_string($fieldValue) && str_contains($fieldValue, $value);

            case 'starts_with':
                return is_string($fieldValue) && str_starts_with($fieldValue, $value);

            case 'ends_with':
                return is_string($fieldValue) && str_ends_with($fieldValue, $value);

            default:
                return true; // Unknown operators default to true
        }
    }

    /**
     * Check if should be visible based on conditional logic
     */
    public function shouldBeVisible(array $formData = []): bool
    {
        $conditionalLogic = $this->conditional_logic ?? [];

        return $this->evaluateConditionalLogic($conditionalLogic, $formData);
    }
}
