# Repository Guidelines

## Project Structure & Module Organization
Nestogy runs on Laravel 12 with domain-driven modules under `app/Domains/*`, keeping bounded contexts such as Asset, Financial, and PhysicalMail isolated. Shared services live in `app/Services`, with Livewire UI components in `app/Livewire`. Front-end assets are in `resources/js`, `resources/css`, and `resources/views`; compiled assets publish to `public/`. Database migrations, seeders, and factories are in `database/`, and custom operations scripts (for seeding, data fixes, etc.) belong in `scripts/` alongside artisan commands in `artisan`.

## Build, Test, and Development Commands
- `composer install && npm install` – bootstrap PHP and front-end dependencies.
- `composer dev` – launch the full local stack: PHP server, queue worker, log tail, and Vite dev server.
- `php artisan migrate --seed` – apply schema changes and load baseline fixtures.
- `npm run build` – generate production-ready assets via Vite.
- `composer test` or `php artisan test` – execute the PHPUnit suite with Laravel bootstrap.

## Coding Style & Naming Conventions
Follow PSR-12 with four-space indentation for PHP; run `./vendor/bin/pint` before committing. Livewire components use StudlyCase classes in `App\Livewire\<Context>` and kebab-case blade filenames. Favor event-oriented method names (e.g., `syncMailboxes`) and constructor injection over facades. Tailwind utility ordering follows the defaults in `tailwind.config.js`; avoid inline styles.

## Testing Guidelines
All automated tests live in `tests/`: domain behaviors in `Feature`, pure logic in `Unit`, and regression checks in `Performance`. Name PHPUnit classes with the `<Thing>Test` suffix that mirrors the subject namespace. Use `php artisan test --filter=Financial` for focused runs, and the helper script `tests/run-financial-accuracy-tests.php` for finance regressions. Favor factories over hand-built fixtures.

**All new code must achieve at least 75% test coverage.** We use [pvoc](https://github.com/akazwz/pvoc) to measure coverage. Run `pvoc` to generate a full coverage report before submitting pull requests.

### Test Execution Best Practices
**CRITICAL: Always capture test output to files for analysis.** When running tests, use output redirection to save results:

```bash
# For full test suite
php vendor/bin/phpunit > test-output.txt 2>&1

# For specific test files
php vendor/bin/phpunit tests/Feature/SomeTest.php > specific-test.txt 2>&1

# For artisan test command
php artisan test > artisan-test.txt 2>&1

# For custom test runner
php run-tests.php --coverage > run-tests-output.txt 2>&1
```

This allows you to:
- Review complete error messages and stack traces
- Analyze failure patterns across multiple test runs
- Compare before/after results when fixing issues
- Share detailed output with team members
- Debug intermittent failures

## Commit & Pull Request Guidelines
Commits are imperative and scoped (e.g., "Add varying company sizes to dev seeder"); keep subjects under 72 characters and follow with contextual body bullets when needed. Reference the affected domain in either the subject or first body line so reviewers can route expertise quickly. Pull requests should link tracking issues, note schema changes and seed impacts, include screenshots for UI tweaks, and list any queued background jobs or config toggles required post-deploy.

## Security & Configuration Tips
Do not commit `.env` files or production credentials; use `php artisan key:generate` after cloning and document secrets in your team vault. Configuration overrides belong in `.env` with matching keys already defined in `config/*.php`. Sanitize uploaded assets with the existing validation helpers, store files under `storage/app`, and confirm S3 credentials locally before enabling `league/flysystem-aws-s3-v3` drivers.

## Standardized Page Headers
All pages should use the standardized header system built into `resources/views/layouts/app.blade.php`. Instead of creating custom headers in views or Livewire components, pass these variables to the layout:

### Required Variables
- `$pageTitle` – Main heading text (string)

### Optional Variables
- `$pageSubtitle` – Subheading/description text (string)
- `$pageActions` – Array of action buttons with structure:
  ```php
  [
      ['label' => 'Edit', 'href' => route('...'), 'icon' => 'pencil', 'variant' => 'ghost'],
      ['label' => 'Back', 'href' => route('...'), 'icon' => 'arrow-left', 'variant' => 'ghost']
  ]
  ```

### Example Usage
```blade
@extends('layouts.app')

@section('title', 'Page Title')

@php
$pageTitle = 'Product Name';
$pageSubtitle = 'SKU: ABC123 • Category: Electronics';
$pageActions = [
    ['label' => 'Edit', 'href' => route('products.edit', $product), 'icon' => 'pencil', 'variant' => 'ghost'],
    ['label' => 'Back', 'href' => route('products.index'), 'icon' => 'arrow-left', 'variant' => 'ghost']
];
@endphp

@section('content')
    <!-- Your content here -->
@endsection
```

### Action Button Properties
- `label` (required) – Button text
- `href` (required) – Route/URL
- `icon` (optional) – FluxUI icon name (without `flux:icon.` prefix)
- `variant` (optional) – FluxUI button variant (default: 'ghost')

**Do not create custom headers in individual views or Livewire components.** Use this standardized system for consistency across the application.
