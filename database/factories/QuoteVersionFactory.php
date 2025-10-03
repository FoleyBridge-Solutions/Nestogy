<?php

namespace Database\Factories;

use App\Models\QuoteVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteVersionFactory extends Factory
{
    protected $model = QuoteVersion::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'version_number' => null,
            'quote_data' => null,
            'changes' => null,
            'change_reason' => null,
            'created_by' => null
        ];
    }
}
