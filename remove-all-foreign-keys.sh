#!/bin/bash

# Remove all problematic foreign key constraints
find database/migrations -name '*.php' -exec sed -i.bak 's/\$table->foreign.*->references.*->on.*->.*;//g' {} \;

echo "Removed all foreign key constraints from migrations"
echo "Original files backed up with .bak extension"