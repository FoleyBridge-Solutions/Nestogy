<?php

namespace App\Domains\Ticket\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('ticket'));
    }

    public function rules(): array
    {
        return [
            'scheduled_at' => 'required|date|after:now',
            'scheduled_duration' => 'nullable|integer|min:15|max:480',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
