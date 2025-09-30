<?php

namespace App\Domains\Knowledge\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Domains\Client\Services\DocumentationTemplateService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DocumentationTemplateController extends Controller
{
    protected DocumentationTemplateService $templateService;

    public function __construct(DocumentationTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * Get template data by key
     */
    public function getTemplate(Request $request, string $templateKey): JsonResponse
    {
        $templates = $this->templateService->getTemplates();
        
        if (!isset($templates[$templateKey])) {
            return response()->json(['error' => 'Template not found'], 404);
        }
        
        return response()->json($templates[$templateKey]);
    }

    /**
     * Get default tabs for a category
     */
    public function getDefaultTabs(Request $request, string $category): JsonResponse
    {
        $tabs = $this->templateService->getDefaultTabsForCategory($category);
        return response()->json(['tabs' => $tabs]);
    }

    /**
     * Get all available tabs
     */
    public function getAvailableTabs(): JsonResponse
    {
        return response()->json($this->templateService->getAvailableTabs());
    }
}