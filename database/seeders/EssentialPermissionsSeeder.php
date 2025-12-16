<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Silber\Bouncer\BouncerFacade as Bouncer;

/**
 * Essential Permissions Seeder
 *
 * Creates wildcard and base permissions that aren't auto-discovered
 * from policies but are required by role templates.
 *
 * These permissions are used in config/role-templates.php for
 * automatic role creation when new companies are onboarded.
 */
class EssentialPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "🔐 Creating essential permissions...\n";

        // Wildcard permissions (for broad access grants)
        $wildcards = [
            'clients.*' => 'Full client management',
            'assets.*' => 'Full asset management',
            'tickets.*' => 'Full ticket management',
            'contracts.*' => 'Full contract management',
            'projects.*' => 'Full project management',
            'financial.*' => 'Full financial management',
            'users.*' => 'Full user management',
            'settings.*' => 'Full settings management',
            'reports.*' => 'Full reports access',
            'knowledge.*' => 'Full knowledge base access',
            'leads.*' => 'Full leads management',
            'marketing.*' => 'Full marketing access',
        ];

        foreach ($wildcards as $name => $title) {
            Bouncer::ability()->firstOrCreate(
                ['name' => $name],
                ['title' => $title]
            );
        }

        echo '  ✅ Created '.count($wildcards)." wildcard permissions\n";

        // Specific permissions not discovered from policies
        $specific = [
            // Knowledge base
            'knowledge.view' => 'View knowledge base',
            'knowledge.create' => 'Create knowledge base articles',
            'knowledge.edit' => 'Edit knowledge base articles',

            // Reports
            'reports.view' => 'View reports',
            'reports.tickets' => 'View ticket reports',
            'reports.assets' => 'View asset reports',
            'reports.clients' => 'View client reports',
            'reports.financial' => 'View financial reports',
            'reports.export' => 'Export reports',

            // Financial quotes
            'financial.quotes.view' => 'View quotes',
            'financial.quotes.create' => 'Create quotes',
            'financial.quotes.edit' => 'Edit quotes',
            'financial.quotes.send' => 'Send quotes',

            // Leads
            'leads.view' => 'View leads',
            'leads.create' => 'Create leads',
            'leads.edit' => 'Edit leads',

            // Tickets (base permissions)
            'tickets.view' => 'View tickets',
            'tickets.create' => 'Create tickets',

            // Clients (base permissions)
            'clients.view' => 'View clients',
            'clients.create' => 'Create clients',
            'clients.edit' => 'Edit clients',

            // Contracts
            'contracts.view' => 'View contracts',
            'contracts.create' => 'Create contracts',
            'contracts.financials' => 'View contract financials',

            // Projects
            'projects.view' => 'View projects',
            'projects.manage' => 'Manage projects',

            // Assets (base permissions)
            'assets.view' => 'View assets',

            // Campaign permissions (for marketing)
            'view-campaigns' => 'View campaigns',
            'create-campaigns' => 'Create campaigns',
            'edit-campaigns' => 'Edit campaigns',
            'delete-campaigns' => 'Delete campaigns',
            'manage-campaigns' => 'Manage campaigns',
            'control-campaigns' => 'Control campaigns',
            'enroll-campaigns' => 'Enroll in campaigns',
            'view-campaign-analytics' => 'View campaign analytics',
            'test-campaigns' => 'Test campaigns',
        ];

        foreach ($specific as $name => $title) {
            Bouncer::ability()->firstOrCreate(
                ['name' => $name],
                ['title' => $title]
            );
        }

        echo '  ✅ Created '.count($specific)." specific permissions\n";

        $total = count($wildcards) + count($specific);
        echo "\n✅ Total essential permissions created: {$total}\n";
        echo "💡 Auto-discovered permissions from policies will be added via 'php artisan permissions:discover --sync'\n";
    }
}
