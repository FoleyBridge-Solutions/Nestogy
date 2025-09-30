<?php
// Ticket routes - specific routes first, then resource routes

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
        
        // NEW FEATURE ROUTES
        // Active Timers
        Route::get('active-timers', [\App\Domains\Ticket\Controllers\TicketController::class, 'activeTimers'])->name('active-timers');
        
        // SLA Management
        Route::get('sla-violations', [\App\Domains\Ticket\Controllers\TicketController::class, 'slaViolations'])->name('sla-violations');
        Route::get('sla-warning', [\App\Domains\Ticket\Controllers\TicketController::class, 'slaWarning'])->name('sla-warning');
        
        // Queue Management
        Route::get('unassigned', [\App\Domains\Ticket\Controllers\TicketController::class, 'unassigned'])->name('unassigned');
        Route::get('due-today', [\App\Domains\Ticket\Controllers\TicketController::class, 'dueToday'])->name('due-today');
        Route::get('team-queue', [\App\Domains\Ticket\Controllers\TicketController::class, 'teamQueue'])->name('team-queue');
        Route::get('customer-waiting', [\App\Domains\Ticket\Controllers\TicketController::class, 'customerWaiting'])->name('customer-waiting');
        
        // Watched & Related
        Route::get('watched', [\App\Domains\Ticket\Controllers\TicketController::class, 'watched'])->name('watched');
        Route::get('escalated', [\App\Domains\Ticket\Controllers\TicketController::class, 'escalated'])->name('escalated');
        Route::get('merged', [\App\Domains\Ticket\Controllers\TicketController::class, 'merged'])->name('merged');
        
        // Analytics & Reports
        Route::get('time-billing', [\App\Domains\Ticket\Controllers\TicketController::class, 'timeBilling'])->name('time-billing');
        Route::get('analytics', [\App\Domains\Ticket\Controllers\TicketController::class, 'analytics'])->name('analytics');
        
        // Knowledge Base Integration
        Route::get('knowledge-base', [\App\Domains\Ticket\Controllers\TicketController::class, 'knowledgeBase'])->name('knowledge-base');
        
        // Automation
        Route::get('automation-rules', [\App\Domains\Ticket\Controllers\TicketController::class, 'automationRules'])->name('automation-rules');
        
        // Archive
        Route::get('archive', [\App\Domains\Ticket\Controllers\TicketController::class, 'archive'])->name('archive');
        
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
        
        // Ticket replies/comments routes
        Route::post('{ticket}/replies', [\App\Domains\Ticket\Controllers\TicketController::class, 'storeReply'])->name('replies.store');
        Route::post('{ticket}/comments', [\App\Domains\Ticket\Controllers\TicketController::class, 'storeReply'])->name('comments.store');
        
        // Resolution routes
        Route::post('{ticket}/resolve', [\App\Domains\Ticket\Controllers\TicketController::class, 'resolve'])->name('resolve');
        Route::post('{ticket}/reopen', [\App\Domains\Ticket\Controllers\TicketController::class, 'reopen'])->name('reopen');
        
        // Ticket PDF export
        Route::get('{ticket}/pdf', [\App\Domains\Ticket\Controllers\TicketController::class, 'generatePdf'])->name('pdf');
        
        // Ticket status update
        Route::patch('{ticket}/status', [\App\Domains\Ticket\Controllers\TicketController::class, 'updateStatus'])->name('status.update');
        
        // Ticket assignment
        Route::patch('{ticket}/assign', [\App\Domains\Ticket\Controllers\TicketController::class, 'assign'])->name('assign');
        
        // Ticket scheduling
        Route::patch('{ticket}/schedule', [\App\Domains\Ticket\Controllers\TicketController::class, 'schedule'])->name('schedule');
        
        // Ticket merging
        Route::post('{ticket}/merge', [\App\Domains\Ticket\Controllers\TicketController::class, 'merge'])->name('merge');
        
        // Ticket search for merge functionality
        Route::get('search', [\App\Domains\Ticket\Controllers\TicketController::class, 'search'])->name('search');
        
        // Ticket viewers (collision detection)
        Route::get('{ticket}/viewers', [\App\Domains\Ticket\Controllers\TicketController::class, 'getViewers'])->name('viewers');
        
        // Smart Time Tracking Routes
        Route::get('{ticket}/smart-tracking-info', [\App\Domains\Ticket\Controllers\TicketController::class, 'getSmartTrackingInfo'])->name('smart-tracking-info');
        Route::post('{ticket}/start-smart-timer', [\App\Domains\Ticket\Controllers\TicketController::class, 'startSmartTimer'])->name('start-smart-timer');
        Route::post('{ticket}/pause-timer', [\App\Domains\Ticket\Controllers\TicketController::class, 'pauseTimer'])->name('pause-timer');
        Route::post('{ticket}/stop-timer', [\App\Domains\Ticket\Controllers\TicketController::class, 'stopTimer'])->name('stop-timer');
        Route::post('{ticket}/create-time-from-template', [\App\Domains\Ticket\Controllers\TicketController::class, 'createTimeFromTemplate'])->name('create-time-from-template');
        Route::get('{ticket}/work-type-suggestions', [\App\Domains\Ticket\Controllers\TicketController::class, 'getWorkTypeSuggestions'])->name('work-type-suggestions');
        
        // Smart Time Tracking API Routes
        Route::get('api/billing-dashboard', [\App\Domains\Ticket\Controllers\TicketController::class, 'getBillingDashboard'])->name('api.billing-dashboard');
        Route::post('api/validate-time-entry', [\App\Domains\Ticket\Controllers\TicketController::class, 'validateTimeEntry'])->name('api.validate-time-entry');
        Route::get('api/current-rate-info', [\App\Domains\Ticket\Controllers\TicketController::class, 'getCurrentRateInfo'])->name('api.current-rate-info');
        Route::get('api/time-templates', [\App\Domains\Ticket\Controllers\TicketController::class, 'getTimeTemplates'])->name('api.time-templates');
        
        Route::get('export/csv', [\App\Domains\Ticket\Controllers\TicketController::class, 'exportCsv'])->name('export.csv');
    });

        
    // Main tickets resource routes (must come after specific prefixed routes)
    Route::resource('tickets', \App\Domains\Ticket\Controllers\TicketController::class);
});