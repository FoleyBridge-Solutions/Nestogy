<?php

namespace App\Domains\Ticket\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MergeTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('merge', $this->route('ticket'));
    }

    public function rules(): array
    {
        $currentTicketId = $this->route('ticket')->id;

        return [
            'target_ticket_id' => [
                'required',
                'integer',
                Rule::exists('tickets', 'id')->where('company_id', $this->user()->company_id),
                Rule::notIn([$currentTicketId]),
            ],
            'merge_comments' => 'boolean',
            'merge_time_entries' => 'boolean',
            'merge_attachments' => 'boolean',
            'close_source_ticket' => 'boolean',
            'merge_reason' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'target_ticket_id.not_in' => 'Cannot merge a ticket with itself.',
        ];
    }
}
