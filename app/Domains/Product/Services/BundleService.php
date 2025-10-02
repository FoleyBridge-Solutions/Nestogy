<?php

namespace App\Domains\Product\Services;

use App\Models\Bundle;
use Illuminate\Support\Facades\DB;

class BundleService
{
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $bundle = Bundle::create($data);
            
            if (isset($data['products'])) {
                $bundle->products()->sync($data['products']);
            }
            
            return $bundle;
        });
    }

    public function update(Bundle $bundle, array $data)
    {
        return DB::transaction(function () use ($bundle, $data) {
            $bundle->update($data);
            
            if (isset($data['products'])) {
                $bundle->products()->sync($data['products']);
            }
            
            return $bundle;
        });
    }

    public function delete(Bundle $bundle)
    {
        return DB::transaction(function () use ($bundle) {
            $bundle->products()->detach();
            return $bundle->delete();
        });
    }

    public function calculatePrice(Bundle $bundle)
    {
        $total = 0;
        
        foreach ($bundle->products as $product) {
            $total += $product->price * ($product->pivot->quantity ?? 1);
        }
        
        // Apply bundle discount if any
        if ($bundle->discount_percentage) {
            $total = $total * (1 - ($bundle->discount_percentage / 100));
        }
        
        if ($bundle->discount_amount) {
            $total -= $bundle->discount_amount;
        }
        
        return max(0, $total);
    }
}
