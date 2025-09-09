<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use App\Services\TaxEngine\TaxProfileService;
use App\Services\TaxEngine\TaxEngineRouter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use League\Csv\Writer;
use League\Csv\Reader;

class ProductService
{
    protected $taxProfileService;
    protected $taxEngineRouter;
    
    public function __construct(TaxProfileService $taxProfileService, TaxEngineRouter $taxEngineRouter)
    {
        $this->taxProfileService = $taxProfileService;
        $this->taxEngineRouter = $taxEngineRouter;
    }
    
    public function create(array $data): Product
    {
        $data['company_id'] = auth()->user()->company_id;
        
        // Ensure tax services have company context
        $this->taxProfileService->setCompanyId($data['company_id']);
        $this->taxEngineRouter->setCompanyId($data['company_id']);
        
        // Map price field to base_price column
        if (isset($data['price'])) {
            $data['base_price'] = $data['price'];
            unset($data['price']);
        }
        
        // Map notes field to short_description column
        if (isset($data['notes'])) {
            $data['short_description'] = $data['notes'];
            unset($data['notes']);
        }
        
        // Map billing_cycle values to database enum values (legacy support)
        if (isset($data['billing_cycle'])) {
            $billingCycleMapping = [
                'month' => 'monthly',
                'year' => 'annually',
                'quarter' => 'quarterly',
                'week' => 'weekly',
                'day' => 'daily',
                'hour' => 'hourly',
                'once' => 'one_time',
            ];
            
            // Only apply mapping if the value is a legacy key
            if (isset($billingCycleMapping[$data['billing_cycle']])) {
                $data['billing_cycle'] = $billingCycleMapping[$data['billing_cycle']];
            }
            // If it's already a valid modern value, keep it as-is
        }
        
        // Generate SKU if not provided
        if (empty($data['sku'])) {
            $data['sku'] = $this->generateSku($data['name']);
        }

        // Handle JSON fields
        $jsonFields = ['pricing_tiers', 'features', 'tags', 'metadata', 'custom_fields', 'gallery_urls'];
        foreach ($jsonFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = json_decode($data[$field], true);
            }
        }
        
        // Handle tax profile and tax data
        if (isset($data['tax_data']) && is_string($data['tax_data'])) {
            $data['tax_specific_data'] = json_decode($data['tax_data'], true);
        }
        
        // Auto-detect tax profile if category is set and no profile specified
        if (isset($data['category_id']) && !isset($data['tax_profile_id'])) {
            $category = Category::find($data['category_id']);
            if ($category) {
                $profile = $this->taxProfileService->getProfile($data['category_id'], null, $category->type ?? $category->name);
                if ($profile) {
                    $data['tax_profile_id'] = $profile->id;
                }
            }
        }

        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        // Ensure tax services have company context
        $this->taxProfileService->setCompanyId($product->company_id);
        $this->taxEngineRouter->setCompanyId($product->company_id);
        
        // Map price field to base_price column
        if (isset($data['price'])) {
            $data['base_price'] = $data['price'];
            unset($data['price']);
        }
        
        // Map notes field to short_description column
        if (isset($data['notes'])) {
            $data['short_description'] = $data['notes'];
            unset($data['notes']);
        }
        
        // Map billing_cycle values to database enum values (legacy support)
        if (isset($data['billing_cycle'])) {
            $billingCycleMapping = [
                'month' => 'monthly',
                'year' => 'annually',
                'quarter' => 'quarterly',
                'week' => 'weekly',
                'day' => 'daily',
                'hour' => 'hourly',
                'once' => 'one_time',
            ];
            
            // Only apply mapping if the value is a legacy key
            if (isset($billingCycleMapping[$data['billing_cycle']])) {
                $data['billing_cycle'] = $billingCycleMapping[$data['billing_cycle']];
            }
            // If it's already a valid modern value, keep it as-is
        }
        
        // Handle JSON fields
        $jsonFields = ['pricing_tiers', 'features', 'tags', 'metadata', 'custom_fields', 'gallery_urls'];
        foreach ($jsonFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = json_decode($data[$field], true);
            }
        }
        
        // Handle tax profile and tax data
        if (isset($data['tax_data']) && is_string($data['tax_data'])) {
            $data['tax_specific_data'] = json_decode($data['tax_data'], true);
        }
        
        // Auto-detect tax profile if category changed and no profile specified
        if (isset($data['category_id']) && $data['category_id'] != $product->category_id && !isset($data['tax_profile_id'])) {
            $category = Category::find($data['category_id']);
            if ($category) {
                $profile = $this->taxProfileService->getProfile($data['category_id'], null, $category->type ?? $category->name);
                if ($profile) {
                    $data['tax_profile_id'] = $profile->id;
                }
            }
        }

        $product->update($data);
        
        return $product->fresh();
    }

    public function delete(Product $product): bool
    {
        return $product->delete();
    }

    public function duplicate(Product $product): Product
    {
        $data = $product->toArray();
        
        // Remove unique fields
        unset($data['id'], $data['created_at'], $data['updated_at'], $data['deleted_at']);
        
        // Modify name and SKU
        $data['name'] = $data['name'] . ' (Copy)';
        $data['sku'] = $this->generateSku($data['name']);
        
        return $this->create($data);
    }

    public function exportProducts(array $filters = []): StreamedResponse
    {
        $filename = 'products_' . date('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($filters) {
            $handle = fopen('php://output', 'w');
            
            // Headers
            fputcsv($handle, [
                'SKU', 'Name', 'Description', 'Type', 'Category', 'Base Price', 
                'Cost', 'Currency', 'Unit Type', 'Billing Model', 'Billing Cycle',
                'Track Inventory', 'Current Stock', 'Min Stock', 'Is Active',
                'Is Featured', 'Is Taxable', 'Allow Discounts', 'Pricing Model',
                'Tags', 'Created At'
            ]);

            // Query products
            $query = Product::with(['category'])
                ->where('company_id', auth()->user()->company_id);

            // Apply filters
            if (!empty($filters['category_id'])) {
                $query->where('category_id', $filters['category_id']);
            }
            if (!empty($filters['type'])) {
                $query->where('type', $filters['type']);
            }
            if (isset($filters['is_active'])) {
                $query->where('is_active', $filters['is_active']);
            }

            $query->chunk(100, function ($products) use ($handle) {
                foreach ($products as $product) {
                    fputcsv($handle, [
                        $product->sku,
                        $product->name,
                        $product->description,
                        $product->type,
                        $product->category?->name,
                        $product->base_price,
                        $product->cost,
                        $product->currency_code,
                        $product->unit_type,
                        $product->billing_model,
                        $product->billing_cycle,
                        $product->track_inventory ? 'Yes' : 'No',
                        $product->current_stock,
                        $product->min_stock_level,
                        $product->is_active ? 'Yes' : 'No',
                        $product->is_featured ? 'Yes' : 'No',
                        $product->is_taxable ? 'Yes' : 'No',
                        $product->allow_discounts ? 'Yes' : 'No',
                        $product->pricing_model,
                        is_array($product->tags) ? implode(', ', $product->tags) : $product->tags,
                        $product->created_at->format('Y-m-d H:i:s')
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function importProducts(UploadedFile $file): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        $csv = Reader::createFromPath($file->getPathname(), 'r');
        $csv->setHeaderOffset(0);

        $companyId = auth()->user()->company_id;

        foreach ($csv as $index => $record) {
            try {
                // Skip if SKU already exists
                if (!empty($record['SKU']) && Product::where('company_id', $companyId)->where('sku', $record['SKU'])->exists()) {
                    $skipped++;
                    continue;
                }

                // Find or create category
                $categoryId = null;
                if (!empty($record['Category'])) {
                    $category = Category::firstOrCreate([
                        'company_id' => $companyId,
                        'name' => $record['Category']
                    ]);
                    $categoryId = $category->id;
                }

                $productData = [
                    'company_id' => $companyId,
                    'name' => $record['Name'],
                    'description' => $record['Description'] ?? null,
                    'sku' => $record['SKU'] ?? $this->generateSku($record['Name']),
                    'type' => $record['Type'] ?? 'product',
                    'category_id' => $categoryId,
                    'base_price' => (float) ($record['Base Price'] ?? 0),
                    'cost' => (float) ($record['Cost'] ?? 0),
                    'currency_code' => $record['Currency'] ?? 'USD',
                    'unit_type' => $record['Unit Type'] ?? 'units',
                    'billing_model' => $record['Billing Model'] ?? 'one_time',
                    'billing_cycle' => $record['Billing Cycle'] ?? 'one_time',
                    'track_inventory' => ($record['Track Inventory'] ?? 'No') === 'Yes',
                    'current_stock' => (int) ($record['Current Stock'] ?? 0),
                    'min_stock_level' => (int) ($record['Min Stock'] ?? 0),
                    'is_active' => ($record['Is Active'] ?? 'Yes') === 'Yes',
                    'is_featured' => ($record['Is Featured'] ?? 'No') === 'Yes',
                    'is_taxable' => ($record['Is Taxable'] ?? 'Yes') === 'Yes',
                    'allow_discounts' => ($record['Allow Discounts'] ?? 'Yes') === 'Yes',
                    'pricing_model' => $record['Pricing Model'] ?? 'fixed',
                ];

                // Handle tags
                if (!empty($record['Tags'])) {
                    $productData['tags'] = array_map('trim', explode(',', $record['Tags']));
                }

                Product::create($productData);
                $imported++;

            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                $skipped++;
            }
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        ];
    }

    protected function generateSku(string $name): string
    {
        $base = strtoupper(Str::slug($name, ''));
        $base = substr($base, 0, 6);
        
        $counter = 1;
        $sku = $base . sprintf('%03d', $counter);
        
        while (Product::where('company_id', auth()->user()->company_id)->where('sku', $sku)->exists()) {
            $counter++;
            $sku = $base . sprintf('%03d', $counter);
        }
        
        return $sku;
    }

    public function getProductAnalytics(Product $product): array
    {
        $thirtyDaysAgo = now()->subDays(30);
        
        return [
            'total_sales' => $product->sales_count,
            'total_revenue' => $product->total_revenue,
            'average_rating' => $product->average_rating,
            'rating_count' => $product->rating_count,
            'recent_sales_count' => DB::table('invoice_items')
                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->where('invoice_items.product_id', $product->id)
                ->where('invoices.created_at', '>=', $thirtyDaysAgo)
                ->count(),
            'inventory_status' => $this->getInventoryStatus($product),
            'pricing_trends' => $this->getPricingTrends($product),
        ];
    }

    protected function getInventoryStatus(Product $product): array
    {
        if (!$product->track_inventory) {
            return ['status' => 'not_tracked'];
        }

        $available = $product->current_stock - $product->reserved_stock;
        
        if ($available <= 0) {
            $status = 'out_of_stock';
        } elseif ($available <= $product->min_stock_level) {
            $status = 'low_stock';
        } elseif ($product->reorder_level && $available <= $product->reorder_level) {
            $status = 'reorder_needed';
        } else {
            $status = 'in_stock';
        }

        return [
            'status' => $status,
            'available' => $available,
            'reserved' => $product->reserved_stock,
            'total' => $product->current_stock,
            'min_level' => $product->min_stock_level,
            'reorder_level' => $product->reorder_level,
        ];
    }

    protected function getPricingTrends(Product $product): array
    {
        $trends = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoice_items.product_id', $product->id)
            ->where('invoices.created_at', '>=', now()->subDays(90))
            ->selectRaw('
                invoices.created_at::date as date,
                AVG(invoice_items.unit_price) as avg_price,
                SUM(invoice_items.quantity) as total_quantity
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $trends->toArray();
    }
}