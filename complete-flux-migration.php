#!/usr/bin/env php
<?php

/**
 * Complete Flux UI Migration Script
 * Converts remaining Bootstrap components to Flux UI components
 * 
 * This script handles:
 * - Bootstrap buttons to Flux buttons
 * - Bootstrap modals to Flux modals  
 * - Bootstrap alerts to Flux callouts
 * - Bootstrap data attributes to Flux equivalents
 * - Form components to Flux components
 */

class FluxMigrationComplete {
    private $viewsPath;
    private $stats = [
        'files_processed' => 0,
        'files_modified' => 0,
        'buttons_converted' => 0,
        'modals_converted' => 0,
        'alerts_converted' => 0,
        'forms_converted' => 0,
        'data_attrs_converted' => 0,
    ];
    private $filesModified = [];
    private $backupSuffix;
    
    public function __construct() {
        $this->viewsPath = __DIR__ . '/resources/views';
        $this->backupSuffix = '.flux-complete-backup-' . date('Ymd-His');
    }
    
    public function run() {
        echo "Starting Complete Flux UI Migration...\n";
        echo "=====================================\n\n";
        
        $this->scanDirectory($this->viewsPath);
        
        $this->printSummary();
    }
    
    private function scanDirectory($dir) {
        $files = scandir($dir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->scanDirectory($path);
            } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php' && strpos($path, '.blade.php') !== false) {
                // Skip backup files
                if (strpos($path, '.backup-') !== false) {
                    continue;
                }
                $this->processFile($path);
            }
        }
    }
    
    private function processFile($filePath) {
        $this->stats['files_processed']++;
        
        $content = file_get_contents($filePath);
        $originalContent = $content;
        
        // Process various conversions
        $content = $this->convertButtons($content, $filePath);
        $content = $this->convertModals($content, $filePath);
        $content = $this->convertAlerts($content, $filePath);
        $content = $this->convertForms($content, $filePath);
        $content = $this->convertDataAttributes($content, $filePath);
        $content = $this->convertTables($content, $filePath);
        $content = $this->convertCards($content, $filePath);
        $content = $this->convertBadges($content, $filePath);
        $content = $this->cleanupBootstrapClasses($content, $filePath);
        
        if ($content !== $originalContent) {
            // Create backup
            file_put_contents($filePath . $this->backupSuffix, $originalContent);
            
            // Write updated content
            file_put_contents($filePath, $content);
            
            $this->stats['files_modified']++;
            $this->filesModified[] = $filePath;
            echo "Modified: " . str_replace(__DIR__ . '/', '', $filePath) . "\n";
        }
    }
    
    private function convertButtons($content, $file) {
        $localCount = 0;
        
        // Map Bootstrap button classes to Flux variants
        $buttonMappings = [
            'btn-primary' => 'variant="primary"',
            'btn-secondary' => 'variant="subtle"',
            'btn-success' => 'variant="primary" color="green"',
            'btn-danger' => 'variant="danger"',
            'btn-warning' => 'variant="primary" color="amber"',
            'btn-info' => 'variant="primary" color="sky"',
            'btn-light' => 'variant="ghost"',
            'btn-dark' => 'variant="filled"',
            'btn-outline-primary' => 'variant="ghost"',
            'btn-outline-secondary' => 'variant="ghost" color="zinc"',
            'btn-outline-success' => 'variant="ghost" color="green"',
            'btn-outline-danger' => 'variant="ghost" color="red"',
            'btn-outline-warning' => 'variant="ghost" color="amber"',
            'btn-outline-info' => 'variant="ghost" color="sky"',
            'btn-outline-light' => 'variant="ghost" color="zinc"',
            'btn-outline-dark' => 'variant="ghost" color="zinc"',
            'btn-link' => 'variant="ghost"',
        ];
        
        // Convert <button> and <a> elements with btn classes
        $patterns = [
            // Standard button elements
            '/<button([^>]*?)class="([^"]*?)btn btn-([^"\s]+)([^"]*?)"([^>]*?)>(.*?)<\/button>/s',
            // Anchor elements styled as buttons
            '/<a([^>]*?)class="([^"]*?)btn btn-([^"\s]+)([^"]*?)"([^>]*?)>(.*?)<\/a>/s',
        ];
        
        foreach ($patterns as $pattern) {
            $content = preg_replace_callback($pattern, function($matches) use (&$localCount, $buttonMappings, $file) {
                $beforeClass = $matches[1];
                $classPrefix = $matches[2];
                $btnType = $matches[3];
                $classSuffix = $matches[4];
                $afterClass = $matches[5];
                $buttonContent = $matches[6];
                $isAnchor = strpos($matches[0], '<a') === 0;
                
                // Extract additional classes
                $additionalClasses = trim($classPrefix . ' ' . $classSuffix);
                $additionalClasses = preg_replace('/\s*btn\s+btn-\S+\s*/', ' ', $additionalClasses);
                $additionalClasses = preg_replace('/\s*btn-\S+\s*/', ' ', $additionalClasses);
                
                // Determine size
                $size = '';
                if (strpos($additionalClasses, 'btn-sm') !== false) {
                    $size = ' size="sm"';
                    $additionalClasses = str_replace('btn-sm', '', $additionalClasses);
                } elseif (strpos($additionalClasses, 'btn-lg') !== false) {
                    $size = '';  // Default size
                    $additionalClasses = str_replace('btn-lg', '', $additionalClasses);
                }
                
                // Get variant attributes
                $btnClass = 'btn-' . $btnType;
                $variantAttr = isset($buttonMappings[$btnClass]) ? $buttonMappings[$btnClass] : 'variant="primary"';
                
                // Clean up additional classes
                $additionalClasses = trim($additionalClasses);
                $classAttr = $additionalClasses ? ' class="' . $additionalClasses . '"' : '';
                
                // Handle href for anchor tags
                $hrefAttr = '';
                if ($isAnchor && preg_match('/href="([^"]*)"/', $afterClass . $beforeClass, $hrefMatch)) {
                    $hrefAttr = ' href="' . $hrefMatch[1] . '"';
                    // Remove href from other attributes
                    $beforeClass = preg_replace('/\s*href="[^"]*"/', '', $beforeClass);
                    $afterClass = preg_replace('/\s*href="[^"]*"/', '', $afterClass);
                }
                
                // Extract other important attributes (wire:click, @click, etc.)
                $otherAttrs = trim($beforeClass . ' ' . $afterClass);
                $otherAttrs = preg_replace('/\s*class="[^"]*"/', '', $otherAttrs);
                
                $localCount++;
                echo "  Converted button in {$file}\n";
                
                return '<flux:button ' . $variantAttr . $size . $hrefAttr . $classAttr . ' ' . $otherAttrs . '>' . 
                       $buttonContent . '</flux:button>';
            }, $content);
        }
        
        // Convert input[type="submit"] and input[type="button"] with btn classes
        $content = preg_replace_callback(
            '/<input([^>]*?)type="(submit|button)"([^>]*?)class="([^"]*?)btn btn-([^"\s]+)([^"]*?)"([^>]*?)\/?>/',
            function($matches) use (&$localCount, $buttonMappings) {
                $beforeType = $matches[1];
                $type = $matches[2];
                $afterType = $matches[3];
                $classPrefix = $matches[4];
                $btnType = $matches[5];
                $classSuffix = $matches[6];
                $afterClass = $matches[7];
                
                // Extract value for button text
                $buttonText = 'Submit';
                if (preg_match('/value="([^"]*)"/', $matches[0], $valueMatch)) {
                    $buttonText = $valueMatch[1];
                }
                
                // Get variant
                $btnClass = 'btn-' . $btnType;
                $variantAttr = isset($buttonMappings[$btnClass]) ? $buttonMappings[$btnClass] : 'variant="primary"';
                
                // Extract other attributes
                $otherAttrs = $beforeType . ' ' . $afterType . ' ' . $afterClass;
                $otherAttrs = preg_replace('/\s*(class|type|value)="[^"]*"/', '', $otherAttrs);
                $otherAttrs = trim($otherAttrs);
                
                // Add type="submit" if original was submit
                if ($type === 'submit') {
                    $otherAttrs = 'type="submit" ' . $otherAttrs;
                }
                
                $localCount++;
                
                return '<flux:button ' . $variantAttr . ' ' . $otherAttrs . '>' . $buttonText . '</flux:button>';
            },
            $content
        );
        
        $this->stats['buttons_converted'] += $localCount;
        return $content;
    }
    
    private function convertModals($content, $file) {
        $localCount = 0;
        
        // Find modal triggers (buttons/links with data-bs-toggle="modal")
        $content = preg_replace_callback(
            '/<(button|a)([^>]*?)data-bs-toggle="modal"([^>]*?)data-bs-target="#([^"]+)"([^>]*?)>(.*?)<\/\1>/s',
            function($matches) use (&$localCount) {
                $element = $matches[1];
                $beforeToggle = $matches[2];
                $afterToggle = $matches[3];
                $modalId = $matches[4];
                $afterTarget = $matches[5];
                $triggerContent = $matches[6];
                
                $localCount++;
                
                // Clean up attributes
                $attrs = $beforeToggle . ' ' . $afterToggle . ' ' . $afterTarget;
                $attrs = preg_replace('/\s*data-bs-[^=]+="[^"]*"/', '', $attrs);
                
                return '<flux:modal.trigger name="' . $modalId . '">' . "\n" .
                       '    <flux:button' . $attrs . '>' . $triggerContent . '</flux:button>' . "\n" .
                       '</flux:modal.trigger>';
            },
            $content
        );
        
        // Convert modal structure
        $content = preg_replace_callback(
            '/<div([^>]*?)class="([^"]*?)modal([^"]*?)"([^>]*?)id="([^"]+)"([^>]*?)>(.*?)<\/div>(\s*<\/div>)*<!-- modal -->/s',
            function($matches) use (&$localCount) {
                $modalId = $matches[5];
                $modalContent = $matches[7];
                
                // Extract modal header, body, and footer
                $headerContent = '';
                $bodyContent = '';
                $footerContent = '';
                
                if (preg_match('/<div[^>]*?class="[^"]*?modal-header[^"]*?"[^>]*?>(.*?)<\/div>/s', $modalContent, $headerMatch)) {
                    $headerContent = strip_tags($headerMatch[1], '<h1><h2><h3><h4><h5><h6><span>');
                    $headerContent = trim($headerContent);
                }
                
                if (preg_match('/<div[^>]*?class="[^"]*?modal-body[^"]*?"[^>]*?>(.*?)<\/div>/s', $modalContent, $bodyMatch)) {
                    $bodyContent = $bodyMatch[1];
                }
                
                if (preg_match('/<div[^>]*?class="[^"]*?modal-footer[^"]*?"[^>]*?>(.*?)<\/div>/s', $modalContent, $footerMatch)) {
                    $footerContent = $footerMatch[1];
                    // Convert footer buttons
                    $footerContent = str_replace('data-bs-dismiss="modal"', '', $footerContent);
                }
                
                $localCount++;
                
                $fluxModal = '<flux:modal name="' . $modalId . '" class="md:w-96">' . "\n";
                $fluxModal .= '    <div class="space-y-6">' . "\n";
                
                if ($headerContent) {
                    $fluxModal .= '        <div>' . "\n";
                    $fluxModal .= '            <flux:heading size="lg">' . $headerContent . '</flux:heading>' . "\n";
                    $fluxModal .= '        </div>' . "\n";
                }
                
                if ($bodyContent) {
                    $fluxModal .= '        <div>' . "\n";
                    $fluxModal .= '            ' . trim($bodyContent) . "\n";
                    $fluxModal .= '        </div>' . "\n";
                }
                
                if ($footerContent) {
                    $fluxModal .= '        <div class="flex gap-2">' . "\n";
                    $fluxModal .= '            <flux:spacer />' . "\n";
                    
                    // Check if there's a close button
                    if (strpos($footerContent, 'Close') !== false || strpos($footerContent, 'Cancel') !== false) {
                        $fluxModal .= '            <flux:modal.close>' . "\n";
                        $fluxModal .= '                <flux:button variant="ghost">Cancel</flux:button>' . "\n";
                        $fluxModal .= '            </flux:modal.close>' . "\n";
                    }
                    
                    // Add other buttons (simplified - may need manual adjustment)
                    if (preg_match('/<button[^>]*?type="submit"[^>]*?>(.*?)<\/button>/s', $footerContent, $submitMatch)) {
                        $fluxModal .= '            <flux:button type="submit" variant="primary">' . strip_tags($submitMatch[1]) . '</flux:button>' . "\n";
                    }
                    
                    $fluxModal .= '        </div>' . "\n";
                }
                
                $fluxModal .= '    </div>' . "\n";
                $fluxModal .= '</flux:modal>';
                
                return $fluxModal;
            },
            $content
        );
        
        // Remove data-bs-dismiss attributes
        $content = str_replace('data-bs-dismiss="modal"', '', $content);
        
        $this->stats['modals_converted'] += $localCount;
        return $content;
    }
    
    private function convertAlerts($content, $file) {
        $localCount = 0;
        
        $alertMappings = [
            'alert-success' => 'variant="success"',
            'alert-danger' => 'variant="danger"',
            'alert-warning' => 'variant="warning"',
            'alert-info' => 'variant="info"',
            'alert-primary' => '',
            'alert-secondary' => 'variant="subtle"',
        ];
        
        $content = preg_replace_callback(
            '/<div([^>]*?)class="([^"]*?)alert\s+alert-([^"\s]+)([^"]*?)"([^>]*?)>(.*?)<\/div>/s',
            function($matches) use (&$localCount, $alertMappings) {
                $beforeClass = $matches[1];
                $classPrefix = $matches[2];
                $alertType = $matches[3];
                $classSuffix = $matches[4];
                $afterClass = $matches[5];
                $alertContent = $matches[6];
                
                $alertClass = 'alert-' . $alertType;
                $variant = isset($alertMappings[$alertClass]) ? $alertMappings[$alertClass] : '';
                
                // Check if dismissible
                $isDismissible = strpos($classPrefix . $classSuffix, 'alert-dismissible') !== false;
                
                $localCount++;
                
                $fluxCallout = '<flux:callout ' . $variant;
                
                if ($isDismissible) {
                    $fluxCallout .= ' dismissible';
                }
                
                // Clean up additional classes
                $additionalClasses = trim($classPrefix . ' ' . $classSuffix);
                $additionalClasses = preg_replace('/\s*alert\s+alert-\S+\s*/', ' ', $additionalClasses);
                $additionalClasses = preg_replace('/\s*alert-\S+\s*/', ' ', $additionalClasses);
                $additionalClasses = trim($additionalClasses);
                
                if ($additionalClasses) {
                    $fluxCallout .= ' class="' . $additionalClasses . '"';
                }
                
                $fluxCallout .= '>' . $alertContent . '</flux:callout>';
                
                return $fluxCallout;
            },
            $content
        );
        
        $this->stats['alerts_converted'] += $localCount;
        return $content;
    }
    
    private function convertForms($content, $file) {
        $localCount = 0;
        
        // Convert form-select to flux:select
        $content = preg_replace_callback(
            '/<select([^>]*?)class="([^"]*?)form-select([^"]*?)"([^>]*?)>(.*?)<\/select>/s',
            function($matches) use (&$localCount) {
                $beforeClass = $matches[1];
                $classPrefix = $matches[2];
                $classSuffix = $matches[3];
                $afterClass = $matches[4];
                $selectContent = $matches[5];
                
                // Clean up classes
                $additionalClasses = trim($classPrefix . ' ' . $classSuffix);
                $additionalClasses = str_replace('form-select', '', $additionalClasses);
                $additionalClasses = trim($additionalClasses);
                
                // Extract attributes
                $attrs = $beforeClass . ' ' . $afterClass;
                if ($additionalClasses) {
                    $attrs .= ' class="' . $additionalClasses . '"';
                }
                
                $localCount++;
                
                return '<flux:select' . $attrs . '>' . $selectContent . '</flux:select>';
            },
            $content
        );
        
        // Convert form-check (checkboxes and radios)
        $content = preg_replace_callback(
            '/<div[^>]*?class="[^"]*?form-check[^"]*?"[^>]*?>(.*?)<\/div>/s',
            function($matches) use (&$localCount) {
                $checkContent = $matches[1];
                
                if (strpos($checkContent, 'type="checkbox"') !== false) {
                    // Extract checkbox attributes
                    if (preg_match('/<input([^>]*?)type="checkbox"([^>]*?)>/', $checkContent, $inputMatch)) {
                        $attrs = $inputMatch[1] . ' ' . $inputMatch[2];
                        $label = '';
                        
                        if (preg_match('/<label[^>]*?>(.*?)<\/label>/s', $checkContent, $labelMatch)) {
                            $label = strip_tags($labelMatch[1]);
                        }
                        
                        $localCount++;
                        return '<flux:checkbox' . $attrs . '>' . $label . '</flux:checkbox>';
                    }
                } elseif (strpos($checkContent, 'type="radio"') !== false) {
                    // Extract radio attributes
                    if (preg_match('/<input([^>]*?)type="radio"([^>]*?)>/', $checkContent, $inputMatch)) {
                        $attrs = $inputMatch[1] . ' ' . $inputMatch[2];
                        $label = '';
                        
                        if (preg_match('/<label[^>]*?>(.*?)<\/label>/s', $checkContent, $labelMatch)) {
                            $label = strip_tags($labelMatch[1]);
                        }
                        
                        $localCount++;
                        return '<flux:radio' . $attrs . '>' . $label . '</flux:radio>';
                    }
                }
                
                return $matches[0]; // Return unchanged if not recognized
            },
            $content
        );
        
        // Convert textarea with form-control
        $content = preg_replace_callback(
            '/<textarea([^>]*?)class="([^"]*?)form-control([^"]*?)"([^>]*?)>(.*?)<\/textarea>/s',
            function($matches) use (&$localCount) {
                $beforeClass = $matches[1];
                $classPrefix = $matches[2];
                $classSuffix = $matches[3];
                $afterClass = $matches[4];
                $textContent = $matches[5];
                
                // Clean up classes
                $additionalClasses = trim($classPrefix . ' ' . $classSuffix);
                $additionalClasses = str_replace('form-control', '', $additionalClasses);
                $additionalClasses = trim($additionalClasses);
                
                // Extract attributes
                $attrs = $beforeClass . ' ' . $afterClass;
                if ($additionalClasses) {
                    $attrs .= ' class="' . $additionalClasses . '"';
                }
                
                $localCount++;
                
                return '<flux:textarea' . $attrs . '>' . $textContent . '</flux:textarea>';
            },
            $content
        );
        
        $this->stats['forms_converted'] += $localCount;
        return $content;
    }
    
    private function convertDataAttributes($content, $file) {
        $localCount = 0;
        
        // Convert Bootstrap tooltips
        $content = preg_replace_callback(
            '/data-bs-toggle="tooltip"\s+data-bs-placement="([^"]+)"\s+title="([^"]+)"/',
            function($matches) use (&$localCount) {
                $placement = $matches[1];
                $title = $matches[2];
                $localCount++;
                
                // This needs to be wrapped around the element, so we'll add a marker
                return 'data-flux-tooltip="' . $title . '" data-flux-tooltip-position="' . $placement . '"';
            },
            $content
        );
        
        // Convert Bootstrap dropdowns
        $content = preg_replace_callback(
            '/data-bs-toggle="dropdown"/',
            function($matches) use (&$localCount) {
                $localCount++;
                return ''; // Flux dropdowns don't need this attribute
            },
            $content
        );
        
        // Remove other Bootstrap data attributes
        $content = preg_replace('/data-bs-[^=]+="[^"]*"/', '', $content);
        
        $this->stats['data_attrs_converted'] += $localCount;
        return $content;
    }
    
    private function convertTables($content, $file) {
        // Convert Bootstrap table classes to Tailwind
        $tableClassMappings = [
            'table' => 'min-w-full divide-y divide-gray-200 dark:divide-gray-700',
            'table-striped' => '',
            'table-bordered' => 'border border-gray-200 dark:border-gray-700',
            'table-hover' => '',
            'table-sm' => 'text-sm',
            'table-responsive' => 'overflow-x-auto',
        ];
        
        foreach ($tableClassMappings as $bsClass => $twClass) {
            if ($twClass) {
                $content = str_replace('class="' . $bsClass . '"', 'class="' . $twClass . '"', $content);
                $content = str_replace('class="' . $bsClass . ' ', 'class="' . $twClass . ' ', $content);
            } else {
                $content = str_replace(' ' . $bsClass, '', $content);
            }
        }
        
        return $content;
    }
    
    private function convertCards($content, $file) {
        // Most cards should already be converted to Flux, but let's check for stragglers
        $content = preg_replace_callback(
            '/<div([^>]*?)class="([^"]*?)card([^"]*?)"([^>]*?)>(.*?)<\/div><!-- end card -->/s',
            function($matches) {
                $cardContent = $matches[5];
                
                $fluxCard = '<flux:card>' . "\n";
                
                // Extract header
                if (preg_match('/<div[^>]*?class="[^"]*?card-header[^"]*?"[^>]*?>(.*?)<\/div>/s', $cardContent, $headerMatch)) {
                    $fluxCard .= '    <flux:card.header>' . "\n";
                    $fluxCard .= '        <flux:card.title>' . strip_tags($headerMatch[1]) . '</flux:card.title>' . "\n";
                    $fluxCard .= '    </flux:card.header>' . "\n";
                }
                
                // Extract body
                if (preg_match('/<div[^>]*?class="[^"]*?card-body[^"]*?"[^>]*?>(.*?)<\/div>/s', $cardContent, $bodyMatch)) {
                    $fluxCard .= '    <flux:card.body>' . "\n";
                    $fluxCard .= '        ' . trim($bodyMatch[1]) . "\n";
                    $fluxCard .= '    </flux:card.body>' . "\n";
                }
                
                $fluxCard .= '</flux:card>';
                
                return $fluxCard;
            },
            $content
        );
        
        return $content;
    }
    
    private function convertBadges($content, $file) {
        $badgeMappings = [
            'badge-primary' => 'variant="primary"',
            'badge-secondary' => 'variant="subtle"',
            'badge-success' => 'color="green"',
            'badge-danger' => 'color="red"',
            'badge-warning' => 'color="amber"',
            'badge-info' => 'color="sky"',
            'badge-light' => 'variant="subtle"',
            'badge-dark' => 'variant="filled"',
        ];
        
        $content = preg_replace_callback(
            '/<span([^>]*?)class="([^"]*?)badge\s+badge-([^"\s]+)([^"]*?)"([^>]*?)>(.*?)<\/span>/',
            function($matches) use ($badgeMappings) {
                $badgeType = 'badge-' . $matches[3];
                $badgeContent = $matches[6];
                
                $variant = isset($badgeMappings[$badgeType]) ? $badgeMappings[$badgeType] : '';
                
                return '<flux:badge ' . $variant . '>' . $badgeContent . '</flux:badge>';
            },
            $content
        );
        
        return $content;
    }
    
    private function cleanupBootstrapClasses($content, $file) {
        // Remove common Bootstrap utility classes and replace with Tailwind
        $utilityMappings = [
            'text-center' => 'text-center',
            'text-left' => 'text-left',
            'text-right' => 'text-right',
            'text-muted' => 'text-gray-500',
            'text-primary' => 'text-blue-600',
            'text-success' => 'text-green-600',
            'text-danger' => 'text-red-600',
            'text-warning' => 'text-yellow-600',
            'text-info' => 'text-sky-600',
            'd-none' => 'hidden',
            'd-block' => 'block',
            'd-inline' => 'inline',
            'd-inline-block' => 'inline-block',
            'd-flex' => 'flex',
            'justify-content-center' => 'justify-center',
            'justify-content-between' => 'justify-between',
            'align-items-center' => 'items-center',
            'mt-1' => 'mt-1',
            'mt-2' => 'mt-2',
            'mt-3' => 'mt-3',
            'mt-4' => 'mt-4',
            'mt-5' => 'mt-5',
            'mb-1' => 'mb-1',
            'mb-2' => 'mb-2',
            'mb-3' => 'mb-3',
            'mb-4' => 'mb-4',
            'mb-5' => 'mb-5',
            'p-1' => 'p-1',
            'p-2' => 'p-2',
            'p-3' => 'p-3',
            'p-4' => 'p-4',
            'p-5' => 'p-5',
            'row' => 'grid grid-cols-12',
            'col' => 'col-span-12',
            'col-md-6' => 'col-span-12 md:col-span-6',
            'col-md-4' => 'col-span-12 md:col-span-4',
            'col-md-3' => 'col-span-12 md:col-span-3',
            'col-md-8' => 'col-span-12 md:col-span-8',
            'col-lg-6' => 'col-span-12 lg:col-span-6',
            'col-lg-4' => 'col-span-12 lg:col-span-4',
            'col-lg-3' => 'col-span-12 lg:col-span-3',
            'col-lg-8' => 'col-span-12 lg:col-span-8',
        ];
        
        foreach ($utilityMappings as $bsClass => $twClass) {
            // Only replace when it's a standalone class or with spaces around it
            $content = preg_replace(
                '/\b' . preg_quote($bsClass, '/') . '\b/',
                $twClass,
                $content
            );
        }
        
        // Remove container-fluid and replace with Tailwind equivalent
        $content = str_replace('container-fluid', 'w-full px-4', $content);
        
        // Remove any remaining btn- classes that weren't caught
        $content = preg_replace('/\bbtn-\S+/', '', $content);
        
        return $content;
    }
    
    private function printSummary() {
        echo "\n=====================================\n";
        echo "Migration Complete!\n";
        echo "=====================================\n\n";
        
        echo "Statistics:\n";
        echo "  Files processed: {$this->stats['files_processed']}\n";
        echo "  Files modified: {$this->stats['files_modified']}\n";
        echo "  Buttons converted: {$this->stats['buttons_converted']}\n";
        echo "  Modals converted: {$this->stats['modals_converted']}\n";
        echo "  Alerts converted: {$this->stats['alerts_converted']}\n";
        echo "  Forms converted: {$this->stats['forms_converted']}\n";
        echo "  Data attributes converted: {$this->stats['data_attrs_converted']}\n";
        
        if (count($this->filesModified) > 0) {
            echo "\nModified files:\n";
            foreach ($this->filesModified as $file) {
                echo "  - " . str_replace(__DIR__ . '/', '', $file) . "\n";
            }
            
            echo "\nBackup files created with suffix: {$this->backupSuffix}\n";
            echo "\nIMPORTANT: Please review the converted files and test your application.\n";
            echo "Some complex components may require manual adjustment.\n";
        }
    }
}

// Run the migration
$migrator = new FluxMigrationComplete();
$migrator->run();