<?php

namespace Database\Seeders\Dev;

use App\Domains\Financial\Models\Category;
use App\Domains\Company\Models\Company;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating categories...');

        // Get all companies
        $companies = Company::all();

        foreach ($companies as $company) {
            $this->command->info("  Creating categories for {$company->name}...");
            $this->createCategories($company->id);
        }

        $this->command->info('Categories created successfully.');
    }

    /**
     * Create all categories with appropriate types
     */
    private function createCategories(int $companyId): void
    {
        // First, create parent categories
        $parentCategories = [
            // IT Products & Services - Used across multiple contexts
            // Note: invoice type automatically includes income and quote types
            [
                'name' => 'Managed Services',
                'types' => [Category::TYPE_PRODUCT, Category::TYPE_INVOICE, Category::TYPE_RECURRING],
                'icon' => 'server',
            ],
            [
                'name' => 'Hardware',
                'types' => [Category::TYPE_PRODUCT, Category::TYPE_EXPENSE, Category::TYPE_INVOICE],
                'icon' => 'computer-desktop',
            ],
            [
                'name' => 'Software',
                'types' => [Category::TYPE_PRODUCT, Category::TYPE_EXPENSE, Category::TYPE_INVOICE],
                'icon' => 'code-bracket',
            ],
            [
                'name' => 'Software Licenses',
                'types' => [Category::TYPE_PRODUCT, Category::TYPE_INVOICE, Category::TYPE_RECURRING],
                'icon' => 'key',
            ],
            [
                'name' => 'Cloud Services',
                'types' => [Category::TYPE_PRODUCT, Category::TYPE_EXPENSE, Category::TYPE_RECURRING],
                'icon' => 'cloud',
            ],
            [
                'name' => 'Consulting',
                'types' => [Category::TYPE_INVOICE],
                'icon' => 'academic-cap',
            ],
            [
                'name' => 'Support Services',
                'types' => [Category::TYPE_PRODUCT, Category::TYPE_INVOICE, Category::TYPE_RECURRING],
                'icon' => 'lifebuoy',
            ],
            [
                'name' => 'Maintenance',
                'types' => [Category::TYPE_INVOICE, Category::TYPE_TICKET, Category::TYPE_RECURRING],
                'icon' => 'cog',
            ],
            [
                'name' => 'Training',
                'types' => [Category::TYPE_INVOICE, Category::TYPE_EXPENSE],
                'icon' => 'users',
            ],
            [
                'name' => 'Project Work',
                'types' => [Category::TYPE_INVOICE, Category::TYPE_TICKET],
                'icon' => 'briefcase',
            ],

            // Networking & Infrastructure
            [
                'name' => 'Networking Equipment',
                'types' => [Category::TYPE_PRODUCT, Category::TYPE_ASSET],
                'icon' => 'wifi',
            ],
            [
                'name' => 'Servers',
                'types' => [Category::TYPE_PRODUCT, Category::TYPE_ASSET],
                'icon' => 'server',
            ],
            [
                'name' => 'Storage',
                'types' => [Category::TYPE_PRODUCT, Category::TYPE_ASSET],
                'icon' => 'circle-stack',
            ],
            [
                'name' => 'Security Equipment',
                'types' => [Category::TYPE_PRODUCT, Category::TYPE_ASSET],
                'icon' => 'shield-check',
            ],

            // Computer Assets
            [
                'name' => 'Mobile Devices',
                'types' => [Category::TYPE_PRODUCT, Category::TYPE_ASSET],
                'icon' => 'device-phone-mobile',
            ],

            // Expenses Only
            [
                'name' => 'Travel',
                'types' => [Category::TYPE_EXPENSE],
                'icon' => 'globe-alt',
            ],
            [
                'name' => 'Office Supplies',
                'types' => [Category::TYPE_EXPENSE],
                'icon' => 'pencil-square',
            ],
            [
                'name' => 'Utilities',
                'types' => [Category::TYPE_EXPENSE],
                'icon' => 'bolt',
            ],
            [
                'name' => 'Rent',
                'types' => [Category::TYPE_EXPENSE],
                'icon' => 'building-office',
            ],
            [
                'name' => 'Insurance',
                'types' => [Category::TYPE_EXPENSE],
                'icon' => 'shield-check',
            ],
            [
                'name' => 'Marketing',
                'types' => [Category::TYPE_EXPENSE],
                'icon' => 'megaphone',
            ],
            [
                'name' => 'Subscriptions',
                'types' => [Category::TYPE_EXPENSE, Category::TYPE_RECURRING],
                'icon' => 'arrow-path',
            ],
            [
                'name' => 'Professional Fees',
                'types' => [Category::TYPE_EXPENSE],
                'icon' => 'briefcase',
            ],
            [
                'name' => 'Telecommunications',
                'types' => [Category::TYPE_EXPENSE, Category::TYPE_RECURRING],
                'icon' => 'phone',
            ],

            // Ticket Categories
            [
                'name' => 'Support Request',
                'types' => [Category::TYPE_TICKET],
                'icon' => 'lifebuoy',
            ],
            [
                'name' => 'Incident',
                'types' => [Category::TYPE_TICKET],
                'icon' => 'exclamation-triangle',
            ],
            [
                'name' => 'Service Request',
                'types' => [Category::TYPE_TICKET],
                'icon' => 'clipboard-document-list',
            ],
            [
                'name' => 'Emergency',
                'types' => [Category::TYPE_TICKET],
                'icon' => 'fire',
            ],
            [
                'name' => 'Change Request',
                'types' => [Category::TYPE_TICKET],
                'icon' => 'arrow-path',
            ],
            [
                'name' => 'Problem',
                'types' => [Category::TYPE_TICKET],
                'icon' => 'puzzle-piece',
            ],
            [
                'name' => 'Security Issue',
                'types' => [Category::TYPE_TICKET],
                'icon' => 'shield-exclamation',
            ],
            [
                'name' => 'Network Issue',
                'types' => [Category::TYPE_TICKET],
                'icon' => 'wifi',
            ],
            [
                'name' => 'Hardware Issue',
                'types' => [Category::TYPE_TICKET],
                'icon' => 'computer-desktop',
            ],
            [
                'name' => 'Software Issue',
                'types' => [Category::TYPE_TICKET],
                'icon' => 'code-bracket',
            ],

            // Quote-specific
            [
                'name' => 'New Project',
                'types' => [Category::TYPE_QUOTE],
                'icon' => 'sparkles',
            ],
            [
                'name' => 'Service Upgrade',
                'types' => [Category::TYPE_QUOTE],
                'icon' => 'arrow-trending-up',
            ],

            // Assets Only
            [
                'name' => 'Firewall',
                'types' => [Category::TYPE_ASSET],
                'icon' => 'shield-check',
            ],
            [
                'name' => 'UPS',
                'types' => [Category::TYPE_ASSET],
                'icon' => 'bolt',
            ],
            [
                'name' => 'Phone System',
                'types' => [Category::TYPE_ASSET],
                'icon' => 'phone',
            ],
            [
                'name' => 'Security Camera',
                'types' => [Category::TYPE_ASSET],
                'icon' => 'camera',
            ],

            // Recurring Services
            [
                'name' => 'Monthly Services',
                'types' => [Category::TYPE_RECURRING],
                'icon' => 'calendar',
            ],
            [
                'name' => 'Quarterly Maintenance',
                'types' => [Category::TYPE_RECURRING],
                'icon' => 'cog',
            ],
            [
                'name' => 'Weekly Backup',
                'types' => [Category::TYPE_RECURRING],
                'icon' => 'circle-stack',
            ],
        ];

        // Create all parent categories first
        $createdParents = [];
        foreach ($parentCategories as $categoryData) {
            $types = $categoryData['types'];
            unset($categoryData['types']);

            $category = Category::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'name' => $categoryData['name'],
                ],
                array_merge($categoryData, [
                    'company_id' => $companyId,
                    'type' => $types,
                ])
            );

            $createdParents[$categoryData['name']] = $category->id;
        }

        // Now create child categories with parent references
        $childCategories = [
            // Hardware subcategories
            [
                'name' => 'Laptops',
                'parent' => 'Hardware',
                'types' => [Category::TYPE_PRODUCT, Category::TYPE_ASSET],
                'icon' => 'device-phone-mobile',
            ],
            [
                'name' => 'Desktop Computers',
                'parent' => 'Hardware',
                'types' => [Category::TYPE_PRODUCT, Category::TYPE_ASSET],
                'icon' => 'computer-desktop',
            ],
            [
                'name' => 'Peripherals',
                'parent' => 'Hardware',
                'types' => [Category::TYPE_PRODUCT, Category::TYPE_ASSET],
                'icon' => 'printer',
            ],

            // Server subcategories
            [
                'name' => 'Physical Servers',
                'parent' => 'Servers',
                'types' => [Category::TYPE_PRODUCT, Category::TYPE_ASSET],
                'icon' => 'server',
            ],
            [
                'name' => 'Virtual Machines',
                'parent' => 'Servers',
                'types' => [Category::TYPE_ASSET],
                'icon' => 'cloud',
            ],

            // Networking subcategories
            [
                'name' => 'Switches',
                'parent' => 'Networking Equipment',
                'types' => [Category::TYPE_PRODUCT, Category::TYPE_ASSET],
                'icon' => 'server',
            ],
            [
                'name' => 'Routers',
                'parent' => 'Networking Equipment',
                'types' => [Category::TYPE_PRODUCT, Category::TYPE_ASSET],
                'icon' => 'wifi',
            ],
            [
                'name' => 'Firewalls',
                'parent' => 'Networking Equipment',
                'types' => [Category::TYPE_PRODUCT, Category::TYPE_ASSET],
                'icon' => 'shield-check',
            ],

            // Software subcategories
            [
                'name' => 'Operating Systems',
                'parent' => 'Software',
                'types' => [Category::TYPE_PRODUCT, Category::TYPE_EXPENSE],
                'icon' => 'code-bracket',
            ],
            [
                'name' => 'Business Applications',
                'parent' => 'Software',
                'types' => [Category::TYPE_PRODUCT, Category::TYPE_EXPENSE],
                'icon' => 'briefcase',
            ],
            [
                'name' => 'Security Software',
                'parent' => 'Software',
                'types' => [Category::TYPE_PRODUCT, Category::TYPE_EXPENSE],
                'icon' => 'shield-check',
            ],

            // Support Services subcategories
            [
                'name' => 'Help Desk Support',
                'parent' => 'Support Services',
                'types' => [Category::TYPE_PRODUCT, Category::TYPE_INVOICE],
                'icon' => 'lifebuoy',
            ],
            [
                'name' => 'Remote Support',
                'parent' => 'Support Services',
                'types' => [Category::TYPE_PRODUCT, Category::TYPE_INVOICE],
                'icon' => 'computer-desktop',
            ],
            [
                'name' => 'On-Site Support',
                'parent' => 'Support Services',
                'types' => [Category::TYPE_PRODUCT, Category::TYPE_INVOICE],
                'icon' => 'map-pin',
            ],
        ];

        foreach ($childCategories as $categoryData) {
            $parentName = $categoryData['parent'];
            unset($categoryData['parent']);

            $types = $categoryData['types'];
            unset($categoryData['types']);

            if (isset($createdParents[$parentName])) {
                Category::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'name' => $categoryData['name'],
                    ],
                    array_merge($categoryData, [
                        'company_id' => $companyId,
                        'parent_id' => $createdParents[$parentName],
                        'type' => $types,
                    ])
                );
            }
        }

        $this->command->info('    ✓ Categories created with appropriate types and hierarchy');
    }
}
