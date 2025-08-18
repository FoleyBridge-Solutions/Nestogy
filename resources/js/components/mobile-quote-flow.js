/**
 * Mobile-Optimized Quote Creation Flow
 * Provides streamlined, touch-friendly quote creation for mobile devices
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('mobileQuoteFlow', (config = {}) => ({
        // Configuration
        enableOfflineMode: config.enableOfflineMode !== false,
        compactMode: config.compactMode !== false,
        
        // Mobile detection
        isMobile: false,
        isTablet: false,
        touchDevice: false,
        orientation: 'portrait',
        
        // Flow state
        currentStep: 1,
        totalSteps: 3,
        stepHistory: [],
        canGoBack: false,
        canGoForward: false,
        
        // Mobile-specific UI state
        showBottomSheet: false,
        bottomSheetContent: '',
        swipeThreshold: 50,
        swipeStartX: 0,
        swipeStartY: 0,
        
        // Simplified form state for mobile
        mobileForm: {
            clientId: '',
            categoryId: '',
            templateId: '',
            quickMode: false,
            items: [],
            notes: '',
            discount: 0
        },
        
        // Mobile-optimized item selection
        itemSelection: {
            showCatalog: false,
            selectedTab: 'products',
            searchQuery: '',
            quickAdd: {
                name: '',
                price: 0,
                quantity: 1
            }
        },
        
        // Touch interactions
        gestureState: {
            touching: false,
            startTime: 0,
            startX: 0,
            startY: 0,
            currentX: 0,
            currentY: 0,
            deltaX: 0,
            deltaY: 0
        },
        
        // Mobile keyboard handling
        keyboardVisible: false,
        viewportHeight: window.innerHeight,
        
        // Mobile notifications
        mobileNotifications: [],
        
        // Initialize mobile flow
        init() {
            this.detectDevice();
            this.setupMobileListeners();
            this.configureForMobile();
            this.loadMobilePreferences();
            this.integrateMobileSidebar();
        },
        
        // Device detection
        detectDevice() {
            const userAgent = navigator.userAgent.toLowerCase();
            const width = window.innerWidth;
            
            this.isMobile = width <= 768;
            this.isTablet = width > 768 && width <= 1024;
            this.touchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
            
            // Update orientation
            this.updateOrientation();
            
            // Set CSS classes for styling
            document.body.classList.toggle('mobile-device', this.isMobile);
            document.body.classList.toggle('tablet-device', this.isTablet);
            document.body.classList.toggle('touch-device', this.touchDevice);
        },
        
        // Update orientation
        updateOrientation() {
            this.orientation = window.innerHeight > window.innerWidth ? 'portrait' : 'landscape';
            document.body.classList.toggle('orientation-portrait', this.orientation === 'portrait');
            document.body.classList.toggle('orientation-landscape', this.orientation === 'landscape');
        },
        
        // Setup mobile-specific event listeners
        setupMobileListeners() {
            // Orientation change
            window.addEventListener('orientationchange', () => {
                setTimeout(() => {
                    this.detectDevice();
                    this.updateOrientation();
                    this.adjustForOrientation();
                }, 100);
            });
            
            // Viewport resize (keyboard detection)
            window.addEventListener('resize', () => {
                this.handleViewportChange();
            });
            
            // Touch gesture handling
            document.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: false });
            document.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
            document.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: false });
            
            // Prevent zoom on double tap for form inputs
            let lastTouchEnd = 0;
            document.addEventListener('touchend', (e) => {
                const now = new Date().getTime();
                if (now - lastTouchEnd <= 300) {
                    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                        e.preventDefault();
                    }
                }
                lastTouchEnd = now;
            }, false);
            
            // Swipe navigation
            this.setupSwipeNavigation();
        },
        
        // Configure interface for mobile
        configureForMobile() {
            if (this.isMobile) {
                // Compact steps for mobile
                this.totalSteps = 3;
                
                // Enable quick mode by default on mobile
                this.mobileForm.quickMode = true;
                
                // Optimize viewport
                this.optimizeViewport();
            }
        },
        
        // Optimize viewport for mobile
        optimizeViewport() {
            // Add viewport meta tag if not present
            let viewport = document.querySelector('meta[name="viewport"]');
            if (!viewport) {
                viewport = document.createElement('meta');
                viewport.name = 'viewport';
                document.head.appendChild(viewport);
            }
            viewport.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover';
            
            // Disable zoom on inputs
            const inputs = document.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('focus', () => {
                    viewport.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no';
                });
                input.addEventListener('blur', () => {
                    viewport.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover';
                });
            });
        },
        
        // Handle viewport changes (keyboard detection)
        handleViewportChange() {
            const currentHeight = window.innerHeight;
            const heightDifference = this.viewportHeight - currentHeight;
            
            // Detect virtual keyboard
            this.keyboardVisible = heightDifference > 150;
            
            if (this.keyboardVisible) {
                document.body.classList.add('keyboard-visible');
                this.adjustForKeyboard();
            } else {
                document.body.classList.remove('keyboard-visible');
            }
            
            this.viewportHeight = currentHeight;
        },
        
        // Adjust layout for keyboard
        adjustForKeyboard() {
            if (!this.isMobile) return;
            
            // Scroll focused input into view
            const focused = document.activeElement;
            if (focused && (focused.tagName === 'INPUT' || focused.tagName === 'TEXTAREA')) {
                setTimeout(() => {
                    focused.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 100);
            }
        },
        
        // Adjust for orientation change
        adjustForOrientation() {
            if (this.orientation === 'landscape' && this.isMobile) {
                // Compact UI for landscape mode
                document.body.classList.add('landscape-compact');
            } else {
                document.body.classList.remove('landscape-compact');
            }
        },
        
        // Touch gesture handlers
        handleTouchStart(e) {
            if (!this.isMobile || e.touches.length > 1) return;
            
            const touch = e.touches[0];
            this.gestureState = {
                touching: true,
                startTime: Date.now(),
                startX: touch.clientX,
                startY: touch.clientY,
                currentX: touch.clientX,
                currentY: touch.clientY,
                deltaX: 0,
                deltaY: 0
            };
        },
        
        handleTouchMove(e) {
            if (!this.gestureState.touching) return;
            
            const touch = e.touches[0];
            this.gestureState.currentX = touch.clientX;
            this.gestureState.currentY = touch.clientY;
            this.gestureState.deltaX = touch.clientX - this.gestureState.startX;
            this.gestureState.deltaY = touch.clientY - this.gestureState.startY;
        },
        
        handleTouchEnd(e) {
            if (!this.gestureState.touching) return;
            
            const duration = Date.now() - this.gestureState.startTime;
            const distance = Math.abs(this.gestureState.deltaX);
            
            // Detect swipe gesture
            if (duration < 300 && distance > this.swipeThreshold) {
                this.handleSwipeGesture();
            }
            
            this.gestureState.touching = false;
        },
        
        // Handle swipe gestures for navigation
        handleSwipeGesture() {
            const { deltaX, deltaY, startX } = this.gestureState;
            
            // Ignore swipes from left edge (reserved for sidebar)
            if (startX < 20) return;
            
            // Horizontal swipe for step navigation
            if (Math.abs(deltaX) > Math.abs(deltaY)) {
                if (deltaX > 0 && this.canGoBack) {
                    this.previousStep();
                } else if (deltaX < 0 && this.canGoForward) {
                    this.nextStep();
                }
            }
        },
        
        // Setup swipe navigation
        setupSwipeNavigation() {
            let startX = 0;
            let startY = 0;
            
            document.addEventListener('touchstart', (e) => {
                startX = e.touches[0].clientX;
                startY = e.touches[0].clientY;
            }, { passive: true });
            
            document.addEventListener('touchend', (e) => {
                if (!startX || !startY) return;
                
                // Skip if swipe started from sidebar edge
                if (startX < 20) {
                    startX = 0;
                    startY = 0;
                    return;
                }
                
                const endX = e.changedTouches[0].clientX;
                const endY = e.changedTouches[0].clientY;
                
                const deltaX = endX - startX;
                const deltaY = endY - startY;
                
                // Only handle horizontal swipes for step navigation
                if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 50) {
                    if (deltaX > 0 && this.canGoBack) {
                        this.previousStep();
                    } else if (deltaX < 0 && this.canGoForward) {
                        this.nextStep();
                    }
                }
                
                startX = 0;
                startY = 0;
            }, { passive: true });
        },
        
        // Step navigation
        nextStep() {
            if (this.currentStep < this.totalSteps && this.validateCurrentStep()) {
                this.stepHistory.push(this.currentStep);
                this.currentStep++;
                this.updateNavigationState();
                this.trackStepProgress();
            }
        },
        
        previousStep() {
            if (this.stepHistory.length > 0) {
                this.currentStep = this.stepHistory.pop();
                this.updateNavigationState();
            }
        },
        
        goToStep(step) {
            if (step >= 1 && step <= this.totalSteps) {
                this.stepHistory.push(this.currentStep);
                this.currentStep = step;
                this.updateNavigationState();
            }
        },
        
        updateNavigationState() {
            this.canGoBack = this.stepHistory.length > 0;
            this.canGoForward = this.currentStep < this.totalSteps && this.validateCurrentStep();
        },
        
        // Step validation
        validateCurrentStep() {
            switch (this.currentStep) {
                case 1:
                    return this.mobileForm.clientId && this.mobileForm.categoryId;
                case 2:
                    return this.mobileForm.items.length > 0;
                case 3:
                    return true; // Review step
                default:
                    return false;
            }
        },
        
        // Mobile-optimized item management
        showItemSelector() {
            this.itemSelection.showCatalog = true;
            this.showBottomSheet = true;
            this.bottomSheetContent = 'item-selector';
        },
        
        hideItemSelector() {
            this.itemSelection.showCatalog = false;
            this.showBottomSheet = false;
        },
        
        quickAddItem() {
            const { name, price, quantity } = this.itemSelection.quickAdd;
            
            if (!name || !price) {
                this.showMobileNotification('Please enter item name and price', 'warning');
                return;
            }
            
            const item = {
                temp_id: 'temp_' + Date.now(),
                name: name,
                unit_price: parseFloat(price),
                quantity: parseInt(quantity),
                subtotal: parseFloat(price) * parseInt(quantity)
            };
            
            this.mobileForm.items.push(item);
            
            // Reset quick add form
            this.itemSelection.quickAdd = { name: '', price: 0, quantity: 1 };
            
            this.showMobileNotification('Item added successfully', 'success');
        },
        
        removeItem(itemId) {
            this.mobileForm.items = this.mobileForm.items.filter(item => 
                (item.id || item.temp_id) !== itemId
            );
        },
        
        editItem(itemId) {
            const item = this.mobileForm.items.find(item => 
                (item.id || item.temp_id) === itemId
            );
            
            if (item) {
                this.itemSelection.quickAdd = {
                    name: item.name,
                    price: item.unit_price,
                    quantity: item.quantity
                };
                
                this.removeItem(itemId);
                this.showBottomSheet = true;
                this.bottomSheetContent = 'quick-add';
            }
        },
        
        // Mobile notifications
        showMobileNotification(message, type = 'info') {
            const notification = {
                id: Date.now(),
                message,
                type,
                timestamp: new Date()
            };
            
            this.mobileNotifications.push(notification);
            
            // Auto-remove after 3 seconds
            setTimeout(() => {
                this.removeMobileNotification(notification.id);
            }, 3000);
        },
        
        removeMobileNotification(id) {
            this.mobileNotifications = this.mobileNotifications.filter(n => n.id !== id);
        },
        
        // Bottom sheet management
        toggleBottomSheet(content = '') {
            this.showBottomSheet = !this.showBottomSheet;
            this.bottomSheetContent = content;
        },
        
        closeBottomSheet() {
            this.showBottomSheet = false;
            this.bottomSheetContent = '';
        },
        
        // Mobile-optimized calculations
        calculateMobileTotal() {
            return this.mobileForm.items.reduce((total, item) => {
                return total + (item.unit_price * item.quantity);
            }, 0) - this.mobileForm.discount;
        },
        
        // Quick actions for mobile
        duplicateLastQuote() {
            // Implementation for duplicating the last quote
            this.showMobileNotification('Loading last quote...', 'info');
        },
        
        useQuickTemplate() {
            // Implementation for quick template selection
            this.showBottomSheet = true;
            this.bottomSheetContent = 'quick-templates';
        },
        
        // Mobile form submission
        async submitMobileQuote() {
            if (!this.validateMobileForm()) {
                this.showMobileNotification('Please check all required fields', 'error');
                return;
            }
            
            try {
                // Show loading state
                this.showMobileNotification('Creating quote...', 'info');
                
                const quoteData = {
                    client_id: this.mobileForm.clientId,
                    category_id: this.mobileForm.categoryId,
                    items: this.mobileForm.items,
                    note: this.mobileForm.notes,
                    discount_amount: this.mobileForm.discount,
                    mobile_created: true
                };
                
                const response = await fetch('/api/quotes', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(quoteData)
                });
                
                if (response.ok) {
                    const result = await response.json();
                    this.showMobileNotification('Quote created successfully!', 'success');
                    
                    // Redirect or reset form
                    setTimeout(() => {
                        window.location.href = `/quotes/${result.data.id}`;
                    }, 1000);
                } else {
                    throw new Error('Failed to create quote');
                }
                
            } catch (error) {
                console.error('Mobile quote submission error:', error);
                this.showMobileNotification('Failed to create quote. Please try again.', 'error');
            }
        },
        
        // Validate mobile form
        validateMobileForm() {
            return this.mobileForm.clientId && 
                   this.mobileForm.categoryId && 
                   this.mobileForm.items.length > 0;
        },
        
        // Mobile preferences
        loadMobilePreferences() {
            try {
                const prefs = localStorage.getItem('mobile-quote-preferences');
                if (prefs) {
                    const preferences = JSON.parse(prefs);
                    this.mobileForm.quickMode = preferences.quickMode !== false;
                }
            } catch (error) {
                console.warn('Failed to load mobile preferences:', error);
            }
        },
        
        saveMobilePreferences() {
            try {
                const preferences = {
                    quickMode: this.mobileForm.quickMode
                };
                localStorage.setItem('mobile-quote-preferences', JSON.stringify(preferences));
            } catch (error) {
                console.warn('Failed to save mobile preferences:', error);
            }
        },

        // Integrate with main sidebar system
        integrateMobileSidebar() {
            if (!this.isMobile) return;

            // Listen for main sidebar events
            window.addEventListener('toggle-mobile-sidebar', () => {
                this.handleMainSidebarToggle();
            });

            // Add mobile-specific sidebar button if missing
            this.ensureMobileSidebarButton();
            
            // Override conflicting touch gestures for sidebar
            this.setupMobileSidebarGestures();
        },

        // Handle main sidebar toggle from mobile
        handleMainSidebarToggle() {
            // Dispatch to main layout component
            window.dispatchEvent(new CustomEvent('toggle-mobile-sidebar'));
        },

        // Ensure mobile sidebar button exists
        ensureMobileSidebarButton() {
            if (document.querySelector('.mobile-sidebar-toggle')) return;

            const button = document.createElement('button');
            button.className = 'mobile-sidebar-toggle fixed top-4 left-4 z-50 lg:hidden bg-white/90 backdrop-blur-sm p-2 rounded-lg shadow-lg border border-gray-200';
            button.innerHTML = `
                <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            `;
            button.addEventListener('click', () => {
                this.handleMainSidebarToggle();
            });

            document.body.appendChild(button);
        },

        // Setup mobile-specific sidebar gestures
        setupMobileSidebarGestures() {
            let sidebarStartX = 0;
            let sidebarStartY = 0;
            let sidebarIsDragging = false;

            // Enhanced touch handling for sidebar
            document.addEventListener('touchstart', (e) => {
                sidebarStartX = e.touches[0].clientX;
                sidebarStartY = e.touches[0].clientY;
                
                // Detect edge swipe for sidebar
                if (sidebarStartX < 20) {
                    sidebarIsDragging = true;
                }
            }, { passive: true });

            document.addEventListener('touchmove', (e) => {
                if (!sidebarIsDragging) return;
                
                const currentX = e.touches[0].clientX;
                const deltaX = currentX - sidebarStartX;
                
                // Provide visual feedback for sidebar swipe
                if (deltaX > 10) {
                    document.body.classList.add('sidebar-swipe-active');
                }
            }, { passive: true });

            document.addEventListener('touchend', (e) => {
                if (!sidebarIsDragging) return;
                
                const endX = e.changedTouches[0].clientX;
                const deltaX = endX - sidebarStartX;
                
                document.body.classList.remove('sidebar-swipe-active');
                
                // Open sidebar if swipe is significant
                if (deltaX > 50) {
                    this.handleMainSidebarToggle();
                }
                
                sidebarIsDragging = false;
            }, { passive: true });
        },
        
        // Track mobile usage
        trackStepProgress() {
            // Analytics tracking for mobile flow
            if (typeof gtag !== 'undefined') {
                gtag('event', 'mobile_quote_step', {
                    step: this.currentStep,
                    device_type: this.isMobile ? 'mobile' : 'tablet'
                });
            }
        },
        
        // Computed properties
        get stepProgress() {
            return (this.currentStep / this.totalSteps) * 100;
        },
        
        get currentStepTitle() {
            const titles = {
                1: 'Quote Details',
                2: 'Add Items',
                3: 'Review & Submit'
            };
            return titles[this.currentStep] || '';
        },
        
        get totalItems() {
            return this.mobileForm.items.length;
        },
        
        get formattedTotal() {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(this.calculateMobileTotal());
        },
        
        get isLastStep() {
            return this.currentStep === this.totalSteps;
        },
        
        get canSubmit() {
            return this.isLastStep && this.validateMobileForm();
        }
    }));
    
    // Mobile-specific Alpine directives
    Alpine.directive('mobile-tap', (el, { expression }, { evaluate }) => {
        if ('ontouchstart' in window) {
            el.addEventListener('touchstart', () => {
                el.classList.add('tap-highlight');
            });
            
            el.addEventListener('touchend', () => {
                setTimeout(() => {
                    el.classList.remove('tap-highlight');
                }, 150);
                
                evaluate(expression);
            });
        } else {
            el.addEventListener('click', () => evaluate(expression));
        }
    });
    
    // Mobile-optimized number input
    Alpine.directive('mobile-number', (el) => {
        if ('ontouchstart' in window) {
            el.setAttribute('inputmode', 'decimal');
            el.setAttribute('pattern', '[0-9]*');
        }
    });
});