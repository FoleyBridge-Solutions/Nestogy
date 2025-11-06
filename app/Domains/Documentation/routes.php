<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Documentation Domain Routes
|--------------------------------------------------------------------------
|
| Public documentation routes - NO authentication required.
| Accessible to all users, prospects, trial accounts, and visitors.
|
| URL Structure:
| - /docs                  → Documentation home page
| - /docs/{page}           → Individual documentation pages
|
*/

use App\Livewire\Documentation\DocumentationIndex;
use App\Livewire\Documentation\DocumentationShow;

Route::middleware('web')->prefix('docs')->name('docs.')->group(function () {
    
    // Documentation home page - full Livewire component
    Route::get('/', DocumentationIndex::class)->name('index');
    
    // Individual documentation pages - full Livewire component
    Route::get('/{page}', DocumentationShow::class)
        ->name('show')
        ->where('page', '[a-z0-9-]+');
    
});
