<?php

namespace App\Domains\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Silber\Bouncer\BouncerFacade as Bouncer;

/**
 * SetupWizardController
 *
 * Handles the initial setup of the Nestogy ERP system when no companies exist.
 * Creates the first company and admin user to get the system bootstrapped.
 */
class SetupWizardController extends Controller
{
    /**
     * Show the setup wizard welcome page.
     */
    public function index()
    {
        // If companies already exist, redirect to login
        if (Company::exists()) {
            return redirect()->route('login');
        }

        return view('setup.welcome');
    }

    /**
     * Show the company setup form.
     */
    public function showSetup()
    {
        // If companies already exist, redirect to login
        if (Company::exists()) {
            return redirect()->route('login');
        }

        return view('setup.company-form');
    }

    /**
     * Process the initial company and admin user setup.
     */
    public function processSetup(Request $request)
    {
        // Double-check that no companies exist
        if (Company::exists()) {
            return redirect()->route('login')->with('info', 'System has already been set up.');
        }

        // Validate the setup data
        $validator = $this->validateSetupData($request);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Step 1: Create the first company
            $company = $this->createFirstCompany($request->all());

            // Step 2: Create the admin user
            $user = $this->createAdminUser($company, $request->all());

            // Step 3: Assign admin role using Bouncer
            $this->assignAdminRole($user);

            // Step 4: Create company settings with all configuration
            $this->createCompanySettings($company, $request->all());

            DB::commit();

            Log::info('Initial system setup completed successfully', [
                'company_id' => $company->id,
                'user_id' => $user->id,
                'admin_email' => $user->email,
            ]);

            // Log in the admin user and redirect to dashboard
            auth()->login($user);

            return redirect()->route('dashboard')->with('success',
                'Welcome to Nestogy! Your ERP system has been successfully initialized. You can now start adding clients and managing your MSP business.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Initial system setup failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->except(['admin_password', 'admin_password_confirmation']),
            ]);

            return redirect()->back()
                ->withErrors(['setup' => 'Setup failed: '.$e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Validate the setup data.
     */
    protected function validateSetupData(Request $request)
    {
        return Validator::make($request->all(), [
            // Company information
            'company_name' => 'required|string|max:255',
            'company_email' => 'required|email|max:255',
            'company_phone' => 'nullable|string|max:20',
            'company_address' => 'nullable|string|max:255',
            'company_city' => 'nullable|string|max:100',
            'company_state' => 'nullable|string|max:100',
            'company_zip' => 'nullable|string|max:20',
            'company_country' => 'nullable|string|max:100',
            'company_website' => 'nullable|url|max:255',
            'currency' => 'required|string|size:3|in:'.implode(',', array_keys(Company::SUPPORTED_CURRENCIES)),

            // Admin user information
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255|unique:users,email',
            'admin_password' => ['required', 'confirmed', Password::defaults()],

            // SMTP Configuration (optional)
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_encryption' => 'nullable|in:tls,ssl',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'mail_from_email' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',

            // System Preferences
            'timezone' => 'required|string|max:255',
            'date_format' => 'nullable|string|max:20',
            'theme' => 'nullable|string|in:'.implode(',', array_keys(\App\Models\Setting::getAvailableThemes())),
            'company_language' => 'nullable|string|size:2',
            'default_net_terms' => 'nullable|integer|min:0|max:365',
            'default_hourly_rate' => 'nullable|numeric|min:0|max:9999.99',

            // Module Selections
            'module_ticketing' => 'nullable|boolean',
            'module_invoicing' => 'nullable|boolean',
            'module_assets' => 'nullable|boolean',
            'module_projects' => 'nullable|boolean',
            'module_contracts' => 'nullable|boolean',
            'module_reporting' => 'nullable|boolean',

            // MSP Business Settings
            'business_hours_start' => 'nullable|date_format:H:i',
            'business_hours_end' => 'nullable|date_format:H:i',
            'rate_standard' => 'nullable|numeric|min:0|max:9999.99',
            'rate_after_hours' => 'nullable|numeric|min:0|max:9999.99',
            'rate_emergency' => 'nullable|numeric|min:0|max:9999.99',
            'rate_weekend' => 'nullable|numeric|min:0|max:9999.99',
            'rate_holiday' => 'nullable|numeric|min:0|max:9999.99',
            'minimum_billing_increment' => 'nullable|numeric|in:0.25,0.5,1',
            'time_rounding_method' => 'nullable|string|in:nearest,up,down',
            'ticket_prefix' => 'nullable|string|max:10',
            'ticket_autoclose_hours' => 'nullable|integer|min:1|max:8760',
            'invoice_prefix' => 'nullable|string|max:10',
            'invoice_starting_number' => 'nullable|integer|min:1',
            'invoice_late_fee_percent' => 'nullable|numeric|min:0|max:100',

            // Security Options
            'enable_two_factor' => 'nullable|boolean',
            'enable_audit_logging' => 'nullable|boolean',
        ], [
            'company_name.required' => 'Company name is required to set up your ERP system.',
            'company_email.required' => 'Company email is required.',
            'admin_name.required' => 'Administrator name is required.',
            'admin_email.required' => 'Administrator email is required.',
            'admin_email.unique' => 'This email address is already in use.',
            'admin_password.required' => 'Administrator password is required.',
            'admin_password.confirmed' => 'Password confirmation does not match.',
            'currency.required' => 'Please select your default currency.',
            'timezone.required' => 'Please select your timezone.',
            'smtp_port.integer' => 'SMTP port must be a valid number.',
            'smtp_port.min' => 'SMTP port must be at least 1.',
            'smtp_port.max' => 'SMTP port cannot exceed 65535.',
            'default_net_terms.max' => 'Payment terms cannot exceed 365 days.',
            'default_hourly_rate.numeric' => 'Hourly rate must be a valid number.',
            'invoice_starting_number.min' => 'Invoice starting number must be at least 1.',
        ]);
    }

    /**
     * Create the first company.
     */
    protected function createFirstCompany(array $data): Company
    {
        return Company::create([
            'name' => $data['company_name'],
            'email' => $data['company_email'],
            'phone' => $data['company_phone'] ?? null,
            'address' => $data['company_address'] ?? null,
            'city' => $data['company_city'] ?? null,
            'state' => $data['company_state'] ?? null,
            'zip' => $data['company_zip'] ?? null,
            'country' => $data['company_country'] ?? 'United States',
            'website' => $data['company_website'] ?? null,
            'currency' => $data['currency'],
            'company_type' => 'root', // This is the root company
            'organizational_level' => 0,
            'can_create_subsidiaries' => true,
            'max_subsidiary_depth' => 5,
        ]);
    }

    /**
     * Create the admin user.
     */
    protected function createAdminUser(Company $company, array $data): User
    {
        // Create the user (unverified)
        $user = User::create([
            'company_id' => $company->id,
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'password' => Hash::make($data['admin_password']),
            'status' => true,
        ]);

        // Send email verification if SMTP is configured
        if (! empty($data['smtp_host'])) {
            // Dynamically configure mail settings for this request
            $this->configureSmtpForVerification($data);
            $user->sendEmailVerificationNotification();

            Log::info('Email verification sent during setup', [
                'user_id' => $user->id,
                'email' => $user->email,
                'smtp_host' => $data['smtp_host'],
            ]);
        } else {
            Log::info('Email verification skipped - no SMTP configured during setup', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        }

        // Create user settings with admin role
        UserSetting::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'role' => UserSetting::ROLE_ADMIN,
            'records_per_page' => 25,
            'dashboard_financial_enable' => true,
            'dashboard_technical_enable' => true,
        ]);

        return $user;
    }

    /**
     * Assign admin role using Bouncer.
     */
    protected function assignAdminRole(User $user): void
    {
        // Set Bouncer scope to the user's company
        Bouncer::scope()->to($user->company_id);

        // Create the admin role if it doesn't exist
        $adminRole = Bouncer::role()->firstOrCreate([
            'name' => 'admin',
            'title' => 'Administrator',
        ]);

        // Assign the role to the user
        $user->assign('admin');

        // Grant all basic permissions to admin role
        $permissions = [
            // Domain access - using plural forms to match policies
            'clients.view', 'clients.create', 'clients.edit', 'clients.delete', 'clients.manage',
            'clients.export', 'clients.import',
            'clients.contacts.view', 'clients.contacts.manage', 'clients.contacts.export',
            'clients.locations.view', 'clients.locations.manage', 'clients.locations.export',
            'clients.documents.view', 'clients.documents.manage', 'clients.documents.export',
            'clients.files.view', 'clients.files.manage', 'clients.files.export',

            'tickets.view', 'tickets.create', 'tickets.edit', 'tickets.delete',
            'assets.view', 'assets.create', 'assets.edit', 'assets.delete',
            'financial.view', 'financial.create', 'financial.edit', 'financial.delete',
            'projects.view', 'projects.create', 'projects.edit', 'projects.delete',
            'reports.view', 'reports.create', 'reports.edit', 'reports.delete',
            'integrations.view', 'integrations.create', 'integrations.edit', 'integrations.delete',
            'security.view', 'security.create', 'security.edit', 'security.delete',
            'knowledge.view', 'knowledge.create', 'knowledge.edit', 'knowledge.delete',
            'leads.view', 'leads.create', 'leads.edit', 'leads.delete',
            'marketing.view', 'marketing.create', 'marketing.edit', 'marketing.delete',

            // System administration
            'users.manage', 'settings.manage', 'company.manage',
            'contracts.view', 'contracts.create', 'contracts.edit', 'contracts.delete',
        ];

        foreach ($permissions as $permission) {
            // Create ability if it doesn't exist
            $ability = Bouncer::ability()->firstOrCreate([
                'name' => $permission,
                'title' => ucwords(str_replace('.', ' ', $permission)),
            ]);

            // Allow admin role to perform this ability
            Bouncer::allow('admin')->to($permission);
        }

        Log::info('Admin role and permissions assigned', [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'permissions_count' => count($permissions),
        ]);
    }

    /**
     * Create company settings with all configuration.
     */
    protected function createCompanySettings(Company $company, array $data): void
    {
        $settingsData = [
            'company_id' => $company->id,
            'current_database_version' => '1.0.0',
            'start_page' => 'dashboard',

            // Regional & System Preferences
            'timezone' => $data['timezone'] ?? 'America/New_York',
            'date_format' => $data['date_format'] ?? 'Y-m-d',
            'theme' => $data['theme'] ?? 'blue',
            'company_language' => $data['company_language'] ?? 'en',
            'company_currency' => $data['currency'],

            // Default Financial Settings
            'default_net_terms' => $data['default_net_terms'] ?? 30,
            'default_hourly_rate' => $data['default_hourly_rate'] ?? 150.00,

            // SMTP Configuration
            'smtp_host' => $data['smtp_host'] ?? null,
            'smtp_port' => $data['smtp_port'] ?? null,
            'smtp_encryption' => $data['smtp_encryption'] ?? null,
            'smtp_username' => $data['smtp_username'] ?? null,
            'smtp_password' => ! empty($data['smtp_password']) ? $data['smtp_password'] : null, // Will be encrypted in model
            'mail_from_email' => $data['mail_from_email'] ?? $data['company_email'],
            'mail_from_name' => $data['mail_from_name'] ?? $data['company_name'],

            // Business Hours
            'business_hours' => ! empty($data['business_hours_start']) && ! empty($data['business_hours_end']) ? [
                'start' => $data['business_hours_start'],
                'end' => $data['business_hours_end'],
                'timezone' => $data['timezone'] ?? 'America/New_York',
            ] : [
                'start' => '08:00',
                'end' => '18:00',
                'timezone' => $data['timezone'] ?? 'America/New_York',
            ],

            // MSP Billing Rates (stored as arrays for the Settings model)
            'time_tracking_settings' => [
                'rates' => [
                    'standard' => $data['rate_standard'] ?? 150.00,
                    'after_hours' => $data['rate_after_hours'] ?? 225.00,
                    'emergency' => $data['rate_emergency'] ?? 300.00,
                    'weekend' => $data['rate_weekend'] ?? 200.00,
                    'holiday' => $data['rate_holiday'] ?? 250.00,
                ],
                'minimum_billing_increment' => $data['minimum_billing_increment'] ?? 0.25,
                'time_rounding_method' => $data['time_rounding_method'] ?? 'nearest',
            ],

            // Time Tracking
            'time_tracking_enabled' => true,
            'default_minimum_billing_increment' => $data['minimum_billing_increment'] ?? 0.25,
            'default_time_rounding_method' => $data['time_rounding_method'] ?? 'nearest',

            // Ticket Settings
            'ticket_prefix' => $data['ticket_prefix'] ?? 'TKT-',
            'ticket_next_number' => 1,
            'ticket_autoclose' => ! empty($data['ticket_autoclose_hours']),
            'ticket_autoclose_hours' => $data['ticket_autoclose_hours'] ?? 72,
            'ticket_email_parse' => false,
            'ticket_client_general_notifications' => true,

            // Invoice Settings
            'invoice_prefix' => $data['invoice_prefix'] ?? 'INV-',
            'invoice_next_number' => $data['invoice_starting_number'] ?? 1000,
            'invoice_late_fee_enable' => ! empty($data['invoice_late_fee_percent']),
            'invoice_late_fee_percent' => $data['invoice_late_fee_percent'] ?? 0,
            'recurring_auto_send_invoice' => true,
            'send_invoice_reminders' => true,

            // Quote Settings
            'quote_prefix' => 'QUO-',
            'quote_next_number' => 1000,

            // Module Settings
            'module_enable_ticketing' => ! empty($data['module_ticketing']),
            'module_enable_accounting' => ! empty($data['module_invoicing']),
            'module_enable_itdoc' => true, // Always enable core documentation

            // Security Settings
            'two_factor_enabled' => ! empty($data['enable_two_factor']),
            'audit_logging_enabled' => ! empty($data['enable_audit_logging']) || true, // Default to enabled
            'audit_retention_days' => 365,
            'password_min_length' => 8,
            'password_require_special' => true,
            'password_require_numbers' => true,
            'password_require_uppercase' => true,
            'password_expiry_days' => 90,
            'session_timeout_minutes' => 480,

            // System Settings
            'telemetry' => false,
            'destructive_deletes_enable' => false,
            'enable_cron' => true,
            'enable_alert_domain_expire' => true,

            // Client Portal Settings
            'client_portal_enable' => true,
            'portal_self_service_tickets' => true,
            'portal_knowledge_base_access' => true,
            'portal_invoice_access' => true,
            'portal_payment_processing' => false,
            'portal_asset_visibility' => true,
        ];

        // Update the existing settings record (created by Company model observer)
        $existingSettings = \App\Models\Setting::where('company_id', $company->id)->first();

        if ($existingSettings) {
            $existingSettings->update($settingsData);
        } else {
            // Fallback: create new settings if none exist (shouldn't happen normally)
            \App\Models\Setting::create($settingsData);
        }

        Log::info('Company settings created during setup', [
            'company_id' => $company->id,
            'smtp_configured' => ! empty($data['smtp_host']),
            'modules_enabled' => array_filter([
                'ticketing' => ! empty($data['module_ticketing']),
                'invoicing' => ! empty($data['module_invoicing']),
                'assets' => ! empty($data['module_assets']),
                'projects' => ! empty($data['module_projects']),
                'contracts' => ! empty($data['module_contracts']),
                'reporting' => ! empty($data['module_reporting']),
            ]),
        ]);
    }

    /**
     * Test SMTP settings during setup wizard.
     */
    public function testSmtp(Request $request)
    {
        // Validate SMTP fields
        $validator = Validator::make($request->all(), [
            'smtp_host' => 'required|string|max:255',
            'smtp_port' => 'required|integer|min:1|max:65535',
            'smtp_encryption' => 'nullable|in:tls,ssl',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'mail_from_email' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
            'company_email' => 'required|email|max:255',
            'company_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please fill in all required SMTP fields: '.implode(', ', $validator->errors()->all()),
            ]);
        }

        try {
            // Configure SMTP settings temporarily
            $this->configureSmtpForVerification($request->all());

            // Create a test email
            $testEmail = $request->input('mail_from_email') ?: $request->input('company_email');
            $companyName = $request->input('company_name');

            // Send test email
            \Mail::raw(
                "This is a test email from your Nestogy ERP setup wizard.\n\n".
                "If you receive this email, your SMTP configuration is working correctly!\n\n".
                "Company: {$companyName}\n".
                'Test sent at: '.now()->format('Y-m-d H:i:s T'),
                function ($message) use ($testEmail, $companyName) {
                    $message->to($testEmail)
                        ->subject("SMTP Test - {$companyName} ERP Setup");
                }
            );

            return response()->json([
                'success' => true,
                'message' => "Test email sent successfully to {$testEmail}! Check your inbox to confirm SMTP is working.",
            ]);

        } catch (\Exception $e) {
            Log::error('SMTP test failed during setup', [
                'error' => $e->getMessage(),
                'smtp_host' => $request->input('smtp_host'),
                'smtp_port' => $request->input('smtp_port'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'SMTP test failed: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Dynamically configure SMTP settings for email verification during setup.
     */
    protected function configureSmtpForVerification(array $data): void
    {
        // Dynamically update mail configuration for this request
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $data['smtp_host'],
            'mail.mailers.smtp.port' => $data['smtp_port'] ?? 587,
            'mail.mailers.smtp.username' => $data['smtp_username'] ?? null,
            'mail.mailers.smtp.password' => $data['smtp_password'] ?? null,
            'mail.mailers.smtp.encryption' => $data['smtp_encryption'] ?? 'tls',
            'mail.from.address' => $data['mail_from_email'] ?? $data['company_email'],
            'mail.from.name' => $data['mail_from_name'] ?? $data['company_name'],
        ]);

        // Clear any cached mail configuration
        app()->forgetInstance('mail.manager');
        app()->forgetInstance('swift.mailer');
    }

    /**
     * Check if the system needs setup.
     */
    public function needsSetup(): bool
    {
        return ! Company::exists();
    }
}
