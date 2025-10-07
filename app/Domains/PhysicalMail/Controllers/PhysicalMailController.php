<?php

namespace App\Domains\PhysicalMail\Controllers;

use App\Domains\Core\Services\NavigationService;
use App\Domains\PhysicalMail\Models\PhysicalMailOrder;
use App\Domains\PhysicalMail\Services\PhysicalMailService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PhysicalMailController extends Controller
{
    public function __construct(
        private PhysicalMailService $mailService
    ) {}

    /**
     * List mail orders
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'sometimes|uuid|exists:clients,id',
            'status' => 'sometimes|in:pending,ready,printing,processed_for_delivery,completed,cancelled,failed',
            'mailable_type' => 'sometimes|in:letter,postcard,cheque,self_mailer',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'include_locations' => 'sometimes|boolean',
        ]);

        $validated = $this->ensureClientId($validated);

        if ($request->boolean('include_locations')) {
            return $this->getLocationData($validated);
        }

        $orders = $this->getOrders($validated);

        return response()->json($orders);
    }

    /**
     * Send physical mail
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:letter,postcard,cheque,self_mailer',
            'to' => 'required|array',
            'to.firstName' => 'sometimes|string',
            'to.lastName' => 'sometimes|string',
            'to.companyName' => 'sometimes|string',
            'to.addressLine1' => 'required|string',
            'to.addressLine2' => 'sometimes|string',
            'to.city' => 'sometimes|string',
            'to.provinceOrState' => 'sometimes|string',
            'to.postalOrZip' => 'sometimes|string',
            'to.country' => 'sometimes|string|size:2',
            'from' => 'sometimes|array',
            'template' => 'sometimes|string',
            'content' => 'sometimes|string',
            'pdf' => 'sometimes|url',
            'color' => 'sometimes|boolean',
            'double_sided' => 'sometimes|boolean',
            'address_placement' => 'sometimes|in:top_first_page,insert_blank_page',
            'size' => 'sometimes|string',
            'merge_variables' => 'sometimes|array',
            'mailing_class' => 'sometimes|string',
            'extra_service' => 'sometimes|in:certified,certified_return_receipt,registered',
            'metadata' => 'sometimes|array',
            'send_date' => 'sometimes|date',
        ]);

        // Ensure either template or content is provided
        if (! isset($validated['template']) && ! isset($validated['content']) && ! isset($validated['pdf'])) {
            return response()->json([
                'error' => 'Either template, content, or pdf must be provided',
            ], 422);
        }

        try {
            $order = $this->mailService->send($validated['type'], $validated);

            return response()->json([
                'success' => true,
                'order' => [
                    'id' => $order->id,
                    'postgrid_id' => $order->postgrid_id,
                    'status' => $order->status,
                    'pdf_url' => $order->pdf_url,
                    'created_at' => $order->created_at,
                ],
                'message' => 'Mail queued for sending',
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Failed to send physical mail', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to send mail: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get order details
     */
    public function show(PhysicalMailOrder $order): JsonResponse
    {
        // Load relationships
        $order->load(['mailable', 'client', 'createdBy']);

        return response()->json([
            'order' => $order,
            'mailable' => $order->mailable,
            'tracking' => $this->mailService->getTracking($order),
        ]);
    }

    /**
     * Cancel an order
     */
    public function cancel(PhysicalMailOrder $order): JsonResponse
    {
        if (! $order->canBeCancelled()) {
            return response()->json([
                'error' => 'Order cannot be cancelled in current status: '.$order->status,
            ], 422);
        }

        try {
            $success = $this->mailService->cancel($order);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order cancelled successfully',
                ]);
            }

            return response()->json([
                'error' => 'Failed to cancel order',
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get tracking information
     */
    public function tracking(PhysicalMailOrder $order): JsonResponse
    {
        $tracking = $this->mailService->getTracking($order);

        return response()->json([
            'tracking' => $tracking,
            'order_id' => $order->id,
            'postgrid_id' => $order->postgrid_id,
        ]);
    }

    /**
     * Progress test order (test mode only)
     */
    public function progressTest(PhysicalMailOrder $order): JsonResponse
    {
        try {
            $response = $this->mailService->progressTestOrder($order);

            return response()->json([
                'success' => true,
                'status' => $response['status'] ?? $order->status,
                'message' => 'Test order progressed',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Test PostGrid connection
     */
    public function testConnection(): JsonResponse
    {
        try {
            $postgrid = app(\App\Domains\PhysicalMail\Services\PostGridClient::class);

            // Try to list templates (lightweight API call)
            $response = $postgrid->list('templates', ['limit' => 1]);

            return response()->json([
                'success' => true,
                'mode' => $postgrid->isTestMode() ? 'test' : 'live',
                'message' => 'PostGrid API connection successful',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send invoice by mail
     */
    public function sendInvoice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_id' => 'required|uuid|exists:invoices,id',
            'color' => 'sometimes|boolean',
            'double_sided' => 'sometimes|boolean',
            'extra_service' => 'sometimes|in:certified,certified_return_receipt,registered',
        ]);

        try {
            $invoice = \App\Domains\Financial\Models\Invoice::findOrFail($validated['invoice_id']);

            // Check if invoice has physical mail trait
            if (! method_exists($invoice, 'sendByMail')) {
                return response()->json([
                    'error' => 'Invoice does not support physical mail',
                ], 422);
            }

            $order = $invoice->sendByMail(array_diff_key($validated, ['invoice_id' => '']));

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'message' => 'Invoice queued for mailing',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to send invoice: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the physical mail dashboard (web view)
     */
    public function webIndex(Request $request)
    {
        // Get selected client from session
        $selectedClient = NavigationService::getSelectedClient();

        // Build base query with optional client filtering
        $baseQuery = PhysicalMailOrder::query();
        if ($selectedClient) {
            $baseQuery->where('client_id', $selectedClient->id);
        }

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'month' => (clone $baseQuery)->whereMonth('created_at', now()->month)->count(),
            'pending' => (clone $baseQuery)->whereIn('status', ['pending', 'processing'])->count(),
            'delivered' => (clone $baseQuery)->where('status', 'delivered')->count(),
        ];

        $recentOrders = (clone $baseQuery)->with(['client', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $activeDomain = 'physical-mail';
        $activeSection = 'dashboard';

        return view('physical-mail.index', compact('stats', 'recentOrders', 'activeDomain', 'activeSection', 'selectedClient'));
    }

    /**
     * Display the send mail form (web view)
     */
    public function webSend(Request $request)
    {
        // Get selected client from session
        $selectedClient = NavigationService::getSelectedClient();

        $activeDomain = 'physical-mail';
        $activeSection = 'send';

        return view('physical-mail.send', compact('activeDomain', 'activeSection', 'selectedClient'));
    }

    /**
     * Display the tracking page (web view)
     */
    public function webTracking(Request $request)
    {
        // Get selected client from session
        $selectedClient = NavigationService::getSelectedClient();

        // Build query with optional client filtering
        $query = PhysicalMailOrder::with(['client', 'createdBy'])
            ->whereNotNull('tracking_number');

        if ($selectedClient) {
            $query->where('client_id', $selectedClient->id);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        $activeDomain = 'physical-mail';
        $activeSection = 'tracking';

        return view('physical-mail.tracking', compact('orders', 'activeDomain', 'activeSection', 'selectedClient'));
    }

    /**
     * Display the templates page (web view)
     */
    public function webTemplates(Request $request)
    {
        // Get selected client from session
        $selectedClient = NavigationService::getSelectedClient();

        $templates = collect(); // TODO: Load from database when template model is created

        $activeDomain = 'physical-mail';
        $activeSection = 'templates';

        return view('physical-mail.templates', compact('templates', 'activeDomain', 'activeSection', 'selectedClient'));
    }

    /**
     * Display the contacts page (web view)
     */
    public function webContacts(Request $request)
    {
        // Get selected client from session
        $selectedClient = NavigationService::getSelectedClient();

        $contacts = collect(); // TODO: Load from database when contact model is created

        $activeDomain = 'physical-mail';
        $activeSection = 'contacts';

        return view('physical-mail.contacts', compact('contacts', 'activeDomain', 'activeSection', 'selectedClient'));
    }

    private function ensureClientId(array $validated): array
    {
        if (! isset($validated['client_id'])) {
            $selectedClient = NavigationService::getSelectedClient();
            if ($selectedClient) {
                $validated['client_id'] = $selectedClient->id;
            }
        }

        return $validated;
    }

    private function getLocationData(array $validated): JsonResponse
    {
        $query = PhysicalMailOrder::with(['client', 'createdBy'])
            ->whereNotNull('tracking_number')
            ->whereNotIn('status', ['cancelled', 'failed']);

        $this->applyFilters($query, $validated);

        $orders = $query->limit(100)->get();
        $mapData = $this->transformOrdersForMap($orders);

        return response()->json(['data' => $mapData]);
    }

    private function getOrders(array $validated)
    {
        $clientId = $validated['client_id'] ?? null;

        if ($clientId) {
            return $this->mailService->getByClient($clientId, $validated);
        }

        return $this->getCompanyOrders($validated);
    }

    private function getCompanyOrders(array $validated)
    {
        $query = PhysicalMailOrder::where('company_id', Auth::user()->company_id);

        $this->applyFilters($query, $validated);

        if (isset($validated['mailable_type'])) {
            $query->where('mailable_type', $validated['mailable_type']);
        }

        return $query->paginate($validated['per_page'] ?? 20);
    }

    private function applyFilters($query, array $validated): void
    {
        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['client_id'])) {
            $query->where('client_id', $validated['client_id']);
        }

        if (isset($validated['date_from'])) {
            $query->where('created_at', '>=', $validated['date_from']);
        }

        if (isset($validated['date_to'])) {
            $query->where('created_at', '<=', $validated['date_to']);
        }
    }

    private function transformOrdersForMap($orders)
    {
        return $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'status' => $order->status,
                'client' => [
                    'name' => $order->client?->name ?? 'Unknown',
                ],
                'address' => $order->formatted_address ??
                    ($order->to_city ? "{$order->to_address_line1}, {$order->to_city}, {$order->to_state}" : 'Unknown location'),
                'lat' => $order->latitude,
                'lng' => $order->longitude,
            ];
        });
    }
}
