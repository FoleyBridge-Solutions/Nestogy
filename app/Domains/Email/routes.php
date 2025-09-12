<?php
// Email routes

use Illuminate\Support\Facades\Route;

// Public OAuth callback route (must be outside auth middleware)
Route::middleware(['web'])->prefix('email')->name('email.')->group(function () {
    Route::get('oauth/callback', [\App\Http\Controllers\Email\OAuthCallbackController::class, 'callback'])->name('oauth.callback');
});

Route::middleware(['web', 'auth', 'verified'])->prefix('email')->name('email.')->group(function () {
        // Account management routes
        Route::resource('accounts', \App\Domains\Email\Controllers\EmailAccountController::class);
        Route::post('accounts/{emailAccount}/test-connection', [\App\Domains\Email\Controllers\EmailAccountController::class, 'testConnection'])->name('accounts.test-connection');
        Route::post('accounts/{emailAccount}/sync', [\App\Domains\Email\Controllers\EmailAccountController::class, 'sync'])->name('accounts.sync');
        Route::post('accounts/{emailAccount}/set-default', [\App\Domains\Email\Controllers\EmailAccountController::class, 'setDefault'])->name('accounts.set-default');
        Route::post('accounts/connect-oauth', [\App\Http\Controllers\Email\EmailAccountController::class, 'connectOAuth'])->name('accounts.connect-oauth');
        Route::post('accounts/{emailAccount}/refresh-tokens', [\App\Http\Controllers\Email\EmailAccountController::class, 'refreshTokens'])->name('accounts.refresh-tokens');
        
        // Inbox management routes
        Route::get('inbox', [\App\Domains\Email\Controllers\InboxController::class, 'index'])->name('inbox.index');
        Route::get('inbox/{message}', [\App\Domains\Email\Controllers\InboxController::class, 'show'])->name('inbox.show');
        Route::post('inbox/mark-read', [\App\Domains\Email\Controllers\InboxController::class, 'markAsRead'])->name('inbox.mark-read');
        Route::post('inbox/mark-unread', [\App\Domains\Email\Controllers\InboxController::class, 'markAsUnread'])->name('inbox.mark-unread');
        Route::post('inbox/flag', [\App\Domains\Email\Controllers\InboxController::class, 'flag'])->name('inbox.flag');
        Route::post('inbox/unflag', [\App\Domains\Email\Controllers\InboxController::class, 'unflag'])->name('inbox.unflag');
        Route::delete('inbox/delete', [\App\Domains\Email\Controllers\InboxController::class, 'delete'])->name('inbox.delete');
        Route::post('inbox/refresh', [\App\Domains\Email\Controllers\InboxController::class, 'refresh'])->name('inbox.refresh');
        Route::get('inbox/stats', [\App\Domains\Email\Controllers\InboxController::class, 'stats'])->name('inbox.stats');
        
        // Compose routes
        Route::get('compose', [\App\Domains\Email\Controllers\ComposeController::class, 'index'])->name('compose.index');
        Route::post('compose', [\App\Domains\Email\Controllers\ComposeController::class, 'store'])->name('compose.store');
        Route::get('compose/reply/{message}', [\App\Domains\Email\Controllers\ComposeController::class, 'reply'])->name('compose.reply');
        Route::get('compose/reply-all/{message}', [\App\Domains\Email\Controllers\ComposeController::class, 'replyAll'])->name('compose.reply-all');
        Route::get('compose/forward/{message}', [\App\Domains\Email\Controllers\ComposeController::class, 'forward'])->name('compose.forward');
        Route::post('compose/send-reply/{message}', [\App\Domains\Email\Controllers\ComposeController::class, 'sendReply'])->name('compose.send-reply');
        Route::post('compose/send-forward/{message}', [\App\Domains\Email\Controllers\ComposeController::class, 'sendForward'])->name('compose.send-forward');
        Route::get('compose/load-draft/{draft}', [\App\Domains\Email\Controllers\ComposeController::class, 'loadDraft'])->name('compose.load-draft');
        
        // Attachment routes
        Route::get('attachments/{attachment}/download', [\App\Domains\Email\Controllers\AttachmentController::class, 'download'])->name('attachments.download');
        Route::get('attachments/{attachment}/thumbnail', [\App\Domains\Email\Controllers\AttachmentController::class, 'thumbnail'])->name('attachments.thumbnail');
        Route::get('attachments/{attachment}/preview', [\App\Domains\Email\Controllers\AttachmentController::class, 'preview'])->name('attachments.preview');
        
        // Signature management routes
        Route::resource('signatures', \App\Domains\Email\Controllers\SignatureController::class);
        Route::post('signatures/{signature}/set-default', [\App\Domains\Email\Controllers\SignatureController::class, 'setDefault'])->name('signatures.set-default');
    });