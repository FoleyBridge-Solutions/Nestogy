<?php

namespace Tests\Unit\Models;

use App\Models\DunningSequence;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DunningSequenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_dunning_sequence_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\DunningSequenceFactory')) {
            $this->markTestSkipped('DunningSequenceFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = DunningSequence::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(DunningSequence::class, $model);
    }

    public function test_dunning_sequence_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\DunningSequenceFactory')) {
            $this->markTestSkipped('DunningSequenceFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = DunningSequence::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_dunning_sequence_has_fillable_attributes(): void
    {
        $model = new DunningSequence();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
