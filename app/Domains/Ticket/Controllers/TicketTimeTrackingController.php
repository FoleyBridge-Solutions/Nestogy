<?php

namespace App\Domains\Ticket\Controllers;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketTimeEntry;
use App\Domains\Ticket\Services\TimeTrackingService;
use App\Domains\Ticket\Services\WorkTypeClassificationService;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TicketTimeTrackingController extends Controller
{
    public function __construct(
        private TimeTrackingService $timeTrackingService,
        private WorkTypeClassificationService $classificationService
    ) {}

    public function getSmartTrackingInfo(Ticket $ticket)
    {
        try {
            $trackingInfo = $this->timeTrackingService->startSmartTracking($ticket, $this->user());
            $templates = $this->classificationService->getTemplateSuggestions($ticket, 5);

            return response()->json([
                'tracking_info' => $trackingInfo,
                'templates' => $templates->map(fn ($suggestion) => [
                    'id' => $suggestion['template']->id,
                    'name' => $suggestion['template']->name,
                    'description' => $suggestion['template']->description,
                    'work_type' => $suggestion['template']->work_type,
                    'default_hours' => $suggestion['template']->default_hours,
                    'confidence' => $suggestion['confidence'],
                ]),
            ]);
        } catch (\Exception $e) {
            \Log::error('Smart tracking info error', [
                'ticket_id' => $ticket->id,
                'user_id' => $this->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Error loading tracking information: '.$e->getMessage(),
                'tracking_info' => ['error' => 'Unable to load tracking info'],
                'templates' => [],
            ], 500);
        }
    }

    public function startSmartTimer(Request $request, Ticket $ticket)
    {
        try {
            $timeEntry = $this->timeTrackingService->startTracking($ticket, $this->user(), [
                'work_type' => $request->input('work_type', 'general_support'),
                'description' => $request->input('description'),
                'auto_start' => true,
            ]);

            return response()->json([
                'success' => true,
                'time_entry' => $timeEntry,
                'message' => 'Timer started successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function pauseTimer(Request $request, Ticket $ticket)
    {
        try {
            $activeEntry = $this->findActiveTimer($ticket);

            if (! $activeEntry) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active timer found for this ticket',
                ], 400);
            }

            $pausedEntry = $this->timeTrackingService->pauseTracking($activeEntry, $request->input('reason'));

            return response()->json([
                'success' => true,
                'time_entry' => $pausedEntry,
                'message' => 'Timer paused successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function stopTimer(Request $request, Ticket $ticket)
    {
        try {
            $activeEntry = $this->findActiveTimer($ticket);

            if (! $activeEntry) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active timer found for this ticket',
                ], 400);
            }

            if ($request->has('description')) {
                $activeEntry->description = $request->input('description');
            }

            if ($request->has('work_performed')) {
                $activeEntry->work_performed = $request->input('work_performed');
            }

            $hoursWorked = $activeEntry->stopTimer();

            $amount = 0;
            if ($activeEntry->billable && $activeEntry->hourly_rate) {
                $amount = $hoursWorked * $activeEntry->hourly_rate;
                $activeEntry->amount = $amount;
                $activeEntry->save();
            }

            return response()->json([
                'success' => true,
                'time_entry' => $activeEntry->fresh(),
                'message' => 'Timer stopped and saved successfully',
                'hours_worked' => $hoursWorked,
                'amount' => $amount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function createTimeFromTemplate(Request $request, Ticket $ticket)
    {
        $request->validate([
            'template_id' => 'required|exists:time_entry_templates,id',
            'hours_worked' => 'nullable|numeric|min:0.01|max:24',
            'description' => 'nullable|string|max:1000',
            'billable' => 'nullable|boolean',
        ]);

        try {
            $overrides = array_filter([
                'hours_worked' => $request->input('hours_worked'),
                'description' => $request->input('description'),
                'billable' => $request->input('billable'),
            ]);

            $timeEntry = $this->timeTrackingService->createFromTemplate(
                $request->input('template_id'),
                $ticket,
                $this->user(),
                $overrides
            );

            return response()->json([
                'success' => true,
                'time_entry' => $timeEntry,
                'message' => 'Time entry created from template',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function getBillingDashboard(Request $request)
    {
        $date = $request->input('date') ? Carbon::parse($request->input('date')) : today();
        $dashboard = $this->timeTrackingService->getBillingDashboard($this->user(), $date);

        return response()->json($dashboard);
    }

    public function validateTimeEntry(Request $request)
    {
        $validation = $this->timeTrackingService->validateTimeEntry($request->all());

        return response()->json($validation);
    }

    public function getWorkTypeSuggestions(Ticket $ticket)
    {
        $suggestions = $this->classificationService->getWorkTypeSuggestions($ticket);

        return response()->json(['suggestions' => $suggestions]);
    }

    public function getCurrentRateInfo(Request $request)
    {
        $time = $request->input('time') ? Carbon::parse($request->input('time')) : now();
        $context = $request->input('context', []);
        $rateInfo = $this->timeTrackingService->getSmartRateInfo($time, $context);

        return response()->json($rateInfo);
    }

    public function getTimeTemplates(Request $request)
    {
        $user = $this->user();

        $query = \App\Domains\Ticket\Models\TimeEntryTemplate::where('company_id', $user->company_id)
            ->active();

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($workType = $request->input('work_type')) {
            $query->where('work_type', $workType);
        }

        $templates = $query->orderBy('usage_count', 'desc')
            ->orderBy('name')
            ->get();

        return response()->json(['templates' => $templates]);
    }

    private function findActiveTimer(Ticket $ticket): ?TicketTimeEntry
    {
        $user = $this->user();

        return TicketTimeEntry::where('ticket_id', $ticket->id)
            ->where('user_id', $user->id)
            ->where('company_id', $user->company_id)
            ->where('entry_type', TicketTimeEntry::TYPE_TIMER)
            ->whereNotNull('started_at')
            ->whereNull('ended_at')
            ->first();
    }

    private function user()
    {
        return auth()->user();
    }
}
