#!/usr/bin/env php
<?php

$testFiles = glob(__DIR__ . '/../tests/Unit/Models/*Test.php');

$newTemplate = <<<'PHP'
<?php

namespace Tests\Unit\Models;

use %MODEL_CLASS%;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class %MODEL_NAME%Test extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_%SNAKE_NAME%_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\%MODEL_NAME%Factory')) {
            $this->markTestSkipped('%MODEL_NAME%Factory does not exist');
        }

        $company = Company::factory()->create();
        $model = %MODEL_NAME%::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(%MODEL_NAME%::class, $model);
    }

    public function test_%SNAKE_NAME%_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\%MODEL_NAME%Factory')) {
            $this->markTestSkipped('%MODEL_NAME%Factory does not exist');
        }

        $company = Company::factory()->create();
        $model = %MODEL_NAME%::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_%SNAKE_NAME%_has_fillable_attributes(): void
    {
        $model = new %MODEL_NAME%();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}

PHP;

foreach ($testFiles as $testFile) {
    $modelName = str_replace('Test.php', '', basename($testFile));
    $snakeCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $modelName));
    
    // Check if model exists in App\Models or a Domain
    $modelClass = "App\\Models\\$modelName";
    if (!class_exists($modelClass)) {
        // Try to find in domains
        $domainDirs = glob(__DIR__ . '/../app/Domains/*/Models');
        foreach ($domainDirs as $dir) {
            $domainModelPath = $dir . '/' . $modelName . '.php';
            if (file_exists($domainModelPath)) {
                $domainName = basename(dirname($dir));
                $modelClass = "App\\Domains\\$domainName\\Models\\$modelName";
                break;
            }
        }
    }
    
    $content = str_replace(
        ['%MODEL_CLASS%', '%MODEL_NAME%', '%SNAKE_NAME%'],
        [$modelClass, $modelName, $snakeCase],
        $newTemplate
    );
    
    file_put_contents($testFile, $content);
    echo "Updated $modelName\n";
}

echo "\nUpdated " . count($testFiles) . " test files!\n";
