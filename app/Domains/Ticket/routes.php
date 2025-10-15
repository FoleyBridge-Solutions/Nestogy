<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::resource('calendar', \App\Domains\Ticket\Controllers\CalendarController::class);
        Route::resource('templates', \App\Domains\Ticket\Controllers\TemplateController::class);
        Route::resource('recurring', \App\Domains\Ticket\Controllers\RecurringTicketController::class);
        Route::resource('time-tracking', \App\Domains\Ticket\Controllers\TimeTrackingController::class);

        // Custom time-tracking routes
        Route::post('time-tracking/start-timer', [\App\Domains\Ticket\Controllers\TimeTrackingController::class, 'startTimer'])->name('time-tracking.start-timer');
        Route::post('time-tracking/stop-timer', [\App\Domains\Ticket\Controllers\TimeTrackingController::class, 'stopTimer'])->name('time-tracking.stop-timer');

        // QUEUE VIEWS - TicketQueueController
        Route::get('active-timers', [\App\Domains\Ticket\Controllers\TicketQueueController::class, 'activeTimers'])->name('active-timers');
        Route::get('sla-violations', [\App\Domains\Ticket\Controllers\TicketQueueController::class, 'slaViolations'])->name('sla-violations');
        Route::get('sla-warning', [\App\Domains\Ticket\Controllers\TicketQueueController::class, 'slaWarning'])->name('sla-warning');
        Route::get('unassigned', [\App\Domains\Ticket\Controllers\TicketQueueController::class, 'unassigned'])->name('unassigned');
        Route::get('due-today', [\App\Domains\Ticket\Controllers\TicketQueueController::class, 'dueToday'])->name('due-today');
        Route::get('team-queue', [\App\Domains\Ticket\Controllers\TicketQueueController::class, 'teamQueue'])->name('team-queue');
        Route::get('customer-waiting', [\App\Domains\Ticket\Controllers\TicketQueueController::class, 'customerWaiting'])->name('customer-waiting');
        Route::get('watched', [\App\Domains\Ticket\Controllers\TicketQueueController::class, 'watched'])->name('watched');
        Route::get('escalated', [\App\Domains\Ticket\Controllers\TicketQueueController::class, 'escalated'])->name('escalated');
        Route::get('merged', [\App\Domains\Ticket\Controllers\TicketQueueController::class, 'merged'])->name('merged');
        Route::get('archive', [\App\Domains\Ticket\Controllers\TicketQueueController::class, 'archive'])->name('archive');
        Route::get('time-billing', [\App\Domains\Ticket\Controllers\TicketQueueController::class, 'timeBilling'])->name('time-billing');
        Route::get('analytics', [\App\Domains\Ticket\Controllers\TicketQueueController::class, 'analytics'])->name('analytics');
        Route::get('knowledge-base', [\App\Domains\Ticket\Controllers\TicketQueueController::class, 'knowledgeBase'])->name('knowledge-base');
        Route::get('automation-rules', [\App\Domains\Ticket\Controllers\TicketQueueController::class, 'automationRules'])->name('automation-rules');

        // Priority Queue custom routes (must come before resource)
        Route::post('priority-queue/auto-prioritize', [\App\Domains\Ticket\Controllers\PriorityQueueController::class, 'autoPrioritize'])->name('priority-queue.auto-prioritize');
        Route::post('priority-queue/bulk-update', [\App\Domains\Ticket\Controllers\PriorityQueueController::class, 'bulkUpdate'])->name('priority-queue.bulk-update');
        Route::post('priority-queue/escalate', [\App\Domains\Ticket\Controllers\PriorityQueueController::class, 'escalate'])->name('priority-queue.escalate');
        Route::get('priority-queue/export', [\App\Domains\Ticket\Controllers\PriorityQueueController::class, 'export'])->name('priority-queue.export');

        Route::resource('priority-queue', \App\Domains\Ticket\Controllers\PriorityQueueController::class);
        Route::resource('workflows', \App\Domains\Ticket\Controllers\WorkflowController::class);
        Route::resource('assignments', \App\Domains\Ticket\Controllers\AssignmentController::class);

        // Custom assignment routes
        Route::get('{ticket}/assignments/assign', [\App\Domains\Ticket\Controllers\AssignmentController::class, 'assignToMe'])->name('assignments.assign');
        Route::post('{ticket}/assignments/watchers/add', [\App\Domains\Ticket\Controllers\AssignmentController::class, 'addWatcher'])->name('assignments.watchers.add');

        // COMMENT ROUTES - TicketCommentController
        Route::post('{ticket}/replies', [\App\Domains\Ticket\Controllers\TicketCommentController::class, 'store'])->name('replies.store');
        Route::post('{ticket}/comments', [\App\Domains\Ticket\Controllers\TicketCommentController::class, 'store'])->name('comments.store');

        // RESOLUTION ROUTES - TicketResolutionController
        Route::post('{ticket}/resolve', [\App\Domains\Ticket\Controllers\TicketResolutionController::class, 'resolve'])->name('resolve');
        Route::post('{ticket}/reopen', [\App\Domains\Ticket\Controllers\TicketResolutionController::class, 'reopen'])->name('reopen');

        // EXPORT ROUTES - TicketExportController
        Route::get('{ticket}/pdf', [\App\Domains\Ticket\Controllers\TicketExportController::class, 'generatePdf'])->name('pdf');
        Route::get('export/csv', [\App\Domains\Ticket\Controllers\TicketExportController::class, 'export'])->name('export.csv');

        // STATUS ROUTES - TicketStatusController
        Route::patch('{ticket}/status', [\App\Domains\Ticket\Controllers\TicketStatusController::class, 'updateStatus'])->name('status.update');
        Route::patch('{ticket}/priority', [\App\Domains\Ticket\Controllers\TicketStatusController::class, 'updatePriority'])->name('priority.update');
        Route::patch('{ticket}/assign', [\App\Domains\Ticket\Controllers\TicketStatusController::class, 'assign'])->name('assign');

        // SCHEDULING ROUTES - TicketSchedulingController
        Route::patch('{ticket}/schedule', [\App\Domains\Ticket\Controllers\TicketSchedulingController::class, 'schedule'])->name('schedule');

        // MERGE ROUTES - TicketMergeController
        Route::post('{ticket}/merge', [\App\Domains\Ticket\Controllers\TicketMergeController::class, 'merge'])->name('merge');

        // SEARCH ROUTES - TicketSearchController
        Route::get('search', [\App\Domains\Ticket\Controllers\TicketSearchController::class, 'search'])->name('search');
        Route::get('{ticket}/viewers', [\App\Domains\Ticket\Controllers\TicketSearchController::class, 'getViewers'])->name('viewers');

        // TIME TRACKING ROUTES - TicketTimeTrackingController
        Route::get('{ticket}/smart-tracking-info', [\App\Domains\Ticket\Controllers\TicketTimeTrackingController::class, 'getSmartTrackingInfo'])->name('smart-tracking-info');
        Route::post('{ticket}/start-smart-timer', [\App\Domains\Ticket\Controllers\TicketTimeTrackingController::class, 'startSmartTimer'])->name('start-smart-timer');
        Route::post('{ticket}/pause-timer', [\App\Domains\Ticket\Controllers\TicketTimeTrackingController::class, 'pauseTimer'])->name('pause-timer');
        Route::post('{ticket}/stop-timer', [\App\Domains\Ticket\Controllers\TicketTimeTrackingController::class, 'stopTimer'])->name('stop-timer');
        Route::post('{ticket}/create-time-from-template', [\App\Domains\Ticket\Controllers\TicketTimeTrackingController::class, 'createTimeFromTemplate'])->name('create-time-from-template');
        Route::get('{ticket}/work-type-suggestions', [\App\Domains\Ticket\Controllers\TicketTimeTrackingController::class, 'getWorkTypeSuggestions'])->name('work-type-suggestions');

        // TIME TRACKING API ROUTES
        Route::get('api/billing-dashboard', [\App\Domains\Ticket\Controllers\TicketTimeTrackingController::class, 'getBillingDashboard'])->name('api.billing-dashboard');
        Route::post('api/validate-time-entry', [\App\Domains\Ticket\Controllers\TicketTimeTrackingController::class, 'validateTimeEntry'])->name('api.validate-time-entry');
        Route::get('api/current-rate-info', [\App\Domains\Ticket\Controllers\TicketTimeTrackingController::class, 'getCurrentRateInfo'])->name('api.current-rate-info');
        Route::get('api/time-templates', [\App\Domains\Ticket\Controllers\TicketTimeTrackingController::class, 'getTimeTemplates'])->name('api.time-templates');
    });

    // Main tickets resource routes (must come after specific prefixed routes)
    Route::resource('tickets', \App\Domains\Ticket\Controllers\TicketController::class);
});
