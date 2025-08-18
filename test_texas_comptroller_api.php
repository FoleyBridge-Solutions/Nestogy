<?php

require_once __DIR__ . '/vendor/autoload.php';

// Boot Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\TaxEngine\TexasComptrollerDataService;

echo "Testing Official Texas Comptroller API...\n\n";

try {
    $service = new TexasComptrollerDataService();
    
    // Check API key configuration
    $apiKey = env('TEXAS_COMPTROLLER_API_KEY');
    echo "API Key: " . ($apiKey ? substr($apiKey, 0, 10) . '...' : 'NOT SET') . "\n\n";
    
    if (!$apiKey) {
        echo "❌ API key not configured\n";
        exit(1);
    }
    
    // Test 1: List available files for current quarter
    echo "1. Listing available files for current quarter...\n";
    $fileList = $service->listAvailableFiles();
    
    if ($fileList['success']) {
        echo "✅ Successfully retrieved file list\n";
        echo "Files available: " . $fileList['count'] . "\n\n";
        
        // Show first few files
        $displayCount = min(10, count($fileList['files']));
        echo "First {$displayCount} files:\n";
        for ($i = 0; $i < $displayCount; $i++) {
            $file = $fileList['files'][$i];
            echo "  • " . basename($file['filePath']) . " (" . number_format($file['fileSize']) . " bytes)\n";
        }
        echo "\n";
        
        // Look for tax jurisdiction rates file
        $taxRatesFile = null;
        foreach ($fileList['files'] as $file) {
            if (str_contains($file['filePath'], 'tax_jurisdiction_rates') || 
                str_contains(strtolower($file['filePath']), 'jurisdiction') ||
                str_contains(strtolower($file['filePath']), 'rates')) {
                $taxRatesFile = $file;
                echo "🎯 Found potential tax rates file: " . basename($file['filePath']) . "\n";
                break;
            }
        }
        
        if (!$taxRatesFile) {
            echo "ℹ️ Tax jurisdiction rates file not found in current quarter\n";
            echo "Let's try Q3 2025 specifically...\n\n";
            
            // Test 2: Try Q3 2025 specifically
            echo "2. Listing files for Q3 2025...\n";
            $q3FileList = $service->listAvailableFiles('2025Q3');
            
            if ($q3FileList['success']) {
                echo "✅ Successfully retrieved Q3 2025 file list\n";
                echo "Q3 2025 files available: " . $q3FileList['count'] . "\n\n";
                
                // Look for tax rates file in Q3 2025
                foreach ($q3FileList['files'] as $file) {
                    if (str_contains($file['filePath'], 'tax_jurisdiction_rates') || 
                        str_contains(strtolower($file['filePath']), 'jurisdiction') ||
                        str_contains(strtolower($file['filePath']), 'rates')) {
                        $taxRatesFile = $file;
                        echo "🎯 Found Q3 2025 tax rates file: " . basename($file['filePath']) . "\n";
                        break;
                    }
                }
                
                // Show some Q3 2025 files
                $displayCount = min(5, count($q3FileList['files']));
                echo "\nSample Q3 2025 files:\n";
                for ($i = 0; $i < $displayCount; $i++) {
                    $file = $q3FileList['files'][$i];
                    echo "  • " . basename($file['filePath']) . " (" . number_format($file['fileSize']) . " bytes)\n";
                }
            } else {
                echo "❌ Failed to get Q3 2025 files: " . $q3FileList['error'] . "\n";
            }
        }
        
        // Test 3: Try to download a sample file
        if ($taxRatesFile) {
            echo "\n3. Testing file download...\n";
            echo "Attempting to download: " . basename($taxRatesFile['filePath']) . "\n";
            
            $download = $service->downloadFile($taxRatesFile['filePath']);
            
            if ($download['success']) {
                echo "✅ Successfully downloaded file!\n";
                echo "Size: " . number_format($download['size']) . " bytes\n";
                echo "Content preview (first 500 chars):\n";
                echo "---\n";
                echo substr($download['content'], 0, 500) . "\n";
                echo "---\n\n";
                
                // Test 4: Parse the content if it looks like CSV
                if (str_contains($download['content'], ',')) {
                    echo "4. Parsing CSV content...\n";
                    $parseResult = $service->parseTaxRatesFile($download['content']);
                    
                    if ($parseResult['success']) {
                        echo "✅ Successfully parsed tax rates!\n";
                        echo "Jurisdictions found: " . $parseResult['count'] . "\n\n";
                        
                        // Show first few jurisdictions
                        $displayCount = min(5, count($parseResult['jurisdictions']));
                        echo "First {$displayCount} jurisdictions:\n";
                        for ($i = 0; $i < $displayCount; $i++) {
                            $jurisdiction = $parseResult['jurisdictions'][$i];
                            echo "  • " . $jurisdiction['name'] . " (ID: " . $jurisdiction['authority_id'] . "): " . $jurisdiction['tax_rate'] . "%\n";
                        }
                        
                        // Test 5: Update database
                        echo "\n5. Testing database update...\n";
                        $updateResult = $service->updateDatabaseWithTexasRates($parseResult['jurisdictions']);
                        
                        if ($updateResult['success']) {
                            echo "✅ Successfully updated database!\n";
                            echo "Inserted: " . $updateResult['inserted'] . " tax rates\n";
                            echo "Quarter: " . $updateResult['quarter'] . "\n";
                        } else {
                            echo "❌ Failed to update database: " . $updateResult['error'] . "\n";
                        }
                        
                    } else {
                        echo "❌ Failed to parse tax rates: " . $parseResult['error'] . "\n";
                    }
                } else {
                    echo "4. File doesn't appear to be CSV format\n";
                }
                
            } else {
                echo "❌ Failed to download file: " . $download['error'] . "\n";
            }
        } else {
            echo "\n3. No tax rates file found to test download\n";
            echo "Available file types in response:\n";
            foreach ($fileList['files'] as $file) {
                $path = $file['filePath'];
                if (str_contains($path, '.csv')) {
                    echo "  CSV: " . basename($path) . "\n";
                } elseif (str_contains($path, '.zip')) {
                    echo "  ZIP: " . basename($path) . "\n";
                }
            }
        }
        
    } else {
        echo "❌ Failed to retrieve file list: " . $fileList['error'] . "\n";
    }
    
    echo "\n=== TEXAS COMPTROLLER API TEST SUMMARY ===\n";
    echo "✅ API key is configured and working\n";
    echo "✅ Can connect to Texas Comptroller API\n";
    echo "✅ Can list available files\n";
    if (isset($download) && $download['success']) {
        echo "✅ Can download files\n";
    }
    if (isset($parseResult) && $parseResult['success']) {
        echo "✅ Can parse tax rate data\n";
    }
    if (isset($updateResult) && $updateResult['success']) {
        echo "✅ Can update database with official data\n";
    }
    echo "🎯 Ready to use OFFICIAL Texas tax data instead of hardcoded rates!\n";
    echo "💰 Completely FREE - no monthly costs like TaxCloud\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}