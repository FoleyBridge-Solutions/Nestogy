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
        $clients = Client::all();
        
        // Create income categories if they don't exist
        $companies = \App\Models\Company::all();
        foreach ($companies as $company) {
            if (!Category::where('company_id', $company->id)->where('type', 'income')->exists()) {
                Category::create([
                    'company_id' => $company->id,
                    'name' => 'Services',
                    'type' => 'income',
                    'color' => '#4A90E2'
                ]);
                Category::create([
                    'company_id' => $company->id,
                    'name' => 'Products',
                    'type' => 'income',
                    'color' => '#7C4DFF'
                ]);
            }
        }

        foreach ($clients as $client) {
            // Generate 3-8 invoices per client over the last 6 months
            $invoiceCount = rand(3, 8);
            
            for ($i = 0; $i < $invoiceCount; $i++) {
                $daysAgo = rand(1, 180);
                $date = Carbon::now()->subDays($daysAgo);
                
                // Determine status based on age
                if ($daysAgo > 60) {
                    $status = fake()->randomElement(['paid', 'paid', 'paid', 'overdue']);
                } elseif ($daysAgo > 30) {
                    $status = fake()->randomElement(['paid', 'sent', 'viewed']);
                } else {
                    $status = fake()->randomElement(['sent', 'draft', 'viewed']);
                }

                $invoice = Invoice::factory()
                    ->create([
                        'company_id' => $client->company_id,
                        'client_id' => $client->id,
                        'date' => $date,
                        'due_date' => $date->copy()->addDays(30),
                        'status' => $status,
                        'category_id' => Category::where('company_id', $client->company_id)
                            ->where('type', 'income')
                            ->inRandomOrder()
                            ->first()?->id ?? 1,
                    ]);

                // Create 1-5 invoice items
                $itemCount = rand(1, 5);
                $subtotal = 0;
                
                for ($j = 0; $j < $itemCount; $j++) {
                    $quantity = fake()->randomFloat(2, 1, 10);
                    $price = fake()->randomFloat(2, 50, 500);
                    $total = $quantity * $price;
                    $subtotal += $total;

                    $tax = fake()->boolean(80) ? $total * 0.08 : 0;
                    $itemDescription = $this->getItemDescription();
                    InvoiceItem::create([
                        'company_id' => $invoice->company_id,
                        'invoice_id' => $invoice->id,
                        'name' => $itemDescription,
                        'description' => $itemDescription,
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
            }
        }
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