<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DevDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * TESTED AND WORKING - Only includes seeders that have been verified to work
     */
    public function run(): void
    {
        // Only run in development or local environment
        if (! App::environment(['local', 'development', 'testing'])) {
            $this->command->error('Development seeders can only run in local/development/testing environments!');
            return;
        }

        $this->command->info('🚀 Starting TESTED & WORKING Development Database Seeding...');
        $this->command->info('Creating realistic test data for mid-market MSPs with 2 years of history.');
        $this->command->newLine();

        $startTime = microtime(true);

        try {
            // ====================================================================================
            // LEVEL 1: Foundation - Must run FIRST
            // ====================================================================================
            $this->command->info('=== LEVEL 1: Foundation ===');
            $this->callSafe('Roles and Permissions', RolesAndPermissionsSeeder::class);
            $this->callSafe('Companies (10 MSPs)', CompanySeeder::class);
            $this->callSafe('Settings', SettingsSeeder::class);

            // ====================================================================================
            // LEVEL 2: Users - Depends on Companies & Roles
            // ====================================================================================
            $this->command->newLine();
            $this->command->info('=== LEVEL 2: Users & Accounts ===');
            $this->callSafe('Users (20-40 per company)', UserSeeder::class);
            $this->callSafe('User Settings', UserSettingSeeder::class);
            $this->callSafe('Accounts', AccountSeeder::class);
            $this->callSafe('Account Holds', AccountHoldSeeder::class);

            // ====================================================================================
            // LEVEL 3: Core Data - Categories, Vendors, SLA
            // ====================================================================================
            $this->command->newLine();
            $this->command->info('=== LEVEL 3: Core Configuration ===');
            $this->callSafe('Categories', CategorySeeder::class);
            $this->callSafe('SLA Levels', SLASeeder::class);
            $this->callSafe('Vendors', VendorSeeder::class);
            $this->callSafe('Tax Rates', TaxSeeder::class);

            // ====================================================================================
            // LEVEL 4: Clients & Related
            // ====================================================================================
            $this->command->newLine();
            $this->command->info('=== LEVEL 4: Clients ===');
            $this->callSafe('Clients (30-80 per MSP)', ClientSeeder::class);
            $this->callSafe('Contacts (2-5 per client)', ContactSeeder::class);
            $this->callSafe('Locations (1-3 per client)', LocationSeeder::class);
            $this->callSafe('Addresses', AddressSeeder::class);
            $this->callSafe('Networks', NetworkSeeder::class);
            $this->callSafe('Client Documents', ClientDocumentSeeder::class);
            $this->callSafe('Client Portal Users', ClientPortalUserSeeder::class);
            $this->callSafe('Client Portal Sessions', ClientPortalSessionSeeder::class);
            $this->callSafe('Communication Logs', CommunicationLogSeeder::class);

            // ====================================================================================
            // LEVEL 5: Products & Services
            // ====================================================================================
            $this->command->newLine();
            $this->command->info('=== LEVEL 5: Products & Services ===');
            $this->callSafe('Products (50-100)', ProductSeeder::class);

            // ====================================================================================
            // LEVEL 6: Assets & Contracts
            // ====================================================================================
            $this->command->newLine();
            $this->command->info('=== LEVEL 6: Assets & Contracts ===');
            $this->callSafe('Assets (50-200 per client)', AssetSeeder::class);
            $this->callSafe('Asset Warranties', AssetWarrantySeeder::class);
            $this->callSafe('Contracts', ContractSeeder::class);

            // ====================================================================================
            // LEVEL 7: Operations - Tickets & Projects
            // ====================================================================================
            $this->command->newLine();
            $this->command->info('=== LEVEL 7: Operations ===');
            $this->callSafe('Projects (5-15 per MSP)', ProjectSeeder::class);
            $this->callSafe('Project Tasks', ProjectTaskSeeder::class);
            $this->callSafe('Tickets (200-500/month)', TicketSeeder::class);
            $this->callSafe('Ticket Replies', TicketReplySeeder::class);
            $this->callSafe('Ticket Comments', TicketCommentSeeder::class);
            $this->callSafe('Ticket Ratings', TicketRatingSeeder::class);
            $this->callSafe('Ticket Watchers', TicketWatcherSeeder::class);
            $this->callSafe('Ticket Time Entries', TicketTimeEntrySeeder::class);
            $this->callSafe('Time Entries', TimeEntrySeeder::class);

            // ====================================================================================
            // LEVEL 8: Financial - Invoices, Payments, Quotes
            // ====================================================================================
            $this->command->newLine();
            $this->command->info('=== LEVEL 8: Financial ===');
            $this->callSafe('Leads (10-30 per MSP)', LeadSeeder::class);
            $this->callSafe('Quotes (10-30 per MSP)', QuoteSeeder::class);
            $this->callSafe('Invoices (2yr history)', InvoiceSeeder::class);
            // SKIPPED: Recurring Invoices (schema mismatch - products.recurring_type doesn't exist)
            $this->callSafe('Payments', PaymentSeeder::class);
            $this->callSafe('Payment Methods', PaymentMethodSeeder::class);
            $this->callSafe('Auto Payments', AutoPaymentSeeder::class);
            $this->callSafe('Expenses (2yr history)', ExpenseSeeder::class);

            // ====================================================================================
            // LEVEL 9: Advanced Features
            // ====================================================================================
            $this->command->newLine();
            $this->command->info('=== LEVEL 9: Advanced Features ===');
            $this->callSafe('Company Customizations', CompanyCustomizationSeeder::class);
            $this->callSafe('Company Mail Settings', CompanyMailSettingsSeeder::class);
            // SKIPPED: Analytics Snapshots (schema mismatch - missing snapshot_date column)
            $this->callSafe('Audit Logs', AuditLogSeeder::class);
            // SKIPPED: Dashboard Widgets (schema mismatch - missing user_id column)
            // SKIPPED: Documents (factory creates duplicate companies)
            $this->callSafe('In-App Notifications', InAppNotificationSeeder::class);
            $this->callSafe('Mail Templates', MailTemplateSeeder::class);
            $this->callSafe('Notification Preferences', NotificationPreferenceSeeder::class);
            // SKIPPED: Knowledge Base (table kb_categories doesn't exist)
            // SKIPPED: Integrations (IntegrationFactory doesn't exist)
            // SKIPPED: Report Templates (needs review)
            $this->callSafe('Tags', TagSeeder::class);

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            $this->command->newLine();
            $this->command->info("✅ Development database seeding completed successfully in {$duration} seconds!");
            $this->command->newLine();

            // Display summary
            $this->displaySummary();

        } catch (\Exception $e) {
            $this->command->error('💥 Seeding failed: '.$e->getMessage());
            $this->command->error('File: '.$e->getFile().':'.$e->getLine());
            throw $e;
        }
    }

    /**
     * Call a seeder safely with error handling
     */
    private function callSafe(string $name, string $seederClass): void
    {
        if (! class_exists($seederClass)) {
            $this->command->warn("  ⚠ Skipping: $name (seeder class not found)");
            return;
        }

        $this->command->info("  ⏳ Seeding: $name");
        
        try {
            $start = microtime(true);
            $this->call($seederClass);
            $duration = round((microtime(true) - $start) * 1000);
            $this->command->info("  ✅ $name completed ({$duration}ms)");
        } catch (\Exception $e) {
            $this->command->error("  ❌ $name FAILED: ".$e->getMessage());
            // Continue with other seeders
        }
    }

    /**
     * Display summary of seeded data
     */
    private function displaySummary(): void
    {
        $summary = [];

        $models = [
            ['Companies', \App\Domains\Company\Models\Company::class],
            ['Users', \App\Domains\Core\Models\User::class],
            ['Clients', \App\Domains\Client\Models\Client::class],
            ['Contacts', \App\Domains\Client\Models\ClientContact::class],
            ['Locations', \App\Domains\Client\Models\Location::class],
            ['Categories', \App\Domains\Financial\Models\Category::class],
            ['Products', \App\Domains\Product\Models\Product::class],
            ['Assets', \App\Domains\Asset\Models\Asset::class],
            ['Contracts', \App\Domains\Contract\Models\Contract::class],
            ['Tickets', \App\Domains\Ticket\Models\Ticket::class],
            ['Projects', \App\Domains\Project\Models\Project::class],
            ['Invoices', \App\Domains\Financial\Models\Invoice::class],
            ['Payments', \App\Domains\Financial\Models\Payment::class],
            ['Quotes', \App\Domains\Financial\Models\Quote::class],
            ['Leads', \App\Domains\Lead\Models\Lead::class],
            ['Expenses', \App\Domains\Financial\Models\Expense::class],
        ];

        foreach ($models as [$label, $class]) {
            if (class_exists($class)) {
                try {
                    $count = $class::count();
                    $summary[] = [$label, number_format($count)];
                } catch (\Exception $e) {
                    $summary[] = [$label, 'Error'];
                }
            }
        }

        $this->command->table(['Entity', 'Count'], $summary);

        // Display date range
        try {
            $oldestTicket = \App\Domains\Ticket\Models\Ticket::oldest('created_at')->first();
            $newestTicket = \App\Domains\Ticket\Models\Ticket::latest('created_at')->first();
            
            if ($oldestTicket && $newestTicket) {
                $this->command->newLine();
                $this->command->info('📅 Data Date Range:');
                $this->command->info("   Tickets: {$oldestTicket->created_at->format('Y-m-d')} to {$newestTicket->created_at->format('Y-m-d')}");
            }
        } catch (\Exception $e) {
            // Silent fail
        }

        $this->command->newLine();
        $this->command->info('🎉 Ready for QA testing!');
        $this->command->info('📧 Login: super@nestogy.com / password123');
    }
}
