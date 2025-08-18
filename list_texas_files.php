<?php

require_once __DIR__ . '/vendor/autoload.php';

// Boot Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\TaxEngine\TexasComptrollerDataService;

echo "Exploring Texas Comptroller Files for Q3 2025...\n\n";

try {
    $service = new TexasComptrollerDataService();
    
    // List all available files for Q3 2025
    $fileList = $service->listAvailableFiles('2025Q3');
    
    if (!$fileList['success']) {
        throw new Exception('Failed to list files: ' . $fileList['error']);
    }
    
    echo "Found " . $fileList['count'] . " files for Q3 2025\n\n";
    
    // Categorize files by type and size
    $categories = [
        'address' => [],
        'tax_rates' => [],
        'jurisdiction' => [],
        'boundary' => [],
        'zip' => [],
        'csv' => [],
        'other' => []
    ];
    
    foreach ($fileList['files'] as $file) {
        $filePath = strtolower($file['filePath']);
        $fileName = basename($file['filePath']);
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $sizeKB = round($file['fileSize'] / 1024, 2);
        
        $fileInfo = [
            'name' => $fileName,
            'path' => $file['filePath'],
            'size_kb' => $sizeKB,
            'size_mb' => round($sizeKB / 1024, 2)
        ];
        
        if (str_contains($filePath, 'address')) {
            $categories['address'][] = $fileInfo;
        } elseif (str_contains($filePath, 'tax_jurisdiction_rates') || str_contains($filePath, 'tax') && str_contains($filePath, 'rate')) {
            $categories['tax_rates'][] = $fileInfo;
        } elseif (str_contains($filePath, 'jurisdiction')) {
            $categories['jurisdiction'][] = $fileInfo;
        } elseif (str_contains($filePath, 'boundary') || str_contains($filePath, 'border')) {
            $categories['boundary'][] = $fileInfo;
        } elseif ($ext === 'zip') {
            $categories['zip'][] = $fileInfo;
        } elseif ($ext === 'csv') {
            $categories['csv'][] = $fileInfo;
        } else {
            $categories['other'][] = $fileInfo;
        }
    }
    
    // Display categorized files
    foreach ($categories as $category => $files) {
        if (!empty($files)) {
            echo "=== " . strtoupper($category) . " FILES ===\n";
            
            // Sort by size (smallest first)
            usort($files, function($a, $b) { return $a['size_kb'] <=> $b['size_kb']; });
            
            foreach ($files as $file) {
                $sizeDisplay = $file['size_mb'] > 1 ? $file['size_mb'] . ' MB' : $file['size_kb'] . ' KB';
                echo "  • {$file['name']} ({$sizeDisplay})\n";
                
                // Show path for smaller files that might be downloadable
                if ($file['size_mb'] < 10) {
                    echo "    Path: {$file['path']}\n";
                }
            }
            echo "\n";
        }
    }
    
    // Show summary statistics
    echo "=== SUMMARY ===\n";
    $totalFiles = array_sum(array_map('count', $categories));
    echo "Total files: {$totalFiles}\n";
    
    foreach ($categories as $category => $files) {
        if (!empty($files)) {
            $count = count($files);
            $totalSize = array_sum(array_column($files, 'size_mb'));
            echo "• {$category}: {$count} files ({$totalSize} MB total)\n";
        }
    }
    
    // Suggest best approach
    echo "\n=== RECOMMENDATIONS ===\n";
    if (!empty($categories['csv'])) {
        echo "✅ Found " . count($categories['csv']) . " CSV files - these are typically easier to process\n";
        $smallCsvFiles = array_filter($categories['csv'], function($f) { return $f['size_mb'] < 5; });
        if (!empty($smallCsvFiles)) {
            echo "🎯 " . count($smallCsvFiles) . " small CSV files (<5MB) available for quick download\n";
        }
    }
    
    if (!empty($categories['address'])) {
        echo "📍 Found " . count($categories['address']) . " address-related files\n";
        $smallAddressFiles = array_filter($categories['address'], function($f) { return $f['size_mb'] < 10; });
        if (!empty($smallAddressFiles)) {
            echo "🎯 " . count($smallAddressFiles) . " address files (<10MB) available\n";
        }
    }
    
    if (!empty($categories['zip'])) {
        echo "📦 Found " . count($categories['zip']) . " ZIP files - may contain multiple datasets\n";
        $smallZipFiles = array_filter($categories['zip'], function($f) { return $f['size_mb'] < 20; });
        if (!empty($smallZipFiles)) {
            echo "🎯 " . count($smallZipFiles) . " smaller ZIP files (<20MB) available\n";
        }
    }
    
    echo "\n💡 Next steps:\n";
    echo "1. Try downloading smaller CSV files first\n";
    echo "2. Use ZIP files for comprehensive data (may require multiple attempts)\n";
    echo "3. Focus on address mapping files for jurisdiction lookup\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}