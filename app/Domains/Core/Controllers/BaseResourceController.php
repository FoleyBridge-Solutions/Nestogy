<?php

namespace App\Domains\Core\Controllers;

use App\Domains\Core\Services\BaseService;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

abstract class BaseResourceController extends Controller
{
    protected BaseService $service;

    protected string $resourceName;

    protected string $viewPath;

    protected string $routePrefix;

    protected array $defaultFilters = [];

    protected int $perPage = 25;

    public function __construct()
    {
        $this->initializeController();
        $this->middleware('auth');
    }

    abstract protected function initializeController(): void;

    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', $this->getModelClass());

        $filters = array_merge(
            $this->defaultFilters,
            $request->only($this->getAllowedFilters())
        );

        $items = $this->service->getPaginated($filters, $this->perPage);

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $items->items(),
                'pagination' => [
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                    'per_page' => $items->perPage(),
                    'total' => $items->total(),
                ],
            ]);
        }

        return view($this->getViewName('index'), [
            'items' => $items,
            'filters' => $filters,
            'resourceName' => $this->resourceName,
            'routePrefix' => $this->routePrefix,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', $this->getModelClass());

        return view($this->getViewName('create'), [
            'resourceName' => $this->resourceName,
            'routePrefix' => $this->routePrefix,
        ]);
    }

    public function store(FormRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', $this->getModelClass());

        try {
            $item = $this->service->create($request->validated());

            $this->afterStore($item, $request);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => ucfirst($this->resourceName).' created successfully.',
                    'data' => $item,
                ], 201);
            }

            return redirect()
                ->route($this->routePrefix.'.show', $item)
                ->with('success', ucfirst($this->resourceName).' created successfully.');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to create '.$this->resourceName,
                    'error' => $e->getMessage(),
                ], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create '.$this->resourceName.': '.$e->getMessage()]);
        }
    }

    public function show($id): View|JsonResponse
    {
        $item = $this->service->findByIdOrFail($id);
        $this->authorize('view', $item);

        if (request()->expectsJson()) {
            return response()->json(['data' => $item]);
        }

        return view($this->getViewName('show'), [
            'item' => $item,
            'resourceName' => $this->resourceName,
            'routePrefix' => $this->routePrefix,
        ]);
    }

    public function edit($id): View
    {
        $item = $this->service->findByIdOrFail($id);
        $this->authorize('update', $item);

        return view($this->getViewName('edit'), [
            'item' => $item,
            'resourceName' => $this->resourceName,
            'routePrefix' => $this->routePrefix,
        ]);
    }

    public function update(FormRequest $request, $id): RedirectResponse|JsonResponse
    {
        $item = $this->service->findByIdOrFail($id);
        $this->authorize('update', $item);

        try {
            $item = $this->service->update($item, $request->validated());

            $this->afterUpdate($item, $request);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => ucfirst($this->resourceName).' updated successfully.',
                    'data' => $item,
                ]);
            }

            return redirect()
                ->route($this->routePrefix.'.show', $item)
                ->with('success', ucfirst($this->resourceName).' updated successfully.');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to update '.$this->resourceName,
                    'error' => $e->getMessage(),
                ], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update '.$this->resourceName.': '.$e->getMessage()]);
        }
    }

    public function destroy($id): RedirectResponse|JsonResponse
    {
        $item = $this->service->findByIdOrFail($id);
        $this->authorize('delete', $item);

        try {
            $this->service->delete($item);

            $this->afterDestroy($item);

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => ucfirst($this->resourceName).' deleted successfully.',
                ]);
            }

            return redirect()
                ->route($this->routePrefix.'.index')
                ->with('success', ucfirst($this->resourceName).' deleted successfully.');

        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to delete '.$this->resourceName,
                    'error' => $e->getMessage(),
                ], 422);
            }

            return back()
                ->withErrors(['error' => 'Failed to delete '.$this->resourceName.': '.$e->getMessage()]);
        }
    }

    public function archive($id): RedirectResponse|JsonResponse
    {
        $item = $this->service->findByIdOrFail($id);
        $this->authorize('delete', $item);

        try {
            $this->service->archive($item);

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => ucfirst($this->resourceName).' archived successfully.',
                ]);
            }

            return redirect()
                ->route($this->routePrefix.'.index')
                ->with('success', ucfirst($this->resourceName).' archived successfully.');

        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to archive '.$this->resourceName,
                    'error' => $e->getMessage(),
                ], 422);
            }

            return back()
                ->withErrors(['error' => 'Failed to archive '.$this->resourceName.': '.$e->getMessage()]);
        }
    }

    public function restore($id): RedirectResponse|JsonResponse
    {
        $item = $this->service->findByIdOrFail($id);
        $this->authorize('update', $item);

        try {
            $this->service->restore($item);

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => ucfirst($this->resourceName).' restored successfully.',
                ]);
            }

            return redirect()
                ->route($this->routePrefix.'.show', $item)
                ->with('success', ucfirst($this->resourceName).' restored successfully.');

        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to restore '.$this->resourceName,
                    'error' => $e->getMessage(),
                ], 422);
            }

            return back()
                ->withErrors(['error' => 'Failed to restore '.$this->resourceName.': '.$e->getMessage()]);
        }
    }

    public function bulkAction(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'action' => 'required|in:archive,delete,restore,update',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'data' => 'array',
        ]);

        $ids = $request->input('ids');
        $action = $request->input('action');
        $data = $request->input('data', []);

        try {
            $count = match ($action) {
                'archive' => $this->service->bulkArchive($ids),
                'delete' => $this->handleBulkDelete($ids),
                'restore' => $this->handleBulkRestore($ids),
                'update' => $this->service->bulkUpdate($ids, $data),
                default => 0
            };

            $message = "Successfully {$action}d {$count} ".str_plural($this->resourceName, $count);

            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'count' => $count]);
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            $message = "Failed to {$action} ".str_plural($this->resourceName).': '.$e->getMessage();

            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'error' => $e->getMessage()], 422);
            }

            return back()->withErrors(['error' => $message]);
        }
    }

    protected function getViewName(string $action): string
    {
        return $this->viewPath.'.'.$action;
    }

    protected function getAllowedFilters(): array
    {
        return ['search', 'status', 'type', 'date_from', 'date_to', 'sort', 'direction'];
    }

    abstract protected function getModelClass(): string;

    protected function afterStore($item, Request $request): void
    {
        // Override in child classes for post-creation logic
    }

    protected function afterUpdate($item, Request $request): void
    {
        // Override in child classes for post-update logic
    }

    protected function afterDestroy($item): void
    {
        // Override in child classes for post-deletion logic
    }

    protected function handleBulkDelete(array $ids): int
    {
        // This method should be overridden if bulk delete is different from archive
        return $this->service->bulkArchive($ids);
    }

    protected function handleBulkRestore(array $ids): int
    {
        $count = 0;
        foreach ($ids as $id) {
            $item = $this->service->findByIdOrFail($id);
            if ($this->service->restore($item)) {
                $count++;
            }
        }

        return $count;
    }
}
