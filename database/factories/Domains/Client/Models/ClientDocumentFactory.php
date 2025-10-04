<?php

namespace Database\Factories\Domains\Client\Models;

use App\Domains\Client\Models\ClientDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientDocumentFactory extends Factory
{
    protected $model = ClientDocument::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'client_id' => \App\Models\Client::factory(),
            'name' => $this->faker->words(3, true),
            'type' => $this->faker->randomElement(['contract', 'invoice', 'receipt', 'agreement']),
            'file_path' => $this->faker->filePath(),
            'mime_type' => 'application/pdf',
            'file_size' => $this->faker->numberBetween(1000, 1000000),
            'description' => $this->faker->optional()->sentence(),
            'tags' => json_encode([$this->faker->word(), $this->faker->word()]),
            'is_confidential' => $this->faker->boolean(30),
        ];
    }
}
