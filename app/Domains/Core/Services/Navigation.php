<?php

namespace App\Domains\Core\Services;

use App\Domains\Core\Services\Navigation\NavigationContext;

class Navigation
{
    public static function getCurrentDomain(): ?string
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
}
