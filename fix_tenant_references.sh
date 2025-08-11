#!/bin/bash

echo "Fixing tenant_id references in PHP files..."

# Replace tenant_id with company_id in all PHP files
find /var/www/html/Nestogy/app -type f -name "*.php" -exec sed -i 's/tenant_id/company_id/g' {} \;

# Replace ->tenant() with ->company() 
find /var/www/html/Nestogy/app -type f -name "*.php" -exec sed -i 's/->tenant()/->company()/g' {} \;

# Replace 'tenant' => with 'company' => in relationships
find /var/www/html/Nestogy/app -type f -name "*.php" -exec sed -i "s/'tenant'/'company'/g" {} \;

# Replace BelongsToTenant with BelongsToCompany
find /var/www/html/Nestogy/app -type f -name "*.php" -exec sed -i 's/BelongsToTenant/BelongsToCompany/g' {} \;

# Replace tenant_id in blade files
find /var/www/html/Nestogy/resources/views -type f -name "*.blade.php" -exec sed -i 's/tenant_id/company_id/g' {} \;
find /var/www/html/Nestogy/resources/views -type f -name "*.blade.php" -exec sed -i 's/->tenant()/->company()/g' {} \;
find /var/www/html/Nestogy/resources/views -type f -name "*.blade.php" -exec sed -i "s/tenant\(\)/company()/g" {} \;

echo "Done! All tenant references have been replaced with company references."