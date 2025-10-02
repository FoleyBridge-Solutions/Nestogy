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
        $categoryId = \Illuminate\Support\Facades\DB::table('expense_categories')->insertGetId([
            'name' => 'Expense',
            'company_id' => $company->id,
            'color' => '#dc3545',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $expense = Expense::factory()->create([
            'company_id' => $company->id,
            'category_id' => $categoryId,
        ]);

        $this->assertInstanceOf(Expense::class, $expense);
        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }

    public function test_expense_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $categoryId = \Illuminate\Support\Facades\DB::table('expense_categories')->insertGetId([
            'name' => 'Expense',
            'company_id' => $company->id,
            'color' => '#dc3545',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $expense = Expense::factory()->create([
            'company_id' => $company->id,
            'category_id' => $categoryId,
        ]);

        $this->assertInstanceOf(Company::class, $expense->company);
        $this->assertEquals($company->id, $expense->company->id);
    }

    public function test_expense_belongs_to_category(): void
    {
        $company = Company::factory()->create();
        $categoryId = \Illuminate\Support\Facades\DB::table('expense_categories')->insertGetId([
            'name' => 'Expense',
            'company_id' => $company->id,
            'color' => '#dc3545',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $expense = Expense::factory()->create([
            'company_id' => $company->id,
            'category_id' => $categoryId,
        ]);

        $this->assertNotNull($expense->category);
        $this->assertEquals($categoryId, $expense->category_id);
    }

    public function test_expense_has_amount_field(): void
    {
        $company = Company::factory()->create();
        $categoryId = \Illuminate\Support\Facades\DB::table('expense_categories')->insertGetId([
            'name' => 'Expense',
            'company_id' => $company->id,
            'color' => '#dc3545',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $expense = Expense::factory()->create([
            'company_id' => $company->id,
            'category_id' => $categoryId,
            'amount' => 250.50,
        ]);

        $this->assertEquals(250.50, $expense->amount);
    }

    public function test_expense_has_date_field(): void
    {
        $company = Company::factory()->create();
        $categoryId = \Illuminate\Support\Facades\DB::table('expense_categories')->insertGetId([
            'name' => 'Expense',
            'company_id' => $company->id,
            'color' => '#dc3545',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $expense = Expense::factory()->create([
            'company_id' => $company->id,
            'category_id' => $categoryId,
        ]);

        $this->assertNotNull($expense->expense_date);
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
        $categoryId = \Illuminate\Support\Facades\DB::table('expense_categories')->insertGetId([
            'name' => 'Expense',
            'company_id' => $company->id,
            'color' => '#dc3545',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $expense = Expense::factory()->create([
            'company_id' => $company->id,
            'category_id' => $categoryId,
        ]);

        $this->assertNotNull($expense->created_at);
        $this->assertNotNull($expense->updated_at);
    }
}