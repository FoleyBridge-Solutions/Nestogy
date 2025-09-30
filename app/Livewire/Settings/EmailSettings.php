<?php

namespace App\Livewire\Settings;

use App\Domains\Core\Services\Settings\CommunicationSettingsService;
use Livewire\Attributes\Validate;
use Livewire\Component;

class EmailSettings extends Component
{
    public $driver = 'smtp';
    public $from_email = '';
    public $from_name = '';
    public $reply_to = '';
    
    // SMTP fields
    public $smtp_host = '';
    public $smtp_port = 587;
    public $smtp_username = '';
    public $smtp_password = '';
    public $smtp_encryption = 'tls';
    
    // API fields
    public $api_key = '';
    public $api_domain = '';
    
    // Features
    public $track_opens = true;
    public $track_clicks = true;
    public $auto_retry_failed = true;
    public $max_retry_attempts = 3;
    
    // Test email
    public $test_email = '';
    
    protected CommunicationSettingsService $service;
    
    public function boot(CommunicationSettingsService $service)
    {
        $this->service = $service;
    }
    
    public function mount()
    {
        $this->test_email = auth()->user()->email;
        
        // Load existing settings
        $settings = $this->service->getSettings('email');
        
        if (!empty($settings)) {
            $this->fill($settings);
        }
    }
    
    protected function rules()
    {
        $rules = [
            'driver' => 'required|in:smtp,smtp2go,mailgun,sendgrid,ses,postmark,log',
            'from_email' => 'required|email',
            'from_name' => 'required|string|max:255',
            'reply_to' => 'nullable|email',
            'smtp_host' => 'required_if:driver,smtp|nullable|string',
            'smtp_port' => 'required_if:driver,smtp|nullable|integer|between:1,65535',
            'smtp_username' => 'required_if:driver,smtp|nullable|string',
            'smtp_password' => 'nullable|string',
            'smtp_encryption' => 'nullable|in:tls,ssl,none',
            'api_key' => 'required_if:driver,smtp2go,mailgun,sendgrid,ses,postmark|nullable|string',
            'api_domain' => 'nullable|string',
            'track_opens' => 'boolean',
            'track_clicks' => 'boolean',
            'auto_retry_failed' => 'boolean',
            'max_retry_attempts' => 'nullable|integer|between:1,10',
            'test_email' => 'nullable|email',
        ];
        
        return $rules;
    }
    
    public function save()
    {
        try {
            $data = $this->validate();
            
            // Remove test_email from data to save
            unset($data['test_email']);
            
            $this->service->saveSettings('email', $data);
            
            session()->flash('success', 'Email settings saved successfully!');
            
            $this->dispatch('settings-saved');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation errors will be shown automatically by Livewire
            throw $e;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save settings: ' . $e->getMessage());
        }
    }
    
    public function testConfiguration()
    {
        try {
            $data = $this->validate();
            $data['test_email'] = $this->test_email;
            
            $result = $this->service->testConfiguration('email', $data);
            
            if ($result['success']) {
                session()->flash('test_success', $result['message']);
            } else {
                session()->flash('test_error', $result['message']);
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            session()->flash('test_error', 'Please correct the validation errors before testing.');
        } catch (\Exception $e) {
            session()->flash('test_error', 'Test failed: ' . $e->getMessage());
        }
    }
    
    public function resetToDefaults()
    {
        $defaults = $this->service->getDefaultSettings('email');
        $this->fill($defaults);
        
        session()->flash('info', 'Settings reset to defaults. Click Save to apply.');
    }

    public function render()
    {
        return view('livewire.settings.email-settings');
    }
}
