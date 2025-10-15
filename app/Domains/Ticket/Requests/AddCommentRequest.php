<?php

namespace App\Domains\Ticket\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('addReply', $this->route('ticket'));
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|min:1',
            'visibility' => 'required|in:public,internal',
            'time_minutes' => 'nullable|integer|min:1|max:480',
            'billable' => 'nullable|boolean',
        ];
    }
}
