<?php

namespace Database\Factories\Domains\Financial\Models;

use App\Domains\Financial\Models\CreditNoteApproval;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreditNoteApprovalFactory extends Factory
{
    protected $model = CreditNoteApproval::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'credit_note_id' => \App\Domains\Financial\Models\CreditNote::factory(),
            'approver_id' => \App\Domains\Core\Models\User::factory(),
            'requested_by' => \App\Domains\Core\Models\User::factory(),
            'status' => 'pending',
            'approval_type' => 'standard',
        ];
    }
}
