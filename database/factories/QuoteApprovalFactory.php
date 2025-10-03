<?php

namespace Database\Factories;

use App\Models\QuoteApproval;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteApprovalFactory extends Factory
{
    protected $model = QuoteApproval::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'approval_level' => null,
            'status' => 'active',
            'comments' => null,
            'approved_at' => null,
            'rejected_at' => null
        ];
    }
}
