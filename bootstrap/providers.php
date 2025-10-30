<?php

return [
    App\Providers\ExceptionHandlerServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\RouteServiceProvider::class,
    App\Providers\FortifyServiceProvider::class,
    App\Providers\EmailServiceProvider::class,
    App\Providers\PdfServiceProvider::class,
    App\Providers\RmmServiceProvider::class,
    App\Providers\ContractPluginServiceProvider::class,
    App\Providers\NavigationServiceProvider::class,
    App\Providers\SidebarServiceProvider::class,
    App\Providers\PhysicalMailServiceProvider::class,
    App\Providers\Smtp2goServiceProvider::class,
    Flux\FluxServiceProvider::class,
    FluxPro\FluxProServiceProvider::class,
    EragLaravelPwa\EragLaravelPwaServiceProvider::class,
];
