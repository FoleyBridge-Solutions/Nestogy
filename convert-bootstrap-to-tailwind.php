<?php

/**
 * Bootstrap to Tailwind/Flux UI Converter Script
 * This script converts Bootstrap classes to Tailwind CSS and Flux UI components
 */

class BootstrapToTailwindConverter
{
    private array $classMap = [
        // Grid System
        'container' => 'container mx-auto',
        'container-fluid' => 'w-full',
        'row' => 'flex flex-wrap -mx-4',
        'col' => 'flex-1 px-4',
        'col-1' => 'w-1/12 px-4',
        'col-2' => 'w-2/12 px-4',
        'col-3' => 'w-3/12 px-4',
        'col-4' => 'w-4/12 px-4',
        'col-5' => 'w-5/12 px-4',
        'col-6' => 'w-6/12 px-4',
        'col-7' => 'w-7/12 px-4',
        'col-8' => 'w-8/12 px-4',
        'col-9' => 'w-9/12 px-4',
        'col-10' => 'w-10/12 px-4',
        'col-11' => 'w-11/12 px-4',
        'col-12' => 'w-full px-4',
        'col-md-1' => 'md:w-1/12 px-4',
        'col-md-2' => 'md:w-2/12 px-4',
        'col-md-3' => 'md:w-3/12 px-4',
        'col-md-4' => 'md:w-4/12 px-4',
        'col-md-5' => 'md:w-5/12 px-4',
        'col-md-6' => 'md:w-1/2 px-4',
        'col-md-7' => 'md:w-7/12 px-4',
        'col-md-8' => 'md:w-2/3 px-4',
        'col-md-9' => 'md:w-9/12 px-4',
        'col-md-10' => 'md:w-10/12 px-4',
        'col-md-11' => 'md:w-11/12 px-4',
        'col-md-12' => 'md:w-full px-4',
        'col-lg-1' => 'lg:w-1/12 px-4',
        'col-lg-2' => 'lg:w-2/12 px-4',
        'col-lg-3' => 'lg:w-1/4 px-4',
        'col-lg-4' => 'lg:w-1/3 px-4',
        'col-lg-5' => 'lg:w-5/12 px-4',
        'col-lg-6' => 'lg:w-1/2 px-4',
        'col-lg-7' => 'lg:w-7/12 px-4',
        'col-lg-8' => 'lg:w-2/3 px-4',
        'col-lg-9' => 'lg:w-3/4 px-4',
        'col-lg-10' => 'lg:w-10/12 px-4',
        'col-lg-11' => 'lg:w-11/12 px-4',
        'col-lg-12' => 'lg:w-full px-4',
        'col-sm-1' => 'sm:w-1/12 px-4',
        'col-sm-2' => 'sm:w-2/12 px-4',
        'col-sm-3' => 'sm:w-1/4 px-4',
        'col-sm-4' => 'sm:w-1/3 px-4',
        'col-sm-5' => 'sm:w-5/12 px-4',
        'col-sm-6' => 'sm:w-1/2 px-4',
        'col-sm-7' => 'sm:w-7/12 px-4',
        'col-sm-8' => 'sm:w-2/3 px-4',
        'col-sm-9' => 'sm:w-3/4 px-4',
        'col-sm-10' => 'sm:w-10/12 px-4',
        'col-sm-11' => 'sm:w-11/12 px-4',
        'col-sm-12' => 'sm:w-full px-4',
        
        // Buttons
        'btn' => 'px-4 py-2 font-medium rounded-md transition-colors',
        'btn-primary' => 'bg-blue-600 text-white hover:bg-blue-700',
        'btn-secondary' => 'bg-gray-600 text-white hover:bg-gray-700',
        'btn-success' => 'bg-green-600 text-white hover:bg-green-700',
        'btn-danger' => 'bg-red-600 text-white hover:bg-red-700',
        'btn-warning' => 'bg-yellow-500 text-white hover:bg-yellow-600',
        'btn-info' => 'bg-cyan-600 text-white hover:bg-cyan-700',
        'btn-light' => 'bg-gray-100 text-gray-800 hover:bg-gray-200',
        'btn-dark' => 'bg-gray-900 text-white hover:bg-gray-800',
        'btn-outline-primary' => 'border border-blue-600 text-blue-600 hover:bg-blue-50',
        'btn-outline-secondary' => 'border border-gray-600 text-gray-600 hover:bg-gray-50',
        'btn-outline-success' => 'border border-green-600 text-green-600 hover:bg-green-50',
        'btn-outline-danger' => 'border border-red-600 text-red-600 hover:bg-red-50',
        'btn-sm' => 'px-3 py-1 text-sm',
        'btn-lg' => 'px-6 py-3 text-lg',
        'btn-block' => 'w-full',
        
        // Cards
        'card' => 'bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden',
        'card-header' => 'px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900',
        'card-body' => 'p-6',
        'card-footer' => 'px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900',
        'card-title' => 'text-lg font-semibold text-gray-800 dark:text-gray-200',
        
        // Forms
        'form-control' => 'block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm',
        'form-label' => 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1',
        'form-group' => 'mb-4',
        'form-check' => 'flex items-center',
        'form-check-input' => 'mr-2',
        'form-check-label' => 'text-sm text-gray-700 dark:text-gray-300',
        'form-select' => 'block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm',
        'input-group' => 'flex',
        'input-group-text' => 'px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-l-md',
        'is-invalid' => 'border-red-500',
        'invalid-feedback' => 'text-red-600 text-sm mt-1',
        
        // Alerts
        'alert' => 'px-4 py-3 rounded mb-4',
        'alert-primary' => 'bg-blue-100 border border-blue-400 text-blue-700',
        'alert-secondary' => 'bg-gray-100 border border-gray-400 text-gray-700',
        'alert-success' => 'bg-green-100 border border-green-400 text-green-700',
        'alert-danger' => 'bg-red-100 border border-red-400 text-red-700',
        'alert-warning' => 'bg-yellow-100 border border-yellow-400 text-yellow-700',
        'alert-info' => 'bg-cyan-100 border border-cyan-400 text-cyan-700',
        'alert-dismissible' => 'relative pr-10',
        
        // Tables
        'table' => 'min-w-full divide-y divide-gray-200 dark:divide-gray-700',
        'table-striped' => 'even:bg-gray-50 dark:even:bg-gray-800',
        'table-bordered' => 'border border-gray-200 dark:border-gray-700',
        'table-hover' => 'hover:bg-gray-50 dark:hover:bg-gray-800',
        'table-responsive' => 'overflow-x-auto',
        
        // Utilities
        'd-none' => 'hidden',
        'd-block' => 'block',
        'd-inline' => 'inline',
        'd-inline-block' => 'inline-block',
        'd-flex' => 'flex',
        'd-inline-flex' => 'inline-flex',
        'justify-content-between' => 'justify-between',
        'justify-content-center' => 'justify-center',
        'justify-content-start' => 'justify-start',
        'justify-content-end' => 'justify-end',
        'align-items-center' => 'items-center',
        'align-items-start' => 'items-start',
        'align-items-end' => 'items-end',
        'text-center' => 'text-center',
        'text-left' => 'text-left',
        'text-right' => 'text-right',
        'text-muted' => 'text-gray-600 dark:text-gray-400',
        'text-primary' => 'text-blue-600 dark:text-blue-400',
        'text-success' => 'text-green-600 dark:text-green-400',
        'text-danger' => 'text-red-600 dark:text-red-400',
        'text-warning' => 'text-yellow-600 dark:text-yellow-400',
        'text-info' => 'text-cyan-600 dark:text-cyan-400',
        'font-weight-bold' => 'font-bold',
        'fw-bold' => 'font-bold',
        'font-weight-normal' => 'font-normal',
        'fw-normal' => 'font-normal',
        
        // Spacing
        'mb-0' => 'mb-0',
        'mb-1' => 'mb-1',
        'mb-2' => 'mb-2',
        'mb-3' => 'mb-4',
        'mb-4' => 'mb-6',
        'mb-5' => 'mb-8',
        'mt-0' => 'mt-0',
        'mt-1' => 'mt-1',
        'mt-2' => 'mt-2',
        'mt-3' => 'mt-4',
        'mt-4' => 'mt-6',
        'mt-5' => 'mt-8',
        'me-1' => 'mr-1',
        'me-2' => 'mr-2',
        'me-3' => 'mr-4',
        'ms-1' => 'ml-1',
        'ms-2' => 'ml-2',
        'ms-3' => 'ml-4',
        'p-0' => 'p-0',
        'p-1' => 'p-1',
        'p-2' => 'p-2',
        'p-3' => 'p-4',
        'p-4' => 'p-6',
        'p-5' => 'p-8',
        'py-1' => 'py-1',
        'py-2' => 'py-2',
        'py-3' => 'py-4',
        'py-4' => 'py-6',
        'py-5' => 'py-8',
        'px-1' => 'px-1',
        'px-2' => 'px-2',
        'px-3' => 'px-4',
        'px-4' => 'px-6',
        'px-5' => 'px-8',
        
        // Borders
        'border' => 'border',
        'border-0' => 'border-0',
        'border-top' => 'border-t',
        'border-bottom' => 'border-b',
        'border-left' => 'border-l',
        'border-right' => 'border-r',
        'rounded' => 'rounded',
        'rounded-circle' => 'rounded-full',
        
        // Modal classes
        'modal' => 'fixed inset-0 z-50 overflow-y-auto',
        'modal-dialog' => 'flex items-center justify-center min-h-screen',
        'modal-content' => 'bg-white dark:bg-gray-800 rounded-lg shadow-xl',
        'modal-header' => 'px-6 py-4 border-b border-gray-200 dark:border-gray-700',
        'modal-body' => 'px-6 py-4',
        'modal-footer' => 'px-6 py-4 border-t border-gray-200 dark:border-gray-700',
        
        // Badges
        'badge' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
        'badge-primary' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'badge-secondary' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        'badge-success' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'badge-danger' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        'badge-warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        'badge-info' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200',
    ];
    
    private array $componentMap = [
        // Convert Bootstrap components to Flux UI
        '<div class="card"' => '<flux:card',
        '</div><!-- .card -->' => '</flux:card>',
        '<div class="card-header"' => '<flux:card.header',
        '</div><!-- .card-header -->' => '</flux:card.header>',
        '<div class="card-body"' => '<flux:card.body',
        '</div><!-- .card-body -->' => '</flux:card.body>',
        '<button class="btn btn-primary"' => '<flux:button variant="primary"',
        '<button class="btn btn-secondary"' => '<flux:button variant="secondary"',
        '<button class="btn btn-danger"' => '<flux:button variant="danger"',
        '<button class="btn btn-success"' => '<flux:button variant="success"',
        '</button>' => '</flux:button>',
        '<input type="text" class="form-control"' => '<flux:input type="text"',
        '<input type="email" class="form-control"' => '<flux:input type="email"',
        '<input type="password" class="form-control"' => '<flux:input type="password"',
        '<select class="form-select"' => '<flux:select',
        '<select class="form-control"' => '<flux:select',
        '</select>' => '</flux:select>',
        '<textarea class="form-control"' => '<flux:textarea',
        '</textarea>' => '</flux:textarea>',
        '<label class="form-label"' => '<flux:label',
        '</label>' => '</flux:label>',
    ];
    
    public function convertFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            echo "File not found: $filePath\n";
            return;
        }
        
        $content = file_get_contents($filePath);
        $originalContent = $content;
        
        // Replace Bootstrap classes with Tailwind
        foreach ($this->classMap as $bootstrap => $tailwind) {
            // Match class in quotes
            $content = preg_replace(
                '/class="([^"]*)\b' . preg_quote($bootstrap, '/') . '\b([^"]*)"/i',
                'class="$1' . $tailwind . '$2"',
                $content
            );
            $content = preg_replace(
                "/class='([^']*)\b" . preg_quote($bootstrap, '/') . "\b([^']*)'/i",
                "class='$1" . $tailwind . "$2'",
                $content
            );
        }
        
        // Replace Bootstrap components with Flux UI components
        foreach ($this->componentMap as $bootstrap => $flux) {
            $content = str_replace($bootstrap, $flux, $content);
        }
        
        // Clean up multiple spaces in class attributes
        $content = preg_replace('/class="([^"]*)\s+([^"]*)"/i', 'class="$1 $2"', $content);
        $content = preg_replace('/class="\\s+"/i', 'class=""', $content);
        
        // Only write if content changed
        if ($content !== $originalContent) {
            // Create backup
            $backupPath = $filePath . '.bootstrap-backup-' . date('Ymd-His');
            copy($filePath, $backupPath);
            
            // Write converted content
            file_put_contents($filePath, $content);
            echo "Converted: $filePath\n";
            echo "Backup saved: $backupPath\n";
        } else {
            echo "No changes needed: $filePath\n";
        }
    }
    
    public function convertDirectory(string $directory, array $extensions = ['blade.php']): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
                $filename = $file->getFilename();
                
                // Check if file has blade.php extension
                if (str_ends_with($filename, '.blade.php')) {
                    // Skip backup files
                    if (strpos($filename, '.backup') !== false || 
                        strpos($filename, 'bootstrap-backup') !== false) {
                        continue;
                    }
                    
                    $this->convertFile($file->getPathname());
                }
            }
        }
    }
}

// Usage
$converter = new BootstrapToTailwindConverter();

// Convert specific directories
$directories = [
    '/var/www/Nestogy/resources/views/client-portal',
    '/var/www/Nestogy/resources/views/assets',
    '/var/www/Nestogy/resources/views/auth',
    '/var/www/Nestogy/resources/views/financial',
    '/var/www/Nestogy/resources/views/clients',
    '/var/www/Nestogy/resources/views/security',
    '/var/www/Nestogy/resources/views/tickets',
    '/var/www/Nestogy/resources/views/admin',
    '/var/www/Nestogy/resources/views/components',
];

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        echo "\nConverting directory: $dir\n";
        echo str_repeat('-', 50) . "\n";
        $converter->convertDirectory($dir);
    }
}

echo "\n✅ Conversion complete!\n";