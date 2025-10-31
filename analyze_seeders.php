<?php

// Script to analyze all seeders for relationship issues

$seederDir = '/opt/nestogy/database/seeders/Dev';
$files = glob($seederDir . '/*.php');

$issues = [];

foreach ($files as $file) {
    $filename = basename($file);
    if ($filename === 'DevDatabaseSeeder.php') continue;
    
    $content = file_get_contents($file);
    
    // Check for ->for() usage without relationship name
    if (preg_match_all('/->for\(\$(\w+)\)(?!\s*,)/', $content, $matches)) {
        foreach ($matches[1] as $match) {
            $issues[] = "$filename: Uses ->for(\$$match) without relationship name";
        }
    }
    
    // Check for namespace issues with models
    if (preg_match('/use App\\\\Domains\\\\(\w+)\\\\Models\\\\(\w+);/', $content, $matches)) {
        $domain = $matches[1];
        $model = $matches[2];
        
        // Check if there are mismatched namespace uses
        if (preg_match_all('/use App\\\\Domains\\\\\w+\\\\Models\\\\\w+;/', $content, $all_uses)) {
            // Look for common mismatches
            if (strpos($content, 'ClientPortalUser') !== false) {
                if (strpos($content, 'App\\Domains\\Client\\Models\\PortalNotification') !== false) {
                    $issues[] = "$filename: Possible namespace mismatch - PortalNotification";
                }
            }
        }
    }
}

echo "Total files analyzed: " . count($files) . "\n\n";
echo "Issues found: " . count($issues) . "\n\n";

foreach ($issues as $issue) {
    echo "- $issue\n";
}
