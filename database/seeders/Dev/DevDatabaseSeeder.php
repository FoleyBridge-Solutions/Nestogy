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
        if (! App::environment(['local', 'development', 'testing'])) {
            $this->command->error('Development seeders can only run in local/development/testing environments!');

            return;
        }

        $this->command->info('Starting Nestogy Mid-Market MSP Development Database Seeding...');
        $this->command->info('Creating realistic test data for mid-market MSPs (10-75 employees) with 2 years of history.');
        $this->command->newLine();

        // Removed transaction for debugging
        // DB::beginTransaction();

        try {
            // Foundation seeders - Realistic mid-market MSP data
            $this->callWithProgressBar('Companies (10 mid-market MSPs)', CompanySeeder::class);
            $this->callWithProgressBar('Settings', SettingsSeeder::class);
            $this->callWithProgressBar('Roles and Permissions', RolesAndPermissionsSeeder::class);
            $this->callWithProgressBar('Users (20-40 per company)', UserSeeder::class);
            $this->callWithProgressBar('Categories', CategorySeeder::class);
            $this->callWithProgressBar('Vendors (20-30)', VendorSeeder::class);

            // SLA must be before Clients as clients reference SLAs
            if (class_exists(SLASeeder::class)) {
                $this->callWithProgressBar('SLA Levels', SLASeeder::class);
            }

            $this->callWithProgressBar('Clients (30-80 per MSP)', ClientSeeder::class);
            $this->callWithProgressBar('Locations (1-3 per client)', LocationSeeder::class);
            $this->callWithProgressBar('Contacts (2-5 per client)', ContactSeeder::class);
            if (class_exists(NetworkSeeder::class)) {
                $this->callWithProgressBar('Networks', NetworkSeeder::class);
            }
            if (class_exists(TaxSeeder::class)) {
                $this->callWithProgressBar('Tax Configuration', TaxSeeder::class);
            }
            if (class_exists(ProductSeeder::class)) {
                $this->callWithProgressBar('Products & Services (50-100)', ProductSeeder::class);
            }

            // Operational seeders - Realistic volumes with historical data
            if (class_exists(AssetSeeder::class)) {
                $this->callWithProgressBar('Assets (50-200 per client)', AssetSeeder::class);
            }
            if (class_exists(AssetWarrantySeeder::class)) {
                $this->callWithProgressBar('Asset Warranties', AssetWarrantySeeder::class);
            }
            if (class_exists(ContractTemplateSeeder::class)) {
                $this->callWithProgressBar('Contract Templates', ContractTemplateSeeder::class);
            }
            if (class_exists(ContractSeeder::class)) {
                $this->callWithProgressBar('Contracts (1 per client)', ContractSeeder::class);
            }
            if (class_exists(TicketSeeder::class)) {
                $this->callWithProgressBar('Tickets (200-500/month per MSP)', TicketSeeder::class);
            }
            if (class_exists(TicketReplySeeder::class)) {
                $this->callWithProgressBar('Ticket Replies (2-5 per ticket)', TicketReplySeeder::class);
            }
            if (class_exists(ProjectSeeder::class)) {
                $this->callWithProgressBar('Projects (5-15 per MSP)', ProjectSeeder::class);
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
                $this->callWithProgressBar('Leads (10-30 per MSP)', LeadSeeder::class);
            }
            if (class_exists(QuoteSeeder::class)) {
                $this->callWithProgressBar('Quotes (10-30 per MSP)', QuoteSeeder::class);
            }
            if (class_exists(ExpenseSeeder::class)) {
                $this->callWithProgressBar('Expenses (2yr history)', ExpenseSeeder::class);
            }
            if (class_exists(KnowledgeBaseSeeder::class)) {
                $this->callWithProgressBar('Knowledge Base Articles (20-50 per MSP)', KnowledgeBaseSeeder::class);
            }
            if (class_exists(IntegrationSeeder::class)) {
                $this->callWithProgressBar('Integrations', IntegrationSeeder::class);
            }
            if (class_exists(ReportTemplateSeeder::class)) {
                $this->callWithProgressBar('Report Templates', ReportTemplateSeeder::class);
            }
            if (class_exists(EmailSeeder::class)) {
                $this->callWithProgressBar('Emails (100-300 per MSP)', EmailSeeder::class);
            }
            if (class_exists(ActivityLogSeeder::class)) {
                $this->callWithProgressBar('Activity Logs (2yr history)', ActivityLogSeeder::class);
            }
            if (class_exists(NotificationSeeder::class)) {
                $this->callWithProgressBar('Notifications', NotificationSeeder::class);
            }

            // Additional comprehensive seeders
            $this->callWithProgressBar('Accounts', AccountSeeder::class);
            $this->callWithProgressBar('Account Holds', AccountHoldSeeder::class);
            $this->callWithProgressBar('Addresses', AddressSeeder::class);
            $this->callWithProgressBar('Analytics Snapshots', AnalyticsSnapshotSeeder::class);
            $this->callWithProgressBar('Audit Logs', AuditLogSeeder::class);
            $this->callWithProgressBar('Auto Payments', AutoPaymentSeeder::class);
            $this->callWithProgressBar('Client Documents', ClientDocumentSeeder::class);
            $this->callWithProgressBar('Client Portal Users', ClientPortalUserSeeder::class);
            $this->callWithProgressBar('Client Portal Sessions', ClientPortalSessionSeeder::class);
            $this->callWithProgressBar('Communication Logs', CommunicationLogSeeder::class);
            $this->callWithProgressBar('Company Customizations', CompanyCustomizationSeeder::class);
            $this->callWithProgressBar('Company Mail Settings', CompanyMailSettingsSeeder::class);
            $this->callWithProgressBar('Dashboard Widgets', DashboardWidgetSeeder::class);
            $this->callWithProgressBar('Documents', DocumentSeeder::class);
            $this->callWithProgressBar('Expense Categories', ExpenseCategorySeeder::class);
            $this->callWithProgressBar('In-App Notifications', InAppNotificationSeeder::class);
            $this->callWithProgressBar('Mail Templates', MailTemplateSeeder::class);
            $this->callWithProgressBar('Networks', NetworkSeeder::class);
            $this->callWithProgressBar('Notification Preferences', NotificationPreferenceSeeder::class);
            $this->callWithProgressBar('Payment Methods', PaymentMethodSeeder::class);
            $this->callWithProgressBar('Tags', TagSeeder::class);
            $this->callWithProgressBar('Ticket Comments', TicketCommentSeeder::class);
            $this->callWithProgressBar('Ticket Ratings', TicketRatingSeeder::class);
            $this->callWithProgressBar('Ticket Time Entries', TicketTimeEntrySeeder::class);
            $this->callWithProgressBar('Ticket Watchers', TicketWatcherSeeder::class);
            $this->callWithProgressBar('Time Entries', TimeEntrySeeder::class);
            $this->callWithProgressBar('User Settings', UserSettingSeeder::class);

            // Commit transaction
            // DB::commit();

            $this->command->newLine();
            $this->command->info('✓ Development database seeding completed successfully!');
            $this->command->info('Database populated with realistic mid-market MSP test data.');
            $this->command->newLine();

            // Display summary
            $this->displaySummary();

        } catch (\Exception $e) {
            // DB::rollBack();
            $this->command->error('Seeding failed: '.$e->getMessage());
            $this->command->error('Stack trace: '.$e->getTraceAsString());
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
                ['Companies', \App\Domains\Company\Models\Company::count()],
                ['Users', \App\Domains\Core\Models\User::count()],
                ['User Settings', \App\Domains\Core\Models\UserSetting::count()],
                ['Clients', \App\Domains\Client\Models\Client::count()],
                ['Contacts', \App\Domains\Client\Models\ClientContact::count()],
                ['Locations', \App\Domains\Client\Models\Location::count()],
                ['Addresses', \App\Domains\Client\Models\Address::count()],
                ['Vendors', \App\Domains\Project\Models\Vendor::count()],
                ['Categories', \App\Domains\Financial\Models\Category::count()],
                ['Expense Categories', \App\Domains\Financial\Models\Category::count()],
                ['Assets', \App\Domains\Asset\Models\Asset::count()],
                ['Tickets', \App\Domains\Ticket\Models\Ticket::count()],
                ['Ticket Replies', \App\Domains\Ticket\Models\TicketComment::count()],
                ['Ticket Comments', \App\Domains\Ticket\Models\TicketComment::count()],
                ['Ticket Time Entries', \App\Domains\Ticket\Models\TicketTimeEntry::count()],
                ['Ticket Watchers', \App\Domains\Ticket\Models\TicketWatcher::count()],
                ['Ticket Ratings', \App\Domains\Ticket\Models\TicketRating::count()],
                ['Projects', \App\Domains\Project\Models\Project::count()],
                ['Time Entries', \App\Domains\Ticket\Models\TimeEntry::count()],
                ['Invoices', \App\Domains\Financial\Models\Invoice::count()],
                ['Payments', \App\Domains\Financial\Models\Payment::count()],
                ['Payment Methods', \App\Domains\Financial\Models\PaymentMethod::count()],
                ['Accounts', \App\Domains\Company\Models\Account::count()],
                ['Account Holds', \App\Domains\Company\Models\AccountHold::count()],
                ['Leads', \App\Domains\Lead\Models\Lead::count()],
                ['Quotes', \App\Domains\Financial\Models\Quote::count()],
                ['Expenses', \App\Domains\Financial\Models\Expense::count()],
                ['Documents', \App\Domains\Core\Models\Document::count()],
                ['Client Documents', \App\Domains\Client\Models\ClientDocument::count()],
                ['Networks', \App\Domains\Client\Models\ClientNetwork::count()],
                ['Tags', \App\Domains\Core\Models\Tag::count()],
                ['Communication Logs', \App\Domains\Client\Models\CommunicationLog::count()],
                ['Audit Logs', \App\Domains\Core\Models\AuditLog::count()],
                ['In-App Notifications', \App\Domains\Core\Models\InAppNotification::count()],
                ['Mail Templates', \App\Domains\Core\Models\MailTemplate::count()],
                ['Dashboard Widgets', \App\Domains\Core\Models\DashboardWidget::count()],
                ['Client Portal Users', \App\Domains\Client\Models\ClientPortalUser::count()],
                ['Client Portal Sessions', \App\Domains\Client\Models\ClientPortalSession::count()],
                ['Analytics Snapshots', \App\Domains\Core\Models\AnalyticsSnapshot::count()],
                ['Auto Payments', \App\Domains\Financial\Models\AutoPayment::count()],
                ['Company Customizations', \App\Domains\Company\Models\CompanyCustomization::count()],
                ['Company Mail Settings', \App\Domains\Company\Models\CompanyMailSettings::count()],
                ['Notification Preferences', \App\Domains\Core\Models\NotificationPreference::count()],
            ]
        );

        // Display date range of data
        $oldestTicket = \App\Domains\Ticket\Models\Ticket::oldest('created_at')->first();
        $newestTicket = \App\Domains\Ticket\Models\Ticket::latest('created_at')->first();
        $oldestInvoice = \App\Domains\Financial\Models\Invoice::oldest('date')->first();
        $newestInvoice = \App\Domains\Financial\Models\Invoice::latest('date')->first();

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
