<?php

namespace Database\Factories\Domains\Financial\Models;

use App\Domains\Financial\Models\CreditNoteItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreditNoteItemFactory extends Factory
{
    protected $model = CreditNoteItem::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'credit_note_id' => \App\Domains\Financial\Models\CreditNote::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence,
            'item_type' => 'product',
            'quantity' => 1,
            'unit_price' => $this->faker->randomFloat(2, 10, 1000),
            'line_total' => $this->faker->randomFloat(2, 10, 1000),
        ];
    }
}
