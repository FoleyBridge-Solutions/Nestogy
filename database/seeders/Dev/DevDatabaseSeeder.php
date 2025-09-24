<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class DevDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only run in development or local environment
        if (!App::environment(['local', 'development', 'testing'])) {
            $this->command->error('Development seeders can only run in local/development/testing environments!');
            return;
        }

        $this->command->info('Starting Nestogy Enhanced Development Database Seeding...');
        $this->command->info('This will create comprehensive test data with 2+ years of history.');
        $this->command->newLine();

        // Removed transaction for debugging
        // DB::beginTransaction();

        try {
            // Foundation seeders - Enhanced with more data
            $this->callWithProgressBar('Companies (5-10)', CompanySeeder::class);
            $this->callWithProgressBar('Settings', SettingsSeeder::class);
            $this->callWithProgressBar('Roles and Permissions', RolesAndPermissionsSeeder::class);
            $this->callWithProgressBar('Users (50-100)', UserSeeder::class);
            $this->callWithProgressBar('Categories', CategorySeeder::class);
            $this->callWithProgressBar('Vendors (20-30)', VendorSeeder::class);
            
            // SLA must be before Clients as clients reference SLAs
            if (class_exists(SLASeeder::class)) {
                $this->callWithProgressBar('SLA Levels', SLASeeder::class);
            }
            
            $this->callWithProgressBar('Clients (100-200)', ClientSeeder::class);
            $this->callWithProgressBar('Locations (200-400)', LocationSeeder::class);
            $this->callWithProgressBar('Contacts (500-1000)', ContactSeeder::class);
            if (class_exists(NetworkSeeder::class)) {
                $this->callWithProgressBar('Networks', NetworkSeeder::class);
            }
            if (class_exists(TaxSeeder::class)) {
                $this->callWithProgressBar('Tax Configuration', TaxSeeder::class);
            }
            if (class_exists(ProductSeeder::class)) {
                $this->callWithProgressBar('Products & Services (50-100)', ProductSeeder::class);
            }

            // Operational seeders - Enhanced with historical data
            if (class_exists(AssetSeeder::class)) {
                $this->callWithProgressBar('Assets (500-1000)', AssetSeeder::class);
            }
            if (class_exists(AssetWarrantySeeder::class)) {
                $this->callWithProgressBar('Asset Warranties', AssetWarrantySeeder::class);
            }
            if (class_exists(ContractTemplateSeeder::class)) {
                $this->callWithProgressBar('Contract Templates', ContractTemplateSeeder::class);
            }
            if (class_exists(ContractSeeder::class)) {
                $this->callWithProgressBar('Contracts (200-400)', ContractSeeder::class);
            }
            if (class_exists(TicketSeeder::class)) {
                $this->callWithProgressBar('Tickets (2000-5000 with 2yr history)', TicketSeeder::class);
            }
            if (class_exists(TicketReplySeeder::class)) {
                $this->callWithProgressBar('Ticket Replies (5000-10000)', TicketReplySeeder::class);
            }
            if (class_exists(ProjectSeeder::class)) {
                $this->callWithProgressBar('Projects (50-100)', ProjectSeeder::class);
            }
            if (class_exists(ProjectTaskSeeder::class)) {
                $this->callWithProgressBar('Project Tasks', ProjectTaskSeeder::class);
            }
            if (class_exists(InvoiceSeeder::class)) {
                $this->callWithProgressBar('Invoices (2yr history)', InvoiceSeeder::class);
            }
            if (class_exists(PaymentSeeder::class)) {
                $this->callWithProgressBar('Payments', PaymentSeeder::class);
            }
            if (class_exists(RecurringInvoiceSeeder::class)) {
                $this->callWithProgressBar('Recurring Invoices', RecurringInvoiceSeeder::class);
            }
            if (class_exists(LeadSeeder::class)) {
                $this->callWithProgressBar('Leads (100-200)', LeadSeeder::class);
            }
            if (class_exists(QuoteSeeder::class)) {
                $this->callWithProgressBar('Quotes (100-200)', QuoteSeeder::class);
            }
            if (class_exists(ExpenseSeeder::class)) {
                $this->callWithProgressBar('Expenses (2yr history)', ExpenseSeeder::class);
            }
            if (class_exists(KnowledgeBaseSeeder::class)) {
                $this->callWithProgressBar('Knowledge Base Articles (50-100)', KnowledgeBaseSeeder::class);
            }
            if (class_exists(IntegrationSeeder::class)) {
                $this->callWithProgressBar('Integrations', IntegrationSeeder::class);
            }
            if (class_exists(ReportTemplateSeeder::class)) {
                $this->callWithProgressBar('Report Templates', ReportTemplateSeeder::class);
            }
            if (class_exists(EmailSeeder::class)) {
                $this->callWithProgressBar('Emails (500-1000)', EmailSeeder::class);
            }
            if (class_exists(ActivityLogSeeder::class)) {
                $this->callWithProgressBar('Activity Logs (2yr history)', ActivityLogSeeder::class);
            }
            if (class_exists(NotificationSeeder::class)) {
                $this->callWithProgressBar('Notifications', NotificationSeeder::class);
            }

            // Commit transaction
            // DB::commit();

            $this->command->newLine();
            $this->command->info('✓ Development database seeding completed successfully!');
            $this->command->info('Database is now populated with comprehensive test data.');
            $this->command->newLine();

            // Display summary
            $this->displaySummary();

        } catch (\Exception $e) {
            // DB::rollBack();
            $this->command->error('Seeding failed: ' . $e->getMessage());
            $this->command->error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Call a seeder with progress bar
     */
    private function callWithProgressBar(string $name, string $seederClass): void
    {
        $this->command->info("Seeding: $name");
        $this->call($seederClass);
        $this->command->info("✓ $name completed");
        $this->command->newLine();
    }

    /**
     * Display summary of seeded data
     */
    private function displaySummary(): void
    {
        $this->command->table(
            ['Entity', 'Count'],
            [
                ['Companies', \App\Models\Company::count()],
                ['Users', \App\Models\User::count()],
                ['Clients', \App\Models\Client::count()],
                ['Contacts', \App\Models\Contact::count()],
                ['Locations', \App\Models\Location::count()],
                ['Vendors', \App\Models\Vendor::count()],
                ['Categories', \App\Models\Category::count()],
                ['Assets', \App\Models\Asset::count()],
                ['Tickets', \App\Models\Ticket::count()],
                ['Ticket Replies', \App\Models\TicketReply::count()],
                ['Projects', \App\Models\Project::count()],
                ['Invoices', \App\Models\Invoice::count()],
                ['Payments', \App\Models\Payment::count()],
                ['Leads', \App\Models\Lead::count()],
                ['Quotes', \App\Models\Quote::count()],
                ['Expenses', \App\Models\Expense::count()],
                ['Networks', \App\Models\Network::count() ?? 0],
                ['Tax Jurisdictions', \App\Models\TaxJurisdiction::count() ?? 0],
            ]
        );
        
        // Display date range of data
        $oldestTicket = \App\Models\Ticket::oldest('created_at')->first();
        $newestTicket = \App\Models\Ticket::latest('created_at')->first();
        $oldestInvoice = \App\Models\Invoice::oldest('date')->first();
        $newestInvoice = \App\Models\Invoice::latest('date')->first();
        
        $this->command->newLine();
        $this->command->info('Data Date Ranges:');
        if ($oldestTicket) {
            $this->command->info("  Tickets: {$oldestTicket->created_at->format('Y-m-d')} to {$newestTicket->created_at->format('Y-m-d')}");
        }
        if ($oldestInvoice) {
            $this->command->info("  Invoices: {$oldestInvoice->date->format('Y-m-d')} to {$newestInvoice->date->format('Y-m-d')}");
        }
    }
}