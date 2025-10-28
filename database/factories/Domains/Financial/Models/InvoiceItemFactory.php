<?php

namespace Database\Factories\Domains\Financial\Models;

use App\Domains\Financial\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 10);
        $price = $this->faker->randomFloat(2, 10, 500);
        $discount = 0;
        $subtotal = $quantity * $price;
        $tax = $subtotal * 0.1; // 10% tax
        $total = $subtotal + $tax - $discount;
        
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'quantity' => $quantity,
            'price' => $price,
            'discount' => $discount,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'tax_rate' => 10.0,
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (InvoiceItem $item) {
            // If invoice_id is set, use the invoice's company_id (ALWAYS override)
            if ($item->invoice_id) {
                $invoice = \App\Domains\Financial\Models\Invoice::withoutGlobalScopes()->find($item->invoice_id);
                if ($invoice) {
                    $item->company_id = $invoice->company_id;
                }
            }
            // If quote_id is set, use the quote's company_id (ALWAYS override)
            elseif ($item->quote_id) {
                $quote = \App\Domains\Financial\Models\Quote::withoutGlobalScopes()->find($item->quote_id);
                if ($quote) {
                    $item->company_id = $quote->company_id;
                }
            }
        });
    }
}
