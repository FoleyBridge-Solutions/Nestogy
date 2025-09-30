<?php

namespace App\Domains\Client\Services;

use App\Domains\Financial\Services\PortalPaymentService;
use App\Domains\Security\Services\PortalAuthService;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\Payment;
use App\Models\PortalNotification;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Client Portal Service
 *
 * Comprehensive service for managing client portal functionality including:
 * - Portal dashboard and analytics
 * - Service history and contract management
 * - Support ticket integration
 * - Document management and sharing
 * - Notification center
 * - Account profile management
 * - Usage tracking and reporting
 * - Portal customization and white-label branding
 * - Integration with billing and payment systems
 * - Client communication and messaging
 * - Service management and configuration
 * - Reporting and analytics
 */
class ClientPortalService
{
    protected PortalAuthService $authService;

    protected PortalPaymentService $paymentService;

    protected array $config;

    public function __construct(PortalAuthService $authService, PortalPaymentService $paymentService)
    {
        $this->authService = $authService;
        $this->paymentService = $paymentService;

        $this->config = config('portal.general', [
            'session_timeout' => 7200, // 2 hours
            'max_document_size' => 10 * 1024 * 1024, // 10MB
            'allowed_document_types' => ['pdf', 'doc', 'docx', 'txt', 'jpg', 'png'],
            'notification_retention_days' => 90,
            'dashboard_cache_ttl' => 900, // 15 minutes
            'usage_tracking_enabled' => true,
            'white_label_enabled' => true,
            'support_integration' => true,
            'analytics_enabled' => true,
        ]);
    }

    /**
     * Get comprehensive dashboard data for client
     */
    public function getDashboardData(Client $client): array
    {
        $cacheKey = "portal_dashboard_{$client->id}";

        return Cache::remember($cacheKey, $this->config['dashboard_cache_ttl'], function () use ($client) {

            $data = [
                'client_info' => $this->getClientInfo($client),
                'account_summary' => $this->getAccountSummary($client),
                'billing_overview' => $this->getBillingOverview($client),
                'service_status' => $this->getServiceStatus($client),
                'recent_activity' => $this->getRecentActivity($client),
                'upcoming_items' => $this->getUpcomingItems($client),
                'notifications' => $this->getRecentNotifications($client),
                'support_summary' => $this->getSupportSummary($client),
                'usage_summary' => $this->getUsageSummary($client),
                'payment_methods' => $this->getPaymentMethodSummary($client),
                'document_summary' => $this->getDocumentSummary($client),
                'portal_metrics' => $this->getPortalMetrics($client),
            ];

            Log::info('Dashboard data generated', [
                'client_id' => $client->id,
                'data_points' => count($data),
            ]);

            return $data;
        });
    }

    /**
     * Get service history for client
     */
    public function getServiceHistory(Client $client, array $filters = []): array
    {
        try {
            // Placeholder implementation for service history
            // This would integrate with actual service management system
            $historyData = collect([
                [
                    'id' => 1,
                    'service_name' => 'VoIP Service',
                    'service_type' => 'communication',
                    'status' => 'active',
                    'start_date' => Carbon::now()->subMonths(6),
                    'end_date' => null,
                    'monthly_cost' => 99.99,
                    'setup_cost' => 50.00,
                    'usage_summary' => [
                        'current_month_usage' => '450 minutes',
                        'average_monthly_usage' => '520 minutes',
                        'peak_usage_date' => Carbon::now()->subDays(15),
                        'usage_trend' => 'stable',
                    ],
                    'recent_modifications' => [],
                ],
            ]);

            return $this->successResponse('Service history retrieved', [
                'contracts' => $historyData,
                'pagination' => [
                    'current_page' => 1,
                    'total_pages' => 1,
                    'total_count' => $historyData->count(),
                    'per_page' => 20,
                ],
                'summary' => [
                    'active_services' => 1,
                    'total_monthly_cost' => 99.99,
                    'service_types' => ['communication' => 1],
                ],
            ]);

        } catch (Exception $e) {
            Log::error('Service history retrieval error', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);

            return $this->failResponse('Unable to retrieve service history');
        }
    }

    /**
     * Get support tickets for client
     */
    public function getSupportTickets(Client $client, array $filters = []): array
    {
        try {
            if (! $this->config['support_integration']) {
                return $this->successResponse('Support integration disabled', ['tickets' => []]);
            }

            // Placeholder implementation for support tickets
            // This would integrate with actual support ticket system
            $ticketData = collect([
                [
                    'id' => 1,
                    'ticket_number' => 'TKT-2024-001',
                    'subject' => 'Service Configuration Issue',
                    'status' => 'open',
                    'priority' => 'normal',
                    'category' => 'Technical Support',
                    'created_at' => Carbon::now()->subDays(2),
                    'updated_at' => Carbon::now()->subHours(4),
                    'assigned_to' => 'Support Team',
                    'response_count' => 2,
                    'last_response_at' => Carbon::now()->subHours(4),
                    'can_respond' => true,
                ],
            ]);

            return $this->successResponse('Support tickets retrieved', [
                'tickets' => $ticketData,
                'pagination' => [
                    'current_page' => 1,
                    'total_pages' => 1,
                    'total_count' => $ticketData->count(),
                    'per_page' => 15,
                ],
                'summary' => [
                    'open_tickets' => 1,
                    'pending_response' => 0,
                    'resolved_this_month' => 2,
                ],
            ]);

        } catch (Exception $e) {
            Log::error('Support tickets retrieval error', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);

            return $this->failResponse('Unable to retrieve support tickets');
        }
    }

    /**
     * Create new support ticket
     */
    public function createSupportTicket(Client $client, array $ticketData): array
    {
        try {
            if (! $this->config['support_integration']) {
                return $this->failResponse('Support integration disabled');
            }

            $validation = $this->validateTicketData($ticketData);
            if (! $validation['valid']) {
                return $this->failResponse($validation['message']);
            }

            // Placeholder implementation for ticket creation
            // This would integrate with actual support ticket system
            $ticketId = rand(1000, 9999);
            $ticketNumber = 'TKT-'.date('Y').'-'.str_pad($ticketId, 6, '0', STR_PAD_LEFT);

            // Create notification
            $this->createNotification($client, 'support_ticket_created', 'Support Ticket Created',
                "Your support ticket #{$ticketNumber} has been created and will be reviewed shortly.");

            Log::info('Support ticket created', [
                'ticket_id' => $ticketId,
                'client_id' => $client->id,
                'subject' => $ticketData['subject'],
            ]);

            return $this->successResponse('Support ticket created successfully', [
                'ticket_id' => $ticketId,
                'ticket_number' => $ticketNumber,
                'status' => 'open',
                'estimated_response_time' => '4 hours',
            ]);

        } catch (Exception $e) {
            Log::error('Support ticket creation error', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);

            return $this->failResponse('Unable to create support ticket');
        }
    }

    /**
     * Manage client documents
     */
    public function getDocuments(Client $client, array $filters = []): array
    {
        try {
            $query = ClientDocument::where('client_id', $client->id);

            // Apply filters
            if (isset($filters['category'])) {
                $query->where('category', $filters['category']);
            }

            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (isset($filters['visibility'])) {
                $query->where('visibility', $filters['visibility']);
            }

            if (isset($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('name', 'like', "%{$filters['search']}%")
                        ->orWhere('description', 'like', "%{$filters['search']}%");
                });
            }

            $documents = $query->orderBy('created_at', 'desc')
                ->paginate($filters['per_page'] ?? 20);

            $documentData = $documents->getCollection()->map(function ($document) {
                return [
                    'id' => $document->id,
                    'name' => $document->name,
                    'description' => $document->description,
                    'category' => $document->category,
                    'type' => $document->type,
                    'visibility' => $document->visibility,
                    'file_size' => $document->file_size,
                    'file_type' => $document->file_type,
                    'version' => $document->version,
                    'created_at' => $document->created_at,
                    'updated_at' => $document->updated_at,
                    'expires_at' => $document->expires_at,
                    'download_url' => $document->getDownloadUrl(),
                    'can_download' => $document->canBeDownloaded(),
                    'download_count' => $document->download_count,
                ];
            });

            return $this->successResponse('Documents retrieved', [
                'documents' => $documentData,
                'pagination' => [
                    'current_page' => $documents->currentPage(),
                    'total_pages' => $documents->lastPage(),
                    'total_count' => $documents->total(),
                    'per_page' => $documents->perPage(),
                ],
                'summary' => [
                    'total_documents' => $client->documents()->count(),
                    'by_category' => $client->documents()
                        ->select('category', DB::raw('count(*) as count'))
                        ->groupBy('category')
                        ->pluck('count', 'category')
                        ->toArray(),
                    'storage_used' => $client->getTotalDocumentStorage(),
                ],
            ]);

        } catch (Exception $e) {
            Log::error('Document retrieval error', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);

            return $this->failResponse('Unable to retrieve documents');
        }
    }

    /**
     * Upload document for client
     */
    public function uploadDocument(Client $client, UploadedFile $file, array $metadata = []): array
    {
        try {
            // Validate file
            $validation = $this->validateDocumentUpload($file);
            if (! $validation['valid']) {
                return $this->failResponse($validation['message']);
            }

            return DB::transaction(function () use ($client, $file, $metadata) {

                // Generate unique filename
                $filename = $this->generateDocumentFilename($file);

                // Store file
                $path = $file->storeAs(
                    "client-documents/{$client->company_id}/{$client->id}",
                    $filename,
                    'secure'
                );

                // Create document record
                $document = ClientDocument::create([
                    'company_id' => $client->company_id,
                    'client_id' => $client->id,
                    'name' => $metadata['name'] ?? $file->getClientOriginalName(),
                    'description' => $metadata['description'] ?? null,
                    'category' => $metadata['category'] ?? 'general',
                    'type' => $metadata['type'] ?? 'client_upload',
                    'visibility' => $metadata['visibility'] ?? 'private',
                    'file_path' => $path,
                    'file_name' => $filename,
                    'file_size' => $file->getSize(),
                    'file_type' => $file->getClientOriginalExtension(),
                    'mime_type' => $file->getMimeType(),
                    'version' => 1,
                    'security_settings' => [
                        'encrypted' => true,
                        'access_logging' => true,
                        'download_limit' => $metadata['download_limit'] ?? null,
                    ],
                    'expires_at' => isset($metadata['expires_days'])
                        ? Carbon::now()->addDays($metadata['expires_days'])
                        : null,
                    'uploaded_by' => 'client',
                ]);

                // Create notification
                $this->createNotification($client, 'document_uploaded', 'Document Uploaded',
                    "Your document '{$document->name}' has been uploaded successfully.");

                Log::info('Document uploaded', [
                    'document_id' => $document->id,
                    'client_id' => $client->id,
                    'filename' => $filename,
                    'size' => $file->getSize(),
                ]);

                return $this->successResponse('Document uploaded successfully', [
                    'document_id' => $document->id,
                    'name' => $document->name,
                    'size' => $document->file_size,
                    'type' => $document->file_type,
                ]);
            });

        } catch (Exception $e) {
            Log::error('Document upload error', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);

            return $this->failResponse('Unable to upload document');
        }
    }

    /**
     * Get notifications for client
     */
    public function getNotifications(Client $client, array $filters = []): array
    {
        try {
            $query = PortalNotification::where('client_id', $client->id);

            // Apply filters
            if (isset($filters['category'])) {
                $query->where('category', $filters['category']);
            }

            if (isset($filters['priority'])) {
                $query->where('priority', $filters['priority']);
            }

            if (isset($filters['read_status'])) {
                if ($filters['read_status'] === 'unread') {
                    $query->whereNull('read_at');
                } else {
                    $query->whereNotNull('read_at');
                }
            }

            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            $notifications = $query->orderBy('created_at', 'desc')
                ->paginate($filters['per_page'] ?? 20);

            $notificationData = $notifications->getCollection()->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'category' => $notification->category,
                    'priority' => $notification->priority,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'created_at' => $notification->created_at,
                    'read_at' => $notification->read_at,
                    'is_read' => $notification->isRead(),
                    'actions' => $notification->getAvailableActions(),
                    'metadata' => $notification->metadata,
                ];
            });

            return $this->successResponse('Notifications retrieved', [
                'notifications' => $notificationData,
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'total_pages' => $notifications->lastPage(),
                    'total_count' => $notifications->total(),
                    'per_page' => $notifications->perPage(),
                ],
                'summary' => [
                    'unread_count' => $client->notifications()->unread()->count(),
                    'total_count' => $client->notifications()->count(),
                    'by_category' => $client->notifications()
                        ->select('category', DB::raw('count(*) as count'))
                        ->groupBy('category')
                        ->pluck('count', 'category')
                        ->toArray(),
                ],
            ]);

        } catch (Exception $e) {
            Log::error('Notifications retrieval error', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);

            return $this->failResponse('Unable to retrieve notifications');
        }
    }

    /**
     * Mark notification as read
     */
    public function markNotificationAsRead(Client $client, int $notificationId): array
    {
        try {
            $notification = PortalNotification::where('client_id', $client->id)
                ->where('id', $notificationId)
                ->first();

            if (! $notification) {
                return $this->failResponse('Notification not found');
            }

            if (! $notification->isRead()) {
                $notification->markAsRead();

                Log::info('Notification marked as read', [
                    'notification_id' => $notificationId,
                    'client_id' => $client->id,
                ]);
            }

            return $this->successResponse('Notification marked as read');

        } catch (Exception $e) {
            Log::error('Notification mark as read error', [
                'notification_id' => $notificationId,
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);

            return $this->failResponse('Unable to mark notification as read');
        }
    }

    /**
     * Update client profile information
     */
    public function updateProfile(Client $client, array $profileData): array
    {
        try {
            $validation = $this->validateProfileData($profileData);
            if (! $validation['valid']) {
                return $this->failResponse($validation['message']);
            }

            return DB::transaction(function () use ($client, $profileData) {

                // Update client basic information
                $clientUpdates = [];
                if (isset($profileData['company_name'])) {
                    $clientUpdates['company_name'] = $profileData['company_name'];
                }
                if (isset($profileData['contact_name'])) {
                    $clientUpdates['contact_name'] = $profileData['contact_name'];
                }
                if (isset($profileData['phone'])) {
                    $clientUpdates['phone'] = $profileData['phone'];
                }

                if (! empty($clientUpdates)) {
                    $client->update($clientUpdates);
                }

                // Update portal access preferences
                if (isset($profileData['notification_preferences'])) {
                    $client->portalAccess->update([
                        'notification_preferences' => array_merge(
                            $client->portalAccess->notification_preferences ?? [],
                            $profileData['notification_preferences']
                        ),
                    ]);
                }

                // Update portal settings
                if (isset($profileData['portal_preferences'])) {
                    $client->portalAccess->update([
                        'portal_preferences' => array_merge(
                            $client->portalAccess->portal_preferences ?? [],
                            $profileData['portal_preferences']
                        ),
                    ]);
                }

                // Create audit log
                $this->createNotification($client, 'profile_updated', 'Profile Updated',
                    'Your account profile has been updated successfully.');

                Log::info('Client profile updated', [
                    'client_id' => $client->id,
                    'updated_fields' => array_keys($profileData),
                ]);

                return $this->successResponse('Profile updated successfully', [
                    'client_id' => $client->id,
                    'updated_at' => $client->fresh()->updated_at,
                ]);
            });

        } catch (Exception $e) {
            Log::error('Profile update error', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);

            return $this->failResponse('Unable to update profile');
        }
    }

    /**
     * Get usage analytics and reporting
     */
    public function getUsageAnalytics(Client $client, array $filters = []): array
    {
        try {
            if (! $this->config['analytics_enabled']) {
                return $this->successResponse('Analytics disabled', ['analytics' => []]);
            }

            $startDate = $filters['start_date'] ?? Carbon::now()->subDays(30);
            $endDate = $filters['end_date'] ?? Carbon::now();

            $analytics = [
                'portal_usage' => $this->getPortalUsageAnalytics($client, $startDate, $endDate),
                'service_usage' => $this->getServiceUsageAnalytics($client, $startDate, $endDate),
                'billing_analytics' => $this->getBillingAnalytics($client, $startDate, $endDate),
                'support_analytics' => $this->getSupportAnalytics($client, $startDate, $endDate),
                'trends' => $this->getUsageTrends($client, $startDate, $endDate),
                'comparisons' => $this->getUsageComparisons($client, $startDate, $endDate),
            ];

            return $this->successResponse('Usage analytics retrieved', [
                'analytics' => $analytics,
                'date_range' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'days' => $startDate->diffInDays($endDate),
                ],
            ]);

        } catch (Exception $e) {
            Log::error('Usage analytics error', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);

            return $this->failResponse('Unable to retrieve usage analytics');
        }
    }

    /**
     * Private helper methods
     */
    private function getClientInfo(Client $client): array
    {
        return [
            'id' => $client->id,
            'company_name' => $client->company_name,
            'contact_name' => $client->contact_name,
            'email' => $client->email,
            'phone' => $client->phone,
            'account_status' => $client->status,
            'account_type' => $client->type,
            'signup_date' => $client->created_at,
            'last_activity' => $client->getLastActivityDate(),
            'portal_access_level' => $client->portalAccess?->access_level ?? 'basic',
        ];
    }

    private function getAccountSummary(Client $client): array
    {
        return [
            'balance' => $client->getBalance(),
            'credit_limit' => $client->credit_limit,
            'available_credit' => $client->getAvailableCredit(),
            'payment_terms' => $client->payment_terms,
            'account_status' => $client->status,
            'next_bill_date' => $client->getNextBillDate(),
            'auto_pay_enabled' => $client->hasActiveAutoPay(),
            'services_count' => $client->activeServices()->count(),
            'monthly_recurring' => $client->getTotalMonthlyCost(),
        ];
    }

    private function getBillingOverview(Client $client): array
    {
        $recentInvoices = $client->invoices()
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'amount' => $invoice->amount,
                    'balance' => $invoice->getBalance(),
                    'status' => $invoice->status,
                    'due_date' => $invoice->due_date,
                    'issued_date' => $invoice->date,
                ];
            });

        return [
            'outstanding_balance' => $client->getOutstandingBalance(),
            'overdue_amount' => $client->getOverdueAmount(),
            'recent_invoices' => $recentInvoices,
            'next_invoice_date' => $client->getNextInvoiceDate(),
            'payment_history_count' => $client->payments()->count(),
            'last_payment_date' => $client->getLastPaymentDate(),
            'last_payment_amount' => $client->getLastPaymentAmount(),
        ];
    }

    private function getServiceStatus(Client $client): array
    {
        $services = $client->activeServices()
            ->with('contract')
            ->get()
            ->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'type' => $service->type,
                    'status' => $service->status,
                    'monthly_cost' => $service->contract?->monthly_cost,
                    'next_bill_date' => $service->contract?->next_bill_date,
                ];
            });

        return [
            'total_services' => $services->count(),
            'active_services' => $services->where('status', 'active')->count(),
            'suspended_services' => $services->where('status', 'suspended')->count(),
            'services' => $services->toArray(),
        ];
    }

    private function getRecentActivity(Client $client): array
    {
        // Combine different activities and sort by date
        $activities = collect();

        // Recent payments
        $payments = $client->payments()
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($payment) {
                return [
                    'type' => 'payment',
                    'description' => "Payment of {$payment->getFormattedAmount()}",
                    'date' => $payment->created_at,
                    'status' => $payment->status,
                    'metadata' => ['payment_id' => $payment->id],
                ];
            });
        $activities = $activities->merge($payments);

        // Recent support tickets (placeholder)
        if ($this->config['support_integration']) {
            $tickets = collect([
                [
                    'type' => 'support_ticket',
                    'description' => 'Support ticket: Service Configuration Issue',
                    'date' => Carbon::now()->subDays(2),
                    'status' => 'open',
                    'metadata' => ['ticket_id' => 1],
                ],
            ]);
            $activities = $activities->merge($tickets);
        }

        // Recent notifications
        $notifications = $client->notifications()
            ->latest()
            ->take(3)
            ->get()
            ->map(function ($notification) {
                return [
                    'type' => 'notification',
                    'description' => $notification->title,
                    'date' => $notification->created_at,
                    'status' => $notification->isRead() ? 'read' : 'unread',
                    'metadata' => ['notification_id' => $notification->id],
                ];
            });
        $activities = $activities->merge($notifications);

        return $activities->sortByDesc('date')->take(10)->values()->toArray();
    }

    private function getUpcomingItems(Client $client): array
    {
        $items = collect();

        // Upcoming invoices
        $upcomingInvoices = $client->invoices()
            ->where('due_date', '>', Carbon::now())
            ->where('due_date', '<=', Carbon::now()->addDays(30))
            ->orderBy('due_date')
            ->take(5)
            ->get()
            ->map(function ($invoice) {
                return [
                    'type' => 'invoice_due',
                    'title' => "Invoice #{$invoice->number} Due",
                    'amount' => $invoice->getBalance(),
                    'date' => $invoice->due_date,
                    'priority' => $invoice->isOverdue() ? 'high' : 'normal',
                    'action_url' => route('portal.invoices.show', $invoice->id),
                ];
            });
        $items = $items->merge($upcomingInvoices);

        // Service renewals (placeholder)
        $serviceRenewals = collect([
            [
                'type' => 'service_renewal',
                'title' => 'Service renewal: VoIP Service',
                'date' => Carbon::now()->addDays(30),
                'priority' => 'normal',
                'action_url' => '#',
            ],
        ]);
        $items = $items->merge($serviceRenewals);

        return $items->sortBy('date')->take(8)->values()->toArray();
    }

    private function getRecentNotifications(Client $client): array
    {
        return $client->notifications()
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'priority' => $notification->priority,
                    'created_at' => $notification->created_at,
                    'is_read' => $notification->isRead(),
                ];
            })
            ->toArray();
    }

    private function getSupportSummary(Client $client): array
    {
        if (! $this->config['support_integration']) {
            return ['enabled' => false];
        }

        return [
            'enabled' => true,
            'open_tickets' => 1,
            'pending_response' => 0,
            'resolved_this_month' => 2,
            'average_response_time' => '2.5 hours',
            'satisfaction_rating' => 4.5,
        ];
    }

    private function getUsageSummary(Client $client): array
    {
        if (! $this->config['usage_tracking_enabled']) {
            return ['enabled' => false];
        }

        $currentMonth = Carbon::now()->format('Y-m');
        $previousMonth = Carbon::now()->subMonth()->format('Y-m');

        return [
            'enabled' => true,
            'current_month' => [
                'portal_logins' => 15,
                'pages_viewed' => 245,
                'documents_downloaded' => 8,
                'payments_made' => 3,
            ],
            'previous_month' => [
                'portal_logins' => 12,
                'pages_viewed' => 198,
                'documents_downloaded' => 5,
                'payments_made' => 2,
            ],
        ];
    }

    private function getPaymentMethodSummary(Client $client): array
    {
        $paymentMethods = $client->paymentMethods()
            ->active()
            ->get()
            ->map(function ($method) {
                return [
                    'id' => $method->id,
                    'type' => $method->type,
                    'display_name' => $method->getDisplayName(),
                    'is_default' => $method->is_default,
                    'is_verified' => $method->isVerified(),
                    'expires_soon' => $method->expiresSoon(),
                ];
            });

        return [
            'total_methods' => $paymentMethods->count(),
            'verified_methods' => $paymentMethods->where('is_verified', true)->count(),
            'expiring_soon' => $paymentMethods->where('expires_soon', true)->count(),
            'auto_pay_enabled' => $client->hasActiveAutoPay(),
            'methods' => $paymentMethods->toArray(),
        ];
    }

    private function getDocumentSummary(Client $client): array
    {
        return [
            'total_documents' => $client->documents()->count(),
            'recent_uploads' => $client->documents()
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->count(),
            'storage_used' => $client->getTotalDocumentStorage(),
            'by_category' => $client->documents()
                ->select('category', DB::raw('count(*) as count'))
                ->groupBy('category')
                ->pluck('count', 'category')
                ->toArray(),
        ];
    }

    private function getPortalMetrics(Client $client): array
    {
        return [
            'last_login' => Carbon::now()->subHours(2),
            'session_duration' => '1h 23m',
            'total_logins' => 47,
            'pages_this_session' => 12,
            'favorite_pages' => ['dashboard', 'invoices', 'payments'],
            'browser_info' => [
                'browser' => 'Chrome',
                'version' => '120.0',
                'os' => 'Windows 10',
            ],
        ];
    }

    private function getContractUsageSummary($contract): array
    {
        return [
            'current_month_usage' => $contract->getCurrentMonthUsage(),
            'average_monthly_usage' => $contract->getAverageMonthlyUsage(),
            'peak_usage_date' => $contract->getPeakUsageDate(),
            'usage_trend' => $contract->getUsageTrend(),
        ];
    }

    private function processTicketAttachments(int $ticketId, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            if ($attachment instanceof UploadedFile) {
                $filename = $this->generateDocumentFilename($attachment);
                $path = $attachment->storeAs(
                    "support-tickets/{$ticketId}",
                    $filename,
                    'secure'
                );

                Log::info('Ticket attachment processed', [
                    'ticket_id' => $ticketId,
                    'filename' => $filename,
                    'path' => $path,
                ]);
            }
        }
    }

    private function validateTicketData(array $ticketData): array
    {
        $errors = [];

        if (empty($ticketData['subject'])) {
            $errors[] = 'Subject is required';
        }

        if (empty($ticketData['description'])) {
            $errors[] = 'Description is required';
        }

        if (isset($ticketData['priority']) && ! in_array($ticketData['priority'], ['low', 'normal', 'high', 'urgent'])) {
            $errors[] = 'Invalid priority level';
        }

        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? 'Valid' : implode('. ', $errors),
        ];
    }

    private function validateDocumentUpload(UploadedFile $file): array
    {
        $errors = [];

        if ($file->getSize() > $this->config['max_document_size']) {
            $errors[] = 'File size exceeds maximum allowed size';
        }

        if (! in_array($file->getClientOriginalExtension(), $this->config['allowed_document_types'])) {
            $errors[] = 'File type not allowed';
        }

        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? 'Valid' : implode('. ', $errors),
        ];
    }

    private function generateDocumentFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $hash = hash('sha256', $file->getContent().time());

        return substr($hash, 0, 16).'.'.$extension;
    }

    private function validateProfileData(array $profileData): array
    {
        $errors = [];

        if (isset($profileData['company_name']) && empty(trim($profileData['company_name']))) {
            $errors[] = 'Company name cannot be empty';
        }

        if (isset($profileData['contact_name']) && empty(trim($profileData['contact_name']))) {
            $errors[] = 'Contact name cannot be empty';
        }

        if (isset($profileData['phone']) && ! preg_match('/^[\d\s\-\+\(\)]+$/', $profileData['phone'])) {
            $errors[] = 'Invalid phone number format';
        }

        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? 'Valid' : implode('. ', $errors),
        ];
    }

    private function getPortalUsageAnalytics(Client $client, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'total_logins' => 45,
            'unique_sessions' => 38,
            'total_page_views' => 523,
            'average_session_duration' => '18 minutes',
            'most_visited_pages' => ['dashboard', 'invoices', 'payments', 'documents'],
            'device_breakdown' => ['desktop' => 65, 'mobile' => 35],
            'browser_breakdown' => ['chrome' => 78, 'firefox' => 15, 'safari' => 7],
        ];
    }

    private function getServiceUsageAnalytics(Client $client, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'total_usage' => '1,240 minutes',
            'usage_by_service' => ['voip' => 1240, 'data' => 0],
            'peak_usage_days' => [Carbon::now()->subDays(5), Carbon::now()->subDays(12)],
            'usage_trends' => 'stable',
            'cost_breakdown' => ['base' => 99.99, 'overage' => 15.50],
        ];
    }

    private function getBillingAnalytics(Client $client, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'total_billed' => 299.97,
            'total_paid' => 299.97,
            'outstanding_balance' => 0.00,
            'payment_history' => ['count' => 3, 'avg_amount' => 99.99],
            'invoice_summary' => ['count' => 3, 'avg_amount' => 99.99],
            'payment_method_usage' => ['credit_card' => 2, 'bank_account' => 1],
        ];
    }

    private function getSupportAnalytics(Client $client, Carbon $startDate, Carbon $endDate): array
    {
        if (! $this->config['support_integration']) {
            return ['enabled' => false];
        }

        return [
            'enabled' => true,
            'tickets_created' => 2,
            'tickets_resolved' => 1,
            'average_resolution_time' => '6 hours',
            'tickets_by_category' => ['technical' => 1, 'billing' => 1],
            'satisfaction_scores' => [4.5, 5.0],
        ];
    }

    private function getUsageTrends(Client $client, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'portal_login_trend' => 'increasing',
            'billing_trend' => 'stable',
            'service_usage_trend' => 'stable',
            'support_activity_trend' => 'decreasing',
        ];
    }

    private function getUsageComparisons(Client $client, Carbon $startDate, Carbon $endDate): array
    {
        $previousPeriodStart = $startDate->copy()->subDays($startDate->diffInDays($endDate));
        $previousPeriodEnd = $startDate->copy()->subDay();

        return [
            'current_period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_logins' => 45,
                'total_payments' => 299.97,
                'support_tickets' => 2,
            ],
            'previous_period' => [
                'start_date' => $previousPeriodStart,
                'end_date' => $previousPeriodEnd,
                'total_logins' => 38,
                'total_payments' => 199.98,
                'support_tickets' => 1,
            ],
        ];
    }

    private function createNotification(Client $client, string $type, string $title, string $message): void
    {
        PortalNotification::create([
            'company_id' => $client->company_id,
            'client_id' => $client->id,
            'type' => $type,
            'category' => 'portal',
            'priority' => 'normal',
            'title' => $title,
            'message' => $message,
            'show_in_portal' => true,
            'send_email' => false,
        ]);
    }

    private function successResponse(string $message, array $data = []): array
    {
        return array_merge([
            'success' => true,
            'message' => $message,
        ], $data);
    }

    private function failResponse(string $message, ?string $errorCode = null): array
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errorCode) {
            $response['error_code'] = $errorCode;
        }

        return $response;
    }
}
