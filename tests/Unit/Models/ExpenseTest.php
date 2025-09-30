<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Company;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_expense_with_factory(): void
    {
        $company = Company::factory()->create();
        $category = Category::create([
            'name' => 'Expense',
            'type' => 'expense',
            'company_id' => $company->id,
            'color' => '#dc3545',
        ]);

        $expense = Expense::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
        ]);

        $this->assertInstanceOf(Expense::class, $expense);
        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }

    public function test_expense_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $category = Category::create([
            'name' => 'Expense',
            'type' => 'expense',
            'company_id' => $company->id,
            'color' => '#dc3545',
        ]);

        $expense = Expense::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
        ]);

        $this->assertInstanceOf(Company::class, $expense->company);
        $this->assertEquals($company->id, $expense->company->id);
    }

    public function test_expense_belongs_to_category(): void
    {
        $company = Company::factory()->create();
        $category = Category::create([
            'name' => 'Expense',
            'type' => 'expense',
            'company_id' => $company->id,
            'color' => '#dc3545',
        ]);

        $expense = Expense::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
        ]);

        $this->assertInstanceOf(Category::class, $expense->category);
        $this->assertEquals($category->id, $expense->category->id);
    }

    public function test_expense_has_amount_field(): void
    {
        $company = Company::factory()->create();
        $category = Category::create([
            'name' => 'Expense',
            'type' => 'expense',
            'company_id' => $company->id,
            'color' => '#dc3545',
        ]);

        $expense = Expense::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'amount' => 250.50,
        ]);

        $this->assertEquals(250.50, $expense->amount);
    }

    public function test_expense_has_date_field(): void
    {
        $company = Company::factory()->create();
        $category = Category::create([
            'name' => 'Expense',
            'type' => 'expense',
            'company_id' => $company->id,
            'color' => '#dc3545',
        ]);

        $expense = Expense::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
        ]);

        $this->assertNotNull($expense->date);
    }

    public function test_expense_has_fillable_attributes(): void
    {
        $fillable = (new Expense)->getFillable();

        $expectedFillable = ['company_id', 'category_id', 'amount', 'date'];
        
        foreach ($expectedFillable as $attribute) {
            $this->assertContains($attribute, $fillable);
        }
    }

    public function test_expense_has_timestamps(): void
    {
        $company = Company::factory()->create();
        $category = Category::create([
            'name' => 'Expense',
            'type' => 'expense',
            'company_id' => $company->id,
            'color' => '#dc3545',
        ]);

        $expense = Expense::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
        ]);

        $this->assertNotNull($expense->created_at);
        $this->assertNotNull($expense->updated_at);
    }
}