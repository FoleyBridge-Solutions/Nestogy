<?php

namespace App\Domains\Documentation\Content;

class GettingStartedContent
{
    public static function get(): array
    {
        return [
            'intro' => [
                'type' => 'text',
                'content' => 'Nestogy is a comprehensive ERP system designed specifically for Managed Service Providers (MSPs). This guide will help you get started with the platform and learn the basics of managing your MSP business with Nestogy.',
            ],
            
            'sections' => [
                [
                    'heading' => 'What You\'ll Learn',
                    'type' => 'list',
                    'items' => [
                        'Logging in and accessing your account',
                        'Understanding the dashboard and navigation',
                        'Setting up your first client',
                        'Creating your first ticket',
                        'Generating your first invoice',
                    ],
                ],
                
                [
                    'heading' => 'Step 1: Logging In',
                    'type' => 'section',
                    'content' => 'To access Nestogy, navigate to your Nestogy URL and enter your credentials:',
                    'list' => [
                        'type' => 'ordered',
                        'items' => [
                            'Go to `https://your-domain.com`',
                            'Enter your email address',
                            'Enter your password',
                            'Click **Login**',
                        ],
                    ],
                    'callout' => [
                        'type' => 'info',
                        'icon' => 'information-circle',
                        'color' => 'blue',
                        'heading' => 'First Time Login',
                        'content' => 'If this is your first time logging in, you may be prompted to set up two-factor authentication (2FA) for enhanced security. We highly recommend enabling this feature.',
                    ],
                ],
                
                [
                    'heading' => 'Step 2: Exploring the Dashboard',
                    'type' => 'section',
                    'content' => 'Once logged in, you\'ll see the Nestogy dashboard. The dashboard provides an overview of your business including:',
                    'list' => [
                        'type' => 'unordered',
                        'items' => [
                            '**Open Tickets** - Active support requests that need attention',
                            '**Recent Activity** - Latest updates across clients and tickets',
                            '**Quick Actions** - Shortcuts to common tasks like creating tickets or invoices',
                            '**Performance Metrics** - Key performance indicators (KPIs) for your MSP',
                        ],
                    ],
                    'link' => [
                        'text' => 'Learn more about the dashboard in the Dashboard & Navigation guide.',
                        'route' => 'dashboard',
                    ],
                ],
                
                [
                    'heading' => 'Step 3: Understanding Navigation',
                    'type' => 'section',
                    'content' => 'Nestogy uses a clean, intuitive navigation system:',
                    'list' => [
                        'type' => 'unordered',
                        'items' => [
                            '**Sidebar Navigation** - Access all major features from the left sidebar',
                            '**Command Palette** - Press `Cmd/Ctrl + K` to quickly search and navigate',
                            '**Client Switcher** - Easily switch between different client contexts',
                            '**User Menu** - Access your profile, settings, and logout from the top right',
                        ],
                    ],
                ],
                
                [
                    'heading' => 'Step 4: Creating Your First Client',
                    'type' => 'section',
                    'content' => 'Before you can create tickets or invoices, you need to set up at least one client:',
                    'list' => [
                        'type' => 'ordered',
                        'items' => [
                            'Click **Clients** in the sidebar navigation',
                            'Click the **Create Client** button',
                            'Fill in the client information:\n• Company name (required)\n• Primary contact name and email\n• Phone number\n• Address information',
                            'Click **Save** to create the client',
                        ],
                    ],
                    'link' => [
                        'text' => 'For detailed information about managing clients, see the Client Management guide.',
                        'route' => 'clients',
                    ],
                ],
                
                [
                    'heading' => 'Step 5: Creating Your First Ticket',
                    'type' => 'section',
                    'content' => 'Tickets are the core of your support workflow in Nestogy:',
                    'list' => [
                        'type' => 'ordered',
                        'items' => [
                            'Select a client using the client switcher',
                            'Click **Tickets** in the sidebar or use the **New Ticket** quick action',
                            'Fill in the ticket details:\n• Title (brief description of the issue)\n• Description (detailed information)\n• Priority (Low, Medium, High, Critical)\n• Assign to a technician',
                            'Click **Create Ticket**',
                        ],
                    ],
                    'link' => [
                        'text' => 'Learn more in the Ticket System guide.',
                        'route' => 'tickets',
                    ],
                ],
                
                [
                    'heading' => 'Step 6: Generating Your First Invoice',
                    'type' => 'section',
                    'content' => 'Create and send professional invoices to your clients:',
                    'list' => [
                        'type' => 'ordered',
                        'items' => [
                            'Select a client using the client switcher',
                            'Navigate to **Financial** → **Invoices**',
                            'Click **Create Invoice**',
                            'Add line items (services, products, or time entries)',
                            'Review the totals and tax calculations',
                            'Click **Save & Send** to email the invoice to your client',
                        ],
                    ],
                    'link' => [
                        'text' => 'See the Invoice & Billing guide for more details.',
                        'route' => 'invoices',
                    ],
                ],
            ],
            
            'next_steps' => [
                'heading' => 'Next Steps',
                'content' => 'Now that you understand the basics, explore these topics to get more out of Nestogy:',
                'cards' => [
                    [
                        'icon' => 'home',
                        'title' => 'Dashboard & Navigation',
                        'description' => 'Master the Nestogy interface and navigation',
                        'route' => 'dashboard',
                    ],
                    [
                        'icon' => 'document-text',
                        'title' => 'Contract Management',
                        'description' => 'Set up service agreements and SLAs',
                        'route' => 'contracts',
                    ],
                    [
                        'icon' => 'server',
                        'title' => 'Asset Management',
                        'description' => 'Track equipment and integrate with RMM tools',
                        'route' => 'assets',
                    ],
                    [
                        'icon' => 'clock',
                        'title' => 'Time Tracking',
                        'description' => 'Track time and generate accurate timesheets',
                        'route' => 'time-tracking',
                    ],
                ],
            ],
            
            'help' => [
                'heading' => 'Need Help?',
                'content' => 'If you have questions or need assistance:',
                'list' => [
                    'type' => 'unordered',
                    'items' => [
                        'Check the [FAQ page](/docs/faq) for common questions',
                        'Contact our support team at [support@nestogy.com](mailto:support@nestogy.com)',
                        'Browse the full documentation using the sidebar navigation',
                    ],
                ],
                'callout' => [
                    'type' => 'success',
                    'icon' => 'check-circle',
                    'color' => 'green',
                    'heading' => 'You\'re All Set!',
                    'content' => 'You now know the basics of Nestogy. Explore the documentation to discover all the powerful features available to help you manage your MSP business efficiently.',
                ],
            ],
        ];
    }
}
