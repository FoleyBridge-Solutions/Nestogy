<?php

namespace App\Livewire\Clients;

use App\Domains\Client\Models\Client;
use App\Domains\Client\Models\ClientCredential;
use App\Domains\Core\Services\NavigationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CreateCredential extends Component
{
    public $client_id;
    public $name = '';
    public $description = '';
    public $credential_type = 'database';
    public $service_name = '';
    public $username = '';
    public $password = '';
    public $email = '';
    public $url = '';
    public $port = '';
    public $database_name = '';
    public $connection_string = '';
    public $api_key = '';
    public $secret_key = '';
    public $certificate = '';
    public $private_key = '';
    public $public_key = '';
    public $token = '';
    public $expires_at = '';
    public $is_active = true;
    public $is_shared = false;
    public $environment = 'production';
    public $access_level = 'read_write';
    public $notes = '';

    public $clients = [];
    public $credentialTypes = [];
    public $environments = [];
    public $accessLevels = [];

    public function mount()
    {
        $selectedClient = NavigationService::getSelectedClient();
        
        if ($selectedClient) {
            $this->client_id = $selectedClient->id;
        }

        $this->clients = Client::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get();

        $this->credentialTypes = ClientCredential::getCredentialTypes();
        $this->environments = ClientCredential::getEnvironments();
        $this->accessLevels = ClientCredential::getAccessLevels();
    }

    public function save()
    {
        $this->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'credential_type' => 'required|in:'.implode(',', array_keys($this->credentialTypes)),
            'environment' => 'nullable|in:'.implode(',', array_keys($this->environments)),
            'access_level' => 'nullable|in:'.implode(',', array_keys($this->accessLevels)),
        ]);

        $credential = new ClientCredential();
        $credential->fill($this->all());
        $credential->company_id = Auth::user()->company_id;
        $credential->created_by = Auth::id();
        $credential->save();

        session()->flash('success', 'Credential created successfully.');

        return redirect()->route('clients.credentials.index');
    }

    public function getShowEnvironmentProperty()
    {
        return in_array($this->credential_type, ['database', 'ftp', 'ssh', 'rdp', 'web_admin', 'cloud_service', 'api', 'vpn', 'payment']);
    }

    public function getShowServerDetailsProperty()
    {
        return in_array($this->credential_type, ['database', 'ftp', 'ssh', 'rdp', 'vpn', 'email']);
    }

    public function getShowAuthenticationProperty()
    {
        return in_array($this->credential_type, ['database', 'ftp', 'ssh', 'rdp', 'web_admin', 'email', 'social_media', 'domain', 'vpn']);
    }

    public function getShowDatabaseDetailsProperty()
    {
        return $this->credential_type === 'database';
    }

    public function getShowApiKeysProperty()
    {
        return in_array($this->credential_type, ['api', 'cloud_service', 'payment', 'domain']);
    }

    public function getShowLicenseInfoProperty()
    {
        return $this->credential_type === 'software';
    }

    public function getShowCertificatesProperty()
    {
        return in_array($this->credential_type, ['ssh', 'vpn', 'ssl_certificate', 'cloud_service']);
    }

    public function getShowAccessSettingsProperty()
    {
        return in_array($this->credential_type, ['web_admin', 'cloud_service', 'api', 'database', 'payment']);
    }

    public function getShowSocialMediaProperty()
    {
        return $this->credential_type === 'social_media';
    }

    public function getShowDomainDetailsProperty()
    {
        return $this->credential_type === 'domain';
    }

    public function getShowAllFieldsProperty()
    {
        return $this->credential_type === 'other';
    }

    public function render()
    {
        return view('livewire.clients.create-credential');
    }
}
