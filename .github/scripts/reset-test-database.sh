#!/bin/bash

# Reset PostgreSQL test database for CI
# This script drops and recreates the test database to avoid type constraint issues

set -e

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-5432}"
DB_USER="${DB_USERNAME:-nestogy}"
DB_PASSWORD="${DB_PASSWORD:-nestogy_dev_pass}"
DB_NAME="${DB_DATABASE:-nestogy_test}"

echo "Resetting test database: $DB_NAME"

# Drop database if exists
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d postgres -c "DROP DATABASE IF EXISTS $DB_NAME;" || true

# Create fresh database
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d postgres -c "CREATE DATABASE $DB_NAME OWNER $DB_USER;"

echo "Test database reset complete"
