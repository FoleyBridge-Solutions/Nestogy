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
            'fileable_type' => null,
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence,
            'file_path' => null,
            'file_name' => $this->faker->words(3, true),
            'original_name' => $this->faker->words(3, true),
            'file_size' => null,
            'mime_type' => null,
            'file_type' => null,
            'is_public' => true,
            'uploaded_by' => null,
            'metadata' => null
        ];
    }
}
