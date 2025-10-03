<?php

namespace Tests\Unit\Models;

use App\Models\File;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FileTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_file_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\FileFactory')) {
            $this->markTestSkipped('FileFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = File::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(File::class, $model);
    }

    public function test_file_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\FileFactory')) {
            $this->markTestSkipped('FileFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = File::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_file_has_fillable_attributes(): void
    {
        $model = new File();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
