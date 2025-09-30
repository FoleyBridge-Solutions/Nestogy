<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Client;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class ModelTestCase extends TestCase
{
    use RefreshDatabase;

    protected Company $testCompany;

    protected Client $testClient;

    protected Category $testCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testCompany = Company::factory()->create();
        $this->testClient = Client::factory()->create(['company_id' => $this->testCompany->id]);
        $this->testCategory = Category::create([
            'name' => 'Income',
            'type' => 'income',
            'company_id' => $this->testCompany->id,
            'color' => '#28a745',
        ]);
    }
}