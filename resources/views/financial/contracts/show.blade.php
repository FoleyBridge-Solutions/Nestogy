@extends('layouts.app')

@section('title', 'Contract Details - ' . $contract->title)

@push('styles')
<!-- Add Visual Workflow Styles -->
<style>
.workflow-node {
    transition: all 0.3s ease;
}
.workflow-node:hover {
    transform: scale(1.05);
    z-index: 10;
}
.workflow-connection {
    stroke-dasharray: 5,5;
    animation: dash 2s linear infinite;
}
@keyframes dash {
    to {
        stroke-dashoffset: -10;
    }
}
.workflow-timeline {
    position: relative;
}
.workflow-timeline::before {
    content: '';
    position: absolute;
    left: 20px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, #e5e7eb, #3b82f6, #10b981);
}
</style>
<style>
.contract-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.status-badge {
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 0.5rem 1rem;
    border-radius: 25px;
}

.milestone-card {
    border-left: 4px solid #dee2e6;
    transition: all 0.3s ease;
}
.milestone-card.completed { border-left-color: #28a745; }
.milestone-card.in-progress { border-left-color: #ffc107; }
.milestone-card.pending { border-left-color: #6c757d; }

.approval-timeline {
    position: relative;
}
.approval-timeline::before {
    content: '';
    position: absolute;
    left: 20px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.approval-item {
    position: relative;
    padding-left: 60px;
    margin-bottom: 2rem;
}

.approval-icon {
    position: absolute;
    left: 0;
    top: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

.approval-icon.pending {
    background: #ffc107;
    color: white;
}
.approval-icon.approved {
    background: #28a745;
    color: white;
}
.approval-icon.rejected {
    background: #dc3545;
    color: white;
}

.signature-status {
    border: 2px solid #dee2e6;
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
}
.signature-status.completed {
    border-color: #28a745;
    background-color: #f8fff9;
}
.signature-status.pending {
    border-color: #ffc107;
    background-color: #fffaf0;
}

.document-preview {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    max-height: 600px;
}

.tab-content-card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-radius: 0 8px 8px 8px;
}

@media print {
    .no-print { display: none; }
    .contract-header { background: #f8f9fa !important; color: #333 !important; }
}
</style>
@endpush

@section('content')
<div class="w-full px-4">
    <!-- Header -->
    <div class="contract-header p-4 mb-4">
        <div class="flex flex-wrap -mx-4 items-center">
            <div class="md:w-2/3 px-4">
                <div class="flex items-center mb-2">
                    <h1 class="h3 mb-0 mr-3">{{ $contract->title }}</h1>
                    <span class="status-badge bg-{{ $contract->status_color ?? 'secondary' }}">
                        {{ $contract->status_label }}
                    </span>
                </div>
                <div class="flex flex-wrap -mx-4">
                    <div class="col-sm-6">
                        <p class="mb-1 opacity-75">
                            <i class="fas fa-user mr-2"></i>
                            <strong>Client:</strong> {{ $contract->client->name ?? 'No Client' }}
                        </p>
                        <p class="mb-1 opacity-75">
                            <i class="fas fa-calendar me-2"></i>
                            <strong>Duration:</strong> 
                            {{ $contract->start_date ? $contract->start_date->format('M d, Y') : 'Not set' }}
                            @if($contract->end_date)
                                - {{ $contract->end_date->format('M d, Y') }}
                            @endif
                        </p>
                    </div>
                    <div class="col-sm-6">
                        <p class="mb-1 opacity-75">
                            <i class="fas fa-dollar-sign me-2"></i>
                            <strong>Value:</strong> ${{ number_format($contract->contract_value, 2) }}
                        </p>
                        <p class="mb-1 opacity-75">
                            <i class="fas fa-tag me-2"></i>
                            <strong>Type:</strong> {{ ucwords(str_replace('_', ' ', $contract->contract_type)) }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="md:w-1/3 px-4 text-md-end">
                <div class="no-print">
                    @if($contract->canBeEdited())
                        <a href="{{ route('contracts.edit', $contract) }}" class="btn btn-light me-2">
                            <i class="fas fa-edit me-1"></i> Edit Contract
                        </a>
                    @endif
                    
                    <div class="btn-group">
                        <button type="button" class="btn btn-light dropdown-toggle" x-data="{ open: false }" @click="open = !open">
                            <i class="fas fa-cog me-1"></i> Actions
                        </button>
                        <ul class="dropdown-menu">
                            @if($contract->status === 'draft' || $contract->status === 'under_negotiation')
                                <li><a class="dropdown-item" href="#" onclick="submitForApproval()">
                                    <i class="fas fa-check me-2"></i> Submit for Approval
                                </a></li>
                            @endif
                            @if($contract->status === 'pending_signature')
                                <li><a class="dropdown-item" href="#" onclick="sendForSignature()">
                                    <i class="fas fa-signature me-2"></i> Send for Signature
                                </a></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ route('contracts.pdf', $contract) }}" target="_blank">
                                <i class="fas fa-file-pdf me-2"></i> Download PDF
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="window.print()">
                                <i class="fas fa-print me-2"></i> Print Contract
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('contracts.duplicate', $contract) }}">
                                <i class="fas fa-copy me-2"></i> Duplicate Contract
                            </a></li>
                            @if($contract->canBeTerminated())
                                <li><a class="dropdown-item text-red-600" href="#" onclick="terminateContract()">
                                    <i class="fas fa-ban me-2"></i> Terminate Contract
                                </a></li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Programmable Features Banner -->
    @if($contract->billing_model !== 'fixed')
        <div class="bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-200 rounded-lg p-6 mb-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-8 h-8 text-purple-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <div>
                        <h3 class="text-lg font-medium text-purple-900">Programmable Contract</h3>
                        <p class="text-sm text-purple-700">
                            This contract uses <strong>{{ ucwords(str_replace('_', ' ', $contract->billing_model)) }}</strong> billing 
                            with automated usage calculations
                        </p>
                        @if($contract->template && $contract->template->automation_settings)
                            <div class="mt-2 text-sm text-purple-600">
                                🤖 Automation: 
                                @php
                                    $automationFeatures = [];
                                    $settings = $contract->template->automation_settings;
                                    if ($settings['auto_assign_new_assets'] ?? false) $automationFeatures[] = 'Auto-assign Assets';
                                    if ($settings['auto_assign_new_contacts'] ?? false) $automationFeatures[] = 'Auto-assign Contacts';
                                    if ($settings['auto_generate_invoices'] ?? false) $automationFeatures[] = 'Auto-generate Invoices';
                                @endphp
                                <strong>{{ implode(', ', $automationFeatures) ?: 'No automation enabled' }}</strong>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <div class="text-lg font-bold text-purple-900">{{ $contract->asset_assignments_count ?? 0 }}</div>
                        <div class="text-xs text-purple-700">Assets</div>
                    </div>
                    <div>
                        <div class="text-lg font-bold text-purple-900">{{ $contract->contact_assignments_count ?? 0 }}</div>
                        <div class="text-xs text-purple-700">Contacts</div>
                    </div>
                    <div>
                        <div class="text-lg font-bold text-purple-900">${{ number_format($contract->current_monthly_amount ?? 0, 0) }}</div>
                        <div class="text-xs text-purple-700">Monthly</div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="mt-4 flex flex-wrap gap-2">
                @if(in_array($contract->billing_model, ['per_asset', 'hybrid']))
                    <a href="{{ route('financial.contracts.asset-assignments', $contract) }}" 
                       class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Manage Assets
                    </a>
                @endif
                @if(in_array($contract->billing_model, ['per_contact', 'hybrid']))
                    <a href="{{ route('financial.contracts.contact-assignments', $contract) }}" 
                       class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 hover:bg-purple-200">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM9 3a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        Manage Contacts
                    </a>
                @endif
                <a href="{{ route('financial.contracts.usage-dashboard', $contract) }}" 
                   class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 hover:bg-green-200">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Usage Dashboard
                </a>
                @if($contract->template)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Template: {{ $contract->template->name }}
                    </span>
                @endif
            </div>
        </div>
    @endif

    <!-- Visual Workflow Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 overflow-hidden">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-gray-900">Contract Workflow</h2>
                <div class="flex items-center space-x-2 text-sm text-gray-600">
                    <div class="flex items-center">
                        <div class="w-3 h-3 rounded-full bg-yellow-400 mr-2"></div>
                        <span>In Progress</span>
                    </div>
                    <div class="flex items-center ml-4">
                        <div class="w-3 h-3 rounded-full bg-green-500 mr-2"></div>
                        <span>Completed</span>
                    </div>
                    <div class="flex items-center ml-4">
                        <div class="w-3 h-3 rounded-full bg-gray-300 mr-2"></div>
                        <span>Pending</span>
                    </div>
                </div>
            </div>
            
            <!-- Interactive Workflow Kanban -->
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6" x-data="workflowKanban()">
                <!-- Draft Stage -->
                <div class="workflow-stage">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-medium text-gray-900 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                            Draft
                        </h3>
                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium 
                            {{ $contract->status === 'draft' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                            @if($contract->status === 'draft') ! @else ✓ @endif
                        </div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 min-h-[100px]">
                        @if($contract->status === 'draft')
                            <div class="workflow-node bg-white rounded-lg p-3 shadow-sm border-l-4 border-yellow-400">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium">Contract in Draft</span>
                                    <span class="text-xs text-gray-500">Active</span>
                                </div>
                                <p class="text-xs text-gray-600 mt-1">Ready for review and submission</p>
                            </div>
                        @else
                            <div class="workflow-node bg-white rounded-lg p-3 shadow-sm border-l-4 border-green-400">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium">Draft Completed</span>
                                    <span class="text-xs text-gray-500">{{ $contract->created_at->format('M j') }}</span>
                                </div>
                                <p class="text-xs text-gray-600 mt-1">Contract created and submitted</p>
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Under Negotiation Stage -->
                <div class="workflow-stage">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-medium text-gray-900 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            Negotiation
                        </h3>
                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium 
                            @if($contract->status === 'under_negotiation') bg-blue-100 text-blue-800
                            @elseif(in_array($contract->status, ['pending_signature', 'active', 'expired', 'terminated'])) bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-400 @endif">
                            @if($contract->status === 'under_negotiation') ! 
                            @elseif(in_array($contract->status, ['pending_signature', 'active', 'expired', 'terminated'])) ✓ 
                            @else ○ @endif
                        </div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 min-h-[100px]">
                        @if($contract->status === 'under_negotiation')
                            <div class="workflow-node bg-white rounded-lg p-3 shadow-sm border-l-4 border-blue-400">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium">Negotiating Terms</span>
                                    <span class="text-xs text-gray-500">Active</span>
                                </div>
                                <p class="text-xs text-gray-600 mt-1">{{ $contract->negotiations_count ?? 0 }} rounds completed</p>
                                @if($contract->last_negotiation_date)
                                    <p class="text-xs text-gray-500 mt-1">Last activity: {{ $contract->last_negotiation_date->diffForHumans() }}</p>
                                @endif
                            </div>
                        @elseif(in_array($contract->status, ['pending_signature', 'active', 'expired', 'terminated']))
                            <div class="workflow-node bg-white rounded-lg p-3 shadow-sm border-l-4 border-green-400">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium">Terms Agreed</span>
                                    <span class="text-xs text-gray-500">Completed</span>
                                </div>
                                <p class="text-xs text-gray-600 mt-1">Ready for signatures</p>
                            </div>
                        @else
                            <div class="text-center py-4 text-gray-400">
                                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                <p class="text-xs">Pending</p>
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Approval Stage -->
                <div class="workflow-stage">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-medium text-gray-900 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Approval
                        </h3>
                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium 
                            @if($contract->status === 'pending_approval') bg-purple-100 text-purple-800
                            @elseif(in_array($contract->status, ['pending_signature', 'active', 'expired', 'terminated'])) bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-400 @endif">
                            @if($contract->status === 'pending_approval') ! 
                            @elseif(in_array($contract->status, ['pending_signature', 'active', 'expired', 'terminated'])) ✓ 
                            @else ○ @endif
                        </div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 min-h-[100px]">
                        @if($contract->approvals && $contract->approvals->count() > 0)
                            <div class="space-y-2">
                                @foreach($contract->approvals->take(2) as $approval)
                                    <div class="workflow-node bg-white rounded-lg p-2 shadow-sm border-l-4 
                                        @if($approval->status === 'approved') border-green-400
                                        @elseif($approval->status === 'pending') border-yellow-400
                                        @else border-red-400 @endif">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-medium truncate">{{ $approval->approverUser->name ?? $approval->approver_role }}</span>
                                            <span class="text-xs text-gray-500">{{ ucfirst($approval->status) }}</span>
                                        </div>
                                    </div>
                                @endforeach
                                @if($contract->approvals->count() > 2)
                                    <div class="text-center">
                                        <span class="text-xs text-gray-500">+{{ $contract->approvals->count() - 2 }} more</span>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="text-center py-4 text-gray-400">
                                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-xs">No approvals required</p>
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Signature Stage -->
                <div class="workflow-stage">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-medium text-gray-900 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Signatures
                        </h3>
                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium 
                            @if($contract->status === 'pending_signature') bg-indigo-100 text-indigo-800
                            @elseif(in_array($contract->status, ['active', 'expired', 'terminated'])) bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-400 @endif">
                            @if($contract->status === 'pending_signature') ! 
                            @elseif(in_array($contract->status, ['active', 'expired', 'terminated'])) ✓ 
                            @else ○ @endif
                        </div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 min-h-[100px]">
                        @if($contract->signatures && $contract->signatures->count() > 0)
                            <div class="space-y-2">
                                @foreach($contract->signatures->take(2) as $signature)
                                    <div class="workflow-node bg-white rounded-lg p-2 shadow-sm border-l-4 
                                        @if($signature->status === 'signed') border-green-400
                                        @elseif($signature->status === 'sent') border-yellow-400
                                        @else border-gray-400 @endif">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-medium truncate">{{ $signature->signer_name }}</span>
                                            <span class="text-xs text-gray-500">{{ ucfirst($signature->status) }}</span>
                                        </div>
                                    </div>
                                @endforeach
                                @if($contract->signatures->count() > 2)
                                    <div class="text-center">
                                        <span class="text-xs text-gray-500">+{{ $contract->signatures->count() - 2 }} more</span>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="text-center py-4 text-gray-400">
                                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                <p class="text-xs">Pending setup</p>
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Active Stage -->
                <div class="workflow-stage">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-medium text-gray-900 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Active
                        </h3>
                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium 
                            @if($contract->status === 'active') bg-green-100 text-green-800
                            @elseif(in_array($contract->status, ['expired', 'terminated'])) bg-gray-100 text-gray-600
                            @else bg-gray-100 text-gray-400 @endif">
                            @if($contract->status === 'active') ✓ 
                            @elseif($contract->status === 'expired') ⏰
                            @elseif($contract->status === 'terminated') ⏹
                            @else ○ @endif
                        </div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 min-h-[100px]">
                        @if($contract->status === 'active')
                            <div class="workflow-node bg-white rounded-lg p-3 shadow-sm border-l-4 border-green-400">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium">Contract Active</span>
                                    <span class="text-xs text-gray-500">Live</span>
                                </div>
                                <p class="text-xs text-gray-600 mt-1">
                                    @if($contract->end_date)
                                        Expires {{ $contract->end_date->format('M j, Y') }}
                                    @else
                                        Ongoing contract
                                    @endif
                                </p>
                                @if($contract->billing_model !== 'fixed')
                                    <p class="text-xs text-green-600 mt-1 font-medium">${{ number_format($contract->current_monthly_amount ?? 0, 0) }}/month</p>
                                @endif
                            </div>
                        @elseif($contract->status === 'expired')
                            <div class="workflow-node bg-white rounded-lg p-3 shadow-sm border-l-4 border-red-400">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium">Contract Expired</span>
                                    <span class="text-xs text-gray-500">{{ $contract->end_date ? $contract->end_date->format('M j') : '' }}</span>
                                </div>
                                <p class="text-xs text-gray-600 mt-1">Renewal required</p>
                            </div>
                        @elseif($contract->status === 'terminated')
                            <div class="workflow-node bg-white rounded-lg p-3 shadow-sm border-l-4 border-gray-400">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium">Terminated</span>
                                    <span class="text-xs text-gray-500">Ended</span>
                                </div>
                                <p class="text-xs text-gray-600 mt-1">Contract terminated</p>
                            </div>
                        @else
                            <div class="text-center py-4 text-gray-400">
                                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-xs">Pending activation</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Workflow Actions -->
            <div class="mt-6 flex items-center justify-center space-x-4">
                @if($contract->status === 'draft')
                    <button onclick="submitForApproval()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                        Submit for Review
                    </button>
                @elseif($contract->status === 'under_negotiation')
                    <button onclick="openNegotiation()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        Continue Negotiation
                    </button>
                @elseif($contract->status === 'pending_signature')
                    <button onclick="sendForSignature()" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Send for Signature
                    </button>
                @endif
                
                <!-- View Timeline -->
                <button @click="showTimeline = !showTimeline" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    View Timeline
                </button>
            </div>
        </div>
    </div>
    
    <!-- Tab Navigation -->
    <ul class="nav nav-tabs mb-0" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#overview" type="button">
                <i class="fas fa-info-circle me-1"></i> Overview
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#content" type="button">
                <i class="fas fa-file-alt me-1"></i> Contract Content
            </button>
        </li>
        @if($contract->approvals->count() > 0)
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#approvals" type="button">
                <i class="fas fa-clipboard-check me-1"></i> Approvals
                @if($contract->approvals->where('status', 'pending')->count() > 0)
                    <span class="badge bg-warning ml-1">{{ $contract->approvals->where('status', 'pending')->count() }}</span>
                @endif
            </button>
        </li>
        @endif
        @if($contract->signatures->count() > 0)
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#signatures" type="button">
                <i class="fas fa-signature me-1"></i> Signatures
            </button>
        </li>
        @endif
        @if($contract->milestones->count() > 0)
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#milestones" type="button">
                <i class="fas fa-tasks me-1"></i> Milestones
            </button>
        </li>
        @endif
        @if($contract->billing_model !== 'fixed')
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#billing" type="button">
                <i class="fas fa-calculator me-1"></i> Billing & Usage
                @if($contract->billing_calculations_count > 0)
                    <span class="badge bg-success ml-1">{{ $contract->billing_calculations_count }}</span>
                @endif
            </button>
        </li>
        @endif
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#history" type="button">
                <i class="fas fa-history me-1"></i> History
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Overview Tab -->
        <div class="tab-pane fade show active" id="overview">
            <div class="bg-white rounded-lg shadow-md overflow-hidden tab-content-bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    <div class="row">
                        <!-- Contract Details -->
                        <div class="col-lg-8">
                            <h5 class="card-title">Contract Details</h5>
                            <div class="row mb-4">
                                <div class="col-sm-6">
                                    <dl class="row">
                                        <dt class="col-sm-5">Contract Number:</dt>
                                        <dd class="col-sm-7">{{ $contract->contract_number }}</dd>
                                        
                                        <dt class="col-sm-5">Contract Type:</dt>
                                        <dd class="col-sm-7">{{ ucwords(str_replace('_', ' ', $contract->contract_type)) }}</dd>
                                        
                                        <dt class="col-sm-5">Start Date:</dt>
                                        <dd class="col-sm-7">{{ $contract->start_date ? $contract->start_date->format('M d, Y') : 'Not set' }}</dd>
                                        
                                        <dt class="col-sm-5">End Date:</dt>
                                        <dd class="col-sm-7">{{ $contract->end_date ? $contract->end_date->format('M d, Y') : 'Open-ended' }}</dd>
                                    </dl>
                                </div>
                                <div class="col-sm-6">
                                    <dl class="row">
                                        <dt class="col-sm-5">Contract Value:</dt>
                                        <dd class="col-sm-7">${{ number_format($contract->contract_value, 2) }}</dd>
                                        
                                        <dt class="col-sm-5">Currency:</dt>
                                        <dd class="col-sm-7">{{ strtoupper($contract->currency) }}</dd>
                                        
                                        <dt class="col-sm-5">Created:</dt>
                                        <dd class="col-sm-7">{{ $contract->created_at->format('M d, Y') }}</dd>
                                        
                                        <dt class="col-sm-5">Last Updated:</dt>
                                        <dd class="col-sm-7">{{ $contract->updated_at->format('M d, Y') }}</dd>
                                    </dl>
                                </div>
                            </div>

                            @if($contract->description)
                                <h6>Description</h6>
                                <p class="mb-4">{{ $contract->description }}</p>
                            @endif

                            @if($contract->terms && !empty($contract->terms))
                                <h6>Terms & Conditions</h6>
                                <div class="mb-4">
                                    @foreach($contract->terms as $term)
                                        <div class="mb-2">
                                            <strong>{{ $term['section'] ?? 'General' }}:</strong>
                                            <p class="mb-0">{{ $term['content'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- Sidebar Info -->
                        <div class="col-lg-4">
                            <!-- Client Information -->
                            @if($contract->client)
                                <div class="card mb-3">
                                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                        <h6 class="mb-0"><i class="fas fa-user me-2"></i>Client Information</h6>
                                    </div>
                                    <div class="p-6">
                                        <h6>{{ $contract->client->name }}</h6>
                                        @if($contract->client->email)
                                            <p class="mb-1"><i class="fas fa-envelope me-2"></i>{{ $contract->client->email }}</p>
                                        @endif
                                        @if($contract->client->phone)
                                            <p class="mb-1"><i class="fas fa-phone me-2"></i>{{ $contract->client->phone }}</p>
                                        @endif
                                        @if($contract->client->address)
                                            <p class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>{{ $contract->client->address }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Progress Summary -->
                            @if($contract->milestones->count() > 0)
                                <div class="card mb-3">
                                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                        <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Progress Summary</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <div class="flex justify-between mb-1">
                                                <small>Overall Progress</small>
                                                <small>{{ $contract->completion_percentage ?? 0 }}%</small>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-success" style="width: {{ $contract->completion_percentage ?? 0 }}%"></div>
                                            </div>
                                        </div>

                                        <div class="row text-center">
                                            <div class="col-4">
                                                <div class="h5 mb-0 text-green-600">{{ $contract->milestones->where('status', 'completed')->count() }}</div>
                                                <small class="text-gray-600">Completed</small>
                                            </div>
                                            <div class="col-4">
                                                <div class="h5 mb-0 text-warning">{{ $contract->milestones->where('status', 'in_progress')->count() }}</div>
                                                <small class="text-gray-600">In Progress</small>
                                            </div>
                                            <div class="col-4">
                                                <div class="h5 mb-0 text-secondary">{{ $contract->milestones->where('status', 'pending')->count() }}</div>
                                                <small class="text-muted">Pending</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Quick Stats -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Contract Stats</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-between align-items-center mb-2">
                                        <span>Approvals:</span>
                                        <span class="fw-bold">{{ $contract->approvals->count() }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span>Signatures:</span>
                                        <span class="fw-bold">{{ $contract->signatures->count() }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span>Milestones:</span>
                                        <span class="fw-bold">{{ $contract->milestones->count() }}</span>
                                    </div>
                                    @if($contract->amendments->count() > 0)
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>Amendments:</span>
                                            <span class="fw-bold">{{ $contract->amendments->count() }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Contract Content Tab with Collaboration -->
        <div class="tab-pane fade" id="content" x-data="contractCollaboration()">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <!-- Enhanced Header with Collaboration Tools -->
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <h3 class="text-lg font-semibold text-gray-900">Contract Content</h3>
                            
                            <!-- Active Users -->
                            <div class="flex items-center space-x-2">
                                <div class="flex -space-x-2">
                                    <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center text-sm font-medium border-2 border-white">
                                        JD
                                    </div>
                                    <div class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center text-sm font-medium border-2 border-white">
                                        MS
                                    </div>
                                    <div class="w-8 h-8 rounded-full bg-purple-500 text-white flex items-center justify-center text-sm font-medium border-2 border-white">
                                        +2
                                    </div>
                                </div>
                                <span class="text-sm text-gray-600">4 people viewing</span>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-3">
                            <!-- Collaboration Controls -->
                            <button @click="showComments = !showComments" 
                                    :class="showComments ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:text-gray-800'"
                                    class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                Comments
                                <span x-show="comments.length > 0" class="ml-2 px-2 py-1 bg-blue-200 text-blue-800 rounded-full text-xs" x-text="comments.length"></span>
                            </button>
                            
                            <button @click="showVersions = !showVersions" 
                                    :class="showVersions ? 'bg-purple-100 text-purple-700' : 'text-gray-600 hover:text-gray-800'"
                                    class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Versions
                            </button>
                            
                            <!-- Export Options -->
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    Export
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                
                                <div x-show="open" @click.away="open = false" 
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="transform opacity-0 scale-95"
                                     x-transition:enter-end="transform opacity-100 scale-100"
                                     class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-20">
                                    <div class="py-1">
                                        <a href="{{ route('contracts.pdf', $contract) }}" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Download PDF</a>
                                        <a href="{{ route('contracts.word', $contract) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Export to Word</a>
                                        <a href="{{ route('contracts.html', $contract) }}" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">View HTML</a>
                                        <div class="border-t border-gray-100 my-1"></div>
                                        <button @click="shareContract()" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Share Link</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Split-Pane Layout -->
                <div class="flex flex-col lg:flex-row" style="min-height: 600px;">
                    <!-- Main Content Area -->
                    <div :class="showComments || showVersions ? 'lg:w-2/3' : 'w-full'" class="flex-1">
                        @if($contract->content)
                            <!-- Interactive Document Viewer -->
                            <div class="relative h-full overflow-auto">
                                <div class="p-6 bg-white" style="font-family: 'Times New Roman', serif; line-height: 1.8; font-size: 16px;" @click="handleDocumentClick($event)">
                                    <!-- Document with annotation capabilities -->
                                    <div class="contract-content" x-html="processContent(`{!! addslashes($contract->content) !!}`)">
                                        {!! $contract->content !!}
                                    </div>
                                    
                                    <!-- Floating comment indicators -->
                                    <template x-for="comment in comments" :key="comment.id">
                                        <div class="absolute w-3 h-3 bg-yellow-400 rounded-full border-2 border-white shadow-lg cursor-pointer hover:scale-110 transition-transform"
                                             :style="`top: ${comment.position.y}px; left: ${comment.position.x}px;`"
                                             @click="showCommentDetail(comment)"
                                             :title="comment.preview">
                                        </div>
                                    </template>
                                </div>
                            </div>
                        @else
                            <div class="flex items-center justify-center h-full p-12">
                                <div class="text-center max-w-md">
                                    <div class="w-20 h-20 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-xl font-semibold text-gray-900 mb-4">No Content Available</h3>
                                    <p class="text-gray-600 mb-6">This contract doesn't have content yet. Generate content using a template or add custom content.</p>
                                    @if($contract->canBeEdited())
                                        <div class="flex items-center justify-center space-x-3">
                                            <a href="{{ route('contracts.edit', $contract) }}" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                Add Content
                                            </a>
                                            <button @click="generateFromTemplate()" class="inline-flex items-center px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                                </svg>
                                                Use Template
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Comments Sidebar -->
                    <div x-show="showComments" 
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform translate-x-full"
                         x-transition:enter-end="opacity-100 transform translate-x-0"
                         class="lg:w-1/3 border-l border-gray-200 bg-gray-50">
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="font-semibold text-gray-900">Comments & Discussions</h4>
                                <button @click="showComments = false" class="lg:hidden text-gray-400 hover:text-gray-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- Comment Threads -->
                            <div class="space-y-4 mb-6 max-h-96 overflow-y-auto">
                                <template x-for="comment in comments" :key="comment.id">
                                    <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200">
                                        <div class="flex items-start space-x-3">
                                            <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-xs font-medium" x-text="comment.author.initials"></div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center space-x-2 mb-1">
                                                    <span class="text-sm font-medium text-gray-900" x-text="comment.author.name"></span>
                                                    <span class="text-xs text-gray-500" x-text="comment.created_at"></span>
                                                </div>
                                                <p class="text-sm text-gray-700" x-text="comment.content"></p>
                                                <div x-show="comment.replies && comment.replies.length > 0" class="mt-3 pl-4 border-l-2 border-gray-200">
                                                    <template x-for="reply in comment.replies" :key="reply.id">
                                                        <div class="mb-2">
                                                            <div class="flex items-center space-x-2 mb-1">
                                                                <span class="text-xs font-medium text-gray-800" x-text="reply.author.name"></span>
                                                                <span class="text-xs text-gray-400" x-text="reply.created_at"></span>
                                                            </div>
                                                            <p class="text-xs text-gray-600" x-text="reply.content"></p>
                                                        </div>
                                                    </template>
                                                </div>
                                                <div class="mt-2 flex items-center space-x-2">
                                                    <button @click="replyToComment(comment)" class="text-xs text-blue-600 hover:text-blue-700">Reply</button>
                                                    <button @click="resolveComment(comment)" class="text-xs text-green-600 hover:text-green-700">Resolve</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                
                                <div x-show="comments.length === 0" class="text-center py-8 text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                    <p class="text-sm">No comments yet.</p>
                                    <p class="text-xs text-gray-400 mt-1">Click on the document to add comments.</p>
                                </div>
                            </div>
                            
                            <!-- Add Comment Form -->
                            <div x-show="showCommentForm" class="bg-white rounded-lg p-4 shadow-sm border border-gray-200">
                                <h5 class="text-sm font-medium text-gray-900 mb-3">Add Comment</h5>
                                <textarea x-model="newComment" rows="3" placeholder="Add your comment here..."
                                         class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
                                <div class="flex items-center justify-end space-x-2 mt-3">
                                    <button @click="cancelComment()" class="px-3 py-1.5 text-sm text-gray-600 hover:text-gray-800 transition-colors">
                                        Cancel
                                    </button>
                                    <button @click="submitComment()" class="px-4 py-1.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                                        Add Comment
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Versions Sidebar -->
                    <div x-show="showVersions" 
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform translate-x-full"
                         x-transition:enter-end="opacity-100 transform translate-x-0"
                         class="lg:w-1/3 border-l border-gray-200 bg-gray-50">
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="font-semibold text-gray-900">Version History</h4>
                                <button @click="showVersions = false" class="lg:hidden text-gray-400 hover:text-gray-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- Version Timeline -->
                            <div class="space-y-3">
                                <template x-for="version in versions" :key="version.id">
                                    <div class="bg-white rounded-lg p-3 shadow-sm border border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors"
                                         @click="selectVersion(version)">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-medium text-gray-900" x-text="'Version ' + version.version"></span>
                                            <span class="text-xs text-gray-500" x-text="version.created_at"></span>
                                        </div>
                                        <p class="text-xs text-gray-600 mb-2" x-text="version.changes_summary"></p>
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-gray-500" x-text="'By ' + version.author.name"></span>
                                            <div class="flex items-center space-x-1">
                                                <span x-show="version.is_current" class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Current</span>
                                                <button @click.stop="compareVersion(version)" class="text-xs text-blue-600 hover:text-blue-700">Compare</button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approvals Tab -->
        @if($contract->approvals->count() > 0)
        <div class="tab-pane fade" id="approvals">
            <div class="card tab-content-card">
                <div class="card-body">
                    <h5 class="card-title">Approval Workflow</h5>
                    
                    <div class="approval-timeline">
                        @foreach($contract->approvals->sortBy('approval_order') as $approval)
                            <div class="approval-item">
                                <div class="approval-icon {{ $approval->status }}">
                                    @if($approval->status === 'approved')
                                        <i class="fas fa-check"></i>
                                    @elseif($approval->status === 'rejected')
                                        <i class="fas fa-times"></i>
                                    @elseif($approval->status === 'pending')
                                        <i class="fas fa-clock"></i>
                                    @else
                                        <i class="fas fa-question"></i>
                                    @endif
                                </div>
                                
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-0">{{ $approval->approval_level_label }}</h6>
                                            <span class="badge bg-{{ $approval->status === 'approved' ? 'success' : ($approval->status === 'rejected' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($approval->status) }}
                                            </span>
                                        </div>
                                        
                                        <p class="text-muted mb-2">
                                            <i class="fas fa-user me-1"></i>
                                            {{ $approval->approverUser->name ?? 'Role: ' . ucwords($approval->approver_role) }}
                                        </p>
                                        
                                        @if($approval->required_by)
                                            <p class="small text-muted mb-2">
                                                <i class="fas fa-calendar me-1"></i>
                                                Required by: {{ $approval->required_by->format('M d, Y H:i') }}
                                                @if($approval->is_overdue)
                                                    <span class="text-red-600 ml-1">(Overdue)</span>
                                                @endif
                                            </p>
                                        @endif
                                        
                                        @if($approval->comments)
                                            <div class="mt-2">
                                                <strong>Comments:</strong>
                                                <p class="mb-0">{{ $approval->comments }}</p>
                                            </div>
                                        @endif
                                        
                                        @if($approval->conditions && !empty($approval->conditions))
                                            <div class="mt-2">
                                                <strong>Conditions:</strong>
                                                <ul class="mb-0">
                                                    @foreach($approval->conditions as $condition)
                                                        <li>{{ $condition }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                        
                                        @if($approval->status !== 'pending' && ($approval->approved_at || $approval->rejected_at))
                                            <small class="text-muted">
                                                {{ ucfirst($approval->status) }} on 
                                                {{ ($approval->approved_at ?? $approval->rejected_at)->format('M d, Y H:i') }}
                                            </small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Signatures Tab -->
        @if($contract->signatures->count() > 0)
        <div class="tab-pane fade" id="signatures">
            <div class="card tab-content-card">
                <div class="card-body">
                    <h5 class="card-title">Digital Signatures</h5>
                    
                    <div class="row">
                        @foreach($contract->signatures as $signature)
                            <div class="col-md-6 mb-3">
                                <div class="signature-status {{ $signature->status === 'signed' ? 'completed' : 'pending' }}">
                                    <h6 class="mb-2">{{ $signature->signer_name }}</h6>
                                    <p class="mb-2">{{ $signature->signer_email }}</p>
                                    
                                    <div class="mb-2">
                                        <span class="badge bg-{{ $signature->status === 'signed' ? 'success' : ($signature->status === 'sent' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($signature->status) }}
                                        </span>
                                    </div>
                                    
                                    @if($signature->signed_at)
                                        <small class="text-muted">
                                            Signed on {{ $signature->signed_at->format('M d, Y H:i') }}
                                        </small>
                                    @elseif($signature->sent_at)
                                        <small class="text-muted">
                                            Sent on {{ $signature->sent_at->format('M d, Y H:i') }}
                                        </small>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Milestones Tab -->
        @if($contract->milestones->count() > 0)
        <div class="tab-pane fade" id="milestones">
            <div class="card tab-content-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0">Project Milestones</h5>
                        @if($contract->canBeEdited())
                            <button class="btn btn-primary btn-sm" @click="$dispatch('open-modal', 'modal-id')" data-bs-target="#addMilestoneModal">
                                <i class="fas fa-plus me-1"></i> Add Milestone
                            </button>
                        @endif
                    </div>
                    
                    @foreach($contract->milestones->sortBy('due_date') as $milestone)
                        <div class="milestone-card card mb-3 {{ $milestone->status }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0">{{ $milestone->title }}</h6>
                                    <span class="badge bg-{{ $milestone->status === 'completed' ? 'success' : ($milestone->status === 'in_progress' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst(str_replace('_', ' ', $milestone->status)) }}
                                    </span>
                                </div>
                                
                                @if($milestone->description)
                                    <p class="text-muted mb-2">{{ $milestone->description }}</p>
                                @endif
                                
                                <div class="row">
                                    <div class="col-sm-6">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            Due: {{ $milestone->due_date ? $milestone->due_date->format('M d, Y') : 'No due date' }}
                                        </small>
                                    </div>
                                    <div class="col-sm-6">
                                        @if($milestone->amount)
                                            <small class="text-muted">
                                                <i class="fas fa-dollar-sign me-1"></i>
                                                Value: ${{ number_format($milestone->amount, 2) }}
                                            </small>
                                        @endif
                                    </div>
                                </div>
                                
                                @if($milestone->completed_at)
                                    <small class="text-green-600">
                                        <i class="fas fa-check me-1"></i>
                                        Completed on {{ $milestone->completed_at->format('M d, Y') }}
                                    </small>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Billing & Usage Tab -->
        @if($contract->billing_model !== 'fixed')
        <div class="tab-pane fade" id="billing">
            <div class="card tab-content-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0">Billing Configuration & Usage</h5>
                        <div class="d-flex gap-2">
                            <a href="{{ route('financial.contracts.usage-dashboard', $contract) }}" 
                               class="btn btn-outline-success btn-sm">
                                <i class="fas fa-chart-line me-1"></i> View Full Dashboard
                            </a>
                        </div>
                    </div>

                    <!-- Billing Model Overview -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Billing Model</h6>
                                    <h4 class="text-primary">{{ ucwords(str_replace('_', ' ', $contract->billing_model)) }}</h4>
                                    <p class="card-text text-muted">
                                        @switch($contract->billing_model)
                                            @case('per_asset')
                                                Bills based on the number of managed devices assigned to this contract.
                                                @break
                                            @case('per_contact')
                                                Bills based on the number of contacts with portal access under this contract.
                                                @break
                                            @case('tiered')
                                                Uses volume-based pricing tiers with different rates based on usage levels.
                                                @break
                                            @case('hybrid')
                                                Combines multiple billing models for complex billing scenarios.
                                                @break
                                        @endswitch
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Current Monthly Amount</h6>
                                    <h4 class="text-success">${{ number_format($contract->current_monthly_amount ?? 0, 2) }}</h4>
                                    <small class="text-muted">
                                        Last calculated: {{ $contract->last_billing_calculation ? $contract->last_billing_calculation->format('M d, Y') : 'Never' }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Usage Summary -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <h4 class="mb-1">{{ $contract->asset_assignments_count ?? 0 }}</h4>
                                <small class="text-muted">Assigned Assets</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <h4 class="mb-1">{{ $contract->contact_assignments_count ?? 0 }}</h4>
                                <small class="text-muted">Assigned Contacts</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <h4 class="mb-1">{{ $contract->billing_calculations_count ?? 0 }}</h4>
                                <small class="text-muted">Billing Calculations</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <h4 class="mb-1">${{ number_format(($contract->total_billed_amount ?? 0) / 1000, 1) }}k</h4>
                                <small class="text-muted">Total Billed</small>
                            </div>
                        </div>
                    </div>

                    <!-- Asset Assignments (if applicable) -->
                    @if(in_array($contract->billing_model, ['per_asset', 'hybrid']))
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6>Asset Assignments</h6>
                                <a href="{{ route('financial.contracts.asset-assignments', $contract) }}" 
                                   class="btn btn-outline-primary btn-sm">Manage Assets</a>
                            </div>
                            @if($contract->asset_assignments_count > 0)
                                <div class="row">
                                    @foreach($contract->asset_type_counts ?? [] as $type => $count)
                                        <div class="col-md-3 mb-2">
                                            <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                                <span class="text-capitalize">{{ str_replace('_', ' ', $type) }}</span>
                                                <span class="badge bg-primary">{{ $count }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-3 text-muted">
                                    <i class="fas fa-server fa-2x mb-2"></i>
                                    <p>No assets assigned to this contract yet.</p>
                                    <a href="{{ route('financial.contracts.asset-assignments', $contract) }}" 
                                       class="btn btn-primary btn-sm">Assign Assets</a>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Contact Assignments (if applicable) -->
                    @if(in_array($contract->billing_model, ['per_contact', 'hybrid']))
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6>Contact Assignments</h6>
                                <a href="{{ route('financial.contracts.contact-assignments', $contract) }}" 
                                   class="btn btn-outline-primary btn-sm">Manage Contacts</a>
                            </div>
                            @if($contract->contact_assignments_count > 0)
                                <div class="row">
                                    @foreach($contract->contact_access_counts ?? [] as $tier => $count)
                                        <div class="col-md-3 mb-2">
                                            <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                                <span class="text-capitalize">{{ str_replace('_', ' ', $tier) }} Access</span>
                                                <span class="badge bg-primary">{{ $count }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-3 text-muted">
                                    <i class="fas fa-users fa-2x mb-2"></i>
                                    <p>No contacts assigned to this contract yet.</p>
                                    <a href="{{ route('financial.contracts.contact-assignments', $contract) }}" 
                                       class="btn btn-primary btn-sm">Assign Contacts</a>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Recent Billing Calculations -->
                    @if($contract->billing_calculations_count > 0)
                        <div class="mb-4">
                            <h6 class="mb-3">Recent Billing Calculations</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Period</th>
                                            <th>Asset Billing</th>
                                            <th>Contact Billing</th>
                                            <th>Total Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($contract->recent_billing_calculations ?? [] as $calculation)
                                            <tr>
                                                <td>{{ $calculation->billing_period }}</td>
                                                <td>${{ number_format($calculation->asset_billing_amount, 2) }}</td>
                                                <td>${{ number_format($calculation->contact_billing_amount, 2) }}</td>
                                                <td><strong>${{ number_format($calculation->total_amount, 2) }}</strong></td>
                                                <td>
                                                    <span class="badge bg-{{ $calculation->status === 'calculated' ? 'warning' : ($calculation->status === 'invoiced' ? 'success' : 'info') }}">
                                                        {{ ucfirst($calculation->status) }}
                                                    </span>
                                                </td>
                                                <td>{{ $calculation->calculated_at->format('M d, Y') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <!-- Automation Settings -->
                    @if($contract->auto_assign_new_assets || $contract->auto_assign_new_contacts || $contract->auto_generate_invoices)
                        <div class="mb-4">
                            <h6 class="mb-3">Automation Settings</h6>
                            <div class="row">
                                @if($contract->auto_assign_new_assets)
                                    <div class="col-md-4 mb-2">
                                        <div class="d-flex align-items-center p-2 bg-light rounded">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <span class="small">Auto-assign new assets</span>
                                        </div>
                                    </div>
                                @endif
                                @if($contract->auto_assign_new_contacts)
                                    <div class="col-md-4 mb-2">
                                        <div class="d-flex align-items-center p-2 bg-light rounded">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <span class="small">Auto-assign new contacts</span>
                                        </div>
                                    </div>
                                @endif
                                @if($contract->auto_generate_invoices)
                                    <div class="col-md-4 mb-2">
                                        <div class="d-flex align-items-center p-2 bg-light rounded">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <span class="small">Auto-generate invoices</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- History Tab -->
        <div class="tab-pane fade" id="history">
            <div class="card tab-content-card">
                <div class="card-body">
                    <h5 class="card-title">Contract History</h5>
                    
                    <div class="timeline">
                        @forelse($contract->getAuditHistory() as $event)
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0">
                                    <div class="rounded-full bg-blue-600 text-white d-flex align-items-center justify-center" 
                                         style="width: 32px; height: 32px;">
                                        <i class="fas fa-{{ $event['icon'] ?? 'circle' }} fa-sm"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="mb-1">
                                        <strong>{{ $event['description'] }}</strong>
                                        @if($event['user'] ?? null)
                                            by {{ $event['user'] }}
                                        @endif
                                    </p>
                                    <small class="text-muted">{{ $event['date'] }}</small>
                                    @if($event['details'] ?? null)
                                        <div class="mt-1">
                                            <small class="text-muted">{{ $event['details'] }}</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <i class="fas fa-history text-muted mb-2" style="font-size: 2rem;"></i>
                                <p class="text-muted">No history available for this contract.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function workflowKanban() {
    return {
        showTimeline: false,
        
        init() {
            // Initialize workflow interactions
        },
        
        moveToStage(contractId, stage) {
            // Handle workflow stage transitions
            fetch(`/api/contracts/${contractId}/workflow/${stage}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error updating workflow: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating workflow');
            });
        },
        
        showWorkflowDetails(stage) {
            // Show detailed information about a workflow stage
            console.log('Showing details for stage:', stage);
        }
    };
}

function openNegotiation() {
    // Open negotiation interface
    window.location.href = `/financial/contracts/{{ $contract->id }}/negotiation`;
}

function contractCollaboration() {
    return {
        showComments: false,
        showVersions: false,
        showCommentForm: false,
        newComment: '',
        selectedPosition: null,
        comments: [
            {
                id: 1,
                content: 'The payment terms section needs clarification on the 30-day period.',
                author: { name: 'John Doe', initials: 'JD' },
                created_at: '2 hours ago',
                position: { x: 400, y: 200 },
                preview: 'Payment terms clarification needed',
                replies: [
                    {
                        id: 11,
                        content: 'Agreed, we should specify business days vs calendar days.',
                        author: { name: 'Sarah Wilson', initials: 'SW' },
                        created_at: '1 hour ago'
                    }
                ]
            },
            {
                id: 2,
                content: 'Should we include the automatic renewal clause here?',
                author: { name: 'Mike Smith', initials: 'MS' },
                created_at: '1 day ago',
                position: { x: 350, y: 450 },
                preview: 'Question about renewal clause',
                replies: []
            }
        ],
        versions: [
            {
                id: 1,
                version: '1.0',
                changes_summary: 'Initial contract draft created',
                author: { name: 'John Doe' },
                created_at: '3 days ago',
                is_current: false
            },
            {
                id: 2,
                version: '1.1',
                changes_summary: 'Updated payment terms and added SLA requirements',
                author: { name: 'Sarah Wilson' },
                created_at: '2 days ago',
                is_current: false
            },
            {
                id: 3,
                version: '1.2',
                changes_summary: 'Incorporated client feedback on termination clause',
                author: { name: 'Mike Smith' },
                created_at: '1 day ago',
                is_current: true
            }
        ],
        
        init() {
            // Initialize real-time collaboration
            this.connectToCollaboration();
        },
        
        handleDocumentClick(event) {
            // Handle clicks on the document for adding comments
            const rect = event.currentTarget.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;
            
            if (event.detail === 2) { // Double click
                this.selectedPosition = { x, y };
                this.showCommentForm = true;
                this.showComments = true;
            }
        },
        
        processContent(content) {
            // Process content to add annotation capabilities
            return content;
        },
        
        submitComment() {
            if (this.newComment.trim()) {
                const comment = {
                    id: Date.now(),
                    content: this.newComment,
                    author: { name: 'Current User', initials: 'CU' },
                    created_at: 'Just now',
                    position: this.selectedPosition || { x: 100, y: 100 },
                    preview: this.newComment.substring(0, 30) + '...',
                    replies: []
                };
                
                this.comments.push(comment);
                this.newComment = '';
                this.showCommentForm = false;
                
                // Send to server
                this.saveComment(comment);
            }
        },
        
        cancelComment() {
            this.newComment = '';
            this.showCommentForm = false;
            this.selectedPosition = null;
        },
        
        saveComment(comment) {
            fetch(`/api/contracts/{{ $contract->id }}/comments`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    content: comment.content,
                    position: comment.position
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Comment saved successfully');
                } else {
                    console.error('Error saving comment:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        },
        
        replyToComment(comment) {
            const reply = prompt('Enter your reply:');
            if (reply) {
                comment.replies.push({
                    id: Date.now(),
                    content: reply,
                    author: { name: 'Current User', initials: 'CU' },
                    created_at: 'Just now'
                });
            }
        },
        
        resolveComment(comment) {
            if (confirm('Mark this comment as resolved?')) {
                const index = this.comments.findIndex(c => c.id === comment.id);
                if (index > -1) {
                    this.comments.splice(index, 1);
                }
            }
        },
        
        showCommentDetail(comment) {
            this.showComments = true;
            // Scroll to comment in sidebar
            setTimeout(() => {
                const commentEl = document.querySelector(`[data-comment-id="${comment.id}"]`);
                if (commentEl) {
                    commentEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 300);
        },
        
        selectVersion(version) {
            // Switch to selected version
            console.log('Switching to version:', version.version);
            // In real implementation, this would load the version content
        },
        
        compareVersion(version) {
            // Show version comparison
            console.log('Comparing version:', version.version);
            // In real implementation, this would show a diff view
        },
        
        shareContract() {
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(() => {
                this.showNotification('Contract link copied to clipboard!', 'success');
            });
        },
        
        generateFromTemplate() {
            // Generate content from template
            console.log('Generating content from template');
        },
        
        connectToCollaboration() {
            // Initialize WebSocket connection for real-time collaboration
            // In real implementation, this would establish WebSocket connection
            console.log('Connecting to collaboration server...');
        },
        
        showNotification(message, type = 'info') {
            // Create and show a notification
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300 ${
                type === 'success' ? 'bg-green-500 text-white' : 
                type === 'error' ? 'bg-red-500 text-white' : 
                'bg-blue-500 text-white'
            }`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            // Remove after 5 seconds
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 5000);
        }
    };
}

<script>
function submitForApproval() {
    if (confirm('Are you sure you want to submit this contract for approval?')) {
        fetch(`/api/contracts/{{ $contract->id }}/submit-approval`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error submitting for approval: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error submitting for approval');
        });
    }
}

function sendForSignature() {
    if (confirm('Send this contract for digital signature?')) {
        fetch(`/api/contracts/{{ $contract->id }}/send-signature`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error sending for signature: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error sending for signature');
        });
    }
}

function terminateContract() {
    if (confirm('Are you sure you want to terminate this contract? This action cannot be undone.')) {
        const reason = prompt('Please provide a reason for termination:');
        if (reason) {
            fetch(`/api/contracts/{{ $contract->id }}/terminate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ reason: reason })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error terminating contract: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error terminating contract');
            });
        }
    }
}

// Enhanced workflow interactions
document.addEventListener('DOMContentLoaded', function() {
    // Add workflow stage hover effects
    const stages = document.querySelectorAll('.workflow-stage');
    stages.forEach(stage => {
        stage.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.transition = 'transform 0.2s ease';
        });
        stage.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Add workflow node click handlers
    const nodes = document.querySelectorAll('.workflow-node');
    nodes.forEach(node => {
        node.addEventListener('click', function(e) {
            e.stopPropagation();
            // Show detailed information
            console.log('Workflow node clicked');
        });
    });
})
</script>
@endpush