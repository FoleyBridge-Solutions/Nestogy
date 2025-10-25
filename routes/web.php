<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ⚠️  DO NOT CREATE ANY ROUTES IN THIS FILE! ⚠️
|--------------------------------------------------------------------------
|
| This file is intentionally empty. All routes are now organized by domain.
|
| ALL ROUTES BELONG IN THEIR RESPECTIVE DOMAIN FILES:
| 
| Core routes (auth, dashboard, navigation, users, admin, webhooks):
|   → app/Domains/Core/routes.php
|
| Domain-specific routes:
|   → app/Domains/Client/routes.php (clients, subsidiaries, IT documentation)
|   → app/Domains/Ticket/routes.php (tickets, time tracking)
|   → app/Domains/Product/routes.php (products, bundles, pricing, services)
|   → app/Domains/Email/routes.php (email accounts, inbox, mail queue)
|   → app/Domains/Financial/routes.php (invoices, quotes, payments, contracts)
|   → app/Domains/HR/routes.php (employees, time clock, payroll)
|   → app/Domains/Marketing/routes.php (campaigns, email tracking)
|   → app/Domains/Report/routes.php (reports, analytics)
|   → app/Domains/Asset/routes.php (assets)
|   → app/Domains/Project/routes.php (projects)
|   → app/Domains/Knowledge/routes.php (knowledge base)
|   → app/Domains/Lead/routes.php (leads)
|   → app/Domains/Integration/routes.php (integrations)
|   → app/Domains/PhysicalMail/routes.php (physical mail)
|
| Special route files:
|   → routes/portal.php (client portal)
|   → routes/settings.php (settings pages)
|
| Domain routes are automatically loaded by:
|   app/Domains/Core/Services/DomainRouteManager.php
|
| If you add routes here, they will be duplicated and cause conflicts!
|
*/

// This file is intentionally left empty.
// All routes have been moved to their appropriate domain files.

// Include special route files (these need to be loaded via web.php)
require __DIR__.'/portal.php';
require __DIR__.'/settings.php';
