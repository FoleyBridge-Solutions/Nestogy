<?php
// Marketing and campaign management routes

use Illuminate\Support\Facades\Route;
use App\Domains\Marketing\Controllers\CampaignController;

// Public email tracking routes (no auth required)
Route::get('marketing/email/track-open/{tracking_id}', function($trackingId) {
    app(\App\Domains\Marketing\Services\CampaignEmailService::class)->trackEmailOpen(
        $trackingId,
        request()->header('User-Agent'),
        request()->ip()
    );
    
    // Return 1x1 transparent pixel
    return response()->make(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), 200)
        ->header('Content-Type', 'image/gif');
})->name('marketing.email.track-open');

Route::get('marketing/email/track-click/{tracking_id}', function($trackingId) {
    $url = urldecode(request('url'));
    
    app(\App\Domains\Marketing\Services\CampaignEmailService::class)->trackEmailClick(
        $trackingId,
        $url,
        request()->header('User-Agent'),
        request()->ip()
    );
    
    return redirect($url);
})->name('marketing.email.track-click');

Route::get('marketing/unsubscribe', function() {
    $enrollmentId = request('enrollment');
    $token = request('token');
    
    return app(\App\Domains\Marketing\Services\CampaignEmailService::class)->handleUnsubscribe($enrollmentId, $token);
})->name('marketing.unsubscribe');

// Authenticated marketing routes
Route::middleware(['auth', 'verified'])->prefix('marketing')->name('marketing.')->group(function () {
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
});