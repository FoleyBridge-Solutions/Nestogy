/**
 * Modern Layout Components
 * Optimized for Vite and Tailwind CSS
 */

export function modernLayout() {
    return {
        // State Management
        sidebarOpen: false,
        mobileMenuOpen: false,
        darkMode: false, // Will be set from server data
        compactMode: localStorage.getItem('compactMode') === 'true' || false,
        shortcuts: [],
        
        // Initialization
        init() {
            // Get theme from server-side data instead of localStorage
            if (window.CURRENT_USER && window.CURRENT_USER.theme) {
                const userTheme = window.CURRENT_USER.theme;
                if (userTheme === 'dark') {
                    this.darkMode = true;
                } else if (userTheme === 'light') {
                    this.darkMode = false;
                } else if (userTheme === 'auto') {
                    this.darkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                }
            } else {
                // Fallback to checking if dark class is already applied
                this.darkMode = document.documentElement.classList.contains('dark');
            }
            
            this.setupEventListeners();
            this.setupKeyboardShortcuts();
            this.setupThemeEventListeners();
            // Don't call applyTheme() here - theme is already applied by server script
            this.detectMobileDevice();
            console.log('Layout initialized with theme:', this.darkMode ? 'dark' : 'light');
        },
        
        // Setup theme event listeners for Alpine communication
        setupThemeEventListeners() {
            // Prevent duplicate event listeners
            if (this.themeToggleHandler) {
                document.removeEventListener('toggle-theme', this.themeToggleHandler);
            }
            
            // Store reference for cleanup
            this.themeToggleHandler = () => {
                this.toggleDarkMode();
            };
            
            // Listen for theme toggle events from other components (like dashboard)
            document.addEventListener('toggle-theme', this.themeToggleHandler);
        },
        
        // Theme Management
        toggleDarkMode() {
            // Determine what the new theme should be based on current state
            const currentTheme = window.CURRENT_USER?.theme || 'light';
            let newTheme;
            
            if (currentTheme === 'auto') {
                // If auto, switch to the opposite of what auto is currently showing
                const systemPrefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                newTheme = systemPrefersDark ? 'light' : 'dark';
            } else {
                // If light or dark, toggle to the opposite
                newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            }
            
            // Apply the new theme
            this.darkMode = newTheme === 'dark';
            
            // Update the global user object immediately (before web request)
            if (window.CURRENT_USER) {
                window.CURRENT_USER.theme = newTheme;
            }
            
            // Save to localStorage as backup
            localStorage.setItem('darkMode', this.darkMode);
            
            // Apply theme immediately
            this.applyTheme();
            
            // Save to server if user is logged in
            if (window.CURRENT_USER && window.CURRENT_USER.id) {
                fetch('/users/settings', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ theme: newTheme })
                }).catch(error => {
                    console.warn('Failed to save theme preference:', error);
                    // Revert the global user object if save failed
                    if (window.CURRENT_USER) {
                        window.CURRENT_USER.theme = currentTheme;
                    }
                });
            }
            
            // Emit theme change event for other components via both window and Alpine events
            window.dispatchEvent(new CustomEvent('theme-changed', { 
                detail: { darkMode: this.darkMode, theme: newTheme }
            }));
            
            // Also dispatch Alpine event for dashboard and other Alpine components
            document.dispatchEvent(new CustomEvent('theme-changed', { 
                detail: { darkMode: this.darkMode, theme: newTheme },
                bubbles: true
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
            let layoutResizeTimeout;
            window.addEventListener('resize', () => {
                clearTimeout(layoutResizeTimeout);
                layoutResizeTimeout = setTimeout(() => {
                    this.handleResize();
                }, 100);
            });
        },
        
        // Keyboard Shortcuts
        setupKeyboardShortcuts() {
            // Load shortcuts from server
            this.loadShortcuts();
            
            document.addEventListener('keydown', (e) => {
                // Skip if in input fields (except for safe shortcuts)
                const isInInput = ['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName);
                const isEditableDiv = e.target.contentEditable === 'true';
                
                if (isInInput || isEditableDiv) {
                    if (!this.isInputSafeShortcut(e)) return;
                }
                
                // Handle keyboard shortcuts dynamically
                this.handleKeyboardShortcut(e);
            });
        },

        async loadShortcuts() {
            try {
                const response = await fetch('/reports/shortcuts/active', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                
                const data = await response.json();
                this.shortcuts = data.shortcuts || [];
                console.log('Loaded shortcuts:', this.shortcuts);
            } catch (error) {
                console.error('Failed to load shortcuts:', error);
                this.shortcuts = [];
            }
        },
        
        isInputSafeShortcut(e) {
            // Allow certain shortcuts even in input fields
            const isCtrlOrCmd = e.ctrlKey || e.metaKey;
            
            // Command palette and help are always safe
            if (isCtrlOrCmd && e.key === '/') return true;
            if (isCtrlOrCmd && e.shiftKey && e.key === 'H') return true;
            
            return false;
        },
        
        handleKeyboardShortcut(e) {
            if (!this.shortcuts) return;
            
            // Build current key combination
            const keyCombo = this.buildKeyCombo(e);
            
            // Find matching shortcut
            const matchingShortcut = this.shortcuts.find(shortcut => {
                return shortcut.keyString === keyCombo;
            });
            
            if (matchingShortcut) {
                e.preventDefault();
                this.executeShortcutCommand(matchingShortcut);
            }
        },
        
        buildKeyCombo(e) {
            const keys = [];
            
            if (e.ctrlKey || e.metaKey) keys.push('Ctrl');
            if (e.altKey) keys.push('Alt');
            if (e.shiftKey) keys.push('Shift');
            
            // Add the main key
            let mainKey = e.key;
            
            // Handle special cases
            if (mainKey === ' ') mainKey = 'Space';
            else if (mainKey.length === 1) mainKey = mainKey.toUpperCase();
            
            keys.push(mainKey);
            
            return keys.join('+');
        },
        
        async executeShortcutCommand(shortcut) {
            // Handle system commands locally for better performance
            switch (shortcut.command) {
                case 'toggle_sidebar':
                    this.toggleSidebar();
                    return;
                case 'toggle_dark_mode':
                    this.toggleDarkMode();
                    return;
                case 'open_command_palette':
                    // This is handled by the command palette component
                    return;
            }
            
            // For all other commands, execute via server
            this.executeCommand(shortcut.command);
        },
        
        async executeCommand(command) {
            try {
                const response = await fetch('/api/navigation/command', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ command: command }),
                });
                
                const data = await response.json();
                
                if (data.action === 'navigate' && data.url) {
                    window.location.href = data.url;
                } else if (data.action === 'help') {
                    this.showHelpMessage(data.message);
                } else if (data.action === 'error') {
                    this.showErrorMessage(data.message);
                }
            } catch (error) {
                console.error('Command execution error:', error);
                this.showErrorMessage('Failed to execute command');
            }
        },
        
        
        showHelpMessage(message) {
            this.showHelpModal(message);
        },
        
        showHelpModal(message) {
            // Create help modal
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50';
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
            
            const modalContent = document.createElement('div');
            modalContent.className = 'bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-96 overflow-y-auto';
            
            // Convert message to HTML with proper formatting
            const formattedMessage = message
                .replace(/\n\n/g, '</p><p class="mb-3">')
                .replace(/\n/g, '<br>')
                .replace(/^/, '<p class="mb-3">')
                .replace(/$/, '</p>');
            
            modalContent.innerHTML = `
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Keyboard Shortcuts & Commands</h3>
                        <button class="text-gray-400 hover:text-gray-600 transition-colors" onclick="this.closest('[class*=fixed]').remove()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="text-sm text-gray-700 font-mono whitespace-pre-line leading-relaxed">
                        ${formattedMessage}
                    </div>
                    <div class="mt-6 flex justify-end">
                        <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors" onclick="this.closest('[class*=fixed]').remove()">
                            Got it!
                        </button>
                    </div>
                </div>
            `;
            
            modal.appendChild(modalContent);
            document.body.appendChild(modal);
            
            // Handle escape key
            const handleEscape = (e) => {
                if (e.key === 'Escape') {
                    modal.remove();
                    document.removeEventListener('keydown', handleEscape);
                }
            };
            document.addEventListener('keydown', handleEscape);
            
            // Auto-focus close button for accessibility
            setTimeout(() => {
                const closeButton = modal.querySelector('button[onclick*="remove"]');
                if (closeButton) closeButton.focus();
            }, 100);
        },
        
        showErrorMessage(message) {
            this.showToast('Error: ' + message, 3000, 'error');
        },
        
        showToast(message, duration = 3000, type = 'info') {
            // Create toast notification
            const toast = document.createElement('div');
            toast.className = `fixed top-20 right-4 z-50 px-4 py-2 rounded-lg shadow-lg text-white max-w-sm ${
                type === 'error' ? 'bg-red-600' : 'bg-gray-800'
            }`;
            toast.textContent = message;
            
            document.body.appendChild(toast);
            
            // Animate in
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                toast.style.transition = 'all 0.3s ease';
                toast.style.opacity = '1';
                toast.style.transform = 'translateX(0)';
            }, 10);
            
            // Remove after duration
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, duration);
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
            let startTime = 0;
            let isEdgeSwipe = false;
            
            document.addEventListener('touchstart', (e) => {
                startX = e.touches[0].clientX;
                startY = e.touches[0].clientY;
                startTime = Date.now();
                isEdgeSwipe = startX < 20; // Detect edge swipe
            }, { passive: true });
            
            document.addEventListener('touchmove', (e) => {
                if (!isEdgeSwipe) return;
                
                const currentX = e.touches[0].clientX;
                const deltaX = currentX - startX;
                
                // Provide visual feedback for sidebar swipe
                if (deltaX > 10) {
                    document.body.classList.add('sidebar-swipe-active');
                }
            }, { passive: true });
            
            document.addEventListener('touchend', (e) => {
                const endX = e.changedTouches[0].clientX;
                const endY = e.changedTouches[0].clientY;
                const endTime = Date.now();
                
                const deltaX = endX - startX;
                const deltaY = endY - startY;
                const deltaTime = endTime - startTime;
                
                document.body.classList.remove('sidebar-swipe-active');
                
                // Only handle edge swipes for sidebar
                if (isEdgeSwipe) {
                    // Horizontal swipe detection for sidebar
                    if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 50 && deltaTime < 500) {
                        if (deltaX > 0) {
                            // Swipe right from edge - open sidebar
                            this.toggleSidebar();
                        }
                    }
                } else if (this.sidebarOpen) {
                    // Swipe anywhere else to close sidebar when open
                    if (Math.abs(deltaX) > Math.abs(deltaY) && deltaX < -50 && deltaTime < 500) {
                        this.closeSidebar();
                    }
                }
                
                isEdgeSwipe = false;
            }, { passive: true });
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