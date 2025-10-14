<?php

namespace App\Providers;

use App\Contracts\Services\PdfServiceInterface;
use App\Domains\Core\Services\PdfService;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Support\ServiceProvider;
use Spatie\LaravelPdf\Facades\Pdf as SpatiePdf;

class PdfServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register PDF Service
        $this->app->singleton(PdfServiceInterface::class, function ($app) {
            return new PdfService;
        });

        // Also bind the concrete class for backward compatibility
        $this->app->singleton(PdfService::class, function ($app) {
            return $app[PdfServiceInterface::class];
        });

        // Register PDF generators
        $this->app->bind('pdf.dompdf', function ($app) {
            return DomPDF::getFacadeRoot();
        });

        $this->app->bind('pdf.spatie', function ($app) {
            return SpatiePdf::getFacadeRoot();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration files
        $this->publishes([
            __DIR__.'/../../config/pdf.php' => config_path('pdf.php'),
        ], 'nestogy-pdf-config');

        // Configure DomPDF settings
        $this->configureDomPdf();

        // Register PDF view composers
        $this->registerViewComposers();
    }

    /**
     * Configure DomPDF settings
     */
    protected function configureDomPdf(): void
    {
        if (config('pdf.default') === 'dompdf') {
            $options = config('pdf.drivers.dompdf.options', []);

            // Set DomPDF options
            foreach ($options['defines'] ?? [] as $key => $value) {
                if (! defined($key)) {
                    define($key, $value);
                }
            }
        }
    }

    /**
     * Register view composers for PDF templates
     */
    protected function registerViewComposers(): void
    {
        // Register common PDF data
        view()->composer('pdf.*', function ($view) {
            // Only set defaults if not already provided by controller
            $data = [];
            
            if (! isset($view->getData()['company'])) {
                $data['company'] = [
                    'name' => config('nestogy.company.name', 'Nestogy ERP'),
                    'address' => config('nestogy.company.address', ''),
                    'phone' => config('nestogy.company.phone', ''),
                    'email' => config('nestogy.company.email', ''),
                    'website' => config('nestogy.company.website', ''),
                    'logo' => config('nestogy.company.logo', ''),
                ];
            }
            
            if (! isset($view->getData()['generated_at'])) {
                $data['generated_at'] = now();
            }
            
            if (! isset($view->getData()['generated_by'])) {
                $data['generated_by'] = auth()->user()->name ?? 'System';
            }
            
            if (! empty($data)) {
                $view->with($data);
            }
        });

        // Register invoice-specific data
        view()->composer('pdf.invoice', function ($view) {
            $view->with([
                'currency' => config('nestogy.currency.symbol', '$'),
                'tax_rate' => config('nestogy.tax.default_rate', 0),
            ]);
        });

        // Register report-specific data
        view()->composer('pdf.report', function ($view) {
            $view->with([
                'report_period' => request('period', 'monthly'),
                'date_format' => config('nestogy.date_format', 'Y-m-d'),
            ]);
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            PdfServiceInterface::class,
            PdfService::class,
            'pdf.dompdf',
            'pdf.spatie',
        ];
    }
}
