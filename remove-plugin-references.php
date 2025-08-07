<?php

/**
 * Script to remove old plugin references from views and code
 */

$baseDir = __DIR__;
$directories = [
    'resources/views',
    'public',
    'app'
];

// Old plugin references to remove
$oldReferences = [
    // jQuery references
    '/includes/plugins/jquery/jquery.min.js',
    '/includes/plugins/jquery-ui/',
    'jquery.min.js',
    'jquery-ui.min.js',
    
    // Bootstrap old references
    '/includes/plugins/bootstrap/js/bootstrap.bundle.min.js',
    '/includes/plugins/bootstrap/css/bootstrap.min.css',
    
    // Plugin-specific references
    '/includes/plugins/select2/',
    '/includes/plugins/moment/',
    '/includes/plugins/daterangepicker/',
    '/includes/plugins/tempusdominus-bootstrap-4/',
    '/includes/plugins/dropzone/',
    '/includes/plugins/sweetalert2/',
    '/includes/plugins/toastr/',
    '/includes/plugins/chart.js/',
    '/includes/plugins/fullcalendar-6.1.10/',
    '/includes/plugins/pdfmake/',
    '/includes/plugins/clipboardjs/',
    '/includes/plugins/inputmask/',
    
    // Old script tags
    '<script src="/includes/plugins/',
    '<link href="/includes/plugins/',
    '<link rel="stylesheet" href="/includes/plugins/',
];

// Replacement patterns
$replacements = [
    // jQuery to Alpine.js/Vanilla JS patterns
    '$(' => '// MIGRATED: $(',
    '$(document).ready(' => '// MIGRATED: document.addEventListener(\'DOMContentLoaded\', ',
    '.click(' => '// MIGRATED: .addEventListener(\'click\', ',
    '.change(' => '// MIGRATED: .addEventListener(\'change\', ',
    '.submit(' => '// MIGRATED: .addEventListener(\'submit\', ',
    '.hide()' => '// MIGRATED: .style.display = \'none\'',
    '.show()' => '// MIGRATED: .style.display = \'block\'',
    '.addClass(' => '// MIGRATED: .classList.add(',
    '.removeClass(' => '// MIGRATED: .classList.remove(',
    '.toggleClass(' => '// MIGRATED: .classList.toggle(',
    
    // Select2 to Tom Select
    '.select2(' => '// MIGRATED: new TomSelect(',
    
    // Moment.js to date-fns
    'moment(' => '// MIGRATED: use date-fns functions',
    
    // SweetAlert patterns
    'swal(' => '// MIGRATED: Swal.fire(',
    
    // Toastr to SweetAlert2
    'toastr.' => '// MIGRATED: Swal.fire(',
];

function scanDirectory($dir, $baseDir) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $extension = $file->getExtension();
            if (in_array($extension, ['php', 'blade.php', 'js', 'html', 'htm'])) {
                $files[] = $file->getPathname();
            }
        }
    }
    
    return $files;
}

function removePluginReferences($filePath, $oldReferences, $replacements) {
    $content = file_get_contents($filePath);
    $originalContent = $content;
    $changes = [];
    
    // Remove old plugin references
    foreach ($oldReferences as $reference) {
        if (strpos($content, $reference) !== false) {
            $content = str_replace($reference, '<!-- REMOVED: ' . $reference . ' -->', $content);
            $changes[] = "Removed reference: $reference";
        }
    }
    
    // Apply replacements (comment out old patterns)
    foreach ($replacements as $old => $new) {
        if (strpos($content, $old) !== false) {
            $content = str_replace($old, $new, $content);
            $changes[] = "Replaced: $old -> $new";
        }
    }
    
    // Remove empty script/link tags
    $content = preg_replace('/<script[^>]*src="[^"]*\/includes\/plugins\/[^"]*"[^>]*><\/script>/', '<!-- REMOVED: Old plugin script -->', $content);
    $content = preg_replace('/<link[^>]*href="[^"]*\/includes\/plugins\/[^"]*"[^>]*>/', '<!-- REMOVED: Old plugin stylesheet -->', $content);
    
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        return $changes;
    }
    
    return [];
}

echo "Starting plugin reference removal...\n\n";

$totalFiles = 0;
$modifiedFiles = 0;
$totalChanges = 0;

foreach ($directories as $dir) {
    $fullDir = $baseDir . '/' . $dir;
    if (!is_dir($fullDir)) {
        echo "Directory not found: $fullDir\n";
        continue;
    }
    
    echo "Scanning directory: $dir\n";
    $files = scanDirectory($fullDir, $baseDir);
    
    foreach ($files as $file) {
        $totalFiles++;
        $changes = removePluginReferences($file, $oldReferences, $replacements);
        
        if (!empty($changes)) {
            $modifiedFiles++;
            $totalChanges += count($changes);
            $relativePath = str_replace($baseDir . '/', '', $file);
            echo "  Modified: $relativePath\n";
            foreach ($changes as $change) {
                echo "    - $change\n";
            }
        }
    }
}

echo "\n=== Summary ===\n";
echo "Total files scanned: $totalFiles\n";
echo "Files modified: $modifiedFiles\n";
echo "Total changes made: $totalChanges\n";

// Create a backup list of removed references
$backupFile = $baseDir . '/removed-plugin-references.log';
$logContent = "Plugin Reference Removal Log - " . date('Y-m-d H:i:s') . "\n";
$logContent .= "=================================================\n\n";
$logContent .= "Old references removed:\n";
foreach ($oldReferences as $ref) {
    $logContent .= "- $ref\n";
}
$logContent .= "\nReplacements made:\n";
foreach ($replacements as $old => $new) {
    $logContent .= "- $old -> $new\n";
}
$logContent .= "\nTotal files scanned: $totalFiles\n";
$logContent .= "Files modified: $modifiedFiles\n";
$logContent .= "Total changes made: $totalChanges\n";

file_put_contents($backupFile, $logContent);
echo "\nLog saved to: removed-plugin-references.log\n";

echo "\nPlugin reference removal completed!\n";
echo "\nNext steps:\n";
echo "1. Review the modified files to ensure changes are correct\n";
echo "2. Test the application functionality\n";
echo "3. Run 'npm install' to install new frontend packages\n";
echo "4. Run 'npm run build' to compile assets\n";
echo "5. Remove old plugin directories when ready\n";