<?php

namespace App\Domains\Core\Controllers\Traits;

use Illuminate\Http\Request;

trait HasPaginationControls
{
    protected function getPerPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', $this->perPage ?? 25);

        // Limit to reasonable values
        return max(10, min(100, $perPage));
    }

    protected function getPaginationData($items): array
    {
        return [
            'current_page' => $items->currentPage(),
            'last_page' => $items->lastPage(),
            'per_page' => $items->perPage(),
            'total' => $items->total(),
            'from' => $items->firstItem(),
            'to' => $items->lastItem(),
            'has_more_pages' => $items->hasMorePages(),
            'links' => $items->links()->render(),
        ];
    }

    protected function buildPaginatedResponse($items, array $additional = []): array
    {
        return array_merge([
            'data' => $items->items(),
            'pagination' => $this->getPaginationData($items),
        ], $additional);
    }
}
