<?php

namespace Database\Factories;

use App\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileFactory extends Factory
{
    protected $model = File::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'fileable_type' => $this->faker->numberBetween(1, 5),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence,
            'file_path' => $this->faker->optional()->word,
            'file_name' => $this->faker->words(3, true),
            'original_name' => $this->faker->words(3, true),
            'file_size' => $this->faker->optional()->word,
            'mime_type' => $this->faker->numberBetween(1, 5),
            'file_type' => $this->faker->numberBetween(1, 5),
            'is_public' => $this->faker->boolean(70),
            'uploaded_by' => $this->faker->optional()->word,
            'metadata' => $this->faker->optional()->word
        ];
    }
}
