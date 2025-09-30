<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization will be handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'client_id' => 'required|integer|exists:clients,id',
            'category_id' => 'required|integer|exists:categories,id',
            'prefix' => 'nullable|string|max:10',
            'scope' => 'nullable|string|max:255',
            'status' => 'required|in:Draft,Sent,Paid,Overdue,Cancelled',
            'date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:date',
            'discount_amount' => 'nullable|numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'note' => 'nullable|string',
            'ticket_id' => 'nullable|integer|exists:tickets,id',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'client_id.required' => 'Please select a client.',
            'client_id.exists' => 'The selected client is invalid.',
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'The selected category is invalid.',
            'date.required' => 'Invoice date is required.',
            'due.required' => 'Due date is required.',
            'due_date.required' => 'Due date is required.',
            'due_date.after_or_equal' => 'Due date must be on or after the invoice date.',
            'currency_code.required' => 'Currency is required.',
            'currency_code.size' => 'Currency code must be exactly 3 characters.',
            'status.in' => 'Invalid invoice status.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'client_id' => 'client',
            'category_id' => 'category',
            'currency_code' => 'currency',
            'due_date' => 'due date',
        ];
    }
}
