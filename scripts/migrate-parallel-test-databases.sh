#!/bin/bash

# Migrate Parallel Test Databases
# Runs Laravel migrations on all parallel test databases

set -e

# Configuration
BASE_DB_NAME="nestogy_test"
NUM_PROCESSES=16

echo "=================================================="
echo "Migrating Parallel Test Databases"
echo "=================================================="
echo ""

# Function to migrate a database
migrate_database() {
    local db_name=$1
    echo "Migrating database: $db_name"
    
    # Temporarily set DB_DATABASE and run migrations
    DB_DATABASE=$db_name php artisan migrate --force --quiet
    
    echo "  ✓ Migrated $db_name"
}

# Migrate the base test database
echo "Migrating base database..."
migrate_database "$BASE_DB_NAME"

# Migrate databases for each parallel process
echo ""
echo "Migrating parallel databases..."
for i in $(seq 0 $((NUM_PROCESSES - 1))); do
    migrate_database "${BASE_DB_NAME}_${i}"
done

echo ""
echo "=================================================="
echo "✓ All test databases migrated successfully!"
echo "=================================================="
echo ""
