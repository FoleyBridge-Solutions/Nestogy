#!/bin/bash

# Get list of all tables
tables=$(php artisan db:show --database=pgsql 2>&1 | rg "public / " | awk '{print $3}')

# For each factory, check if columns match
for factory in database/factories/Domains/**/*Factory.php; do
    if [ -f "$factory" ]; then
        model_name=$(basename "$factory" Factory.php)
        # Convert CamelCase to snake_case for table name
        table_name=$(echo "$model_name" | sed 's/\([A-Z]\)/_\L\1/g' | sed 's/^_//' | sed 's/$/_/')
        
        echo "Checking: $model_name -> potential table: ${table_name}*"
    fi
done
