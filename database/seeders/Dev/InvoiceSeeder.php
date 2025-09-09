<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Client;
use App\Models\Company;
use App\Models\Category;
use App\Domains\Contract\Models\Contract;
use Carbon\Carbon;
use Faker\Factory as Faker;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Invoice Seeder...');
        $faker = Faker::create();

        DB::transaction(function () use ($faker) {
            $companies = Company::where('id', '>', 1)->get();

            foreach ($companies as $company) {
                $this->command->info("Creating invoices for company: {$company->name}");
                
                $categories = Category::where('company_id', $company->id)
                    ->where('type', 'invoice')
                    ->pluck('id')->toArray();
                    
                if (empty($categories)) {
                    // Create a default category if none exist
                    $defaultCategory = Category::create([
                        'company_id' => $company->id,
                        'name' => 'Services',
                        'type' => 'invoice',
                        'color' => '#4A90E2'
                    ]);
                    $categories = [$defaultCategory->id];
                }
                
                $clients = Client::where('company_id', $company->id)
                    ->where('lead', false)
                    ->get();
                
                // Generate 6 months of invoices
                for ($month = 5; $month >= 0; $month--) {
                    $invoiceDate = Carbon::now()->subMonths($month)->startOfMonth();
                    
                    foreach ($clients as $client) {
                        // Check if client has a contract
                        $contract = Contract::where('client_id', $client->id)
                            ->where('status', 'active')
                            ->first();
                        
                        if ($contract) {
                            // Monthly recurring invoice for contract
                            $this->createRecurringInvoice($client, $company, $contract, $invoiceDate, $categories, $faker);
                        }
                        
                        // Random chance for ad-hoc invoice
                        if ($faker->boolean(30)) {
                            $this->createAdHocInvoice($client, $company, $invoiceDate, $categories, $faker);
                        }
                    }
                }
                
                $this->command->info("Completed invoices for company: {$company->name}");
            }
        });

        $this->command->info('Invoice Seeder completed!');
    }

    /**
     * Create recurring invoice for contract
     */
    private function createRecurringInvoice($client, $company, $contract, $invoiceDate, $categories, $faker)
    {
        $dueDate = Carbon::instance($invoiceDate)->addDays(30); // Net 30
        
        // Determine status based on age
        $age = $invoiceDate->diffInDays(now());
        if ($age > 60) {
            $status = $faker->randomElement(['paid', 'paid', 'paid', 'overdue']); // 75% paid, 25% overdue
        } elseif ($age > 30) {
            $status = $faker->randomElement(['paid', 'paid', 'sent']); // 66% paid, 33% sent
        } else {
            $status = $faker->randomElement(['sent', 'draft']);
        }
        
        // Calculate monthly value from contract
        $monthlyValue = $contract->contract_value / ($contract->term_months ?: 12);
        
        $invoice = Invoice::create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'contract_id' => $contract->id,
            'invoice_number' => 'INV-' . Carbon::parse($invoiceDate)->format('Ym') . '-' . str_pad($faker->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'status' => $status,
            'category_id' => $faker->randomElement($categories),
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'subtotal' => $monthlyValue,
            'tax_amount' => $monthlyValue * 0.08, // 8% tax
            'discount_amount' => 0,
            'total' => $monthlyValue * 1.08,
            'amount_paid' => $status === 'paid' ? $monthlyValue * 1.08 : 0,
            'balance_due' => $status === 'paid' ? 0 : $monthlyValue * 1.08,
            'notes' => 'Monthly managed services fee',
            'terms' => 'Net 30',
            'created_at' => $invoiceDate,
            'updated_at' => $status === 'paid' ? Carbon::instance($invoiceDate)->addDays($faker->numberBetween(15, 30)) : $invoiceDate,
        ]);
        
        // Create invoice items
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Managed Services - ' . Carbon::parse($invoiceDate)->format('F Y'),
            'quantity' => 1,
            'price' => $monthlyValue,
            'total' => $monthlyValue,
            'category_id' => $faker->randomElement($categories),
            'taxable' => true,
        ]);
    }

    /**
     * Create ad-hoc invoice
     */
    private function createAdHocInvoice($client, $company, $invoiceDate, $categories, $faker)
    {
        $dueDate = Carbon::instance($invoiceDate)->addDays($faker->randomElement([15, 30, 45]));
        
        // Determine status
        $age = $invoiceDate->diffInDays(now());
        if ($age > 45) {
            $status = $faker->randomElement(['paid', 'paid', 'overdue']);
        } else {
            $status = $faker->randomElement(['sent', 'draft']);
        }
        
        // Generate random items
        $itemCount = $faker->numberBetween(1, 5);
        $subtotal = 0;
        $items = [];
        
        for ($i = 0; $i < $itemCount; $i++) {
            $itemType = $faker->randomElement(['service', 'product', 'project']);
            $quantity = $itemType === 'service' ? $faker->randomFloat(2, 0.5, 8) : $faker->numberBetween(1, 10);
            $price = $itemType === 'service' ? $faker->randomElement([125, 150, 175, 200]) : $faker->randomFloat(2, 50, 500);
            $total = $quantity * $price;
            $subtotal += $total;
            
            $items[] = [
                'description' => $this->getItemDescription($itemType, $faker),
                'quantity' => $quantity,
                'price' => $price,
                'total' => $total,
                'taxable' => $faker->boolean(80),
            ];
        }
        
        $taxAmount = $subtotal * 0.08;
        $discountAmount = $faker->boolean(20) ? $subtotal * $faker->randomFloat(2, 0.05, 0.15) : 0;
        $total = $subtotal + $taxAmount - $discountAmount;
        
        $invoice = Invoice::create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'invoice_number' => 'INV-' . Carbon::parse($invoiceDate)->format('Ym') . '-' . str_pad($faker->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'status' => $status,
            'category_id' => $faker->randomElement($categories),
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total' => $total,
            'amount_paid' => $status === 'paid' ? $total : 0,
            'balance_due' => $status === 'paid' ? 0 : $total,
            'notes' => $faker->optional(0.3)->sentence(),
            'terms' => 'Net ' . $dueDate->diffInDays($invoiceDate),
            'created_at' => $invoiceDate,
            'updated_at' => $status === 'paid' ? Carbon::instance($invoiceDate)->addDays($faker->numberBetween(10, 30)) : $invoiceDate,
        ]);
        
        // Create invoice items
        foreach ($items as $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'total' => $item['total'],
                'category_id' => $faker->randomElement($categories),
                'taxable' => $item['taxable'],
            ]);
        }
    }

    /**
     * Get item description
     */
    private function getItemDescription($type, $faker)
    {
        $descriptions = [
            'service' => [
                'On-site support visit',
                'Remote support hours',
                'Emergency response',
                'System maintenance',
                'Security assessment',
                'Network configuration',
                'Server administration',
                'Help desk support'
            ],
            'product' => [
                'Antivirus license',
                'Backup software',
                'Microsoft Office license',
                'Network cable',
                'Ethernet switch',
                'Wireless access point',
                'External hard drive',
                'UPS battery replacement'
            ],
            'project' => [
                'Server migration - Phase 1',
                'Network upgrade - Implementation',
                'Security audit - Final report',
                'Cloud setup - Configuration',
                'Database optimization',
                'Email migration',
                'Firewall installation',
                'VPN setup'
            ]
        ];
        
        return $faker->randomElement($descriptions[$type]);
    }
}