<?php

namespace App\Http\Resources\Financial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Quote Collection Resource
 * Handles paginated collections of quotes with metadata
 */
class QuoteCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = QuoteResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function ($quote) use ($request) {
                // Use minimal format for collection views
                return (new QuoteResource($quote))->toArrayMinimal($request);
            }),
            'pagination' => $this->when($this->resource instanceof \Illuminate\Pagination\LengthAwarePaginator, [
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'total' => $this->total(),
                'from' => $this->firstItem(),
                'to' => $this->lastItem(),
                'has_more_pages' => $this->hasMorePages(),
                'links' => [
                    'first' => $this->url(1),
                    'last' => $this->url($this->lastPage()),
                    'prev' => $this->previousPageUrl(),
                    'next' => $this->nextPageUrl(),
                ],
            ]),
            'summary' => $this->getSummaryData(),
            'filters' => $this->getAppliedFilters($request),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version' => '1.0',
                'type' => 'quote_collection',
                'timestamp' => now()->toISOString(),
                'total_items' => $this->count(),
            ],
        ];
    }

    /**
     * Customize the outgoing response for the resource.
     */
    public function withResponse(Request $request, \Illuminate\Http\JsonResponse $response): void
    {
        $response->header('X-Resource-Type', 'QuoteCollection');
        $response->header('X-API-Version', '1.0');
        $response->header('X-Total-Count', $this->count());
    }

    /**
     * Get summary data for the collection
     *
     * @return array<string, mixed>
     */
    protected function getSummaryData(): array
    {
        $summary = [
            'count' => $this->count(),
            'total_value' => 0,
            'average_value' => 0,
            'status_breakdown' => [],
            'currency_breakdown' => [],
        ];

        if ($this->isEmpty()) {
            return $summary;
        }

        // Calculate totals and breakdowns
        $totalValue = 0;
        $statusCounts = [];
        $currencyCounts = [];

        foreach ($this->collection as $quote) {
            $totalValue += (float) $quote->total_amount;

            // Count by status
            $status = $quote->status;
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;

            // Count by currency
            $currency = $quote->currency_code;
            $currencyCounts[$currency] = ($currencyCounts[$currency] ?? 0) + 1;
        }

        $summary['total_value'] = $totalValue;
        $summary['average_value'] = $this->count() > 0 ? $totalValue / $this->count() : 0;
        $summary['status_breakdown'] = $statusCounts;
        $summary['currency_breakdown'] = $currencyCounts;

        // Format currency values
        $summary['total_value_formatted'] = number_format($summary['total_value'], 2, '.', ',');
        $summary['average_value_formatted'] = number_format($summary['average_value'], 2, '.', ',');

        return $summary;
    }

    /**
     * Get applied filters from request
     *
     * @return array<string, mixed>
     */
    protected function getAppliedFilters(Request $request): array
    {
        $filters = [];

        if ($request->has('status')) {
            $filters['status'] = $request->get('status');
        }

        if ($request->has('client_id')) {
            $filters['client_id'] = $request->get('client_id');
        }

        if ($request->has('date_from')) {
            $filters['date_from'] = $request->get('date_from');
        }

        if ($request->has('date_to')) {
            $filters['date_to'] = $request->get('date_to');
        }

        if ($request->has('search')) {
            $filters['search'] = $request->get('search');
        }

        if ($request->has('sort_by')) {
            $filters['sort_by'] = $request->get('sort_by');
        }

        if ($request->has('sort_order')) {
            $filters['sort_order'] = $request->get('sort_order');
        }

        return $filters;
    }

    /**
     * Create a simplified collection for dashboard widgets
     *
     * @return array<string, mixed>
     */
    public function toArrayDashboard(Request $request): array
    {
        return [
            'quotes' => $this->collection->take(5)->map(function ($quote) {
                return [
                    'id' => $quote->id,
                    'quote_number' => $quote->quote_number,
                    'client_name' => $quote->client?->name,
                    'total_amount' => number_format($quote->total_amount, 2),
                    'status' => $quote->status,
                    'created_at' => $quote->created_at?->format('M j, Y'),
                ];
            }),
            'summary' => [
                'total_count' => $this->count(),
                'total_value' => number_format($this->collection->sum('total_amount'), 2),
                'pending_count' => $this->collection->whereIn('status', ['draft', 'sent'])->count(),
                'accepted_count' => $this->collection->where('status', 'accepted')->count(),
            ],
        ];
    }

    /**
     * Create an export-friendly format
     *
     * @return array<string, mixed>
     */
    public function toArrayExport(Request $request): array
    {
        return [
            'quotes' => $this->collection->map(function ($quote) {
                return [
                    'Quote Number' => $quote->quote_number,
                    'Client' => $quote->client?->name,
                    'Company' => $quote->client?->company_name,
                    'Status' => ucfirst($quote->status),
                    'Date' => $quote->date?->format('Y-m-d'),
                    'Expiry Date' => $quote->expire_date?->format('Y-m-d'),
                    'Subtotal' => $quote->subtotal,
                    'Discount' => $quote->discount_calculated,
                    'Tax' => $quote->tax_amount,
                    'Total' => $quote->total_amount,
                    'Currency' => $quote->currency_code,
                    'Items Count' => $quote->items_count ?? $quote->items?->count() ?? 0,
                    'Created' => $quote->created_at?->format('Y-m-d H:i:s'),
                    'Updated' => $quote->updated_at?->format('Y-m-d H:i:s'),
                ];
            }),
            'summary' => $this->getSummaryData(),
            'exported_at' => now()->toISOString(),
            'exported_by' => auth()->user()?->name,
        ];
    }
}
