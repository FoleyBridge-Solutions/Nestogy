<?php

namespace App\Http\Resources\Financial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Quote API Resource
 * Standardizes quote JSON responses across the application
 */
class QuoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quote_number' => $this->getFullNumber(),
            'status' => $this->status,
            'status_label' => $this->getStatusLabelAttribute(),
            
            // Basic information
            'date' => $this->date?->format('Y-m-d'),
            'expire_date' => $this->expire?->format('Y-m-d'),
            'currency_code' => $this->currency_code,
            
            // Client information (when loaded)
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                    'company_name' => $this->client->company_name,
                    'email' => $this->client->email,
                    'phone' => $this->client->phone,
                ];
            }),
            
            // Category information (when loaded)
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ];
            }),
            
            // User information (when loaded)
            'user' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            
            // Content
            'scope' => $this->scope,
            'note' => $this->note,
            'terms_conditions' => $this->terms_conditions,
            
            // Pricing
            'subtotal' => $this->formatCurrency($this->getSubtotal()),
            'discount_type' => $this->discount_type ?? 'percentage',
            'discount_amount' => $this->discount_amount ?? 0,
            'discount_calculated' => $this->formatCurrency($this->getDiscountAmount()),
            'tax_amount' => $this->formatCurrency($this->getTotalTax()),
            'total_amount' => $this->formatCurrency($this->amount),
            
            // Raw pricing values for calculations
            'pricing_raw' => [
                'subtotal' => (float) $this->getSubtotal(),
                'discount_calculated' => (float) $this->getDiscountAmount(),
                'tax_amount' => (float) $this->getTotalTax(),
                'total_amount' => (float) $this->amount,
            ],
            
            // Configuration
            'voip_config' => $this->voip_config,
            'pricing_model' => $this->pricing_model,
            
            // Items (when loaded)
            'items' => $this->when(
                $this->relationLoaded('items'), 
                QuoteItemResource::collection($this->items)
            ),
            'items_count' => $this->whenCounted('items'),
            
            // Computed values
            'is_expired' => $this->isExpired(),
            'days_until_expiry' => $this->getDaysUntilExpiration(),
            'can_edit' => $this->canEditQuote(),
            'can_delete' => $this->canDeleteQuote(),
            'has_items' => $this->getItemsCount() > 0,
            
            // URLs
            'urls' => [
                'show' => route('financial.quotes.show', $this->id),
                'edit' => route('financial.quotes.edit', $this->id),
                'pdf' => route('financial.quotes.pdf', $this->id),
                'duplicate' => route('financial.quotes.duplicate', $this->id),
            ],
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'updated_at_human' => $this->updated_at?->diffForHumans(),
            
            // Company context
            'company_id' => $this->company_id,
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version' => '1.0',
                'type' => 'quote',
                'timestamp' => now()->toISOString(),
            ],
        ];
    }

    /**
     * Customize the outgoing response for the resource.
     *
     * @param Request $request
     * @param \Illuminate\Http\JsonResponse $response
     * @return void
     */
    public function withResponse(Request $request, \Illuminate\Http\JsonResponse $response): void
    {
        $response->header('X-Resource-Type', 'Quote');
        $response->header('X-API-Version', '1.0');
    }

    /**
     * Format currency value
     *
     * @param float|null $amount
     * @return string
     */
    protected function formatCurrency(?float $amount): string
    {
        if ($amount === null) {
            return '$0.00';
        }

        return number_format($amount, 2, '.', ',');
    }

    /**
     * Get status label
     *
     * @return string
     */
    protected function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'sent' => 'Sent',
            'viewed' => 'Viewed',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status)
        };
    }

    /**
     * Check if quote is expired
     *
     * @return bool
     */
    protected function isExpired(): bool
    {
        if (!$this->expire) {
            return false;
        }

        return $this->expire->isPast();
    }

    /**
     * Get days until expiry
     *
     * @return int|null
     */
    protected function getDaysUntilExpiry(): ?int
    {
        if (!$this->expire) {
            return null;
        }

        return now()->diffInDays($this->expire, false);
    }

    /**
     * Check if user can edit this quote
     *
     * @return bool
     */
    protected function canEditQuote(): bool
    {
        // Basic permission check - expand based on business rules
        $user = auth()->user();
        return in_array($this->status, ['draft', 'sent']) && 
               $user && $this->company_id === $user->company_id;
    }

    /**
     * Check if user can delete this quote
     *
     * @return bool
     */
    protected function canDeleteQuote(): bool
    {
        // Basic permission check - expand based on business rules
        $user = auth()->user();
        return $this->status === 'draft' && 
               $user && $this->company_id === $user->company_id;
    }

    /**
     * Get items count for quote
     *
     * @return int
     */
    protected function getItemsCount(): int
    {
        return $this->relationLoaded('items') ? 
               $this->items->count() : 
               ($this->items_count ?? 0);
    }

    /**
     * Create a minimal resource for listing views
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArrayMinimal(Request $request): array
    {
        return [
            'id' => $this->id,
            'quote_number' => $this->getFullNumber(),
            'status' => $this->status,
            'status_label' => $this->getStatusLabelAttribute(),
            'client_name' => $this->whenLoaded('client', $this->client?->name),
            'total_amount' => $this->formatCurrency($this->amount),
            'total_amount_raw' => (float) $this->amount,
            'date' => $this->date?->format('Y-m-d'),
            'expire_date' => $this->expire?->format('Y-m-d'),
            'is_expired' => $this->isExpired(),
            'created_at' => $this->created_at?->toISOString(),
            'urls' => [
                'show' => route('financial.quotes.show', $this->id),
                'edit' => route('financial.quotes.edit', $this->id),
            ],
        ];
    }

    /**
     * Create a detailed resource for single quote views
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArrayDetailed(Request $request): array
    {
        $baseArray = $this->toArray($request);
        
        // Add additional detailed information
        $baseArray['activity_log'] = $this->whenLoaded('activities', function () {
            return $this->activities->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'causer' => $activity->causer?->name,
                    'created_at' => $activity->created_at?->toISOString(),
                    'created_at_human' => $activity->created_at?->diffForHumans(),
                ];
            });
        });

        $baseArray['attachments'] = $this->whenLoaded('attachments', function () {
            return $this->attachments->map(function ($attachment) {
                return [
                    'id' => $attachment->id,
                    'name' => $attachment->name,
                    'size' => $attachment->size,
                    'mime_type' => $attachment->mime_type,
                    'url' => $attachment->getUrl(),
                ];
            });
        });

        $baseArray['pdf_generations'] = $this->whenLoaded('pdfGenerations', function () {
            return $this->pdfGenerations->map(function ($generation) {
                return [
                    'id' => $generation->id,
                    'filename' => $generation->filename,
                    'status' => $generation->status,
                    'url' => $generation->url,
                    'created_at' => $generation->created_at?->toISOString(),
                ];
            });
        });

        return $baseArray;
    }
}