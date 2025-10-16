<?php

namespace App\Domains\Core\Services\Navigation;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class NavigationContext
{
    protected static array $domainMappings = [
        'clients' => ['clients.*'],
        'tickets' => ['tickets.*'],
        'assets' => ['assets.*'],
        'projects' => ['projects.*'],
        'financial' => ['financial.*', 'billing.*', 'products.*', 'services.*', 'bundles.*'],
        'reports' => ['reports.*'],
        'settings' => ['settings.*', 'users.*', 'admin.*'],
        'email' => ['email.*'],
        'physical-mail' => ['mail.*', 'physical-mail.*'],
        'manager' => ['manager.*'],
    ];

    public static function getCurrentDomain(): ?string
    {
        $route = Route::currentRouteName();
        
        if (!$route) {
            return null;
        }

        if (static::shouldHideSidebar($route)) {
            return null;
        }

        if (static::hasSelectedClient()) {
            return 'clients';
        }

        foreach (static::$domainMappings as $domain => $patterns) {
            foreach ($patterns as $pattern) {
                if (Str::is($pattern, $route)) {
                    return $domain;
                }
            }
        }

        return null;
    }

    protected static function shouldHideSidebar(string $route): bool
    {
        $alwaysHidden = [
            'clients.create',
            'clients.store',
        ];
        
        if (in_array($route, $alwaysHidden)) {
            return true;
        }
        
        $hiddenWithoutClient = [
            'clients.index',
            'tickets.index',
            'assets.index',
            'projects.index',
        ];
        
        if (in_array($route, $hiddenWithoutClient) && !static::hasSelectedClient()) {
            return true;
        }

        return false;
    }

    public static function getSelectedClient(): ?\App\Domains\Client\Models\Client
    {
        $clientId = Session::get('selected_client_id');
        
        if (!$clientId) {
            return null;
        }

        try {
            return \App\Domains\Client\Models\Client::find($clientId);
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function setSelectedClient(int $clientId): void
    {
        Session::put('selected_client_id', $clientId);
    }

    public static function clearSelectedClient(): void
    {
        Session::forget('selected_client_id');
    }

    public static function hasSelectedClient(): bool
    {
        return Session::has('selected_client_id');
    }
}
