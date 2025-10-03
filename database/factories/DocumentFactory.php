<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'documentable_type' => $this->faker->numberBetween(1, 5),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence,
            'file_path' => $this->faker->optional()->word,
            'file_name' => $this->faker->words(3, true),
            'file_size' => $this->faker->optional()->word,
            'mime_type' => $this->faker->numberBetween(1, 5),
            'category' => $this->faker->optional()->word,
            'is_private' => $this->faker->boolean(70),
            'uploaded_by' => $this->faker->optional()->word,
            'tags' => $this->faker->optional()->word
        ];
    }
}
