<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmailSettingsRequest extends FormRequest
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
            // SMTP Settings
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_encryption' => 'nullable|in:tls,ssl',
            'smtp_auth_method' => 'nullable|in:password,oauth',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'mail_from_email' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',

            // IMAP Settings
            'imap_host' => 'nullable|string|max:255',
            'imap_port' => 'nullable|integer|min:1|max:65535',
            'imap_encryption' => 'nullable|in:tls,ssl',
            'imap_auth_method' => 'nullable|in:password,oauth',
            'imap_username' => 'nullable|string|max:255',
            'imap_password' => 'nullable|string|max:255',

            // Ticket Email Settings
            'ticket_email_parse' => 'boolean',
            'ticket_new_ticket_notification_email' => 'nullable|email|max:255',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'smtp_host.max' => 'The SMTP host must not exceed 255 characters.',
            'smtp_port.min' => 'The SMTP port must be at least 1.',
            'smtp_port.max' => 'The SMTP port must not exceed 65535.',
            'smtp_encryption.in' => 'The SMTP encryption must be either TLS or SSL.',
            'mail_from_email.email' => 'The from email must be a valid email address.',
            'imap_host.max' => 'The IMAP host must not exceed 255 characters.',
            'imap_port.min' => 'The IMAP port must be at least 1.',
            'imap_port.max' => 'The IMAP port must not exceed 65535.',
            'imap_encryption.in' => 'The IMAP encryption must be either TLS or SSL.',
            'ticket_new_ticket_notification_email.email' => 'The notification email must be a valid email address.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert checkbox to boolean
        $this->merge([
            'ticket_email_parse' => $this->has('ticket_email_parse'),
        ]);
        
        // Remove empty passwords (keep existing)
        if (empty($this->smtp_password)) {
            $this->request->remove('smtp_password');
        }
        
        if (empty($this->imap_password)) {
            $this->request->remove('imap_password');
        }
    }
}