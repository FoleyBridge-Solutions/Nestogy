<?php

/**
 * Convert Blade views to use standardized page header system
 * 
 * Usage:
 *   php scripts/convert-headers-to-standard.php --dry-run
 *   php scripts/convert-headers-to-standard.php --file=resources/views/clients/index.blade.php
 *   php scripts/convert-headers-to-standard.php --module=clients
 *   php scripts/convert-headers-to-standard.php --all
 */

class HeaderConverter
{
    private $dryRun = false;
    private $backupDir;
    private $converted = [];
    private $skipped = [];
    private $errors = [];

    public function __construct($dryRun = false)
    {
        $this->dryRun = $dryRun;
        $this->backupDir = __DIR__ . '/../storage/backups/header-conversion-' . date('Y-m-d-His');
    }

    public function convertFile($filePath)
    {
        if (!file_exists($filePath)) {
            $this->errors[] = "File not found: $filePath";
            return false;
        }

        $content = file_get_contents($filePath);
        
        // Skip if doesn't extend layouts.app
        if (!preg_match('/@extends\([\'"]layouts\.app[\'"]\)/', $content)) {
            $this->skipped[] = "$filePath - Does not extend layouts.app";
            return false;
        }

        // Skip if already converted
        if (preg_match('/\$pageTitle\s*=/', $content)) {
            $this->skipped[] = "$filePath - Already converted";
            return false;
        }

        // Skip auth, errors, emails, pdf files
        if (preg_match('/\/(auth|errors|emails|pdf)\//', $filePath)) {
            $this->skipped[] = "$filePath - Excluded directory";
            return false;
        }

        // Extract header components
        $components = $this->extractHeaderComponents($content);
        
        if (empty($components['title'])) {
            $this->skipped[] = "$filePath - No header found";
            return false;
        }

        // Generate new content
        $newContent = $this->replaceHeaderWithStandard($content, $components);

        if ($this->dryRun) {
            echo "\n========================================\n";
            echo "File: $filePath\n";
            echo "========================================\n";
            echo "Title: {$components['title']}\n";
            if ($components['subtitle']) {
                echo "Subtitle: {$components['subtitle']}\n";
            }
            if (!empty($components['actions'])) {
                echo "Actions: " . count($components['actions']) . "\n";
                foreach ($components['actions'] as $action) {
                    echo "  - {$action['label']}\n";
                }
            }
            $this->converted[] = $filePath;
            return true;
        }

        // Backup original
        $this->backupFile($filePath);

        // Write new content
        file_put_contents($filePath, $newContent);
        $this->converted[] = $filePath;
        
        echo "✓ Converted: $filePath\n";
        return true;
    }

    private function extractHeaderComponents($content)
    {
        $title = '';
        $subtitle = '';
        $actions = [];

        // Extract title - various patterns
        if (preg_match('/<h1[^>]*class="[^"]*(?:text-2xl|text-3xl)[^"]*"[^>]*>\s*(.*?)\s*<\/h1>/s', $content, $matches)) {
            $title = trim(strip_tags($matches[1]));
        } elseif (preg_match('/<flux:heading(?:\s+size="xl")?\s*>\s*(.*?)\s*<\/flux:heading>/s', $content, $matches)) {
            // Only match page-level headings, not card headings
            $beforeMatch = substr($content, 0, strpos($content, $matches[0]));
            // If heading is not inside a card, it's likely a page header
            if (strpos($beforeMatch, '<flux:card>') === false || 
                strrpos($beforeMatch, '</flux:card>') > strrpos($beforeMatch, '<flux:card>')) {
                $title = trim(strip_tags($matches[1]));
            }
        }

        // Extract subtitle - various patterns
        if (preg_match('/<p[^>]*class="[^"]*text-sm[^"]*text-gray-500[^"]*"[^>]*>\s*(.*?)\s*<\/p>/s', $content, $matches)) {
            $subtitle = trim(strip_tags($matches[1]));
        } elseif (preg_match('/<flux:text[^>]*>\s*(.*?)\s*<\/flux:text>/s', $content, $matches)) {
            $potentialSubtitle = trim(strip_tags($matches[1]));
            // Only use if it's near the title
            if ($title && strlen($potentialSubtitle) < 200) {
                $subtitle = $potentialSubtitle;
            }
        } elseif (preg_match('/<flux:subheading[^>]*>\s*(.*?)\s*<\/flux:subheading>/s', $content, $matches)) {
            $potentialSubtitle = trim(strip_tags($matches[1]));
            if (strlen($potentialSubtitle) < 200) {
                $subtitle = $potentialSubtitle;
            }
        }

        // Extract action buttons
        $actions = $this->extractActions($content);

        return compact('title', 'subtitle', 'actions');
    }

    private function extractActions($content)
    {
        $actions = [];

        // Pattern 1: Standard anchor tags with button styling
        preg_match_all('/<a\s+href="([^"]*)"[^>]*class="[^"]*(?:bg-blue-600|btn-primary|inline-flex items-center px-4 py-2)[^"]*"[^>]*>(.*?)<\/a>/s', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $href = $match[1];
            $label = trim(strip_tags($match[2]));
            
            // Determine icon from content or label
            $icon = $this->determineIcon($match[2], $label);
            $variant = strpos($match[0], 'bg-blue-600') !== false ? 'primary' : 'ghost';
            
            if ($label && !in_array(strtolower($label), ['', 'new ticket', 'create invoice'])) {
                $actions[] = [
                    'label' => $label,
                    'href' => $href,
                    'icon' => $icon,
                    'variant' => $variant
                ];
            }
        }

        // Pattern 2: FluxUI buttons
        preg_match_all('/<flux:button\s+href="([^"]*)"[^>]*variant="([^"]*)"[^>]*(?:icon="([^"]*)")?[^>]*>(.*?)<\/flux:button>/s', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $href = $match[1];
            $variant = $match[2];
            $icon = $match[3] ?? '';
            $label = trim(strip_tags($match[4]));
            
            if (!$icon) {
                $icon = $this->determineIcon($match[4], $label);
            }
            
            $actions[] = [
                'label' => $label,
                'href' => $href,
                'icon' => $icon,
                'variant' => $variant
            ];
        }

        return $actions;
    }

    private function determineIcon($html, $label)
    {
        // Check for existing SVG patterns
        if (preg_match('/M12 4v16m8-8H4/', $html)) return 'plus';
        if (preg_match('/M15\.232 5\.232/', $html)) return 'pencil';
        if (preg_match('/M10 19l-7-7m0 0l7-7/', $html)) return 'arrow-left';
        if (preg_match('/M4 16v1a3 3 0 003 3h10/', $html)) return 'arrow-up-tray';
        if (preg_match('/M4 16v1a3 3 0 003 3h10.*M8 8m4-4v12/', $html)) return 'arrow-down-tray';
        
        // Check for Flux icons
        if (preg_match('/<flux:icon\.([a-z-]+)/', $html, $m)) return $m[1];
        
        // Determine from label
        $lower = strtolower($label);
        if (strpos($lower, 'create') !== false || strpos($lower, 'add') !== false || strpos($lower, 'new') !== false) return 'plus';
        if (strpos($lower, 'edit') !== false) return 'pencil';
        if (strpos($lower, 'delete') !== false || strpos($lower, 'remove') !== false) return 'trash';
        if (strpos($lower, 'back') !== false) return 'arrow-left';
        if (strpos($lower, 'export') !== false) return 'arrow-down-tray';
        if (strpos($lower, 'import') !== false) return 'arrow-up-tray';
        if (strpos($lower, 'view') !== false || strpos($lower, 'show') !== false) return 'eye';
        if (strpos($lower, 'download') !== false) return 'arrow-down-circle';
        
        return '';
    }

    private function replaceHeaderWithStandard($content, $components)
    {
        // Find @section('content') or @section('title') position
        $titleSectionPos = strpos($content, "@section('title'");
        $contentSectionPos = strpos($content, "@section('content')");
        
        // Build the PHP block
        $phpBlock = "\n@php\n";
        $phpBlock .= "\$pageTitle = " . $this->formatPhpValue($components['title']) . ";\n";
        
        if (!empty($components['subtitle'])) {
            $phpBlock .= "\$pageSubtitle = " . $this->formatPhpValue($components['subtitle']) . ";\n";
        }
        
        if (!empty($components['actions'])) {
            $phpBlock .= "\$pageActions = [\n";
            foreach ($components['actions'] as $action) {
                $phpBlock .= "    [\n";
                $phpBlock .= "        'label' => " . $this->formatPhpValue($action['label']) . ",\n";
                $phpBlock .= "        'href' => " . $action['href'] . ",\n";
                if (!empty($action['icon'])) {
                    $phpBlock .= "        'icon' => '{$action['icon']}',\n";
                }
                if (!empty($action['variant']) && $action['variant'] !== 'ghost') {
                    $phpBlock .= "        'variant' => '{$action['variant']}',\n";
                }
                $phpBlock .= "    ],\n";
            }
            $phpBlock .= "];\n";
        }
        
        $phpBlock .= "@endphp\n";
        
        // Insert PHP block after title section if exists, otherwise before content section
        if ($titleSectionPos !== false) {
            $insertPos = strpos($content, "\n", $titleSectionPos + 20);
            $content = substr_replace($content, "\n" . $phpBlock, $insertPos, 0);
        } elseif ($contentSectionPos !== false) {
            $content = substr_replace($content, $phpBlock . "\n", $contentSectionPos, 0);
        }
        
        // Remove old header HTML - find and remove the header section
        $content = $this->removeOldHeader($content, $components);
        
        return $content;
    }

    private function removeOldHeader($content, $components)
    {
        // Remove wrapper divs containing headers
        $patterns = [
            // Pattern 1: div with mb-8/mb-6 containing h1
            '/<div[^>]*class="[^"]*mb-[68][^"]*"[^>]*>\s*<div[^>]*class="[^"]*flex items-center justify-between[^"]*"[^>]*>.*?<h1[^>]*>.*?<\/h1>.*?<\/div>\s*<\/div>/s',
            
            // Pattern 2: Standalone h1 with classes
            '/<h1[^>]*class="[^"]*(?:text-2xl|text-3xl)[^"]*"[^>]*>.*?<\/h1>\s*(?:<p[^>]*class="[^"]*text-sm[^"]*text-gray-500[^"]*"[^>]*>.*?<\/p>)?/s',
            
            // Pattern 3: flux:card with heading at page level
            '/<flux:card[^>]*>\s*<div[^>]*class="[^"]*flex[^"]*justify-between[^"]*"[^>]*>\s*<div>\s*<flux:heading[^>]*>.*?<\/flux:heading>.*?<\/div>.*?<\/flux:card>/s',
        ];

        foreach ($patterns as $pattern) {
            $content = preg_replace($pattern, '', $content, 1);
        }

        // Clean up extra whitespace
        $content = preg_replace("/\n{3,}/", "\n\n", $content);
        
        return $content;
    }

    private function formatPhpValue($value)
    {
        // Clean up the value
        $value = trim($value);
        
        // Check if it contains Blade directives
        if (strpos($value, '@if') !== false || strpos($value, '@else') !== false || strpos($value, '@foreach') !== false) {
            // Too complex, return as string
            return "''";
        }
        
        // Check if it contains PHP/Blade syntax
        if (preg_match('/\{\{\s*\$[\w>-]+(?:->[\w]+)*(?:\([^)]*\))?\s*\}\}/', $value)) {
            // Extract and clean the variable
            $cleaned = preg_replace('/\{\{\s*|\s*\}\}/', '', $value);
            return $cleaned;
        }
        
        // Check if it contains simple variables
        if (strpos($value, '$') !== false && strpos($value, '{{') === false) {
            return $value;
        }
        
        // Plain string
        return "'" . addslashes($value) . "'";
    }

    private function backupFile($filePath)
    {
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
        
        $relativePath = str_replace(__DIR__ . '/../', '', $filePath);
        $backupPath = $this->backupDir . '/' . $relativePath;
        $backupDir = dirname($backupPath);
        
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        copy($filePath, $backupPath);
    }

    public function convertModule($module)
    {
        $baseDir = __DIR__ . '/../resources/views/' . $module;
        
        if (!is_dir($baseDir)) {
            echo "Module directory not found: $baseDir\n";
            return;
        }

        $files = $this->findBladeFiles($baseDir);
        
        foreach ($files as $file) {
            $this->convertFile($file);
        }
    }

    public function convertAll()
    {
        $viewsDir = __DIR__ . '/../resources/views';
        $excludeDirs = ['livewire', 'components', 'layouts', 'partials', 'emails', 'pdf', 'errors', 'auth', 'vendor', 'flux.backup'];
        
        $modules = array_filter(scandir($viewsDir), function($dir) use ($viewsDir, $excludeDirs) {
            return is_dir($viewsDir . '/' . $dir) 
                && $dir !== '.' 
                && $dir !== '..'
                && !in_array($dir, $excludeDirs);
        });

        foreach ($modules as $module) {
            echo "\n=== Processing module: $module ===\n";
            $this->convertModule($module);
        }
    }

    private function findBladeFiles($dir)
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php' && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    public function printSummary()
    {
        echo "\n\n========================================\n";
        echo "CONVERSION SUMMARY\n";
        echo "========================================\n";
        echo "Converted: " . count($this->converted) . " files\n";
        echo "Skipped: " . count($this->skipped) . " files\n";
        echo "Errors: " . count($this->errors) . " files\n";
        
        if (!empty($this->errors)) {
            echo "\nErrors:\n";
            foreach ($this->errors as $error) {
                echo "  - $error\n";
            }
        }
        
        if (!$this->dryRun && !empty($this->converted)) {
            echo "\nBackup location: {$this->backupDir}\n";
        }
        
        echo "\nMode: " . ($this->dryRun ? "DRY RUN (no changes made)" : "LIVE (files modified)") . "\n";
    }
}

// Parse command line arguments
$options = getopt('', ['dry-run', 'file:', 'module:', 'all']);

$dryRun = isset($options['dry-run']);
$converter = new HeaderConverter($dryRun);

if (isset($options['file'])) {
    $converter->convertFile($options['file']);
} elseif (isset($options['module'])) {
    $converter->convertModule($options['module']);
} elseif (isset($options['all'])) {
    $converter->convertAll();
} else {
    echo "Usage:\n";
    echo "  php scripts/convert-headers-to-standard.php --dry-run --all\n";
    echo "  php scripts/convert-headers-to-standard.php --file=resources/views/clients/index.blade.php\n";
    echo "  php scripts/convert-headers-to-standard.php --module=clients\n";
    echo "  php scripts/convert-headers-to-standard.php --all\n";
    exit(1);
}

$converter->printSummary();
