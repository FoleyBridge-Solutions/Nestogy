@extends('client-portal.layouts.app')

@section('title', 'Support Tickets')

@section('content')
<div class="portal-container">
    <!-- Header -->
    <div class="portal-row portal-mb-4">
        <div class="portal-col-12">
            <div class="portal-d-flex portal-justify-content-between portal-align-items-center">
                <div>
                    <h1 class="portal-text-3xl portal-mb-0 text-gray-800">Support Tickets</h1>
                    <p class="text-gray-600">Manage your support requests and track their progress</p>
                </div>
                @php
                    $permissions = $contact->portal_permissions ?? [];
                @endphp
                @if(in_array('can_create_tickets', $permissions))
                <div>
                    <a href="{{ route('client.tickets.create') ?? '#' }}" class="portal-btn portal-btn-primary">
                        <i class="fas fa-plus portal-mr-2"></i>New Ticket
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="portal-row portal-mb-4">
        <div class="portal-col-6 portal-col-xl-3 portal-mb-4">
            <div class="portal-card portal-card-border-primary portal-shadow portal-h-100">
                <div class="portal-card-body portal-py-4">
                    <div class="portal-d-flex portal-align-items-center">
                        <div class="portal-col portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-primary portal-uppercase portal-mb-1">
                                Total Tickets
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800">
                                {{ $stats['total_tickets'] ?? 0 }}
                            </div>
                        </div>
                        <div class="portal-col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="portal-col-6 portal-col-xl-3 portal-mb-4">
            <div class="portal-card portal-card-border-danger portal-shadow portal-h-100">
                <div class="portal-card-body portal-py-4">
                    <div class="portal-d-flex portal-align-items-center">
                        <div class="portal-col portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-danger portal-uppercase portal-mb-1">
                                Open Tickets
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800">
                                {{ $stats['open_tickets'] ?? 0 }}
                            </div>
                        </div>
                        <div class="portal-col-auto">
                            <i class="fas fa-ticket-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="portal-col-6 portal-col-xl-3 portal-mb-4">
            <div class="portal-card portal-card-border-success portal-shadow portal-h-100">
                <div class="portal-card-body portal-py-4">
                    <div class="portal-d-flex portal-align-items-center">
                        <div class="portal-col portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-success portal-uppercase portal-mb-1">
                                Resolved This Month
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800">
                                {{ $stats['resolved_this_month'] ?? 0 }}
                            </div>
                        </div>
                        <div class="portal-col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="portal-col-6 portal-col-xl-3 portal-mb-4">
            <div class="portal-card portal-card-border-warning portal-shadow portal-h-100">
                <div class="portal-card-body portal-py-4">
                    <div class="portal-d-flex portal-align-items-center">
                        <div class="portal-col portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-warning portal-uppercase portal-mb-1">
                                Avg Response Time
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800">
                                {{ $stats['avg_response_time'] ?? '< 1h' }}
                            </div>
                        </div>
                        <div class="portal-col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tickets Table -->
    <div class="portal-row">
        <div class="portal-col-12">
            <div class="portal-card portal-shadow">
                <div class="px-6 py-4 portal-border-b portal-bg-gray-50 py-3">
                    <h6 class="portal-mb-0 portal-font-bold portal-text-primary">
                        <i class="fas fa-ticket-alt portal-mr-2"></i>Your Support Tickets
                    </h6>
                </div>
                <div class="portal-card-body">
                    @if(count($tickets) > 0)
                    <div class="portal-table-responsive">
                        <table class="portal-table portal-min-w-full">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">
                                        Ticket #
                                    </th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">
                                        Subject
                                    </th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">
                                        Priority
                                    </th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">
                                        Status
                                    </th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">
                                        Created
                                    </th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">
                                        Last Updated
                                    </th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="portal-divide-y portal-divide-gray-200">
                                @foreach($tickets as $ticket)
                                <tr class="portal-table-row">
                                    <td class="px-4 py-4 portal-text-sm">
                                        <div class="portal-font-bold portal-text-primary">
                                            #{{ $ticket->number ?? $ticket->id }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="portal-text-sm portal-font-medium text-gray-900">
                                            {{ Str::limit($ticket->subject, 50) }}
                                        </div>
                                        <div class="portal-text-xs text-gray-500">
                                            {{ Str::limit($ticket->details ?? '', 80) }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="inline-flex px-2 py-1 portal-text-xs portal-font-medium rounded-full
                                            @if($ticket->priority === 'Critical') bg-red-100 text-red-800
                                            @elseif($ticket->priority === 'High') bg-orange-100 text-orange-800
                                            @elseif($ticket->priority === 'Medium') bg-blue-100 text-blue-800
                                            @elseif($ticket->priority === 'Low') bg-green-100 text-green-800
                                            @else bg-gray-100 text-gray-800 @endif">
                                            {{ $ticket->priority ?? 'Medium' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="inline-flex px-2 py-1 portal-text-xs portal-font-medium rounded-full
                                            @if($ticket->status === 'Open') bg-red-100 text-red-800
                                            @elseif($ticket->status === 'In Progress') bg-yellow-100 text-yellow-800
                                            @elseif($ticket->status === 'Resolved') bg-green-100 text-green-800
                                            @elseif($ticket->status === 'Closed') bg-gray-100 text-gray-800
                                            @elseif($ticket->status === 'On Hold') bg-orange-100 text-orange-800
                                            @elseif($ticket->status === 'Waiting') bg-blue-100 text-blue-800
                                            @else bg-gray-100 text-gray-800 @endif">
                                            {{ $ticket->status ?? 'Open' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 portal-text-sm text-gray-500">
                                        {{ $ticket->created_at->format('M j, Y') }}
                                        <div class="portal-text-xs">
                                            {{ $ticket->created_at->format('g:i A') }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 portal-text-sm text-gray-500">
                                        {{ $ticket->updated_at->format('M j, Y') }}
                                        <div class="portal-text-xs">
                                            {{ $ticket->updated_at->format('g:i A') }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 portal-text-sm">
                                        <div class="portal-d-flex space-x-2">
                                            <a href="{{ route('client.tickets.show', $ticket) ?? '#' }}" 
                                               class="portal-btn portal-btn-sm portal-btn-outline-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination would go here if implemented -->
                    @if(method_exists($tickets, 'links'))
                        <div class="px-4 py-3 portal-border-t">
                            {{ $tickets->links() }}
                        </div>
                    @endif

                    @else
                    <div class="text-center py-8">
                        <div class="portal-mb-4">
                            <i class="fas fa-ticket-alt fa-3x text-gray-300"></i>
                        </div>
                        <h3 class="portal-text-lg portal-font-medium text-gray-900 portal-mb-2">
                            No Support Tickets
                        </h3>
                        <p class="portal-text-sm text-gray-500 portal-mb-4">
                            You haven't submitted any support tickets yet.
                        </p>
                        <a href="{{ route('client.tickets.create') ?? '#' }}" class="portal-btn portal-btn-primary">
                            <i class="fas fa-plus portal-mr-2"></i>Create Your First Ticket
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.space-x-2 > * + * { margin-left: 0.5rem; }

/* Badge colors for status indicators */
.bg-green-100 { background-color: #dcfce7; } .text-green-800 { color: #166534; }
.bg-yellow-100 { background-color: #fef3c7; } .text-yellow-800 { color: #92400e; }
.bg-red-100 { background-color: #fee2e2; } .text-red-800 { color: #991b1b; }
.bg-blue-100 { background-color: #dbeafe; } .text-blue-800 { color: #1e40af; }
.bg-orange-100 { background-color: #fed7aa; } .text-orange-800 { color: #9a3412; }
.bg-gray-100 { background-color: #f3f4f6; } .text-gray-800 { color: #1f2937; }

/* Hover effects */
.portal-table-row:hover {
    background-color: #f9fafb;
}
</style>
@endpush