import './bootstrap';

// Livewire will handle Alpine.js initialization automatically
// No need to import or initialize Alpine manually

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
// window.Alpine = Alpine; // Disabled
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

// Import utility components only (Alpine components removed)
import { layoutUtils } from './components/layout.js';

// Import modal system
import './modal-system.js';

// Import client search field Alpine component
import { clientSearchField } from './components/client-search-field.js';
window.clientSearchField = clientSearchField;

// Alpine components removed - using Livewire/Flux instead

// Setup Wizard removed - use Livewire component instead
/*
Removed Alpine setup wizard
    currentStep: 1,
    totalSteps: 5,
    
    // SMTP testing state
    smtpTesting: false,
    smtpTestResult: false,
    smtpTestSuccess: false,
    smtpTestMessage: '',
    
    // Form data
    smtp_host: '',
    smtp_port: '',
    
    nextStep() {
        if (this.currentStep < this.totalSteps) {
            this.currentStep++;
            this.updateProgressIndicator();
        }
    },
    
    previousStep() {
        if (this.currentStep > 1) {
            this.currentStep--;
            this.updateProgressIndicator();
        }
    },
    
    updateProgressIndicator() {
        // Update progress steps visual state
        const steps = document.querySelectorAll('.progress-step');
        
        steps.forEach((step, index) => {
            const stepNumber = index + 1;
            const stepCircle = step.querySelector('.step-circle');
            const stepText = step.querySelector('.step-text');
            const stepLine = step.querySelector('.step-line');
            
            if (stepNumber <= this.currentStep) {
                // Completed or current step
                stepCircle?.classList.remove('bg-gray-300', 'dark:bg-gray-600');
                stepCircle?.classList.add('bg-blue-600', 'dark:bg-blue-500');
                stepCircle?.querySelector('span')?.classList.remove('text-gray-600', 'dark:text-gray-300');
                stepCircle?.querySelector('span')?.classList.add('text-white');
                
                stepText?.classList.remove('text-gray-500', 'dark:text-gray-400');
                stepText?.classList.add('text-gray-900', 'dark:text-white');
                
                if (stepLine) {
                    stepLine.classList.remove('bg-gray-200', 'dark:bg-gray-700');
                    stepLine.classList.add('bg-blue-200', 'dark:bg-blue-800');
                }
            } else {
                // Future step
                stepCircle?.classList.remove('bg-blue-600', 'dark:bg-blue-500');
                stepCircle?.classList.add('bg-gray-300', 'dark:bg-gray-600');
                stepCircle?.querySelector('span')?.classList.remove('text-white');
                stepCircle?.querySelector('span')?.classList.add('text-gray-600', 'dark:text-gray-300');
                
                stepText?.classList.remove('text-gray-900', 'dark:text-white');
                stepText?.classList.add('text-gray-500', 'dark:text-gray-400');
                
                if (stepLine) {
                    stepLine.classList.remove('bg-blue-200', 'dark:bg-blue-800');
                    stepLine.classList.add('bg-gray-200', 'dark:bg-gray-700');
                }
            }
        });
    },
    
    async testSmtpSettings() {
        // Get current form values
        const formData = new FormData(this.$root.querySelector('form'));
        
        this.smtpTesting = true;
        this.smtpTestResult = false;
        
        try {
            const response = await fetch('/setup/test-smtp', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': formData.get('_token'),
                    'Accept': 'application/json',
                },
                body: formData
            });
            
            const result = await response.json();
            
            this.smtpTestResult = true;
            this.smtpTestSuccess = result.success;
            this.smtpTestMessage = result.message;
            
        } catch (error) {
            this.smtpTestResult = true;
            this.smtpTestSuccess = false;
            this.smtpTestMessage = 'Network error: ' + error.message;
        }
        
        this.smtpTesting = false;
    },
    
    init() {
        // Initialize progress indicator on component mount
        this.$nextTick(() => {
            // Add classes to step elements for easier targeting
            const stepElements = document.querySelectorAll('nav[aria-label="Progress"] li');
            stepElements.forEach((step, index) => {
                step.classList.add('progress-step');
                const circle = step.querySelector('div:first-child div');
                if (circle) circle.classList.add('step-circle');
                const text = step.querySelector('span');
                if (text) text.classList.add('step-text');
                const line = step.querySelector('div.absolute');
                if (line) line.classList.add('step-line');
            });
            
            this.updateProgressIndicator();
        });
    }
}));
*/

// Make utilities available globally
window.layoutUtils = layoutUtils;

// Alpine components removed - use Livewire instead
/*
Removed Alpine components
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

// Alpine components removed - using Livewire/Flux components only
*/

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

// Alpine.js completely removed - using Flux/Livewire only
