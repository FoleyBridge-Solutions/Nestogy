#!/bin/bash

# Analyze Seeder Status Script
# Shows which seeders exist, which are called, and which will actually run

echo "============================================="
echo "NESTOGY SEEDER ANALYSIS"
echo "============================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get all seeders from DevDatabaseSeeder
echo "📊 Analyzing seeders in DevDatabaseSeeder.php..."
echo ""

called_seeders=$(grep -o "[A-Z][a-zA-Z]*Seeder::class" database/seeders/DevDatabaseSeeder.php | sed 's/::class//' | sort | uniq)

total_called=0
exists_count=0
missing_count=0

echo "✅ WILL RUN (Seeder exists):"
echo "-------------------------------------------"
for seeder in $called_seeders; do
    total_called=$((total_called + 1))
    file="database/seeders/${seeder}.php"
    if [ -f "$file" ]; then
        exists_count=$((exists_count + 1))
        echo "  ✓ $seeder"
    fi
done

echo ""
echo "⚠️  WILL SKIP (Seeder missing):"
echo "-------------------------------------------"
for seeder in $called_seeders; do
    file="database/seeders/${seeder}.php"
    if [ ! -f "$file" ]; then
        missing_count=$((missing_count + 1))
        echo "  ✗ $seeder (file not found)"
    fi
done

echo ""
echo "============================================="
echo "SUMMARY"
echo "============================================="
echo "Total seeders called in DevDatabaseSeeder: $total_called"
echo -e "${GREEN}Seeders that WILL run: $exists_count${NC}"
echo -e "${YELLOW}Seeders that will be SKIPPED: $missing_count${NC}"
echo ""

# Show seeder files not called
echo "📁 EXISTING SEEDERS NOT CALLED:"
echo "-------------------------------------------"
not_called=0
for file in database/seeders/*.php; do
    seeder=$(basename "$file" .php)
    if [[ "$seeder" != "DatabaseSeeder" && "$seeder" != "DevDatabaseSeeder" && "$seeder" != "Seeder" ]]; then
        if ! grep -q "${seeder}::class" database/seeders/DevDatabaseSeeder.php; then
            not_called=$((not_called + 1))
            echo "  - $seeder"
        fi
    fi
done

echo ""
echo "Total existing seeders NOT called: $not_called"
echo ""
echo "============================================="
echo "READY TO SEED!"
echo "============================================="
echo ""
echo "Run: php artisan migrate:fresh --seed"
echo ""
