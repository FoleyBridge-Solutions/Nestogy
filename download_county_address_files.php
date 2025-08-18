<?php

require_once __DIR__ . '/vendor/autoload.php';

// Boot Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\TaxEngine\TexasComptrollerDataService;

echo "Downloading Texas County Address Files...\n";
echo "Starting with smaller counties to build address-to-jurisdiction mapping\n\n";

try {
    $service = new TexasComptrollerDataService();
    
    // Target specific smaller counties for initial testing
    $targetCounties = [
        'TX-County-FIPS-261-2025Q3.zip', // ~5KB
        'TX-County-FIPS-301-2025Q3.zip', // ~6KB  
        'TX-County-FIPS-431-2025Q3.zip', // ~15KB
        'TX-County-FIPS-393-2025Q3.zip', // ~16KB
        'TX-County-FIPS-345-2025Q3.zip', // ~17KB
    ];
    
    // List files to get full paths
    $fileList = $service->listAvailableFiles('2025Q3');
    
    if (!$fileList['success']) {
        throw new Exception('Failed to list files: ' . $fileList['error']);
    }
    
    $downloadedFiles = [];
    $totalDownloaded = 0;
    
    foreach ($targetCounties as $targetFile) {
        // Find the full path for this county file
        $countyFile = null;
        foreach ($fileList['files'] as $file) {
            if (str_ends_with($file['filePath'], $targetFile)) {
                $countyFile = $file;
                break;
            }
        }
        
        if (!$countyFile) {
            echo "⚠️ County file not found: {$targetFile}\n";
            continue;
        }
        
        $fileName = basename($countyFile['filePath']);
        $sizeKB = round($countyFile['fileSize'] / 1024, 2);
        
        echo "Downloading: {$fileName} ({$sizeKB} KB)...\n";
        
        $download = $service->downloadFile($countyFile['filePath']);
        
        if ($download['success']) {
            echo "✅ Downloaded {$fileName} ({$download['size']} bytes)\n";
            
            // Create directory if it doesn't exist
            $countyDir = __DIR__ . "/texas_counties/";
            if (!is_dir($countyDir)) {
                mkdir($countyDir, 0755, true);
            }
            
            // Save ZIP file
            $zipPath = $countyDir . $fileName;
            file_put_contents($zipPath, $download['content']);
            echo "💾 Saved ZIP to: {$zipPath}\n";
            
            // Extract ZIP contents
            $extractDir = $countyDir . pathinfo($fileName, PATHINFO_FILENAME) . '/';
            $zip = new ZipArchive();
            
            if ($zip->open($zipPath) === TRUE) {
                if (!is_dir($extractDir)) {
                    mkdir($extractDir, 0755, true);
                }
                
                $zip->extractTo($extractDir);
                echo "📦 Extracted to: {$extractDir}\n";
                
                // List extracted files
                echo "Extracted files:\n";
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $stat = $zip->statIndex($i);
                    $extractedPath = $extractDir . $stat['name'];
                    echo "  • " . $stat['name'] . " (" . number_format($stat['size']) . " bytes)\n";
                    
                    // If it's a CSV file, show first few lines
                    if (str_ends_with($stat['name'], '.csv') && file_exists($extractedPath)) {
                        echo "    Preview:\n";
                        $lines = array_slice(file($extractedPath), 0, 5);
                        foreach ($lines as $lineNum => $line) {
                            echo "    " . ($lineNum + 1) . ": " . trim($line) . "\n";
                        }
                        echo "\n";
                    }
                }
                
                $zip->close();
                
                $downloadedFiles[] = [
                    'file' => $fileName,
                    'zip_path' => $zipPath,
                    'extract_dir' => $extractDir,
                    'size' => $download['size']
                ];
                
                $totalDownloaded++;
                
            } else {
                echo "❌ Failed to extract ZIP file\n";
            }
            
            echo "\n";
            
        } else {
            echo "❌ Failed to download {$fileName}: " . $download['error'] . "\n\n";
        }
    }
    
    echo "=== DOWNLOAD SUMMARY ===\n";
    echo "Successfully downloaded: {$totalDownloaded} county files\n";
    echo "Storage location: " . __DIR__ . "/texas_counties/\n\n";
    
    if (!empty($downloadedFiles)) {
        echo "Downloaded counties:\n";
        foreach ($downloadedFiles as $file) {
            echo "📁 {$file['file']} - " . number_format($file['size']) . " bytes\n";
            echo "   ZIP: {$file['zip_path']}\n";
            echo "   Extracted: {$file['extract_dir']}\n";
        }
        
        echo "\n🎯 Next steps:\n";
        echo "1. Parse the CSV files to understand address format\n";
        echo "2. Create database tables for address-to-jurisdiction mapping\n";
        echo "3. Import address data for these counties\n";
        echo "4. Test address lookup functionality\n";
        echo "5. Expand to download more counties as needed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}