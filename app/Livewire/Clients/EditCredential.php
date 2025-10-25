<?php

namespace App\Livewire\Clients;

use App\Domains\Client\Models\Client;
use App\Domains\Client\Models\ClientCredential;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EditCredential extends Component
{
    public ClientCredential $credential;
    
    public $client_id;
    public $name;
    public $description;
    public $credential_type;
    public $service_name;
    public $username;
    public $password;
    public $email;
    public $url;
    public $port;
    public $database_name;
    public $connection_string;
    public $api_key;
    public $secret_key;
    public $certificate;
    public $private_key;
    public $public_key;
    public $token;
    public $expires_at;
    public $is_active;
    public $is_shared;
    public $environment;
    public $access_level;
    public $notes;

    public $clients = [];
    public $credentialTypes = [];
    public $environments = [];
    public $accessLevels = [];

    public function mount(ClientCredential $credential)
    {
        $this->credential = $credential;
        
        $this->client_id = $credential->client_id;
        $this->name = $credential->name;
        $this->description = $credential->description;
        $this->credential_type = $credential->credential_type;
        $this->service_name = $credential->service_name;
        $this->username = $credential->username;
        $this->password = $credential->password;
        $this->email = $credential->email;
        $this->url = $credential->url;
        $this->port = $credential->port;
        $this->database_name = $credential->database_name;
        $this->connection_string = $credential->connection_string;
        $this->api_key = $credential->api_key;
        $this->secret_key = $credential->secret_key;
        $this->certificate = $credential->certificate;
        $this->private_key = $credential->private_key;
        $this->public_key = $credential->public_key;
        $this->token = $credential->token;
        $this->expires_at = $credential->expires_at;
        $this->is_active = $credential->is_active;
        $this->is_shared = $credential->is_shared;
        $this->environment = $credential->environment;
        $this->access_level = $credential->access_level;
        $this->notes = $credential->notes;

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

        $this->credential->fill($this->all());
        $this->credential->save();

        session()->flash('success', 'Credential updated successfully.');

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
        return view('livewire.clients.edit-credential');
    }
}
