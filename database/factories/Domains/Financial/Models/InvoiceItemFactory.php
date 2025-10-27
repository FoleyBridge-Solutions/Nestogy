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
}
