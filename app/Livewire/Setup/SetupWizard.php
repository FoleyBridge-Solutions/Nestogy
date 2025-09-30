<?php

namespace App\Livewire\Setup;

use App\Livewire\Setup\Traits\ManagesStepData;
use App\Livewire\Setup\Traits\ValidatesSteps;
use App\Models\Company;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Silber\Bouncer\BouncerFacade as Bouncer;

class SetupWizard extends Component
{
    use ManagesStepData, ValidatesSteps;

    // Step management
    public int $currentStep = 1;

    public int $totalSteps = 5;

    public array $completedSteps = [];

    // Livewire validation rules (required for validateOnly)
    protected $rules = [
        'company_name' => 'required|string|max:255',
        'company_email' => 'required|email|max:255',
        'currency' => 'required|string|size:3',
        'company_phone' => 'nullable|string|max:20',
        'company_address' => 'nullable|string|max:255',
        'company_city' => 'nullable|string|max:100',
        'company_state' => 'nullable|string|max:100',
        'company_zip' => 'nullable|string|max:20',
        'company_country' => 'nullable|string|max:100',
        'company_website' => 'nullable|url|max:255',
    ];

    // Step 1: Company Information
    public string $company_name = '';

    public string $company_email = '';

    public string $company_phone = '';

    public string $company_address = '';

    public string $company_city = '';

    public string $company_state = '';

    public string $company_zip = '';

    public string $company_country = 'United States';

    public string $company_website = '';

    public string $currency = 'USD';

    // Step 2: Email Configuration
    public string $smtp_host = '';

    public string $smtp_port = '587';

    public string $smtp_encryption = 'tls';

    public string $smtp_username = '';

    public string $smtp_password = '';

    public string $mail_from_email = '';

    public string $mail_from_name = '';

    public bool $smtp_testing = false;

    public string $smtp_test_message = '';

    public bool $smtp_test_success = false;

    // Step 3: System Preferences
    public string $timezone = 'America/New_York';

    public string $date_format = 'Y-m-d';

    public string $theme = 'blue';

    public string $company_language = 'en';

    public int $default_net_terms = 30;

    public float $default_hourly_rate = 150.00;

    public array $modules = [
        'ticketing' => true,
        'invoicing' => true,
        'assets' => true,
        'projects' => true,
        'contracts' => true,
        'reporting' => true,
    ];

    // Step 4: MSP Business Settings
    public string $business_hours_start = '09:00';

    public string $business_hours_end = '17:00';

    public float $rate_standard = 150.00;

    public float $rate_after_hours = 225.00;

    public float $rate_emergency = 300.00;

    public float $rate_weekend = 200.00;

    public float $rate_holiday = 250.00;

    public float $minimum_billing_increment = 0.25;

    public string $time_rounding_method = 'nearest';

    public string $ticket_prefix = 'TKT-';

    public int $ticket_autoclose_hours = 72;

    public string $invoice_prefix = 'INV-';

    public int $invoice_starting_number = 1000;

    public float $invoice_late_fee_percent = 1.5;

    // Step 5: Administrator Account
    public string $admin_name = '';

    public string $admin_email = '';

    public string $admin_password = '';

    public string $admin_password_confirmation = '';

    public bool $enable_two_factor = false;

    public bool $enable_audit_logging = true;

    public function mount()
    {
        // Redirect if companies already exist
        if (Company::exists()) {
            return redirect()->route('login');
        }

        $this->loadDefaults();
        $this->loadStepData();
    }

    public function updated($propertyName)
    {
        // Auto-populate mail_from_email and mail_from_name if empty
        if ($propertyName === 'company_email' && empty($this->mail_from_email)) {
            $this->mail_from_email = $this->company_email;
        }

        if ($propertyName === 'company_name' && empty($this->mail_from_name)) {
            $this->mail_from_name = $this->company_name;
        }
    }

    public function nextStep()
    {
        try {
            // Simple validation for step 1
            if ($this->currentStep === 1) {
                if (empty($this->company_name) || empty($this->company_email)) {
                    session()->flash('error', 'Please fill out Company Name and Company Email.');

                    return;
                }
            }

            $this->saveStepData();

            // Mark current step as completed
            if (! in_array($this->currentStep, $this->completedSteps)) {
                $this->completedSteps[] = $this->currentStep;
            }

            if ($this->currentStep < $this->totalSteps) {
                $this->currentStep++;
                session()->flash('success', 'Step completed successfully!');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->saveStepData();
            $this->currentStep--;
        }
    }

    public function goToStep($step)
    {
        if ($this->canNavigateToStep($step)) {
            $this->saveStepData();
            $this->currentStep = $step;
        }
    }

    public function testSmtpConnection()
    {
        $this->smtp_testing = true;
        $this->smtp_test_message = '';
        $this->smtp_test_success = false;

        try {
            // Validate required SMTP fields
            $this->validate([
                'smtp_host' => 'required|string',
                'smtp_port' => 'required|integer',
                'company_email' => 'required|email',
            ]);

            // Configure mail temporarily
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $this->smtp_host,
                'mail.mailers.smtp.port' => $this->smtp_port,
                'mail.mailers.smtp.username' => $this->smtp_username,
                'mail.mailers.smtp.password' => $this->smtp_password,
                'mail.mailers.smtp.encryption' => $this->smtp_encryption,
                'mail.from.address' => $this->mail_from_email ?: $this->company_email,
                'mail.from.name' => $this->mail_from_name ?: $this->company_name,
            ]);

            // Clear mail manager cache
            app()->forgetInstance('mail.manager');

            $testEmail = $this->mail_from_email ?: $this->company_email;

            // Send test email
            Mail::raw(
                "This is a test email from your Nestogy ERP setup wizard.\n\n".
                "If you receive this email, your SMTP configuration is working correctly!\n\n".
                "Company: {$this->company_name}\n".
                'Test sent at: '.now()->format('Y-m-d H:i:s T'),
                function ($message) use ($testEmail) {
                    $message->to($testEmail)
                        ->subject("SMTP Test - {$this->company_name} ERP Setup");
                }
            );

            $this->smtp_test_success = true;
            $this->smtp_test_message = "Test email sent successfully to {$testEmail}! Check your inbox to confirm SMTP is working.";

        } catch (\Exception $e) {
            $this->smtp_test_success = false;
            $this->smtp_test_message = 'SMTP test failed: '.$e->getMessage();

            Log::error('SMTP test failed during setup', [
                'error' => $e->getMessage(),
                'smtp_host' => $this->smtp_host,
                'smtp_port' => $this->smtp_port,
            ]);
        } finally {
            $this->smtp_testing = false;
        }
    }

    public function completeSetup()
    {
        // Validate final step
        if (! $this->validateCurrentStep()) {
            return;
        }

        try {
            DB::beginTransaction();

            // Create the first company
            $company = $this->createFirstCompany();

            // Create the admin user
            $user = $this->createAdminUser($company);

            // Assign admin role using Bouncer
            $this->assignAdminRole($user);

            // Create company settings
            $this->createCompanySettings($company);

            DB::commit();

            // Clear setup session
            $this->clearSetupSession();

            Log::info('Initial system setup completed successfully', [
                'company_id' => $company->id,
                'user_id' => $user->id,
                'admin_email' => $user->email,
            ]);

            // Log in the admin user and redirect to dashboard
            auth()->login($user);

            session()->flash('success',
                'Welcome to Nestogy! Your ERP system has been successfully initialized. You can now start adding clients and managing your MSP business.');

            return redirect()->route('dashboard');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Initial system setup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            session()->flash('error', 'Setup failed: '.$e->getMessage());
        }
    }

    protected function createFirstCompany(): Company
    {
        return Company::create([
            'name' => $this->company_name,
            'email' => $this->company_email,
            'phone' => $this->company_phone ?: null,
            'address' => $this->company_address ?: null,
            'city' => $this->company_city ?: null,
            'state' => $this->company_state ?: null,
            'zip' => $this->company_zip ?: null,
            'country' => $this->company_country,
            'website' => $this->company_website ?: null,
            'currency' => $this->currency,
            'company_type' => 'root',
            'organizational_level' => 0,
            'can_create_subsidiaries' => true,
            'max_subsidiary_depth' => 5,
        ]);
    }

    protected function createAdminUser(Company $company): User
    {
        $user = User::create([
            'company_id' => $company->id,
            'name' => $this->admin_name,
            'email' => $this->admin_email,
            'password' => Hash::make($this->admin_password),
            'status' => true,
        ]);

        // Create user settings
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

    protected function assignAdminRole(User $user): void
    {
        Bouncer::scope()->to($user->company_id);

        $adminRole = Bouncer::role()->firstOrCreate([
            'name' => 'admin',
            'title' => 'Administrator',
        ]);

        $user->assign('admin');

        // Grant comprehensive permissions
        $permissions = [
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
            'users.manage', 'settings.manage', 'company.manage',
            'contracts.view', 'contracts.create', 'contracts.edit', 'contracts.delete',
        ];

        foreach ($permissions as $permission) {
            Bouncer::ability()->firstOrCreate([
                'name' => $permission,
                'title' => ucwords(str_replace('.', ' ', $permission)),
            ]);

            Bouncer::allow('admin')->to($permission);
        }
    }

    protected function createCompanySettings(Company $company): void
    {
        $settingsData = [
            'company_id' => $company->id,
            'current_database_version' => '1.0.0',
            'start_page' => 'dashboard',
            'timezone' => $this->timezone,
            'date_format' => $this->date_format,
            'theme' => $this->theme,
            'company_language' => $this->company_language,
            'company_currency' => $this->currency,
            'default_net_terms' => $this->default_net_terms,
            'default_hourly_rate' => $this->default_hourly_rate,
            'smtp_host' => $this->smtp_host ?: null,
            'smtp_port' => $this->smtp_port ?: null,
            'smtp_encryption' => $this->smtp_encryption ?: null,
            'smtp_username' => $this->smtp_username ?: null,
            'smtp_password' => $this->smtp_password ?: null,
            'mail_from_email' => $this->mail_from_email ?: $this->company_email,
            'mail_from_name' => $this->mail_from_name ?: $this->company_name,
            'business_hours' => [
                'start' => $this->business_hours_start,
                'end' => $this->business_hours_end,
                'timezone' => $this->timezone,
            ],
            'time_tracking_settings' => [
                'rates' => [
                    'standard' => $this->rate_standard,
                    'after_hours' => $this->rate_after_hours,
                    'emergency' => $this->rate_emergency,
                    'weekend' => $this->rate_weekend,
                    'holiday' => $this->rate_holiday,
                ],
                'minimum_billing_increment' => $this->minimum_billing_increment,
                'time_rounding_method' => $this->time_rounding_method,
            ],
            'time_tracking_enabled' => true,
            'default_minimum_billing_increment' => $this->minimum_billing_increment,
            'default_time_rounding_method' => $this->time_rounding_method,
            'ticket_prefix' => $this->ticket_prefix,
            'ticket_next_number' => 1,
            'ticket_autoclose' => ! empty($this->ticket_autoclose_hours),
            'ticket_autoclose_hours' => $this->ticket_autoclose_hours,
            'ticket_email_parse' => false,
            'ticket_client_general_notifications' => true,
            'invoice_prefix' => $this->invoice_prefix,
            'invoice_next_number' => $this->invoice_starting_number,
            'invoice_late_fee_enable' => ! empty($this->invoice_late_fee_percent),
            'invoice_late_fee_percent' => $this->invoice_late_fee_percent,
            'recurring_auto_send_invoice' => true,
            'send_invoice_reminders' => true,
            'quote_prefix' => 'QUO-',
            'quote_next_number' => 1000,
            'module_enable_ticketing' => $this->modules['ticketing'] ?? false,
            'module_enable_accounting' => $this->modules['invoicing'] ?? false,
            'module_enable_itdoc' => true,
            'two_factor_enabled' => $this->enable_two_factor,
            'audit_logging_enabled' => $this->enable_audit_logging,
            'audit_retention_days' => 365,
            'password_min_length' => 8,
            'password_require_special' => true,
            'password_require_numbers' => true,
            'password_require_uppercase' => true,
            'password_expiry_days' => 90,
            'session_timeout_minutes' => 480,
            'telemetry' => false,
            'destructive_deletes_enable' => false,
            'enable_cron' => true,
            'enable_alert_domain_expire' => true,
            'client_portal_enable' => true,
            'portal_self_service_tickets' => true,
            'portal_knowledge_base_access' => true,
            'portal_invoice_access' => true,
            'portal_payment_processing' => false,
            'portal_asset_visibility' => true,
        ];

        $existingSettings = Setting::where('company_id', $company->id)->first();

        if ($existingSettings) {
            $existingSettings->update($settingsData);
        } else {
            Setting::create($settingsData);
        }
    }

    public function dehydrate()
    {
        // Save progress on each request
        $this->saveStepData();
    }

    public function render()
    {
        return view('livewire.setup.setup-wizard')
            ->layout('layouts.setup-livewire');
    }
}
