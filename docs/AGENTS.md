# Repository Guidelines

## Project Structure & Module Organization
Laravel 12 with domain-driven modules in `app/Domains/*` (Asset, Financial, PhysicalMail, etc.), shared services in `app/Services`, Livewire in `app/Livewire`. Front-end in `resources/{js,css,views}`, compiled to `public/`. Database files in `database/`, ops scripts in `scripts/`.

## Build, Test, and Development Commands
- `composer install && npm install` – install dependencies
- `composer dev` – run PHP server, queue worker, logs, Vite dev
- `php artisan migrate --seed` – run migrations and seeds
- `npm run build` – build production assets
- `composer test` or `php artisan test` – run PHPUnit

## Coding Style & Naming Conventions
PSR-12, 4-space indent, run `./vendor/bin/pint` pre-commit. Livewire: StudlyCase classes (`App\Livewire\<Context>`), kebab-case blades. Use event-oriented names (e.g., `syncMailboxes`), constructor injection over facades. Tailwind utilities per `tailwind.config.js`, no inline styles.

## Testing Guidelines
Tests in `tests/`: `Feature` (domain), `Unit` (logic), `Performance` (regression). Name classes `<Thing>Test` matching subject namespace. Use `php artisan test --filter=Financial`, `tests/run-financial-accuracy-tests.php` for finance. Prefer factories.

**Minimum 75% coverage required.** Use [pvoc](https://github.com/akazwz/pvoc). Run `pvoc` before PRs.

### Test Execution Best Practices
**CRITICAL: Redirect output to files:** `php vendor/bin/phpunit > test-output.txt 2>&1`, `php artisan test > artisan-test.txt 2>&1`, etc.

Benefits:
- Review errors/stack traces
- Analyze failure patterns
- Compare before/after
- Share with team
- Debug intermittent failures

## Commit & Pull Request Guidelines
Imperative commits (e.g., "Add company sizes to seeder"), <72 char subjects, contextual bullets. Reference affected domain. PRs: link issues, note schema/seed changes, include UI screenshots, list post-deploy jobs/config.

## Security & Configuration Tips
Never commit `.env` or credentials. Use `php artisan key:generate` post-clone, vault secrets. Config overrides in `.env` match `config/*.php` keys. Validate uploads, store in `storage/app`, verify S3 credentials before enabling.

## Standardized Page Headers
Use header system in `resources/views/layouts/app.blade.php`. Pass variables to layout, don't create custom headers.

### Required Variables
- `$pageTitle` – heading (string)

### Optional Variables
- `$pageSubtitle` – description (string)
- `$pageActions` – button array: `[['label' => '', 'href' => '', 'icon' => '', 'variant' => '']]`

### Example Usage
```blade
@extends('layouts.app')
@section('title', 'Page Title')
@php
$pageTitle = 'Product Name';
$pageSubtitle = 'SKU: ABC123 • Category: Electronics';
$pageActions = [['label' => 'Edit', 'href' => route('products.edit', $product), 'icon' => 'pencil', 'variant' => 'ghost']];
@endphp
@section('content')
    @livewire('products.product-index')
@endsection
```

**Don't wrap `@section('content')` in spacing divs—layout handles spacing.**

### Action Button Properties
- `label` (required) – text
- `href` (required) – route/URL
- `icon` (optional) – FluxUI name (no `flux:icon.` prefix)
- `variant` (optional) – button style (default: 'ghost')

**Use standardized headers only, no custom headers.**
