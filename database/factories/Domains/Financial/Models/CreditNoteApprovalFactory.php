<?php

namespace Database\Factories\Domains\Financial\Models;

use App\Domains\Financial\Models\CreditNoteApproval;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreditNoteApprovalFactory extends Factory
{
    protected $model = CreditNoteApproval::class;

    public function definition(): array
    {
        return ['company_id' => \App\Domains\Company\Models\Company::factory(),
        ];
    }
}
