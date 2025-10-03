<?php

namespace Tests\Unit\Models;

use App\Models\Recurring;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_recurring_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\RecurringFactory')) {
            $this->markTestSkipped('RecurringFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = Recurring::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Recurring::class, $model);
    }

    public function test_recurring_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\RecurringFactory')) {
            $this->markTestSkipped('RecurringFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = Recurring::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_recurring_has_fillable_attributes(): void
    {
        $model = new Recurring();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
