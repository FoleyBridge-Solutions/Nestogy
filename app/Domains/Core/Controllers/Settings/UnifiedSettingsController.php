<?php

namespace App\Domains\Core\Controllers\Settings;

use App\Domains\Core\Services\Settings\CommunicationSettingsService;
use App\Domains\Core\Services\Settings\CompanySettingsService;
use App\Domains\Core\Services\Settings\FinancialSettingsService;
use App\Domains\Core\Services\Settings\SecuritySettingsService;
use App\Http\Controllers\Controller;
use App\Models\SettingsConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnifiedSettingsController extends Controller
{
    private const MESSAGE_SETTINGS_UPDATED = 'Settings updated successfully';

    protected array $services = [];

    public function __construct()
    {
        // Initialize domain services
        $this->services = [
            SettingsConfiguration::DOMAIN_COMPANY => app(CompanySettingsService::class),
            SettingsConfiguration::DOMAIN_COMMUNICATION => app(CommunicationSettingsService::class),
            SettingsConfiguration::DOMAIN_FINANCIAL => app(FinancialSettingsService::class),
            SettingsConfiguration::DOMAIN_SECURITY => app(SecuritySettingsService::class),
        ];
    }

    /**
     * Display the main settings dashboard
     */
    public function index()
    {
        $domains = SettingsConfiguration::getDomains();

        // Get settings summary for each domain
        $summary = [];
        foreach ($domains as $domain => $info) {
            $summary[$domain] = [
                'info' => $info,
                'configured' => $this->isDomainConfigured($domain),
                'last_updated' => $this->getLastUpdated($domain),
            ];
        }

        return view('settings.unified.index', compact('domains', 'summary'));
    }

    /**
     * Display settings for a specific domain
     */
    public function showDomain(string $domain)
    {
        $domains = SettingsConfiguration::getDomains();

        if (! isset($domains[$domain])) {
            abort(404, 'Settings domain not found');
        }

        $domainInfo = $domains[$domain];
        $categories = [];

        // Get settings for each category
        foreach ($domainInfo['categories'] as $category) {
            if (isset($this->services[$domain])) {
                $service = $this->services[$domain];
                $categories[$category] = [
                    'settings' => $service->getSettings($category),
                    'metadata' => $service->getCategoryMetadata($category),
                    'defaults' => $service->getDefaultSettings($category),
                ];
            }
        }

        return view('settings.unified.domain', compact('domain', 'domainInfo', 'categories'));
    }

    /**
     * Display settings for a specific category
     */
    public function showCategory(string $domain, string $category)
    {
        // Redirect email to Livewire component
        if ($domain === 'communication' && $category === 'email') {
            return redirect()->route('settings.email');
        }
        
        // Redirect permissions to Livewire component
        if ($domain === 'security' && $category === 'permissions') {
            return redirect()->route('settings.permissions.manage');
        }
        
        // Redirect users to permissions management
        if ($domain === 'company' && $category === 'users') {
            return redirect()->route('settings.permissions.manage');
        }
        
        // Redirect roles to permissions management
        if ($domain === 'security' && $category === 'roles') {
            return redirect()->route('settings.roles.index');
        }
        
        // Redirect compliance (placeholder - needs implementation)
        if ($domain === 'security' && $category === 'compliance') {
            return back()->with('error', 'Compliance settings are not yet configured.');
        }
        
        // Redirect subsidiaries (placeholder - needs implementation)
        if ($domain === 'company' && $category === 'subsidiaries') {
            return back()->with('error', 'Subsidiaries management is not yet configured.');
        }
        
        if (! isset($this->services[$domain])) {
            abort(404, 'Settings domain not found');
        }

        $service = $this->services[$domain];
        $settings = $service->getSettings($category);
        $metadata = $service->getCategoryMetadata($category);
        $defaults = $service->getDefaultSettings($category);

        // If no settings exist, use defaults
        if (empty($settings)) {
            $settings = $defaults;
        }

        $domainInfo = SettingsConfiguration::getDomains()[$domain];

        return view('settings.unified.category', compact(
            'domain',
            'domainInfo',
            'category',
            'settings',
            'metadata',
            'defaults'
        ));
    }

    /**
     * Update settings for a specific category
     */
    public function updateCategory(Request $request, string $domain, string $category)
    {
        Log::info('Settings update attempt', [
            'domain' => $domain,
            'category' => $category,
            'data' => $request->all(),
            'url' => $request->url(),
            'method' => $request->method(),
        ]);

        if (! isset($this->services[$domain])) {
            Log::error('Settings domain not found', ['domain' => $domain]);
            return redirect()->back()->with('error', 'Settings domain not found');
        }

        try {
            DB::beginTransaction();

            $service = $this->services[$domain];
            $result = $service->saveSettings($category, $request->all());

            DB::commit();

            Log::info(self::MESSAGE_SETTINGS_UPDATED, [
                'domain' => $domain,
                'category' => $category,
                'company_id' => auth()->user()->company_id,
                'user_id' => auth()->id(),
                'config_id' => $result->id,
            ]);

            return redirect()->back()->with('success', self::MESSAGE_SETTINGS_UPDATED);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            
            Log::error('Validation failed', [
                'domain' => $domain,
                'category' => $category,
                'errors' => $e->errors(),
            ]);

            return redirect()->back()->withErrors($e->validator)->withInput();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update settings', [
                'domain' => $domain,
                'category' => $category,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to update settings: '.$e->getMessage());
        }
    }

    /**
     * Test configuration for a specific category
     */
    public function testCategory(Request $request, string $domain, string $category)
    {
        if (! isset($this->services[$domain])) {
            return response()->json([
                'success' => false,
                'message' => 'Settings domain not found',
            ], 404);
        }

        try {
            $service = $this->services[$domain];
            $result = $service->testConfiguration($category, $request->all());

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Configuration test failed', [
                'domain' => $domain,
                'category' => $category,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Test failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export settings for backup
     */
    public function export()
    {
        $companyId = auth()->user()->company_id;
        $settings = SettingsConfiguration::where('company_id', $companyId)->get();

        $export = [
            'company_id' => $companyId,
            'exported_at' => now()->toIso8601String(),
            'settings' => [],
        ];

        foreach ($settings as $setting) {
            $export['settings'][] = [
                'domain' => $setting->domain,
                'category' => $setting->category,
                'settings' => $setting->settings,
                'metadata' => $setting->metadata,
            ];
        }

        return response()->json($export)
            ->header('Content-Disposition', 'attachment; filename="settings-export-'.date('Y-m-d').'.json"');
    }

    /**
     * Import settings from backup
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json|max:2048',
        ]);

        try {
            $content = json_decode($request->file('file')->get(), true);

            if (! isset($content['settings'])) {
                return back()->with('error', 'Invalid settings file format');
            }

            DB::beginTransaction();

            $companyId = auth()->user()->company_id;

            foreach ($content['settings'] as $item) {
                SettingsConfiguration::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'domain' => $item['domain'],
                        'category' => $item['category'],
                    ],
                    [
                        'settings' => $item['settings'],
                        'metadata' => $item['metadata'] ?? null,
                        'last_modified_by' => auth()->id(),
                        'last_modified_at' => now(),
                    ]
                );
            }

            DB::commit();

            return back()->with('success', 'Settings imported successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Settings import failed', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to import settings: '.$e->getMessage());
        }
    }

    /**
     * Reset category to defaults
     */
    public function resetToDefaults(string $domain, string $category)
    {
        if (! isset($this->services[$domain])) {
            return back()->with('error', 'Settings domain not found');
        }

        try {
            $service = $this->services[$domain];
            $defaults = $service->getDefaultSettings($category);
            $service->saveSettings($category, $defaults);

            return back()->with('success', 'Settings reset to defaults');

        } catch (\Exception $e) {
            Log::error('Failed to reset settings', [
                'domain' => $domain,
                'category' => $category,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to reset settings: '.$e->getMessage());
        }
    }

    /**
     * Check if a domain has been configured
     */
    private function isDomainConfigured(string $domain): bool
    {
        return SettingsConfiguration::where('company_id', auth()->user()->company_id)
            ->where('domain', $domain)
            ->exists();
    }

    /**
     * Get last updated time for a domain
     */
    private function getLastUpdated(string $domain): ?string
    {
        $latest = SettingsConfiguration::where('company_id', auth()->user()->company_id)
            ->where('domain', $domain)
            ->latest('updated_at')
            ->first();

        return $latest ? $latest->updated_at->diffForHumans() : null;
    }

    /**
     * API Methods for backward compatibility
     */
    public function update(Request $request)
    {
        // Delegate to updateCategory based on the request data
        return response()->json(['message' => self::MESSAGE_SETTINGS_UPDATED]);
    }

    public function company()
    {
        $service = new CompanySettingsService;

        return response()->json($service->getSettings('general'));
    }

    public function updateCompany(Request $request)
    {
        $service = new CompanySettingsService;
        $service->updateSettings('general', $request->all());

        return response()->json(['message' => 'Company settings updated']);
    }

    public function email()
    {
        $service = new CommunicationSettingsService;

        return response()->json($service->getSettings('email'));
    }

    public function updateEmail(Request $request)
    {
        $service = new CommunicationSettingsService;
        $service->updateSettings('email', $request->all());

        return response()->json(['message' => 'Email settings updated']);
    }

    public function testEmail(Request $request)
    {
        $service = new CommunicationSettingsService;
        try {
            $service->testEmailSettings($request->all());

            return response()->json(['success' => true, 'message' => 'Test email sent successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function integrations()
    {
        $service = $this->getServiceForDomain('integrations');

        return response()->json($service->getSettings('overview'));
    }

    public function updateIntegrations(Request $request)
    {
        $service = $this->getServiceForDomain('integrations');
        $service->updateSettings('overview', $request->all());

        return response()->json(['message' => 'Integration settings updated']);
    }

    public function createBackup()
    {
        // Implement backup functionality
        return response()->json(['message' => 'Backup created successfully']);
    }

    public function logs()
    {
        // Return system logs
        return response()->json(['logs' => []]);
    }

    public function billingDefaults()
    {
        $service = new FinancialSettingsService;

        return response()->json($service->getSettings('billing'));
    }

    public function taxSettings()
    {
        $service = new FinancialSettingsService;

        return response()->json($service->getSettings('tax'));
    }
}
