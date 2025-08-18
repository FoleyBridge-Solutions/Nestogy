#!/bin/bash

# Dark Mode Automation Script for Nestogy ERP
# This script adds dark mode variants to existing CSS classes in Blade templates

echo "🌙 Adding dark mode support to Blade templates..."

# Define high-priority file patterns
PRIORITY_FILES=(
    "resources/views/dashboard*.blade.php"
    "resources/views/*/dashboard.blade.php"
    "resources/views/*/index.blade.php" 
    "resources/views/*/show.blade.php"
    "resources/views/*/create.blade.php"
    "resources/views/*/edit.blade.php"
    "resources/views/financial/*.blade.php"
    "resources/views/clients/*.blade.php"
    "resources/views/tickets/*.blade.php"
    "resources/views/assets/*.blade.php"
    "resources/views/users/*.blade.php"
    "resources/views/reports/*.blade.php"
)

# Create backup directory
BACKUP_DIR="resources/views/.backup-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Function to process a single file
process_file() {
    local file="$1"
    echo "Processing: $file"
    
    # Create backup
    cp "$file" "$BACKUP_DIR/$(basename "$file")"
    
    # Apply dark mode transformations
    sed -i \
        -e 's/\bbg-white\b/bg-white dark:bg-gray-800/g' \
        -e 's/\bbg-gray-50\b/bg-gray-50 dark:bg-gray-900/g' \
        -e 's/\bbg-gray-100\b/bg-gray-100 dark:bg-gray-800/g' \
        -e 's/\btext-gray-900\b/text-gray-900 dark:text-white/g' \
        -e 's/\btext-gray-800\b/text-gray-800 dark:text-gray-200/g' \
        -e 's/\btext-gray-700\b/text-gray-700 dark:text-gray-300/g' \
        -e 's/\btext-gray-600\b/text-gray-600 dark:text-gray-400/g' \
        -e 's/\bborder-gray-200\b/border-gray-200 dark:border-gray-700/g' \
        -e 's/\bborder-gray-300\b/border-gray-300 dark:border-gray-600/g' \
        -e 's/\bhover:bg-gray-50\b/hover:bg-gray-50 dark:hover:bg-gray-700/g' \
        -e 's/\bhover:bg-gray-100\b/hover:bg-gray-100 dark:hover:bg-gray-700/g' \
        -e 's/\bhover:text-gray-900\b/hover:text-gray-900 dark:hover:text-white/g' \
        "$file"
}

# Process priority files
total_files=0
for pattern in "${PRIORITY_FILES[@]}"; do
    for file in $pattern; do
        if [[ -f "$file" ]]; then
            process_file "$file"
            ((total_files++))
        fi
    done
done

echo "✅ Processed $total_files files"
echo "📁 Backups saved to: $BACKUP_DIR"
echo ""
echo "🔄 Next steps:"
echo "1. Run: npm run build"
echo "2. Test dark mode in browser"
echo "3. If issues, restore from backup: cp $BACKUP_DIR/* resources/views/"
echo ""
echo "🌙 Dark mode automation complete!"