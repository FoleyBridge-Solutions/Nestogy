<?php

/**
 * Final Bootstrap to Tailwind/Flux UI Cleanup Script
 * This script performs a more thorough conversion
 */

class FinalBootstrapCleanup
{
    private array $patterns = [
        // Remove all col-* classes and replace with Tailwind grid
        '/\bcol-(\d+)\b/' => 'w-$1/12',
        '/\bcol-md-(\d+)\b/' => 'md:w-$1/12',
        '/\bcol-lg-(\d+)\b/' => 'lg:w-$1/12',
        '/\bcol-sm-(\d+)\b/' => 'sm:w-$1/12',
        '/\bcol-xl-(\d+)\b/' => 'xl:w-$1/12',
        
        // Replace specific column sizes
        '/\bw-6\/12\b/' => 'w-1/2',
        '/\bmd:w-6\/12\b/' => 'md:w-1/2',
        '/\blg:w-6\/12\b/' => 'lg:w-1/2',
        '/\bw-4\/12\b/' => 'w-1/3',
        '/\bmd:w-4\/12\b/' => 'md:w-1/3',
        '/\blg:w-4\/12\b/' => 'lg:w-1/3',
        '/\bw-3\/12\b/' => 'w-1/4',
        '/\bmd:w-3\/12\b/' => 'md:w-1/4',
        '/\blg:w-3\/12\b/' => 'lg:w-1/4',
        '/\bw-8\/12\b/' => 'w-2/3',
        '/\bmd:w-8\/12\b/' => 'md:w-2/3',
        '/\blg:w-8\/12\b/' => 'lg:w-2/3',
        '/\bw-9\/12\b/' => 'w-3/4',
        '/\bmd:w-9\/12\b/' => 'md:w-3/4',
        '/\blg:w-9\/12\b/' => 'lg:w-3/4',
        
        // Replace d-* display utilities
        '/\bd-none\b/' => 'hidden',
        '/\bd-block\b/' => 'block',
        '/\bd-inline\b/' => 'inline',
        '/\bd-inline-block\b/' => 'inline-block',
        '/\bd-flex\b/' => 'flex',
        '/\bd-inline-flex\b/' => 'inline-flex',
        '/\bd-grid\b/' => 'grid',
        '/\bd-lg-none\b/' => 'lg:hidden',
        '/\bd-lg-block\b/' => 'lg:block',
        '/\bd-lg-flex\b/' => 'lg:flex',
        '/\bd-md-none\b/' => 'md:hidden',
        '/\bd-md-block\b/' => 'md:block',
        '/\bd-md-flex\b/' => 'md:flex',
        
        // More button replacements
        '/\bbtn\s+btn-primary\b/' => 'px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700',
        '/\bbtn\s+btn-secondary\b/' => 'px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700',
        '/\bbtn\s+btn-success\b/' => 'px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700',
        '/\bbtn\s+btn-danger\b/' => 'px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700',
        '/\bbtn\s+btn-warning\b/' => 'px-4 py-2 bg-yellow-500 text-white rounded-md hover:bg-yellow-600',
        '/\bbtn\s+btn-info\b/' => 'px-4 py-2 bg-cyan-600 text-white rounded-md hover:bg-cyan-700',
        '/\bbtn-sm\b/' => 'px-3 py-1 text-sm',
        '/\bbtn-lg\b/' => 'px-6 py-3 text-lg',
        '/\bbtn-block\b/' => 'w-full',
        
        // More spacing utilities
        '/\bme-(\d+)\b/' => 'mr-$1',
        '/\bms-(\d+)\b/' => 'ml-$1',
        '/\bpe-(\d+)\b/' => 'pr-$1',
        '/\bps-(\d+)\b/' => 'pl-$1',
        
        // Text utilities
        '/\btext-muted\b/' => 'text-gray-600 dark:text-gray-400',
        '/\btext-primary\b/' => 'text-blue-600 dark:text-blue-400',
        '/\btext-success\b/' => 'text-green-600 dark:text-green-400',
        '/\btext-danger\b/' => 'text-red-600 dark:text-red-400',
        '/\btext-warning\b/' => 'text-yellow-600 dark:text-yellow-400',
        '/\btext-info\b/' => 'text-cyan-600 dark:text-cyan-400',
        '/\btext-secondary\b/' => 'text-gray-600 dark:text-gray-400',
        '/\bfw-bold\b/' => 'font-bold',
        '/\bfw-normal\b/' => 'font-normal',
        '/\bfont-weight-bold\b/' => 'font-bold',
        '/\bfont-weight-normal\b/' => 'font-normal',
        
        // Card utilities
        '/\bcard-title\b/' => 'text-lg font-semibold text-gray-800 dark:text-gray-200',
        
        // Alignment
        '/\bjustify-content-between\b/' => 'justify-between',
        '/\bjustify-content-center\b/' => 'justify-center',
        '/\bjustify-content-start\b/' => 'justify-start',
        '/\bjustify-content-end\b/' => 'justify-end',
        '/\balign-items-center\b/' => 'items-center',
        '/\balign-items-start\b/' => 'items-start',
        '/\balign-items-end\b/' => 'items-end',
        
        // Remove unnecessary classes
        '/\brow\b/' => 'flex flex-wrap',
        '/\bcontainer-fluid\b/' => 'w-full',
        
        // Alert classes
        '/\balert\s+alert-primary\b/' => 'px-4 py-3 rounded mb-4 bg-blue-100 border border-blue-400 text-blue-700',
        '/\balert\s+alert-success\b/' => 'px-4 py-3 rounded mb-4 bg-green-100 border border-green-400 text-green-700',
        '/\balert\s+alert-danger\b/' => 'px-4 py-3 rounded mb-4 bg-red-100 border border-red-400 text-red-700',
        '/\balert\s+alert-warning\b/' => 'px-4 py-3 rounded mb-4 bg-yellow-100 border border-yellow-400 text-yellow-700',
        '/\balert\s+alert-info\b/' => 'px-4 py-3 rounded mb-4 bg-cyan-100 border border-cyan-400 text-cyan-700',
        
        // Badge classes
        '/\bbadge\s+badge-primary\b/' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800',
        '/\bbadge\s+badge-success\b/' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800',
        '/\bbadge\s+badge-danger\b/' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800',
        '/\bbadge\s+badge-warning\b/' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800',
        
        // Form classes
        '/\bform-control\b/' => 'block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm',
        '/\bform-select\b/' => 'block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm',
        '/\bform-label\b/' => 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1',
        '/\bform-group\b/' => 'mb-4',
        '/\bform-check\b/' => 'flex items-center',
        '/\bform-check-input\b/' => 'mr-2',
        '/\bform-check-label\b/' => 'text-sm text-gray-700 dark:text-gray-300',
        '/\binput-group\b/' => 'flex',
        '/\binput-group-text\b/' => 'px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-l-md',
        '/\bis-invalid\b/' => 'border-red-500',
        '/\binvalid-feedback\b/' => 'text-red-600 text-sm mt-1',
        
        // Table classes
        '/\btable-responsive\b/' => 'overflow-x-auto',
        '/\btable-striped\b/' => '[&>tbody>tr:nth-child(even)]:bg-gray-50 dark:[&>tbody>tr:nth-child(even)]:bg-gray-800',
        '/\btable-bordered\b/' => 'border border-gray-200 dark:border-gray-700',
        '/\btable-hover\b/' => '[&>tbody>tr]:hover:bg-gray-50 dark:[&>tbody>tr]:hover:bg-gray-800',
        
        // Modal classes
        '/\bmodal-dialog\b/' => 'flex items-center justify-center min-h-screen',
        '/\bmodal-content\b/' => 'bg-white dark:bg-gray-800 rounded-lg shadow-xl',
        '/\bmodal-header\b/' => 'px-6 py-4 border-b border-gray-200 dark:border-gray-700',
        '/\bmodal-body\b/' => 'px-6 py-4',
        '/\bmodal-footer\b/' => 'px-6 py-4 border-t border-gray-200 dark:border-gray-700',
        
        // List groups
        '/\blist-group\b/' => 'divide-y divide-gray-200 dark:divide-gray-700',
        '/\blist-group-item\b/' => 'px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800',
    ];
    
    public function processFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            echo "File not found: $filePath\n";
            return false;
        }
        
        // Skip backup files
        if (strpos($filePath, '.backup') !== false || 
            strpos($filePath, 'bootstrap-backup') !== false) {
            return false;
        }
        
        $content = file_get_contents($filePath);
        $originalContent = $content;
        $changed = false;
        
        // Apply all pattern replacements
        foreach ($this->patterns as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== $content) {
                $content = $newContent;
                $changed = true;
            }
        }
        
        // Clean up multiple spaces in class attributes
        $content = preg_replace('/class="([^"]*)\s{2,}([^"]*)"/i', 'class="$1 $2"', $content);
        $content = preg_replace("/class='([^']*)\s{2,}([^']*)'/i", "class='$1 $2'", $content);
        
        // Remove empty class attributes
        $content = preg_replace('/class="\\s*"/i', 'class=""', $content);
        $content = preg_replace("/class='\\s*'/i", "class=''", $content);
        
        // Save if changed
        if ($changed) {
            file_put_contents($filePath, $content);
            echo "✓ Cleaned: " . basename($filePath) . "\n";
            return true;
        }
        
        return false;
    }
    
    public function processDirectory(string $directory): void
    {
        $count = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                if ($this->processFile($file->getPathname())) {
                    $count++;
                }
            }
        }
        
        echo "Processed $count files in $directory\n";
    }
}

// Run the cleanup
$cleanup = new FinalBootstrapCleanup();

$directories = [
    '/var/www/Nestogy/resources/views',
];

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        echo "\n🔧 Cleaning directory: $dir\n";
        echo str_repeat('-', 50) . "\n";
        $cleanup->processDirectory($dir);
    }
}

echo "\n✅ Final cleanup complete!\n";