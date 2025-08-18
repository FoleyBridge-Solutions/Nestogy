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
use App\Models\Client;
use App\Models\Company;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    protected SettingsService $settingsService;
    protected EmailConnectionTestService $emailTestService;
    protected DynamicMailConfigService $mailConfigService;
    protected SLAService $slaService;
    
    public function __construct(SettingsService $settingsService, EmailConnectionTestService $emailTestService, DynamicMailConfigService $mailConfigService, SLAService $slaService)
    {
        $this->settingsService = $settingsService;
        $this->emailTestService = $emailTestService;
        $this->mailConfigService = $mailConfigService;
        $this->slaService = $slaService;
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
     * Update general settings
     */
    public function updateGeneral(GeneralSettingsRequest $request)
    {
        $company = Auth::user()->company;
        $setting = $this->getOrCreateSettings($company);
        
        $success = $this->settingsService->updateGeneralSettings($setting, $request->validated());
        
        if ($success) {
            return redirect()->route('settings.general')
                ->with('success', 'General settings updated successfully.');
        }
        
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
        
        return view('settings.security', compact('company', 'setting'));
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
        
        return view('settings.email', compact('company', 'setting'));
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
            'smtp_username' => 'required|string|max:255',
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
                'smtp_username', 'smtp_password', 
                'mail_from_email', 'mail_from_name'
            ]);
            
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
            $presets = $this->emailTestService->getCommonProviderPresets();
            
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
        
        $success = $this->settingsService->updateBillingFinancialSettings($setting, $request->validated());
        
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
        
        $setting->update($request->only([
            'stripe_settings', 'paypal_settings', 'square_settings',
            'authorize_net_settings', 'payment_gateway_settings',
            'recurring_billing_enabled', 'auto_payment_retry_enabled'
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
}