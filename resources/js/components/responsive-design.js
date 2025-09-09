/**
 * Responsive Design Component
 * Handles adaptive layouts and responsive behavior for the quote system
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('responsiveDesign', (config = {}) => ({
        // Configuration
        breakpoints: config.breakpoints || {
            xs: 480,
            sm: 768,
            md: 1024,
            lg: 1280,
            xl: 1536
        },
        debounceDelay: config.debounceDelay || 150,
        
        // Current viewport state
        viewport: {
            width: window.innerWidth,
            height: window.innerHeight,
            breakpoint: 'lg',
            orientation: 'landscape',
            ratio: 1
        },
        
        // Device capabilities
        device: {
            isMobile: false,
            isTablet: false,
            isDesktop: true,
            isTouch: false,
            hasHover: true,
            pixelRatio: 1
        },
        
        // Layout state
        layout: {
            sidebar: {
                visible: true,
                collapsed: false,
                overlay: false
            },
            navbar: {
                height: 64,
                collapsed: false
            },
            content: {
                padding: 'normal',
                columns: 'auto'
            }
        },
        
        // Responsive states
        adaptiveStates: {
            compactMode: false,
            singleColumn: false,
            reducedSpacing: false,
            largerTouchTargets: false,
            simplifiedUI: false
        },
        
        // Performance tracking
        resizeDebouncer: null,
        observedElements: new Set(),
        
        // Initialize responsive design
        init() {
            this.detectDevice();
            this.updateViewport();
            this.setupResponsiveListeners();
            this.initializeAdaptiveElements();
            this.setupIntersectionObserver();
            this.applyInitialResponsiveStates();
        },
        
        // Device detection
        detectDevice() {
            const userAgent = navigator.userAgent.toLowerCase();
            const width = window.innerWidth;
            
            // Device type detection
            this.device.isMobile = width <= this.breakpoints.sm;
            this.device.isTablet = width > this.breakpoints.sm && width <= this.breakpoints.md;
            this.device.isDesktop = width > this.breakpoints.md;
            
            // Touch capability
            this.device.isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
            this.device.hasHover = window.matchMedia('(hover: hover)').matches;
            this.device.pixelRatio = window.devicePixelRatio || 1;
            
            // Update body classes
            this.updateDeviceClasses();
        },
        
        // Update device classes on body
        updateDeviceClasses() {
            const body = document.body;
            
            body.classList.toggle('mobile-device', this.device.isMobile);
            body.classList.toggle('tablet-device', this.device.isTablet);
            body.classList.toggle('desktop-device', this.device.isDesktop);
            body.classList.toggle('touch-device', this.device.isTouch);
            body.classList.toggle('hover-device', this.device.hasHover);
            body.classList.toggle('high-dpi', this.device.pixelRatio > 1);
        },
        
        // Update viewport information
        updateViewport() {
            this.viewport.width = window.innerWidth;
            this.viewport.height = window.innerHeight;
            this.viewport.orientation = this.viewport.width > this.viewport.height ? 'landscape' : 'portrait';
            this.viewport.ratio = this.viewport.width / this.viewport.height;
            
            // Determine current breakpoint
            this.viewport.breakpoint = this.getCurrentBreakpoint();
            
            // Update CSS custom properties
            this.updateCSSProperties();
            
            // Update body classes
            this.updateViewportClasses();
        },
        
        // Get current breakpoint
        getCurrentBreakpoint() {
            const width = this.viewport.width;
            
            if (width < this.breakpoints.xs) return 'xs';
            if (width < this.breakpoints.sm) return 'sm';
            if (width < this.breakpoints.md) return 'md';
            if (width < this.breakpoints.lg) return 'lg';
            return 'xl';
        },
        
        // Update CSS custom properties
        updateCSSProperties() {
            const root = document.documentElement;
            
            root.style.setProperty('--viewport-width', `${this.viewport.width}px`);
            root.style.setProperty('--viewport-height', `${this.viewport.height}px`);
            root.style.setProperty('--viewport-ratio', this.viewport.ratio);
            root.style.setProperty('--device-pixel-ratio', this.device.pixelRatio);
        },
        
        // Update viewport classes
        updateViewportClasses() {
            const body = document.body;
            
            // Remove old breakpoint classes
            Object.keys(this.breakpoints).forEach(bp => {
                body.classList.remove(`bp-${bp}`);
            });
            
            // Add current breakpoint class
            body.classList.add(`bp-${this.viewport.breakpoint}`);
            
            // Add orientation class
            body.classList.toggle('orientation-portrait', this.viewport.orientation === 'portrait');
            body.classList.toggle('orientation-landscape', this.viewport.orientation === 'landscape');
        },
        
        // Setup responsive event listeners
        setupResponsiveListeners() {
            // Resize listener with debouncing
            window.addEventListener('resize', () => {
                if (this.resizeDebouncer) {
                    clearTimeout(this.resizeDebouncer);
                }
                
                this.resizeDebouncer = setTimeout(() => {
                    this.handleViewportChange();
                }, this.debounceDelay);
            });
            
            // Orientation change
            window.addEventListener('orientationchange', () => {
                setTimeout(() => {
                    this.handleViewportChange();
                }, 100);
            });
            
            // Media query listeners
            this.setupMediaQueryListeners();
        },
        
        // Setup media query listeners
        setupMediaQueryListeners() {
            // Hover capability
            const hoverQuery = window.matchMedia('(hover: hover)');
            hoverQuery.addEventListener('change', (e) => {
                this.device.hasHover = e.matches;
                this.updateDeviceClasses();
            });
            
            // Pointer type
            const pointerQuery = window.matchMedia('(pointer: coarse)');
            pointerQuery.addEventListener('change', (e) => {
                this.adaptiveStates.largerTouchTargets = e.matches;
                this.applyAdaptiveStates();
            });
            
            // Reduced motion preference
            const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
            motionQuery.addEventListener('change', (e) => {
                document.body.classList.toggle('reduced-motion', e.matches);
            });
            
            // Dark mode preference
            const darkQuery = window.matchMedia('(prefers-color-scheme: dark)');
            darkQuery.addEventListener('change', (e) => {
                this.$dispatch('color-scheme-change', { isDark: e.matches });
            });
        },
        
        // Handle viewport changes
        handleViewportChange() {
            const wasBreakpoint = this.viewport.breakpoint;
            const wasOrientation = this.viewport.orientation;
            
            this.detectDevice();
            this.updateViewport();
            this.updateAdaptiveStates();
            this.updateLayoutConfiguration();
            
            // Dispatch events for breakpoint changes
            if (wasBreakpoint !== this.viewport.breakpoint) {
                this.$dispatch('breakpoint-change', {
                    from: wasBreakpoint,
                    to: this.viewport.breakpoint,
                    viewport: this.viewport
                });
            }
            
            // Dispatch events for orientation changes
            if (wasOrientation !== this.viewport.orientation) {
                this.$dispatch('orientation-change', {
                    from: wasOrientation,
                    to: this.viewport.orientation,
                    viewport: this.viewport
                });
            }
        },
        
        // Update adaptive states based on viewport
        updateAdaptiveStates() {
            this.adaptiveStates.compactMode = this.device.isMobile;
            this.adaptiveStates.singleColumn = this.viewport.width < this.breakpoints.md;
            this.adaptiveStates.reducedSpacing = this.device.isMobile;
            this.adaptiveStates.largerTouchTargets = this.device.isTouch;
            this.adaptiveStates.simplifiedUI = this.device.isMobile;
            
            this.applyAdaptiveStates();
        },
        
        // Apply adaptive states to DOM
        applyAdaptiveStates() {
            const body = document.body;
            
            body.classList.toggle('compact-mode', this.adaptiveStates.compactMode);
            body.classList.toggle('single-column', this.adaptiveStates.singleColumn);
            body.classList.toggle('reduced-spacing', this.adaptiveStates.reducedSpacing);
            body.classList.toggle('large-touch-targets', this.adaptiveStates.largerTouchTargets);
            body.classList.toggle('simplified-ui', this.adaptiveStates.simplifiedUI);
        },
        
        // Update layout configuration
        updateLayoutConfiguration() {
            // Sidebar behavior
            if (this.device.isMobile) {
                this.layout.sidebar.visible = false;
                this.layout.sidebar.overlay = true;
            } else if (this.device.isTablet) {
                this.layout.sidebar.collapsed = true;
                this.layout.sidebar.overlay = false;
            } else {
                this.layout.sidebar.visible = true;
                this.layout.sidebar.collapsed = false;
                this.layout.sidebar.overlay = false;
            }
            
            // Content layout
            this.layout.content.columns = this.adaptiveStates.singleColumn ? '1' : 'auto';
            this.layout.content.padding = this.adaptiveStates.reducedSpacing ? 'compact' : 'normal';
            
            // Apply layout classes
            this.applyLayoutClasses();
        },
        
        // Apply layout classes
        applyLayoutClasses() {
            const body = document.body;
            
            body.classList.toggle('sidebar-visible', this.layout.sidebar.visible);
            body.classList.toggle('sidebar-collapsed', this.layout.sidebar.collapsed);
            body.classList.toggle('sidebar-overlay', this.layout.sidebar.overlay);
            body.classList.toggle('compact-padding', this.layout.content.padding === 'compact');
        },
        
        // Initialize adaptive elements
        initializeAdaptiveElements() {
            // Make tables responsive
            this.makeTablesResponsive();
            
            // Enhance form inputs for mobile
            this.enhanceFormInputs();
            
            // Setup adaptive images
            this.setupAdaptiveImages();
            
            // Configure responsive typography
            this.configureResponsiveTypography();
        },
        
        // Make tables responsive
        makeTablesResponsive() {
            const tables = document.querySelectorAll('table:not(.responsive-table)');
            
            tables.forEach(table => {
                table.classList.add('responsive-table');
                
                // Wrap table in responsive container
                if (!table.parentElement.classList.contains('table-responsive')) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'table-responsive';
                    table.parentNode.insertBefore(wrapper, table);
                    wrapper.appendChild(table);
                }
                
                // Add mobile-friendly table behavior
                this.enhanceTableForMobile(table);
            });
        },
        
        // Enhance table for mobile viewing
        enhanceTableForMobile(table) {
            if (!this.device.isMobile) return;
            
            const headers = Array.from(table.querySelectorAll('th')).map(th => th.textContent);
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                cells.forEach((cell, index) => {
                    if (headers[index]) {
                        cell.setAttribute('data-label', headers[index]);
                    }
                });
            });
        },
        
        // Enhance form inputs for responsive design
        enhanceFormInputs() {
            const inputs = document.querySelectorAll('input, select, textarea');
            
            inputs.forEach(input => {
                // Prevent zoom on iOS
                if (this.device.isMobile && input.type !== 'file') {
                    input.style.fontSize = '16px';
                }
                
                // Add touch-friendly sizing
                if (this.device.isTouch) {
                    input.style.minHeight = '44px';
                    input.style.minWidth = '44px';
                }
            });
        },
        
        // Setup adaptive images
        setupAdaptiveImages() {
            const images = document.querySelectorAll('img:not(.adaptive-image)');
            
            images.forEach(img => {
                img.classList.add('adaptive-image');
                
                // Add responsive behavior
                img.style.maxWidth = '100%';
                img.style.height = 'auto';
                
                // Add loading optimization
                if (!img.hasAttribute('loading')) {
                    img.setAttribute('loading', 'lazy');
                }
            });
        },
        
        // Configure responsive typography
        configureResponsiveTypography() {
            if (this.device.isMobile) {
                document.documentElement.style.fontSize = '14px';
            } else if (this.device.isTablet) {
                document.documentElement.style.fontSize = '15px';
            } else {
                document.documentElement.style.fontSize = '16px';
            }
        },
        
        // Setup intersection observer for performance
        setupIntersectionObserver() {
            this.intersectionObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.optimizeVisibleElement(entry.target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '50px'
            });
        },
        
        // Optimize visible elements
        optimizeVisibleElement(element) {
            // Lazy load responsive content
            if (element.dataset.responsiveContent && !element.dataset.loaded) {
                this.loadResponsiveContent(element);
            }
            
            // Apply viewport-specific optimizations
            this.applyElementOptimizations(element);
        },
        
        // Load responsive content
        async loadResponsiveContent(element) {
            const contentType = element.dataset.responsiveContent;
            
            try {
                let content = '';
                
                switch (contentType) {
                    case 'chart':
                        content = await this.loadResponsiveChart(element);
                        break;
                    case 'table':
                        content = await this.loadResponsiveTable(element);
                        break;
                    case 'form':
                        content = await this.loadResponsiveForm(element);
                        break;
                }
                
                element.innerHTML = content;
                element.dataset.loaded = 'true';
                
            } catch (error) {
                console.error('Failed to load responsive content:', error);
            }
        },
        
        // Apply element-specific optimizations
        applyElementOptimizations(element) {
            // Optimize based on element type and viewport
            if (element.classList.contains('quote-item-list')) {
                this.optimizeItemList(element);
            } else if (element.classList.contains('pricing-calculator')) {
                this.optimizePricingCalculator(element);
            } else if (element.classList.contains('template-selector')) {
                this.optimizeTemplateSelector(element);
            }
        },
        
        // Optimize item list for current viewport
        optimizeItemList(element) {
            if (this.device.isMobile) {
                // Switch to card layout
                element.classList.add('mobile-card-layout');
                element.classList.remove('table-layout');
            } else {
                // Use table layout
                element.classList.add('table-layout');
                element.classList.remove('mobile-card-layout');
            }
        },
        
        // Optimize pricing calculator
        optimizePricingCalculator(element) {
            if (this.adaptiveStates.singleColumn) {
                element.classList.add('single-column-layout');
            } else {
                element.classList.remove('single-column-layout');
            }
        },
        
        // Optimize template selector
        optimizeTemplateSelector(element) {
            const templatesPerRow = this.device.isMobile ? 1 : 
                                  this.device.isTablet ? 2 : 3;
            
            element.style.setProperty('--templates-per-row', templatesPerRow);
        },
        
        // Apply initial responsive states
        applyInitialResponsiveStates() {
            this.updateAdaptiveStates();
            this.updateLayoutConfiguration();
            this.injectResponsiveStyles();
        },
        
        // Inject responsive CSS styles
        injectResponsiveStyles() {
            if (document.querySelector('#responsive-styles')) return;
            
            const styles = `
                <style id="responsive-styles">
                /* Responsive Design Utilities */
                .responsive-container {
                    width: 100%;
                    max-width: 100%;
                    margin: 0 auto;
                    padding: 0 1rem;
                }
                
                /* Breakpoint-specific containers */
                .bp-xs .responsive-container { max-width: 100%; }
                .bp-sm .responsive-container { max-width: 640px; }
                .bp-md .responsive-container { max-width: 768px; }
                .bp-lg .responsive-container { max-width: 1024px; }
                .bp-xl .responsive-container { max-width: 1280px; }
                
                /* Responsive Grid */
                .responsive-grid {
                    display: grid;
                    gap: 1rem;
                    grid-template-columns: 1fr;
                }
                
                .bp-sm .responsive-grid { grid-template-columns: repeat(2, 1fr); }
                .bp-md .responsive-grid { grid-template-columns: repeat(3, 1fr); }
                .bp-lg .responsive-grid { grid-template-columns: repeat(4, 1fr); }
                
                /* Responsive Tables */
                .table-responsive {
                    overflow-x: auto;
                    -webkit-overflow-scrolling: touch;
                }
                
                .mobile-device .responsive-table {
                    border: 0;
                }
                
                .mobile-device .responsive-table thead {
                    border: none;
                    clip: rect(0 0 0 0);
                    height: 1px;
                    margin: -1px;
                    overflow: hidden;
                    padding: 0;
                    position: absolute;
                    width: 1px;
                }
                
                .mobile-device .responsive-table tr {
                    border-bottom: 3px solid #ddd;
                    display: block;
                    margin-bottom: 0.625em;
                }
                
                .mobile-device .responsive-table td {
                    border: none;
                    border-bottom: 1px solid #eee;
                    display: block;
                    font-size: 0.8em;
                    text-align: right;
                    padding-left: 50%;
                    position: relative;
                }
                
                .mobile-device .responsive-table td:before {
                    content: attr(data-label) ": ";
                    font-weight: bold;
                    position: absolute;
                    left: 6px;
                    width: 45%;
                    text-align: left;
                }
                
                /* Touch-friendly elements */
                .large-touch-targets button,
                .large-touch-targets .btn,
                .large-touch-targets input[type="checkbox"],
                .large-touch-targets input[type="radio"] {
                    min-height: 44px;
                    min-width: 44px;
                }
                
                /* Compact spacing */
                .reduced-spacing .container,
                .reduced-spacing .card,
                .reduced-spacing .form-group {
                    padding: 0.5rem;
                    margin: 0.25rem 0;
                }
                
                /* Single column layout */
                .single-column .row {
                    flex-direction: column;
                }
                
                .single-column .col,
                .single-column [class*="col-"] {
                    flex: 1 1 100%;
                    max-width: 100%;
                }
                
                /* Simplified UI */
                .simplified-ui .advanced-options,
                .simplified-ui .secondary-actions {
                    display: none;
                }
                
                .simplified-ui .primary-actions {
                    width: 100%;
                    text-align: center;
                }
                
                /* Responsive text sizing */
                .bp-xs { font-size: 14px; }
                .bp-sm { font-size: 15px; }
                .bp-md { font-size: 16px; }
                
                /* Orientation-specific styles */
                .orientation-landscape.mobile-device .quote-form {
                    max-height: 70vh;
                    overflow-y: auto;
                }
                
                /* High DPI optimizations */
                .high-dpi img,
                .high-dpi .icon {
                    image-rendering: -webkit-optimize-contrast;
                }
                
                /* Reduced motion support */
                .reduced-motion * {
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                }
                
                /* Print styles */
                @media print {
                    .responsive-container {
                        max-width: none;
                        padding: 0;
                    }
                    
                    .no-print {
                        display: none !important;
                    }
                }
                
                /* Dark mode support */
                @media (prefers-color-scheme: dark) {
                    .responsive-table td:before {
                        color: #ccc;
                    }
                }
                </style>
            `;
            
            document.head.insertAdjacentHTML('beforeend', styles);
        },
        
        // Public API methods
        toggleSidebar() {
            this.layout.sidebar.visible = !this.layout.sidebar.visible;
            this.applyLayoutClasses();
        },
        
        setSidebarState(visible, collapsed = false) {
            this.layout.sidebar.visible = visible;
            this.layout.sidebar.collapsed = collapsed;
            this.applyLayoutClasses();
        },
        
        // Utility methods
        isBreakpoint(breakpoint) {
            return this.viewport.breakpoint === breakpoint;
        },
        
        isBreakpointUp(breakpoint) {
            const breakpoints = Object.keys(this.breakpoints);
            const currentIndex = breakpoints.indexOf(this.viewport.breakpoint);
            const targetIndex = breakpoints.indexOf(breakpoint);
            return currentIndex >= targetIndex;
        },
        
        isBreakpointDown(breakpoint) {
            const breakpoints = Object.keys(this.breakpoints);
            const currentIndex = breakpoints.indexOf(this.viewport.breakpoint);
            const targetIndex = breakpoints.indexOf(breakpoint);
            return currentIndex <= targetIndex;
        },
        
        // Computed properties
        get isMobileLayout() {
            return this.device.isMobile || this.adaptiveStates.compactMode;
        },
        
        get isTabletLayout() {
            return this.device.isTablet && !this.adaptiveStates.compactMode;
        },
        
        get isDesktopLayout() {
            return this.device.isDesktop && !this.adaptiveStates.compactMode;
        },
        
        get shouldUseSingleColumn() {
            return this.adaptiveStates.singleColumn;
        },
        
        get shouldShowSidebar() {
            return this.layout.sidebar.visible && !this.device.isMobile;
        }
    }));
    
    // Responsive directives
    Alpine.directive('responsive-hide', (el, { expression }, { evaluate, cleanup }) => {
        const breakpoints = expression.split(',').map(bp => bp.trim());
        
        const checkVisibility = () => {
            const responsiveData = Alpine.findClosest(el, x => x.$data.viewport);
            if (responsiveData) {
                const currentBp = responsiveData.viewport.breakpoint;
                const shouldHide = breakpoints.includes(currentBp);
                el.style.display = shouldHide ? 'none' : '';
            }
        };
        
        // Initial check
        checkVisibility();
        
        // Listen for breakpoint changes
        const listener = () => checkVisibility();
        document.addEventListener('breakpoint-change', listener);
        
        cleanup(() => {
            document.removeEventListener('breakpoint-change', listener);
        });
    });
    
    Alpine.directive('responsive-show', (el, { expression }, { evaluate, cleanup }) => {
        const breakpoints = expression.split(',').map(bp => bp.trim());
        
        const checkVisibility = () => {
            const responsiveData = Alpine.findClosest(el, x => x.$data.viewport);
            if (responsiveData) {
                const currentBp = responsiveData.viewport.breakpoint;
                const shouldShow = breakpoints.includes(currentBp);
                el.style.display = shouldShow ? '' : 'none';
            }
        };
        
        // Initial check
        checkVisibility();
        
        // Listen for breakpoint changes
        const listener = () => checkVisibility();
        document.addEventListener('breakpoint-change', listener);
        
        cleanup(() => {
            document.removeEventListener('breakpoint-change', listener);
        });
    });
});