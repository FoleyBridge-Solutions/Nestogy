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

// Bootstrap
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';

// FontAwesome
import '@fortawesome/fontawesome-free/css/all.min.css';

// Flatpickr for date/time picking
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

// Tom Select for enhanced selects
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.bootstrap5.css';

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
import bootstrap5Plugin from '@fullcalendar/bootstrap5';

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
    interactionPlugin,
    bootstrap5Plugin
};

// Import modern components
import { modernDashboard } from './components/dashboard.js';
import { modernLayout, layoutUtils } from './components/layout.js';
import { clientSwitcher } from './components/client-switcher.js';

// Register components globally
Alpine.data('modernDashboard', modernDashboard);
Alpine.data('modernLayout', modernLayout);
Alpine.data('clientSwitcher', clientSwitcher);

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
    init() {
        new TomSelect(this.$refs.select, {
            plugins: ['remove_button'],
            create: false,
            sortField: {
                field: 'text',
                direction: 'asc'
            }
        });
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
                interactionPlugin,
                bootstrap5Plugin
            ],
            themeSystem: 'bootstrap5',
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
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));
});

// Utility functions
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

window.formatDate = (date, formatStr = 'yyyy-MM-dd') => {
    return format(parseISO(date), formatStr);
};

// Start Alpine
Alpine.start();
