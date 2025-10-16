<div class="flex items-center gap-2">
    @php
        $colors = [
            'database' => 'blue',
            'ftp' => 'purple',
            'ssh' => 'green',
            'rdp' => 'indigo',
            'web_admin' => 'orange',
            'email' => 'red',
            'cloud_service' => 'sky',
            'api' => 'cyan',
            'vpn' => 'teal',
            'software' => 'amber',
            'domain' => 'yellow',
            'ssl_certificate' => 'lime',
            'social_media' => 'pink',
            'payment' => 'rose',
            'other' => 'gray',
        ];
        $color = $colors[$item->credential_type] ?? 'gray';
    @endphp
    <flux:badge :color="$color" :label="str_replace('_', ' ', ucfirst($item->credential_type))" />
</div>
