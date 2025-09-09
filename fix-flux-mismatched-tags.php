#!/usr/bin/env php
<?php

/**
 * Fix mismatched Flux component tags in blade templates
 * This script fixes cases where HTML opening tags have Flux closing tags
 * Example: <button>...</flux:button> becomes <button>...</button>
 */

$viewsPath = __DIR__ . '/resources/views';
$fixedCount = 0;
$filesFixed = [];

// Common HTML tags that were incorrectly converted
$tagsToFix = [
    'button',
    'label', 
    'select',
    'input',
    'textarea',
    'div',
    'span',
    'a',
    'form',
    'option'
];

function fixMismatchedTags($content, $file) {
    global $tagsToFix, $fixedCount;
    $originalContent = $content;
    $localFixCount = 0;
    
    foreach ($tagsToFix as $tag) {
        // Pattern to find HTML opening tag with Flux closing tag
        // This handles cases like <button ...>content</flux:button>
        $pattern = '/(<' . $tag . '(?:\s[^>]*)?>)(.*?)(<\/flux:' . $tag . '>)/s';
        
        $content = preg_replace_callback($pattern, function($matches) use ($tag, &$localFixCount, $file) {
            $localFixCount++;
            echo "  Fixed mismatched <{$tag}>...</flux:{$tag}> in {$file}\n";
            // Replace flux closing tag with regular HTML closing tag
            return $matches[1] . $matches[2] . '</' . $tag . '>';
        }, $content);
    }
    
    // Also check for proper Flux components that should have matching tags
    // Find all flux opening tags
    preg_match_all('/<flux:([a-z\-\.]+)(?:\s[^>]*)?>/', $content, $fluxOpens);
    
    foreach ($fluxOpens[1] as $fluxTag) {
        // Check if there's a matching closing tag
        $openPattern = '/<flux:' . preg_quote($fluxTag, '/') . '(?:\s[^>]*)?>/';
        $closePattern = '/<\/flux:' . preg_quote($fluxTag, '/') . '>/';
        
        $openCount = preg_match_all($openPattern, $content, $openMatches);
        $closeCount = preg_match_all($closePattern, $content, $closeMatches);
        
        if ($openCount != $closeCount) {
            echo "  WARNING: Unmatched flux:{$fluxTag} tags in {$file} (opens: {$openCount}, closes: {$closeCount})\n";
        }
    }
    
    // Check for orphaned flux closing tags (closing tags without opening tags)
    preg_match_all('/<\/flux:([a-z\-\.]+)>/', $content, $fluxCloses);
    
    foreach ($fluxCloses[1] as $fluxTag) {
        $openPattern = '/<flux:' . preg_quote($fluxTag, '/') . '(?:\s[^>]*)?>/';
        
        if (!preg_match($openPattern, $content)) {
            // This is an orphaned closing tag - likely should be HTML
            echo "  WARNING: Orphaned </flux:{$fluxTag}> tag found in {$file}\n";
            
            // Try to find matching HTML opening tag
            $htmlOpenPattern = '/<' . preg_quote($fluxTag, '/') . '(?:\s[^>]*)?>/';
            if (preg_match($htmlOpenPattern, $content)) {
                // Found HTML opening tag, fix the closing tag
                $content = str_replace('</flux:' . $fluxTag . '>', '</' . $fluxTag . '>', $content);
                $localFixCount++;
                echo "  Fixed orphaned </flux:{$fluxTag}> -> </{$fluxTag}> in {$file}\n";
            }
        }
    }
    
    $fixedCount += $localFixCount;
    return $content;
}

function processFile($filePath) {
    global $filesFixed;
    
    if (!file_exists($filePath) || !is_readable($filePath)) {
        return;
    }
    
    $content = file_get_contents($filePath);
    $originalContent = $content;
    
    // Skip backup files
    if (strpos($filePath, '.bootstrap-backup-') !== false) {
        return;
    }
    
    $fixedContent = fixMismatchedTags($content, $filePath);
    
    if ($fixedContent !== $originalContent) {
        // Create backup
        $backupPath = $filePath . '.flux-fix-backup-' . date('Ymd-His');
        file_put_contents($backupPath, $originalContent);
        
        // Write fixed content
        file_put_contents($filePath, $fixedContent);
        $filesFixed[] = $filePath;
        echo "Fixed: {$filePath}\n";
    }
}

function scanDirectory($dir) {
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            scanDirectory($path);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php' && strpos($path, '.blade.php') !== false) {
            processFile($path);
        }
    }
}

echo "Starting Flux component tag mismatch fix...\n";
echo "=========================================\n\n";

// Process all blade files
scanDirectory($viewsPath);

echo "\n=========================================\n";
echo "Fix completed!\n";
echo "Total fixes applied: {$fixedCount}\n";
echo "Files modified: " . count($filesFixed) . "\n";

if (count($filesFixed) > 0) {
    echo "\nFiles that were fixed:\n";
    foreach ($filesFixed as $file) {
        echo "  - " . str_replace(__DIR__ . '/', '', $file) . "\n";
    }
    echo "\nBackup files created with .flux-fix-backup-* suffix\n";
}