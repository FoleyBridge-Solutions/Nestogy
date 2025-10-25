<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <flux:select wire:model.live="credential_type" label="Type" required>
        @foreach($credentialTypes as $key => $label)
            <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
        @endforeach
    </flux:select>

    @if($this->showEnvironment)
        <flux:select wire:model="environment" label="Environment">
            <flux:select.option value="">Select environment...</flux:select.option>
            @foreach($environments as $key => $label)
                <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    @endif
</div>

@if($this->showServerDetails)
    <flux:separator />
    <flux:heading size="md">Server/Host Details</flux:heading>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @if($credential_type === 'email')
            <flux:input wire:model="service_name" label="Service Provider" placeholder="Gmail, Outlook, etc." class="md:col-span-2" />
        @else
            <flux:input wire:model="url" label="Host/Server" placeholder="server.example.com" class="md:col-span-2" />
        @endif
        
        <flux:input wire:model="port" type="number" label="Port" placeholder="{{ $credential_type === 'database' ? '3306' : ($credential_type === 'ssh' ? '22' : ($credential_type === 'ftp' ? '21' : '')) }}" />
    </div>

    @if($credential_type === 'email')
        <flux:input wire:model="url" label="Webmail URL (optional)" placeholder="https://mail.example.com" />
    @endif
@endif

@if($this->showAuthentication)
    <flux:separator />
    <flux:heading size="md">Authentication</flux:heading>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @if($credential_type === 'email')
            <flux:input wire:model="email" type="email" label="Email Address" placeholder="user@example.com" required />
        @elseif(in_array($credential_type, ['social_media', 'web_admin', 'domain']))
            <flux:input wire:model="username" label="Username" placeholder="admin" />
            <flux:input wire:model="email" type="email" label="Email (optional)" placeholder="user@example.com" />
        @else
            <flux:input wire:model="username" label="Username" placeholder="admin" />
        @endif
        
        <flux:input wire:model="password" type="password" label="Password" placeholder="••••••••" />
    </div>
@endif

@if($this->showDatabaseDetails)
    <flux:separator />
    <flux:heading size="md">Database Details</flux:heading>

    <flux:input wire:model="database_name" label="Database Name" placeholder="my_database" />
    <flux:textarea wire:model="connection_string" label="Connection String (optional)" placeholder="Server=...;Database=...;User Id=...;Password=..." rows="2" />
@endif

@if($this->showLicenseInfo)
    <flux:separator />
    <flux:heading size="md">License Information</flux:heading>

    <flux:input wire:model="service_name" label="Software Name" placeholder="Windows Server 2022" />
    <flux:input wire:model="api_key" label="License Key" placeholder="XXXXX-XXXXX-XXXXX-XXXXX" />
    <flux:input wire:model="email" type="email" label="Registered Email" placeholder="user@example.com" />
    <flux:input wire:model="url" label="Activation Portal (optional)" placeholder="https://activate.example.com" />
@endif

@if($this->showApiKeys)
    <flux:separator />
    <flux:heading size="md">API & Keys</flux:heading>

    @if(in_array($credential_type, ['cloud_service', 'payment']))
        <flux:input wire:model="service_name" label="Service Name" placeholder="AWS, Azure, Stripe, etc." />
    @endif
    
    @if(in_array($credential_type, ['api', 'cloud_service', 'payment', 'domain']))
        <flux:input wire:model="url" label="API Endpoint" placeholder="https://api.example.com" />
    @endif
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <flux:input wire:model="api_key" label="API Key" placeholder="sk_live_..." />
        <flux:input wire:model="secret_key" label="Secret Key" placeholder="Secret key..." />
    </div>
    
    <flux:input wire:model="token" label="Token (optional)" placeholder="Bearer token..." />
@endif

@if($this->showCertificates)
    <flux:separator />
    <flux:heading size="md">Certificates & Keys</flux:heading>

    @if($credential_type === 'ssl_certificate')
        <flux:input wire:model="service_name" label="Domain/Service" placeholder="example.com" />
        <flux:input wire:model="url" label="Issuer/CA" placeholder="Let's Encrypt, DigiCert, etc." />
    @endif

    <flux:textarea wire:model="certificate" label="Certificate" placeholder="-----BEGIN CERTIFICATE-----" rows="3" />
    <flux:textarea wire:model="private_key" label="Private Key" placeholder="-----BEGIN PRIVATE KEY-----" rows="3" />
    
    @if(in_array($credential_type, ['ssh', 'ssl_certificate']))
        <flux:textarea wire:model="public_key" label="Public Key" placeholder="-----BEGIN PUBLIC KEY-----" rows="3" />
    @endif
@endif

@if($this->showAccessSettings)
    <flux:separator />
    <flux:heading size="md">Access Settings</flux:heading>

    <flux:select wire:model="access_level" label="Access Level">
        <flux:select.option value="">Select access level...</flux:select.option>
        @foreach($accessLevels as $key => $label)
            <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
        @endforeach
    </flux:select>
@endif

@if($this->showSocialMedia)
    <flux:separator />
    <flux:heading size="md">Platform Details</flux:heading>

    <flux:input wire:model="service_name" label="Platform" placeholder="Facebook, Twitter, LinkedIn, etc." />
    <flux:input wire:model="url" label="Profile/Page URL" placeholder="https://facebook.com/yourpage" />
@endif

@if($this->showDomainDetails)
    <flux:separator />
    <flux:heading size="md">Domain Details</flux:heading>

    <flux:input wire:model="service_name" label="Domain Name" placeholder="example.com" />
    <flux:input wire:model="url" label="Registrar Panel URL" placeholder="https://registrar.example.com" />
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <flux:input wire:model="username" label="Registrar Username" placeholder="admin" />
        <flux:input wire:model="password" type="password" label="Registrar Password" placeholder="••••••••" />
    </div>
    
    <flux:input wire:model="email" type="email" label="Contact Email" placeholder="admin@example.com" />
    <flux:input wire:model="api_key" label="API Key (optional)" placeholder="For domain API access" />
@endif

@if($this->showAllFields)
    <flux:separator />
    <flux:heading size="md">Connection Details</flux:heading>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <flux:input wire:model="service_name" label="Service Name" placeholder="Service name" />
        <flux:input wire:model="username" label="Username" placeholder="admin" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <flux:input wire:model="password" type="password" label="Password" placeholder="••••••••" />
        <flux:input wire:model="email" type="email" label="Email" placeholder="user@example.com" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <flux:input wire:model="url" label="URL" placeholder="https://example.com" class="md:col-span-2" />
        <flux:input wire:model="port" type="number" label="Port" placeholder="443" />
    </div>

    <flux:input wire:model="database_name" label="Database Name" placeholder="my_database" />

    <flux:separator />

    <flux:heading size="md">API & Keys</flux:heading>

    <flux:input wire:model="api_key" label="API Key" placeholder="API key..." />
    <flux:input wire:model="secret_key" label="Secret Key" placeholder="Secret key..." />
    <flux:input wire:model="token" label="Token" placeholder="Bearer token..." />

    <flux:textarea wire:model="connection_string" label="Connection String" placeholder="Connection string..." rows="2" />

    <flux:separator />

    <flux:heading size="md">Certificates & Keys</flux:heading>

    <flux:textarea wire:model="certificate" label="Certificate" placeholder="-----BEGIN CERTIFICATE-----" rows="3" />
    <flux:textarea wire:model="private_key" label="Private Key" placeholder="-----BEGIN PRIVATE KEY-----" rows="3" />
    <flux:textarea wire:model="public_key" label="Public Key" placeholder="-----BEGIN PUBLIC KEY-----" rows="3" />

    <flux:separator />

    <flux:heading size="md">Access Settings</flux:heading>

    <flux:select wire:model="access_level" label="Access Level">
        <flux:select.option value="">Select access level...</flux:select.option>
        @foreach($accessLevels as $key => $label)
            <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
        @endforeach
    </flux:select>
@endif
