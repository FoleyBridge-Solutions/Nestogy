<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SecuritySettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->company_id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'client_portal_enable' => 'boolean',
            'login_message' => 'nullable|string|max:500',
            'login_key_required' => 'boolean',
            'login_key_secret' => 'nullable|string|min:8|max:255',
            'destructive_deletes_enable' => 'boolean',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'login_message.max' => 'The login message must not exceed 500 characters.',
            'login_key_secret.min' => 'The login key secret must be at least 8 characters.',
            'login_key_secret.max' => 'The login key secret must not exceed 255 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert checkboxes to boolean values
        $this->merge([
            'client_portal_enable' => $this->has('client_portal_enable'),
            'login_key_required' => $this->has('login_key_required'),
            'destructive_deletes_enable' => $this->has('destructive_deletes_enable'),
        ]);
    }
}
