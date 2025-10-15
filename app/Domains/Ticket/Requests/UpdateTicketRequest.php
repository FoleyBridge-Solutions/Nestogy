<?php

namespace App\Domains\Ticket\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('ticket'));
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'client_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('clients', 'id')->where('company_id', $user->company_id),
            ],
            'contact_id' => [
                'nullable',
                'integer',
                Rule::exists('contacts', 'id')->where('client_id', $this->input('client_id', $this->route('ticket')->client_id)),
            ],
            'subject' => 'sometimes|required|string|max:255',
            'details' => 'sometimes|required|string',
            'priority' => 'sometimes|required|in:Low,Medium,High,Critical',
            'status' => 'sometimes|required|in:new,open,in_progress,pending,resolved,closed',
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('company_id', $user->company_id),
            ],
            'scheduled_at' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0|max:999.99',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'custom_fields' => 'nullable|array',
        ];
    }
}
