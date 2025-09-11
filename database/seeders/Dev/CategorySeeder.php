<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Company;

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
            
            // Invoice categories
            $this->createInvoiceCategories($company->id);
            
            // Expense categories
            $this->createExpenseCategories($company->id);
            
            // Income categories  
            $this->createIncomeCategories($company->id);
            
            // Ticket categories
            $this->createTicketCategories($company->id);
            
            // Product categories
            $this->createProductCategories($company->id);
            
            // Asset categories
            $this->createAssetCategories($company->id);
            
            // Quote categories
            $this->createQuoteCategories($company->id);
            
            // Recurring categories
            $this->createRecurringCategories($company->id);
        }

        $this->command->info('Categories created successfully.');
    }

    /**
     * Create invoice categories
     */
    private function createInvoiceCategories(int $companyId): void
    {
        $categories = [
            ['name' => 'Service', 'color' => '#3B82F6', 'icon' => 'wrench'],
            ['name' => 'Product', 'color' => '#10B981', 'icon' => 'cube'],
            ['name' => 'Support', 'color' => '#8B5CF6', 'icon' => 'support'],
            ['name' => 'Consulting', 'color' => '#F59E0B', 'icon' => 'academic-cap'],
            ['name' => 'Project', 'color' => '#EF4444', 'icon' => 'briefcase'],
            ['name' => 'Maintenance', 'color' => '#EC4899', 'icon' => 'cog'],
            ['name' => 'Training', 'color' => '#14B8A6', 'icon' => 'users'],
            ['name' => 'Licensing', 'color' => '#6366F1', 'icon' => 'key'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'name' => $category['name'], 
                    'type' => Category::TYPE_INVOICE
                ],
                array_merge($category, [
                    'company_id' => $companyId,
                    'type' => Category::TYPE_INVOICE
                ])
            );
        }
        $this->command->info('    ✓ Invoice categories created');
    }

    /**
     * Create expense categories
     */
    private function createExpenseCategories(int $companyId): void
    {
        $categories = [
            ['name' => 'Hardware', 'color' => '#3B82F6', 'icon' => 'desktop-computer'],
            ['name' => 'Software', 'color' => '#10B981', 'icon' => 'code'],
            ['name' => 'Travel', 'color' => '#8B5CF6', 'icon' => 'globe'],
            ['name' => 'Office Supplies', 'color' => '#F59E0B', 'icon' => 'pencil'],
            ['name' => 'Utilities', 'color' => '#EF4444', 'icon' => 'lightning-bolt'],
            ['name' => 'Rent', 'color' => '#EC4899', 'icon' => 'office-building'],
            ['name' => 'Insurance', 'color' => '#14B8A6', 'icon' => 'shield-check'],
            ['name' => 'Marketing', 'color' => '#6366F1', 'icon' => 'speakerphone'],
            ['name' => 'Training', 'color' => '#F97316', 'icon' => 'academic-cap'],
            ['name' => 'Subscriptions', 'color' => '#84CC16', 'icon' => 'refresh'],
            ['name' => 'Professional Fees', 'color' => '#06B6D4', 'icon' => 'briefcase'],
            ['name' => 'Telecommunications', 'color' => '#A855F7', 'icon' => 'phone'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'name' => $category['name'], 
                    'type' => Category::TYPE_EXPENSE
                ],
                array_merge($category, [
                    'company_id' => $companyId,
                    'type' => Category::TYPE_EXPENSE
                ])
            );
        }
        $this->command->info('    ✓ Expense categories created');
    }

    /**
     * Create income categories
     */
    private function createIncomeCategories(int $companyId): void
    {
        $categories = [
            ['name' => 'Managed Services', 'color' => '#3B82F6', 'icon' => 'server'],
            ['name' => 'Project Revenue', 'color' => '#10B981', 'icon' => 'chart-bar'],
            ['name' => 'Break-Fix Services', 'color' => '#8B5CF6', 'icon' => 'wrench'],
            ['name' => 'Hardware Sales', 'color' => '#F59E0B', 'icon' => 'desktop-computer'],
            ['name' => 'Software Sales', 'color' => '#EF4444', 'icon' => 'code'],
            ['name' => 'Consultation Fees', 'color' => '#EC4899', 'icon' => 'users'],
            ['name' => 'Training Revenue', 'color' => '#14B8A6', 'icon' => 'academic-cap'],
            ['name' => 'Commission', 'color' => '#6366F1', 'icon' => 'cash'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'name' => $category['name'], 
                    'type' => Category::TYPE_INCOME
                ],
                array_merge($category, [
                    'company_id' => $companyId,
                    'type' => Category::TYPE_INCOME
                ])
            );
        }
        $this->command->info('    ✓ Income categories created');
    }

    /**
     * Create ticket categories
     */
    private function createTicketCategories(int $companyId): void
    {
        $categories = [
            ['name' => 'Support Request', 'color' => '#3B82F6', 'icon' => 'support'],
            ['name' => 'Incident', 'color' => '#EF4444', 'icon' => 'exclamation'],
            ['name' => 'Service Request', 'color' => '#10B981', 'icon' => 'clipboard-list'],
            ['name' => 'Maintenance', 'color' => '#8B5CF6', 'icon' => 'cog'],
            ['name' => 'Emergency', 'color' => '#DC2626', 'icon' => 'fire'],
            ['name' => 'Project Task', 'color' => '#F59E0B', 'icon' => 'briefcase'],
            ['name' => 'Change Request', 'color' => '#EC4899', 'icon' => 'refresh'],
            ['name' => 'Problem', 'color' => '#14B8A6', 'icon' => 'puzzle'],
            ['name' => 'Security Issue', 'color' => '#991B1B', 'icon' => 'shield-exclamation'],
            ['name' => 'Network Issue', 'color' => '#6366F1', 'icon' => 'wifi'],
            ['name' => 'Hardware Issue', 'color' => '#F97316', 'icon' => 'desktop-computer'],
            ['name' => 'Software Issue', 'color' => '#84CC16', 'icon' => 'code'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'name' => $category['name'], 
                    'type' => Category::TYPE_TICKET
                ],
                array_merge($category, [
                    'company_id' => $companyId,
                    'type' => Category::TYPE_TICKET
                ])
            );
        }
        $this->command->info('    ✓ Ticket categories created');
    }

    /**
     * Create product categories
     */
    private function createProductCategories(int $companyId): void
    {
        $categories = [
            ['name' => 'Computers', 'color' => '#3B82F6', 'icon' => 'desktop-computer'],
            ['name' => 'Networking', 'color' => '#10B981', 'icon' => 'wifi'],
            ['name' => 'Servers', 'color' => '#8B5CF6', 'icon' => 'server'],
            ['name' => 'Storage', 'color' => '#F59E0B', 'icon' => 'database'],
            ['name' => 'Security', 'color' => '#EF4444', 'icon' => 'shield-check'],
            ['name' => 'Software Licenses', 'color' => '#EC4899', 'icon' => 'key'],
            ['name' => 'Peripherals', 'color' => '#14B8A6', 'icon' => 'printer'],
            ['name' => 'Cloud Services', 'color' => '#6366F1', 'icon' => 'cloud'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'name' => $category['name'], 
                    'type' => Category::TYPE_PRODUCT
                ],
                array_merge($category, [
                    'company_id' => $companyId,
                    'type' => Category::TYPE_PRODUCT
                ])
            );
        }
        $this->command->info('    ✓ Product categories created');
    }

    /**
     * Create asset categories
     */
    private function createAssetCategories(int $companyId): void
    {
        $categories = [
            ['name' => 'Desktop', 'color' => '#3B82F6', 'icon' => 'desktop-computer'],
            ['name' => 'Laptop', 'color' => '#10B981', 'icon' => 'device-mobile'],
            ['name' => 'Server', 'color' => '#8B5CF6', 'icon' => 'server'],
            ['name' => 'Network Device', 'color' => '#F59E0B', 'icon' => 'wifi'],
            ['name' => 'Printer', 'color' => '#EF4444', 'icon' => 'printer'],
            ['name' => 'Firewall', 'color' => '#EC4899', 'icon' => 'shield-check'],
            ['name' => 'Storage Device', 'color' => '#14B8A6', 'icon' => 'database'],
            ['name' => 'UPS', 'color' => '#6366F1', 'icon' => 'lightning-bolt'],
            ['name' => 'Phone System', 'color' => '#F97316', 'icon' => 'phone'],
            ['name' => 'Security Camera', 'color' => '#84CC16', 'icon' => 'camera'],
            ['name' => 'Mobile Device', 'color' => '#06B6D4', 'icon' => 'device-mobile'],
            ['name' => 'Virtual Machine', 'color' => '#A855F7', 'icon' => 'cloud'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'name' => $category['name'], 
                    'type' => Category::TYPE_ASSET
                ],
                array_merge($category, [
                    'company_id' => $companyId,
                    'type' => Category::TYPE_ASSET
                ])
            );
        }
        $this->command->info('    ✓ Asset categories created');
    }

    /**
     * Create quote categories
     */
    private function createQuoteCategories(int $companyId): void
    {
        $categories = [
            ['name' => 'New Project', 'color' => '#3B82F6', 'icon' => 'sparkles'],
            ['name' => 'Service Upgrade', 'color' => '#10B981', 'icon' => 'trending-up'],
            ['name' => 'Hardware Purchase', 'color' => '#8B5CF6', 'icon' => 'shopping-cart'],
            ['name' => 'Software Licensing', 'color' => '#F59E0B', 'icon' => 'key'],
            ['name' => 'Managed Services', 'color' => '#EF4444', 'icon' => 'cog'],
            ['name' => 'Consultation', 'color' => '#EC4899', 'icon' => 'chat'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'name' => $category['name'], 
                    'type' => Category::TYPE_QUOTE
                ],
                array_merge($category, [
                    'company_id' => $companyId,
                    'type' => Category::TYPE_QUOTE
                ])
            );
        }
        $this->command->info('    ✓ Quote categories created');
    }

    /**
     * Create recurring categories
     */
    private function createRecurringCategories(int $companyId): void
    {
        $categories = [
            ['name' => 'Monthly Services', 'color' => '#3B82F6', 'icon' => 'calendar'],
            ['name' => 'Quarterly Maintenance', 'color' => '#10B981', 'icon' => 'cog'],
            ['name' => 'Annual Licenses', 'color' => '#8B5CF6', 'icon' => 'key'],
            ['name' => 'Weekly Backup', 'color' => '#F59E0B', 'icon' => 'database'],
            ['name' => 'Bi-Annual Review', 'color' => '#EF4444', 'icon' => 'clipboard-check'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'name' => $category['name'], 
                    'type' => Category::TYPE_RECURRING
                ],
                array_merge($category, [
                    'company_id' => $companyId,
                    'type' => Category::TYPE_RECURRING
                ])
            );
        }
        $this->command->info('    ✓ Recurring categories created');
    }
}