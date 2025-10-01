<div class="space-y-8">
    <flux:card>
        <flux:heading size="lg">IP Access Control</flux:heading>
        <flux:text variant="muted" class="mb-6">Restrict access based on IP addresses</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Enable IP Whitelist</flux:label>
                <flux:switch name="ip_whitelist_enabled" :checked="$settings['ip_whitelist_enabled'] ?? false" />
                <flux:text size="sm" variant="muted">Only allow access from whitelisted IP addresses</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Whitelisted IP Addresses</flux:label>
                <flux:textarea name="ip_whitelist" rows="5" placeholder="One IP address per line&#10;192.168.1.1&#10;10.0.0.0/8">{{ is_array($settings['ip_whitelist'] ?? null) ? implode("\n", $settings['ip_whitelist']) : '' }}</flux:textarea>
                <flux:text size="sm" variant="muted">Enter IP addresses or CIDR ranges, one per line</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Block Tor/VPN Connections</flux:label>
                <flux:switch name="block_tor_vpn" :checked="$settings['block_tor_vpn'] ?? false" />
                <flux:text size="sm" variant="muted">Prevent access from known Tor exit nodes and VPN services</flux:text>
            </flux:field>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">Geographic Access Control</flux:heading>
        <flux:text variant="muted" class="mb-6">Restrict access based on geographic location</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Allowed Countries</flux:label>
                <flux:textarea name="allowed_countries" rows="5" placeholder="Leave empty to allow all countries&#10;Or enter country codes (US, CA, GB, etc.), one per line">{{ is_array($settings['allowed_countries'] ?? null) ? implode("\n", $settings['allowed_countries']) : '' }}</flux:textarea>
                <flux:text size="sm" variant="muted">Enter 2-letter country codes (ISO 3166-1 alpha-2), one per line. Leave empty to allow all.</flux:text>
            </flux:field>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">API Rate Limiting</flux:heading>
        <flux:text variant="muted" class="mb-6">Control API request limits</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>API Rate Limit (requests per minute)</flux:label>
                <flux:input type="number" name="api_rate_limit" value="{{ $settings['api_rate_limit'] ?? 1000 }}" min="10" max="10000" />
                <flux:text size="sm" variant="muted">Maximum API requests per minute per user/IP</flux:text>
            </flux:field>
        </div>
    </flux:card>
</div>
