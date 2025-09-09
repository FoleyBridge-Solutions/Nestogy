@extends('layouts.app')

@section('title', 'Create Trip')

@section('content')
<div class="w-full px-6">
    <div class="flex flex-wrap -mx-4">
        <div class="flex-1 px-6-12">
            <div class="flex justify-between items-center mb-6">
                <h1 class="h3 mb-0">Create Trip</h1>
                <a href="{{ route('clients.trips.standalone.index') }}" class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Trips
                </a>
            </div>

            <div class="flex flex-wrap -mx-4">
                <div class="lg:w-2/3 px-4 flex-1 px-6-xl-6">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-6">
                            <form method="POST" action="{{ route('clients.trips.standalone.store') }}">
                                @csrf

                                <!-- Client Selection -->
                                <div class="mb-6">
                                    <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">Client <span class="text-red-600">*</span></label>
                                    <select name="client_id" 
                                            id="client_id" 
                                            class="block w-full px-6 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('client_id') border-red-500 @enderror" 
                                            required>
                                        <option value="">Select a client...</option>
                                        @foreach($clients as $client)
                                            <option value="{{ $client->id }}" 
                                                    {{ old('client_id', $selectedClientId) == $client->id ? 'selected' : '' }}>
                                                {{ $client->display_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('client_id')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Trip Title -->
                                <div class="mb-6">
                                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Trip Title <span class="text-red-600">*</span></label>
                                    <input type="text" 
                                           name="title" 
                                           id="title" 
                                           class="block w-full px-6 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('title') border-red-500 @enderror" 
                                           value="{{ old('title') }}" 
                                           required 
                                           maxlength="255"
                                           placeholder="Enter trip title">
                                    @error('title')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Description and Purpose -->
                                <div class="flex flex-wrap -mx-4">
                                    <div class="md:w-1/2 px-6">
                                        <div class="mb-6">
                                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                            <textarea name="description" 
                                                      id="description" 
                                                      class="block w-full px-6 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('description') border-red-500 @enderror" 
                                                      rows="3" 
                                                      placeholder="Trip description...">{{ old('description') }}</textarea>
                                            @error('description')
                                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="md:w-1/2 px-6">
                                        <div class="mb-6">
                                            <label for="purpose" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Purpose</label>
                                            <input type="text" 
                                                   name="purpose" 
                                                   id="purpose" 
                                                   class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('purpose') border-red-500 @enderror" 
                                                   value="{{ old('purpose') }}" 
                                                   maxlength="255"
                                                   placeholder="Meeting, support, installation...">
                                            @error('purpose')
                                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Trip Type and Transportation -->
                                <div class="flex flex-wrap -mx-4">
                                    <div class="flex-1 px-6-md-6">
                                        <div class="mb-6">
                                            <label for="trip_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Trip Type <span class="text-red-600 dark:text-red-400">*</span></label>
                                            <select name="trip_type" 
                                                    id="trip_type" 
                                                    class="block w-full px-6 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('trip_type') border-red-500 @enderror" 
                                                    required>
                                                <option value="">Select trip type...</option>
                                                @foreach($tripTypes as $key => $value)
                                                    <option value="{{ $key }}" {{ old('trip_type') == $key ? 'selected' : '' }}>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('trip_type')
                                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="flex-1 px-6-md-6">
                                        <div class="mb-6">
                                            <label for="transportation_mode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Transportation <span class="text-red-600 dark:text-red-400">*</span></label>
                                            <select name="transportation_mode" 
                                                    id="transportation_mode" 
                                                    class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('transportation_mode') border-red-500 @enderror" 
                                                    required>
                                                <option value="">Select transportation...</option>
                                                @foreach($transportationModes as $key => $value)
                                                    <option value="{{ $key }}" {{ old('transportation_mode') == $key ? 'selected' : '' }}>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('transportation_mode')
                                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Destination -->
                                <div class="mb-6">
                                    <label for="destination_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Destination Address</label>
                                    <input type="text" 
                                           name="destination_address" 
                                           id="destination_address" 
                                           class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('destination_address') border-red-500 @enderror" 
                                           value="{{ old('destination_address') }}" 
                                           maxlength="255"
                                           placeholder="Street address">
                                    @error('destination_address')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="flex flex-wrap -mx-4">
                                    <div class="flex-1 px-6-md-4">
                                        <div class="mb-6">
                                            <label for="destination_city" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">City <span class="text-red-600 dark:text-red-400">*</span></label>
                                            <input type="text" 
                                                   name="destination_city" 
                                                   id="destination_city" 
                                                   class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('destination_city') border-red-500 @enderror" 
                                                   value="{{ old('destination_city') }}" 
                                                   required 
                                                   maxlength="100"
                                                   placeholder="City">
                                            @error('destination_city')
                                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="flex-1 px-6-md-4">
                                        <div class="mb-6">
                                            <label for="destination_state" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">State/Province</label>
                                            <input type="text" 
                                                   name="destination_state" 
                                                   id="destination_state" 
                                                   class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('destination_state') border-red-500 @enderror" 
                                                   value="{{ old('destination_state') }}" 
                                                   maxlength="100"
                                                   placeholder="State/Province">
                                            @error('destination_state')
                                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="flex-1 px-6-md-4">
                                        <div class="mb-6">
                                            <label for="destination_country" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Country</label>
                                            <input type="text" 
                                                   name="destination_country" 
                                                   id="destination_country" 
                                                   class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('destination_country') border-red-500 @enderror" 
                                                   value="{{ old('destination_country', 'United States') }}" 
                                                   maxlength="100"
                                                   placeholder="Country">
                                            @error('destination_country')
                                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Dates and Times -->
                                <div class="flex flex-wrap -mx-4">
                                    <div class="flex-1 px-6-md-6">
                                        <div class="mb-6">
                                            <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date <span class="text-red-600 dark:text-red-400">*</span></label>
                                            <input type="date" 
                                                   name="start_date" 
                                                   id="start_date" 
                                                   class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('start_date') border-red-500 @enderror" 
                                                   value="{{ old('start_date') }}" 
                                                   required>
                                            @error('start_date')
                                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="flex-1 px-6-md-6">
                                        <div class="mb-6">
                                            <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date <span class="text-red-600 dark:text-red-400">*</span></label>
                                            <input type="date" 
                                                   name="end_date" 
                                                   id="end_date" 
                                                   class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('end_date') border-red-500 @enderror" 
                                                   value="{{ old('end_date') }}" 
                                                   required>
                                            @error('end_date')
                                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap -mx-4">
                                    <div class="flex-1 px-6-md-6">
                                        <div class="mb-6">
                                            <label for="departure_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Departure Time</label>
                                            <input type="datetime-local" 
                                                   name="departure_time" 
                                                   id="departure_time" 
                                                   class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('departure_time') border-red-500 @enderror" 
                                                   value="{{ old('departure_time') }}">
                                            @error('departure_time')
                                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="flex-1 px-6-md-6">
                                        <div class="mb-6">
                                            <label for="return_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Return Time</label>
                                            <input type="datetime-local" 
                                                   name="return_time" 
                                                   id="return_time" 
                                                   class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('return_time') border-red-500 @enderror" 
                                                   value="{{ old('return_time') }}">
                                            @error('return_time')
                                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="mb-6">
                                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status <span class="text-red-600 dark:text-red-400">*</span></label>
                                    <select name="status" 
                                            id="status" 
                                            class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('status') border-red-500 @enderror" 
                                            required>
                                        @foreach($statuses as $key => $value)
                                            <option value="{{ $key }}" {{ old('status', 'planned') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Expenses -->
                                <div class="flex flex-wrap -mx-4">
                                    <div class="flex-1 px-6-md-4">
                                        <div class="mb-6">
                                            <label for="estimated_expenses" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Estimated Expenses</label>
                                            <input type="number" 
                                                   name="estimated_expenses" 
                                                   id="estimated_expenses" 
                                                   class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('estimated_expenses') border-red-500 @enderror" 
                                                   value="{{ old('estimated_expenses') }}" 
                                                   min="0" 
                                                   step="0.01"
                                                   placeholder="0.00">
                                            @error('estimated_expenses')
                                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="flex-1 px-6-md-4">
                                        <div class="mb-6">
                                            <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Currency <span class="text-red-600 dark:text-red-400">*</span></label>
                                            <select name="currency" 
                                                    id="currency" 
                                                    class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('currency') border-red-500 @enderror" 
                                                    required>
                                                @foreach($currencies as $key => $value)
                                                    <option value="{{ $key }}" {{ old('currency', 'USD') == $key ? 'selected' : '' }}>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('currency')
                                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="flex-1 px-6-md-4">
                                        <div class="mb-6">
                                            <label for="mileage" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mileage</label>
                                            <input type="number" 
                                                   name="mileage" 
                                                   id="mileage" 
                                                   class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('mileage') border-red-500 @enderror" 
                                                   value="{{ old('mileage') }}" 
                                                   min="0" 
                                                   step="0.1"
                                                   placeholder="0.0">
                                            @error('mileage')
                                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Per Diem -->
                                <div class="mb-6">
                                    <label for="per_diem_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Per Diem Amount</label>
                                    <input type="number" 
                                           name="per_diem_amount" 
                                           id="per_diem_amount" 
                                           class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('per_diem_amount') border-red-500 @enderror" 
                                           value="{{ old('per_diem_amount') }}" 
                                           min="0" 
                                           step="0.01"
                                           placeholder="0.00">
                                    <div class="form-text">Daily allowance for meals and incidental expenses</div>
                                    @error('per_diem_amount')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Expense Breakdown -->
                                <div class="mb-6">
                                    <label for="expense_breakdown" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Expense Breakdown</label>
                                    <textarea name="expense_breakdown" 
                                              id="expense_breakdown" 
                                              class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('expense_breakdown') border-red-500 @enderror" 
                                              rows="4" 
                                              placeholder="Enter expense details...">{{ old('expense_breakdown') }}</textarea>
                                    <div class="form-text">
                                        Format: Category | Amount | Description<br>
                                        Example: Transportation | 150.00 | Flight tickets
                                    </div>
                                    @error('expense_breakdown')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Settings -->
                                <div class="flex flex-wrap -mx-4">
                                    <div class="flex-1 px-6-md-4">
                                        <div class="mb-6">
                                            <div class="flex items-center">
                                                <input class="flex items-center-input" 
                                                       type="checkbox" 
                                                       name="billable_to_client" 
                                                       id="billable_to_client" 
                                                       value="1" 
                                                       {{ old('billable_to_client') ? 'checked' : '' }}>
                                                <label class="flex items-center-label" for="billable_to_client">
                                                    Billable to Client
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1 px-6-md-4">
                                        <div class="mb-6">
                                            <div class="flex items-center">
                                                <input class="flex items-center-input" 
                                                       type="checkbox" 
                                                       name="reimbursable" 
                                                       id="reimbursable" 
                                                       value="1" 
                                                       {{ old('reimbursable') ? 'checked' : '' }}>
                                                <label class="flex items-center-label" for="reimbursable">
                                                    Reimbursable
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1 px-6-md-4">
                                        <div class="mb-6">
                                            <div class="flex items-center">
                                                <input class="flex items-center-input" 
                                                       type="checkbox" 
                                                       name="approval_required" 
                                                       id="approval_required" 
                                                       value="1" 
                                                       {{ old('approval_required') ? 'checked' : '' }}>
                                                <label class="flex items-center-label" for="approval_required">
                                                    Requires Approval
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Accommodation -->
                                <div class="mb-6">
                                    <label for="accommodation_details" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Accommodation Details</label>
                                    <textarea name="accommodation_details" 
                                              id="accommodation_details" 
                                              class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('accommodation_details') border-red-500 @enderror" 
                                              rows="3" 
                                              placeholder="Hotel bookings, special requirements...">{{ old('accommodation_details') }}</textarea>
                                    @error('accommodation_details')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Attendees -->
                                <div class="mb-6">
                                    <label for="attendees" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Attendees</label>
                                    <input type="text" 
                                           name="attendees" 
                                           id="attendees" 
                                           class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('attendees') border-red-500 @enderror" 
                                           value="{{ old('attendees') }}" 
                                           placeholder="John Doe, Jane Smith, client contacts">
                                    <div class="form-text">Separate multiple names with commas</div>
                                    @error('attendees')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Notes -->
                                <div class="mb-6">
                                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                                    <textarea name="notes" 
                                              id="notes" 
                                              class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('notes') border-red-500 @enderror" 
                                              rows="3" 
                                              placeholder="Additional notes, special instructions...">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="flex gap-2">
                                    <button type="submit" class="inline-flex items-center px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <i class="fas fa-save mr-2"></i>Create Trip
                                    </button>
                                    <a href="{{ route('clients.trips.standalone.index') }}" class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-secondary">
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="lg:w-1/3 px-4 flex-1 px-6-xl-6">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="px-6 py-6 border-b border-gray-200 bg-gray-50">
                            <h5 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-0">
                                <i class="fas fa-info-circle mr-2"></i>Trip Planning Guide
                            </h5>
                        </div>
                        <div class="p-6">
                            <div class="small text-gray-600">
                                <h6>Trip Types:</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Client Visit:</strong> General client meetings and visits</li>
                                    <li><strong>Site Inspection:</strong> On-site assessments and evaluations</li>
                                    <li><strong>Installation:</strong> Equipment or software installation</li>
                                    <li><strong>Training:</strong> Client training sessions</li>
                                    <li><strong>Support:</strong> Technical support and troubleshooting</li>
                                    <li><strong>Maintenance:</strong> Routine maintenance activities</li>
                                </ul>

                                <h6 class="mt-6">Expense Planning:</h6>
                                <ul class="list-unstyled">
                                    <li>• Include transportation, lodging, meals</li>
                                    <li>• Consider parking, tolls, and incidentals</li>
                                    <li>• Set realistic per diem amounts</li>
                                    <li>• Track mileage for reimbursement</li>
                                </ul>

                                <h6 class="mt-6">Best Practices:</h6>
                                <ul class="list-unstyled">
                                    <li>• Plan trips well in advance</li>
                                    <li>• Coordinate with client schedules</li>
                                    <li>• Include buffer time for delays</li>
                                    <li>• Prepare all necessary materials</li>
                                    <li>• Confirm accommodation reservations</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-fill end date when start date changes
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    startDateInput.addEventListener('change', function() {
        if (this.value && !endDateInput.value) {
            endDateInput.value = this.value;
        }
    });
    
    // Update departure/return times based on dates
    const departureTimeInput = document.getElementById('departure_time');
    const returnTimeInput = document.getElementById('return_time');
    
    startDateInput.addEventListener('change', function() {
        if (this.value && !departureTimeInput.value) {
            departureTimeInput.value = this.value + 'T08:00';
        }
    });
    
    endDateInput.addEventListener('change', function() {
        if (this.value && !returnTimeInput.value) {
            returnTimeInput.value = this.value + 'T17:00';
        }
    });
});
</script>
@endpush
