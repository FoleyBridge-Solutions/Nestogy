#!/bin/bash

# Setup Parallel Test Databases for ParaTest
# Creates 16 separate PostgreSQL databases for parallel test execution
# This prevents transaction conflicts when running tests in parallel

set -e

# Configuration
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-5432}"
DB_USERNAME="${DB_USERNAME:-nestogy}"
DB_PASSWORD="${DB_PASSWORD:-nestogy_dev_pass}"
BASE_DB_NAME="nestogy_test"
NUM_PROCESSES=16

echo "=================================================="
echo "Setting up Parallel Test Databases"
echo "=================================================="
echo "Host: $DB_HOST:$DB_PORT"
echo "Base database name: $BASE_DB_NAME"
echo "Number of databases: $NUM_PROCESSES"
echo "=================================================="
echo ""

# Function to create a database
create_database() {
    local db_name=$1
    echo "Creating database: $db_name"
    
    # Check if database exists
    DB_EXISTS=$(PGPASSWORD=$DB_PASSWORD psql -h $DB_HOST -p $DB_PORT -U $DB_USERNAME -tAc "SELECT 1 FROM pg_database WHERE datname='$db_name'" postgres 2>/dev/null || echo "0")
    
    if [ "$DB_EXISTS" = "1" ]; then
        echo "  ✓ Database $db_name already exists, dropping and recreating..."
        PGPASSWORD=$DB_PASSWORD psql -h $DB_HOST -p $DB_PORT -U $DB_USERNAME -c "DROP DATABASE IF EXISTS $db_name" postgres
    fi
    
    # Create database
    PGPASSWORD=$DB_PASSWORD psql -h $DB_HOST -p $DB_PORT -U $DB_USERNAME -c "CREATE DATABASE $db_name" postgres
    echo "  ✓ Created $db_name"
}

# Create the base test database (for non-parallel runs)
create_database "$BASE_DB_NAME"

# Create databases for each parallel process (0 to 15)
for i in $(seq 0 $((NUM_PROCESSES - 1))); do
    create_database "${BASE_DB_NAME}_${i}"
done

echo ""
echo "=================================================="
echo "✓ All test databases created successfully!"
echo "=================================================="
echo ""
echo "Created databases:"
echo "  - $BASE_DB_NAME (base)"
for i in $(seq 0 $((NUM_PROCESSES - 1))); do
    echo "  - ${BASE_DB_NAME}_${i}"
done
echo ""
echo "Next steps:"
echo "  1. Run migrations: ./scripts/migrate-parallel-test-databases.sh"
echo "  2. Run tests: php artisan test"
echo ""
