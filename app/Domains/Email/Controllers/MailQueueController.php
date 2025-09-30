<?php

namespace App\Domains\Email\Controllers;

use App\Models\MailQueue;
use App\Domains\Email\Services\UnifiedMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MailQueueController extends Controller
{
    protected UnifiedMailService $mailService;
    
    public function __construct(UnifiedMailService $mailService)
    {
        $this->middleware('auth');
        $this->middleware('permission:settings.email.view')->only(['index', 'show']);
        $this->middleware('permission:settings.email.manage')->only(['retry', 'cancel', 'process']);
        
        $this->mailService = $mailService;
    }
    
    /**
     * Display mail queue dashboard
     */
    public function index(Request $request)
    {
        $query = MailQueue::query()
            ->with(['client', 'contact', 'user']);
        
        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('to_email', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('uuid', $search);
            });
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Get statistics
        $stats = $this->getStatistics($request);
        
        // Get emails with pagination
        $emails = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();
        
        // Get failure reasons for filter
        $failureReasons = MailQueue::where('status', 'failed')
            ->whereNotNull('failure_reason')
            ->distinct()
            ->pluck('failure_reason');
        
        return view('settings.mail-queue.index', compact(
            'emails',
            'stats',
            'failureReasons'
        ));
    }
    
    /**
     * Show email details
     */
    public function show(MailQueue $mailQueue)
    {
        $mailQueue->load(['client', 'contact', 'user']);
        
        return view('settings.mail-queue.show', compact('mailQueue'));
    }
    
    /**
     * Retry a failed email
     */
    public function retry(MailQueue $mailQueue)
    {
        if (!$mailQueue->canRetry()) {
            return back()->with('error', 'This email cannot be retried.');
        }
        
        // Reset status to pending and clear error
        $mailQueue->update([
            'status' => MailQueue::STATUS_PENDING,
            'last_error' => null,
            'next_retry_at' => null,
        ]);
        
        // Process immediately
        if ($this->mailService->process($mailQueue)) {
            return back()->with('success', 'Email has been sent successfully.');
        }
        
        return back()->with('error', 'Failed to send email. Check the error details.');
    }
    
    /**
     * Cancel a pending email
     */
    public function cancel(MailQueue $mailQueue)
    {
        if ($this->mailService->cancel($mailQueue)) {
            return back()->with('success', 'Email has been cancelled.');
        }
        
        return back()->with('error', 'Cannot cancel this email.');
    }
    
    /**
     * Process pending emails manually
     */
    public function process(Request $request)
    {
        $limit = $request->get('limit', 10);
        $processed = $this->mailService->processPending($limit);
        
        return back()->with('success', "Processed {$processed} email(s).");
    }
    
    /**
     * Get mail queue statistics
     */
    protected function getStatistics(Request $request): array
    {
        $query = MailQueue::query();
        
        // Apply same filters as listing
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $stats = [
            'total' => $query->count(),
            'by_status' => [],
            'by_category' => [],
            'by_priority' => [],
            'recent_failures' => [],
            'hourly_chart' => [],
        ];
        
        // Status breakdown
        $stats['by_status'] = $query->clone()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        // Category breakdown
        $stats['by_category'] = $query->clone()
            ->whereNotNull('category')
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();
        
        // Priority breakdown
        $stats['by_priority'] = $query->clone()
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();
        
        // Recent failures
        $stats['recent_failures'] = MailQueue::where('status', 'failed')
            ->orderBy('failed_at', 'desc')
            ->limit(5)
            ->get(['id', 'to_email', 'subject', 'failure_reason', 'failed_at']);
        
        // Hourly chart data for last 24 hours
        $stats['hourly_chart'] = $query->clone()
            ->where('created_at', '>=', now()->subHours(24))
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('count(*) as total'),
                DB::raw('sum(case when status = "sent" then 1 else 0 end) as sent'),
                DB::raw('sum(case when status = "failed" then 1 else 0 end) as failed')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
        
        // Calculate rates
        $totalSent = $stats['by_status']['sent'] ?? 0;
        $totalFailed = $stats['by_status']['failed'] ?? 0;
        $total = $stats['total'];
        
        $stats['success_rate'] = $total > 0 ? round(($totalSent / $total) * 100, 2) : 0;
        $stats['failure_rate'] = $total > 0 ? round(($totalFailed / $total) * 100, 2) : 0;
        
        return $stats;
    }
    
    /**
     * Export mail queue to CSV
     */
    public function export(Request $request)
    {
        $query = MailQueue::query();
        
        // Apply filters (same as index)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $emails = $query->get();
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="mail_queue_' . date('Y-m-d') . '.csv"',
        ];
        
        $callback = function() use ($emails) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'Date',
                'To',
                'Subject',
                'Status',
                'Category',
                'Priority',
                'Attempts',
                'Error',
                'Sent At',
            ]);
            
            foreach ($emails as $email) {
                fputcsv($file, [
                    $email->created_at->format('Y-m-d H:i:s'),
                    $email->to_email,
                    $email->subject,
                    $email->status,
                    $email->category,
                    $email->priority,
                    $email->attempts,
                    $email->last_error,
                    $email->sent_at?->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}