<?php

namespace App\Domains\Core\Services;

use App\Domains\Core\Services\Navigation\NavigationContext;

class NavigationService
{
    public static function getCurrentDomain(): ?string
    {
        return NavigationContext::getCurrentDomain();
    }

    public static function getActiveDomain(): ?string
    {
        return NavigationContext::getCurrentDomain();
    }

    public static function getSidebarContext(): ?string
    {
        return NavigationContext::getCurrentDomain();
    }

    public static function getSelectedClient()
    {
        return NavigationContext::getSelectedClient();
    }

    public static function setSelectedClient(int $clientId): void
    {
        NavigationContext::setSelectedClient($clientId);
    }

    public static function clearSelectedClient(): void
    {
        NavigationContext::clearSelectedClient();
    }

    public static function hasSelectedClient(): bool
    {
        return NavigationContext::hasSelectedClient();
    }

    public static function getBreadcrumbs(): array
    {
        return [];
    }

    public static function getActiveNavigationItem(): ?string
    {
        return null;
    }
}
