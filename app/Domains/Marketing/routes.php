<?php

// Marketing and campaign management routes

use App\Domains\Marketing\Controllers\CampaignController;
use Illuminate\Support\Facades\Route;

// Public email tracking routes (no auth required)
Route::get('marketing/email/track-open/{tracking_id}', function ($trackingId) {
    app(\App\Domains\Marketing\Services\CampaignEmailService::class)->trackEmailOpen(
        $trackingId,
        request()->header('User-Agent'),
        request()->ip()
    );

    // Return 1x1 transparent pixel
    return response()->make(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), 200)
        ->header('Content-Type', 'image/gif');
})->name('marketing.email.track-open');

Route::get('marketing/email/track-click/{tracking_id}', function ($trackingId) {
    $url = urldecode(request('url'));

    app(\App\Domains\Marketing\Services\CampaignEmailService::class)->trackEmailClick(
        $trackingId,
        $url,
        request()->header('User-Agent'),
        request()->ip()
    );

    return redirect($url);
})->name('marketing.email.track-click');

Route::get('marketing/unsubscribe', function () {
    $enrollmentId = request('enrollment');
    $token = request('token');

    return app(\App\Domains\Marketing\Services\CampaignEmailService::class)->handleUnsubscribe($enrollmentId, $token);
})->name('marketing.unsubscribe');

// Authenticated marketing routes
Route::middleware(['web', 'auth', 'verified'])->prefix('marketing')->name('marketing.')->group(function () {
    // Marketing campaigns
    Route::resource('campaigns', CampaignController::class);

    Route::prefix('campaigns/{campaign}')->name('campaigns.')->group(function () {
        Route::post('start', [CampaignController::class, 'start'])->name('start');
        Route::post('pause', [CampaignController::class, 'pause'])->name('pause');
        Route::post('complete', [CampaignController::class, 'complete'])->name('complete');
        Route::post('clone', [CampaignController::class, 'clone'])->name('clone');
        Route::get('analytics', [CampaignController::class, 'analytics'])->name('analytics');

        // Email sequences
        Route::post('sequences', [CampaignController::class, 'addSequence'])->name('sequences.store');
        Route::post('sequences/{sequence}/test-email', [CampaignController::class, 'sendTestEmail'])->name('sequences.test-email');

        // Enrollment
        Route::post('enroll-leads', [CampaignController::class, 'enrollLeads'])->name('enroll-leads');
        Route::post('enroll-contacts', [CampaignController::class, 'enrollContacts'])->name('enroll-contacts');
    });

    // Enrollments
    Route::get('enrollments', [\App\Domains\Marketing\Controllers\EnrollmentController::class, 'index'])
        ->name('enrollments.index');

    // Email Templates
    Route::resource('templates', \App\Domains\Marketing\Controllers\EmailTemplateController::class);

    // Analytics
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('campaigns', [\App\Domains\Marketing\Controllers\AnalyticsController::class, 'campaigns'])
            ->name('campaigns');
        Route::get('email-tracking', [\App\Domains\Marketing\Controllers\AnalyticsController::class, 'emailTracking'])
            ->name('email-tracking');
        Route::get('attribution', [\App\Domains\Marketing\Controllers\AnalyticsController::class, 'attribution'])
            ->name('attribution');
        Route::get('revenue', [\App\Domains\Marketing\Controllers\AnalyticsController::class, 'revenue'])
            ->name('revenue');
    });
});
