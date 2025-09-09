<div class="p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Timezone -->
        <div>
            <label for="timezone" class="block text-sm font-medium text-gray-700 mb-1">
                Timezone
            </label>
            <select id="timezone"
                    name="timezone"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                @foreach($timezones as $value => $label)
                    <option value="{{ $value }}" {{ old('timezone', $settings['timezone'] ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <!-- Date Format -->
        <div>
            <label for="date_format" class="block text-sm font-medium text-gray-700 mb-1">
                Date Format
            </label>
            <select id="date_format"
                    name="date_format"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                @foreach($dateFormats as $value => $label)
                    <option value="{{ $value }}" {{ old('date_format', $settings['date_format'] ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <!-- Time Format -->
        <div>
            <label for="time_format" class="block text-sm font-medium text-gray-700 mb-1">
                Time Format
            </label>
            <select id="time_format"
                    name="time_format"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                <option value="12" {{ old('time_format', $settings['time_format'] ?? '12') == '12' ? 'selected' : '' }}>12 Hour (1:30 PM)</option>
                <option value="24" {{ old('time_format', $settings['time_format'] ?? '12') == '24' ? 'selected' : '' }}>24 Hour (13:30)</option>
            </select>
        </div>

        <!-- Currency -->
        <div>
            <label for="currency" class="block text-sm font-medium text-gray-700 mb-1">
                Currency
            </label>
            <select id="currency"
                    name="currency"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                @foreach($currencies as $code => $name)
                    <option value="{{ $code }}" {{ old('currency', $settings['currency'] ?? 'USD') == $code ? 'selected' : '' }}>{{ $code }} - {{ $name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Language -->
        <div>
            <label for="language" class="block text-sm font-medium text-gray-700 mb-1">
                Language
            </label>
            <select id="language"
                    name="language"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                <option value="en" {{ old('language', $settings['language'] ?? 'en') == 'en' ? 'selected' : '' }}>English</option>
                <option value="es" {{ old('language', $settings['language'] ?? 'en') == 'es' ? 'selected' : '' }}>Spanish</option>
                <option value="fr" {{ old('language', $settings['language'] ?? 'en') == 'fr' ? 'selected' : '' }}>French</option>
                <option value="de" {{ old('language', $settings['language'] ?? 'en') == 'de' ? 'selected' : '' }}>German</option>
            </select>
        </div>

        <!-- Fiscal Year Start -->
        <div>
            <label for="fiscal_year_start" class="block text-sm font-medium text-gray-700 mb-1">
                Fiscal Year Start
            </label>
            <select id="fiscal_year_start"
                    name="fiscal_year_start"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                <option value="1" {{ old('fiscal_year_start', $settings['fiscal_year_start'] ?? '1') == '1' ? 'selected' : '' }}>January</option>
                <option value="2" {{ old('fiscal_year_start', $settings['fiscal_year_start'] ?? '1') == '2' ? 'selected' : '' }}>February</option>
                <option value="3" {{ old('fiscal_year_start', $settings['fiscal_year_start'] ?? '1') == '3' ? 'selected' : '' }}>March</option>
                <option value="4" {{ old('fiscal_year_start', $settings['fiscal_year_start'] ?? '1') == '4' ? 'selected' : '' }}>April</option>
                <option value="5" {{ old('fiscal_year_start', $settings['fiscal_year_start'] ?? '1') == '5' ? 'selected' : '' }}>May</option>
                <option value="6" {{ old('fiscal_year_start', $settings['fiscal_year_start'] ?? '1') == '6' ? 'selected' : '' }}>June</option>
                <option value="7" {{ old('fiscal_year_start', $settings['fiscal_year_start'] ?? '1') == '7' ? 'selected' : '' }}>July</option>
                <option value="8" {{ old('fiscal_year_start', $settings['fiscal_year_start'] ?? '1') == '8' ? 'selected' : '' }}>August</option>
                <option value="9" {{ old('fiscal_year_start', $settings['fiscal_year_start'] ?? '1') == '9' ? 'selected' : '' }}>September</option>
                <option value="10" {{ old('fiscal_year_start', $settings['fiscal_year_start'] ?? '1') == '10' ? 'selected' : '' }}>October</option>
                <option value="11" {{ old('fiscal_year_start', $settings['fiscal_year_start'] ?? '1') == '11' ? 'selected' : '' }}>November</option>
                <option value="12" {{ old('fiscal_year_start', $settings['fiscal_year_start'] ?? '1') == '12' ? 'selected' : '' }}>December</option>
            </select>
        </div>
    </div>
    
    <!-- Form Actions -->
    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
        <button type="submit" 
                class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Save Localization Settings
        </button>
    </div>
</div>
