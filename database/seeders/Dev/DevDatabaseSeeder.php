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

        $this->command->info('Starting Nestogy Development Database Seeding...');
        $this->command->newLine();

        // Begin database transaction for safety
        DB::beginTransaction();

        try {
            // Foundation seeders (Agent 1)
            $this->callWithProgressBar('Companies', CompanySeeder::class);
            $this->callWithProgressBar('Settings', SettingsSeeder::class);
            $this->callWithProgressBar('Roles and Permissions', RolesAndPermissionsSeeder::class);
            $this->callWithProgressBar('Users', UserSeeder::class);
            $this->callWithProgressBar('Categories', CategorySeeder::class);
            $this->callWithProgressBar('Vendors', VendorSeeder::class);
            $this->callWithProgressBar('Clients', ClientSeeder::class);
            $this->callWithProgressBar('Locations', LocationSeeder::class);
            $this->callWithProgressBar('Contacts', ContactSeeder::class);
            $this->callWithProgressBar('Networks', NetworkSeeder::class);
            $this->callWithProgressBar('Tax Configuration', TaxSeeder::class);

            // Operational seeders (Agent 2 - will be added later)
            if (class_exists(AssetSeeder::class)) {
                $this->callWithProgressBar('Assets', AssetSeeder::class);
            }
            if (class_exists(AssetWarrantySeeder::class)) {
                $this->callWithProgressBar('Asset Warranties', AssetWarrantySeeder::class);
            }
            if (class_exists(ContractTemplateSeeder::class)) {
                $this->callWithProgressBar('Contract Templates', ContractTemplateSeeder::class);
            }
            if (class_exists(SLASeeder::class)) {
                $this->callWithProgressBar('SLA Levels', SLASeeder::class);
            }
            if (class_exists(ContractSeeder::class)) {
                $this->callWithProgressBar('Contracts', ContractSeeder::class);
            }
            if (class_exists(ContractScheduleSeeder::class)) {
                $this->callWithProgressBar('Contract Schedules', ContractScheduleSeeder::class);
            }
            if (class_exists(TicketSeeder::class)) {
                $this->callWithProgressBar('Tickets', TicketSeeder::class);
            }
            if (class_exists(TicketReplySeeder::class)) {
                $this->callWithProgressBar('Ticket Replies', TicketReplySeeder::class);
            }
            if (class_exists(ProjectSeeder::class)) {
                $this->callWithProgressBar('Projects', ProjectSeeder::class);
            }
            if (class_exists(ProjectTaskSeeder::class)) {
                $this->callWithProgressBar('Project Tasks', ProjectTaskSeeder::class);
            }
            if (class_exists(InvoiceSeeder::class)) {
                $this->callWithProgressBar('Invoices', InvoiceSeeder::class);
            }
            if (class_exists(InvoiceItemSeeder::class)) {
                $this->callWithProgressBar('Invoice Items', InvoiceItemSeeder::class);
            }
            if (class_exists(PaymentSeeder::class)) {
                $this->callWithProgressBar('Payments', PaymentSeeder::class);
            }
            if (class_exists(RecurringInvoiceSeeder::class)) {
                $this->callWithProgressBar('Recurring Invoices', RecurringInvoiceSeeder::class);
            }
            if (class_exists(LeadSeeder::class)) {
                $this->callWithProgressBar('Leads', LeadSeeder::class);
            }
            if (class_exists(QuoteSeeder::class)) {
                $this->callWithProgressBar('Quotes', QuoteSeeder::class);
            }
            if (class_exists(ExpenseSeeder::class)) {
                $this->callWithProgressBar('Expenses', ExpenseSeeder::class);
            }
            if (class_exists(KnowledgeBaseSeeder::class)) {
                $this->callWithProgressBar('Knowledge Base', KnowledgeBaseSeeder::class);
            }
            if (class_exists(IntegrationSeeder::class)) {
                $this->callWithProgressBar('Integrations', IntegrationSeeder::class);
            }
            if (class_exists(ReportTemplateSeeder::class)) {
                $this->callWithProgressBar('Report Templates', ReportTemplateSeeder::class);
            }

            // Commit transaction
            DB::commit();

            $this->command->newLine();
            $this->command->info('✓ Development database seeding completed successfully!');
            $this->command->info('Database is now populated with comprehensive test data.');
            $this->command->newLine();

            // Display summary
            $this->displaySummary();

        } catch (\Exception $e) {
            DB::rollBack();
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
                ['Networks', \App\Models\Network::count() ?? 0],
                ['Tax Jurisdictions', \App\Models\TaxJurisdiction::count() ?? 0],
            ]
        );
    }
}