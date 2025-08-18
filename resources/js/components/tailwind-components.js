/**
 * Tailwind-based Alpine.js Components
 * Replacements for Bootstrap JavaScript components
 */

import Alpine from 'alpinejs';
import tippy from 'tippy.js';

// Modal Component
Alpine.data('modal', (initialOpen = false) => ({
    open: initialOpen,
    
    init() {
        // Listen for global modal events
        this.$watch('open', value => {
            if (value) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });
        
        // Listen for escape key
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.open) {
                this.close();
            }
        });
    },
    
    toggle() {
        this.open = !this.open;
    },
    
    close() {
        this.open = false;
    },
    
    open() {
        this.open = true;
    }
}));

// Dropdown Component
Alpine.data('dropdown', () => ({
    open: false,
    
    init() {
        // Close on click outside
        document.addEventListener('click', (e) => {
            if (!this.$el.contains(e.target)) {
                this.open = false;
            }
        });
    },
    
    toggle() {
        this.open = !this.open;
    }
}));

// Toast/Alert Component
Alpine.data('toast', (message = '', type = 'info', duration = 3000) => ({
    show: false,
    message: message,
    type: type,
    
    init() {
        if (this.message) {
            this.display();
        }
        
        // Listen for global toast events
        window.addEventListener('show-toast', (event) => {
            this.message = event.detail.message;
            this.type = event.detail.type || 'info';
            this.display();
        });
    },
    
    display() {
        this.show = true;
        
        if (duration > 0) {
            setTimeout(() => {
                this.close();
            }, duration);
        }
    },
    
    close() {
        this.show = false;
    },
    
    get bgColor() {
        const colors = {
            'success': 'bg-green-500',
            'error': 'bg-red-500',
            'warning': 'bg-yellow-500',
            'info': 'bg-blue-500'
        };
        return colors[this.type] || 'bg-gray-500';
    },
    
    get iconClass() {
        const icons = {
            'success': 'fa-check-circle',
            'error': 'fa-exclamation-circle',
            'warning': 'fa-exclamation-triangle',
            'info': 'fa-info-circle'
        };
        return icons[this.type] || 'fa-info-circle';
    }
}));

// Tabs Component
Alpine.data('tabs', (defaultTab = 0) => ({
    activeTab: defaultTab,
    
    init() {
        // Set initial tab from URL hash if present
        const hash = window.location.hash.substring(1);
        if (hash) {
            const tabIndex = parseInt(hash.replace('tab-', ''));
            if (!isNaN(tabIndex)) {
                this.activeTab = tabIndex;
            }
        }
    },
    
    selectTab(index) {
        this.activeTab = index;
        window.location.hash = `tab-${index}`;
    },
    
    isActive(index) {
        return this.activeTab === index;
    }
}));

// Accordion Component
Alpine.data('accordion', (allowMultiple = false) => ({
    activeItems: [],
    allowMultiple: allowMultiple,
    
    toggle(id) {
        if (this.allowMultiple) {
            const index = this.activeItems.indexOf(id);
            if (index > -1) {
                this.activeItems.splice(index, 1);
            } else {
                this.activeItems.push(id);
            }
        } else {
            this.activeItems = this.isActive(id) ? [] : [id];
        }
    },
    
    isActive(id) {
        return this.activeItems.includes(id);
    }
}));

// Collapse Component
Alpine.data('collapse', (initialOpen = false) => ({
    open: initialOpen,
    
    toggle() {
        this.open = !this.open;
    }
}));

// Offcanvas/Sidebar Component
Alpine.data('offcanvas', () => ({
    open: false,
    
    init() {
        // Close on escape
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.open) {
                this.close();
            }
        });
    },
    
    toggle() {
        this.open = !this.open;
        if (this.open) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    },
    
    close() {
        this.open = false;
        document.body.style.overflow = '';
    }
}));

// Popover Component (using Tippy.js)
Alpine.directive('popover', (el, { expression }, { evaluate }) => {
    const content = evaluate(expression) || el.getAttribute('data-popover-content') || '';
    
    tippy(el, {
        content: content,
        trigger: 'click',
        interactive: true,
        placement: 'top',
        theme: 'tailwind',
        arrow: true,
        animation: 'shift-away'
    });
});

// Tooltip Component (using Tippy.js)
Alpine.directive('tooltip', (el, { expression }, { evaluate }) => {
    const content = evaluate(expression) || el.getAttribute('title') || el.getAttribute('data-tooltip') || '';
    
    // Remove title attribute to prevent browser default tooltip
    el.removeAttribute('title');
    
    tippy(el, {
        content: content,
        placement: 'top',
        theme: 'tailwind',
        arrow: true,
        animation: 'shift-away'
    });
});

// Progress Component
Alpine.data('progress', (value = 0, max = 100) => ({
    value: value,
    max: max,
    
    get percentage() {
        return Math.min(100, Math.max(0, (this.value / this.max) * 100));
    },
    
    setValue(value) {
        this.value = value;
    },
    
    increment(amount = 1) {
        this.value = Math.min(this.max, this.value + amount);
    },
    
    decrement(amount = 1) {
        this.value = Math.max(0, this.value - amount);
    }
}));

// Pagination Component
Alpine.data('pagination', (totalPages = 1, currentPage = 1) => ({
    totalPages: totalPages,
    currentPage: currentPage,
    maxVisible: 5,
    
    get pages() {
        const pages = [];
        const half = Math.floor(this.maxVisible / 2);
        let start = Math.max(1, this.currentPage - half);
        let end = Math.min(this.totalPages, start + this.maxVisible - 1);
        
        if (end - start < this.maxVisible - 1) {
            start = Math.max(1, end - this.maxVisible + 1);
        }
        
        for (let i = start; i <= end; i++) {
            pages.push(i);
        }
        
        return pages;
    },
    
    get showFirst() {
        return this.pages[0] > 1;
    },
    
    get showLast() {
        return this.pages[this.pages.length - 1] < this.totalPages;
    },
    
    goToPage(page) {
        if (page >= 1 && page <= this.totalPages) {
            this.currentPage = page;
            this.$dispatch('page-changed', { page: page });
        }
    },
    
    nextPage() {
        this.goToPage(this.currentPage + 1);
    },
    
    prevPage() {
        this.goToPage(this.currentPage - 1);
    }
}));

// Carousel/Slider Component
Alpine.data('carousel', (items = [], autoplay = false, interval = 5000) => ({
    items: items,
    currentIndex: 0,
    autoplay: autoplay,
    interval: interval,
    timer: null,
    
    init() {
        if (this.autoplay) {
            this.startAutoplay();
        }
    },
    
    get currentItem() {
        return this.items[this.currentIndex];
    },
    
    next() {
        this.currentIndex = (this.currentIndex + 1) % this.items.length;
        this.resetAutoplay();
    },
    
    prev() {
        this.currentIndex = (this.currentIndex - 1 + this.items.length) % this.items.length;
        this.resetAutoplay();
    },
    
    goTo(index) {
        this.currentIndex = index;
        this.resetAutoplay();
    },
    
    startAutoplay() {
        if (this.autoplay) {
            this.timer = setInterval(() => this.next(), this.interval);
        }
    },
    
    stopAutoplay() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    },
    
    resetAutoplay() {
        this.stopAutoplay();
        this.startAutoplay();
    },
    
    destroy() {
        this.stopAutoplay();
    }
}));

// Form Validation Component
Alpine.data('formValidation', () => ({
    errors: {},
    touched: {},
    
    validateField(field, value, rules) {
        this.touched[field] = true;
        const errors = [];
        
        for (const rule of rules) {
            if (rule.required && !value) {
                errors.push(rule.message || 'This field is required');
            }
            
            if (rule.minLength && value.length < rule.minLength) {
                errors.push(rule.message || `Minimum length is ${rule.minLength}`);
            }
            
            if (rule.maxLength && value.length > rule.maxLength) {
                errors.push(rule.message || `Maximum length is ${rule.maxLength}`);
            }
            
            if (rule.pattern && !rule.pattern.test(value)) {
                errors.push(rule.message || 'Invalid format');
            }
            
            if (rule.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                errors.push(rule.message || 'Invalid email address');
            }
            
            if (rule.custom && !rule.custom(value)) {
                errors.push(rule.message || 'Validation failed');
            }
        }
        
        this.errors[field] = errors;
        return errors.length === 0;
    },
    
    getError(field) {
        return this.touched[field] ? (this.errors[field] || [])[0] : null;
    },
    
    hasError(field) {
        return this.touched[field] && this.errors[field] && this.errors[field].length > 0;
    },
    
    isValid() {
        return Object.values(this.errors).every(errors => errors.length === 0);
    },
    
    reset() {
        this.errors = {};
        this.touched = {};
    }
}));

// Confirmation Dialog Component
Alpine.data('confirmDialog', () => ({
    show: false,
    title: 'Confirm',
    message: 'Are you sure?',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    onConfirm: null,
    onCancel: null,
    
    open(options = {}) {
        this.title = options.title || this.title;
        this.message = options.message || this.message;
        this.confirmText = options.confirmText || this.confirmText;
        this.cancelText = options.cancelText || this.cancelText;
        this.onConfirm = options.onConfirm || null;
        this.onCancel = options.onCancel || null;
        this.show = true;
    },
    
    confirm() {
        if (this.onConfirm) {
            this.onConfirm();
        }
        this.close();
    },
    
    cancel() {
        if (this.onCancel) {
            this.onCancel();
        }
        this.close();
    },
    
    close() {
        this.show = false;
    }
}));

// Export for use in app.js
export default {
    modal: Alpine.data('modal'),
    dropdown: Alpine.data('dropdown'),
    toast: Alpine.data('toast'),
    tabs: Alpine.data('tabs'),
    accordion: Alpine.data('accordion'),
    collapse: Alpine.data('collapse'),
    offcanvas: Alpine.data('offcanvas'),
    progress: Alpine.data('progress'),
    pagination: Alpine.data('pagination'),
    carousel: Alpine.data('carousel'),
    formValidation: Alpine.data('formValidation'),
    confirmDialog: Alpine.data('confirmDialog')
};