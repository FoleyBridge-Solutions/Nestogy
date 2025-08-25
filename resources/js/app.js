import './bootstrap';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';
import mask from '@alpinejs/mask';
import persist from '@alpinejs/persist';

// Alpine.js plugins
Alpine.plugin(collapse);
Alpine.plugin(focus);
Alpine.plugin(mask);
Alpine.plugin(persist);

// Tailwind CSS is imported via app.css

// FontAwesome
import '@fortawesome/fontawesome-free/css/all.min.css';

// Flatpickr for date/time picking
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

// Tom Select for enhanced selects
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.css';

// SweetAlert2 for modals/alerts
import Swal from 'sweetalert2';

// Chart.js for charts
import {
    Chart,
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    ArcElement,
    LineController,
    BarController,
    DoughnutController,
    PieController,
    Title,
    Tooltip,
    Legend,
    TimeScale,
    Filler
} from 'chart.js';
import 'chartjs-adapter-date-fns';

Chart.register(
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    ArcElement,
    LineController,
    BarController,
    DoughnutController,
    PieController,
    Title,
    Tooltip,
    Legend,
    TimeScale,
    Filler
);

// FullCalendar
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

// Clipboard
import ClipboardJS from 'clipboard';

// Date utilities
import { format, parseISO, addDays, subDays, startOfMonth, endOfMonth } from 'date-fns';

// Make libraries available globally
window.Alpine = Alpine;
window.flatpickr = flatpickr;
window.TomSelect = TomSelect;
window.Swal = Swal;
window.Chart = Chart;
window.Calendar = Calendar;
window.ClipboardJS = ClipboardJS;
window.dateFns = { format, parseISO, addDays, subDays, startOfMonth, endOfMonth };

// Calendar plugins
window.FullCalendarPlugins = {
    dayGridPlugin,
    timeGridPlugin,
    interactionPlugin
};

// Import modern components
import { modernDashboard } from './components/dashboard.js';
import { modernLayout, layoutUtils } from './components/layout.js';
import { commandPalette } from './components/command-palette.js';
import { clientSwitcher } from './components/client-switcher.js';
import { clientSearchField } from './components/client-search-field.js';
import './components/contact-search-field.js';
import './components/asset-search-field.js';
import './components/user-search-field.js';
import { ticketCreateForm } from './components/ticket-create-form.js';
import { productCreateForm } from './components/product-create-form.js';
import { ticketMerge } from './components/ticket-merge.js';
import { adminTerminal } from './components/admin-terminal.js';
import './components/financial-document-builder-simple.js';
import './components/financial-document-builder-advanced.js';
import productSelectorAdvanced from './components/product-selector-advanced.js';

// Import Tailwind replacement components
import './components/tailwind-components.js';

// Import modal system
import './modal-system.js';

// Register components globally
Alpine.data('modernDashboard', modernDashboard);
Alpine.data('modernLayout', modernLayout);
Alpine.data('commandPalette', commandPalette);
Alpine.data('clientSwitcher', clientSwitcher);
Alpine.data('clientSearchField', clientSearchField);
Alpine.data('ticketCreateForm', ticketCreateForm);
Alpine.data('productCreateForm', productCreateForm);
Alpine.data('ticketMerge', ticketMerge);
Alpine.data('adminTerminal', adminTerminal);
Alpine.data('productSelectorAdvanced', productSelectorAdvanced);

// Make utilities available globally
window.layoutUtils = layoutUtils;

// Alpine.js components
Alpine.data('fileUpload', () => ({
    uploading: false,
    error: null,
    files: [],
    
    async uploadFile(event) {
        const files = Array.from(event.target.files);
        if (!files.length) return;
        
        this.uploading = true;
        this.error = null;
        
        for (const file of files) {
            const formData = new FormData();
            formData.append('file', file);
            
            try {
                const response = await fetch('/upload', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    this.files.push(result.file);
                } else {
                    this.error = result.error;
                }
            } catch (error) {
                this.error = 'Upload failed';
            }
        }
        
        this.uploading = false;
    },
    
    removeFile(index) {
        this.files.splice(index, 1);
    }
}));

Alpine.data('dateRangePicker', () => ({
    startDate: null,
    endDate: null,
    
    init() {
        flatpickr(this.$refs.dateRange, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            onChange: (selectedDates) => {
                this.startDate = selectedDates[0] ? format(selectedDates[0], 'yyyy-MM-dd') : null;
                this.endDate = selectedDates[1] ? format(selectedDates[1], 'yyyy-MM-dd') : null;
            }
        });
    }
}));

Alpine.data('enhancedSelect', () => ({
    tomSelect: null,
    init() {
        this.tomSelect = new TomSelect(this.$refs.select, {
            plugins: ['remove_button'],
            create: false,
            sortField: {
                field: 'text',
                direction: 'asc'
            }
        });
    },
    destroy() {
        if (this.tomSelect) {
            this.tomSelect.destroy();
            this.tomSelect = null;
        }
    }
}));

Alpine.data('chartComponent', () => ({
    chart: null,
    
    init() {
        this.initChart();
    },
    
    initChart() {
        const ctx = this.$refs.canvas.getContext('2d');
        this.chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: []
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Chart'
                    }
                }
            }
        });
    },
    
    updateChart(data) {
        this.chart.data = data;
        this.chart.update();
    }
}));

Alpine.data('calendar', () => ({
    calendar: null,
    
    init() {
        this.calendar = new Calendar(this.$refs.calendar, {
            plugins: [
                dayGridPlugin,
                timeGridPlugin,
                interactionPlugin
            ],
            themeSystem: 'standard',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            initialView: 'dayGridMonth',
            editable: true,
            selectable: true,
            events: '/api/calendar/events',
            eventClick: (info) => {
                this.handleEventClick(info);
            },
            select: (info) => {
                this.handleDateSelect(info);
            }
        });
        
        this.calendar.render();
    },
    
    handleEventClick(info) {
        // Handle event click
        console.log('Event clicked:', info.event);
    },
    
    handleDateSelect(info) {
        // Handle date selection
        console.log('Date selected:', info);
    }
}));

// Initialize clipboard functionality
document.addEventListener('DOMContentLoaded', () => {
    // Initialize clipboard buttons
    const clipboard = new ClipboardJS('[data-clipboard-text]');
    
    clipboard.on('success', (e) => {
        Swal.fire({
            icon: 'success',
            title: 'Copied!',
            text: 'Text copied to clipboard',
            timer: 1500,
            showConfirmButton: false
        });
    });
    
    // Initialize Tippy.js for tooltips (Tailwind-friendly alternative)
    // Tooltips will be initialized per component as needed
});

// Utility functions - both SweetAlert2 and custom modal system
window.showAlert = (type, title, message) => {
    Swal.fire({
        icon: type,
        title: title,
        text: message
    });
};

window.showConfirm = (title, message, callback) => {
    Swal.fire({
        title: title,
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, proceed!'
    }).then((result) => {
        if (result.isConfirmed && callback) {
            callback();
        }
    });
};

// Enhanced modal utilities using our custom modal system
window.showNotification = (message, type = 'info', options = {}) => {
    const config = {
        title: options.title || (type === 'error' ? 'Error' : type === 'success' ? 'Success' : 'Notice'),
        confirmText: 'OK',
        ...options
    };
    return window.customAlert(message, type, config);
};

window.confirmAction = async (message, options = {}) => {
    const config = {
        title: options.title || 'Confirm Action',
        confirmText: options.confirmText || 'Confirm',
        cancelText: options.cancelText || 'Cancel',
        type: options.type || 'warning',
        ...options
    };
    return await window.customConfirm(message, config);
};

window.promptUser = async (message, options = {}) => {
    const config = {
        title: options.title || 'Input Required',
        placeholder: options.placeholder || '',
        defaultValue: options.defaultValue || '',
        confirmText: options.confirmText || 'Submit',
        cancelText: options.cancelText || 'Cancel',
        ...options
    };
    return await window.customPrompt(message, config);
};

window.formatDate = (date, formatStr = 'yyyy-MM-dd') => {
    return format(parseISO(date), formatStr);
};

// Start Alpine
Alpine.start();
