<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreTicketReplyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('update', $this->route('ticket'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Required fields (support both 'reply' and 'message' for backward compatibility)
            'message' => 'required|string|max:65535',
            'reply' => 'sometimes|string|max:65535',
            'type' => 'required|in:public,private',

            // Optional fields (support both field names for backward compatibility)
            'time_spent' => 'nullable|numeric|min:0|max:999.99',
            'time_worked' => 'nullable|numeric|min:0|max:999.99',
            'status' => 'nullable|in:Open,In Progress,Waiting,Resolved,Closed',

            // Attachments
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:pdf,doc,docx,txt,jpg,jpeg,png,gif|max:10240', // 10MB max per file
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'message.required' => 'Reply content is required.',
            'message.max' => 'Reply content cannot exceed 65,535 characters.',
            'type.required' => 'Please select reply type (public or private).',
            'type.in' => 'Reply type must be either public or private.',
            'time_spent.numeric' => 'Time spent must be a valid number.',
            'time_spent.min' => 'Time spent cannot be negative.',
            'time_spent.max' => 'Time spent cannot exceed 999.99 hours.',
            'status.in' => 'Status must be Open, In Progress, Waiting, Resolved, or Closed.',
            'attachments.max' => 'You can upload a maximum of 5 files.',
            'attachments.*.file' => 'Each attachment must be a valid file.',
            'attachments.*.mimes' => 'Attachments must be PDF, DOC, DOCX, TXT, JPG, JPEG, PNG, or GIF files.',
            'attachments.*.max' => 'Each attachment cannot exceed 10MB.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'message' => 'reply content',
            'type' => 'reply type',
            'time_spent' => 'time spent',
            'status' => 'ticket status',
            'attachments' => 'attachments',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Map 'reply' field to 'message' for backward compatibility
        if ($this->has('reply') && ! $this->has('message')) {
            $this->merge([
                'message' => $this->reply,
            ]);
        }

        // Map 'time_worked' field to 'time_spent' for backward compatibility
        if ($this->has('time_worked') && ! $this->has('time_spent')) {
            $this->merge([
                'time_spent' => $this->time_worked,
            ]);
        }

        // Clean and format time spent - handle empty strings and null values
        $timeSpentValue = $this->input('time_spent');
        $timeWorkedValue = $this->input('time_worked');

        // Use whichever field has a value, prioritizing time_spent
        $timeValue = $timeSpentValue ?? $timeWorkedValue;

        if (! empty($timeValue) && $timeValue !== '' && $timeValue !== 'null') {
            $timeSpent = (float) $timeValue;
            $this->merge([
                'time_spent' => $timeSpent > 0 ? $timeSpent : null,
            ]);
        } else {
            $this->merge(['time_spent' => null]);
        }

        // Ensure type is lowercase
        if ($this->has('type')) {
            $this->merge([
                'type' => strtolower(trim($this->type)),
            ]);
        }

        // Clean message content
        if ($this->has('message')) {
            $this->merge([
                'message' => trim($this->message),
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = Auth::user();
            $ticket = $this->route('ticket');

            // Validate message content is not empty after trimming
            if (empty(trim($this->message ?? ''))) {
                $validator->errors()->add('message', 'Reply content cannot be empty.');
            }

            // Validate status change permissions
            if ($this->filled('status') && $ticket) {
                $currentStatus = $ticket->status;
                $newStatus = $this->status;

                // Prevent reopening closed tickets without proper permissions
                if ($currentStatus === 'Closed' && $newStatus !== 'Closed') {
                    if (! $user->settings || $user->settings->role < 3) { // Manager level required
                        $validator->errors()->add('status', 'Only managers can reopen closed tickets.');
                    }
                }

                // Prevent closing tickets without proper permissions
                if ($newStatus === 'Closed' && $currentStatus !== 'Closed') {
                    if (! $user->settings || $user->settings->role < 2) { // Technician level required
                        $validator->errors()->add('status', 'Only technicians and managers can close tickets.');
                    }
                }
            }

            // Validate time tracking permissions
            if ($this->filled('time_spent')) {
                if (! $user->settings || $user->settings->role < 2) { // Technician level required
                    $validator->errors()->add('time_spent', 'Only technicians and managers can log time.');
                }
            }

            // Business rule: Private replies cannot change ticket status
            if ($this->type === 'private' && $this->filled('status')) {
                $validator->errors()->add('status', 'Private replies cannot change ticket status.');
            }

            // Business rule: Time can only be logged on public replies or by technicians
            if ($this->filled('time_spent') && $this->type === 'private') {
                if (! $user->settings || $user->settings->role < 2) {
                    $validator->errors()->add('time_spent', 'Time can only be logged on public replies.');
                }
            }
        });
    }
}
