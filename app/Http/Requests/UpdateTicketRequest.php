<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateTicketRequest extends FormRequest
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
            // Required fields
            'client_id' => 'required|exists:clients,id',
            'subject' => 'required|string|max:255',
            'details' => 'required|string|max:65535',
            'priority' => 'required|in:Low,Medium,High,Critical',
            'status' => 'required|in:Open,In Progress,Waiting,Resolved,Closed',

            // Optional fields
            'contact_id' => 'nullable|exists:contacts,id',
            'assigned_to' => 'nullable|exists:users,id',
            'asset_id' => 'nullable|exists:assets,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'vendor_ticket_number' => 'nullable|string|max:100',
            'billable' => 'boolean',
            'category_id' => 'nullable|exists:categories,id',
            'location_id' => 'nullable|exists:locations,id',

            // Scheduling fields
            'scheduled_at' => 'nullable|date|after:now',
            'onsite' => 'boolean',

            // Attachments
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => 'file|mimes:pdf,doc,docx,txt,jpg,jpeg,png,gif|max:10240', // 10MB max per file

            // Watchers
            'watchers' => 'nullable|array|max:10',
            'watchers.*' => 'email|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'client_id.required' => 'Please select a client.',
            'client_id.exists' => 'The selected client is invalid.',
            'subject.required' => 'Ticket subject is required.',
            'subject.max' => 'Ticket subject cannot exceed 255 characters.',
            'details.required' => 'Ticket details are required.',
            'details.max' => 'Ticket details cannot exceed 65,535 characters.',
            'priority.required' => 'Please select a priority level.',
            'priority.in' => 'Priority must be Low, Medium, High, or Critical.',
            'status.required' => 'Please select a status.',
            'status.in' => 'Status must be Open, In Progress, Waiting, Resolved, or Closed.',
            'contact_id.exists' => 'The selected contact is invalid.',
            'assigned_to.exists' => 'The selected assignee is invalid.',
            'asset_id.exists' => 'The selected asset is invalid.',
            'vendor_id.exists' => 'The selected vendor is invalid.',
            'vendor_ticket_number.max' => 'Vendor ticket number cannot exceed 100 characters.',
            'category_id.exists' => 'The selected category is invalid.',
            'location_id.exists' => 'The selected location is invalid.',
            'scheduled_at.date' => 'Scheduled date must be a valid date.',
            'scheduled_at.after' => 'Scheduled date must be in the future.',
            'attachments.max' => 'You can upload a maximum of 10 files.',
            'attachments.*.file' => 'Each attachment must be a valid file.',
            'attachments.*.mimes' => 'Attachments must be PDF, DOC, DOCX, TXT, JPG, JPEG, PNG, or GIF files.',
            'attachments.*.max' => 'Each attachment cannot exceed 10MB.',
            'watchers.max' => 'You can add a maximum of 10 watchers.',
            'watchers.*.email' => 'Each watcher must have a valid email address.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'client_id' => 'client',
            'subject' => 'subject',
            'details' => 'details',
            'priority' => 'priority',
            'status' => 'status',
            'contact_id' => 'contact',
            'assigned_to' => 'assigned technician',
            'asset_id' => 'asset',
            'vendor_id' => 'vendor',
            'vendor_ticket_number' => 'vendor ticket number',
            'billable' => 'billable status',
            'category_id' => 'category',
            'location_id' => 'location',
            'scheduled_at' => 'scheduled date',
            'onsite' => 'onsite status',
            'attachments' => 'attachments',
            'watchers' => 'watchers',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set boolean defaults
        $this->merge([
            'billable' => $this->boolean('billable'),
            'onsite' => $this->boolean('onsite'),
        ]);

        // Clean vendor ticket number
        if ($this->has('vendor_ticket_number')) {
            $this->merge([
                'vendor_ticket_number' => trim($this->vendor_ticket_number),
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

            // Validate client belongs to user's company
            if ($this->filled('client_id')) {
                $client = \App\Models\Client::find($this->client_id);
                if ($client && $client->company_id !== $user->company_id) {
                    $validator->errors()->add('client_id', 'The selected client is invalid.');
                }
            }

            // Validate contact belongs to the selected client
            if ($this->filled('contact_id') && $this->filled('client_id')) {
                $contact = \App\Domains\Client\Models\ClientContact::find($this->contact_id);
                if ($contact && $contact->client_id != $this->client_id) {
                    $validator->errors()->add('contact_id', 'The selected contact does not belong to the selected client.');
                }
            }

            // Validate asset belongs to the selected client
            if ($this->filled('asset_id') && $this->filled('client_id')) {
                $asset = \App\Domains\Asset\Models\Asset::find($this->asset_id);
                if ($asset && $asset->client_id != $this->client_id) {
                    $validator->errors()->add('asset_id', 'The selected asset does not belong to the selected client.');
                }
            }

            // Validate vendor belongs to the selected client
            if ($this->filled('vendor_id') && $this->filled('client_id')) {
                $vendor = \App\Domains\Client\Models\ClientVendor::find($this->vendor_id);
                if ($vendor && $vendor->client_id != $this->client_id) {
                    $validator->errors()->add('vendor_id', 'The selected vendor does not belong to the selected client.');
                }
            }

            // Validate location belongs to the selected client
            if ($this->filled('location_id') && $this->filled('client_id')) {
                $location = \App\Domains\Client\Models\ClientAddress::find($this->location_id);
                if ($location && $location->client_id != $this->client_id) {
                    $validator->errors()->add('location_id', 'The selected location does not belong to the selected client.');
                }
            }

            // Validate assigned user has appropriate role
            if ($this->filled('assigned_to')) {
                $assignedUser = \App\Models\User::with('settings')->find($this->assigned_to);
                if ($assignedUser) {
                    if ($assignedUser->company_id !== $user->company_id) {
                        $validator->errors()->add('assigned_to', 'The selected user is invalid.');
                    } elseif (! $assignedUser->settings || $assignedUser->settings->role < 2) {
                        $validator->errors()->add('assigned_to', 'The selected user does not have technician privileges.');
                    }
                }
            }

            // Validate category is appropriate for tickets
            if ($this->filled('category_id')) {
                $category = \App\Models\Category::find($this->category_id);
                if ($category && $category->type !== 'Ticket') {
                    $validator->errors()->add('category_id', 'The selected category is not valid for tickets.');
                }
            }

            // If scheduling, ensure assigned user is set
            if ($this->filled('scheduled_at') && ! $this->filled('assigned_to')) {
                $validator->errors()->add('assigned_to', 'An assigned technician is required when scheduling a ticket.');
            }

            // Validate watcher emails are unique
            if ($this->filled('watchers')) {
                $watchers = array_filter($this->watchers);
                if (count($watchers) !== count(array_unique($watchers))) {
                    $validator->errors()->add('watchers', 'Watcher email addresses must be unique.');
                }
            }

            // Validate status transitions (business logic)
            if ($this->filled('status') && $ticket) {
                $currentStatus = $ticket->status;
                $newStatus = $this->status;

                // Prevent reopening closed tickets without proper permissions
                if ($currentStatus === 'Closed' && $newStatus !== 'Closed') {
                    if (! $user->settings || $user->settings->role < 3) { // Manager level required
                        $validator->errors()->add('status', 'Only managers can reopen closed tickets.');
                    }
                }
            }
        });
    }
}
