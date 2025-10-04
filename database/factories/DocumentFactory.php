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
            'company_id' => \App\Models\Company::factory(),
            'documentable_id' => 1,
            'documentable_type' => $this->faker->randomElement([\App\Models\Client::class, \App\Models\Invoice::class, \App\Models\Project::class]),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence,
            'file_path' => 'documents/'.$this->faker->uuid.'.'.$this->faker->fileExtension,
            'file_name' => $this->faker->word.'.'.$this->faker->fileExtension,
            'file_size' => $this->faker->numberBetween(1024, 5242880),
            'mime_type' => $this->faker->randomElement(['application/pdf', 'image/jpeg', 'image/png', 'text/plain', 'application/msword']),
            'category' => $this->faker->randomElement(['contract', 'invoice', 'report', 'other']),
            'is_private' => $this->faker->boolean(40),
            'uploaded_by' => \App\Models\User::factory(),
            'tags' => json_encode([]),
        ];
    }
}
