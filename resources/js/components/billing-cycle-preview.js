/**
 * Billing Cycle Preview Component
 * Visual preview of billing cycles and revenue patterns for subscription services
 */

class BillingCyclePreview {
    constructor(options = {}) {
        this.options = {
            containerId: 'billing-cycle-preview',
            maxPreviewMonths: 12,
            currency: 'USD',
            locale: 'en-US',
            showChart: true,
            showTable: true,
            ...options
        };
        
        this.state = {
            billingModel: 'one_time',
            billingCycle: 'month',
            billingInterval: 1,
            price: 0,
            startDate: new Date(),
            projectionMonths: 12
        };
        
        this.billingDates = [];
        this.chart = null;
        
        this.init();
    }

    init() {
        this.createContainer();
        this.bindEvents();
        this.updatePreview();
    }

    createContainer() {
        const container = document.getElementById(this.options.containerId);
        if (!container) {
            console.warn('Billing cycle preview container not found');
            return;
        }

        container.innerHTML = this.getPreviewHTML();
        this.container = container;
    }

    getPreviewHTML() {
        return `
            <div class="billing-cycle-preview card">
                <div class="card-header">
                    <h6 class="card-title mb-0 d-flex align-items-center">
                        <i class="fas fa-calendar-alt text-info me-2"></i>
                        Billing Cycle Preview
                        <div class="ms-auto">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="customize-preview">
                                <i class="fas fa-cog"></i> Customize
                            </button>
                        </div>
                    </h6>
                </div>
                <div class="card-body">
                    <!-- Quick Settings -->
                    <div class="row mb-3" id="preview-controls">
                        <div class="col-md-3">
                            <label class="form-label small">Start Date</label>
                            <input type="date" id="preview-start-date" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Preview Period</label>
                            <select id="preview-months" class="form-select form-select-sm">
                                <option value="6">6 months</option>
                                <option value="12" selected>12 months</option>
                                <option value="24">24 months</option>
                                <option value="36">36 months</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">View Type</label>
                            <select id="preview-view" class="form-select form-select-sm">
                                <option value="both" selected>Chart & Table</option>
                                <option value="chart">Chart Only</option>
                                <option value="table">Table Only</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Currency</label>
                            <select id="preview-currency" class="form-select form-select-sm">
                                <option value="USD" selected>USD</option>
                                <option value="EUR">EUR</option>
                                <option value="GBP">GBP</option>
                                <option value="CAD">CAD</option>
                            </select>
                        </div>
                    </div>

                    <!-- Billing Summary -->
                    <div class="billing-summary row mb-4">
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="small text-muted">Billing Frequency</div>
                                <div id="billing-frequency" class="fw-bold">-</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="small text-muted">Next Billing</div>
                                <div id="next-billing" class="fw-bold">-</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="small text-muted">Total Billings</div>
                                <div id="total-billings" class="fw-bold">-</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="small text-muted">Projected Revenue</div>
                                <div id="projected-revenue" class="fw-bold text-success">$0.00</div>
                            </div>
                        </div>
                    </div>

                    <!-- Chart View -->
                    <div id="billing-chart-container" class="mb-4">
                        <canvas id="billing-chart" width="400" height="200"></canvas>
                    </div>

                    <!-- Table View -->
                    <div id="billing-table-container">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Billing Date</th>
                                        <th>Amount</th>
                                        <th>Days Since Last</th>
                                        <th>Cumulative Revenue</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="billing-table-body">
                                    <!-- Billing rows will be populated here -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Export Options -->
                    <div class="mt-3 d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="export-csv">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" id="export-calendar">
                            <i class="fas fa-calendar-plus"></i> Add to Calendar
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm" id="print-preview">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    bindEvents() {
        if (!this.container) return;

        // Form input changes
        document.addEventListener('change', (e) => {
            if (this.isRelevantInput(e.target)) {
                this.updateFromForm();
            }
        });

        // Preview control changes
        const controls = ['preview-start-date', 'preview-months', 'preview-view', 'preview-currency'];
        controls.forEach(id => {
            const element = this.container.querySelector(`#${id}`);
            if (element) {
                element.addEventListener('change', () => this.updatePreview());
            }
        });

        // Action buttons
        this.bindActionEvents();
    }

    bindActionEvents() {
        const buttons = [
            { id: 'customize-preview', handler: () => this.showCustomizeModal() },
            { id: 'export-csv', handler: () => this.exportCSV() },
            { id: 'export-calendar', handler: () => this.exportCalendar() },
            { id: 'print-preview', handler: () => this.printPreview() }
        ];

        buttons.forEach(({ id, handler }) => {
            const button = this.container.querySelector(`#${id}`);
            if (button) {
                button.addEventListener('click', handler);
            }
        });
    }

    isRelevantInput(input) {
        const relevantNames = ['billing_model', 'billing_cycle', 'billing_interval', 'price', 'base_price'];
        return relevantNames.includes(input.name);
    }

    updateFromForm() {
        // Update state from form inputs
        const billingModelInput = document.querySelector('select[name="billing_model"]');
        const billingCycleInput = document.querySelector('select[name="billing_cycle"]');
        const billingIntervalInput = document.querySelector('input[name="billing_interval"]');
        const priceInput = document.querySelector('input[name="price"], input[name="base_price"]');

        if (billingModelInput) this.state.billingModel = billingModelInput.value;
        if (billingCycleInput) this.state.billingCycle = billingCycleInput.value;
        if (billingIntervalInput) this.state.billingInterval = parseInt(billingIntervalInput.value) || 1;
        if (priceInput) this.state.price = parseFloat(priceInput.value) || 0;

        // Update currency from preview controls
        const currencySelect = this.container.querySelector('#preview-currency');
        if (currencySelect) this.options.currency = currencySelect.value;

        this.updatePreview();
    }

    updatePreview() {
        if (this.state.billingModel !== 'subscription') {
            this.showOneTimeMessage();
            return;
        }

        this.updateStartDate();
        this.updateProjectionMonths();
        this.calculateBillingDates();
        this.updateSummary();
        this.updateChart();
        this.updateTable();
        this.updateVisibility();
    }

    showOneTimeMessage() {
        if (!this.container) return;
        
        const chartContainer = this.container.querySelector('#billing-chart-container');
        const tableContainer = this.container.querySelector('#billing-table-container');
        
        if (chartContainer) {
            chartContainer.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">One-Time Billing</h6>
                    <p class="text-muted">No recurring billing cycle for this billing model.</p>
                </div>
            `;
        }
        
        if (tableContainer) {
            tableContainer.style.display = 'none';
        }
    }

    updateStartDate() {
        const startDateInput = this.container.querySelector('#preview-start-date');
        if (startDateInput && startDateInput.value) {
            this.state.startDate = new Date(startDateInput.value);
        } else {
            this.state.startDate = new Date();
            if (startDateInput) {
                startDateInput.value = this.formatDate(this.state.startDate);
            }
        }
    }

    updateProjectionMonths() {
        const monthsSelect = this.container.querySelector('#preview-months');
        if (monthsSelect) {
            this.state.projectionMonths = parseInt(monthsSelect.value) || 12;
        }
    }

    calculateBillingDates() {
        this.billingDates = [];
        let currentDate = new Date(this.state.startDate);
        const endDate = new Date(this.state.startDate);
        endDate.setMonth(endDate.getMonth() + this.state.projectionMonths);

        let billingNumber = 1;
        while (currentDate <= endDate) {
            this.billingDates.push({
                number: billingNumber++,
                date: new Date(currentDate),
                amount: this.state.price,
                daysSinceLast: billingNumber === 1 ? 0 : this.getDaysBetween(this.billingDates[this.billingDates.length - 1]?.date, currentDate)
            });

            // Calculate next billing date
            currentDate = this.getNextBillingDate(currentDate);
        }
    }

    getNextBillingDate(currentDate) {
        const nextDate = new Date(currentDate);
        const interval = this.state.billingInterval;

        switch (this.state.billingCycle) {
            case 'day':
                nextDate.setDate(nextDate.getDate() + interval);
                break;
            case 'week':
                nextDate.setDate(nextDate.getDate() + (interval * 7));
                break;
            case 'month':
                nextDate.setMonth(nextDate.getMonth() + interval);
                break;
            case 'quarter':
                nextDate.setMonth(nextDate.getMonth() + (interval * 3));
                break;
            case 'year':
                nextDate.setFullYear(nextDate.getFullYear() + interval);
                break;
        }

        return nextDate;
    }

    getDaysBetween(date1, date2) {
        if (!date1 || !date2) return 0;
        const diffTime = Math.abs(date2 - date1);
        return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    }

    updateSummary() {
        if (!this.container) return;

        const frequency = this.getBillingFrequencyText();
        const nextBilling = this.billingDates.length > 1 ? this.billingDates[1].date : null;
        const totalBillings = this.billingDates.length;
        const projectedRevenue = totalBillings * this.state.price;

        this.updateElement('#billing-frequency', frequency);
        this.updateElement('#next-billing', nextBilling ? this.formatDate(nextBilling) : 'N/A');
        this.updateElement('#total-billings', totalBillings.toString());
        this.updateElement('#projected-revenue', this.formatCurrency(projectedRevenue));
    }

    getBillingFrequencyText() {
        const interval = this.state.billingInterval;
        const cycle = this.state.billingCycle;
        
        if (interval === 1) {
            return cycle.charAt(0).toUpperCase() + cycle.slice(1) + 'ly';
        } else {
            return `Every ${interval} ${cycle}s`;
        }
    }

    updateChart() {
        if (!this.options.showChart || !this.container) return;

        const chartContainer = this.container.querySelector('#billing-chart-container');
        const viewType = this.container.querySelector('#preview-view')?.value || 'both';
        
        if (viewType === 'table') {
            chartContainer.style.display = 'none';
            return;
        }

        chartContainer.style.display = 'block';
        this.renderChart();
    }

    renderChart() {
        const canvas = this.container.querySelector('#billing-chart');
        if (!canvas) return;

        // Destroy existing chart
        if (this.chart) {
            this.chart.destroy();
        }

        const ctx = canvas.getContext('2d');
        const labels = this.billingDates.map(billing => this.formatDate(billing.date, 'MMM yyyy'));
        const data = this.billingDates.map(billing => billing.amount);
        const cumulativeData = this.billingDates.map((billing, index) => 
            this.billingDates.slice(0, index + 1).reduce((sum, b) => sum + b.amount, 0)
        );

        if (window.Chart) {
            this.chart = new window.Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Billing Amount',
                            data: data,
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.1)',
                            yAxisID: 'y',
                        },
                        {
                            label: 'Cumulative Revenue',
                            data: cumulativeData,
                            borderColor: 'rgb(255, 99, 132)',
                            backgroundColor: 'rgba(255, 99, 132, 0.1)',
                            yAxisID: 'y1',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Billing Date'
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Billing Amount'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Cumulative Revenue'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Billing Cycle Revenue Projection'
                        },
                        legend: {
                            display: true
                        }
                    }
                }
            });
        }
    }

    updateTable() {
        if (!this.container) return;

        const tableContainer = this.container.querySelector('#billing-table-container');
        const viewType = this.container.querySelector('#preview-view')?.value || 'both';
        
        if (viewType === 'chart') {
            tableContainer.style.display = 'none';
            return;
        }

        tableContainer.style.display = 'block';
        this.renderTable();
    }

    renderTable() {
        const tableBody = this.container.querySelector('#billing-table-body');
        if (!tableBody) return;

        let cumulativeRevenue = 0;
        const today = new Date();
        
        tableBody.innerHTML = this.billingDates.map(billing => {
            cumulativeRevenue += billing.amount;
            const isPast = billing.date < today;
            const status = isPast ? 
                '<span class="badge bg-success">Completed</span>' : 
                '<span class="badge bg-primary">Scheduled</span>';
            
            return `
                <tr class="${isPast ? 'table-secondary' : ''}">
                    <td>${billing.number}</td>
                    <td>${this.formatDate(billing.date)}</td>
                    <td>${this.formatCurrency(billing.amount)}</td>
                    <td>${billing.daysSinceLast || '-'}</td>
                    <td class="fw-bold">${this.formatCurrency(cumulativeRevenue)}</td>
                    <td>${status}</td>
                </tr>
            `;
        }).join('');
    }

    updateVisibility() {
        const viewType = this.container.querySelector('#preview-view')?.value || 'both';
        const chartContainer = this.container.querySelector('#billing-chart-container');
        const tableContainer = this.container.querySelector('#billing-table-container');

        switch (viewType) {
            case 'chart':
                if (chartContainer) chartContainer.style.display = 'block';
                if (tableContainer) tableContainer.style.display = 'none';
                break;
            case 'table':
                if (chartContainer) chartContainer.style.display = 'none';
                if (tableContainer) tableContainer.style.display = 'block';
                break;
            default: // 'both'
                if (chartContainer) chartContainer.style.display = 'block';
                if (tableContainer) tableContainer.style.display = 'block';
        }
    }

    updateElement(selector, content) {
        const element = this.container.querySelector(selector);
        if (element) {
            element.textContent = content;
        }
    }

    formatDate(date, format = 'yyyy-MM-dd') {
        if (!date) return '';
        
        const options = {
            'yyyy-MM-dd': { year: 'numeric', month: '2-digit', day: '2-digit' },
            'MMM yyyy': { year: 'numeric', month: 'short' }
        };

        return new Intl.DateTimeFormat(this.options.locale, options[format] || options['yyyy-MM-dd'])
            .format(date);
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat(this.options.locale, {
            style: 'currency',
            currency: this.options.currency
        }).format(amount || 0);
    }

    // Export functions
    exportCSV() {
        const headers = ['Billing #', 'Date', 'Amount', 'Days Since Last', 'Cumulative Revenue'];
        const rows = this.billingDates.map((billing, index) => [
            billing.number,
            this.formatDate(billing.date),
            billing.amount,
            billing.daysSinceLast || '',
            this.billingDates.slice(0, index + 1).reduce((sum, b) => sum + b.amount, 0)
        ]);

        const csvContent = [headers, ...rows]
            .map(row => row.map(field => `"${field}"`).join(','))
            .join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `billing-schedule-${Date.now()}.csv`;
        a.click();
        URL.revokeObjectURL(url);
    }

    exportCalendar() {
        // Generate ICS calendar file
        const events = this.billingDates.map(billing => {
            const date = billing.date.toISOString().replace(/[-:]/g, '').split('T')[0];
            return [
                'BEGIN:VEVENT',
                `DTSTART:${date}`,
                `DTEND:${date}`,
                `SUMMARY:Billing Due - ${this.formatCurrency(billing.amount)}`,
                `DESCRIPTION:Recurring billing #${billing.number}`,
                'END:VEVENT'
            ].join('\n');
        });

        const icsContent = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Nestogy//Billing Schedule//EN',
            ...events,
            'END:VCALENDAR'
        ].join('\n');

        const blob = new Blob([icsContent], { type: 'text/calendar' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `billing-schedule-${Date.now()}.ics`;
        a.click();
        URL.revokeObjectURL(url);
    }

    printPreview() {
        const printWindow = window.open('', '_blank');
        const printContent = this.generatePrintHTML();
        
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.print();
    }

    generatePrintHTML() {
        return `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Billing Schedule</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .header { text-align: center; margin-bottom: 30px; }
                    .summary { margin-bottom: 20px; }
                    .summary-item { display: inline-block; margin-right: 30px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                    @media print { .no-print { display: none; } }
                </style>
            </head>
            <body>
                <div class="header">
                    <h2>Billing Schedule Preview</h2>
                    <p>Generated on ${new Date().toLocaleDateString()}</p>
                </div>
                
                <div class="summary">
                    <div class="summary-item"><strong>Billing Frequency:</strong> ${this.getBillingFrequencyText()}</div>
                    <div class="summary-item"><strong>Price per Billing:</strong> ${this.formatCurrency(this.state.price)}</div>
                    <div class="summary-item"><strong>Total Billings:</strong> ${this.billingDates.length}</div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Billing #</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Cumulative Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${this.billingDates.map((billing, index) => {
                            const cumulative = this.billingDates.slice(0, index + 1).reduce((sum, b) => sum + b.amount, 0);
                            return `
                                <tr>
                                    <td>${billing.number}</td>
                                    <td>${this.formatDate(billing.date)}</td>
                                    <td>${this.formatCurrency(billing.amount)}</td>
                                    <td>${this.formatCurrency(cumulative)}</td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </body>
            </html>
        `;
    }

    showCustomizeModal() {
        if (window.Swal) {
            window.Swal.fire({
                title: 'Customize Preview',
                html: `
                    <div class="text-start">
                        <div class="mb-3">
                            <label class="form-label">Preview Period (months)</label>
                            <input type="number" id="custom-months" class="form-control" value="${this.state.projectionMonths}" min="1" max="60">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" id="custom-start-date" class="form-control" value="${this.formatDate(this.state.startDate)}">
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Apply',
                preConfirm: () => {
                    const months = document.getElementById('custom-months').value;
                    const startDate = document.getElementById('custom-start-date').value;
                    
                    return { months: parseInt(months), startDate };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    this.state.projectionMonths = result.value.months;
                    this.state.startDate = new Date(result.value.startDate);
                    this.updatePreview();
                }
            });
        }
    }
}

// Auto-initialize if container exists
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('billing-cycle-preview');
    if (container && !window.billingCyclePreview) {
        window.billingCyclePreview = new BillingCyclePreview();
    }
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BillingCyclePreview;
}