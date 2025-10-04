<?php

namespace Database\Factories;

use App\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileFactory extends Factory
{
    protected $model = File::class;

    public function definition(): array
    {
        $extension = $this->faker->fileExtension;
        return [
            'company_id' => \App\Models\Company::factory(),
            'fileable_id' => 1,
            'fileable_type' => $this->faker->randomElement([\App\Models\Client::class, \App\Models\Ticket::class, \App\Models\Project::class]),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence,
            'file_path' => 'files/'.$this->faker->uuid.'.'.$extension,
            'file_name' => $this->faker->word.'.'.$extension,
            'original_name' => $this->faker->words(2, true).'.'.$extension,
            'file_size' => $this->faker->numberBetween(1024, 5242880),
            'mime_type' => $this->faker->randomElement(['application/pdf', 'image/jpeg', 'image/png', 'text/plain']),
            'file_type' => $this->faker->randomElement(['document', 'image', 'spreadsheet', 'other']),
            'is_public' => $this->faker->boolean(30),
            'uploaded_by' => \App\Models\User::factory(),
            'metadata' => json_encode([]),
        ];
    }
}
