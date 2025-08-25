<?php

/**
 * Financial Accuracy Test Runner
 * 
 * Comprehensive test runner for all financial accuracy and integrity tests
 * Ensures accounting calculations are always accurate and compliant
 * 
 * Usage: php tests/run-financial-accuracy-tests.php [options]
 * 
 * Options:
 *   --verbose     Show detailed test output
 *   --coverage    Generate code coverage report
 *   --filter=X    Run only tests matching pattern X
 *   --suite=X     Run only specific test suite (invoice|tax|billing|payment|audit|contract)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FinancialAccuracyTestCommand extends Command
{
    protected static $defaultName = 'test:financial-accuracy';

    protected function configure(): void
    {
        $this->setDescription('Run comprehensive financial accuracy tests')
            ->setHelp('This command runs all financial accuracy tests to ensure accounting integrity')
            ->addOption('verbose', 'v', InputOption::VALUE_NONE, 'Enable verbose output')
            ->addOption('coverage', 'c', InputOption::VALUE_NONE, 'Generate code coverage report')
            ->addOption('filter', 'f', InputOption::VALUE_REQUIRED, 'Filter tests by pattern')
            ->addOption('suite', 's', InputOption::VALUE_REQUIRED, 'Run specific test suite');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('🧮 Nestogy Financial Accuracy Test Suite');
        $io->text('Ensuring accounting calculations are always accurate and compliant');
        
        // Test suite configuration
        $testSuites = [
            'invoice' => [
                'name' => 'Invoice Calculation Tests',
                'path' => 'tests/Unit/Financial/CalculationAccuracy/InvoiceCalculationTest.php',
                'critical' => true,
                'description' => 'Validates invoice calculations, totals, and precision'
            ],
            'tax' => [
                'name' => 'VoIP Tax Precision Tests',
                'path' => 'tests/Unit/Financial/CalculationAccuracy/VoIPTaxPrecisionTest.php',
                'critical' => true,
                'description' => 'Ensures VoIP tax calculations comply with federal/state requirements'
            ],
            'billing' => [
                'name' => 'Recurring Billing Tests',
                'path' => 'tests/Unit/Financial/CalculationAccuracy/RecurringBillingAccuracyTest.php',
                'critical' => true,
                'description' => 'Validates recurring billing calculations and prorations'
            ],
            'contract' => [
                'name' => 'Contract Billing Tests',
                'path' => 'tests/Unit/Financial/CalculationAccuracy/ContractBillingAccuracyTest.php',
                'critical' => true,
                'description' => 'Ensures contract-based billing accuracy across all models'
            ],
            'payment' => [
                'name' => 'Payment Reconciliation Tests',
                'path' => 'tests/Unit/Financial/CalculationAccuracy/PaymentReconciliationTest.php',
                'critical' => true,
                'description' => 'Validates payment allocation and balance calculations'
            ],
            'audit' => [
                'name' => 'Financial Audit Trail Tests',
                'path' => 'tests/Unit/Financial/CalculationAccuracy/FinancialAuditTrailTest.php',
                'critical' => false,
                'description' => 'Ensures complete audit trail for compliance'
            ]
        ];

        $selectedSuite = $input->getOption('suite');
        $filter = $input->getOption('filter');
        $verbose = $input->getOption('verbose');
        $coverage = $input->getOption('coverage');

        // Filter test suites if specific suite requested
        if ($selectedSuite && isset($testSuites[$selectedSuite])) {
            $testSuites = [$selectedSuite => $testSuites[$selectedSuite]];
        } elseif ($selectedSuite) {
            $io->error("Unknown test suite: $selectedSuite");
            $io->text('Available suites: ' . implode(', ', array_keys($testSuites)));
            return Command::FAILURE;
        }

        $io->section('📋 Test Suite Overview');
        $table = [];
        foreach ($testSuites as $key => $suite) {
            $status = $suite['critical'] ? '🔴 Critical' : '🟡 Important';
            $table[] = [$key, $suite['name'], $status, $suite['description']];
        }
        
        $io->table(['Suite', 'Name', 'Priority', 'Description'], $table);

        // Build PHPUnit command
        $phpunitCmd = ['./vendor/bin/phpunit'];
        
        if ($verbose) {
            $phpunitCmd[] = '--verbose';
        }
        
        if ($coverage) {
            $phpunitCmd[] = '--coverage-html=storage/app/test-coverage/financial';
            $io->note('Code coverage will be generated in storage/app/test-coverage/financial/');
        }

        if ($filter) {
            $phpunitCmd[] = "--filter=$filter";
        }

        // Run tests
        $totalTests = 0;
        $failedTests = 0;
        $results = [];

        foreach ($testSuites as $key => $suite) {
            $io->section("🧪 Running {$suite['name']}");
            
            $suiteCmd = array_merge($phpunitCmd, [$suite['path']]);
            $cmdString = implode(' ', $suiteCmd);
            
            if ($verbose) {
                $io->text("Command: $cmdString");
            }

            // Execute test
            $output_lines = [];
            $return_code = 0;
            exec($cmdString . ' 2>&1', $output_lines, $return_code);
            
            $success = $return_code === 0;
            $results[$key] = [
                'suite' => $suite,
                'success' => $success,
                'output' => $output_lines,
                'critical' => $suite['critical']
            ];

            if ($success) {
                $io->success("✅ {$suite['name']} - PASSED");
            } else {
                $io->error("❌ {$suite['name']} - FAILED");
                $failedTests++;
                
                if ($verbose || $suite['critical']) {
                    $io->text("Error output:");
                    foreach ($output_lines as $line) {
                        $io->text("  $line");
                    }
                }
            }
            
            $totalTests++;
        }

        // Summary
        $io->section('📊 Test Results Summary');
        
        $successCount = $totalTests - $failedTests;
        $successRate = $totalTests > 0 ? ($successCount / $totalTests) * 100 : 0;
        
        $io->text([
            "Total Suites: $totalTests",
            "Passed: $successCount",
            "Failed: $failedTests",
            "Success Rate: " . round($successRate, 1) . "%"
        ]);

        // Critical failure analysis
        $criticalFailures = [];
        foreach ($results as $key => $result) {
            if (!$result['success'] && $result['critical']) {
                $criticalFailures[] = $result['suite']['name'];
            }
        }

        if (!empty($criticalFailures)) {
            $io->error('🚨 CRITICAL FINANCIAL ACCURACY FAILURES DETECTED:');
            foreach ($criticalFailures as $failure) {
                $io->text("  • $failure");
            }
            $io->text('');
            $io->text('❗ These failures indicate potential accounting accuracy issues.');
            $io->text('❗ DO NOT deploy to production until all critical tests pass.');
            $io->text('❗ Review failed tests and fix calculation errors immediately.');
            
            return Command::FAILURE;
        }

        if ($failedTests > 0) {
            $io->warning("⚠️  Some non-critical tests failed. Review and fix when possible.");
            return Command::FAILURE;
        }

        // All tests passed
        $io->success('🎉 ALL FINANCIAL ACCURACY TESTS PASSED!');
        $io->text([
            '',
            '✅ Invoice calculations are accurate',
            '✅ Tax calculations comply with regulations', 
            '✅ Recurring billing is precise',
            '✅ Contract billing handles all scenarios',
            '✅ Payment reconciliation maintains integrity',
            '✅ Audit trails are complete and accurate',
            '',
            '💰 Your accounting system maintains financial accuracy and compliance!',
            ''
        ]);

        if ($coverage) {
            $io->note('📈 Code coverage report available at: storage/app/test-coverage/financial/index.html');
        }

        return Command::SUCCESS;
    }
}

// Run the command if executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $application = new Application('Financial Accuracy Test Runner', '1.0.0');
    $application->add(new FinancialAccuracyTestCommand());
    $application->setDefaultCommand('test:financial-accuracy', true);
    
    try {
        $application->run();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}