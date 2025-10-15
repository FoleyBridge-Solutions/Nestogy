<?php

namespace App\Providers;

use App\Domains\Core\Services\Navigation\NavigationRegistry;
use Illuminate\Support\ServiceProvider;

class NavigationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        NavigationRegistry::boot();
    }
}
