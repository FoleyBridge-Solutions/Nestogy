<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TestS3Connectivity extends Command
{
    protected $signature = 'nestogy:test-s3 {--detailed : Show detailed output}';
    protected $description = 'Test S3/DigitalOcean Spaces connectivity and configuration';

    public function handle()
    {
        $this->info('Testing S3/DigitalOcean Spaces connectivity...');
        $this->newLine();

        $verbose = $this->option('detailed');
        $allTestsPassed = true;

        // Test 1: Environment Configuration
        $this->info('1. Checking environment configuration...');
        
        $envVars = [
            'AWS_ACCESS_KEY_ID' => env('AWS_ACCESS_KEY_ID'),
            'AWS_SECRET_ACCESS_KEY' => env('AWS_SECRET_ACCESS_KEY'),
            'AWS_DEFAULT_REGION' => env('AWS_DEFAULT_REGION'),
            'AWS_BUCKET' => env('AWS_BUCKET'),
            'AWS_ENDPOINT' => env('AWS_ENDPOINT'),
            'AWS_URL' => env('AWS_URL'),
        ];

        foreach ($envVars as $key => $value) {
            if (empty($value)) {
                $this->error("   ❌ $key is not set");
                $allTestsPassed = false;
            } else {
                if ($verbose) {
                    $displayValue = in_array($key, ['AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY']) 
                        ? substr($value, 0, 8) . '...' 
                        : $value;
                    $this->info("   ✅ $key: $displayValue");
                } else {
                    $this->info("   ✅ $key is set");
                }
            }
        }

        // Check if keys are identical
        if (env('AWS_ACCESS_KEY_ID') === env('AWS_SECRET_ACCESS_KEY')) {
            $this->error('   ❌ AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY are identical!');
            $this->error('      DigitalOcean Spaces requires different access key and secret key values.');
            $allTestsPassed = false;
        } else {
            $this->info('   ✅ Access key and secret key are different');
        }

        $this->newLine();

        // Test 2: S3 Disk Configuration
        $this->info('2. Testing S3 disk configuration...');
        
        try {
            $s3Disk = Storage::disk('s3');
            $this->info('   ✅ S3 disk loaded successfully');
            
            if ($verbose) {
                $adapter = $s3Disk->getAdapter();
                $this->info('   ℹ️  Adapter: ' . get_class($adapter));
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Failed to load S3 disk: ' . $e->getMessage());
            $allTestsPassed = false;
            return 1;
        }

        $this->newLine();

        // Test 3: Write Test
        $this->info('3. Testing write operation...');
        
        $testFile = 'test/connectivity_test_' . time() . '.txt';
        $testContent = 'S3 connectivity test at ' . now()->toDateTimeString();

        try {
            $result = Storage::disk('s3')->put($testFile, $testContent);
            
            if ($result) {
                $this->info('   ✅ File written successfully');
            } else {
                $this->error('   ❌ File write returned false');
                $allTestsPassed = false;
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Write failed: ' . $e->getMessage());
            if ($verbose) {
                $this->error('   Exception: ' . get_class($e));
                if (method_exists($e, 'getAwsErrorCode')) {
                    $this->error('   AWS Error Code: ' . $e->getAwsErrorCode());
                }
            }
            $allTestsPassed = false;
        }

        $this->newLine();

        // Test 4: Read Test
        $this->info('4. Testing read operation...');
        
        try {
            if (Storage::disk('s3')->exists($testFile)) {
                $this->info('   ✅ File exists in S3');
                
                $readContent = Storage::disk('s3')->get($testFile);
                
                if (strlen($readContent) > 0) {
                    $this->info('   ✅ File read successfully (' . strlen($readContent) . ' bytes)');
                    
                    if ($readContent === $testContent) {
                        $this->info('   ✅ Content integrity verified');
                    } else {
                        $this->error('   ❌ Content mismatch detected');
                        $allTestsPassed = false;
                    }
                } else {
                    $this->error('   ❌ File read but content is empty');
                    $allTestsPassed = false;
                }
            } else {
                $this->error('   ❌ File does not exist in S3');
                $allTestsPassed = false;
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Read failed: ' . $e->getMessage());
            $allTestsPassed = false;
        }

        $this->newLine();

        // Test 5: List Test
        $this->info('5. Testing list operation...');
        
        try {
            $files = Storage::disk('s3')->files('test');
            $this->info('   ✅ Listed ' . count($files) . ' files in test directory');
        } catch (\Exception $e) {
            $this->error('   ❌ List failed: ' . $e->getMessage());
            $allTestsPassed = false;
        }

        $this->newLine();

        // Test 6: PDF Storage Test (simulating actual usage)
        $this->info('6. Testing PDF storage (real-world simulation)...');
        
        $pdfPath = 'pdfs/test_contract_' . time() . '.pdf';
        $fakePdfContent = '%PDF-1.4' . str_repeat("\nTest PDF content line", 100);
        
        try {
            Storage::put($pdfPath, $fakePdfContent); // Uses default disk (should be s3)
            $this->info('   ✅ PDF stored using default Storage facade');
            
            $retrievedSize = strlen(Storage::get($pdfPath));
            $this->info("   ✅ PDF retrieved successfully ($retrievedSize bytes)");
            
            // Cleanup
            Storage::delete($pdfPath);
            $this->info('   ✅ PDF cleaned up');
            
        } catch (\Exception $e) {
            $this->error('   ❌ PDF storage test failed: ' . $e->getMessage());
            $allTestsPassed = false;
        }

        $this->newLine();

        // Cleanup
        $this->info('7. Cleaning up test files...');
        try {
            if (Storage::disk('s3')->exists($testFile)) {
                Storage::disk('s3')->delete($testFile);
                $this->info('   ✅ Test file cleaned up');
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Cleanup failed: ' . $e->getMessage());
        }

        $this->newLine();

        // Final result
        if ($allTestsPassed) {
            $this->info('🎉 All S3 connectivity tests passed!');
            $this->info('Your S3/DigitalOcean Spaces configuration is working correctly.');
            return 0;
        } else {
            $this->error('❌ Some tests failed. Please check the issues above.');
            $this->newLine();
            $this->info('Common solutions:');
            $this->info('1. Verify your DigitalOcean Spaces credentials');
            $this->info('2. Ensure AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY are different');
            $this->info('3. Check that your bucket name and region are correct');
            $this->info('4. Verify your Spaces bucket exists and has proper permissions');
            return 1;
        }
    }
}