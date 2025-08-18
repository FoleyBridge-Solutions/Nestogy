<?php

require_once __DIR__ . '/vendor/autoload.php';

// Boot Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\TaxEngine\TexasComptrollerDataService;

echo "Downloading Texas Comptroller Address Dataset Files...\n";
echo "These files map addresses to tax jurisdictions for accurate tax calculation\n\n";

try {
    $service = new TexasComptrollerDataService();
    
    // Check API key configuration
    $apiKey = env('TEXAS_COMPTROLLER_API_KEY');
    echo "API Key: " . ($apiKey ? substr($apiKey, 0, 10) . '...' : 'NOT SET') . "\n\n";
    
    if (!$apiKey) {
        throw new Exception('Texas Comptroller API key not configured');
    }
    
    // List available files for Q3 2025
    echo "1. Listing available files for Q3 2025...\n";
    $fileList = $service->listAvailableFiles('2025Q3');
    
    if (!$fileList['success']) {
        throw new Exception('Failed to list files: ' . $fileList['error']);
    }
    
    echo "✅ Found " . $fileList['count'] . " files for Q3 2025\n\n";
    
    // Look for address dataset files
    $addressFiles = [];
    $taxRatesFiles = [];
    
    foreach ($fileList['files'] as $file) {
        $filePath = strtolower($file['filePath']);
        $fileName = basename($file['filePath']);
        
        if (str_contains($filePath, 'address') && str_contains($filePath, '.zip')) {
            $addressFiles[] = $file;
        } elseif (str_contains($filePath, 'tax_jurisdiction_rates')) {
            $taxRatesFiles[] = $file;
        }
    }
    
    echo "📁 Found " . count($addressFiles) . " address dataset files\n";
    echo "📊 Found " . count($taxRatesFiles) . " tax rates files\n\n";
    
    // Show available address files
    if (!empty($addressFiles)) {
        echo "Available address dataset files:\n";
        foreach ($addressFiles as $file) {
            $fileName = basename($file['filePath']);
            $sizeKB = round($file['fileSize'] / 1024, 2);
            echo "  • {$fileName} ({$sizeKB} KB)\n";
        }
        echo "\n";
        
        // Download the first few address files (they're typically by county/MSA)
        $downloadCount = min(3, count($addressFiles)); // Limit to 3 files for now
        
        echo "2. Downloading first {$downloadCount} address dataset files...\n\n";
        
        $downloadedFiles = [];
        
        for ($i = 0; $i < $downloadCount; $i++) {
            $file = $addressFiles[$i];
            $fileName = basename($file['filePath']);
            
            echo "Downloading: {$fileName}...\n";
            
            $download = $service->downloadFile($file['filePath']);
            
            if ($download['success']) {
                echo "✅ Downloaded {$fileName} ({$download['size']} bytes)\n";
                
                // Save to local file
                $localPath = __DIR__ . "/texas_address_data/{$fileName}";
                
                // Create directory if it doesn't exist
                if (!is_dir(dirname($localPath))) {
                    mkdir(dirname($localPath), 0755, true);
                }
                
                file_put_contents($localPath, $download['content']);
                echo "💾 Saved to: {$localPath}\n";
                
                $downloadedFiles[] = [
                    'file_info' => $file,
                    'local_path' => $localPath,
                    'size' => $download['size']
                ];
                
                // Check if it's a ZIP file
                if (str_ends_with($fileName, '.zip')) {
                    echo "📦 ZIP file detected - will need to extract contents\n";
                    
                    // Try to list ZIP contents
                    $zip = new ZipArchive();
                    if ($zip->open($localPath) === TRUE) {
                        echo "ZIP contents:\n";
                        for ($j = 0; $j < $zip->numFiles; $j++) {
                            $stat = $zip->statIndex($j);
                            echo "  - " . $stat['name'] . " (" . number_format($stat['size']) . " bytes)\n";
                        }
                        $zip->close();
                    }
                }
                
                echo "\n";
                
            } else {
                echo "❌ Failed to download {$fileName}: " . $download['error'] . "\n\n";
            }
        }
        
        // Summary of downloaded files
        echo "=== DOWNLOAD SUMMARY ===\n";
        echo "Successfully downloaded " . count($downloadedFiles) . " address dataset files\n";
        $totalSize = array_sum(array_column($downloadedFiles, 'size'));
        echo "Total size: " . number_format($totalSize) . " bytes (" . round($totalSize / 1024 / 1024, 2) . " MB)\n";
        echo "Storage location: " . __DIR__ . "/texas_address_data/\n\n";
        
        foreach ($downloadedFiles as $file) {
            echo "📁 " . basename($file['local_path']) . " - " . number_format($file['size']) . " bytes\n";
        }
        
        echo "\n🎯 Next steps:\n";
        echo "1. Extract ZIP files to access CSV data\n";
        echo "2. Parse address-to-jurisdiction mappings\n";
        echo "3. Import into database for fast lookups\n";
        echo "4. Update tax calculation to use address mapping\n";
        
    } else {
        echo "⚠️ No address dataset files found\n";
        echo "Available file types:\n";
        
        $fileTypes = [];
        foreach ($fileList['files'] as $file) {
            $ext = pathinfo($file['filePath'], PATHINFO_EXTENSION);
            $fileTypes[$ext] = ($fileTypes[$ext] ?? 0) + 1;
        }
        
        foreach ($fileTypes as $ext => $count) {
            echo "  • .{$ext}: {$count} files\n";
        }
        
        echo "\nSample files:\n";
        $sampleCount = min(10, count($fileList['files']));
        for ($i = 0; $i < $sampleCount; $i++) {
            $file = $fileList['files'][$i];
            echo "  • " . basename($file['filePath']) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}