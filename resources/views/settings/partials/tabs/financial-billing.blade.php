<div class="p-6">
    <div class="space-y-6">
        <!-- Billing Configuration -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Billing Configuration</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Default Payment Terms -->
                <div>
                    <label for="default_payment_terms" class="block text-sm font-medium text-gray-700 mb-1">
                        Default Payment Terms (Days)
                    </label>
                    <select id="default_payment_terms"
                            name="default_payment_terms"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="15" {{ old('default_payment_terms', $settings['default_payment_terms'] ?? 30) == '15' ? 'selected' : '' }}>15 Days</option>
                        <option value="30" {{ old('default_payment_terms', $settings['default_payment_terms'] ?? 30) == '30' ? 'selected' : '' }}>30 Days (Net 30)</option>
                        <option value="45" {{ old('default_payment_terms', $settings['default_payment_terms'] ?? 30) == '45' ? 'selected' : '' }}>45 Days</option>
                        <option value="60" {{ old('default_payment_terms', $settings['default_payment_terms'] ?? 30) == '60' ? 'selected' : '' }}>60 Days</option>
                        <option value="0" {{ old('default_payment_terms', $settings['default_payment_terms'] ?? 30) == '0' ? 'selected' : '' }}>Due on Receipt</option>
                    </select>
                </div>

                <!-- Late Fee Policy -->
                <div>
                    <label for="late_fee_percentage" class="block text-sm font-medium text-gray-700 mb-1">
                        Late Fee Percentage (%)
                    </label>
                    <input type="number" 
                           id="late_fee_percentage"
                           name="late_fee_percentage"
                           value="{{ old('late_fee_percentage', $settings['late_fee_percentage'] ?? 1.5) }}"
                           step="0.1" min="0" max="25"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                           placeholder="1.5">
                </div>

                <!-- Late Fee Grace Period -->
                <div>
                    <label for="late_fee_grace_period" class="block text-sm font-medium text-gray-700 mb-1">
                        Late Fee Grace Period (Days)
                    </label>
                    <select id="late_fee_grace_period"
                            name="late_fee_grace_period"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="0" {{ old('late_fee_grace_period', $settings['late_fee_grace_period'] ?? 5) == '0' ? 'selected' : '' }}>No Grace Period</option>
                        <option value="5" {{ old('late_fee_grace_period', $settings['late_fee_grace_period'] ?? 5) == '5' ? 'selected' : '' }}>5 Days</option>
                        <option value="10" {{ old('late_fee_grace_period', $settings['late_fee_grace_period'] ?? 5) == '10' ? 'selected' : '' }}>10 Days</option>
                        <option value="15" {{ old('late_fee_grace_period', $settings['late_fee_grace_period'] ?? 5) == '15' ? 'selected' : '' }}>15 Days</option>
                        <option value="30" {{ old('late_fee_grace_period', $settings['late_fee_grace_period'] ?? 5) == '30' ? 'selected' : '' }}>30 Days</option>
                    </select>
                </div>

                <!-- Billing Cycle -->
                <div>
                    <label for="default_billing_cycle" class="block text-sm font-medium text-gray-700 mb-1">
                        Default Billing Cycle
                    </label>
                    <select id="default_billing_cycle"
                            name="default_billing_cycle"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="monthly" {{ old('default_billing_cycle', $settings['default_billing_cycle'] ?? 'monthly') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="quarterly" {{ old('default_billing_cycle', $settings['default_billing_cycle'] ?? 'monthly') == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                        <option value="semi_annual" {{ old('default_billing_cycle', $settings['default_billing_cycle'] ?? 'monthly') == 'semi_annual' ? 'selected' : '' }}>Semi-Annual</option>
                        <option value="annual" {{ old('default_billing_cycle', $settings['default_billing_cycle'] ?? 'monthly') == 'annual' ? 'selected' : '' }}>Annual</option>
                        <option value="custom" {{ old('default_billing_cycle', $settings['default_billing_cycle'] ?? 'monthly') == 'custom' ? 'selected' : '' }}>Custom</option>
                    </select>
                </div>

                <!-- Invoice Numbering -->
                <div>
                    <label for="invoice_number_prefix" class="block text-sm font-medium text-gray-700 mb-1">
                        Invoice Number Prefix
                    </label>
                    <input type="text" 
                           id="invoice_number_prefix"
                           name="invoice_number_prefix"
                           value="{{ old('invoice_number_prefix', $settings['invoice_number_prefix'] ?? 'INV-') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                           placeholder="INV-">
                </div>

                <!-- Starting Invoice Number -->
                <div>
                    <label for="invoice_starting_number" class="block text-sm font-medium text-gray-700 mb-1">
                        Next Invoice Number
                    </label>
                    <input type="number" 
                           id="invoice_starting_number"
                           name="invoice_starting_number"
                           value="{{ old('invoice_starting_number', $settings['invoice_starting_number'] ?? 1001) }}"
                           min="1"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                           placeholder="1001">
                </div>
            </div>
        </div>

        <!-- Automatic Billing -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Automatic Billing</h3>
            <div class="space-y-6">
                <!-- Auto-billing Settings -->
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="auto_billing_enabled"
                               name="auto_billing_enabled"
                               value="1"
                               {{ old('auto_billing_enabled', $settings['auto_billing_enabled'] ?? true) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600-600 focus:ring-primary-500 border-gray-300 rounded">
                        <label for="auto_billing_enabled" class="ml-3 block text-sm font-medium text-gray-700">
                            Enable Automatic Billing for Recurring Services
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="auto_generate_invoices"
                               name="auto_generate_invoices"
                               value="1"
                               {{ old('auto_generate_invoices', $settings['auto_generate_invoices'] ?? true) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600-600 focus:ring-primary-500 border-gray-300 rounded">
                        <label for="auto_generate_invoices" class="ml-3 block text-sm font-medium text-gray-700">
                            Automatically Generate Monthly Invoices
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="auto_send_invoices"
                               name="auto_send_invoices"
                               value="1"
                               {{ old('auto_send_invoices', $settings['auto_send_invoices'] ?? false) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 dark:text-blue-400-600 focus:ring-primary-500 border-gray-300 rounded">
                        <label for="auto_send_invoices" class="ml-3 block text-sm font-medium text-gray-700">
                            Automatically Send Generated Invoices
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="auto_charge_cards"
                               name="auto_charge_cards"
                               value="1"
                               {{ old('auto_charge_cards', $settings['auto_charge_cards'] ?? false) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 dark:text-blue-400-600 focus:ring-primary-500 border-gray-300 rounded">
                        <label for="auto_charge_cards" class="ml-3 block text-sm font-medium text-gray-700">
                            Automatically Charge Saved Payment Methods
                        </label>
                    </div>
                </div>

                <!-- Billing Schedule -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="invoice_generation_day" class="block text-sm font-medium text-gray-700 mb-1">
                            Monthly Invoice Generation Day
                        </label>
                        <select id="invoice_generation_day"
                                name="invoice_generation_day"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            @for($day = 1; $day <= 28; $day++)
                                <option value="{{ $day }}" {{ old('invoice_generation_day', $settings['invoice_generation_day'] ?? 1) == $day ? 'selected' : '' }}>
                                    {{ ordinal($day) }} of the month
                                </option>
                            @endfor
                            <option value="last" {{ old('invoice_generation_day', $settings['invoice_generation_day'] ?? 1) == 'last' ? 'selected' : '' }}>Last day of month</option>
                        </select>
                    </div>

                    <div>
                        <label for="billing_advance_days" class="block text-sm font-medium text-gray-700 mb-1">
                            Bill Services in Advance (Days)
                        </label>
                        <select id="billing_advance_days"
                                name="billing_advance_days"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            <option value="0" {{ old('billing_advance_days', $settings['billing_advance_days'] ?? 0) == '0' ? 'selected' : '' }}>Bill on service date</option>
                            <option value="7" {{ old('billing_advance_days', $settings['billing_advance_days'] ?? 0) == '7' ? 'selected' : '' }}>7 days in advance</option>
                            <option value="14" {{ old('billing_advance_days', $settings['billing_advance_days'] ?? 0) == '14' ? 'selected' : '' }}>14 days in advance</option>
                            <option value="30" {{ old('billing_advance_days', $settings['billing_advance_days'] ?? 0) == '30' ? 'selected' : '' }}>30 days in advance</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Reminders -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Payment Reminders</h3>
            <div class="space-y-6">
                <!-- Reminder Settings -->
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="send_payment_reminders"
                               name="send_payment_reminders"
                               value="1"
                               {{ old('send_payment_reminders', $settings['send_payment_reminders'] ?? true) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 dark:text-blue-400-600 focus:ring-primary-500 border-gray-300 rounded">
                        <label for="send_payment_reminders" class="ml-3 block text-sm font-medium text-gray-700">
                            Send Automatic Payment Reminders
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="send_overdue_notices"
                               name="send_overdue_notices"
                               value="1"
                               {{ old('send_overdue_notices', $settings['send_overdue_notices'] ?? true) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 dark:text-blue-400-600 focus:ring-primary-500 border-gray-300 rounded">
                        <label for="send_overdue_notices" class="ml-3 block text-sm font-medium text-gray-700">
                            Send Overdue Payment Notices
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="suspend_overdue_services"
                               name="suspend_overdue_services"
                               value="1"
                               {{ old('suspend_overdue_services', $settings['suspend_overdue_services'] ?? false) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 dark:text-blue-400-600 focus:ring-primary-500 border-gray-300 rounded">
                        <label for="suspend_overdue_services" class="ml-3 block text-sm font-medium text-gray-700">
                            Suspend Services for Overdue Accounts
                        </label>
                    </div>
                </div>

                <!-- Reminder Schedule -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-md font-medium text-gray-900 mb-3">Reminder Schedule</h4>
                    <div class="space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                            <label class="text-sm text-gray-700">First Reminder:</label>
                            <select name="first_reminder_days" class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                <option value="3" {{ old('first_reminder_days', $settings['first_reminder_days'] ?? 7) == '3' ? 'selected' : '' }}>3 days before due</option>
                                <option value="7" {{ old('first_reminder_days', $settings['first_reminder_days'] ?? 7) == '7' ? 'selected' : '' }}>7 days before due</option>
                                <option value="14" {{ old('first_reminder_days', $settings['first_reminder_days'] ?? 7) == '14' ? 'selected' : '' }}>14 days before due</option>
                                <option value="0" {{ old('first_reminder_days', $settings['first_reminder_days'] ?? 7) == '0' ? 'selected' : '' }}>On due date</option>
                            </select>
                            <span class="text-sm text-gray-500">before due date</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                            <label class="text-sm text-gray-700">Second Reminder:</label>
                            <select name="second_reminder_days" class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                <option value="3" {{ old('second_reminder_days', $settings['second_reminder_days'] ?? 7) == '3' ? 'selected' : '' }}>3 days</option>
                                <option value="7" {{ old('second_reminder_days', $settings['second_reminder_days'] ?? 7) == '7' ? 'selected' : '' }}>7 days</option>
                                <option value="14" {{ old('second_reminder_days', $settings['second_reminder_days'] ?? 7) == '14' ? 'selected' : '' }}>14 days</option>
                                <option value="30" {{ old('second_reminder_days', $settings['second_reminder_days'] ?? 7) == '30' ? 'selected' : '' }}>30 days</option>
                            </select>
                            <span class="text-sm text-gray-500">after due date</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                            <label class="text-sm text-gray-700">Final Notice:</label>
                            <select name="final_notice_days" class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                <option value="14" {{ old('final_notice_days', $settings['final_notice_days'] ?? 30) == '14' ? 'selected' : '' }}>14 days</option>
                                <option value="30" {{ old('final_notice_days', $settings['final_notice_days'] ?? 30) == '30' ? 'selected' : '' }}>30 days</option>
                                <option value="45" {{ old('final_notice_days', $settings['final_notice_days'] ?? 30) == '45' ? 'selected' : '' }}>45 days</option>
                                <option value="60" {{ old('final_notice_days', $settings['final_notice_days'] ?? 30) == '60' ? 'selected' : '' }}>60 days</option>
                            </select>
                            <span class="text-sm text-gray-500">after due date</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Billing -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Service Billing</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Time Tracking -->
                <div>
                    <label for="time_rounding_increment" class="block text-sm font-medium text-gray-700 mb-1">
                        Time Rounding Increment (Minutes)
                    </label>
                    <select id="time_rounding_increment"
                            name="time_rounding_increment"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="1" {{ old('time_rounding_increment', $settings['time_rounding_increment'] ?? 15) == '1' ? 'selected' : '' }}>1 minute (exact)</option>
                        <option value="5" {{ old('time_rounding_increment', $settings['time_rounding_increment'] ?? 15) == '5' ? 'selected' : '' }}>5 minutes</option>
                        <option value="10" {{ old('time_rounding_increment', $settings['time_rounding_increment'] ?? 15) == '10' ? 'selected' : '' }}>10 minutes</option>
                        <option value="15" {{ old('time_rounding_increment', $settings['time_rounding_increment'] ?? 15) == '15' ? 'selected' : '' }}>15 minutes</option>
                        <option value="30" {{ old('time_rounding_increment', $settings['time_rounding_increment'] ?? 15) == '30' ? 'selected' : '' }}>30 minutes</option>
                        <option value="60" {{ old('time_rounding_increment', $settings['time_rounding_increment'] ?? 15) == '60' ? 'selected' : '' }}>1 hour</option>
                    </select>
                </div>

                <!-- Minimum Billing -->
                <div>
                    <label for="minimum_billing_increment" class="block text-sm font-medium text-gray-700 mb-1">
                        Minimum Billing Increment (Hours)
                    </label>
                    <select id="minimum_billing_increment"
                            name="minimum_billing_increment"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="0" {{ old('minimum_billing_increment', $settings['minimum_billing_increment'] ?? 1) == '0' ? 'selected' : '' }}>No minimum</option>
                        <option value="0.25" {{ old('minimum_billing_increment', $settings['minimum_billing_increment'] ?? 1) == '0.25' ? 'selected' : '' }}>15 minutes</option>
                        <option value="0.5" {{ old('minimum_billing_increment', $settings['minimum_billing_increment'] ?? 1) == '0.5' ? 'selected' : '' }}>30 minutes</option>
                        <option value="1" {{ old('minimum_billing_increment', $settings['minimum_billing_increment'] ?? 1) == '1' ? 'selected' : '' }}>1 hour</option>
                        <option value="2" {{ old('minimum_billing_increment', $settings['minimum_billing_increment'] ?? 1) == '2' ? 'selected' : '' }}>2 hours</option>
                    </select>
                </div>

                <!-- Default Hourly Rate -->
                <div>
                    <label for="default_hourly_rate" class="block text-sm font-medium text-gray-700 mb-1">
                        Default Hourly Rate ($)
                    </label>
                    <input type="number" 
                           id="default_hourly_rate"
                           name="default_hourly_rate"
                           value="{{ old('default_hourly_rate', $settings['default_hourly_rate'] ?? 150) }}"
                           step="0.01" min="0"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                           placeholder="150.00">
                </div>

                <!-- Emergency Rate Multiplier -->
                <div>
                    <label for="emergency_rate_multiplier" class="block text-sm font-medium text-gray-700 mb-1">
                        Emergency Rate Multiplier
                    </label>
                    <select id="emergency_rate_multiplier"
                            name="emergency_rate_multiplier"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="1" {{ old('emergency_rate_multiplier', $settings['emergency_rate_multiplier'] ?? 1.5) == '1' ? 'selected' : '' }}>1x (No increase)</option>
                        <option value="1.25" {{ old('emergency_rate_multiplier', $settings['emergency_rate_multiplier'] ?? 1.5) == '1.25' ? 'selected' : '' }}>1.25x (+25%)</option>
                        <option value="1.5" {{ old('emergency_rate_multiplier', $settings['emergency_rate_multiplier'] ?? 1.5) == '1.5' ? 'selected' : '' }}>1.5x (+50%)</option>
                        <option value="2" {{ old('emergency_rate_multiplier', $settings['emergency_rate_multiplier'] ?? 1.5) == '2' ? 'selected' : '' }}>2x (+100%)</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Form Actions -->
    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
        <button type="submit" 
                class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Save Billing Settings
        </button>
    </div>
</div>
