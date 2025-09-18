<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Client;
use App\Models\Category;
use Carbon\Carbon;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating 2 years of invoice history...');
        
        $clients = Client::all();
        $totalInvoices = 0;
        
        // Create income categories if they don't exist
        $companies = \App\Models\Company::all();
        foreach ($companies as $company) {
            if (!Category::where('company_id', $company->id)->where('type', 'income')->exists()) {
                $categories = [
                    ['name' => 'Managed Services', 'color' => '#4A90E2'],
                    ['name' => 'Professional Services', 'color' => '#7C4DFF'],
                    ['name' => 'Hardware', 'color' => '#00BCD4'],
                    ['name' => 'Software Licenses', 'color' => '#FFC107'],
                    ['name' => 'Cloud Services', 'color' => '#8BC34A'],
                    ['name' => 'Support & Maintenance', 'color' => '#FF5722'],
                ];
                
                foreach ($categories as $cat) {
                    Category::create([
                        'company_id' => $company->id,
                        'name' => $cat['name'],
                        'type' => 'income',
                        'color' => $cat['color']
                    ]);
                }
            }
        }

        foreach ($clients as $clientIndex => $client) {
            // Only active clients get regular invoices
            if ($client->status !== 'active') {
                continue;
            }
            
            // Determine invoice frequency based on client size
            $employeeCount = $client->employee_count ?? 50;
            if ($employeeCount > 500) {
                // Enterprise clients - monthly invoicing
                $invoicesPerYear = 12;
            } elseif ($employeeCount > 50) {
                // Medium clients - quarterly or monthly
                $invoicesPerYear = fake()->randomElement([4, 6, 12]);
            } else {
                // Small clients - irregular invoicing
                $invoicesPerYear = fake()->randomElement([2, 4, 6]);
            }
            
            // Generate invoices for the past 2 years
            $startDate = Carbon::now()->subYears(2);
            $endDate = Carbon::now();
            
            // Calculate invoice interval in days
            $intervalDays = (int)(365 / $invoicesPerYear);
            
            $currentDate = $startDate->copy();
            while ($currentDate <= $endDate) {
                // Add some randomness to invoice dates
                $invoiceDate = $currentDate->copy()->addDays(rand(-3, 3));
                
                // Determine status based on age
                $daysOld = $invoiceDate->diffInDays(Carbon::now());
                if ($daysOld > 90) {
                    $status = fake()->randomElement(['paid', 'paid', 'paid', 'paid', 'paid', 'cancelled']);
                } elseif ($daysOld > 45) {
                    $status = fake()->randomElement(['paid', 'paid', 'paid', 'overdue']);
                } elseif ($daysOld > 30) {
                    $status = fake()->randomElement(['paid', 'sent', 'viewed', 'overdue']);
                } else {
                    $status = fake()->randomElement(['sent', 'draft', 'viewed', 'sent']);
                }

                $invoice = Invoice::factory()
                    ->create([
                        'company_id' => $client->company_id,
                        'client_id' => $client->id,
                        'date' => $invoiceDate,
                        'due_date' => $invoiceDate->copy()->addDays(30),
                        'status' => $status,
                        'category_id' => Category::where('company_id', $client->company_id)
                            ->where('type', 'income')
                            ->inRandomOrder()
                            ->first()?->id,
                        'created_at' => $invoiceDate,
                        'updated_at' => fake()->dateTimeBetween($invoiceDate, 'now'),
                    ]);

                // Create invoice items based on client size
                $itemCount = match(true) {
                    $employeeCount > 500 => rand(5, 15),  // Enterprise
                    $employeeCount > 50 => rand(3, 8),    // Medium
                    default => rand(1, 5),                // Small
                };
                
                $subtotal = 0;
                
                for ($j = 0; $j < $itemCount; $j++) {
                    $quantity = fake()->randomFloat(2, 1, 40);
                    
                    // Price varies by client size
                    $price = match(true) {
                        $employeeCount > 500 => fake()->randomFloat(2, 100, 1000),
                        $employeeCount > 50 => fake()->randomFloat(2, 50, 500),
                        default => fake()->randomFloat(2, 25, 250),
                    };
                    
                    $total = $quantity * $price;
                    $subtotal += $total;

                    $tax = fake()->boolean(80) ? $total * 0.08 : 0;
                    $itemDescription = $this->getItemDescription();
                    InvoiceItem::create([
                        'company_id' => $invoice->company_id,
                        'invoice_id' => $invoice->id,
                        'name' => $itemDescription,
                        'description' => $itemDescription . ' - ' . fake()->sentence(),
                        'quantity' => $quantity,
                        'price' => $price,
                        'subtotal' => $total,
                        'tax' => $tax,
                        'total' => $total + $tax,
                        'category_id' => $invoice->category_id,
                    ]);
                }

                // Update invoice amount to match items
                $invoice->update(['amount' => $subtotal]);
                
                $totalInvoices++;
                $currentDate->addDays($intervalDays);
            }
            
            // Show progress every 10 clients
            if ($clientIndex % 10 == 0) {
                $this->command->info("  Processed {$clientIndex} clients...");
            }
        }
        
        $this->command->info("Created {$totalInvoices} invoices with 2 years of history.");
    }

    /**
     * Get random item description
     */
    private function getItemDescription(): string
    {
        $descriptions = [
            'On-site support visit',
            'Remote support hours',
            'Emergency response',
            'System maintenance',
            'Security assessment',
            'Network configuration',
            'Server administration',
            'Help desk support',
            'Antivirus license',
            'Backup software',
            'Microsoft Office license',
            'Cloud storage subscription',
            'Email hosting',
            'Domain registration',
            'SSL certificate',
            'Firewall configuration',
            'Data recovery service',
            'Hardware installation',
            'Software deployment',
            'Training session',
        ];
        
        return fake()->randomElement($descriptions);
    }
}