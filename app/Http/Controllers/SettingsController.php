<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingsUpdateRequest;
use App\Http\Requests\SecuritySettingsRequest;
use App\Http\Requests\EmailSettingsRequest;
use App\Http\Requests\IntegrationsSettingsRequest;
use App\Http\Requests\GeneralSettingsRequest;
use App\Http\Requests\BillingFinancialSettingsRequest;
use App\Http\Requests\RmmMonitoringSettingsRequest;
use App\Http\Requests\TicketingServiceDeskSettingsRequest;
use App\Http\Requests\ComplianceAuditSettingsRequest;
use App\Http\Requests\UserManagementSettingsRequest;
use App\Services\SettingsService;
use App\Services\EmailConnectionTestService;
use App\Services\DynamicMailConfigService;
use App\Domains\Ticket\Services\SLAService;
use App\Domains\Email\Services\EmailProviderService;
use App\Models\Client;
use App\Models\Company;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    protected SettingsService $settingsService;
    protected EmailConnectionTestService $emailTestService;
    protected DynamicMailConfigService $mailConfigService;
    protected SLAService $slaService;
    protected EmailProviderService $emailProviderService;

    public function __construct(
        SettingsService $settingsService,
        EmailConnectionTestService $emailTestService,
        DynamicMailConfigService $mailConfigService,
        SLAService $slaService,
        EmailProviderService $emailProviderService
    ) {
        $this->settingsService = $settingsService;
        $this->emailTestService = $emailTestService;
        $this->mailConfigService = $mailConfigService;
        $this->slaService = $slaService;
        $this->emailProviderService = $emailProviderService;
    }
    
    /**
     * Display the main settings page
     */
    public function index()
    {
        return $this->general();
    }
    
    /**
     * Display general settings
     */
    public function general()
    {
        $company = Auth::user()->company;
        $settings = $this->settingsService->getComprehensiveSettings($company);
        
        $timezones = SettingsService::getTimezones();
        $dateFormats = SettingsService::getDateFormats();
        $currencies = SettingsService::getCurrencies();
        $companyColors = $this->settingsService->getCompanyColors($company);
        $colorPresets = $this->settingsService->getColorPresets();
        
        return view('settings.general', compact('company', 'settings', 'timezones', 'dateFormats', 'currencies', 'companyColors', 'colorPresets'));
    }
    
    /**
     * Get or create company settings
     */
    private function getOrCreateSettings(Company $company): Setting
    {
        $setting = $company->setting;

        if (!$setting) {
            $setting = Setting::create([
                'company_id' => $company->id,
                'current_database_version' => '1.0.0',
                'start_page' => 'clients.php',
                'default_net_terms' => 30,
                'default_hourly_rate' => 0.00,
                'invoice_next_number' => 1,
                'quote_next_number' => 1,
                'ticket_next_number' => 1,
                'theme' => 'blue',
                'timezone' => 'America/New_York',
            ]);
        }

        return $setting;
    }

    /**
     * Get provider-specific settings for the company
     */
    private function getProviderSpecificSettings(Company $company, string $providerType): array
    {
        $settings = [
            'is_oauth_provider' => in_array($providerType, ['microsoft365', 'google_workspace']),
            'can_use_oauth' => false,
            'oauth_configured' => false,
            'recommended_settings' => [],
            'setup_instructions' => null,
        ];

        if ($settings['is_oauth_provider']) {
            $config = $company->email_provider_config ?? [];
            $settings['can_use_oauth'] = !empty($config['client_id']) && !empty($config['client_secret']);
            $settings['oauth_configured'] = $settings['can_use_oauth'];

            // Get recommended SMTP/IMAP settings for OAuth providers
            $settings['recommended_settings'] = $this->getRecommendedProviderSettings($providerType);

            // Get setup instructions
            $settings['setup_instructions'] = $this->getProviderSetupInstructions($providerType);
        }

        return $settings;
    }

    /**
     * Get recommended SMTP/IMAP settings for OAuth providers
     */
    private function getRecommendedProviderSettings(string $providerType): array
    {
        $settings = [];

        if ($providerType === 'microsoft365') {
            $settings = [
                'smtp' => [
                    'host' => 'smtp-mail.outlook.com',
                    'port' => 587,
                    'encryption' => 'tls',
                    'auth_mode' => 'oauth'
                ],
                'imap' => [
                    'host' => 'outlook.office365.com',
                    'port' => 993,
                    'encryption' => 'tls',
                    'auth_mode' => 'oauth'
                ]
            ];
        } elseif ($providerType === 'google_workspace') {
            $settings = [
                'smtp' => [
                    'host' => 'smtp.gmail.com',
                    'port' => 587,
                    'encryption' => 'tls',
                    'auth_mode' => 'oauth'
                ],
                'imap' => [
                    'host' => 'imap.gmail.com',
                    'port' => 993,
                    'encryption' => 'tls',
                    'auth_mode' => 'oauth'
                ]
            ];
        }

        return $settings;
    }

    /**
     * Get setup instructions for OAuth providers
     */
    private function getProviderSetupInstructions(string $providerType): ?string
    {
        if ($providerType === 'microsoft365') {
            return 'Configure Microsoft 365 OAuth in Company Email Provider settings first, then use OAuth authentication here.';
        } elseif ($providerType === 'google_workspace') {
            return 'Configure Google Workspace OAuth in Company Email Provider settings first, then use OAuth authentication here.';
        }

        return null;
    }

    /**
     * Update general settings
     */
    public function updateGeneral(GeneralSettingsRequest $request)
    {
        $company = Auth::user()->company;
        $setting = $this->getOrCreateSettings($company);
        
        // Debug logging
        Log::info('Updating general settings', [
            'company_id' => $company->id,
            'setting_id' => $setting->id,
            'request_data' => $request->validated()
        ]);
        
        $success = $this->settingsService->updateGeneralSettings($setting, $request->validated());
        
        if ($success) {
            Log::info('General settings updated successfully', [
                'company_id' => $company->id
            ]);
            return redirect()->route('settings.general')
                ->with('success', 'General settings updated successfully.');
        }
        
        Log::warning('Failed to update general settings', [
            'company_id' => $company->id
        ]);
        return redirect()->route('settings.general')
            ->with('error', 'Failed to update general settings.');
    }
    
    /**
     * Update the settings (legacy method)
     */
    public function update(SettingsUpdateRequest $request)
    {
        $company = Auth::user()->company;
        
        if (!$company) {
            return redirect()->route('settings.general')
                ->with('error', 'Company not found.');
        }
        
        $success = $this->settingsService->updateSettings($company, $request->validated());
        
        if ($success) {
            return redirect()->route('settings.general')
                ->with('success', 'Settings updated successfully.');
        }
        
        return redirect()->route('settings.general')
            ->with('error', 'Failed to update settings. Please try again.');
    }
    
    
    /**
     * Display security settings
     */
    public function security()
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        
        // Navigation context for sidebar
        $activeDomain = 'settings';
        $activeItem = 'security';
        
        return view('settings.security', compact('company', 'setting', 'activeDomain', 'activeItem'));
    }
    
    /**
     * Update security settings
     */
    public function updateSecurity(SecuritySettingsRequest $request)
    {
        $company = Auth::user()->company;
        $setting = $this->getOrCreateSettings($company);
        
        $success = $this->settingsService->updateSecuritySettings($setting, $request->validated());
        
        if ($success) {
            return redirect()->route('settings.security')
                ->with('success', 'Security settings updated successfully.');
        }
        
        return redirect()->route('settings.security')
            ->with('error', 'Failed to update security settings.');
    }
    
    /**
     * Display email settings
     */
    public function email()
    {
        $company = Auth::user()->company;
        $setting = $company->setting;

        // Get email provider information
        $availableProviders = EmailProviderService::getAvailableProviders();
        $currentProvider = $company->email_provider_type ?? 'manual';
        $providerConfig = $company->email_provider_config ?? [];

        // Get provider-specific settings
        $providerSettings = $this->getProviderSpecificSettings($company, $currentProvider);

        return view('settings.email', compact(
            'company',
            'setting',
            'availableProviders',
            'currentProvider',
            'providerConfig',
            'providerSettings'
        ));
    }
    
    /**
     * Update email settings
     */
    public function updateEmail(EmailSettingsRequest $request)
    {
        $company = Auth::user()->company;
        $setting = $this->getOrCreateSettings($company);
        
        $success = $this->settingsService->updateEmailSettings($setting, $request->validated());
        
        if ($success) {
            return redirect()->route('settings.email')
                ->with('success', 'Email settings updated successfully.');
        }
        
        return redirect()->route('settings.email')
            ->with('error', 'Failed to update email settings.');
    }
    
    /**
     * Test email connection (AJAX endpoint)
     */
    public function testEmailConnection(Request $request)
    {
        $company = Auth::user()->company;
        
        // Rate limiting: 5 attempts per minute per company
        $key = 'email_test:' . $company->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many test attempts. Please wait before trying again.',
                'error_type' => 'rate_limit'
            ], 429);
        }
        
        RateLimiter::hit($key, 60);
        
        // Validate input
        $validator = Validator::make($request->all(), [
            'smtp_host' => 'required|string|max:255',
            'smtp_port' => 'required|integer|min:1|max:65535',
            'smtp_encryption' => 'nullable|in:tls,ssl',
            'smtp_auth_method' => 'nullable|in:password,oauth',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'mail_from_email' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
            'test_email_address' => 'nullable|email|max:255',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email settings provided',
                'errors' => $validator->errors(),
                'error_type' => 'validation'
            ], 422);
        }
        
        try {
            // Prepare settings for testing
            $settings = $request->only([
                'smtp_host', 'smtp_port', 'smtp_encryption',
                'smtp_auth_method', 'smtp_username', 'smtp_password',
                'mail_from_email', 'mail_from_name'
            ]);

            // Handle OAuth authentication
            if (($settings['smtp_auth_method'] ?? 'password') === 'oauth') {
                $company = Auth::user()->company;

                // Check if OAuth is configured for the company
                if ($company->email_provider_type === 'microsoft365' || $company->email_provider_type === 'google_workspace') {
                    $config = $company->email_provider_config ?? [];

                    if (empty($config['client_id']) || empty($config['client_secret'])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'OAuth is not properly configured. Please configure your email provider settings first.',
                            'error_type' => 'oauth_not_configured'
                        ], 400);
                    }

                    // For OAuth testing, we'll use a placeholder - actual OAuth token would be obtained during real usage
                    $settings['smtp_username'] = $request->mail_from_email ?? $settings['smtp_username'];
                    $settings['smtp_password'] = 'oauth_placeholder'; // This would be replaced with actual OAuth token
                    $settings['auth_mode'] = 'oauth';
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'OAuth authentication is selected but no OAuth provider is configured.',
                        'error_type' => 'no_oauth_provider'
                    ], 400);
                }
            }
            
            // If password is empty, try to use saved password
            if (empty($settings['smtp_password'])) {
                $savedSetting = $company->setting;
                if ($savedSetting && !empty($savedSetting->smtp_password)) {
                    try {
                        $settings['smtp_password'] = decrypt($savedSetting->smtp_password);
                    } catch (\Exception $e) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Unable to use saved password. Please enter your password again.',
                            'error_type' => 'password_decrypt_error'
                        ], 400);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'SMTP password is required. No saved password found.',
                        'error_type' => 'missing_password'
                    ], 400);
                }
            }
            
            // Test the connection
            $result = $this->emailTestService->testConnection(
                $settings, 
                $request->test_email_address
            );
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            \Log::error('Email connection test failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred during testing',
                'error_type' => 'exception'
            ], 500);
        }
    }
    
    /**
     * Get email provider presets
     */
    public function getEmailProviderPresets()
    {
        try {
            $company = Auth::user()->company;
            $presets = $this->emailTestService->getCommonProviderPresets();

            // Add OAuth provider presets if configured
            if ($company->email_provider_type === 'microsoft365') {
                $presets['microsoft365_oauth'] = [
                    'name' => 'Microsoft 365 (OAuth)',
                    'smtp_host' => 'smtp-mail.outlook.com',
                    'smtp_port' => 587,
                    'smtp_encryption' => 'tls',
                    'imap_host' => 'outlook.office365.com',
                    'imap_port' => 993,
                    'imap_encryption' => 'tls',
                    'auth_mode' => 'oauth',
                    'oauth_provider' => 'microsoft365',
                    'instructions' => 'Uses OAuth authentication with your Microsoft 365 account.'
                ];
            } elseif ($company->email_provider_type === 'google_workspace') {
                $presets['google_workspace_oauth'] = [
                    'name' => 'Google Workspace (OAuth)',
                    'smtp_host' => 'smtp.gmail.com',
                    'smtp_port' => 587,
                    'smtp_encryption' => 'tls',
                    'imap_host' => 'imap.gmail.com',
                    'imap_port' => 993,
                    'imap_encryption' => 'tls',
                    'auth_mode' => 'oauth',
                    'oauth_provider' => 'google_workspace',
                    'instructions' => 'Uses OAuth authentication with your Google Workspace account.'
                ];
            }

            return response()->json([
                'success' => true,
                'presets' => $presets
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load email provider presets'
            ], 500);
        }
    }
    
    /**
     * Get mail configuration status
     */
    public function getMailConfigStatus()
    {
        try {
            $status = $this->mailConfigService->getMailConfigStatus();
            $testResult = $this->mailConfigService->testCurrentMailConfig();
            
            return response()->json([
                'success' => true,
                'status' => $status,
                'test_result' => $testResult
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get mail configuration status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Send a real test email using current configuration
     */
    public function sendRealTestEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string'
        ]);
        
        try {
            $emailService = app(\App\Services\EmailService::class);
            
            $subject = $request->subject ?? 'Nestogy Email System Test';
            $message = $request->message ?? 'This is a test email sent from your Nestogy MSP platform to verify that all email systems are working correctly.';
            
            $result = $emailService->send(
                $request->email,
                $subject,
                $message
            );
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Test email sent successfully',
                    'details' => [
                        'to' => $request->email,
                        'subject' => $subject,
                        'sent_at' => now()->toISOString()
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send test email'
                ], 500);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Display integrations settings
     */
    public function integrations()
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        
        return view('settings.integrations', compact('company', 'setting'));
    }
    
    /**
     * Update integrations settings
     */
    public function updateIntegrations(IntegrationsSettingsRequest $request)
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        
        if (!$setting) {
            return redirect()->route('settings.integrations')
                ->with('error', 'Settings not found.');
        }
        
        $success = $this->settingsService->updateIntegrationsSettings($setting, $request->validated());
        
        if ($success) {
            return redirect()->route('settings.integrations')
                ->with('success', 'Integration settings updated successfully.');
        }
        
        return redirect()->route('settings.integrations')
            ->with('error', 'Failed to update integration settings.');
    }
    
    /**
     * Display billing and financial settings
     */
    public function billingFinancial()
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        $currencies = SettingsService::getCurrencies();
        
        return view('settings.billing-financial', compact('company', 'setting', 'currencies'));
    }
    
    /**
     * Update billing and financial settings
     */
    public function updateBillingFinancial(BillingFinancialSettingsRequest $request)
    {
        $company = Auth::user()->company;
        $setting = $this->getOrCreateSettings($company);
        
        // Log incoming request data
        Log::info('Billing Financial Settings Form Submission', [
            'company_id' => $company->id,
            'setting_id' => $setting->id,
            'form_data' => $request->all(),
            'validated_data' => $request->validated(),
            'checkbox_states' => [
                'paypal_enabled' => $request->get('paypal_enabled'),
                'stripe_enabled' => $request->get('stripe_enabled'), 
                'ach_enabled' => $request->get('ach_enabled'),
                'wire_enabled' => $request->get('wire_enabled'),
                'check_enabled' => $request->get('check_enabled')
            ]
        ]);
        
        $success = $this->settingsService->updateBillingFinancialSettings($setting, $request->validated());
        
        // Log result
        Log::info('Billing Financial Settings Update Result', [
            'success' => $success,
            'company_id' => $company->id,
            'setting_id' => $setting->id
        ]);
        
        if ($success) {
            return redirect()->route('settings.billing-financial')
                ->with('success', 'Billing & financial settings updated successfully.');
        }
        
        return redirect()->route('settings.billing-financial')
            ->with('error', 'Failed to update billing & financial settings.');
    }
    
    /**
     * Display RMM and monitoring settings
     */
    public function rmmMonitoring()
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        
        return view('settings.rmm-monitoring', compact('company', 'setting'));
    }
    
    /**
     * Update RMM and monitoring settings
     */
    public function updateRmmMonitoring(RmmMonitoringSettingsRequest $request)
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        
        if (!$setting) {
            return redirect()->route('settings.rmm-monitoring')
                ->with('error', 'Settings not found.');
        }
        
        $success = $this->settingsService->updateRmmMonitoringSettings($setting, $request->validated());
        
        if ($success) {
            return redirect()->route('settings.rmm-monitoring')
                ->with('success', 'RMM & monitoring settings updated successfully.');
        }
        
        return redirect()->route('settings.rmm-monitoring')
            ->with('error', 'Failed to update RMM & monitoring settings.');
    }
    
    /**
     * Display ticketing and service desk settings
     */
    public function ticketingServiceDesk()
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        
        // Get SLA data for the new interface
        $slas = $this->slaService->getActiveSLAs($company->id);
        $defaultSLA = $this->slaService->getDefaultSLA($company->id);
        
        // Get client assignment data
        $clientsWithSLA = Client::where('company_id', $company->id)
            ->with('sla')
            ->whereNotNull('sla_id')
            ->count();
            
        $totalClients = Client::where('company_id', $company->id)->count();
        
        // Get SLA metrics for performance overview
        $slaMetrics = $this->slaService->getSLAMetrics(
            $company->id,
            now()->subMonth(),
            now()
        );
        
        return view('settings.ticketing-service-desk', compact(
            'company', 
            'setting', 
            'slas', 
            'defaultSLA', 
            'clientsWithSLA', 
            'totalClients', 
            'slaMetrics'
        ));
    }
    
    /**
     * Update ticketing and service desk settings
     */
    public function updateTicketingServiceDesk(TicketingServiceDeskSettingsRequest $request)
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        
        if (!$setting) {
            return redirect()->route('settings.ticketing-service-desk')
                ->with('error', 'Settings not found.');
        }
        
        $success = $this->settingsService->updateTicketingServiceDeskSettings($setting, $request->validated());
        
        if ($success) {
            return redirect()->route('settings.ticketing-service-desk')
                ->with('success', 'Ticketing & service desk settings updated successfully.');
        }
        
        return redirect()->route('settings.ticketing-service-desk')
            ->with('error', 'Failed to update ticketing & service desk settings.');
    }
    
    /**
     * Display compliance and audit settings
     */
    public function complianceAudit()
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        
        return view('settings.compliance-audit', compact('company', 'setting'));
    }
    
    /**
     * Update compliance and audit settings
     */
    public function updateComplianceAudit(ComplianceAuditSettingsRequest $request)
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        
        if (!$setting) {
            return redirect()->route('settings.compliance-audit')
                ->with('error', 'Settings not found.');
        }
        
        $success = $this->settingsService->updateComplianceAuditSettings($setting, $request->validated());
        
        if ($success) {
            return redirect()->route('settings.compliance-audit')
                ->with('success', 'Compliance & audit settings updated successfully.');
        }
        
        return redirect()->route('settings.compliance-audit')
            ->with('error', 'Failed to update compliance & audit settings.');
    }
    
    /**
     * Display user management settings
     */
    public function userManagement()
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        $subscription = $company->subscription;
        
        return view('settings.user-management', compact('company', 'setting', 'subscription'));
    }
    
    /**
     * Update user management settings
     */
    public function updateUserManagement(UserManagementSettingsRequest $request)
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        
        if (!$setting) {
            return redirect()->route('settings.user-management')
                ->with('error', 'Settings not found.');
        }
        
        $success = $this->settingsService->updateUserManagementSettings($setting, $request->validated());
        
        if ($success) {
            return redirect()->route('settings.user-management')
                ->with('success', 'User management settings updated successfully.');
        }
        
        return redirect()->route('settings.user-management')
            ->with('error', 'Failed to update user management settings.');
    }
    
    /**
     * Export settings
     */
    public function export()
    {
        $company = Auth::user()->company;
        $jsonData = $this->settingsService->exportSettings($company);
        
        $filename = 'nestogy_settings_' . $company->id . '_' . now()->format('Y-m-d_H-i-s') . '.json';
        
        return Response::make($jsonData, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
    
    /**
     * Import settings
     */
    public function import(Request $request)
    {
        $request->validate([
            'settings_file' => 'required|file|mimes:json|max:2048',
        ]);
        
        $company = Auth::user()->company;
        $file = $request->file('settings_file');
        $jsonData = file_get_contents($file->getPathname());
        
        $success = $this->settingsService->importSettings($company, $jsonData);
        
        if ($success) {
            return redirect()->route('settings.general')
                ->with('success', 'Settings imported successfully.');
        }
        
        return redirect()->route('settings.general')
            ->with('error', 'Failed to import settings. Please check the file format.');
    }
    
    /**
     * Apply settings template
     */
    public function applyTemplate(Request $request)
    {
        $request->validate([
            'template_type' => 'required|string|in:small_msp,medium_msp,large_msp,healthcare_msp,financial_msp',
        ]);
        
        $company = Auth::user()->company;
        $success = $this->settingsService->applySettingsTemplate($company, $request->template_type);
        
        if ($success) {
            return redirect()->route('settings.general')
                ->with('success', 'Settings template applied successfully.');
        }
        
        return redirect()->route('settings.general')
            ->with('error', 'Failed to apply settings template.');
    }
    
    /**
     * Get available templates
     */
    public function templates()
    {
        $templates = [
            'small_msp' => $this->settingsService->getSettingsTemplate('small_msp'),
            'medium_msp' => $this->settingsService->getSettingsTemplate('medium_msp'),
            'large_msp' => $this->settingsService->getSettingsTemplate('large_msp'),
            'healthcare_msp' => $this->settingsService->getSettingsTemplate('healthcare_msp'),
            'financial_msp' => $this->settingsService->getSettingsTemplate('financial_msp'),
        ];
        
        return view('settings.templates', compact('templates'));
    }
    
    // MISSING SETTINGS CATEGORY METHODS
    
    public function accounting()
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        return view('settings.accounting', compact('company', 'setting'));
    }
    
    public function updateAccounting(Request $request)
    {
        $company = Auth::user()->company;
        $setting = $this->getOrCreateSettings($company);
        
        $setting->update($request->only([
            'quickbooks_integration_enabled', 'quickbooks_settings',
            'xero_integration_enabled', 'xero_settings',
            'sage_integration_enabled', 'sage_settings',
            'accounting_sync_enabled', 'chart_of_accounts_sync',
            'auto_invoice_sync', 'payment_sync_enabled'
        ]));
        
        return redirect()->route('settings.accounting')->with('success', 'Accounting settings updated successfully.');
    }
    
    public function paymentGateways()
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        return view('settings.payment-gateways', compact('company', 'setting'));
    }
    
    public function updatePaymentGateways(Request $request)
    {
        $company = Auth::user()->company;
        $setting = $this->getOrCreateSettings($company);
        
        // Only update configuration settings, not enable/disable toggles
        $setting->update($request->only([
            'stripe_publishable_key', 'stripe_secret_key',
            'paypal_client_id', 'paypal_client_secret',
            'ach_bank_name', 'ach_routing_number', 'ach_account_number'
        ]));
        
        return redirect()->route('settings.payment-gateways')->with('success', 'Payment gateway settings updated successfully.');
    }
    
    public function projectManagement()
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        return view('settings.project-management', compact('company', 'setting'));
    }
    
    public function updateProjectManagement(Request $request)
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        
        if (!$setting) {
            return redirect()->route('settings.project-management')->with('error', 'Settings not found.');
        }
        
        $setting->update($request->only([
            'project_auto_numbering', 'project_templates_enabled',
            'project_time_tracking', 'project_budget_alerts',
            'project_milestone_tracking', 'project_resource_management'
        ]));
        
        return redirect()->route('settings.project-management')->with('success', 'Project management settings updated successfully.');
    }
    
    public function assetInventory()
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        return view('settings.asset-inventory', compact('company', 'setting'));
    }
    
    public function updateAssetInventory(Request $request)
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        
        if (!$setting) {
            return redirect()->route('settings.asset-inventory')->with('error', 'Settings not found.');
        }
        
        $setting->update($request->only([
            'asset_auto_discovery', 'asset_depreciation_tracking',
            'asset_warranty_tracking', 'asset_maintenance_scheduling',
            'asset_location_tracking', 'asset_compliance_tracking'
        ]));
        
        return redirect()->route('settings.asset-inventory')->with('success', 'Asset & inventory settings updated successfully.');
    }
    
    public function clientPortal()
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        return view('settings.client-portal', compact('company', 'setting'));
    }
    
    public function updateClientPortal(Request $request)
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        
        if (!$setting) {
            return redirect()->route('settings.client-portal')->with('error', 'Settings not found.');
        }
        
        $setting->update($request->only([
            'client_portal_enabled', 'client_portal_settings',
            'client_self_service_enabled', 'client_ticket_creation',
            'client_invoice_access', 'client_knowledge_base_access'
        ]));
        
        return redirect()->route('settings.client-portal')->with('success', 'Client portal settings updated successfully.');
    }
    
    public function automationWorkflows()
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        return view('settings.automation-workflows', compact('company', 'setting'));
    }
    
    public function updateAutomationWorkflows(Request $request)
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        
        if (!$setting) {
            return redirect()->route('settings.automation-workflows')->with('error', 'Settings not found.');
        }
        
        $setting->update($request->only([
            'workflow_automation_enabled', 'workflow_templates',
            'auto_assignment_rules', 'escalation_workflows',
            'notification_workflows', 'approval_workflows'
        ]));
        
        return redirect()->route('settings.automation-workflows')->with('success', 'Automation & workflow settings updated successfully.');
    }
    
    public function apiWebhooks()
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        return view('settings.api-webhooks', compact('company', 'setting'));
    }
    
    public function updateApiWebhooks(Request $request)
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        
        if (!$setting) {
            return redirect()->route('settings.api-webhooks')->with('error', 'Settings not found.');
        }
        
        $setting->update($request->only([
            'api_enabled', 'api_rate_limiting', 'webhook_endpoints',
            'webhook_authentication', 'api_documentation_enabled',
            'developer_portal_enabled'
        ]));
        
        return redirect()->route('settings.api-webhooks')->with('success', 'API & webhook settings updated successfully.');
    }
    
    public function performanceOptimization()
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        return view('settings.performance-optimization', compact('company', 'setting'));
    }
    
    public function updatePerformanceOptimization(Request $request)
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        
        if (!$setting) {
            return redirect()->route('settings.performance-optimization')->with('error', 'Settings not found.');
        }
        
        $setting->update($request->only([
            'caching_enabled', 'database_optimization',
            'cdn_enabled', 'image_optimization',
            'search_indexing', 'performance_monitoring'
        ]));
        
        return redirect()->route('settings.performance-optimization')->with('success', 'Performance optimization settings updated successfully.');
    }
    
    public function reportingAnalytics()
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        return view('settings.reporting-analytics', compact('company', 'setting'));
    }
    
    public function updateReportingAnalytics(Request $request)
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        
        if (!$setting) {
            return redirect()->route('settings.reporting-analytics')->with('error', 'Settings not found.');
        }
        
        $setting->update($request->only([
            'analytics_enabled', 'custom_dashboards',
            'automated_reports', 'report_scheduling',
            'data_retention_reports', 'business_intelligence'
        ]));
        
        return redirect()->route('settings.reporting-analytics')->with('success', 'Reporting & analytics settings updated successfully.');
    }
    
    public function notificationsAlerts()
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        return view('settings.notifications-alerts', compact('company', 'setting'));
    }
    
    public function updateNotificationsAlerts(Request $request)
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        
        if (!$setting) {
            return redirect()->route('settings.notifications-alerts')->with('error', 'Settings not found.');
        }
        
        $setting->update($request->only([
            'email_notifications', 'sms_notifications',
            'push_notifications', 'slack_notifications',
            'teams_notifications', 'alert_escalation'
        ]));
        
        return redirect()->route('settings.notifications-alerts')->with('success', 'Notifications & alerts settings updated successfully.');
    }
    
    public function mobileRemote()
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        return view('settings.mobile-remote', compact('company', 'setting'));
    }
    
    public function updateMobileRemote(Request $request)
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        
        if (!$setting) {
            return redirect()->route('settings.mobile-remote')->with('error', 'Settings not found.');
        }
        
        $setting->update($request->only([
            'mobile_app_enabled', 'remote_access_enabled',
            'vpn_settings', 'mobile_security_settings',
            'offline_capability', 'location_tracking'
        ]));
        
        return redirect()->route('settings.mobile-remote')->with('success', 'Mobile & remote access settings updated successfully.');
    }
    
    public function trainingDocumentation()
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        return view('settings.training-documentation', compact('company', 'setting'));
    }
    
    public function updateTrainingDocumentation(Request $request)
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        
        if (!$setting) {
            return redirect()->route('settings.training-documentation')->with('error', 'Settings not found.');
        }
        
        $setting->update($request->only([
            'training_portal_enabled', 'documentation_wiki',
            'video_tutorials', 'certification_tracking',
            'onboarding_workflows', 'training_analytics'
        ]));
        
        return redirect()->route('settings.training-documentation')->with('success', 'Training & documentation settings updated successfully.');
    }
    
    public function knowledgeBase()
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        return view('settings.knowledge-base', compact('company', 'setting'));
    }
    
    public function updateKnowledgeBase(Request $request)
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        
        if (!$setting) {
            return redirect()->route('settings.knowledge-base')->with('error', 'Settings not found.');
        }
        
        $setting->update($request->only([
            'knowledge_base_enabled', 'kb_public_access',
            'kb_search_enabled', 'kb_categorization',
            'kb_version_control', 'kb_analytics'
        ]));
        
        return redirect()->route('settings.knowledge-base')->with('success', 'Knowledge base settings updated successfully.');
    }
    
    public function backupRecovery()
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        return view('settings.backup-recovery', compact('company', 'setting'));
    }
    
    public function updateBackupRecovery(Request $request)
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        
        if (!$setting) {
            return redirect()->route('settings.backup-recovery')->with('error', 'Settings not found.');
        }
        
        $setting->update($request->only([
            'automated_backups', 'backup_schedule',
            'backup_retention', 'disaster_recovery',
            'backup_encryption', 'recovery_testing'
        ]));
        
        return redirect()->route('settings.backup-recovery')->with('success', 'Backup & recovery settings updated successfully.');
    }
    
    public function dataManagement()
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        return view('settings.data-management', compact('company', 'setting'));
    }
    
    public function updateDataManagement(Request $request)
    {
        $company = Auth::user()->company;
        $setting = $company->setting;
        
        if (!$setting) {
            return redirect()->route('settings.data-management')->with('error', 'Settings not found.');
        }
        
        $setting->update($request->only([
            'data_archiving', 'data_purging_schedule',
            'gdpr_compliance', 'data_export_tools',
            'search_discovery_settings', 'data_classification'
        ]));
        
        return redirect()->route('settings.data-management')->with('success', 'Data management settings updated successfully.');
    }
    
    /**
     * Update company colors
     */
    public function updateColors(Request $request)
    {
        $request->validate([
            'colors' => 'required|array',
            'colors.primary' => 'nullable|array',
            'colors.primary.*' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'colors.secondary' => 'nullable|array',
            'colors.secondary.*' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
        ]);
        
        $company = Auth::user()->company;
        
        if ($this->settingsService->updateCompanyColors($company, $request->colors)) {
            return response()->json([
                'success' => true,
                'message' => 'Colors updated successfully.',
                'css' => $this->settingsService->generateCompanyCss($company),
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to update colors.',
        ], 500);
    }
    
    /**
     * Apply color preset
     */
    public function applyColorPreset(Request $request)
    {
        $request->validate([
            'preset' => 'required|string|in:blue,green,purple,red,orange',
        ]);
        
        $company = Auth::user()->company;
        
        if ($this->settingsService->applyColorPreset($company, $request->preset)) {
            return response()->json([
                'success' => true,
                'message' => 'Color preset applied successfully.',
                'colors' => $this->settingsService->getCompanyColors($company),
                'css' => $this->settingsService->generateCompanyCss($company),
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to apply color preset.',
        ], 500);
    }
    
    /**
     * Reset colors to default
     */
    public function resetColors(Request $request)
    {
        $company = Auth::user()->company;
        
        if ($this->settingsService->resetCompanyColors($company)) {
            return response()->json([
                'success' => true,
                'message' => 'Colors reset to default successfully.',
                'colors' => $this->settingsService->getCompanyColors($company),
                'css' => $this->settingsService->generateCompanyCss($company),
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to reset colors.',
        ], 500);
    }
    
    /**
     * Get content for a specific settings section (AJAX)
     */
    public function getContent(Request $request, $section)
    {
        $company = Auth::user()->company;
        $settings = $this->settingsService->getComprehensiveSettings($company);
        
        // Define section mappings to controller methods
        $sectionMethods = [
            'general' => 'getGeneralContent',
            'security' => 'getSecurityContent',
            'email' => 'getEmailContent',
            'user-management' => 'getUserManagementContent',
            'billing-financial' => 'getBillingFinancialContent',
            'accounting' => 'getAccountingContent',
            'payment-gateways' => 'getPaymentGatewaysContent',
            'ticketing-service-desk' => 'getTicketingContent',
            'project-management' => 'getProjectManagementContent',
            'asset-inventory' => 'getAssetInventoryContent',
            'client-portal' => 'getClientPortalContent',
            'rmm-monitoring' => 'getRmmMonitoringContent',
            'integrations' => 'getIntegrationsContent',
            'automation-workflows' => 'getAutomationWorkflowsContent',
            'api-webhooks' => 'getApiWebhooksContent',
            'compliance-audit' => 'getComplianceAuditContent',
            'backup-recovery' => 'getBackupRecoveryContent',
            'data-management' => 'getDataManagementContent',
            'performance-optimization' => 'getPerformanceOptimizationContent',
            'reporting-analytics' => 'getReportingAnalyticsContent',
            'notifications-alerts' => 'getNotificationsAlertsContent',
            'mobile-remote' => 'getMobileRemoteContent',
            'training-documentation' => 'getTrainingDocumentationContent',
            'knowledge-base' => 'getKnowledgeBaseContent',
        ];
        
        if (!isset($sectionMethods[$section])) {
            return response()->json(['error' => 'Section not found'], 404);
        }
        
        $method = $sectionMethods[$section];
        
        if (!method_exists($this, $method)) {
            return response()->json(['error' => 'Content method not implemented'], 404);
        }
        
        $content = $this->$method($company, $settings);
        
        return response()->json([
            'success' => true,
            'section' => $section,
            'html' => $content,
            'title' => ucwords(str_replace('-', ' ', $section)) . ' Settings'
        ]);
    }
    
    /**
     * Get section data for lazy loading
     */
    public function getSectionData(Request $request, $section)
    {
        $company = Auth::user()->company;
        $settings = $this->settingsService->getComprehensiveSettings($company);
        
        return response()->json([
            'success' => true,
            'section' => $section,
            'data' => $settings,
            'lastModified' => $company->updated_at
        ]);
    }
    
    /**
     * Get tab content for lazy loading
     */
    public function getTabContent(Request $request, $section, $tab)
    {
        $company = Auth::user()->company;
        $settings = $this->settingsService->getComprehensiveSettings($company);
        
        // Define tab content methods
        $tabMethods = [
            'general' => [
                'company' => 'getGeneralCompanyTabContent',
                'localization' => 'getGeneralLocalizationTabContent', 
                'branding' => 'getGeneralBrandingTabContent',
                'system' => 'getGeneralSystemTabContent'
            ],
            'email' => [
                'smtp' => 'getEmailSmtpTabContent',
                'imap' => 'getEmailImapTabContent',
                'tickets' => 'getEmailTicketsTabContent',
                'templates' => 'getEmailTemplatesTabContent'
            ],
            'integrations' => [
                'modules' => 'getIntegrationsModulesTabContent',
                'automation' => 'getIntegrationsAutomationTabContent',
                'apis' => 'getIntegrationsApisTabContent'
            ],
            'compliance-audit' => [
                'compliance' => 'getComplianceStandardsTabContent',
                'audit' => 'getAuditConfigTabContent',
                'reporting' => 'getComplianceReportingTabContent',
                'retention' => 'getComplianceRetentionTabContent'
            ],
            'data-management' => [
                'retention' => 'getDataRetentionTabContent',
                'destruction' => 'getDataDestructionTabContent',
                'governance' => 'getDataGovernanceTabContent',
                'quality' => 'getDataQualityTabContent',
                'privacy' => 'getDataPrivacyTabContent',
                'lineage' => 'getDataLineageTabContent',
                'migration' => 'getDataMigrationTabContent'
            ],
            'billing-financial' => [
                'billing' => 'getBillingTabContent',
                'taxes' => 'getTaxesTabContent',
                'invoicing' => 'getInvoicingTabContent',
                'payments' => 'getPaymentsTabContent'
            ]
        ];
        
        if (!isset($tabMethods[$section][$tab])) {
            return response()->json(['error' => 'Tab not found'], 404);
        }
        
        $method = $tabMethods[$section][$tab];
        
        if (!method_exists($this, $method)) {
            return response()->json(['error' => 'Tab content method not implemented'], 404);
        }
        
        $content = $this->$method($company, $settings);
        
        return response()->json([
            'success' => true,
            'section' => $section,
            'tab' => $tab,
            'html' => $content,
            'title' => ucwords(str_replace('-', ' ', $tab))
        ]);
    }
    
    /**
     * Get tabs configuration for a section
     */
    public function getTabsConfiguration(Request $request, $section)
    {
        $tabsConfig = [
            'general' => [
                'company' => ['title' => 'Company Information', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h1a1 1 0 011 1v5m-4 0h4'],
                'localization' => ['title' => 'Localization', 'icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'],
                'branding' => ['title' => 'Branding & Colors', 'icon' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM7 3H5v12a2 2 0 002 2h2V5a2 2 0 00-2-2zM17 21a4 4 0 004-4V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4z'],
                'system' => ['title' => 'System Preferences', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z']
            ],
            'email' => [
                'smtp' => ['title' => 'SMTP Configuration', 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                'imap' => ['title' => 'IMAP Configuration', 'icon' => 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4'],
                'tickets' => ['title' => 'Ticket Email Settings', 'icon' => 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z'],
                'templates' => ['title' => 'Email Templates', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z']
            ],
            'integrations' => [
                'modules' => ['title' => 'Module Management', 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                'automation' => ['title' => 'Automation Settings', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                'apis' => ['title' => 'API Configuration', 'icon' => 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z']
            ],
            'compliance-audit' => [
                'compliance' => ['title' => 'Compliance Standards', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                'audit' => ['title' => 'Audit Configuration', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                'reporting' => ['title' => 'Reporting', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                'retention' => ['title' => 'Data Retention', 'icon' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4']
            ],
            'data-management' => [
                'retention' => ['title' => 'Data Retention', 'icon' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4'],
                'destruction' => ['title' => 'Data Destruction', 'icon' => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'],
                'governance' => ['title' => 'Classification & Governance', 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                'quality' => ['title' => 'Quality & Validation', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                'privacy' => ['title' => 'Privacy & Protection', 'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
                'lineage' => ['title' => 'Lineage & Audit', 'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                'migration' => ['title' => 'Migration & Import/Export', 'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4']
            ],
            'billing-financial' => [
                'billing' => ['title' => 'Billing Configuration', 'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
                'taxes' => ['title' => 'Tax Settings', 'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
                'invoicing' => ['title' => 'Invoicing', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                'payments' => ['title' => 'Payment Processing', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z']
            ]
        ];
        
        return response()->json([
            'success' => true,
            'section' => $section,
            'tabs' => $tabsConfig[$section] ?? [],
            'defaultTab' => array_key_first($tabsConfig[$section] ?? [])
        ]);
    }
    
    /**
     * Get navigation tree with lazy loading metadata
     */
    public function getNavigationTree(Request $request)
    {
        $navigationSections = [
            'core' => [
                'title' => 'Core Settings',
                'items' => ['general', 'security', 'email', 'user-management']
            ],
            'financial' => [
                'title' => 'Financial Management', 
                'items' => ['billing-financial', 'accounting', 'payment-gateways']
            ],
            'service' => [
                'title' => 'Service Delivery',
                'items' => ['ticketing-service-desk', 'project-management', 'asset-inventory', 'client-portal']
            ],
            'tech' => [
                'title' => 'Technology Integration',
                'items' => ['rmm-monitoring', 'integrations', 'automation-workflows', 'api-webhooks']
            ],
            'compliance' => [
                'title' => 'Compliance & Security',
                'items' => ['compliance-audit', 'backup-recovery', 'data-management']
            ],
            'system' => [
                'title' => 'System & Performance',
                'items' => ['performance-optimization', 'reporting-analytics', 'notifications-alerts', 'mobile-remote']
            ],
            'knowledge' => [
                'title' => 'Knowledge & Training',
                'items' => ['training-documentation', 'knowledge-base']
            ]
        ];
        
        return response()->json([
            'success' => true,
            'sections' => $navigationSections,
            'loadingStates' => array_keys($navigationSections)
        ]);
    }
    
    /**
     * Get General Settings content for lazy loading
     */
    private function getGeneralContent($company, $settings)
    {
        $timezones = SettingsService::getTimezones();
        $dateFormats = SettingsService::getDateFormats();
        $currencies = SettingsService::getCurrencies();
        $companyColors = $this->settingsService->getCompanyColors($company);
        $colorPresets = $this->settingsService->getColorPresets();
        
        return view('settings.partials.general-content', compact(
            'company', 'settings', 'timezones', 'dateFormats', 
            'currencies', 'companyColors', 'colorPresets'
        ))->render();
    }
    
    /**
     * Get Security Settings content for lazy loading
     */
    private function getSecurityContent($company, $settings)
    {
        return view('settings.partials.security-content', compact('company', 'settings'))->render();
    }
    
    /**
     * Placeholder methods for other sections
     * These should be implemented as needed
     */
    private function getEmailContent($company, $settings) 
    {
        return view('settings.partials.email-content', compact('company', 'settings'))->render();
    }
    
    private function getUserManagementContent($company, $settings)
    {
        return view('settings.partials.user-management-content', compact('company', 'settings'))->render();
    }
    
    private function getBillingFinancialContent($company, $settings)
    {
        return view('settings.partials.billing-financial-content', compact('company', 'settings'))->render();
    }
    
    private function getAccountingContent($company, $settings)
    {
        return view('settings.partials.accounting-content', compact('company', 'settings'))->render();
    }
    
    private function getPaymentGatewaysContent($company, $settings)
    {
        return view('settings.partials.payment-gateways-content', compact('company', 'settings'))->render();
    }
    
    private function getTicketingContent($company, $settings)
    {
        $slas = $this->slaService->getActiveSLAs($company);
        return view('settings.partials.ticketing-content', compact('company', 'settings', 'slas'))->render();
    }
    
    private function getProjectManagementContent($company, $settings)
    {
        return view('settings.partials.project-management-content', compact('company', 'settings'))->render();
    }
    
    private function getAssetInventoryContent($company, $settings)
    {
        return view('settings.partials.asset-inventory-content', compact('company', 'settings'))->render();
    }
    
    private function getClientPortalContent($company, $settings)
    {
        return view('settings.partials.client-portal-content', compact('company', 'settings'))->render();
    }
    
    private function getRmmMonitoringContent($company, $settings)
    {
        return view('settings.partials.rmm-monitoring-content', compact('company', 'settings'))->render();
    }
    
    private function getIntegrationsContent($company, $settings)
    {
        return view('settings.partials.integrations-content', compact('company', 'settings'))->render();
    }
    
    private function getAutomationWorkflowsContent($company, $settings)
    {
        return view('settings.partials.automation-workflows-content', compact('company', 'settings'))->render();
    }
    
    private function getApiWebhooksContent($company, $settings)
    {
        return view('settings.partials.api-webhooks-content', compact('company', 'settings'))->render();
    }
    
    private function getComplianceAuditContent($company, $settings)
    {
        return view('settings.partials.compliance-audit-content', compact('company', 'settings'))->render();
    }
    
    private function getBackupRecoveryContent($company, $settings)
    {
        return view('settings.partials.backup-recovery-content', compact('company', 'settings'))->render();
    }
    
    private function getDataManagementContent($company, $settings)
    {
        return view('settings.partials.data-management-content', compact('company', 'settings'))->render();
    }
    
    private function getPerformanceOptimizationContent($company, $settings)
    {
        return view('settings.partials.performance-optimization-content', compact('company', 'settings'))->render();
    }
    
    private function getReportingAnalyticsContent($company, $settings)
    {
        return view('settings.partials.reporting-analytics-content', compact('company', 'settings'))->render();
    }
    
    private function getNotificationsAlertsContent($company, $settings)
    {
        return view('settings.partials.notifications-alerts-content', compact('company', 'settings'))->render();
    }
    
    private function getMobileRemoteContent($company, $settings)
    {
        return view('settings.partials.mobile-remote-content', compact('company', 'settings'))->render();
    }
    
    private function getTrainingDocumentationContent($company, $settings)
    {
        return view('settings.partials.training-documentation-content', compact('company', 'settings'))->render();
    }
    
    private function getKnowledgeBaseContent($company, $settings)
    {
        return view('settings.partials.knowledge-base-content', compact('company', 'settings'))->render();
    }
    
    /**
     * Tab content methods for lazy loading
     */
    
    // General Settings Tab Methods
    private function getGeneralCompanyTabContent($company, $settings)
    {
        return view('settings.partials.tabs.general-company', compact('company', 'settings'))->render();
    }
    
    private function getGeneralLocalizationTabContent($company, $settings)
    {
        $timezones = SettingsService::getTimezones();
        $dateFormats = SettingsService::getDateFormats();
        $currencies = SettingsService::getCurrencies();
        return view('settings.partials.tabs.general-localization', compact('company', 'settings', 'timezones', 'dateFormats', 'currencies'))->render();
    }
    
    private function getGeneralBrandingTabContent($company, $settings)
    {
        $companyColors = $this->settingsService->getCompanyColors($company);
        $colorPresets = $this->settingsService->getColorPresets();
        return view('settings.partials.tabs.general-branding', compact('company', 'settings', 'companyColors', 'colorPresets'))->render();
    }
    
    private function getGeneralSystemTabContent($company, $settings)
    {
        return view('settings.partials.tabs.general-system', compact('company', 'settings'))->render();
    }
    
    // Email Settings Tab Methods
    private function getEmailSmtpTabContent($company, $settings)
    {
        return view('settings.partials.tabs.email-smtp', compact('company', 'settings'))->render();
    }
    
    private function getEmailImapTabContent($company, $settings)
    {
        return view('settings.partials.tabs.email-imap', compact('company', 'settings'))->render();
    }
    
    private function getEmailTicketsTabContent($company, $settings)
    {
        return view('settings.partials.tabs.email-tickets', compact('company', 'settings'))->render();
    }
    
    private function getEmailTemplatesTabContent($company, $settings)
    {
        return view('settings.partials.tabs.email-templates', compact('company', 'settings'))->render();
    }
    
    // Integration Settings Tab Methods
    private function getIntegrationsModulesTabContent($company, $settings)
    {
        return view('settings.partials.tabs.integrations-modules', compact('company', 'settings'))->render();
    }
    
    private function getIntegrationsAutomationTabContent($company, $settings)
    {
        return view('settings.partials.tabs.integrations-automation', compact('company', 'settings'))->render();
    }
    
    private function getIntegrationsApisTabContent($company, $settings)
    {
        return view('settings.partials.tabs.integrations-apis', compact('company', 'settings'))->render();
    }
    
    // Compliance & Audit Tab Methods
    private function getComplianceStandardsTabContent($company, $settings)
    {
        return view('settings.partials.tabs.compliance-standards', compact('company', 'settings'))->render();
    }
    
    private function getAuditConfigTabContent($company, $settings)
    {
        return view('settings.partials.tabs.audit-config', compact('company', 'settings'))->render();
    }
    
    private function getComplianceReportingTabContent($company, $settings)
    {
        return view('settings.partials.tabs.compliance-reporting', compact('company', 'settings'))->render();
    }
    
    private function getComplianceRetentionTabContent($company, $settings)
    {
        return view('settings.partials.tabs.compliance-retention', compact('company', 'settings'))->render();
    }
    
    // Data Management Tab Methods
    private function getDataRetentionTabContent($company, $settings)
    {
        return view('settings.partials.tabs.data-retention', compact('company', 'settings'))->render();
    }
    
    private function getDataDestructionTabContent($company, $settings)
    {
        return view('settings.partials.tabs.data-destruction', compact('company', 'settings'))->render();
    }
    
    private function getDataGovernanceTabContent($company, $settings)
    {
        return view('settings.partials.tabs.data-governance', compact('company', 'settings'))->render();
    }
    
    private function getDataQualityTabContent($company, $settings)
    {
        return view('settings.partials.tabs.data-quality', compact('company', 'settings'))->render();
    }
    
    private function getDataPrivacyTabContent($company, $settings)
    {
        return view('settings.partials.tabs.data-privacy', compact('company', 'settings'))->render();
    }
    
    private function getDataLineageTabContent($company, $settings)
    {
        return view('settings.partials.tabs.data-lineage', compact('company', 'settings'))->render();
    }
    
    private function getDataMigrationTabContent($company, $settings)
    {
        return view('settings.partials.tabs.data-migration', compact('company', 'settings'))->render();
    }
    
    // Billing & Financial Tab Methods
    private function getBillingTabContent($company, $settings)
    {
        return view('settings.partials.tabs.billing-config', compact('company', 'settings'))->render();
    }
    
    private function getTaxesTabContent($company, $settings)
    {
        return view('settings.partials.tabs.tax-settings', compact('company', 'settings'))->render();
    }
    
    private function getInvoicingTabContent($company, $settings)
    {
        return view('settings.partials.tabs.invoicing', compact('company', 'settings'))->render();
    }
    
    private function getPaymentsTabContent($company, $settings)
    {
        return view('settings.partials.tabs.payments', compact('company', 'settings'))->render();
    }
    
    /**
     * Display contract clauses management page
     */
    public function contractClauses(Request $request)
    {
        $company = Auth::user()->company;
        
        $query = \App\Domains\Contract\Models\ContractClause::where('company_id', $company->id)
            ->with(['creator', 'updater']);
        
        // Apply filters
        if ($request->has('category') && $request->category != 'all') {
            $query->where('category', $request->category);
        }
        
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }
        
        if ($request->has('clause_type') && $request->clause_type != 'all') {
            $query->where('clause_type', $request->clause_type);
        }
        
        if ($request->has('is_system')) {
            $query->where('is_system', $request->boolean('is_system'));
        }
        
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }
        
        $clauses = $query->orderBy('category')
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->paginate(20);
        
        $categories = \App\Domains\Contract\Models\ContractClause::getAvailableCategories();
        $clauseTypes = \App\Domains\Contract\Models\ContractClause::getAvailableTypes();
        
        // Get available definitions for UI
        $definitionRegistryService = app(\App\Services\DefinitionRegistryService::class);
        $availableDefinitions = $definitionRegistryService->getDefinitionsByCategory();
        
        // Get complete template variable reference data
        $templateVariables = [
            'categories' => [
                'Core Contract' => [
                    'description' => 'Basic contract information and metadata',
                    'variables' => [
                        'contract_title' => ['label' => 'Contract Title', 'example' => 'Managed Services Agreement'],
                        'contract_type' => ['label' => 'Contract Type', 'example' => 'managed_services'],
                        'effective_date' => ['label' => 'Effective Date', 'example' => '2024-01-01'],
                        'end_date' => ['label' => 'End Date', 'example' => '2024-12-31'],
                        'currency_code' => ['label' => 'Currency Code', 'example' => 'USD'],
                        'governing_law' => ['label' => 'Governing Law', 'example' => 'Texas'],
                        'initial_term' => ['label' => 'Initial Term', 'example' => 'one (1) year'],
                        'renewal_term' => ['label' => 'Renewal Term', 'example' => 'one (1) year'],
                        'termination_notice_days' => ['label' => 'Termination Notice Days', 'example' => '30 days'],
                        'arbitration_location' => ['label' => 'Arbitration Location', 'example' => 'Dallas, Texas'],
                    ]
                ],
                'Client Information' => [
                    'description' => 'Client company and contact details',
                    'variables' => [
                        'client_name' => ['label' => 'Client Company Name', 'example' => 'Acme Corporation'],
                        'client_short_name' => ['label' => 'Client Short Name', 'example' => 'Acme'],
                        'client_address' => ['label' => 'Client Address', 'example' => '123 Main St, Dallas, TX'],
                        'client_signatory_name' => ['label' => 'Client Signatory Name', 'example' => 'John Smith'],
                        'client_signatory_title' => ['label' => 'Client Signatory Title', 'example' => 'CEO'],
                        'client_signature_date' => ['label' => 'Client Signature Date', 'example' => 'January 1, 2024'],
                    ]
                ],
                'Service Provider' => [
                    'description' => 'Service provider company information',
                    'variables' => [
                        'service_provider_name' => ['label' => 'Service Provider Name', 'example' => 'TechSupport Pro LLC'],
                        'service_provider_short_name' => ['label' => 'Service Provider Short Name', 'example' => 'TechSupport Pro'],
                        'service_provider_address' => ['label' => 'Service Provider Address', 'example' => '456 Tech Ave, Austin, TX'],
                        'service_provider_signatory_name' => ['label' => 'Provider Signatory Name', 'example' => 'Jane Doe'],
                        'service_provider_signatory_title' => ['label' => 'Provider Signatory Title', 'example' => 'President'],
                        'service_provider_signature_date' => ['label' => 'Provider Signature Date', 'example' => 'January 1, 2024'],
                    ]
                ],
                'MSP Services' => [
                    'description' => 'Managed Service Provider specific variables',
                    'variables' => [
                        'supported_asset_types' => ['label' => 'Supported Asset Types', 'example' => 'Workstations, Servers, and Network Devices'],
                        'supported_asset_count' => ['label' => 'Supported Asset Count', 'example' => '50'],
                        'service_tier' => ['label' => 'Service Tier', 'example' => 'Gold'],
                        'response_time_hours' => ['label' => 'Response Time (Hours)', 'example' => '2'],
                        'resolution_time_hours' => ['label' => 'Resolution Time (Hours)', 'example' => '12'],
                        'uptime_percentage' => ['label' => 'Uptime Percentage', 'example' => '99.9'],
                        'business_hours' => ['label' => 'Business Hours', 'example' => '24x7'],
                        'tier_benefits' => ['label' => 'Service Tier Benefits', 'example' => '24x7 support, On-site support included'],
                        'has_workstation_support' => ['label' => 'Has Workstation Support', 'example' => 'true'],
                        'has_server_support' => ['label' => 'Has Server Support', 'example' => 'true'],
                        'has_network_support' => ['label' => 'Has Network Support', 'example' => 'true'],
                        'includes_remote_support' => ['label' => 'Includes Remote Support', 'example' => 'true'],
                        'includes_onsite_support' => ['label' => 'Includes On-site Support', 'example' => 'false'],
                        'auto_assign_assets' => ['label' => 'Auto Assign Assets', 'example' => 'false'],
                        'excluded_asset_types' => ['label' => 'Excluded Asset Types', 'example' => 'Personal devices'],
                        'excluded_services' => ['label' => 'Excluded Services', 'example' => 'Software development'],
                    ]
                ],
                'VoIP Services' => [
                    'description' => 'Voice over IP telecommunications variables',
                    'variables' => [
                        'channel_count' => ['label' => 'Channel Count', 'example' => '10'],
                        'protocol' => ['label' => 'Protocol', 'example' => 'SIP'],
                        'calling_plan' => ['label' => 'Calling Plan', 'example' => 'local_long_distance'],
                        'international_calling' => ['label' => 'International Calling', 'example' => 'additional'],
                        'emergency_services' => ['label' => 'Emergency Services', 'example' => 'enabled'],
                        'mos_score' => ['label' => 'MOS Score', 'example' => '4.2'],
                        'jitter_ms' => ['label' => 'Jitter (ms)', 'example' => '30'],
                        'packet_loss_percent' => ['label' => 'Packet Loss (%)', 'example' => '0.1'],
                        'telecom_uptime_percent' => ['label' => 'Telecom Uptime (%)', 'example' => '99.9'],
                        'latency_ms' => ['label' => 'Latency (ms)', 'example' => '80'],
                        'primary_carrier' => ['label' => 'Primary Carrier', 'example' => 'Verizon'],
                        'backup_carrier' => ['label' => 'Backup Carrier', 'example' => 'AT&T'],
                        'fcc_compliant' => ['label' => 'FCC Compliant', 'example' => 'true'],
                        'karis_law' => ['label' => 'Karis Law Compliant', 'example' => 'true'],
                        'ray_baums' => ['label' => 'RAY BAUMS Act Compliant', 'example' => 'true'],
                        'encryption_enabled' => ['label' => 'Encryption Enabled', 'example' => 'true'],
                        'fraud_protection' => ['label' => 'Fraud Protection', 'example' => 'true'],
                        'call_recording' => ['label' => 'Call Recording', 'example' => 'false'],
                    ]
                ],
                'VAR Services' => [
                    'description' => 'Value Added Reseller hardware and installation variables',
                    'variables' => [
                        'hardware_categories' => ['label' => 'Hardware Categories', 'example' => 'Servers, Networking Equipment'],
                        'procurement_model' => ['label' => 'Procurement Model', 'example' => 'direct_resale'],
                        'lead_time_days' => ['label' => 'Lead Time (Days)', 'example' => '5'],
                        'lead_time_type' => ['label' => 'Lead Time Type', 'example' => 'business_days'],
                        'includes_installation' => ['label' => 'Includes Installation', 'example' => 'true'],
                        'includes_rack_stack' => ['label' => 'Includes Rack & Stack', 'example' => 'false'],
                        'includes_cabling' => ['label' => 'Includes Cabling', 'example' => 'false'],
                        'includes_configuration' => ['label' => 'Includes Configuration', 'example' => 'true'],
                        'includes_project_management' => ['label' => 'Includes Project Management', 'example' => 'false'],
                        'installation_timeline' => ['label' => 'Installation Timeline', 'example' => 'Within 5 business days'],
                        'configuration_timeline' => ['label' => 'Configuration Timeline', 'example' => 'Within 2 business days'],
                        'hardware_warranty_period' => ['label' => 'Hardware Warranty Period', 'example' => '1_year'],
                        'support_warranty_period' => ['label' => 'Support Warranty Period', 'example' => '1_year'],
                        'onsite_warranty_support' => ['label' => 'On-site Warranty Support', 'example' => 'false'],
                        'advanced_replacement' => ['label' => 'Advanced Replacement', 'example' => 'false'],
                    ]
                ],
                'Compliance' => [
                    'description' => 'Compliance and regulatory framework variables',
                    'variables' => [
                        'compliance_frameworks' => ['label' => 'Compliance Frameworks', 'example' => 'SOC 2, HIPAA'],
                        'risk_level' => ['label' => 'Risk Level', 'example' => 'medium'],
                        'industry_sector' => ['label' => 'Industry Sector', 'example' => 'healthcare'],
                        'includes_internal_audits' => ['label' => 'Includes Internal Audits', 'example' => 'true'],
                        'includes_external_audits' => ['label' => 'Includes External Audits', 'example' => 'false'],
                        'includes_penetration_testing' => ['label' => 'Includes Penetration Testing', 'example' => 'false'],
                        'includes_vulnerability_scanning' => ['label' => 'Includes Vulnerability Scanning', 'example' => 'true'],
                        'comprehensive_audit_frequency' => ['label' => 'Comprehensive Audit Frequency', 'example' => 'annually'],
                        'interim_audit_frequency' => ['label' => 'Interim Audit Frequency', 'example' => 'quarterly'],
                        'training_programs' => ['label' => 'Training Programs', 'example' => 'Security Awareness, Compliance Training'],
                        'training_delivery_method' => ['label' => 'Training Delivery Method', 'example' => 'online'],
                        'training_frequency' => ['label' => 'Training Frequency', 'example' => 'annually'],
                    ]
                ],
                'Financial Terms' => [
                    'description' => 'Billing, pricing, and payment variables',
                    'variables' => [
                        'billing_model' => ['label' => 'Billing Model', 'example' => 'per_asset'],
                        'monthly_base_rate' => ['label' => 'Monthly Base Rate', 'example' => '$2,500.00'],
                        'setup_fee' => ['label' => 'Setup Fee', 'example' => '$500.00'],
                        'hourly_rate' => ['label' => 'Hourly Rate', 'example' => '$150.00'],
                        'billing_frequency' => ['label' => 'Billing Frequency', 'example' => 'monthly'],
                        'payment_terms' => ['label' => 'Payment Terms', 'example' => 'net_30'],
                        'price_per_user' => ['label' => 'Price Per User', 'example' => '$50.00'],
                    ]
                ],
                'Section References' => [
                    'description' => 'Dynamic section cross-references',
                    'variables' => [
                        'definitions_section_ref' => ['label' => 'Definitions Section Reference', 'example' => 'Section 1 (Definitions)'],
                        'services_section_ref' => ['label' => 'Services Section Reference', 'example' => 'Section 2 (Scope of Support Services)'],
                        'sla_section_ref' => ['label' => 'SLA Section Reference', 'example' => 'Section 3 (Service Level Agreements)'],
                        'obligations_section_ref' => ['label' => 'Obligations Section Reference', 'example' => 'Section 4 (Client Obligations)'],
                        'financial_section_ref' => ['label' => 'Financial Section Reference', 'example' => 'Section 5 (Fees and Payment Terms)'],
                        'exclusions_section_ref' => ['label' => 'Exclusions Section Reference', 'example' => 'Section 6 (Service Exclusions)'],
                        'warranties_section_ref' => ['label' => 'Warranties Section Reference', 'example' => 'Section 7 (Warranties & Liability)'],
                        'confidentiality_section_ref' => ['label' => 'Confidentiality Section Reference', 'example' => 'Section 8 (Confidentiality)'],
                        'legal_section_ref' => ['label' => 'Legal Section Reference', 'example' => 'Section 9 (Legal Framework)'],
                        'admin_section_ref' => ['label' => 'Administrative Section Reference', 'example' => 'Section 10 (Administrative)'],
                    ]
                ]
            ],
            'formatters' => [
                'Text Formatting' => [
                    'upper' => ['label' => 'Uppercase', 'example' => 'client_name|upper → ACME CORP'],
                    'lower' => ['label' => 'Lowercase', 'example' => 'client_name|lower → acme corp'],
                    'title' => ['label' => 'Title Case', 'example' => 'client_name|title → Acme Corp'],
                    'capitalize' => ['label' => 'Capitalize', 'example' => 'client_name|capitalize → Acme corp'],
                    'replace_underscore' => ['label' => 'Replace Underscores', 'example' => 'billing_model|replace_underscore → per asset'],
                    'replace_underscore_title' => ['label' => 'Replace Underscores + Title', 'example' => 'billing_model|replace_underscore_title → Per Asset'],
                ],
                'Number Formatting' => [
                    'currency' => ['label' => 'Currency', 'example' => 'monthly_rate|currency → $2,500.00'],
                    'number' => ['label' => 'Number', 'example' => 'asset_count|number → 1,234'],
                    'percent' => ['label' => 'Percentage', 'example' => 'uptime|percent → 99.9%'],
                ],
                'Date Formatting' => [
                    'date' => ['label' => 'Full Date', 'example' => 'effective_date|date → January 1, 2024'],
                    'date_short' => ['label' => 'Short Date', 'example' => 'effective_date|date_short → 01/01/2024'],
                ],
                'List Formatting' => [
                    'list' => ['label' => 'Grammatical List', 'example' => 'asset_types|list → servers, workstations, and routers'],
                    'bullet_list' => ['label' => 'Bullet List', 'example' => 'asset_types|bullet_list → • servers\\n• workstations\\n• routers'],
                ],
                'Time Units' => [
                    'hours' => ['label' => 'Hours', 'example' => 'response_time|hours → 2 hours'],
                    'days' => ['label' => 'Days', 'example' => 'lead_time|days → 5 days'],
                ],
                'Boolean Values' => [
                    'boolean_text' => ['label' => 'Yes/No Text', 'example' => 'includes_onsite|boolean_text → Yes'],
                    'boolean_enabled' => ['label' => 'Enabled/Disabled', 'example' => 'auto_assign|boolean_enabled → Enabled'],
                ]
            ],
            'conditionals' => [
                'Basic Conditionals' => [
                    'if' => [
                        'label' => 'If Statement',
                        'syntax' => 'if variable_name content /if',
                        'example' => 'if includes_onsite_support On-site support is included. /if'
                    ],
                    'if_else' => [
                        'label' => 'If-Else Statement', 
                        'syntax' => 'if variable_name content else alternate /if',
                        'example' => 'if includes_onsite_support On-site support included. else Remote support only. /if'
                    ]
                ],
                'Comparisons' => [
                    'equals' => [
                        'label' => 'Equals Comparison',
                        'syntax' => 'if variable_name == value content /if',
                        'example' => 'if service_tier == Gold Premium support included. /if'
                    ],
                    'not_equals' => [
                        'label' => 'Not Equals',
                        'syntax' => 'if variable_name != value content /if',
                        'example' => 'if billing_model != fixed Usage-based billing applies. /if'
                    ]
                ],
                'List Processing' => [
                    'list_block' => [
                        'label' => 'List Block',
                        'syntax' => 'list variable_name item /list',
                        'example' => 'Supported assets: list supported_asset_types item /list'
                    ],
                    'exists_check' => [
                        'label' => 'Existence Check',
                        'syntax' => 'exists variable_name content /exists',
                        'example' => 'exists excluded_services The following services are excluded: excluded_services. /exists'
                    ]
                ]
            ]
        ];
        
        // Get statistics
        $stats = [
            'total' => \App\Domains\Contract\Models\ContractClause::where('company_id', $company->id)->count(),
            'active' => \App\Domains\Contract\Models\ContractClause::where('company_id', $company->id)->where('status', 'active')->count(),
            'system' => \App\Domains\Contract\Models\ContractClause::where('company_id', $company->id)->where('is_system', true)->count(),
            'user_created' => \App\Domains\Contract\Models\ContractClause::where('company_id', $company->id)->where('is_system', false)->count(),
        ];
        
        return view('settings.contract-clauses', compact('clauses', 'categories', 'clauseTypes', 'stats', 'company', 'availableDefinitions', 'templateVariables'));
    }
    
    /**
     * Store a new contract clause
     */
    public function storeContractClause(Request $request)
    {
        $company = Auth::user()->company;
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:' . implode(',', array_keys(\App\Domains\Contract\Models\ContractClause::getAvailableCategories())),
            'clause_type' => 'required|string|in:' . implode(',', array_keys(\App\Domains\Contract\Models\ContractClause::getAvailableTypes())),
            'content' => 'required|string',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'status' => 'required|string|in:active,inactive,archived',
            'is_required' => 'boolean',
            'applicable_contract_types' => 'nullable|array',
            'variables' => 'nullable|array',
            'conditions' => 'nullable|array',
            'metadata' => 'nullable|array',
            'required_definitions' => 'nullable|array',
            'required_definitions.*' => 'string',
        ]);
        
        // Handle required definitions in metadata
        if (isset($validated['required_definitions'])) {
            $metadata = $validated['metadata'] ?? [];
            $metadata['required_definitions'] = array_values(array_filter($validated['required_definitions']));
            $validated['metadata'] = $metadata;
            unset($validated['required_definitions']); // Remove from top level since it's in metadata
        }
        
        $validated['company_id'] = $company->id;
        $validated['created_by'] = Auth::id();
        $validated['version'] = '1.0';
        $validated['is_system'] = false; // User-created clauses are never system clauses
        
        // Ensure sort_order has a default value if null
        if (is_null($validated['sort_order'])) {
            // Get the next sort order for this category
            $maxSortOrder = \App\Domains\Contract\Models\ContractClause::where('company_id', $company->id)
                ->where('category', $validated['category'])
                ->max('sort_order');
            $validated['sort_order'] = ($maxSortOrder ?? 0) + 1;
        }
        
        $clause = \App\Domains\Contract\Models\ContractClause::create($validated);
        
        return redirect()->route('settings.contract-clauses')
            ->with('success', "Contract clause '{$clause->name}' created successfully.");
    }
    
    /**
     * Show the edit form for a contract clause
     */
    public function editContractClause(\App\Domains\Contract\Models\ContractClause $clause)
    {
        // Check if user can edit this clause
        if ($clause->is_system && !Auth::user()->hasRole('super-admin')) {
            return redirect()->route('settings.contract-clauses')
                ->with('error', 'System clauses cannot be edited.');
        }
        
        // Ensure clause belongs to user's company
        if ($clause->company_id !== Auth::user()->company_id) {
            return redirect()->route('settings.contract-clauses')
                ->with('error', 'Clause not found.');
        }

        $categories = \App\Domains\Contract\Models\ContractClause::getAvailableCategories();
        $clauseTypes = \App\Domains\Contract\Models\ContractClause::getAvailableTypes();
        
        // Get available definitions for the form
        $availableDefinitions = [
            'Legal Terms' => [
                'confidential_information' => 'Confidential Information',
                'business_day' => 'Business Day',
                'force_majeure' => 'Force Majeure',
                'governing_law' => 'Governing Law',
            ],
            'Service Terms' => [
                'service_hours' => 'Service Hours',
                'response_time' => 'Response Time',
                'uptime_sla' => 'Uptime SLA',
                'maintenance_window' => 'Maintenance Window',
            ],
            'Technical Terms' => [
                'managed_services' => 'Managed Services',
                'system_requirements' => 'System Requirements',
                'backup_procedures' => 'Backup Procedures',
                'security_standards' => 'Security Standards',
            ]
        ];

        // Get template variables for the form
        $templateVariables = [
            'categories' => [
                'Company Information' => [
                    'description' => 'Variables related to company details',
                    'variables' => [
                        'company.name' => ['label' => 'Company Name', 'example' => 'Acme Corp'],
                        'company.address' => ['label' => 'Company Address', 'example' => '123 Main St'],
                        'company.phone' => ['label' => 'Company Phone', 'example' => '555-123-4567'],
                        'company.email' => ['label' => 'Company Email', 'example' => 'info@company.com'],
                    ]
                ],
                'Client Information' => [
                    'description' => 'Variables related to client details',
                    'variables' => [
                        'client.name' => ['label' => 'Client Name', 'example' => 'ABC Inc'],
                        'client.address' => ['label' => 'Client Address', 'example' => '456 Oak Ave'],
                        'client.contact_name' => ['label' => 'Contact Name', 'example' => 'John Smith'],
                        'client.contact_email' => ['label' => 'Contact Email', 'example' => 'john@abcinc.com'],
                    ]
                ],
                'Contract Details' => [
                    'description' => 'Variables related to contract specifics',
                    'variables' => [
                        'contract.start_date' => ['label' => 'Start Date', 'example' => 'January 1, 2024'],
                        'contract.end_date' => ['label' => 'End Date', 'example' => 'December 31, 2024'],
                        'contract.term' => ['label' => 'Contract Term', 'example' => '12 months'],
                        'contract.value' => ['label' => 'Contract Value', 'example' => '$50,000'],
                    ]
                ]
            ]
        ];

        return response()->json([
            'success' => true,
            'clause' => $clause,
            'categories' => $categories,
            'clauseTypes' => $clauseTypes,
            'availableDefinitions' => $availableDefinitions,
            'templateVariables' => $templateVariables,
        ]);
    }
    
    /**
     * Update an existing contract clause
     */
    public function updateContractClause(Request $request, \App\Domains\Contract\Models\ContractClause $clause)
    {
        // Check if user can edit this clause
        if ($clause->is_system && !Auth::user()->hasRole('super-admin')) {
            return redirect()->route('settings.contract-clauses')
                ->with('error', 'System clauses cannot be modified.');
        }
        
        // Ensure clause belongs to user's company
        if ($clause->company_id !== Auth::user()->company_id) {
            return redirect()->route('settings.contract-clauses')
                ->with('error', 'Clause not found.');
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:' . implode(',', array_keys(\App\Domains\Contract\Models\ContractClause::getAvailableCategories())),
            'clause_type' => 'required|string|in:' . implode(',', array_keys(\App\Domains\Contract\Models\ContractClause::getAvailableTypes())),
            'content' => 'required|string',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'status' => 'required|string|in:active,inactive,archived',
            'is_required' => 'boolean',
            'applicable_contract_types' => 'nullable|array',
            'variables' => 'nullable|array',
            'conditions' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);
        
        $validated['updated_by'] = Auth::id();
        
        // Ensure sort_order has a default value if null
        if (is_null($validated['sort_order'])) {
            $validated['sort_order'] = $clause->sort_order ?? 0;
        }
        
        $clause->update($validated);
        
        return redirect()->route('settings.contract-clauses')
            ->with('success', "Contract clause '{$clause->name}' updated successfully.");
    }
    
    /**
     * Delete a contract clause
     */
    public function destroyContractClause(\App\Domains\Contract\Models\ContractClause $clause)
    {
        // Check if user can delete this clause
        if ($clause->is_system && !Auth::user()->hasRole('super-admin')) {
            return redirect()->route('settings.contract-clauses')
                ->with('error', 'System clauses cannot be deleted.');
        }
        
        // Ensure clause belongs to user's company
        if ($clause->company_id !== Auth::user()->company_id) {
            return redirect()->route('settings.contract-clauses')
                ->with('error', 'Clause not found.');
        }
        
        $clauseName = $clause->name;
        
        // Check if clause is used in any templates
        $templatesCount = $clause->templates()->count();
        if ($templatesCount > 0) {
            return redirect()->route('settings.contract-clauses')
                ->with('error', "Cannot delete clause '{$clauseName}' as it's used in {$templatesCount} contract template(s).");
        }
        
        $clause->delete();
        
        return redirect()->route('settings.contract-clauses')
            ->with('success', "Contract clause '{$clauseName}' deleted successfully.");
    }
    
    /**
     * Duplicate a contract clause
     */
    public function duplicateContractClause(\App\Domains\Contract\Models\ContractClause $clause)
    {
        // Ensure clause belongs to user's company
        if ($clause->company_id !== Auth::user()->company_id) {
            return redirect()->route('settings.contract-clauses')
                ->with('error', 'Clause not found.');
        }
        
        $duplicated = $clause->replicate();
        $duplicated->name = $clause->name . ' (Copy)';
        $duplicated->slug = null; // Will be auto-generated
        $duplicated->is_system = false; // Duplicated clauses are never system clauses
        $duplicated->created_by = Auth::id();
        $duplicated->updated_by = null;
        $duplicated->version = '1.0';
        $duplicated->save();
        
        return redirect()->route('settings.contract-clauses')
            ->with('success', "Contract clause duplicated as '{$duplicated->name}'.");
    }
    
    /**
     * Handle bulk actions on contract clauses
     */
    public function bulkActionContractClauses(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|string|in:activate,deactivate,archive,delete',
            'clause_ids' => 'required|array|min:1',
            'clause_ids.*' => 'integer|exists:contract_clauses,id',
        ]);
        
        $company = Auth::user()->company;
        $clauses = \App\Domains\Contract\Models\ContractClause::where('company_id', $company->id)
            ->whereIn('id', $validated['clause_ids']);
        
        // Filter out system clauses if not super admin
        if (!Auth::user()->hasRole('super-admin')) {
            $clauses = $clauses->where('is_system', false);
        }
        
        $clausesList = $clauses->get();
        $count = $clausesList->count();
        
        if ($count === 0) {
            return redirect()->route('settings.contract-clauses')
                ->with('error', 'No clauses were updated. System clauses cannot be modified.');
        }
        
        switch ($validated['action']) {
            case 'activate':
                $clauses->update(['status' => 'active']);
                $message = "{$count} clause(s) activated successfully.";
                break;
            case 'deactivate':
                $clauses->update(['status' => 'inactive']);
                $message = "{$count} clause(s) deactivated successfully.";
                break;
            case 'archive':
                $clauses->update(['status' => 'archived']);
                $message = "{$count} clause(s) archived successfully.";
                break;
            case 'delete':
                // Check for template dependencies
                $inUse = $clausesList->filter(function($clause) {
                    return $clause->templates()->count() > 0;
                })->count();
                
                if ($inUse > 0) {
                    return redirect()->route('settings.contract-clauses')
                        ->with('error', "{$inUse} clause(s) could not be deleted as they are used in contract templates.");
                }
                
                $clauses->delete();
                $message = "{$count} clause(s) deleted successfully.";
                break;
        }
        
        return redirect()->route('settings.contract-clauses')->with('success', $message);
    }
    
    /**
     * Update contract clause content via AJAX
     */
    public function updateContractClauseContent(Request $request, \App\Domains\Contract\Models\ContractClause $clause)
    {
        // Check if user can edit this clause
        if ($clause->is_system && !Auth::user()->hasRole('super-admin')) {
            return response()->json(['error' => 'System clauses cannot be modified.'], 403);
        }
        
        // Ensure clause belongs to user's company
        if ($clause->company_id !== Auth::user()->company_id) {
            return response()->json(['error' => 'Clause not found.'], 404);
        }
        
        $validated = $request->validate([
            'content' => 'required|string|min:1|max:50000',
        ]);
        
        $clause->update([
            'content' => $validated['content'],
            'updated_by' => Auth::id(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Clause content updated successfully.',
            'clause' => [
                'id' => $clause->id,
                'content' => $clause->content,
                'updated_at' => $clause->updated_at->diffForHumans(),
                'updater' => $clause->updater ? $clause->updater->name : null,
            ]
        ]);
    }

    /**
     * Display contract templates for clause management.
     */
    public function contractTemplates(Request $request)
    {
        $company = Auth::user()->company;
        
        // Get templates with clause counts
        $templates = \App\Domains\Contract\Models\ContractTemplate::where('company_id', $company->id)
            ->withCount('clauses')
            ->orderBy('name')
            ->paginate(20);

        return view('settings.contract-templates', compact('templates'));
    }

    /**
     * Display template clause management.
     */
    public function templateClauses(Request $request, \App\Domains\Contract\Models\ContractTemplate $template)
    {
        $company = Auth::user()->company;
        
        // Security check: ensure template belongs to user's company
        if ($template->company_id !== $company->id) {
            abort(404);
        }

        // Get template clauses with pivot data
        $templateClauses = $template->clauses()
            ->withPivot(['sort_order', 'is_required', 'conditions', 'variable_overrides', 'metadata'])
            ->orderByPivot('sort_order')
            ->get();

        // Get available clauses not yet attached to this template
        $availableClauses = \App\Domains\Contract\Models\ContractClause::where('company_id', $company->id)
            ->whereNotIn('id', $templateClauses->pluck('id'))
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        // Get clause categories for filtering
        $categories = \App\Domains\Contract\Models\ContractClause::getAvailableCategories();

        return view('settings.template-clauses', compact(
            'template',
            'templateClauses', 
            'availableClauses',
            'categories'
        ));
    }

    /**
     * Attach clauses to template.
     */
    public function attachTemplateClauses(Request $request, \App\Domains\Contract\Models\ContractTemplate $template)
    {
        $company = Auth::user()->company;
        
        if ($template->company_id !== $company->id) {
            return response()->json(['error' => 'Template not found.'], 404);
        }

        $validated = $request->validate([
            'clause_ids' => 'required|array|min:1',
            'clause_ids.*' => 'required|integer|exists:contract_clauses,id',
            'is_required' => 'nullable|array',
            'is_required.*' => 'boolean',
        ]);

        $attachData = [];
        $maxSortOrder = $template->clauses()->max('contract_template_clauses.sort_order') ?? 0;

        foreach ($validated['clause_ids'] as $index => $clauseId) {
            // Verify clause belongs to company
            $clause = \App\Domains\Contract\Models\ContractClause::where('id', $clauseId)
                ->where('company_id', $company->id)
                ->first();
            
            if (!$clause) {
                continue;
            }

            $attachData[$clauseId] = [
                'sort_order' => $maxSortOrder + $index + 1,
                'is_required' => isset($validated['is_required']) ? ($validated['is_required'][$clauseId] ?? false) : false,
                'conditions' => null,
                'variable_overrides' => null,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $template->clauses()->attach($attachData);

        // Synchronize variables from all attached clauses
        $template->load('clauses'); // Refresh the relationship
        $template->syncVariablesFromClauses();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => count($attachData) . ' clause(s) added to template.',
            ]);
        }

        return redirect()->route('settings.template-clauses', $template)
            ->with('success', count($attachData) . ' clause(s) added to template.');
    }

    /**
     * Detach clause from template.
     */
    public function detachTemplateClause(\App\Domains\Contract\Models\ContractTemplate $template, \App\Domains\Contract\Models\ContractClause $clause)
    {
        $company = Auth::user()->company;
        
        if ($template->company_id !== $company->id || $clause->company_id !== $company->id) {
            return response()->json(['error' => 'Not found.'], 404);
        }

        $template->clauses()->detach($clause->id);

        // Synchronize variables from remaining attached clauses
        $template->load('clauses'); // Refresh the relationship
        $template->syncVariablesFromClauses();

        return response()->json([
            'success' => true,
            'message' => 'Clause removed from template.',
        ]);
    }

    /**
     * Reorder template clauses.
     */
    public function reorderTemplateClauses(Request $request, \App\Domains\Contract\Models\ContractTemplate $template)
    {
        $company = Auth::user()->company;
        
        if ($template->company_id !== $company->id) {
            return response()->json(['error' => 'Template not found.'], 404);
        }

        $validated = $request->validate([
            'clause_ids' => 'required|array|min:1',
            'clause_ids.*' => 'required|integer',
        ]);

        foreach ($validated['clause_ids'] as $index => $clauseId) {
            $template->clauses()->updateExistingPivot($clauseId, [
                'sort_order' => $index + 1,
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Clause order updated.',
        ]);
    }

    /**
     * Update template clause settings.
     */
    public function updateTemplateClause(Request $request, \App\Domains\Contract\Models\ContractTemplate $template, \App\Domains\Contract\Models\ContractClause $clause)
    {
        $company = Auth::user()->company;
        
        if ($template->company_id !== $company->id || $clause->company_id !== $company->id) {
            return response()->json(['error' => 'Not found.'], 404);
        }

        $validated = $request->validate([
            'is_required' => 'required|boolean',
            'conditions' => 'nullable|string|max:1000',
            'variable_overrides' => 'nullable|json',
        ]);

        $updateData = [
            'is_required' => $validated['is_required'],
            'conditions' => $validated['conditions'],
            'updated_at' => now(),
        ];

        if (isset($validated['variable_overrides'])) {
            $updateData['variable_overrides'] = json_decode($validated['variable_overrides'], true);
        }

        $template->clauses()->updateExistingPivot($clause->id, $updateData);

        return response()->json([
            'success' => true,
            'message' => 'Clause settings updated.',
        ]);
    }

    /**
     * Bulk attach clauses to template.
     */
    public function bulkAttachTemplateClauses(Request $request, \App\Domains\Contract\Models\ContractTemplate $template)
    {
        $company = Auth::user()->company;
        
        if ($template->company_id !== $company->id) {
            return response()->json(['error' => 'Template not found.'], 404);
        }

        $validated = $request->validate([
            'clause_ids' => 'required|array|min:1',
            'clause_ids.*' => 'required|integer|exists:contract_clauses,id',
            'bulk_settings' => 'nullable|array',
            'bulk_settings.is_required' => 'nullable|boolean',
        ]);

        // Validate definition dependencies before attaching
        $clausesToAttach = \App\Domains\Contract\Models\ContractClause::whereIn('id', $validated['clause_ids'])
            ->where('company_id', $company->id)
            ->get();
        
        if ($clausesToAttach->count() !== count($validated['clause_ids'])) {
            return response()->json(['error' => 'Some clauses could not be found.'], 400);
        }
        
        // Check for missing definitions
        $definitionRegistry = app(\App\Services\DefinitionRegistryService::class);
        $allRequiredDefinitions = [];
        $definitionWarnings = [];
        
        foreach ($clausesToAttach as $clause) {
            $clauseDefinitions = $clause->getRequiredDefinitions();
            $allRequiredDefinitions = array_merge($allRequiredDefinitions, $clauseDefinitions);
            
            // Check if any required definitions are missing from registry
            $missing = $definitionRegistry->validateDefinitions($clauseDefinitions);
            if (!empty($missing)) {
                $definitionWarnings[] = "Clause '{$clause->name}' requires undefined definitions: " . implode(', ', $missing);
            }
        }

        $attachData = [];
        $maxSortOrder = $template->clauses()->max('sort_order') ?? 0;
        $bulkIsRequired = $validated['bulk_settings']['is_required'] ?? false;

        foreach ($validated['clause_ids'] as $index => $clauseId) {
            // Verify clause belongs to company
            $clause = \App\Domains\Contract\Models\ContractClause::where('id', $clauseId)
                ->where('company_id', $company->id)
                ->first();
            
            if (!$clause) {
                continue;
            }

            $attachData[$clauseId] = [
                'sort_order' => $maxSortOrder + $index + 1,
                'is_required' => $bulkIsRequired,
                'conditions' => null,
                'variable_overrides' => null,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $template->clauses()->attach($attachData);
        
        // Synchronize variables from all attached clauses
        $template->load('clauses'); // Refresh the relationship
        $template->syncVariablesFromClauses();
        
        // Prepare response with warnings if any
        $response = [
            'success' => true,
            'message' => count($attachData) . ' clause(s) added to template.',
            'definition_count' => count(array_unique($allRequiredDefinitions)),
            'required_definitions' => array_unique($allRequiredDefinitions),
        ];
        
        if (!empty($definitionWarnings)) {
            $response['warnings'] = $definitionWarnings;
        }

        return response()->json($response);
    }

    /**
     * Generate template preview with clauses.
     */
    public function previewTemplateWithClauses(Request $request, \App\Domains\Contract\Models\ContractTemplate $template)
    {
        $company = Auth::user()->company;
        
        if ($template->company_id !== $company->id) {
            return response()->json(['error' => 'Template not found.'], 404);
        }

        // Get template clauses in order
        $clauses = $template->clauses()
            ->withPivot(['sort_order', 'is_required', 'conditions'])
            ->orderByPivot('sort_order')
            ->get();

        // Get definition registry service
        $definitionRegistry = app(\App\Services\DefinitionRegistryService::class);
        
        // Analyze required definitions from all clauses
        $requiredDefinitions = [];
        foreach ($clauses as $clause) {
            $clauseDefinitions = $clause->getRequiredDefinitions();
            $requiredDefinitions = array_merge($requiredDefinitions, $clauseDefinitions);
        }
        $requiredDefinitions = array_unique($requiredDefinitions);

        // Generate template header with sample variables
        $sampleVariables = [
            'service_provider_short_name' => 'MSP Company',
            'business_hours' => '8:00 AM to 6:00 PM, Monday through Friday',
            'service_tier' => 'Professional',
            'supported_asset_types' => 'servers, workstations, network equipment, and software applications',
            'client_name' => 'Client Company LLC',
            'service_provider_name' => 'MSP Technology Services Inc.',
            'contract_title' => $template->name,
            'current_date' => now()->format('F j, Y'),
        ];
        
        // Build preview content with proper clause ordering
        $previewContent = $template->template_content;
        
        // Separate clauses by category to determine proper ordering
        $headerClauses = [];
        $definitionsClause = '';
        $otherClauses = [];
        
        foreach ($clauses as $clause) {
            $required = $clause->pivot->is_required ? ' (Required)' : '';
            $clauseSection = "\n\n" . $clause->name . $required . "\n";
            $clauseSection .= str_repeat('-', strlen($clause->name . $required)) . "\n";
            $clauseSection .= $clause->content . "\n";
            
            if ($clause->category === 'header') {
                $headerClauses[] = $clauseSection;
            } elseif ($clause->category === 'definitions') {
                // Skip existing definitions clauses - we'll generate dynamically
                continue;
            } else {
                $otherClauses[] = $clauseSection;
            }
        }
        
        // Generate dynamic definitions section
        $dynamicDefinitionsSection = '';
        if (!empty($requiredDefinitions)) {
            $definitionsContent = $definitionRegistry->generateDefinitionsSection($requiredDefinitions, $sampleVariables);
            $dynamicDefinitionsSection = "\n\nDEFINITIONS (Dynamic)\n" . str_repeat('-', 21) . "\n" . $definitionsContent . "\n";
        }
        
        // Combine clauses in proper order: Header → Definitions → Everything else
        $allClauseContent = implode('', $headerClauses) . $dynamicDefinitionsSection . implode('', $otherClauses);
        
        // If template has clause placeholder, replace it; otherwise append
        if (strpos($previewContent, '{{clauses}}') !== false) {
            $previewContent = str_replace('{{clauses}}', $allClauseContent, $previewContent);
        } else {
            $previewContent .= "\n\n" . "CONTRACT CLAUSES" . "\n" . str_repeat('=', 16) . "\n" . $allClauseContent;
        }

        return response()->json([
            'success' => true,
            'preview' => $previewContent,
            'clause_count' => $clauses->count(),
            'required_clauses' => $clauses->where('pivot.is_required', true)->count(),
            'definition_count' => count($requiredDefinitions),
            'required_definitions' => $requiredDefinitions,
        ]);
    }

    /**
     * Get subscription plans for platform company (AJAX endpoint).
     */
    public function getSubscriptionPlans()
    {
        $plans = \App\Models\SubscriptionPlan::orderBy('sort_order')->orderBy('price_monthly')->get();
        
        return response()->json([
            'plans' => $plans->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'price_monthly' => $plan->price_monthly,
                    'formatted_price' => $plan->getFormattedPrice(),
                    'user_limit' => $plan->user_limit,
                    'user_limit_text' => $plan->getUserLimitText(),
                    'features' => $plan->features,
                    'description' => $plan->description,
                    'is_active' => $plan->is_active,
                    'sort_order' => $plan->sort_order,
                    'stripe_price_id' => $plan->stripe_price_id,
                ];
            })
        ]);
    }

    /**
     * Store a new subscription plan.
     */
    public function storeSubscriptionPlan(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'price_monthly' => 'required|numeric|min:0',
            'user_limit' => 'nullable|integer|min:1',
            'features' => 'nullable|array',
            'description' => 'nullable|string|max:500',
            'stripe_price_id' => 'required|string|unique:subscription_plans,stripe_price_id',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        try {
            $plan = \App\Models\SubscriptionPlan::create([
                'name' => $request->name,
                'price_monthly' => $request->price_monthly,
                'user_limit' => $request->user_limit,
                'features' => $request->features,
                'description' => $request->description,
                'stripe_price_id' => $request->stripe_price_id,
                'is_active' => $request->boolean('is_active', true),
                'sort_order' => $request->integer('sort_order', 0),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription plan created successfully.',
                'plan' => $plan,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscription plan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing subscription plan.
     */
    public function updateSubscriptionPlan(Request $request, \App\Models\SubscriptionPlan $plan)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'price_monthly' => 'required|numeric|min:0',
            'user_limit' => 'nullable|integer|min:1',
            'features' => 'nullable|array',
            'description' => 'nullable|string|max:500',
            'stripe_price_id' => 'required|string|unique:subscription_plans,stripe_price_id,' . $plan->id,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        try {
            $plan->update([
                'name' => $request->name,
                'price_monthly' => $request->price_monthly,
                'user_limit' => $request->user_limit,
                'features' => $request->features,
                'description' => $request->description,
                'stripe_price_id' => $request->stripe_price_id,
                'is_active' => $request->boolean('is_active', true),
                'sort_order' => $request->integer('sort_order', 0),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription plan updated successfully.',
                'plan' => $plan->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subscription plan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete (deactivate) a subscription plan.
     */
    public function deleteSubscriptionPlan(\App\Models\SubscriptionPlan $plan)
    {
        try {
            // Check if plan is in use by any active subscriptions
            $activeSubscriptions = \App\Models\Client::where('subscription_plan_id', $plan->id)
                ->whereIn('subscription_status', ['active', 'trialing'])
                ->count();

            if ($activeSubscriptions > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete plan that has active subscriptions. Consider deactivating it instead.',
                ], 400);
            }

            // Deactivate rather than delete to preserve historical data
            $plan->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription plan deactivated successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete subscription plan: ' . $e->getMessage(),
            ], 500);
        }
    }
    
}