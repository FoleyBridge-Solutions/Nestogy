/**
 * Modern Layout Components
 * Optimized for Vite and Tailwind CSS
 */

export function modernLayout() {
    return {
        // State Management
        sidebarOpen: false,
        mobileMenuOpen: false,
        darkMode: localStorage.getItem('darkMode') === 'true' || false,
        compactMode: localStorage.getItem('compactMode') === 'true' || false,
        
        // Initialization
        init() {
            this.setupEventListeners();
            this.setupKeyboardShortcuts();
            this.applyTheme();
            this.detectMobileDevice();
            console.log('Modern Layout initialized');
        },
        
        // Theme Management
        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('darkMode', this.darkMode);
            this.applyTheme();
            
            // Emit theme change event for other components
            window.dispatchEvent(new CustomEvent('theme-changed', { 
                detail: { darkMode: this.darkMode }
            }));
            
            this.showNotification(
                `${this.darkMode ? 'Dark' : 'Light'} mode enabled`,
                'success'
            );
        },
        
        applyTheme() {
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },
        
        // Sidebar Management
        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
            
            if (this.sidebarOpen) {
                this.closeMobileMenu();
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        },
        
        closeSidebar() {
            this.sidebarOpen = false;
            document.body.style.overflow = '';
        },
        
        // Mobile Menu Management
        toggleMobileMenu() {
            this.mobileMenuOpen = !this.mobileMenuOpen;
            
            if (this.mobileMenuOpen) {
                this.closeSidebar();
            }
        },
        
        closeMobileMenu() {
            this.mobileMenuOpen = false;
        },
        
        // Compact Mode
        toggleCompactMode() {
            this.compactMode = !this.compactMode;
            localStorage.setItem('compactMode', this.compactMode);
            
            // Apply compact classes
            const sidebar = document.querySelector('aside');
            if (sidebar) {
                if (this.compactMode) {
                    sidebar.classList.add('compact');
                } else {
                    sidebar.classList.remove('compact');
                }
            }
            
            this.showNotification(
                `Compact mode ${this.compactMode ? 'enabled' : 'disabled'}`,
                'info'
            );
        },
        
        // Event Listeners
        setupEventListeners() {
            // Handle outside clicks
            document.addEventListener('click', (e) => {
                if (!e.target.closest('aside') && !e.target.closest('.sidebar-toggle')) {
                    this.closeSidebar();
                }
                
                if (!e.target.closest('.mobile-menu') && !e.target.closest('.mobile-menu-toggle')) {
                    this.closeMobileMenu();
                }
            });
            
            // Handle escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.closeSidebar();
                    this.closeMobileMenu();
                }
            });
            
            // Handle resize
            let resizeTimeout;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    this.handleResize();
                }, 100);
            });
        },
        
        // Keyboard Shortcuts
        setupKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                // Ctrl/Cmd + B for sidebar toggle
                if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                    e.preventDefault();
                    this.toggleSidebar();
                }
                
                // Ctrl/Cmd + Shift + D for dark mode
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'D') {
                    e.preventDefault();
                    this.toggleDarkMode();
                }
                
                // Ctrl/Cmd + Shift + C for compact mode
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'C') {
                    e.preventDefault();
                    this.toggleCompactMode();
                }
            });
        },
        
        // Responsive Handling
        handleResize() {
            const isMobile = window.innerWidth < 1024; // lg breakpoint
            
            if (!isMobile) {
                this.closeSidebar();
                this.closeMobileMenu();
                document.body.style.overflow = '';
            }
            
            // Auto-compact on smaller screens
            if (window.innerWidth < 1280 && !localStorage.getItem('manualCompact')) {
                this.compactMode = true;
            }
        },
        
        // Device Detection
        detectMobileDevice() {
            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            
            if (isMobile) {
                document.body.classList.add('mobile-device');
                // Enable touch-friendly features
                this.setupTouchGestures();
            }
        },
        
        // Touch Gestures (for mobile)
        setupTouchGestures() {
            let startX = 0;
            let startY = 0;
            
            document.addEventListener('touchstart', (e) => {
                startX = e.touches[0].clientX;
                startY = e.touches[0].clientY;
            });
            
            document.addEventListener('touchend', (e) => {
                const endX = e.changedTouches[0].clientX;
                const endY = e.changedTouches[0].clientY;
                
                const diffX = startX - endX;
                const diffY = startY - endY;
                
                // Horizontal swipe detection
                if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
                    if (diffX > 0 && startX < 50) {
                        // Swipe from left edge to right - open sidebar
                        this.toggleSidebar();
                    } else if (diffX < 0 && this.sidebarOpen) {
                        // Swipe right to left - close sidebar
                        this.closeSidebar();
                    }
                }
            });
        },
        
        // Breadcrumb Navigation
        updateBreadcrumbs(breadcrumbs) {
            const breadcrumbContainer = document.querySelector('.breadcrumbs');
            if (breadcrumbContainer && breadcrumbs) {
                // Update breadcrumbs dynamically
                breadcrumbContainer.innerHTML = this.renderBreadcrumbs(breadcrumbs);
            }
        },
        
        renderBreadcrumbs(breadcrumbs) {
            return breadcrumbs.map((crumb, index) => {
                const isLast = index === breadcrumbs.length - 1;
                return `
                    <li class="inline-flex items-center">
                        ${index > 0 ? '<svg class="w-4 h-4 text-gray-400 mx-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 111.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>' : ''}
                        ${!isLast ? `<a href="${crumb.url}" class="text-sm font-medium text-gray-700 hover:text-indigo-600 transition-colors duration-200">${crumb.name}</a>` : `<span class="text-sm font-medium text-gray-500">${crumb.name}</span>`}
                    </li>
                `;
            }).join('');
        },
        
        // Flash Message Management
        showFlashMessage(message, type = 'info', duration = 5000) {
            const container = document.querySelector('.flash-messages');
            if (!container) return;
            
            const messageEl = this.createFlashMessage(message, type);
            container.appendChild(messageEl);
            
            // Animate in
            setTimeout(() => {
                messageEl.classList.add('show');
            }, 10);
            
            // Auto-remove
            setTimeout(() => {
                this.removeFlashMessage(messageEl);
            }, duration);
        },
        
        createFlashMessage(message, type) {
            const colors = {
                success: 'bg-green-50 border-green-200 text-green-800',
                error: 'bg-red-50 border-red-200 text-red-800',
                warning: 'bg-yellow-50 border-yellow-200 text-yellow-800',
                info: 'bg-blue-50 border-blue-200 text-blue-800'
            };
            
            const icons = {
                success: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>',
                error: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>',
                warning: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>',
                info: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
            };
            
            const messageEl = document.createElement('div');
            messageEl.className = `flash-message relative border rounded-lg p-4 mb-4 transition-all duration-300 transform translate-x-full opacity-0 ${colors[type]}`;
            messageEl.innerHTML = `
                <div class="flex items-center">
                    <div class="flex-shrink-0">${icons[type]}</div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">${message}</p>
                    </div>
                    <button type="button" class="ml-auto -mx-1.5 -my-1.5 rounded-lg p-1.5 hover:bg-black hover:bg-opacity-10 focus:outline-none" onclick="this.parentElement.parentElement.remove()">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;
            
            return messageEl;
        },
        
        removeFlashMessage(messageEl) {
            messageEl.classList.remove('show');
            messageEl.classList.add('translate-x-full', 'opacity-0');
            
            setTimeout(() => {
                if (messageEl.parentNode) {
                    messageEl.parentNode.removeChild(messageEl);
                }
            }, 300);
        },
        
        // Quick Notification System
        showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `
                fixed top-4 right-4 p-4 rounded-xl shadow-lg z-50 max-w-sm 
                transform translate-x-full transition-all duration-300 ease-in-out
                ${type === 'success' ? 'bg-green-500 text-white' : 
                  type === 'error' ? 'bg-red-500 text-white' : 
                  type === 'warning' ? 'bg-yellow-500 text-white' : 
                  'bg-blue-500 text-white'}
            `;
            
            notification.innerHTML = `
                <div class="flex items-center space-x-2">
                    <span class="text-sm font-medium">${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 10);
            
            // Auto remove
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        },
        
        // Performance Monitoring
        trackPerformance() {
            if ('performance' in window) {
                const navigation = performance.getEntriesByType('navigation')[0];
                if (navigation) {
                    console.log('Page Load Time:', navigation.loadEventEnd - navigation.loadEventStart, 'ms');
                }
            }
        }
    };
}

// Utility functions for layout components
export const layoutUtils = {
    // Smooth scroll to element
    scrollTo(element, options = {}) {
        const target = typeof element === 'string' ? document.querySelector(element) : element;
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
                ...options
            });
        }
    },
    
    // Debounce function
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    // Throttle function
    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },
    
    // Format date for display
    formatDate(date, options = {}) {
        return new Intl.DateTimeFormat('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            ...options
        }).format(new Date(date));
    },
    
    // Format currency
    formatCurrency(amount, currency = 'USD') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(amount);
    }
};