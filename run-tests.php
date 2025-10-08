#!/usr/bin/env php
<?php

/**
 * Custom Test Runner
 * 
 * Runs each test file in its own process to avoid memory issues.
 * Generates code coverage by compiling individual .cov files.
 */

class TestRunner
{
    private array $results = [
        'passed' => 0,
        'failed' => 0,
        'errors' => 0,
        'skipped' => 0,
        'total' => 0,
    ];

    private array $failedTests = [];
    private array $errorTests = [];
    private string $baseDir;
    private string $coverageDir;
    private bool $withCoverage;

    public function __construct(bool $withCoverage = false)
    {
        $this->baseDir = __DIR__;
        $this->coverageDir = $this->baseDir . '/storage/coverage';
        $this->withCoverage = $withCoverage;

        if ($withCoverage) {
            if (!extension_loaded('pcov') && !extension_loaded('xdebug')) {
                echo "Warning: Neither PCOV nor Xdebug extensions are loaded.\n";
                echo "Coverage will not be collected. Install one of these extensions:\n";
                echo "  - PCOV (recommended): pecl install pcov\n";
                echo "  - Xdebug: pecl install xdebug\n\n";
                $this->withCoverage = false;
            } elseif (!is_dir($this->coverageDir)) {
                mkdir($this->coverageDir, 0755, true);
            }
        }
    }

    public function run(): int
    {
        $startTime = microtime(true);
        
        echo "Custom Test Runner - Running tests individually to manage memory\n";
        echo str_repeat("=", 80) . "\n\n";

        // Reset test database to avoid PostgreSQL type constraint issues
        $this->resetTestDatabase();
        
        // Run migrations once at the start
        $this->runMigrations();

        $testFiles = $this->findTestFiles();
        $totalFiles = count($testFiles);

        echo "Found {$totalFiles} test files\n";
        echo "Coverage: " . ($this->withCoverage ? 'ENABLED' : 'DISABLED') . "\n\n";

        foreach ($testFiles as $index => $testFile) {
            $fileNum = $index + 1;
            echo "[{$fileNum}/{$totalFiles}] Running: " . basename($testFile) . "... ";
            
            $this->runTestFile($testFile, $index);
            
            echo "\n";
        }

        echo "\n" . str_repeat("=", 80) . "\n";
        $this->printSummary();

        if ($this->withCoverage) {
            echo "\n" . str_repeat("=", 80) . "\n";
            $this->generateCoverageReport();
        }

        $duration = round(microtime(true) - $startTime, 2);
        echo "\nTotal execution time: {$duration}s\n";

        return $this->results['failed'] > 0 || $this->results['errors'] > 0 ? 1 : 0;
    }

    private function resetTestDatabase(): void
    {
        // Load phpunit.xml defaults
        $phpunitXml = $this->baseDir . '/phpunit.xml';
        if (file_exists($phpunitXml)) {
            $xml = simplexml_load_file($phpunitXml);
            foreach ($xml->php->env as $env) {
                $name = (string)$env['name'];
                $value = (string)$env['value'];
                if (!getenv($name)) {
                    putenv("{$name}={$value}");
                }
            }
        }

        $dbConnection = getenv('DB_CONNECTION') ?: 'pgsql';
        
        if ($dbConnection !== 'pgsql') {
            return;
        }

        echo "Resetting PostgreSQL test database...\n";

        $dbHost = getenv('DB_HOST') ?: '127.0.0.1';
        $dbPort = getenv('DB_PORT') ?: '5432';
        $dbName = getenv('DB_DATABASE') ?: 'nestogy_test';
        $dbUser = getenv('DB_USERNAME') ?: 'nestogy';
        $dbPassword = getenv('DB_PASSWORD') ?: 'nestogy_dev_pass';

        // Build PostgreSQL connection string
        $pgConnStr = "postgresql://{$dbUser}:{$dbPassword}@{$dbHost}:{$dbPort}/postgres";

        // Terminate all connections to test database
        $terminateCmd = sprintf(
            'psql %s -c %s 2>/dev/null',
            escapeshellarg($pgConnStr),
            escapeshellarg("SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '{$dbName}' AND pid <> pg_backend_pid();")
        );
        exec($terminateCmd);

        // Force drop database (WITH FORCE handles remaining connections)
        $dropCmd = sprintf(
            'psql %s -c %s 2>&1',
            escapeshellarg($pgConnStr),
            escapeshellarg("DROP DATABASE IF EXISTS {$dbName} WITH (FORCE);")
        );
        exec($dropCmd, $dropOutput, $dropCode);

        // Create fresh database
        $createCmd = sprintf(
            'psql %s -c %s 2>&1',
            escapeshellarg($pgConnStr),
            escapeshellarg("CREATE DATABASE {$dbName} OWNER {$dbUser};")
        );
        exec($createCmd, $createOutput, $createCode);

        if ($createCode !== 0) {
            echo "ERROR: Failed to create test database (code: {$createCode})\n";
            if (!empty($createOutput)) {
                echo "  " . implode("\n  ", $createOutput) . "\n";
            }
            exit(1);
        }

        echo "Test database reset complete\n";
    }

    private function runMigrations(): void
    {
        echo "Running migrations...\n";
        
        // Get DB settings from phpunit.xml env vars (already loaded in resetTestDatabase)
        $dbName = getenv('DB_DATABASE') ?: 'nestogy_test';
        
        // Run migrations with correct database environment variable
        $migrateCmd = "DB_DATABASE={$dbName} php {$this->baseDir}/artisan migrate --force 2>&1";
        exec($migrateCmd, $output, $code);
        
        if ($code !== 0) {
            echo "ERROR: Migrations failed (code: {$code})\n";
            echo "  " . implode("\n  ", array_slice($output, -10)) . "\n";
            exit(1);
        }
        
        echo "Migrations complete\n\n";
    }

    private function findTestFiles(): array
    {
        $testDirs = [
            $this->baseDir . '/tests/Unit',
            $this->baseDir . '/tests/Feature',
        ];

        $testFiles = [];
        
        foreach ($testDirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && str_ends_with($file->getFilename(), 'Test.php')) {
                    $testFiles[] = $file->getPathname();
                }
            }
        }

        sort($testFiles);
        return $testFiles;
    }

    private function runTestFile(string $testFile, int $index): void
    {
        $relativePath = str_replace($this->baseDir . '/', '', $testFile);
        
        $command = 'php -d memory_limit=512M vendor/bin/phpunit';
        
        if ($this->withCoverage) {
            $covFile = $this->coverageDir . '/test-' . $index . '.cov';
            $command .= ' --coverage-php=' . escapeshellarg($covFile);
        }
        
        // Don't use --no-output so we can parse the results
        $command .= ' --colors=never --no-progress ' . escapeshellarg($testFile) . ' 2>&1';

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        $outputStr = implode("\n", $output);

        // Parse output for results
        if (preg_match('/Tests:\s+(\d+)\s+passed/', $outputStr, $matches)) {
            $passed = (int)$matches[1];
            $this->results['passed'] += $passed;
            $this->results['total'] += $passed;
            echo "✓ PASSED ({$passed} tests)";
        } elseif (preg_match('/(\d+)\s+failed/', $outputStr, $matches)) {
            $failed = (int)$matches[1];
            $this->results['failed'] += $failed;
            $this->results['total'] += $failed;
            $this->failedTests[] = $relativePath;
            echo "✗ FAILED ({$failed} failures)";
        } elseif (preg_match('/(\d+)\s+error/', $outputStr, $matches)) {
            $errors = (int)$matches[1];
            $this->results['errors'] += $errors;
            $this->results['total'] += $errors;
            $this->errorTests[] = $relativePath;
            echo "E ERROR ({$errors} errors)";
        } elseif (preg_match('/OK \((\d+) test/', $outputStr, $matches)) {
            $passed = (int)$matches[1];
            $this->results['passed'] += $passed;
            $this->results['total'] += $passed;
            echo "✓ PASSED ({$passed} tests)";
        } elseif (preg_match('/No tests executed/', $outputStr)) {
            echo "⊘ SKIPPED (no tests)";
            $this->results['skipped']++;
        } elseif ($returnCode !== 0) {
            $this->results['errors']++;
            $this->results['total']++;
            $this->errorTests[] = $relativePath;
            echo "E ERROR (exit code: {$returnCode})";
        } else {
            echo "? UNKNOWN";
        }
    }

    private function printSummary(): void
    {
        echo "\nTest Summary:\n";
        echo "  Total Tests:  {$this->results['total']}\n";
        echo "  Passed:       {$this->results['passed']}\n";
        echo "  Failed:       {$this->results['failed']}\n";
        echo "  Errors:       {$this->results['errors']}\n";
        echo "  Skipped:      {$this->results['skipped']}\n";

        if (!empty($this->failedTests)) {
            echo "\nFailed Test Files:\n";
            foreach ($this->failedTests as $file) {
                echo "  - {$file}\n";
            }
        }

        if (!empty($this->errorTests)) {
            echo "\nTest Files with Errors:\n";
            foreach ($this->errorTests as $file) {
                echo "  - {$file}\n";
            }
        }

        $successRate = $this->results['total'] > 0 
            ? round(($this->results['passed'] / $this->results['total']) * 100, 2)
            : 0;

        echo "\nSuccess Rate: {$successRate}%\n";
    }

    private function generateCoverageReport(): void
    {
        if (!$this->withCoverage) {
            return;
        }

        $covFiles = glob($this->coverageDir . '/*.cov');
        
        if (empty($covFiles)) {
            echo "No coverage files found.\n";
            return;
        }

        echo "Found " . count($covFiles) . " coverage files\n";
        echo "Merging coverage data...\n";

        try {
            require_once $this->baseDir . '/vendor/autoload.php';
            
            $mergedCoverage = null;

            foreach ($covFiles as $index => $covFile) {
                if (!file_exists($covFile)) {
                    continue;
                }

                $coverage = include $covFile;

                if ($coverage instanceof \SebastianBergmann\CodeCoverage\CodeCoverage) {
                    if ($mergedCoverage === null) {
                        $mergedCoverage = $coverage;
                    } else {
                        $mergedCoverage->merge($coverage);
                    }
                }

                if (($index + 1) % 50 === 0) {
                    echo "  Merged " . ($index + 1) . "/" . count($covFiles) . " files...\n";
                }
            }

            if ($mergedCoverage === null) {
                echo "Failed to merge coverage data - no valid coverage objects found.\n";
                return;
            }

            echo "Generating clover report...\n";
            $cloverWriter = new \SebastianBergmann\CodeCoverage\Report\Clover();
            $cloverWriter->process($mergedCoverage, $this->baseDir . '/coverage.xml');

            if (file_exists($this->baseDir . '/coverage.xml')) {
                $fileSize = filesize($this->baseDir . '/coverage.xml');
                echo "✓ Coverage report generated: coverage.xml (" . round($fileSize / 1024, 2) . " KB)\n";
            } else {
                echo "Failed to generate coverage.xml file\n";
            }

            // Clean up individual coverage files
            echo "Cleaning up temporary coverage files...\n";
            foreach ($covFiles as $covFile) {
                if (file_exists($covFile)) {
                    unlink($covFile);
                }
            }
            
            if (is_dir($this->coverageDir)) {
                @rmdir($this->coverageDir);
            }

        } catch (\Exception $e) {
            echo "Error generating coverage report: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        }
    }
}

// Parse command line arguments
$withCoverage = in_array('--coverage', $argv) || in_array('-c', $argv);

$runner = new TestRunner($withCoverage);
exit($runner->run());
