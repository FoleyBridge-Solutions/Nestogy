#!/usr/bin/env node

/**
 * Bootstrap to Tailwind Conversion Script
 * This script automates the conversion of Bootstrap classes to Tailwind in Blade templates
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { dirname } from 'path';
import { classMap } from '../resources/js/bootstrap-to-tailwind-map.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

// Extended mapping for complex patterns
const complexPatterns = [
    // Container patterns
    { pattern: /class="([^"]*\b)container-fluid(\b[^"]*)"/, replacement: 'class="$1w-full px-4$2"' },
    { pattern: /class="([^"]*\b)container(\b[^"]*)"/, replacement: 'class="$1container mx-auto px-4$2"' },
    
    // Row and column patterns
    { pattern: /class="([^"]*\b)row(\b[^"]*)"/, replacement: 'class="$1flex flex-wrap -mx-4$2"' },
    { pattern: /class="([^"]*\b)col-md-(\d+)(\b[^"]*)"/, replacement: (match, p1, p2, p3) => {
        const widthMap = {
            '1': 'md:w-1/12',
            '2': 'md:w-1/6',
            '3': 'md:w-1/4',
            '4': 'md:w-1/3',
            '5': 'md:w-5/12',
            '6': 'md:w-1/2',
            '7': 'md:w-7/12',
            '8': 'md:w-2/3',
            '9': 'md:w-3/4',
            '10': 'md:w-5/6',
            '11': 'md:w-11/12',
            '12': 'md:w-full'
        };
        return `class="${p1}${widthMap[p2] || `md:w-${p2}/12`} px-4${p3}"`;
    }},
    
    // Display utilities
    { pattern: /class="([^"]*\b)d-flex(\b[^"]*)"/, replacement: 'class="$1flex$2"' },
    { pattern: /class="([^"]*\b)d-none(\b[^"]*)"/, replacement: 'class="$1hidden$2"' },
    { pattern: /class="([^"]*\b)d-block(\b[^"]*)"/, replacement: 'class="$1block$2"' },
    { pattern: /class="([^"]*\b)d-inline-block(\b[^"]*)"/, replacement: 'class="$1inline-block$2"' },
    
    // Flexbox utilities
    { pattern: /class="([^"]*\b)justify-content-between(\b[^"]*)"/, replacement: 'class="$1justify-between$2"' },
    { pattern: /class="([^"]*\b)justify-content-center(\b[^"]*)"/, replacement: 'class="$1justify-center$2"' },
    { pattern: /class="([^"]*\b)align-items-center(\b[^"]*)"/, replacement: 'class="$1items-center$2"' },
    { pattern: /class="([^"]*\b)flex-column(\b[^"]*)"/, replacement: 'class="$1flex-col$2"' },
    
    // Button patterns
    { pattern: /class="([^"]*\b)btn btn-primary(\b[^"]*)"/, replacement: 'class="$1inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500$2"' },
    { pattern: /class="([^"]*\b)btn btn-secondary(\b[^"]*)"/, replacement: 'class="$1inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500$2"' },
    { pattern: /class="([^"]*\b)btn btn-success(\b[^"]*)"/, replacement: 'class="$1inline-flex items-center px-4 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500$2"' },
    { pattern: /class="([^"]*\b)btn btn-danger(\b[^"]*)"/, replacement: 'class="$1inline-flex items-center px-4 py-2 bg-red-600 text-white font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500$2"' },
    
    // Card patterns
    { pattern: /class="([^"]*\b)card(\b[^"]*)"/, replacement: 'class="$1bg-white rounded-lg shadow-md overflow-hidden$2"' },
    { pattern: /class="([^"]*\b)card-body(\b[^"]*)"/, replacement: 'class="$1p-6$2"' },
    { pattern: /class="([^"]*\b)card-header(\b[^"]*)"/, replacement: 'class="$1px-6 py-4 border-b border-gray-200 bg-gray-50$2"' },
    
    // Form patterns
    { pattern: /class="([^"]*\b)form-control(\b[^"]*)"/, replacement: 'class="$1block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm$2"' },
    { pattern: /class="([^"]*\b)form-select(\b[^"]*)"/, replacement: 'class="$1block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm$2"' },
    { pattern: /class="([^"]*\b)form-label(\b[^"]*)"/, replacement: 'class="$1block text-sm font-medium text-gray-700 mb-1$2"' },
    
    // Table patterns
    { pattern: /class="([^"]*\b)table(\b[^"]*)"/, replacement: 'class="$1min-w-full divide-y divide-gray-200$2"' },
    { pattern: /class="([^"]*\b)table-responsive(\b[^"]*)"/, replacement: 'class="$1overflow-x-auto$2"' },
    { pattern: /class="([^"]*\b)table-hover(\b[^"]*)"/, replacement: 'class="$1[&>tbody>tr:hover]:bg-gray-100$2"' },
    
    // Text utilities
    { pattern: /class="([^"]*\b)text-muted(\b[^"]*)"/, replacement: 'class="$1text-gray-600$2"' },
    { pattern: /class="([^"]*\b)text-primary(\b[^"]*)"/, replacement: 'class="$1text-blue-600$2"' },
    { pattern: /class="([^"]*\b)text-danger(\b[^"]*)"/, replacement: 'class="$1text-red-600$2"' },
    { pattern: /class="([^"]*\b)text-success(\b[^"]*)"/, replacement: 'class="$1text-green-600$2"' },
    { pattern: /class="([^"]*\b)text-center(\b[^"]*)"/, replacement: 'class="$1text-center$2"' },
    
    // Background utilities
    { pattern: /class="([^"]*\b)bg-primary(\b[^"]*)"/, replacement: 'class="$1bg-blue-600$2"' },
    { pattern: /class="([^"]*\b)bg-secondary(\b[^"]*)"/, replacement: 'class="$1bg-gray-600$2"' },
    { pattern: /class="([^"]*\b)bg-light(\b[^"]*)"/, replacement: 'class="$1bg-gray-100$2"' },
    { pattern: /class="([^"]*\b)bg-dark(\b[^"]*)"/, replacement: 'class="$1bg-gray-900$2"' },
    { pattern: /class="([^"]*\b)bg-white(\b[^"]*)"/, replacement: 'class="$1bg-white$2"' },
    
    // Spacing patterns (Bootstrap to Tailwind)
    { pattern: /class="([^"]*\b)mb-0(\b[^"]*)"/, replacement: 'class="$1mb-0$2"' },
    { pattern: /class="([^"]*\b)mb-1(\b[^"]*)"/, replacement: 'class="$1mb-1$2"' },
    { pattern: /class="([^"]*\b)mb-2(\b[^"]*)"/, replacement: 'class="$1mb-2$2"' },
    { pattern: /class="([^"]*\b)mb-3(\b[^"]*)"/, replacement: 'class="$1mb-3$2"' },
    { pattern: /class="([^"]*\b)mb-4(\b[^"]*)"/, replacement: 'class="$1mb-4$2"' },
    { pattern: /class="([^"]*\b)mb-5(\b[^"]*)"/, replacement: 'class="$1mb-6$2"' },
    { pattern: /class="([^"]*\b)mt-(\d)(\b[^"]*)"/, replacement: 'class="$1mt-$2$3"' },
    { pattern: /class="([^"]*\b)ms-(\d)(\b[^"]*)"/, replacement: 'class="$1ml-$2$3"' },
    { pattern: /class="([^"]*\b)me-(\d)(\b[^"]*)"/, replacement: 'class="$1mr-$2$3"' },
    { pattern: /class="([^"]*\b)ps-(\d)(\b[^"]*)"/, replacement: 'class="$1pl-$2$3"' },
    { pattern: /class="([^"]*\b)pe-(\d)(\b[^"]*)"/, replacement: 'class="$1pr-$2$3"' },
    
    // Border patterns
    { pattern: /class="([^"]*\b)border-0(\b[^"]*)"/, replacement: 'class="$1border-0$2"' },
    { pattern: /class="([^"]*\b)border(\b[^"]*)"/, replacement: 'class="$1border$2"' },
    { pattern: /class="([^"]*\b)rounded-circle(\b[^"]*)"/, replacement: 'class="$1rounded-full$2"' },
    { pattern: /class="([^"]*\b)rounded(\b[^"]*)"/, replacement: 'class="$1rounded$2"' },
    
    // Shadow patterns
    { pattern: /class="([^"]*\b)shadow-sm(\b[^"]*)"/, replacement: 'class="$1shadow-sm$2"' },
    { pattern: /class="([^"]*\b)shadow-lg(\b[^"]*)"/, replacement: 'class="$1shadow-lg$2"' },
    { pattern: /class="([^"]*\b)shadow(\b[^"]*)"/, replacement: 'class="$1shadow$2"' },
    
    // Alert patterns
    { pattern: /class="([^"]*\b)alert alert-success(\b[^"]*)"/, replacement: 'class="$1px-4 py-3 rounded bg-green-100 border border-green-400 text-green-700$2"' },
    { pattern: /class="([^"]*\b)alert alert-danger(\b[^"]*)"/, replacement: 'class="$1px-4 py-3 rounded bg-red-100 border border-red-400 text-red-700$2"' },
    { pattern: /class="([^"]*\b)alert alert-warning(\b[^"]*)"/, replacement: 'class="$1px-4 py-3 rounded bg-yellow-100 border border-yellow-400 text-yellow-700$2"' },
    { pattern: /class="([^"]*\b)alert alert-info(\b[^"]*)"/, replacement: 'class="$1px-4 py-3 rounded bg-cyan-100 border border-cyan-400 text-cyan-700$2"' },
    
    // Badge patterns
    { pattern: /class="([^"]*\b)badge badge-primary(\b[^"]*)"/, replacement: 'class="$1inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800$2"' },
    { pattern: /class="([^"]*\b)badge badge-success(\b[^"]*)"/, replacement: 'class="$1inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800$2"' },
    { pattern: /class="([^"]*\b)badge badge-danger(\b[^"]*)"/, replacement: 'class="$1inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800$2"' },
    
    // Modal data attributes to Alpine.js
    { pattern: /data-bs-toggle="modal"/g, replacement: '@click="$dispatch(\'open-modal\', \'modal-id\')"' },
    { pattern: /data-bs-dismiss="modal"/g, replacement: '@click="$dispatch(\'close-modal\')"' },
    { pattern: /data-bs-toggle="tooltip"/g, replacement: 'x-data x-tooltip' },
    { pattern: /data-bs-toggle="popover"/g, replacement: 'x-data x-popover' },
    { pattern: /data-bs-toggle="dropdown"/g, replacement: 'x-data="{ open: false }" @click="open = !open"' },
];

/**
 * Process a single file
 */
function processFile(filePath) {
    try {
        let content = fs.readFileSync(filePath, 'utf8');
        let modified = false;
        
        // Apply complex pattern replacements
        for (const { pattern, replacement } of complexPatterns) {
            const originalContent = content;
            if (typeof replacement === 'function') {
                content = content.replace(pattern, replacement);
            } else {
                content = content.replace(pattern, replacement);
            }
            if (content !== originalContent) {
                modified = true;
            }
        }
        
        // Clean up multiple spaces in class attributes
        content = content.replace(/class="([^"]*)"/g, (match, classes) => {
            const cleaned = classes.replace(/\s+/g, ' ').trim();
            return `class="${cleaned}"`;
        });
        
        if (modified) {
            // Create backup
            const backupPath = filePath + '.bootstrap-backup';
            if (!fs.existsSync(backupPath)) {
                fs.copyFileSync(filePath, backupPath);
            }
            
            // Write converted file
            fs.writeFileSync(filePath, content);
            return true;
        }
        
        return false;
    } catch (error) {
        console.error(`Error processing ${filePath}:`, error.message);
        return false;
    }
}

/**
 * Recursively process all Blade files in a directory
 */
function processDirectory(dirPath, stats = { processed: 0, modified: 0, errors: 0 }) {
    const files = fs.readdirSync(dirPath);
    
    for (const file of files) {
        const fullPath = path.join(dirPath, file);
        const stat = fs.statSync(fullPath);
        
        if (stat.isDirectory()) {
            // Skip vendor and node_modules directories
            if (file !== 'vendor' && file !== 'node_modules' && file !== '.git') {
                processDirectory(fullPath, stats);
            }
        } else if (file.endsWith('.blade.php')) {
            stats.processed++;
            console.log(`Processing: ${fullPath}`);
            
            if (processFile(fullPath)) {
                stats.modified++;
                console.log(`  ✓ Modified`);
            } else {
                console.log(`  - No changes needed`);
            }
        }
    }
    
    return stats;
}

/**
 * Main execution
 */
function main() {
    const args = process.argv.slice(2);
    const targetPath = args[0] || path.join(__dirname, '..', 'resources', 'views');
    
    console.log('Bootstrap to Tailwind Conversion Script');
    console.log('========================================');
    console.log(`Target: ${targetPath}`);
    console.log('');
    
    if (!fs.existsSync(targetPath)) {
        console.error(`Error: Path does not exist: ${targetPath}`);
        process.exit(1);
    }
    
    const stats = processDirectory(targetPath);
    
    console.log('');
    console.log('Conversion Complete!');
    console.log('========================================');
    console.log(`Files processed: ${stats.processed}`);
    console.log(`Files modified: ${stats.modified}`);
    console.log(`Errors: ${stats.errors}`);
    console.log('');
    console.log('Note: Backup files created with .bootstrap-backup extension');
    console.log('Review changes and test thoroughly before deploying.');
}

// Run the script
main();

export { processFile, processDirectory };