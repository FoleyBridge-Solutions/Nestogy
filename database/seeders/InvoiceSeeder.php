<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run()
    {
        // Skip if invoices already exist
        if (Invoice::count() > 0) {
            $this->command->info('Invoices already exist, skipping seeder.');

            return;
        }

        // Get the first company (or create one if none exists)
        $company = Company::first();
        if (! $company) {
            $company = Company::create([
                'name' => 'Demo Company',
                'email' => 'demo@company.com',
                'phone' => '555-0100',
                'address' => '123 Business St',
                'city' => 'New York',
                'state' => 'NY',
                'zip' => '10001',
                'country' => 'USA',
            ]);
        }

        // Get or create some clients
        $clients = Client::where('company_id', $company->id)->take(3)->get();

        if ($clients->count() < 3) {
            // Create some demo clients if we don't have enough
            $clientData = [
                ['name' => 'Acme Corporation', 'email' => 'billing@acme.com', 'phone' => '555-0101'],
                ['name' => 'TechCo Industries', 'email' => 'accounts@techco.com', 'phone' => '555-0102'],
                ['name' => 'Global Solutions LLC', 'email' => 'finance@globalsolutions.com', 'phone' => '555-0103'],
            ];

            foreach ($clientData as $data) {
                $client = Client::firstOrCreate(
                    ['email' => $data['email'], 'company_id' => $company->id],
                    array_merge($data, [
                        'company_id' => $company->id,
                        'address' => '456 Client Ave',
                        'city' => 'Los Angeles',
                        'state' => 'CA',
                        'zip' => '90001',
                        'country' => 'USA',
                        'status' => 'active',
                    ])
                );
                $clients->push($client);
            }

            $clients = $clients->take(3);
        }

        // Create invoices with different statuses
        $invoiceData = [
            // Overdue invoices
            [
                'client' => $clients[0],
                'invoice_number' => 'INV-2024-001',
                'status' => 'sent',
                'issue_date' => Carbon::now()->subDays(45),
                'due_date' => Carbon::now()->subDays(15),
                'items' => [
                    ['description' => 'Monthly Managed Services', 'quantity' => 1, 'price' => 2500.00],
                    ['description' => 'Emergency Support (3 hours)', 'quantity' => 3, 'price' => 150.00],
                ],
            ],
            [
                'client' => $clients[1],
                'invoice_number' => 'INV-2024-002',
                'status' => 'sent',
                'issue_date' => Carbon::now()->subDays(35),
                'due_date' => Carbon::now()->subDays(5),
                'items' => [
                    ['description' => 'Server Maintenance', 'quantity' => 1, 'price' => 1800.00],
                    ['description' => 'Backup Solution Setup', 'quantity' => 1, 'price' => 750.00],
                ],
            ],

            // Current/recent invoices
            [
                'client' => $clients[2],
                'invoice_number' => 'INV-2024-003',
                'status' => 'paid',
                'issue_date' => Carbon::now()->subDays(20),
                'due_date' => Carbon::now()->addDays(10),
                'paid_date' => Carbon::now()->subDays(5),
                'items' => [
                    ['description' => 'Network Security Audit', 'quantity' => 1, 'price' => 3500.00],
                    ['description' => 'Firewall Configuration', 'quantity' => 1, 'price' => 850.00],
                ],
            ],
            [
                'client' => $clients[0],
                'invoice_number' => 'INV-2024-004',
                'status' => 'sent',
                'issue_date' => Carbon::now()->subDays(10),
                'due_date' => Carbon::now()->addDays(20),
                'items' => [
                    ['description' => 'Cloud Migration Services', 'quantity' => 40, 'price' => 125.00],
                    ['description' => 'Training Session', 'quantity' => 2, 'price' => 500.00],
                ],
            ],

            // Draft invoices
            [
                'client' => $clients[1],
                'invoice_number' => 'INV-2024-005',
                'status' => 'draft',
                'issue_date' => Carbon::now(),
                'due_date' => Carbon::now()->addDays(30),
                'items' => [
                    ['description' => 'Software License Renewal', 'quantity' => 25, 'price' => 45.00],
                    ['description' => 'Implementation Support', 'quantity' => 8, 'price' => 175.00],
                ],
            ],
            [
                'client' => $clients[2],
                'invoice_number' => 'INV-2024-006',
                'status' => 'draft',
                'issue_date' => Carbon::now(),
                'due_date' => Carbon::now()->addDays(30),
                'items' => [
                    ['description' => 'Quarterly IT Review', 'quantity' => 1, 'price' => 950.00],
                    ['description' => 'Hardware Procurement', 'quantity' => 1, 'price' => 4200.00],
                ],
            ],

            // More paid invoices
            [
                'client' => $clients[0],
                'invoice_number' => 'INV-2024-007',
                'status' => 'paid',
                'issue_date' => Carbon::now()->subDays(60),
                'due_date' => Carbon::now()->subDays(30),
                'paid_date' => Carbon::now()->subDays(35),
                'items' => [
                    ['description' => 'Annual Support Contract', 'quantity' => 1, 'price' => 12000.00],
                ],
            ],
            [
                'client' => $clients[1],
                'invoice_number' => 'INV-2024-008',
                'status' => 'paid',
                'issue_date' => Carbon::now()->subDays(50),
                'due_date' => Carbon::now()->subDays(20),
                'paid_date' => Carbon::now()->subDays(25),
                'items' => [
                    ['description' => 'Email Migration Project', 'quantity' => 1, 'price' => 3750.00],
                    ['description' => 'User Training', 'quantity' => 15, 'price' => 85.00],
                ],
            ],
        ];

        foreach ($invoiceData as $data) {
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $subtotal += $item['quantity'] * $item['price'];
            }

            $tax = $subtotal * 0.08; // 8% tax
            $total = $subtotal + $tax;

            // Extract number from invoice number (e.g., INV-2024-001 -> 1)
            preg_match('/(\d+)$/', $data['invoice_number'], $matches);
            $invoiceNumber = isset($matches[1]) ? (int) $matches[1] : rand(1000, 9999);

            // Get first category or create a default one
            $category = \App\Models\Category::first();
            if (! $category) {
                $category = \App\Models\Category::create([
                    'company_id' => $company->id,
                    'name' => 'Services',
                    'type' => 'income',
                ]);
            }

            $invoice = Invoice::create([
                'company_id' => $company->id,
                'client_id' => $data['client']->id,
                'category_id' => $category->id,
                'prefix' => 'INV',
                'number' => $invoiceNumber,
                'status' => $data['status'],
                'date' => $data['issue_date'],
                'due' => $data['due_date'],
                'due_date' => $data['due_date'],
                'amount' => $total,
                'note' => 'Thank you for your business! Payment due within 30 days.',
                'currency_code' => 'USD',
            ]);

            // Create invoice items
            $order = 1;
            foreach ($data['items'] as $item) {
                $itemTotal = $item['quantity'] * $item['price'];
                InvoiceItem::create([
                    'company_id' => $company->id,
                    'invoice_id' => $invoice->id,
                    'name' => $item['description'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $itemTotal,
                    'total' => $itemTotal,
                    'order' => $order++,
                ]);
            }
        }

        $this->command->info('Created '.count($invoiceData).' sample invoices with items.');
    }
}
