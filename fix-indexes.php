#!/usr/bin/env php
<?php

// Script to fix overly long index names in migration files
$migrationPath = 'database/migrations/';
$files = glob($migrationPath . '*.php');

$replacements = [
    // Specific problematic indexes
    "table->index(['company_id', 'projection_type', 'period_start']);" => "table->index(['company_id', 'projection_type', 'period_start'], 'cashflow_company_proj_period_idx');",
    "table->index(['company_id', 'alert_type', 'severity', 'acknowledged']);" => "table->index(['company_id', 'alert_type', 'severity', 'acknowledged'], 'alerts_company_type_sev_ack_idx');",
    "table->index(['device_id', 'monitoring_enabled', 'last_heartbeat']);" => "table->index(['device_id', 'monitoring_enabled', 'last_heartbeat'], 'device_monitor_heartbeat_idx');",
    "table->index(['integration_id', 'status', 'last_executed_at']);" => "table->index(['integration_id', 'status', 'last_executed_at'], 'integration_status_exec_idx');",
    "table->index(['company_id', 'template_name', 'is_active']);" => "table->index(['company_id', 'template_name', 'is_active'], 'company_template_active_idx');",
    "table->index(['company_id', 'client_id', 'alert_type', 'created_at']);" => "table->index(['company_id', 'client_id', 'alert_type', 'created_at'], 'rmm_alert_company_client_type_idx');",
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    foreach ($replacements as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }
    
    // Handle generic long indexes by adding custom names
    $content = preg_replace_callback(
        '/\$table->index\(\[(.*?)\]\);/',
        function($matches) {
            $fields = $matches[1];
            $fieldCount = substr_count($fields, ',') + 1;
            
            // Only process if it has 3+ fields (likely to be long)
            if ($fieldCount >= 3) {
                // Generate a short index name
                $fieldNames = array_map('trim', explode(',', str_replace("'", '', $fields)));
                $shortName = '';
                
                foreach ($fieldNames as $field) {
                    $shortName .= substr($field, 0, 4) . '_';
                }
                
                $shortName = rtrim($shortName, '_') . '_idx';
                
                // Make sure it's not too long
                if (strlen($shortName) > 30) {
                    $shortName = substr($shortName, 0, 27) . '_idx';
                }
                
                return '$table->index([' . $matches[1] . '], \'' . $shortName . '\');';
            }
            
            return $matches[0];
        },
        $content
    );
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "Fixed indexes in: " . basename($file) . "\n";
    }
}

echo "Index fix complete!\n";