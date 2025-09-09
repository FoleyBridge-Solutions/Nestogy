@extends('layouts.app')

@section('title', 'Create Lead')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Create New Lead</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">Add a new lead to your sales pipeline.</p>
                    </div>
                    <a href="{{ route('leads.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Leads
                    </a>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <form method="POST" action="{{ route('leads.store') }}" class="space-y-6">
                @csrf
                
                <div class="px-4 py-5 sm:px-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Personal Information -->
                        <div class="col-span-12-span-2">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Personal Information</h4>
                        </div>

                        <!-- First Name -->
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">First Name *</label>
                            <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" required
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('first_name') border-red-300 @enderror">
                            @error('first_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Last Name -->
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Last Name *</label>
                            <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('last_name') border-red-300 @enderror">
                            @error('last_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email *</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('email') border-red-300 @enderror">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('phone') border-red-300 @enderror">
                            @error('phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Company Information -->
                        <div class="col-span-12-span-2 mt-8">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Company Information</h4>
                        </div>

                        <!-- Company Name -->
                        <div>
                            <label for="company_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Company Name</label>
                            <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}"
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('company_name') border-red-300 @enderror">
                            @error('company_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Job Title -->
                        <div>
                            <label for="job_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Job Title</label>
                            <input type="text" name="job_title" id="job_title" value="{{ old('job_title') }}"
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('job_title') border-red-300 @enderror">
                            @error('job_title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Lead Details -->
                        <div class="col-span-12-span-2 mt-8">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Lead Details</h4>
                        </div>

                        <!-- Lead Source -->
                        <div>
                            <label for="lead_source_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lead Source *</label>
                            <select name="lead_source_id" id="lead_source_id" required
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('lead_source_id') border-red-300 @enderror">
                                <option value="">Select a source</option>
                                @foreach(\App\Domains\Lead\Models\LeadSource::where('company_id', auth()->user()->company_id)->active()->get() as $source)
                                    <option value="{{ $source->id }}" {{ old('lead_source_id') == $source->id ? 'selected' : '' }}>
                                        {{ $source->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('lead_source_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                            <select name="status" id="status"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('status') border-red-300 @enderror">
                                <option value="new" {{ old('status') === 'new' ? 'selected' : '' }}>New</option>
                                <option value="contacted" {{ old('status') === 'contacted' ? 'selected' : '' }}>Contacted</option>
                                <option value="qualified" {{ old('status') === 'qualified' ? 'selected' : '' }}>Qualified</option>
                                <option value="proposal" {{ old('status') === 'proposal' ? 'selected' : '' }}>Proposal</option>
                                <option value="negotiation" {{ old('status') === 'negotiation' ? 'selected' : '' }}>Negotiation</option>
                            </select>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Assigned User -->
                        <div>
                            <label for="assigned_user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Assign To</label>
                            <select name="assigned_user_id" id="assigned_user_id"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('assigned_user_id') border-red-300 @enderror">
                                <option value="">Unassigned</option>
                                @foreach(\App\Models\User::where('company_id', auth()->user()->company_id)->get() as $user)
                                    <option value="{{ $user->id }}" {{ old('assigned_user_id', auth()->id()) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('assigned_user_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Interest Level -->
                        <div>
                            <label for="interest_level" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Interest Level</label>
                            <select name="interest_level" id="interest_level"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('interest_level') border-red-300 @enderror">
                                <option value="low" {{ old('interest_level') === 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ old('interest_level', 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ old('interest_level') === 'high' ? 'selected' : '' }}>High</option>
                                <option value="urgent" {{ old('interest_level') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                            </select>
                            @error('interest_level')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Notes -->
                        <div class="col-span-12-span-2">
                            <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                            <textarea name="notes" id="notes" rows="4" placeholder="Any additional information about this lead..."
                                      class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('notes') border-red-300 @enderror">{{ old('notes') }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 text-right sm:px-6 rounded-b-lg">
                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('leads.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Cancel
                        </a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Create Lead
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
