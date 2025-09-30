<?php

// Project routes

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::resource('projects', \App\Domains\Project\Controllers\ProjectController::class)->except(['show']);
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('{project}', [\App\Domains\Project\Controllers\ProjectController::class, 'show'])->name('show');
        Route::resource('{project}/tasks', \App\Domains\Project\Controllers\TaskController::class);
    });
});
