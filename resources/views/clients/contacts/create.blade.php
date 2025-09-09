@extends('layouts.app')

@section('title', 'Add Contact')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-3xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-8 sm:px-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Add New Contact</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">Create a new client contact.</p>
                    </div>
                    <a href="{{ route('clients.contacts.index', $client) }}" 
                       class="inline-flex items-center px-6 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Contacts
                    </a>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white shadow rounded-lg">
            <form method="POST" action="{{ route('clients.contacts.store', $client) }}" class="space-y-6">
                @csrf
                
                <div class="px-6 py-8 sm:p-6">
                    <!-- Client Information -->
                    <div class="grid grid-cols-1 gap-6">
                        <div class="bg-gray-50 px-6 py-6 rounded-lg">
                            <p class="text-sm font-medium text-gray-700">Client</p>
                            <p class="mt-1 text-sm text-gray-900">{{ $client->name }}</p>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="mt-6">
                        <h4 class="text-md font-medium text-gray-900 mb-6">Contact Information</h4>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Full Name *</label>
                                <input type="text" 
                                       name="name" 
                                       id="name" 
                                       value="{{ old('name') }}"
                                       required
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('name') border-red-300 @enderror">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700">Job Title</label>
                                <input type="text" 
                                       name="title" 
                                       id="title" 
                                       value="{{ old('title') }}"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('title') border-red-300 @enderror">
                                @error('title')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                <input type="email" 
                                       name="email" 
                                       id="email" 
                                       value="{{ old('email') }}"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('email') border-red-300 @enderror">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="department" class="block text-sm font-medium text-gray-700">Department</label>
                                <input type="text" 
                                       name="department" 
                                       id="department" 
                                       value="{{ old('department') }}"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('department') border-red-300 @enderror">
                                @error('department')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Phone Numbers -->
                    <div class="mt-6">
                        <h4 class="text-md font-medium text-gray-900 mb-6">Phone Numbers</h4>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <input type="text" 
                                       name="phone" 
                                       id="phone" 
                                       value="{{ old('phone') }}"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('phone') border-red-300 @enderror">
                                @error('phone')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="extension" class="block text-sm font-medium text-gray-700">Extension</label>
                                <input type="text" 
                                       name="extension" 
                                       id="extension" 
                                       value="{{ old('extension') }}"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('extension') border-red-300 @enderror">
                                @error('extension')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="mobile" class="block text-sm font-medium text-gray-700">Mobile Number</label>
                                <input type="text" 
                                       name="mobile" 
                                       id="mobile" 
                                       value="{{ old('mobile') }}"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('mobile') border-red-300 @enderror">
                                @error('mobile')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Contact Types -->
                    <div class="mt-6">
                        <h4 class="text-md font-medium text-gray-900 mb-6">Contact Types</h4>
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="primary" 
                                           name="primary" 
                                           type="checkbox" 
                                           value="1"
                                           {{ old('primary') ? 'checked' : '' }}
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="primary" class="font-medium text-gray-700">Primary Contact</label>
                                    <p class="text-gray-500">Main point of contact</p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="billing" 
                                           name="billing" 
                                           type="checkbox" 
                                           value="1"
                                           {{ old('billing') ? 'checked' : '' }}
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="billing" class="font-medium text-gray-700">Billing Contact</label>
                                    <p class="text-gray-500">Handles billing matters</p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="technical" 
                                           name="technical" 
                                           type="checkbox" 
                                           value="1"
                                           {{ old('technical') ? 'checked' : '' }}
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="technical" class="font-medium text-gray-700">Technical Contact</label>
                                    <p class="text-gray-500">Technical inquiries</p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="important" 
                                           name="important" 
                                           type="checkbox" 
                                           value="1"
                                           {{ old('important') ? 'checked' : '' }}
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="important" class="font-medium text-gray-700">Important Contact</label>
                                    <p class="text-gray-500">High priority contact</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mt-6">
                        <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea name="notes" 
                                  id="notes" 
                                  rows="4"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('notes') border-red-300 @enderror"
                                  placeholder="Additional notes about this contact...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="px-6 py-6 bg-gray-50 text-right sm:px-6 space-x-3">
                    <a href="{{ route('clients.contacts.index', $client) }}" 
                       class="inline-flex justify-center py-2 px-6 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Create Contact
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
