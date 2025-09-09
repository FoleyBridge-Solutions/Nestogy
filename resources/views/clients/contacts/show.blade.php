@extends('layouts.app')

@section('title', $contact->name . ' - Contact Details')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-8 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-12 w-12">
                            <div class="h-12 w-12 rounded-full bg-indigo-100 flex items-center justify-center">
                                <span class="text-lg font-medium text-indigo-800">
                                    {{ strtoupper(substr($contact->name, 0, 2)) }}
                                </span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">{{ $contact->name }}</h3>
                            <p class="text-sm text-gray-500">
                                {{ $contact->title ? $contact->title . ' at ' : '' }}{{ $contact->client->display_name }}
                            </p>
                        </div>
                    </div>
                    <div class="flex space-x-3">
                        <a href="{{ route('clients.contacts.index', $client) }}" 
                           class="inline-flex items-center px-6 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Back to Contacts
                        </a>
                        <a href="{{ route('clients.contacts.edit', [$client, $contact]) }}" 
                           class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit Contact
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Main Contact Information -->
            <div class="lg:flex-1 px-6-span-2">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Contact Information</h3>
                    </div>
                    <div class="px-6 py-8 sm:p-6">
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $contact->name }}</dd>
                            </div>

                            @if($contact->title)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Job Title</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $contact->title }}</dd>
                            </div>
                            @endif

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Client</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <a href="{{ route('clients.show', $contact->client) }}" 
                                       class="text-indigo-600 hover:text-indigo-500">
                                        {{ $contact->client->display_name }}
                                    </a>
                                </dd>
                            </div>

                            @if($contact->department)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Department</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $contact->department }}</dd>
                            </div>
                            @endif

                            @if($contact->email)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Email Address</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <a href="mailto:{{ $contact->email }}" 
                                       class="text-indigo-600 hover:text-indigo-500">
                                        {{ $contact->email }}
                                    </a>
                                </dd>
                            </div>
                            @endif

                            @if($contact->phone)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Phone Number</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <a href="tel:{{ $contact->phone }}" 
                                       class="text-indigo-600 hover:text-indigo-500">
                                        {{ $contact->display_phone }}
                                    </a>
                                </dd>
                            </div>
                            @endif

                            @if($contact->mobile)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Mobile Number</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <a href="tel:{{ $contact->mobile }}" 
                                       class="text-indigo-600 hover:text-indigo-500">
                                        {{ $contact->mobile }}
                                    </a>
                                </dd>
                            </div>
                            @endif

                            @if($contact->notes)
                            <div class="sm:flex-1 px-6-span-2">
                                <dt class="text-sm font-medium text-gray-500">Notes</dt>
                                <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ $contact->notes }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:flex-1 px-6-span-1">
                <!-- Contact Types -->
                <div class="bg-white shadow rounded-lg mb-6">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Contact Types</h3>
                    </div>
                    <div class="px-6 py-8 sm:p-6">
                        @if(count($contact->type_labels) > 0)
                            <div class="flex flex-wrap gap-2">
                                @foreach($contact->type_labels as $type)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        @if($type === 'Primary') bg-blue-100 text-blue-800
                                        @elseif($type === 'Billing') bg-green-100 text-green-800
                                        @elseif($type === 'Technical') bg-purple-100 text-purple-800
                                        @elseif($type === 'Important') bg-red-100 text-red-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ $type }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500">No specific contact types assigned.</p>
                        @endif
                    </div>
                </div>

                <!-- Addresses -->
                @if($contact->addresses->count() > 0)
                <div class="bg-white shadow rounded-lg mb-6">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Addresses</h3>
                    </div>
                    <div class="px-6 py-8 sm:p-6">
                        <div class="space-y-4">
                            @foreach($contact->addresses as $address)
                                <div class="border-l-4 border-indigo-400 pl-4">
                                    <h4 class="text-sm font-medium text-gray-900">{{ $address->display_name }}</h4>
                                    <div class="mt-1 text-sm text-gray-600 whitespace-pre-line">{{ $address->formatted_address }}</div>
                                    @if($address->phone)
                                        <div class="mt-1 text-sm text-gray-600">Phone: {{ $address->phone }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Quick Actions -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-8 sm:px-6 border-b border-gray-200">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Quick Actions</h3>
                    </div>
                    <div class="px-6 py-8 sm:p-6">
                        <div class="space-y-3">
                            @if($contact->email)
                                <a href="mailto:{{ $contact->email }}" 
                                   class="w-full inline-flex justify-center items-center px-6 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    Send Email
                                </a>
                            @endif

                            @if($contact->phone)
                                <a href="tel:{{ $contact->phone }}" 
                                   class="w-full inline-flex justify-center items-center px-6 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    Call Phone
                                </a>
                            @endif

                            <a href="{{ route('clients.show', $contact->client) }}" 
                               class="w-full inline-flex justify-center items-center px-6 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                View Client
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
