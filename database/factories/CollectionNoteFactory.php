<?php

namespace Database\Factories;

use App\Models\CollectionNote;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollectionNoteFactory extends Factory
{
    protected $model = CollectionNote::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
