/**
 * Touch-Friendly Interactions Component
 * Enhances the interface with touch-optimized interactions for mobile devices
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('touchInteractions', (config = {}) => ({
        // Configuration
        tapTimeout: config.tapTimeout || 300,
        longPressTimeout: config.longPressTimeout || 500,
        swipeThreshold: config.swipeThreshold || 50,
        enableHaptics: config.enableHaptics !== false,
        
        // Touch state tracking
        touchState: {
            active: false,
            startTime: 0,
            startX: 0,
            startY: 0,
            currentX: 0,
            currentY: 0,
            element: null
        },
        
        // Gesture detection
        gestureHandlers: new Map(),
        longPressTimer: null,
        tapTimer: null,
        
        // Touch feedback
        feedbackElements: new Set(),
        
        // Scroll momentum
        scrollMomentum: {
            isScrolling: false,
            velocity: 0,
            lastY: 0,
            lastTime: 0
        },
        
        // Pull-to-refresh
        pullToRefresh: {
            enabled: false,
            threshold: 80,
            maxDistance: 120,
            currentDistance: 0,
            isRefreshing: false
        },
        
        // Initialize touch interactions
        init() {
            this.setupTouchListeners();
            this.enhanceExistingElements();
            this.setupPullToRefresh();
            this.initializeHaptics();
        },
        
        // Setup global touch event listeners
        setupTouchListeners() {
            // Passive listeners for better performance
            document.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: false });
            document.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
            document.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: false });
            document.addEventListener('touchcancel', this.handleTouchCancel.bind(this), { passive: false });
            
            // Prevent context menu on long press for touch elements
            document.addEventListener('contextmenu', (e) => {
                if (e.target.closest('[data-touch-action]')) {
                    e.preventDefault();
                }
            });
            
            // Enhance click targets
            this.enhanceClickTargets();
        },
        
        // Handle touch start
        handleTouchStart(e) {
            if (e.touches.length > 1) return; // Ignore multi-touch
            
            const touch = e.touches[0];
            const element = e.target;
            
            this.touchState = {
                active: true,
                startTime: Date.now(),
                startX: touch.clientX,
                startY: touch.clientY,
                currentX: touch.clientX,
                currentY: touch.clientY,
                element: element
            };
            
            // Add touch feedback
            this.addTouchFeedback(element);
            
            // Setup long press detection
            this.setupLongPress(element);
            
            // Handle pull-to-refresh
            this.handlePullToRefreshStart(touch);
        },
        
        // Handle touch move
        handleTouchMove(e) {
            if (!this.touchState.active || e.touches.length > 1) return;
            
            const touch = e.touches[0];
            this.touchState.currentX = touch.clientX;
            this.touchState.currentY = touch.clientY;
            
            const deltaX = touch.clientX - this.touchState.startX;
            const deltaY = touch.clientY - this.touchState.startY;
            const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);
            
            // Cancel long press if moved too much
            if (distance > 10 && this.longPressTimer) {
                clearTimeout(this.longPressTimer);
                this.longPressTimer = null;
            }
            
            // Remove touch feedback if moved
            if (distance > 5) {
                this.removeTouchFeedback(this.touchState.element);
            }
            
            // Handle pull-to-refresh
            this.handlePullToRefreshMove(touch, deltaY);
            
            // Update scroll momentum
            this.updateScrollMomentum(touch);
        },
        
        // Handle touch end
        handleTouchEnd(e) {
            if (!this.touchState.active) return;
            
            const duration = Date.now() - this.touchState.startTime;
            const deltaX = this.touchState.currentX - this.touchState.startX;
            const deltaY = this.touchState.currentY - this.touchState.startY;
            const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);
            
            // Clear timers
            if (this.longPressTimer) {
                clearTimeout(this.longPressTimer);
                this.longPressTimer = null;
            }
            
            // Detect gesture type
            if (distance < 10 && duration < this.tapTimeout) {
                this.handleTap(this.touchState.element, e);
            } else if (distance > this.swipeThreshold && duration < 300) {
                this.handleSwipe(deltaX, deltaY, this.touchState.element);
            }
            
            // Remove touch feedback
            setTimeout(() => {
                this.removeTouchFeedback(this.touchState.element);
            }, 100);
            
            // Handle pull-to-refresh end
            this.handlePullToRefreshEnd();
            
            // Reset touch state
            this.touchState.active = false;
        },
        
        // Handle touch cancel
        handleTouchCancel(e) {
            this.removeTouchFeedback(this.touchState.element);
            
            if (this.longPressTimer) {
                clearTimeout(this.longPressTimer);
                this.longPressTimer = null;
            }
            
            this.touchState.active = false;
        },
        
        // Setup long press detection
        setupLongPress(element) {
            this.longPressTimer = setTimeout(() => {
                if (this.touchState.active) {
                    this.handleLongPress(element);
                }
            }, this.longPressTimeout);
        },
        
        // Handle tap gesture
        handleTap(element, originalEvent) {
            // Haptic feedback for tap
            this.triggerHaptic('light');
            
            // Check for custom tap handler
            const tapAction = element.dataset.tapAction;
            if (tapAction) {
                this.executeAction(tapAction, element, { type: 'tap', originalEvent });
                return;
            }
            
            // Enhanced button/link handling
            if (element.tagName === 'BUTTON' || element.tagName === 'A') {
                this.enhanceButtonTap(element);
            }
            
            // Double-tap detection
            this.detectDoubleTap(element);
        },
        
        // Handle long press gesture
        handleLongPress(element) {
            // Haptic feedback for long press
            this.triggerHaptic('medium');
            
            // Add visual feedback
            element.classList.add('long-pressed');
            setTimeout(() => element.classList.remove('long-pressed'), 200);
            
            // Check for custom long press handler
            const longPressAction = element.dataset.longPressAction;
            if (longPressAction) {
                this.executeAction(longPressAction, element, { type: 'longpress' });
                return;
            }
            
            // Default long press actions
            if (element.dataset.touchAction === 'contextMenu') {
                this.showContextMenu(element);
            } else if (element.dataset.touchAction === 'selection') {
                this.toggleSelection(element);
            }
        },
        
        // Handle swipe gesture
        handleSwipe(deltaX, deltaY, element) {
            const absX = Math.abs(deltaX);
            const absY = Math.abs(deltaY);
            
            let direction;
            if (absX > absY) {
                direction = deltaX > 0 ? 'right' : 'left';
            } else {
                direction = deltaY > 0 ? 'down' : 'up';
            }
            
            // Haptic feedback for swipe
            this.triggerHaptic('light');
            
            // Check for custom swipe handler
            const swipeAction = element.dataset[`swipe${direction.charAt(0).toUpperCase() + direction.slice(1)}Action`];
            if (swipeAction) {
                this.executeAction(swipeAction, element, { type: 'swipe', direction });
                return;
            }
            
            // Default swipe actions
            this.handleDefaultSwipe(direction, element);
        },
        
        // Handle default swipe actions
        handleDefaultSwipe(direction, element) {
            // Card swipe actions
            if (element.classList.contains('swipeable-card')) {
                this.handleCardSwipe(element, direction);
            }
            
            // List item swipe actions
            if (element.classList.contains('swipeable-item')) {
                this.handleListItemSwipe(element, direction);
            }
            
            // Navigation swipe
            if (element.classList.contains('swipeable-nav')) {
                this.handleNavigationSwipe(direction);
            }
        },
        
        // Add touch feedback to element
        addTouchFeedback(element) {
            if (!element) return;
            
            // Add ripple effect
            this.createRippleEffect(element);
            
            // Add pressed state
            element.classList.add('touch-active');
            this.feedbackElements.add(element);
            
            // Scale effect for buttons
            if (element.tagName === 'BUTTON' || element.classList.contains('btn')) {
                element.classList.add('touch-scale');
            }
        },
        
        // Remove touch feedback
        removeTouchFeedback(element) {
            if (!element) return;
            
            element.classList.remove('touch-active', 'touch-scale');
            this.feedbackElements.delete(element);
            
            // Remove ripple effects
            const ripples = element.querySelectorAll('.ripple-effect');
            ripples.forEach(ripple => {
                setTimeout(() => ripple.remove(), 300);
            });
        },
        
        // Create ripple effect
        createRippleEffect(element) {
            if (!element.dataset.ripple) return;
            
            const rect = element.getBoundingClientRect();
            const ripple = document.createElement('div');
            
            ripple.className = 'ripple-effect';
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.3);
                transform: scale(0);
                animation: ripple 0.6s linear;
                pointer-events: none;
                left: ${this.touchState.startX - rect.left - 10}px;
                top: ${this.touchState.startY - rect.top - 10}px;
                width: 20px;
                height: 20px;
            `;
            
            element.style.position = 'relative';
            element.style.overflow = 'hidden';
            element.appendChild(ripple);
        },
        
        // Enhance existing elements
        enhanceExistingElements() {
            // Enhance buttons
            document.querySelectorAll('button, .btn').forEach(btn => {
                btn.dataset.ripple = 'true';
                this.makeTouchFriendly(btn);
            });
            
            // Enhance form inputs
            document.querySelectorAll('input, select, textarea').forEach(input => {
                this.enhanceFormInput(input);
            });
            
            // Enhance lists
            document.querySelectorAll('.list-item, .card').forEach(item => {
                this.makeTouchFriendly(item);
            });
        },
        
        // Make element touch-friendly
        makeTouchFriendly(element) {
            // Minimum touch target size (44px)
            const computedStyle = getComputedStyle(element);
            const minSize = 44;
            
            if (parseInt(computedStyle.height) < minSize) {
                element.style.minHeight = minSize + 'px';
            }
            
            if (parseInt(computedStyle.width) < minSize) {
                element.style.minWidth = minSize + 'px';
            }
            
            // Add touch-friendly padding
            if (!element.style.padding && !element.className.includes('no-touch-padding')) {
                element.style.padding = '12px 16px';
            }
        },
        
        // Enhance form inputs for touch
        enhanceFormInput(input) {
            // Larger touch targets for mobile
            input.style.minHeight = '44px';
            input.style.fontSize = '16px'; // Prevent zoom on iOS
            
            // Touch-friendly focus handling
            input.addEventListener('focus', () => {
                input.classList.add('touch-focused');
                
                // Scroll into view on mobile
                if (window.innerWidth <= 768) {
                    setTimeout(() => {
                        input.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 100);
                }
            });
            
            input.addEventListener('blur', () => {
                input.classList.remove('touch-focused');
            });
        },
        
        // Enhance button tap behavior
        enhanceButtonTap(button) {
            // Prevent double-tap zoom
            button.style.touchAction = 'manipulation';
            
            // Add pressed state
            button.classList.add('button-pressed');
            setTimeout(() => button.classList.remove('button-pressed'), 150);
        },
        
        // Detect double tap
        detectDoubleTap(element) {
            const now = Date.now();
            const lastTap = element.dataset.lastTap || 0;
            
            if (now - lastTap < 300) {
                this.handleDoubleTap(element);
            }
            
            element.dataset.lastTap = now;
        },
        
        // Handle double tap
        handleDoubleTap(element) {
            this.triggerHaptic('medium');
            
            const doubleTapAction = element.dataset.doubleTapAction;
            if (doubleTapAction) {
                this.executeAction(doubleTapAction, element, { type: 'doubletap' });
            }
        },
        
        // Card swipe handling
        handleCardSwipe(card, direction) {
            card.classList.add(`swiped-${direction}`);
            
            if (direction === 'left' || direction === 'right') {
                // Animate card out
                card.style.transform = `translateX(${direction === 'left' ? '-' : ''}100%)`;
                card.style.opacity = '0';
                
                setTimeout(() => {
                    card.style.transform = '';
                    card.style.opacity = '';
                    card.classList.remove(`swiped-${direction}`);
                    
                    // Trigger action
                    const action = direction === 'left' ? 'dismiss' : 'archive';
                    this.executeAction(action, card, { type: 'swipe', direction });
                }, 300);
            }
        },
        
        // List item swipe handling
        handleListItemSwipe(item, direction) {
            if (direction === 'left') {
                this.showListItemActions(item);
            } else if (direction === 'right') {
                this.hideListItemActions(item);
            }
        },
        
        // Show list item actions
        showListItemActions(item) {
            // Create action buttons if not exist
            let actions = item.querySelector('.swipe-actions');
            if (!actions) {
                actions = this.createSwipeActions(item);
            }
            
            actions.style.display = 'flex';
            item.style.transform = 'translateX(-80px)';
        },
        
        // Hide list item actions
        hideListItemActions(item) {
            const actions = item.querySelector('.swipe-actions');
            if (actions) {
                actions.style.display = 'none';
            }
            item.style.transform = '';
        },
        
        // Create swipe actions
        createSwipeActions(item) {
            const actions = document.createElement('div');
            actions.className = 'swipe-actions';
            actions.style.cssText = `
                position: absolute;
                right: 0;
                top: 0;
                height: 100%;
                width: 80px;
                display: none;
                background: #dc3545;
                color: white;
                align-items: center;
                justify-content: center;
                font-size: 18px;
                cursor: pointer;
            `;
            
            actions.innerHTML = '🗑️';
            actions.addEventListener('click', () => {
                this.executeAction('delete', item, { type: 'swipe-action' });
            });
            
            item.style.position = 'relative';
            item.appendChild(actions);
            
            return actions;
        },
        
        // Navigation swipe handling
        handleNavigationSwipe(direction) {
            // Emit navigation events
            this.$dispatch('navigation-swipe', { direction });
        },
        
        // Pull-to-refresh functionality
        setupPullToRefresh() {
            // Only enable on specific containers
            const containers = document.querySelectorAll('[data-pull-refresh]');
            containers.forEach(container => {
                container.style.overscrollBehavior = 'contain';
            });
        },
        
        handlePullToRefreshStart(touch) {
            const container = touch.target.closest('[data-pull-refresh]');
            if (!container || container.scrollTop > 0) return;
            
            this.pullToRefresh.enabled = true;
        },
        
        handlePullToRefreshMove(touch, deltaY) {
            if (!this.pullToRefresh.enabled || deltaY < 0) return;
            
            this.pullToRefresh.currentDistance = Math.min(deltaY, this.pullToRefresh.maxDistance);
            
            // Update visual indicator
            this.updatePullToRefreshIndicator();
        },
        
        handlePullToRefreshEnd() {
            if (!this.pullToRefresh.enabled) return;
            
            if (this.pullToRefresh.currentDistance >= this.pullToRefresh.threshold) {
                this.triggerRefresh();
            } else {
                this.resetPullToRefresh();
            }
        },
        
        updatePullToRefreshIndicator() {
            const indicator = document.querySelector('.pull-refresh-indicator');
            if (indicator) {
                const progress = this.pullToRefresh.currentDistance / this.pullToRefresh.threshold;
                indicator.style.transform = `translateY(${this.pullToRefresh.currentDistance}px) rotate(${progress * 180}deg)`;
                indicator.style.opacity = Math.min(progress, 1);
            }
        },
        
        triggerRefresh() {
            this.pullToRefresh.isRefreshing = true;
            this.$dispatch('pull-refresh');
            
            // Reset after refresh completes
            setTimeout(() => {
                this.resetPullToRefresh();
            }, 2000);
        },
        
        resetPullToRefresh() {
            this.pullToRefresh = {
                enabled: false,
                threshold: 80,
                maxDistance: 120,
                currentDistance: 0,
                isRefreshing: false
            };
        },
        
        // Context menu handling
        showContextMenu(element) {
            const menu = this.createContextMenu(element);
            document.body.appendChild(menu);
            
            // Position menu
            const rect = element.getBoundingClientRect();
            menu.style.left = rect.left + 'px';
            menu.style.top = (rect.bottom + 10) + 'px';
            
            // Auto-hide menu
            setTimeout(() => {
                if (menu.parentNode) {
                    menu.parentNode.removeChild(menu);
                }
            }, 3000);
        },
        
        createContextMenu(element) {
            const menu = document.createElement('div');
            menu.className = 'touch-context-menu';
            menu.style.cssText = `
                position: fixed;
                background: #333;
                color: white;
                border-radius: 8px;
                padding: 8px;
                z-index: 1000;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            `;
            
            // Add menu items based on element
            const items = this.getContextMenuItems(element);
            items.forEach(item => {
                const menuItem = document.createElement('div');
                menuItem.textContent = item.label;
                menuItem.style.cssText = `
                    padding: 8px 12px;
                    cursor: pointer;
                    border-radius: 4px;
                `;
                
                menuItem.addEventListener('click', () => {
                    item.action();
                    menu.remove();
                });
                
                menu.appendChild(menuItem);
            });
            
            return menu;
        },
        
        getContextMenuItems(element) {
            const items = [];
            
            if (element.dataset.contextCopy) {
                items.push({
                    label: 'Copy',
                    action: () => this.copyElement(element)
                });
            }
            
            if (element.dataset.contextEdit) {
                items.push({
                    label: 'Edit',
                    action: () => this.editElement(element)
                });
            }
            
            if (element.dataset.contextDelete) {
                items.push({
                    label: 'Delete',
                    action: () => this.deleteElement(element)
                });
            }
            
            return items;
        },
        
        // Scroll momentum
        updateScrollMomentum(touch) {
            const now = Date.now();
            const deltaY = touch.clientY - this.scrollMomentum.lastY;
            const deltaTime = now - this.scrollMomentum.lastTime;
            
            if (deltaTime > 0) {
                this.scrollMomentum.velocity = deltaY / deltaTime;
            }
            
            this.scrollMomentum.lastY = touch.clientY;
            this.scrollMomentum.lastTime = now;
            this.scrollMomentum.isScrolling = true;
        },
        
        // Haptic feedback
        initializeHaptics() {
            this.hasHaptics = 'vibrate' in navigator;
        },
        
        triggerHaptic(type = 'light') {
            if (!this.enableHaptics || !this.hasHaptics) return;
            
            const patterns = {
                light: 10,
                medium: 50,
                heavy: 100,
                double: [50, 50, 50],
                success: [50, 100, 50],
                error: [100, 50, 100, 50, 100]
            };
            
            const pattern = patterns[type] || patterns.light;
            navigator.vibrate(pattern);
        },
        
        // Action execution
        executeAction(action, element, context) {
            // Emit custom event
            this.$dispatch('touch-action', {
                action,
                element,
                context
            });
            
            // Execute built-in actions
            switch (action) {
                case 'select':
                    this.toggleSelection(element);
                    break;
                case 'delete':
                    this.deleteElement(element);
                    break;
                case 'edit':
                    this.editElement(element);
                    break;
                case 'copy':
                    this.copyElement(element);
                    break;
            }
        },
        
        // Selection handling
        toggleSelection(element) {
            element.classList.toggle('selected');
            this.triggerHaptic('light');
        },
        
        // Element actions
        copyElement(element) {
            const text = element.textContent || element.value;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text);
            }
            this.triggerHaptic('success');
        },
        
        editElement(element) {
            element.focus();
            if (element.select) element.select();
        },
        
        deleteElement(element) {
            if (confirm('Delete this item?')) {
                element.remove();
                this.triggerHaptic('heavy');
            }
        },
        
        // Click target enhancement
        enhanceClickTargets() {
            // Add invisible padding to small click targets
            document.querySelectorAll('a, button, input[type="checkbox"], input[type="radio"]').forEach(el => {
                const rect = el.getBoundingClientRect();
                if (rect.width < 44 || rect.height < 44) {
                    el.style.position = 'relative';
                    el.style.setProperty('--touch-padding', '12px');
                }
            });
        },
        
        // Public methods
        enableTouchMode() {
            document.body.classList.add('touch-mode');
        },
        
        disableTouchMode() {
            document.body.classList.remove('touch-mode');
        },
        
        // CSS injection for touch styles
        injectTouchStyles() {
            if (document.querySelector('#touch-styles')) return;
            
            const styles = `
                <style id="touch-styles">
                .touch-active {
                    background-color: rgba(0, 0, 0, 0.05) !important;
                    transform: scale(0.98);
                }
                
                .touch-scale {
                    transition: transform 0.1s ease;
                }
                
                .button-pressed {
                    transform: scale(0.95);
                    transition: transform 0.1s ease;
                }
                
                .long-pressed {
                    background-color: rgba(0, 0, 0, 0.1) !important;
                }
                
                .touch-focused {
                    outline: 2px solid #007AFF;
                    outline-offset: 2px;
                }
                
                @keyframes ripple {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
                
                .tap-highlight {
                    background-color: rgba(0, 0, 0, 0.1);
                }
                
                @media (max-width: 768px) {
                    * {
                        -webkit-tap-highlight-color: rgba(0, 0, 0, 0.1);
                        -webkit-touch-callout: none;
                        -webkit-user-select: none;
                        user-select: none;
                    }
                    
                    input, textarea, [contenteditable] {
                        -webkit-user-select: text;
                        user-select: text;
                    }
                }
                </style>
            `;
            
            document.head.insertAdjacentHTML('beforeend', styles);
        }
    }));
    
    // Auto-initialize touch interactions on mobile
    if ('ontouchstart' in window) {
        document.addEventListener('DOMContentLoaded', () => {
            const touchHandler = Alpine.reactive(Alpine.data('touchInteractions')());
            touchHandler.init();
            touchHandler.injectTouchStyles();
        });
    }
});