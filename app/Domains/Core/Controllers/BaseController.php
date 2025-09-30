<?php

namespace App\Domains\Core\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

abstract class BaseController extends Controller
{
    protected string $modelClass;

    protected string $serviceClass;

    protected string $resourceName;

    protected string $viewPrefix;

    protected array $eagerLoadRelations = [];

    public function __construct()
    {
        // Only initialize if the method exists (for backward compatibility)
        if (method_exists($this, 'initializeController')) {
            $this->initializeController();
        }
    }

    // Make this optional for backward compatibility
    protected function initializeController(): void
    {
        // Default implementation - can be overridden
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', $this->modelClass);

        $filters = $this->getFilters($request);
        $service = app($this->serviceClass);

        if (method_exists($service, 'getPaginated')) {
            $items = $service->getPaginated($filters, $request->get('per_page', 25));
        } else {
            $query = $this->buildIndexQuery($request);
            $items = $query->paginate($request->get('per_page', 25));
        }

        if ($request->wantsJson()) {
            return response()->json($items);
        }

        $viewData = array_merge(
            [$this->getResourcePluralName() => $items],
            $this->getIndexViewData($request)
        );

        return view("{$this->viewPrefix}.index", $viewData);
    }

    protected function baseShow(Request $request, $model)
    {
        $this->authorize('view', $model);

        if (! empty($this->eagerLoadRelations)) {
            $model->load($this->eagerLoadRelations);
        }

        if ($request->wantsJson()) {
            return response()->json($model);
        }

        $viewData = array_merge(
            [$this->getResourceSingularName() => $model],
            $this->getShowViewData($model)
        );

        return view("{$this->viewPrefix}.show", $viewData);
    }

    public function create()
    {
        $this->authorize('create', $this->modelClass);

        $viewData = $this->getCreateViewData();

        return view("{$this->viewPrefix}.create", $viewData);
    }

    protected function baseStore($request)
    {
        try {
            $service = app($this->serviceClass);
            $data = $this->prepareStoreData($request->validated());

            if (method_exists($service, 'create')) {
                $model = $service->create($data);
            } else {
                $data['company_id'] = Auth::user()->company_id;
                $model = $this->modelClass::create($data);
            }

            $this->logActivity($model, 'created', $request);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $this->getSuccessMessage('created'),
                    'data' => $model,
                ], 201);
            }

            return redirect()
                ->route("{$this->resourceName}.show", $model)
                ->with('success', $this->getSuccessMessage('created'));

        } catch (\Exception $e) {
            $this->logError('creation', $e, $request);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $this->getErrorMessage('creation'),
                ], 500);
            }

            return back()
                ->withInput()
                ->with('error', $this->getErrorMessage('creation'));
        }
    }

    protected function baseEdit($model)
    {
        $this->authorize('update', $model);

        $viewData = array_merge(
            [$this->getResourceSingularName() => $model],
            $this->getEditViewData($model)
        );

        return view("{$this->viewPrefix}.edit", $viewData);
    }

    protected function baseUpdate($request, $model)
    {
        $this->authorize('update', $model);

        try {
            $service = app($this->serviceClass);
            $data = $this->prepareUpdateData($request->validated(), $model);

            if (method_exists($service, 'update')) {
                $model = $service->update($model, $data);
            } else {
                $model->update($data);
                $model = $model->fresh();
            }

            $this->logActivity($model, 'updated', $request);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $this->getSuccessMessage('updated'),
                    'data' => $model,
                ]);
            }

            return redirect()
                ->route("{$this->resourceName}.show", $model)
                ->with('success', $this->getSuccessMessage('updated'));

        } catch (\Exception $e) {
            $this->logError('update', $e, $request, $model);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $this->getErrorMessage('update'),
                ], 500);
            }

            return back()
                ->withInput()
                ->with('error', $this->getErrorMessage('update'));
        }
    }

    protected function baseDestroy(Request $request, $model)
    {
        $this->authorize('delete', $model);

        try {
            $service = app($this->serviceClass);
            $modelName = $this->getModelDisplayName($model);

            if (method_exists($service, 'archive')) {
                $service->archive($model);
                $action = 'archived';
            } elseif (method_exists($service, 'delete')) {
                $service->delete($model);
                $action = 'deleted';
            } else {
                if (method_exists($model, 'archive')) {
                    $model->archive();
                    $action = 'archived';
                } else {
                    $model->delete();
                    $action = 'deleted';
                }
            }

            $this->logActivity($model, $action, $request);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $this->getSuccessMessage($action),
                ]);
            }

            return redirect()
                ->route("{$this->resourceName}.index")
                ->with('success', $this->getSuccessMessage($action));

        } catch (\Exception $e) {
            $this->logError('deletion', $e, $request, $model);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $this->getErrorMessage('deletion'),
                ], 500);
            }

            return back()->with('error', $this->getErrorMessage('deletion'));
        }
    }

    protected function buildIndexQuery(Request $request)
    {
        $query = $this->modelClass::where('company_id', Auth::user()->company_id);

        // Apply common filters
        if ($request->filled('search')) {
            $query = $this->applySearchFilter($query, $request->get('search'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        // Apply custom filters
        $query = $this->applyCustomFilters($query, $request);

        // Apply sorting
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        if (! empty($this->eagerLoadRelations)) {
            $query->with($this->eagerLoadRelations);
        }

        return $query;
    }

    protected function applySearchFilter($query, string $search)
    {
        if (method_exists($this->modelClass, 'scopeSearch')) {
            return $query->search($search);
        }

        return $query->where('name', 'like', "%{$search}%");
    }

    protected function applyCustomFilters($query, Request $request)
    {
        return $query;
    }

    protected function getFilters(Request $request): array
    {
        return $request->only(['search', 'status', 'type']);
    }

    protected function prepareStoreData(array $data): array
    {
        $data['company_id'] = Auth::user()->company_id;

        return $data;
    }

    protected function prepareUpdateData(array $data, Model $model): array
    {
        return $data;
    }

    protected function getIndexViewData(Request $request): array
    {
        return [];
    }

    protected function getShowViewData(Model $model): array
    {
        return [];
    }

    protected function getCreateViewData(): array
    {
        return [];
    }

    protected function getEditViewData(Model $model): array
    {
        return [];
    }

    protected function getResourceSingularName(): string
    {
        return str_replace(['-', '_'], '', $this->resourceName);
    }

    protected function getResourcePluralName(): string
    {
        return str_plural($this->getResourceSingularName());
    }

    protected function getModelDisplayName(Model $model): string
    {
        return $model->name ?? $model->title ?? class_basename($model);
    }

    protected function getSuccessMessage(string $action): string
    {
        $resourceName = ucfirst($this->getResourceSingularName());

        return "{$resourceName} {$action} successfully.";
    }

    protected function getErrorMessage(string $action): string
    {
        $resourceName = ucfirst($this->getResourceSingularName());

        return "Failed to {$action} {$resourceName}.";
    }

    protected function logActivity(Model $model, string $action, Request $request): void
    {
        Log::info(ucfirst($this->getResourceSingularName())." {$action}", [
            'model_id' => $model->id,
            'model_type' => get_class($model),
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    protected function logError(string $action, \Exception $e, Request $request, ?Model $model = null): void
    {
        Log::error(ucfirst($this->getResourceSingularName())." {$action} failed", [
            'error' => $e->getMessage(),
            'model_id' => $model?->id,
            'model_type' => $model ? get_class($model) : $this->modelClass,
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
