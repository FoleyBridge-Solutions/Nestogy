/**
 * Nestogy Modal System
 * 
 * A comprehensive modal system to replace browser alerts, confirms, and prompts
 * with beautiful custom modals using Alpine.js and Tailwind CSS.
 */

class ModalSystem {
    constructor() {
        this.modalCounter = 0;
        this.activeModals = new Map();
    }

    /**
     * Generate unique modal ID
     */
    generateId() {
        return `modal-${++this.modalCounter}-${Date.now()}`;
    }

    /**
     * Create and show an alert modal
     * @param {string} message - The alert message
     * @param {string} type - 'info', 'success', 'warning', 'error'
     * @param {Object} options - Additional options
     */
    alert(message, type = 'info', options = {}) {
        const config = {
            title: options.title || this.getDefaultTitle(type),
            message,
            type,
            confirmText: options.confirmText || 'OK',
            icon: options.icon || this.getDefaultIcon(type),
            ...options
        };

        return this.createModal('alert', config);
    }

    /**
     * Create and show a confirm modal
     * @param {string} message - The confirmation message
     * @param {Object} options - Additional options
     */
    confirm(message, options = {}) {
        const config = {
            title: options.title || 'Confirm Action',
            message,
            type: options.type || 'warning',
            confirmText: options.confirmText || 'Confirm',
            cancelText: options.cancelText || 'Cancel',
            icon: options.icon || this.getDefaultIcon(options.type || 'warning'),
            ...options
        };

        return this.createModal('confirm', config);
    }

    /**
     * Create and show a prompt modal
     * @param {string} message - The prompt message
     * @param {Object} options - Additional options
     */
    prompt(message, options = {}) {
        const config = {
            title: options.title || 'Input Required',
            message,
            type: options.type || 'info',
            confirmText: options.confirmText || 'Submit',
            cancelText: options.cancelText || 'Cancel',
            placeholder: options.placeholder || '',
            defaultValue: options.defaultValue || '',
            inputType: options.inputType || 'text',
            icon: options.icon || this.getDefaultIcon(options.type || 'info'),
            ...options
        };

        return this.createModal('prompt', config);
    }

    /**
     * Create and show a custom modal
     * @param {string} type - Modal type: 'alert', 'confirm', 'prompt', 'custom'
     * @param {Object} config - Modal configuration
     */
    createModal(type, config) {
        const modalId = this.generateId();
        const modal = document.createElement('div');
        modal.id = modalId;
        modal.className = 'fixed inset-0 z-50 overflow-y-auto';

        // Create modal HTML based on type
        modal.innerHTML = this.getModalHTML(type, config, modalId);

        // Add to DOM
        document.body.appendChild(modal);

        // Initialize Alpine.js if needed
        if (window.Alpine) {
            window.Alpine.initTree(modal);
        }

        // Show modal with animation
        setTimeout(() => {
            modal.classList.remove('hidden');
            const backdrop = modal.querySelector('.modal-backdrop');
            const content = modal.querySelector('.modal-content');
            
            if (backdrop) backdrop.classList.remove('opacity-0');
            if (content) {
                content.classList.remove('opacity-0', 'translate-y-4', 'scale-95');
                content.classList.add('opacity-100', 'translate-y-0', 'scale-100');
            }
        }, 10);

        // Store modal reference
        this.activeModals.set(modalId, modal);

        // Return promise for result
        return new Promise((resolve, reject) => {
            // Set up event listeners
            this.setupModalEvents(modal, modalId, resolve, reject, type);
        });
    }

    /**
     * Get modal HTML based on type
     */
    getModalHTML(type, config, modalId) {
        const { title, message, icon, confirmText, cancelText } = config;
        
        const iconHTML = icon ? `
            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full ${this.getIconBgClass(config.type)} mb-4">
                ${this.getIconSVG(icon, config.type)}
            </div>
        ` : '';

        const inputHTML = type === 'prompt' ? `
            <div class="mt-4">
                <input 
                    type="${config.inputType || 'text'}" 
                    id="${modalId}-input"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                    placeholder="${config.placeholder || ''}"
                    value="${config.defaultValue || ''}"
                />
            </div>
        ` : '';

        const buttonsHTML = type === 'alert' ? `
            <button data-action="confirm" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:text-sm">
                ${confirmText}
            </button>
        ` : `
            <button data-action="confirm" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                ${confirmText}
            </button>
            <button data-action="cancel" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                ${cancelText}
            </button>
        `;

        return `
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="modal-backdrop fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity opacity-0"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="modal-content inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full opacity-0 translate-y-4 scale-95">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                ${iconHTML}
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-2">${title}</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">${message}</p>
                                    ${inputHTML}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        ${buttonsHTML}
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Setup modal event listeners
     */
    setupModalEvents(modal, modalId, resolve, reject, type) {
        const closeModal = (result = null) => {
            // Animation out
            const backdrop = modal.querySelector('.modal-backdrop');
            const content = modal.querySelector('.modal-content');
            
            if (backdrop) backdrop.classList.add('opacity-0');
            if (content) {
                content.classList.remove('opacity-100', 'translate-y-0', 'scale-100');
                content.classList.add('opacity-0', 'translate-y-4', 'scale-95');
            }

            setTimeout(() => {
                modal.remove();
                this.activeModals.delete(modalId);
            }, 200);

            return result;
        };

        // Button click handlers
        modal.addEventListener('click', (e) => {
            const action = e.target.dataset.action;
            
            if (action === 'confirm') {
                if (type === 'prompt') {
                    const input = modal.querySelector(`#${modalId}-input`);
                    const value = input ? input.value : '';
                    resolve(closeModal(value));
                } else {
                    resolve(closeModal(true));
                }
            } else if (action === 'cancel') {
                resolve(closeModal(false));
            }
        });

        // Backdrop click
        modal.querySelector('.modal-backdrop')?.addEventListener('click', () => {
            resolve(closeModal(false));
        });

        // Escape key
        const escapeHandler = (e) => {
            if (e.key === 'Escape' && this.activeModals.has(modalId)) {
                document.removeEventListener('keydown', escapeHandler);
                resolve(closeModal(false));
            }
        };
        document.addEventListener('keydown', escapeHandler);

        // Enter key for single-button modals
        if (type === 'alert') {
            const enterHandler = (e) => {
                if (e.key === 'Enter' && this.activeModals.has(modalId)) {
                    document.removeEventListener('keydown', enterHandler);
                    resolve(closeModal(true));
                }
            };
            document.addEventListener('keydown', enterHandler);
        }
    }

    /**
     * Close all modals
     */
    closeAll() {
        this.activeModals.forEach((modal, id) => {
            modal.remove();
        });
        this.activeModals.clear();
    }

    /**
     * Get default title based on type
     */
    getDefaultTitle(type) {
        const titles = {
            info: 'Information',
            success: 'Success',
            warning: 'Warning',
            error: 'Error'
        };
        return titles[type] || 'Notice';
    }

    /**
     * Get default icon based on type
     */
    getDefaultIcon(type) {
        const icons = {
            info: 'info',
            success: 'check',
            warning: 'exclamation',
            error: 'x'
        };
        return icons[type] || 'info';
    }

    /**
     * Get icon background class
     */
    getIconBgClass(type) {
        const classes = {
            info: 'bg-blue-100',
            success: 'bg-green-100',
            warning: 'bg-yellow-100',
            error: 'bg-red-100'
        };
        return classes[type] || 'bg-gray-100';
    }

    /**
     * Get icon SVG
     */
    getIconSVG(icon, type) {
        const colorClass = {
            info: 'text-blue-600',
            success: 'text-green-600',
            warning: 'text-yellow-600',
            error: 'text-red-600'
        }[type] || 'text-gray-600';

        const svgs = {
            info: `<svg class="h-6 w-6 ${colorClass}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
            check: `<svg class="h-6 w-6 ${colorClass}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>`,
            exclamation: `<svg class="h-6 w-6 ${colorClass}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>`,
            x: `<svg class="h-6 w-6 ${colorClass}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>`
        };

        return svgs[icon] || svgs.info;
    }
}

// Create global instance
window.Modal = new ModalSystem();

// Provide convenience methods that match browser API
window.customAlert = (message, type = 'info', options = {}) => {
    return window.Modal.alert(message, type, options);
};

window.customConfirm = (message, options = {}) => {
    return window.Modal.confirm(message, options);
};

window.customPrompt = (message, options = {}) => {
    return window.Modal.prompt(message, options);
};

// Export for module systems
export default ModalSystem;