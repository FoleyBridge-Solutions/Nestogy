<?php

namespace Database\Factories\Domains\Collections\Models;

use App\Domains\Collections\Models\CollectionNote;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollectionNoteFactory extends Factory
{
    protected $model = CollectionNote::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'notes' => $this->faker->paragraph,
        ];
    }
}
