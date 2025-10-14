<?php

namespace App\Livewire\Products;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ShowProduct extends Component
{
    public Product $product;
    public $recentSales = [];
    
    public function mount(Product $product)
    {
        $this->product = $product->load(['category']);
        $this->loadRecentSales();
    }
    
    protected function loadRecentSales()
    {
        $this->recentSales = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoice_items.product_id', $this->product->id)
            ->where('invoices.company_id', auth()->user()->company_id)
            ->select('invoices.created_at', 'invoice_items.quantity', 'invoice_items.price')
            ->orderBy('invoices.created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function render()
    {
        return view('livewire.products.show-product');
    }
}
