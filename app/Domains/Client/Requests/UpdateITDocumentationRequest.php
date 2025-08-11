<?php

namespace App\Domains\Client\Requests;

use App\Domains\Client\Models\ClientITDocumentation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateITDocumentationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $documentation = $this->route('itDocumentation');
        return auth()->check() && $this->user()->can('update', $documentation);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('tenant_id', auth()->user()->tenant_id);
                }),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'it_category' => [
                'required',
                'string',
                Rule::in(array_keys(ClientITDocumentation::getITCategories())),
            ],
            'access_level' => [
                'required',
                'string', 
                Rule::in(array_keys(ClientITDocumentation::getAccessLevels())),
            ],
            'review_schedule' => [
                'required',
                'string',
                Rule::in(array_keys(ClientITDocumentation::getReviewSchedules())),
            ],
            'system_references' => 'nullable|array',
            'system_references.*' => 'string|max:255',
            'ip_addresses' => 'nullable|array',
            'ip_addresses.*' => 'string|max:45', // IPv6 max length
            'software_versions' => 'nullable|array',
            'software_versions.*.name' => 'required_with:software_versions|string|max:255',
            'software_versions.*.version' => 'required_with:software_versions|string|max:100',
            'compliance_requirements' => 'nullable|array',
            'compliance_requirements.*' => 'string|max:255',
            'procedure_steps' => 'nullable|array',
            'procedure_steps.*.title' => 'required_with:procedure_steps|string|max:255',
            'procedure_steps.*.description' => 'required_with:procedure_steps|string|max:2000',
            'procedure_steps.*.order' => 'required_with:procedure_steps|integer|min:1',
            'related_entities' => 'nullable|array',
            'related_entities.*.type' => 'required_with:related_entities|string|max:100',
            'related_entities.*.id' => 'required_with:related_entities|integer',
            'related_entities.*.name' => 'required_with:related_entities|string|max:255',
            'tags' => 'nullable|string|max:500',
            'file' => 'nullable|file|mimes:pdf,doc,docx,txt,png,jpg,jpeg,gif,zip,xlsx,xls,pptx,ppt|max:51200', // 50MB
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'client_id.required' => 'Please select a client.',
            'client_id.exists' => 'The selected client is invalid.',
            'name.required' => 'Documentation name is required.',
            'name.max' => 'Documentation name cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 5000 characters.',
            'it_category.required' => 'Please select an IT category.',
            'it_category.in' => 'The selected IT category is invalid.',
            'access_level.required' => 'Please select an access level.',
            'access_level.in' => 'The selected access level is invalid.',
            'review_schedule.required' => 'Please select a review schedule.',
            'review_schedule.in' => 'The selected review schedule is invalid.',
            'file.max' => 'File size cannot exceed 50MB.',
            'file.mimes' => 'File must be a PDF, DOC, DOCX, TXT, image, ZIP, Excel, or PowerPoint file.',
            'tags.max' => 'Tags cannot exceed 500 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'client_id' => 'client',
            'it_category' => 'IT category',
            'access_level' => 'access level',
            'review_schedule' => 'review schedule',
            'system_references.*' => 'system reference',
            'ip_addresses.*' => 'IP address',
            'software_versions.*.name' => 'software name',
            'software_versions.*.version' => 'software version',
            'compliance_requirements.*' => 'compliance requirement',
            'procedure_steps.*.title' => 'step title',
            'procedure_steps.*.description' => 'step description',
            'procedure_steps.*.order' => 'step order',
            'related_entities.*.type' => 'entity type',
            'related_entities.*.name' => 'entity name',
            'is_active' => 'active status',
        ];
    }

    /**
     * Handle a passed validation attempt.
     */
    public function passedValidation(): void
    {
        // Process tags into array
        if ($this->filled('tags')) {
            $this->merge([
                'tags' => array_filter(array_map('trim', explode(',', $this->input('tags')))),
            ]);
        }

        // Ensure procedure steps are ordered
        if ($this->filled('procedure_steps')) {
            $steps = collect($this->input('procedure_steps'))
                ->sortBy('order')
                ->values()
                ->toArray();
            
            $this->merge(['procedure_steps' => $steps]);
        }

        // Handle checkbox for is_active
        $this->merge([
            'is_active' => $this->has('is_active'),
        ]);
    }
}