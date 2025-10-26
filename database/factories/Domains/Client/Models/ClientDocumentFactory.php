<?php

namespace Database\Factories\Domains\Client\Models;

use App\Domains\Client\Models\ClientDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientDocumentFactory extends Factory
{
    protected $model = ClientDocument::class;

    public function definition(): array
    {
        $filename = $this->faker->uuid() . '.pdf';
        $path = 'documents/' . $filename;
        
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'client_id' => \App\Domains\Client\Models\Client::factory(),
            'name' => $this->faker->words(3, true),
            'type' => $this->faker->randomElement(['contract', 'invoice', 'report', 'other']),
            'file_path' => $path,
            'storage_path' => $path,
            'storage_disk' => 'local',
            'mime_type' => 'application/pdf',
            'file_size' => $this->faker->numberBetween(1000, 1000000),
            'description' => $this->faker->optional()->sentence,
            'tags' => '[]',
            'is_confidential' => false,
        ];
    }
}
