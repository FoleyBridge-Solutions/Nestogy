@extends('layouts.app')

@section('title', 'Certificate Details - ' . $certificate->name)

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-8 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <span class="text-3xl mr-3">
                            @if($certificate->type === 'ssl_tls')
                                🔒
                            @elseif($certificate->type === 'code_signing')
                                📝
                            @elseif($certificate->type === 'email_smime')
                                📧
                            @else
                                🛡️
                            @endif
                        </span>
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900">{{ $certificate->name }}</h3>
                            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                                {{ $certificate->client->display_name }} - {{ $certificate->formatted_domains }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="inline-flex items-center px-6 py-1 rounded-full text-sm font-medium 
                                    {{ $certificate->status_color === 'green' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $certificate->status_color === 'yellow' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $certificate->status_color === 'red' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $certificate->status_color === 'blue' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $certificate->status_color === 'gray' ? 'bg-gray-100 text-gray-800' : '' }}">
                            {{ ucfirst($certificate->status) }}
                        </span>
                        @if($certificate->is_wildcard)
                            <span class="inline-flex items-center px-6 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                                ★ Wildcard
                            </span>
                        @endif
                        @if($certificate->expiry_status === 'expired')
                            <span class="inline-flex items-center px-6 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                ❌ Expired
                            </span>
                        @elseif($certificate->expiry_status === 'expiring_soon')
                            <span class="inline-flex items-center px-6 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                ⚠️ Expiring Soon
                            </span>
                        @endif
                        <div class="flex space-x-3">
                            <a href="{{ route('clients.certificates.standalone.edit', $certificate) }}" 
                               class="inline-flex items-center px-6 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit
                            </a>
                            <a href="{{ route('clients.certificates.standalone.index') }}" 
                               class="inline-flex items-center px-6 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Back to Certificates
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Main Information -->
            <div class="lg:flex-1 px-6-span-2 space-y-6">
                <!-- Basic Information -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Basic Information</h3>
                    </div>
                    <div class="px-6 py-8 sm:p-6">
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Client</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $certificate->client->display_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Certificate Type</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $certificate->type }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                {{ $certificate->status_color === 'green' ? 'bg-green-100 text-green-800' : '' }}
                                                {{ $certificate->status_color === 'yellow' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                {{ $certificate->status_color === 'red' ? 'bg-red-100 text-red-800' : '' }}
                                                {{ $certificate->status_color === 'blue' ? 'bg-blue-100 text-blue-800' : '' }}
                                                {{ $certificate->status_color === 'gray' ? 'bg-gray-100 text-gray-800' : '' }}">
                                        {{ ucfirst($certificate->status) }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Wildcard Certificate</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    @if($certificate->is_wildcard)
                                        <span class="text-green-600">✓ Yes</span>
                                    @else
                                        <span class="text-gray-600">✗ No</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Created</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $certificate->created_at->format('M d, Y g:i A') }}</dd>
                            </div>
                            @if($certificate->accessed_at)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Last Accessed</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $certificate->accessed_at->format('M d, Y g:i A') }}</dd>
                                </div>
                            @endif
                        </dl>

                        @if($certificate->description)
                            <div class="mt-6">
                                <dt class="text-sm font-medium text-gray-500">Description</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $certificate->description }}</dd>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Certificate Details -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Certificate Details</h3>
                    </div>
                    <div class="px-6 py-8 sm:p-6">
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            @if($certificate->issuer)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Issuer</dt>
                                    <dd class="mt-1 text-sm text-gray-900 break-all">{{ $certificate->issuer }}</dd>
                                </div>
                            @endif
                            @if($certificate->subject)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Subject</dt>
                                    <dd class="mt-1 text-sm text-gray-900 break-all">{{ $certificate->subject }}</dd>
                                </div>
                            @endif
                            @if($certificate->serial_number)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Serial Number</dt>
                                    <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $certificate->serial_number }}</dd>
                                </div>
                            @endif
                            @if($certificate->key_size)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Key Size</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ $certificate->key_size }} bits
                                        @php $securityLevel = $certificate->security_level; @endphp
                                        @if($securityLevel === 'high')
                                            <span class="ml-2 text-green-600">🛡️ High Security</span>
                                        @elseif($securityLevel === 'medium')
                                            <span class="ml-2 text-yellow-600">⚠️ Medium Security</span>
                                        @elseif($securityLevel === 'low')
                                            <span class="ml-2 text-red-600">⚠️ Low Security</span>
                                        @endif
                                    </dd>
                                </div>
                            @endif
                            @if($certificate->algorithm)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Algorithm</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $certificate->algorithm_display }}</dd>
                                </div>
                            @endif
                            @if($certificate->fingerprint_sha1)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">SHA1 Fingerprint</dt>
                                    <dd class="mt-1 text-sm text-gray-900 font-mono break-all">{{ $certificate->fingerprint_sha1 }}</dd>
                                </div>
                            @endif
                            @if($certificate->fingerprint_sha256)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">SHA256 Fingerprint</dt>
                                    <dd class="mt-1 text-sm text-gray-900 font-mono break-all">{{ $certificate->fingerprint_sha256 }}</dd>
                                </div>
                            @endif
                        </dl>

                        <!-- Domain Names -->
                        @if($certificate->domain_names && count($certificate->domain_names) > 0)
                            <div class="mt-6">
                                <dt class="text-sm font-medium text-gray-500">Covered Domains</dt>
                                <dd class="mt-2">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($certificate->domain_names as $domain)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $domain }}
                                            </span>
                                        @endforeach
                                    </div>
                                </dd>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Dates & Renewal -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Dates & Renewal</h3>
                    </div>
                    <div class="px-6 py-8 sm:p-6">
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            @if($certificate->issued_at)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Issue Date</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $certificate->issued_at->format('M d, Y') }}</dd>
                                </div>
                            @endif
                            @if($certificate->expires_at)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Expiry Date</dt>
                                    <dd class="mt-1 text-sm {{ $certificate->isExpired() ? 'text-red-600' : 'text-gray-900' }}">
                                        {{ $certificate->expires_at->format('M d, Y') }}
                                        @if($certificate->isExpired())
                                            <span class="ml-2 text-red-600 font-medium">(Expired)</span>
                                        @elseif($certificate->isExpiringSoon())
                                            <span class="ml-2 text-orange-600 font-medium">(Expiring Soon)</span>
                                        @endif
                                    </dd>
                                </div>
                            @endif
                            @if($certificate->renewal_date)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Renewal Date</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $certificate->renewal_date->format('M d, Y') }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Auto Renewal</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    @if($certificate->auto_renewal)
                                        <span class="text-green-600">✓ Enabled</span>
                                    @else
                                        <span class="text-gray-600">✗ Disabled</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Alert Days Before Expiry</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $certificate->days_before_expiry_alert ?: 30 }} days</dd>
                            </div>
                            @if($certificate->days_until_expiry !== null)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Days Until Expiry</dt>
                                    <dd class="mt-1 text-sm font-medium {{ $certificate->days_until_expiry < 0 ? 'text-red-600' : ($certificate->days_until_expiry <= 30 ? 'text-yellow-600' : 'text-green-600') }}">
                                        {{ abs($certificate->days_until_expiry) }}{{ $certificate->days_until_expiry < 0 ? ' days overdue' : ' days remaining' }}
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <!-- File Paths -->
                @if($certificate->hasCertificateFiles())
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Certificate Files</h3>
                        </div>
                        <div class="px-6 py-8 sm:p-6">
                            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-1">
                                @if($certificate->certificate_path)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Certificate File</dt>
                                        <dd class="mt-1 text-sm text-gray-900 font-mono break-all">{{ $certificate->certificate_path }}</dd>
                                    </div>
                                @endif
                                @if($certificate->private_key_path)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Private Key File</dt>
                                        <dd class="mt-1 text-sm text-gray-900 font-mono break-all">{{ $certificate->private_key_path }}</dd>
                                    </div>
                                @endif
                                @if($certificate->intermediate_path)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Intermediate Certificate</dt>
                                        <dd class="mt-1 text-sm text-gray-900 font-mono break-all">{{ $certificate->intermediate_path }}</dd>
                                    </div>
                                @endif
                                @if($certificate->root_ca_path)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Root CA Certificate</dt>
                                        <dd class="mt-1 text-sm text-gray-900 font-mono break-all">{{ $certificate->root_ca_path }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    </div>
                @endif

                <!-- Notes -->
                @if($certificate->notes)
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Notes</h3>
                        </div>
                        <div class="px-6 py-8 sm:p-6">
                            <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $certificate->notes }}</p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Expiry Status -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Expiry Status</h3>
                    </div>
                    <div class="px-6 py-8 sm:p-6">
                        @if($certificate->expires_at)
                            <!-- Expiry Progress Circle -->
                            <div class="flex items-center justify-center mb-6">
                                <div class="relative inline-flex items-center justify-center w-24 h-24">
                                    @php
                                        $totalDays = $certificate->issued_at && $certificate->expires_at ? 
                                                    $certificate->expires_at->diffInDays($certificate->issued_at) : 365;
                                        $remainingDays = $certificate->days_until_expiry ?? 0;
                                        $usedPercentage = $totalDays > 0 ? max(0, min(100, (($totalDays - $remainingDays) / $totalDays) * 100)) : 0;
                                        
                                        if ($certificate->isExpired()) {
                                            $circleColor = 'text-red-500';
                                            $usedPercentage = 100;
                                        } elseif ($certificate->isExpiringSoon()) {
                                            $circleColor = 'text-yellow-500';
                                        } else {
                                            $circleColor = 'text-green-500';
                                        }
                                    @endphp
                                    <svg class="w-24 h-24 transform -rotate-90" viewBox="0 0 36 36">
                                        <path class="text-gray-300" stroke="currentColor" stroke-width="3" fill="transparent"
                                              d="M18,2.0845 a 15.9155,15.9155 0 0,1 0,31.831 a 15.9155,15.9155 0 0,1 0,-31.831"/>
                                        <path class="{{ $circleColor }}" 
                                              stroke="currentColor" stroke-width="3" stroke-linecap="round" fill="transparent"
                                              stroke-dasharray="{{ $usedPercentage }}, 100"
                                              d="M18,2.0845 a 15.9155,15.9155 0 0,1 0,31.831 a 15.9155,15.9155 0 0,1 0,-31.831"/>
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        @if($certificate->isExpired())
                                            <span class="text-sm font-medium text-red-600">Expired</span>
                                        @else
                                            <span class="text-lg font-medium text-gray-900">{{ abs($remainingDays) }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <dl class="space-y-3">
                                <div class="flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Expires</dt>
                                    <dd class="text-sm {{ $certificate->isExpired() ? 'text-red-600' : 'text-gray-900' }}">
                                        {{ $certificate->expires_at->format('M d, Y') }}
                                    </dd>
                                </div>
                                @if(!$certificate->isExpired())
                                    <div class="flex justify-between border-t pt-3">
                                        <dt class="text-sm font-medium text-gray-500">Days Remaining</dt>
                                        <dd class="text-sm font-semibold {{ $remainingDays <= 30 ? 'text-red-600' : 'text-green-600' }}">
                                            {{ $remainingDays }}
                                        </dd>
                                    </div>
                                @endif
                                <div class="flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Renewal Urgency</dt>
                                    <dd class="text-sm">
                                        @php $urgency = $certificate->renewal_urgency; @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                    {{ $urgency === 'critical' ? 'bg-red-100 text-red-800' : '' }}
                                                    {{ $urgency === 'high' ? 'bg-orange-100 text-orange-800' : '' }}
                                                    {{ $urgency === 'medium' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                    {{ $urgency === 'low' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $urgency === 'none' ? 'bg-gray-100 text-gray-800' : '' }}">
                                            {{ ucfirst($urgency) }}
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        @else
                            <p class="text-sm text-gray-500 text-center py-6">No expiry date set</p>
                        @endif
                    </div>
                </div>

                <!-- Vendor Information -->
                @if($certificate->vendor || $certificate->purchase_cost || $certificate->renewal_cost)
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Vendor & Costs</h3>
                        </div>
                        <div class="px-6 py-8 sm:p-6">
                            <dl class="space-y-3">
                                @if($certificate->vendor)
                                    <div class="flex justify-between">
                                        <dt class="text-sm font-medium text-gray-500">Vendor</dt>
                                        <dd class="text-sm text-gray-900">{{ $certificate->vendor }}</dd>
                                    </div>
                                @endif
                                @if($certificate->purchase_cost)
                                    <div class="flex justify-between">
                                        <dt class="text-sm font-medium text-gray-500">Purchase Cost</dt>
                                        <dd class="text-sm text-gray-900">${{ number_format($certificate->purchase_cost, 2) }}</dd>
                                    </div>
                                @endif
                                @if($certificate->renewal_cost)
                                    <div class="flex justify-between">
                                        <dt class="text-sm font-medium text-gray-500">Annual Renewal</dt>
                                        <dd class="text-sm text-gray-900">${{ number_format($certificate->renewal_cost, 2) }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    </div>
                @endif

                <!-- Quick Stats -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Quick Stats</h3>
                    </div>
                    <div class="px-6 py-8 sm:p-6">
                        <dl class="space-y-3">
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Type</dt>
                                <dd class="text-sm text-gray-900">{{ $certificate->type }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Domains</dt>
                                <dd class="text-sm text-gray-900">{{ count($certificate->domain_names ?? []) }}</dd>
                            </div>
                            @if($certificate->key_size)
                                <div class="flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Security</dt>
                                    <dd class="text-sm">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                    {{ $certificate->security_level === 'high' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $certificate->security_level === 'medium' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                    {{ $certificate->security_level === 'low' ? 'bg-red-100 text-red-800' : '' }}">
                                            {{ ucfirst($certificate->security_level) }}
                                        </span>
                                    </dd>
                                </div>
                            @endif
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Auto Renewal</dt>
                                <dd class="text-sm">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                {{ $certificate->auto_renewal ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $certificate->auto_renewal ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
