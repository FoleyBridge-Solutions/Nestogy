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