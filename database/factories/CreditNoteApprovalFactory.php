<?php

namespace Database\Factories;

use App\Models\CreditNoteApproval;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreditNoteApprovalFactory extends Factory
{
    protected $model = CreditNoteApproval::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
