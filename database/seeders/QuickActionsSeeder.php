<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CustomQuickAction;
use Illuminate\Database\Seeder;

class QuickActionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all companies to create default actions for each
        $companies = Company::all();

        foreach ($companies as $company) {
            $this->createDefaultActionsForCompany($company->id);
        }
    }

    /**
     * Create default quick actions for a specific company
     */
    public function createDefaultActionsForCompany(int $companyId): void
    {
        $defaultActions = [
            // Common actions for all users
            [
                'company_id' => $companyId,
                'title' => 'New Client',
                'description' => 'Add a new client to the system',
                'icon' => 'user-plus',
                'color' => 'green',
                'type' => 'route',
                'target' => 'clients.create',
                'open_in' => 'same_tab',
                'visibility' => 'company',
                'permission' => 'clients.create',
                'position' => 1,
            ],
            [
                'company_id' => $companyId,
                'title' => 'Create Invoice',
                'description' => 'Generate a new invoice for a client',
                'icon' => 'document-plus',
                'color' => 'blue',
                'type' => 'route',
                'target' => 'financial.invoices.create',
                'open_in' => 'same_tab',
                'visibility' => 'company',
                'permission' => 'financial.invoices.manage',
                'position' => 2,
            ],
            [
                'company_id' => $companyId,
                'title' => 'Create Ticket',
                'description' => 'Open a new support ticket',
                'icon' => 'plus-circle',
                'color' => 'blue',
                'type' => 'route',
                'target' => 'tickets.create',
                'open_in' => 'same_tab',
                'visibility' => 'company',
                'permission' => null, // No specific permission needed
                'position' => 3,
            ],
            [
                'company_id' => $companyId,
                'title' => 'View Assets',
                'description' => 'Monitor client assets',
                'icon' => 'server',
                'color' => 'green',
                'type' => 'route',
                'target' => 'assets.index',
                'open_in' => 'same_tab',
                'visibility' => 'company',
                'permission' => 'assets.view',
                'position' => 4,
            ],
            [
                'company_id' => $companyId,
                'title' => 'Financial Reports',
                'description' => 'View financial reports',
                'icon' => 'chart-pie',
                'color' => 'purple',
                'type' => 'route',
                'target' => 'reports.financial',
                'open_in' => 'same_tab',
                'visibility' => 'company',
                'permission' => 'reports.financial',
                'position' => 5,
            ],
            [
                'company_id' => $companyId,
                'title' => 'Record Payment',
                'description' => 'Log a payment received',
                'icon' => 'currency-dollar',
                'color' => 'green',
                'type' => 'route',
                'target' => 'financial.payments.create',
                'open_in' => 'same_tab',
                'visibility' => 'company',
                'permission' => 'financial.payments.manage',
                'position' => 6,
            ],
            [
                'company_id' => $companyId,
                'title' => 'Collections',
                'description' => 'Manage overdue accounts',
                'icon' => 'exclamation-triangle',
                'color' => 'orange',
                'type' => 'route',
                'target' => 'financial.collections.index',
                'open_in' => 'same_tab',
                'visibility' => 'company',
                'permission' => 'financial.view',
                'position' => 7,
            ],
            [
                'company_id' => $companyId,
                'title' => 'Knowledge Base',
                'description' => 'Search solutions',
                'icon' => 'academic-cap',
                'color' => 'purple',
                'type' => 'route',
                'target' => 'settings.knowledge-base',
                'open_in' => 'same_tab',
                'visibility' => 'company',
                'permission' => null, // No specific permission needed
                'position' => 8,
            ],
            [
                'company_id' => $companyId,
                'title' => 'Compose Email',
                'description' => 'Send an email to clients',
                'icon' => 'pencil',
                'color' => 'green',
                'type' => 'route',
                'target' => 'email.compose.index',
                'open_in' => 'same_tab',
                'visibility' => 'company',
                'permission' => null, // No specific permission needed
                'position' => 9,
            ],
            [
                'company_id' => $companyId,
                'title' => 'View Tickets',
                'description' => 'View all tickets',
                'icon' => 'ticket',
                'color' => 'blue',
                'type' => 'route',
                'target' => 'tickets.index',
                'open_in' => 'same_tab',
                'visibility' => 'company',
                'permission' => null, // No specific permission needed
                'position' => 10,
            ],
            [
                'company_id' => $companyId,
                'title' => 'Time Entry',
                'description' => 'Log time worked',
                'icon' => 'clock',
                'color' => 'green',
                'type' => 'route',
                'target' => 'tickets.time-tracking.create',
                'open_in' => 'same_tab',
                'visibility' => 'company',
                'permission' => null, // No specific permission needed
                'position' => 11,
            ],
            [
                'company_id' => $companyId,
                'title' => 'Documentation',
                'description' => 'Browse documentation',
                'icon' => 'book-open',
                'color' => 'orange',
                'type' => 'route',
                'target' => 'clients.it-documentation.index',
                'open_in' => 'same_tab',
                'visibility' => 'company',
                'permission' => 'clients.documents.view',
                'position' => 12,
            ],
            [
                'company_id' => $companyId,
                'title' => 'Email Accounts',
                'description' => 'Manage email accounts',
                'icon' => 'cog-6-tooth',
                'color' => 'blue',
                'type' => 'route',
                'target' => 'email.accounts.index',
                'open_in' => 'same_tab',
                'visibility' => 'company',
                'permission' => null, // No specific permission needed
                'position' => 13,
            ],

            // External links examples
            [
                'company_id' => $companyId,
                'title' => 'RMM Portal',
                'description' => 'Access remote monitoring',
                'icon' => 'computer-desktop',
                'color' => 'red',
                'type' => 'url',
                'target' => 'https://rmm.example.com',
                'open_in' => 'new_tab',
                'visibility' => 'company',
                'permission' => null, // No specific permission needed
                'position' => 14,
            ],
            [
                'company_id' => $companyId,
                'title' => 'Status Page',
                'description' => 'Check system status',
                'icon' => 'signal',
                'color' => 'green',
                'type' => 'url',
                'target' => 'https://status.example.com',
                'open_in' => 'new_tab',
                'visibility' => 'company',
                'position' => 15,
            ],
        ];

        foreach ($defaultActions as $action) {
            // Check if action already exists to avoid duplicates
            $exists = CustomQuickAction::where('company_id', $companyId)
                ->where('title', $action['title'])
                ->exists();

            if (! $exists) {
                CustomQuickAction::create($action);
            }
        }
    }
}
