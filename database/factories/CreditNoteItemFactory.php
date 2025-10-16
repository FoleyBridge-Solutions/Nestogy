<?php

namespace Database\Factories;

use App\Domains\Financial\Models\CreditNoteItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreditNoteItemFactory extends Factory
{
    protected $model = CreditNoteItem::class;

    public function definition(): array
    {
        return ['company_id' => \App\Models\Company::factory(),
        ];
    }
}
