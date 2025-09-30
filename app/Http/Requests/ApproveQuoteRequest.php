<?php

namespace App\Http\Requests;

use App\Models\Quote;
use App\Models\QuoteApproval;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * ApproveQuoteRequest
 *
 * Validation rules for processing quote approvals in the multi-tier
 * approval workflow system.
 */
class ApproveQuoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        $quote = $this->route('quote');

        // User must have approval permissions
        if (! $user->hasPermission('financial.quotes.approve')) {
            return false;
        }

        // Quote must belong to user's company
        if ($quote && $quote->company_id !== $user->company_id) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'level' => [
                'required',
                'string',
                'in:'.implode(',', QuoteApproval::getAvailableLevels()),
                function ($attribute, $value, $fail) {
                    $quote = $this->route('quote');
                    $user = Auth::user();

                    if (! $quote) {
                        $fail('Quote not found.');

                        return;
                    }

                    // Check if user has permission to approve at this level
                    if (! $this->canApproveAtLevel($user, $value, $quote)) {
                        $fail("You don't have permission to approve at the {$value} level.");

                        return;
                    }

                    // Check if approval at this level exists and is pending
                    $approval = QuoteApproval::where('quote_id', $quote->id)
                        ->where('approval_level', $value)
                        ->first();

                    if (! $approval) {
                        $fail("No approval required at the {$value} level for this quote.");

                        return;
                    }

                    if ($approval->status !== QuoteApproval::STATUS_PENDING) {
                        $fail("The {$value} level approval has already been processed.");
                    }
                },
            ],
            'action' => [
                'required',
                'string',
                'in:approve,reject',
            ],
            'comments' => [
                'nullable',
                'string',
                'max:1000',
                function ($attribute, $value, $fail) {
                    // Comments are required when rejecting
                    if ($this->action === 'reject' && empty($value)) {
                        $fail('Comments are required when rejecting a quote.');
                    }
                },
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'level' => 'approval level',
            'action' => 'approval action',
            'comments' => 'approval comments',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'level.required' => 'The approval level is required.',
            'level.in' => 'The selected approval level is invalid.',
            'action.required' => 'The approval action is required.',
            'action.in' => 'The approval action must be either approve or reject.',
            'comments.max' => 'Comments cannot exceed 1000 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $quote = $this->route('quote');
            $user = Auth::user();

            if (! $quote) {
                return;
            }

            // Validate quote is in correct state for approval
            if (! $quote->needsApproval()) {
                $validator->errors()->add('quote', 'This quote does not require approval.');

                return;
            }

            // Validate approval workflow prerequisites
            $level = $this->level;
            if ($level === QuoteApproval::LEVEL_EXECUTIVE) {
                // Executive approval requires manager approval first
                $managerApproval = QuoteApproval::where('quote_id', $quote->id)
                    ->where('approval_level', QuoteApproval::LEVEL_MANAGER)
                    ->where('status', QuoteApproval::STATUS_APPROVED)
                    ->first();

                if (! $managerApproval) {
                    $validator->errors()->add('level',
                        'Manager approval is required before executive approval.');
                }
            }

            // Validate user hasn't already processed this approval
            $existingApproval = QuoteApproval::where('quote_id', $quote->id)
                ->where('approval_level', $level)
                ->where('user_id', $user->id)
                ->first();

            if ($existingApproval && $existingApproval->status !== QuoteApproval::STATUS_PENDING) {
                $validator->errors()->add('level',
                    'You have already processed the approval for this level.');
            }
        });
    }

    /**
     * Check if user can approve at the specified level.
     */
    private function canApproveAtLevel($user, string $level, Quote $quote): bool
    {
        // This would typically check user roles, permissions, and company hierarchy
        // For now, we'll implement basic permission checks

        switch ($level) {
            case QuoteApproval::LEVEL_MANAGER:
                return $user->hasRole('manager') || $user->hasRole('admin') || $user->hasRole('executive');

            case QuoteApproval::LEVEL_EXECUTIVE:
                return $user->hasRole('executive') || $user->hasRole('admin');

            case QuoteApproval::LEVEL_FINANCE:
                return $user->hasRole('finance') || $user->hasRole('admin') || $user->hasRole('executive');

            default:
                return false;
        }
    }

    /**
     * Get the validated approval data.
     */
    public function getApprovalData(): array
    {
        $validated = $this->validated();

        return [
            'level' => $validated['level'],
            'action' => $validated['action'],
            'comments' => $validated['comments'] ?? null,
            'user_id' => Auth::id(),
            'processed_at' => now(),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Trim whitespace from comments
        if ($this->comments) {
            $this->merge([
                'comments' => trim($this->comments),
            ]);
        }
    }
}
