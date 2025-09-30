<?php

namespace App\Http\Requests;

abstract class BaseUpdateRequest extends BaseRequest
{
    public function authorize(): bool
    {
        $model = $this->route($this->getRouteParameterName());

        return $this->user()->can('update', $model);
    }

    public function rules(): array
    {
        return $this->getValidationRules();
    }

    abstract protected function getValidationRules(): array;

    protected function getRouteParameterName(): string
    {
        // Default route parameter name, can be overridden
        return $this->getResourceName();
    }

    protected function getResourceName(): string
    {
        // Extract resource name from class name
        // e.g. UpdateClientDocumentRequest -> document
        $className = class_basename($this);
        $name = str_replace(['Update', 'Request'], '', $className);

        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name));
    }

    protected function prepareForValidation(): void
    {
        // Convert boolean fields
        $booleanFields = $this->getBooleanFields();
        $data = [];

        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $data[$field] = $this->boolean($field);
            }
        }

        if (! empty($data)) {
            $this->merge($data);
        }

        $this->customPrepareForValidation();
    }

    protected function getBooleanFields(): array
    {
        return [];
    }

    protected function customPrepareForValidation(): void
    {
        // Override in child classes
    }
}
