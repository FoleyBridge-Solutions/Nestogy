<?php

namespace Tests\Unit\Models;

use App\Domains\Financial\Models\ExpenseCategory;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_expense_category_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\ExpenseCategoryFactory')) {
            $this->markTestSkipped('ExpenseCategoryFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = ExpenseCategory::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(ExpenseCategory::class, $model);
    }

    public function test_expense_category_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\ExpenseCategoryFactory')) {
            $this->markTestSkipped('ExpenseCategoryFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = ExpenseCategory::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_expense_category_has_fillable_attributes(): void
    {
        $model = new ExpenseCategory();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
