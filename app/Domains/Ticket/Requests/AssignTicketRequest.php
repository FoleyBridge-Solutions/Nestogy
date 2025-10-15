<?php

namespace App\Domains\Ticket\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assign', $this->route('ticket'));
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'assigned_to' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('company_id', $user->company_id),
            ],
            'reason' => 'nullable|string|max:500',
        ];
    }
}
