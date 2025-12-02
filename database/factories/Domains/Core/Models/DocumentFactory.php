<?php

namespace Database\Factories\Domains\Core\Models;

use App\Domains\Core\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'company_id' => 1, // Don't create new companies - use existing
            'documentable_type' => null,
            'documentable_id' => null,
            'type' => $this->faker->randomElement(['contract', 'proposal', 'invoice', 'report', 'agreement', 'policy', 'procedure']),
            'name' => $this->faker->words(3, true),
            'file_name' => $this->faker->word().'.'.$this->faker->randomElement(['pdf', 'docx', 'xlsx', 'txt']),
            'file_path' => $this->faker->filePath(),
            'file_size' => $this->faker->numberBetween(1024, 10485760),
            'mime_type' => $this->faker->randomElement(['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain']),
            'description' => $this->faker->optional()->sentence(),
            'uploaded_by' => User::factory(),
            'is_public' => $this->faker->boolean(30),
            'tags' => $this->faker->optional()->words(3),
        ];
    }
}
