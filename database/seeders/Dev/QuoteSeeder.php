<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Company;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;

class QuoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating quotes...');
        
        $companies = Company::where('id', '>', 1)->get();
        $totalQuotes = 0;
        
        foreach ($companies as $company) {
            $this->command->info("  Creating quotes for {$company->name}...");
            
            $clients = Client::where('company_id', $company->id)->get();
            $leads = Lead::where('company_id', $company->id)
                ->whereIn('status', ['qualified', 'proposal', 'negotiation'])
                ->get();
            $products = Product::where('company_id', $company->id)->get();
            $users = User::where('company_id', $company->id)->get();
            
            if ($users->isEmpty() || $products->isEmpty()) {
                continue;
            }
            
            // Create quotes for existing clients (renewals, upgrades)
            foreach ($clients->take(20) as $client) {
                $createdDate = fake()->dateTimeBetween('-3 months', 'now');
                $validUntil = Carbon::parse($createdDate)->addDays(30);
                
                $daysOld = Carbon::parse($createdDate)->diffInDays(now());
                
                // Status based on age
                if ($validUntil->isPast()) {
                    $status = fake()->randomElement(['accepted', 'rejected', 'expired']);
                } else {
                    $status = fake()->randomElement(['draft', 'sent', 'viewed']);
                }
                
                $quote = Quote::create([
                    'company_id' => $company->id,
                    'client_id' => $client->id,
                    'lead_id' => null,
                    'user_id' => $users->random()->id,
                    'quote_number' => 'Q-' . str_pad($totalQuotes + 1, 6, '0', STR_PAD_LEFT),
                    'title' => fake()->randomElement([
                        'Service Upgrade Proposal',
                        'Annual Renewal Quote',
                        'Additional Services Quote',
                        'Hardware Refresh Proposal',
                        'Security Enhancement Package',
                        'Cloud Migration Proposal'
                    ]),
                    'status' => $status,
                    'valid_until' => $validUntil,
                    'discount_type' => fake()->randomElement(['percentage', 'fixed', null]),
                    'discount_value' => fake()->optional(0.3)->numberBetween(5, 20),
                    'notes' => fake()->optional(0.5)->paragraph(),
                    'terms_conditions' => "Standard terms and conditions apply. Quote valid for 30 days.",
                    'accepted_at' => $status === 'accepted' ? 
                        fake()->dateTimeBetween($createdDate, 'now') : null,
                    'rejected_at' => $status === 'rejected' ? 
                        fake()->dateTimeBetween($createdDate, 'now') : null,
                    'sent_at' => in_array($status, ['sent', 'viewed', 'accepted', 'rejected']) ?
                        fake()->dateTimeBetween($createdDate, 'now') : null,
                    'viewed_at' => in_array($status, ['viewed', 'accepted', 'rejected']) ?
                        fake()->dateTimeBetween($createdDate, 'now') : null,
                    'created_at' => $createdDate,
                    'updated_at' => fake()->dateTimeBetween($createdDate, 'now'),
                ]);
                
                // Add quote items
                $this->createQuoteItems($quote, $products);
                $totalQuotes++;
            }
            
            // Create quotes for leads
            foreach ($leads as $lead) {
                $createdDate = fake()->dateTimeBetween('-2 months', 'now');
                $validUntil = Carbon::parse($createdDate)->addDays(30);
                
                $status = fake()->randomElement(['draft', 'sent', 'viewed', 'accepted', 'rejected']);
                
                $quote = Quote::create([
                    'company_id' => $company->id,
                    'client_id' => null,
                    'lead_id' => $lead->id,
                    'user_id' => $users->random()->id,
                    'quote_number' => 'Q-' . str_pad($totalQuotes + 1, 6, '0', STR_PAD_LEFT),
                    'title' => fake()->randomElement([
                        'Initial Service Proposal',
                        'Managed IT Services Quote',
                        'Complete IT Solution Package',
                        'Starter Package Quote',
                        'Enterprise Solution Proposal'
                    ]),
                    'status' => $status,
                    'valid_until' => $validUntil,
                    'discount_type' => fake()->randomElement(['percentage', 'fixed', null]),
                    'discount_value' => fake()->optional(0.4)->numberBetween(10, 25),
                    'notes' => fake()->optional(0.6)->paragraph(),
                    'terms_conditions' => "Standard terms and conditions apply. Quote valid for 30 days. First month free for new customers.",
                    'accepted_at' => $status === 'accepted' ? 
                        fake()->dateTimeBetween($createdDate, 'now') : null,
                    'rejected_at' => $status === 'rejected' ? 
                        fake()->dateTimeBetween($createdDate, 'now') : null,
                    'sent_at' => in_array($status, ['sent', 'viewed', 'accepted', 'rejected']) ?
                        fake()->dateTimeBetween($createdDate, 'now') : null,
                    'viewed_at' => in_array($status, ['viewed', 'accepted', 'rejected']) ?
                        fake()->dateTimeBetween($createdDate, 'now') : null,
                    'created_at' => $createdDate,
                    'updated_at' => fake()->dateTimeBetween($createdDate, 'now'),
                ]);
                
                // Add quote items
                $this->createQuoteItems($quote, $products);
                $totalQuotes++;
            }
            
            $this->command->info("    ✓ Created quotes for {$company->name}");
        }
        
        $this->command->info("Created {$totalQuotes} quotes total.");
    }
    
    private function createQuoteItems($quote, $products)
    {
        if ($products->isEmpty()) {
            return;
        }
        
        $numItems = rand(2, 8);
        $subtotal = 0;
        
        for ($i = 0; $i < $numItems; $i++) {
            $product = $products->random();
            $quantity = fake()->numberBetween(1, 20);
            $price = $product->price * fake()->randomFloat(2, 0.9, 1.1); // Allow some price flexibility
            $total = $quantity * $price;
            $subtotal += $total;
            
            QuoteItem::create([
                'quote_id' => $quote->id,
                'product_id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'quantity' => $quantity,
                'price' => $price,
                'total' => $total,
                'sort_order' => $i,
            ]);
        }
        
        // Apply discount if set
        $discountAmount = 0;
        if ($quote->discount_type === 'percentage' && $quote->discount_value) {
            $discountAmount = $subtotal * ($quote->discount_value / 100);
        } elseif ($quote->discount_type === 'fixed' && $quote->discount_value) {
            $discountAmount = $quote->discount_value;
        }
        
        $tax = ($subtotal - $discountAmount) * 0.08; // 8% tax
        $total = $subtotal - $discountAmount + $tax;
        
        $quote->update([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
        ]);
    }
}