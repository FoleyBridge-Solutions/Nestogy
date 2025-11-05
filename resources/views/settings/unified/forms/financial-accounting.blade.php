<div class="space-y-8">
    <flux:card>
        <flux:heading size="lg">General Ledger Settings</flux:heading>
        <flux:text variant="muted" class="mb-6">Configure chart of accounts and accounting rules</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Enable Chart of Accounts</flux:label>
                <flux:switch name="chart_of_accounts_enabled" :checked="$settings['chart_of_accounts_enabled'] ?? true" />
                <flux:text size="sm" variant="muted">Track income and expenses using a chart of accounts</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Accounting Method</flux:label>
                <flux:select name="accounting_method">
                    <option value="accrual" {{ ($settings['accounting_method'] ?? 'accrual') === 'accrual' ? 'selected' : '' }}>Accrual</option>
                    <option value="cash" {{ ($settings['accounting_method'] ?? '') === 'cash' ? 'selected' : '' }}>Cash</option>
                </flux:select>
                <flux:text size="sm" variant="muted">
                    <strong>Accrual:</strong> Recognize revenue when earned and expenses when incurred<br>
                    <strong>Cash:</strong> Recognize revenue when received and expenses when paid
                </flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Auto-Create Accounts</flux:label>
                <flux:switch name="auto_create_accounts" :checked="$settings['auto_create_accounts'] ?? true" />
                <flux:text size="sm" variant="muted">Automatically create missing accounts when posting transactions</flux:text>
            </flux:field>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">Fiscal Year</flux:heading>
        <flux:text variant="muted" class="mb-6">Configure your fiscal year settings</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Fiscal Year Start Month</flux:label>
                <flux:select name="fiscal_year_start">
                    <option value="1" {{ ($settings['fiscal_year_start'] ?? 1) == 1 ? 'selected' : '' }}>January</option>
                    <option value="2" {{ ($settings['fiscal_year_start'] ?? 0) == 2 ? 'selected' : '' }}>February</option>
                    <option value="3" {{ ($settings['fiscal_year_start'] ?? 0) == 3 ? 'selected' : '' }}>March</option>
                    <option value="4" {{ ($settings['fiscal_year_start'] ?? 0) == 4 ? 'selected' : '' }}>April</option>
                    <option value="5" {{ ($settings['fiscal_year_start'] ?? 0) == 5 ? 'selected' : '' }}>May</option>
                    <option value="6" {{ ($settings['fiscal_year_start'] ?? 0) == 6 ? 'selected' : '' }}>June</option>
                    <option value="7" {{ ($settings['fiscal_year_start'] ?? 0) == 7 ? 'selected' : '' }}>July</option>
                    <option value="8" {{ ($settings['fiscal_year_start'] ?? 0) == 8 ? 'selected' : '' }}>August</option>
                    <option value="9" {{ ($settings['fiscal_year_start'] ?? 0) == 9 ? 'selected' : '' }}>September</option>
                    <option value="10" {{ ($settings['fiscal_year_start'] ?? 0) == 10 ? 'selected' : '' }}>October</option>
                    <option value="11" {{ ($settings['fiscal_year_start'] ?? 0) == 11 ? 'selected' : '' }}>November</option>
                    <option value="12" {{ ($settings['fiscal_year_start'] ?? 0) == 12 ? 'selected' : '' }}>December</option>
                </flux:select>
                <flux:text size="sm" variant="muted">First month of your fiscal year for reporting purposes</flux:text>
            </flux:field>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">Currency Settings</flux:heading>
        <flux:text variant="muted" class="mb-6">Configure currency and multi-currency support</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Default Currency</flux:label>
                <flux:select name="default_currency">
                    <option value="USD" {{ ($settings['default_currency'] ?? 'USD') === 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                    <option value="EUR" {{ ($settings['default_currency'] ?? '') === 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                    <option value="GBP" {{ ($settings['default_currency'] ?? '') === 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                    <option value="CAD" {{ ($settings['default_currency'] ?? '') === 'CAD' ? 'selected' : '' }}>CAD - Canadian Dollar</option>
                    <option value="AUD" {{ ($settings['default_currency'] ?? '') === 'AUD' ? 'selected' : '' }}>AUD - Australian Dollar</option>
                    <option value="JPY" {{ ($settings['default_currency'] ?? '') === 'JPY' ? 'selected' : '' }}>JPY - Japanese Yen</option>
                    <option value="CHF" {{ ($settings['default_currency'] ?? '') === 'CHF' ? 'selected' : '' }}>CHF - Swiss Franc</option>
                    <option value="CNY" {{ ($settings['default_currency'] ?? '') === 'CNY' ? 'selected' : '' }}>CNY - Chinese Yuan</option>
                </flux:select>
                <flux:text size="sm" variant="muted">Primary currency for accounting and reporting</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Enable Multi-Currency</flux:label>
                <flux:switch name="enable_multi_currency" :checked="$settings['enable_multi_currency'] ?? false" />
                <flux:text size="sm" variant="muted">Allow transactions in multiple currencies with automatic conversion</flux:text>
            </flux:field>
        </div>
    </flux:card>
</div>
