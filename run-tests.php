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
        
        $command .= ' --no-output ' . escapeshellarg($testFile) . ' 2>&1';

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
