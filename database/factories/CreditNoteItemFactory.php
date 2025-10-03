<?php

namespace Database\Factories;

use App\Models\CreditNoteItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreditNoteItemFactory extends Factory
{
    protected $model = CreditNoteItem::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
