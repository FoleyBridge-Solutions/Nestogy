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
            'documentable_type' => null,
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence,
            'file_path' => null,
            'file_name' => $this->faker->words(3, true),
            'file_size' => null,
            'mime_type' => null,
            'category' => null,
            'is_private' => true,
            'uploaded_by' => null,
            'tags' => null
        ];
    }
}
