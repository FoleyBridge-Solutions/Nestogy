/**
 * Drag and Drop Component
 * Handles drag-and-drop reordering for quote items with touch support
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('dragDrop', (config = {}) => ({
        // Configuration
        enabled: config.enabled !== false,
        enableTouch: config.enableTouch !== false,
        animationDuration: config.animationDuration || 300,
        scrollSensitivity: config.scrollSensitivity || 50,
        
        // State
        dragging: false,
        draggedItem: null,
        draggedElement: null,
        dropZone: null,
        placeholder: null,
        initialPosition: null,
        offset: { x: 0, y: 0 },
        
        // Touch state
        touching: false,
        touchStartTime: 0,
        longPressTimer: null,
        longPressDelay: 500,
        touchIdentifier: null,
        
        // Auto-scroll state
        autoScrolling: false,
        scrollDirection: 0,
        scrollSpeed: 0,
        
        // Visual feedback
        dragPreview: null,
        showDropIndicator: false,
        dropIndicatorPosition: null,

        // Initialize drag and drop
        init() {
            this.setupEventListeners();
            this.createDragPreview();
            this.setupAutoScroll();
        },

        // Setup event listeners
        setupEventListeners() {
            // Mouse events
            document.addEventListener('mousedown', this.handleMouseDown.bind(this));
            document.addEventListener('mousemove', this.handleMouseMove.bind(this));
            document.addEventListener('mouseup', this.handleMouseUp.bind(this));
            
            // Touch events
            if (this.enableTouch) {
                document.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: false });
                document.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
                document.addEventListener('touchend', this.handleTouchEnd.bind(this));
                document.addEventListener('touchcancel', this.handleTouchCancel.bind(this));
            }
            
            // Keyboard support
            document.addEventListener('keydown', this.handleKeyDown.bind(this));
            
            // Prevent default drag on images and other elements
            document.addEventListener('dragstart', (e) => {
                if (this.enabled) {
                    e.preventDefault();
                }
            });
        },

        // Mouse event handlers
        handleMouseDown(e) {
            if (!this.enabled || e.button !== 0) return;
            
            const dragHandle = e.target.closest('[data-drag-handle]');
            if (!dragHandle) return;
            
            const item = dragHandle.closest('[data-draggable-item]');
            if (!item) return;
            
            e.preventDefault();
            this.startDrag(item, e.clientX, e.clientY);
        },

        handleMouseMove(e) {
            if (!this.dragging) return;
            
            e.preventDefault();
            this.updateDragPosition(e.clientX, e.clientY);
            this.handleAutoScroll(e.clientY);
        },

        handleMouseUp(e) {
            if (this.dragging) {
                this.endDrag();
            }
        },

        // Touch event handlers
        handleTouchStart(e) {
            if (!this.enabled || !this.enableTouch) return;
            
            const touch = e.touches[0];
            const dragHandle = touch.target.closest('[data-drag-handle]');
            if (!dragHandle) return;
            
            const item = dragHandle.closest('[data-draggable-item]');
            if (!item) return;
            
            this.touching = true;
            this.touchStartTime = Date.now();
            this.touchIdentifier = touch.identifier;
            
            // Long press to start drag
            this.longPressTimer = setTimeout(() => {
                if (this.touching) {
                    e.preventDefault();
                    this.startDrag(item, touch.clientX, touch.clientY);
                    
                    // Provide haptic feedback if available
                    if (navigator.vibrate) {
                        navigator.vibrate(50);
                    }
                }
            }, this.longPressDelay);
        },

        handleTouchMove(e) {
            if (!this.touching && !this.dragging) return;
            
            const touch = Array.from(e.touches).find(t => t.identifier === this.touchIdentifier);
            if (!touch) return;
            
            if (this.dragging) {
                e.preventDefault();
                this.updateDragPosition(touch.clientX, touch.clientY);
                this.handleAutoScroll(touch.clientY);
            } else if (this.touching) {
                // Cancel long press if touch moves too much
                const threshold = 10;
                const deltaX = Math.abs(touch.clientX - this.initialPosition.x);
                const deltaY = Math.abs(touch.clientY - this.initialPosition.y);
                
                if (deltaX > threshold || deltaY > threshold) {
                    this.cancelTouch();
                }
            }
        },

        handleTouchEnd(e) {
            if (this.dragging) {
                this.endDrag();
            } else {
                this.cancelTouch();
            }
        },

        handleTouchCancel(e) {
            if (this.dragging) {
                this.cancelDrag();
            } else {
                this.cancelTouch();
            }
        },

        // Keyboard support
        handleKeyDown(e) {
            if (!this.enabled || !document.activeElement?.dataset.draggableItem) return;
            
            const item = document.activeElement;
            const itemId = item.dataset.itemId;
            const items = this.$store.quote.selectedItems;
            const currentIndex = items.findIndex(i => (i.id || i.temp_id) === itemId);
            
            if (currentIndex === -1) return;
            
            let newIndex = currentIndex;
            
            switch (e.key) {
                case 'ArrowUp':
                    e.preventDefault();
                    newIndex = Math.max(0, currentIndex - 1);
                    break;
                case 'ArrowDown':
                    e.preventDefault();
                    newIndex = Math.min(items.length - 1, currentIndex + 1);
                    break;
                case ' ':
                case 'Enter':
                    e.preventDefault();
                    this.toggleSelection(itemId);
                    return;
                default:
                    return;
            }
            
            if (newIndex !== currentIndex) {
                this.moveItem(currentIndex, newIndex);
                this.focusItem(newIndex);
            }
        },

        // Start drag operation
        startDrag(element, clientX, clientY) {
            const itemId = element.dataset.itemId;
            const item = this.$store.quote.selectedItems.find(i => (i.id || i.temp_id) === itemId);
            
            if (!item) return;
            
            this.dragging = true;
            this.draggedItem = item;
            this.draggedElement = element;
            this.initialPosition = { x: clientX, y: clientY };
            
            // Calculate offset from element center
            const rect = element.getBoundingClientRect();
            this.offset = {
                x: clientX - rect.left - rect.width / 2,
                y: clientY - rect.top - rect.height / 2
            };
            
            // Setup visual feedback
            this.setupDragVisuals();
            this.createPlaceholder();
            
            // Add dragging class to body
            document.body.classList.add('dragging');
            element.classList.add('dragging');
            
            // Dispatch drag start event
            this.$dispatch('drag-start', {
                item: this.draggedItem,
                element: this.draggedElement
            });
        },

        // Update drag position
        updateDragPosition(clientX, clientY) {
            if (!this.dragging || !this.dragPreview) return;
            
            // Update preview position
            this.dragPreview.style.left = (clientX - this.offset.x) + 'px';
            this.dragPreview.style.top = (clientY - this.offset.y) + 'px';
            
            // Find drop target
            const elementBelow = this.getElementBelow(clientX, clientY);
            const dropTarget = elementBelow?.closest('[data-draggable-item]');
            
            if (dropTarget && dropTarget !== this.draggedElement) {
                this.updateDropIndicator(dropTarget, clientY);
            } else {
                this.hideDropIndicator();
            }
        },

        // End drag operation
        endDrag() {
            if (!this.dragging) return;
            
            const dropTarget = this.getDropTarget();
            
            if (dropTarget) {
                this.performDrop(dropTarget);
            } else {
                this.cancelDrag();
            }
            
            this.cleanup();
        },

        // Cancel drag operation
        cancelDrag() {
            if (this.placeholder) {
                // Animate back to original position
                this.animateToOriginalPosition();
            } else {
                this.cleanup();
            }
        },

        // Cancel touch
        cancelTouch() {
            this.touching = false;
            if (this.longPressTimer) {
                clearTimeout(this.longPressTimer);
                this.longPressTimer = null;
            }
        },

        // Setup drag visuals
        setupDragVisuals() {
            if (!this.draggedElement) return;
            
            // Update drag preview
            const rect = this.draggedElement.getBoundingClientRect();
            this.dragPreview.innerHTML = this.draggedElement.outerHTML;
            this.dragPreview.style.width = rect.width + 'px';
            this.dragPreview.style.height = rect.height + 'px';
            this.dragPreview.style.display = 'block';
            
            // Style the preview
            const previewContent = this.dragPreview.firstElementChild;
            if (previewContent) {
                previewContent.classList.add('drag-preview-content');
                previewContent.style.transform = 'rotate(5deg)';
                previewContent.style.opacity = '0.9';
                previewContent.style.boxShadow = '0 10px 30px rgba(0,0,0,0.3)';
            }
        },

        // Create drag preview element
        createDragPreview() {
            this.dragPreview = document.createElement('div');
            this.dragPreview.className = 'drag-preview';
            this.dragPreview.style.cssText = `
                position: fixed;
                z-index: 10000;
                pointer-events: none;
                display: none;
                transition: none;
            `;
            document.body.appendChild(this.dragPreview);
        },

        // Create placeholder
        createPlaceholder() {
            if (!this.draggedElement) return;
            
            this.placeholder = document.createElement('div');
            this.placeholder.className = 'drag-placeholder';
            this.placeholder.style.cssText = `
                height: ${this.draggedElement.offsetHeight}px;
                border: 2px dashed #cbd5e0;
                background: #f7fafc;
                border-radius: 8px;
                margin: 8px 0;
                display: flex;
                align-items: center;
                justify-content: center;
                opacity: 0.7;
                transition: all 0.2s ease;
            `;
            this.placeholder.innerHTML = '<span style="color: #a0aec0;">Drop here</span>';
            
            this.draggedElement.parentNode.insertBefore(this.placeholder, this.draggedElement);
            this.draggedElement.style.display = 'none';
        },

        // Update drop indicator
        updateDropIndicator(dropTarget, clientY) {
            const rect = dropTarget.getBoundingClientRect();
            const isAbove = clientY < rect.top + rect.height / 2;
            
            this.showDropIndicator = true;
            this.dropIndicatorPosition = {
                element: dropTarget,
                above: isAbove
            };
            
            // Update visual indicator
            this.updateDropIndicatorVisual(dropTarget, isAbove);
        },

        // Update drop indicator visual
        updateDropIndicatorVisual(element, above) {
            // Remove existing indicators
            document.querySelectorAll('.drop-indicator').forEach(el => el.remove());
            
            // Create new indicator
            const indicator = document.createElement('div');
            indicator.className = 'drop-indicator';
            indicator.style.cssText = `
                height: 3px;
                background: #3182ce;
                border-radius: 2px;
                margin: 2px 0;
                transition: all 0.2s ease;
                box-shadow: 0 0 10px rgba(49, 130, 206, 0.5);
            `;
            
            if (above) {
                element.parentNode.insertBefore(indicator, element);
            } else {
                element.parentNode.insertBefore(indicator, element.nextSibling);
            }
        },

        // Hide drop indicator
        hideDropIndicator() {
            this.showDropIndicator = false;
            this.dropIndicatorPosition = null;
            document.querySelectorAll('.drop-indicator').forEach(el => el.remove());
        },

        // Get element below cursor/touch
        getElementBelow(clientX, clientY) {
            // Temporarily hide drag preview
            const originalDisplay = this.dragPreview.style.display;
            this.dragPreview.style.display = 'none';
            
            const element = document.elementFromPoint(clientX, clientY);
            
            // Restore drag preview
            this.dragPreview.style.display = originalDisplay;
            
            return element;
        },

        // Get drop target
        getDropTarget() {
            if (!this.dropIndicatorPosition) return null;
            
            const targetElement = this.dropIndicatorPosition.element;
            const targetId = targetElement.dataset.itemId;
            const targetItem = this.$store.quote.selectedItems.find(i => (i.id || i.temp_id) === targetId);
            
            return {
                item: targetItem,
                element: targetElement,
                insertBefore: this.dropIndicatorPosition.above
            };
        },

        // Perform drop operation
        performDrop(dropTarget) {
            const draggedId = this.draggedItem.id || this.draggedItem.temp_id;
            const targetId = dropTarget.item.id || dropTarget.item.temp_id;
            
            if (draggedId === targetId) {
                this.cancelDrag();
                return;
            }
            
            const items = this.$store.quote.selectedItems;
            const draggedIndex = items.findIndex(i => (i.id || i.temp_id) === draggedId);
            const targetIndex = items.findIndex(i => (i.id || i.temp_id) === targetId);
            
            if (draggedIndex === -1 || targetIndex === -1) {
                this.cancelDrag();
                return;
            }
            
            // Calculate new index
            let newIndex = targetIndex;
            if (!dropTarget.insertBefore && targetIndex >= draggedIndex) {
                newIndex = targetIndex + 1;
            } else if (dropTarget.insertBefore && targetIndex <= draggedIndex) {
                newIndex = targetIndex;
            }
            
            // Perform the move
            this.moveItem(draggedIndex, newIndex);
            
            // Animate success
            this.animateDropSuccess();
            
            // Dispatch drop event
            this.$dispatch('item-dropped', {
                item: this.draggedItem,
                fromIndex: draggedIndex,
                toIndex: newIndex
            });
        },

        // Move item in store
        moveItem(fromIndex, toIndex) {
            const items = [...this.$store.quote.selectedItems];
            const [movedItem] = items.splice(fromIndex, 1);
            items.splice(toIndex, 0, movedItem);
            
            this.$store.quote.selectedItems = items;
            this.$store.quote.recalculate();
        },

        // Animate drop success
        animateDropSuccess() {
            if (this.placeholder) {
                this.placeholder.style.background = '#48bb78';
                this.placeholder.style.borderColor = '#38a169';
                this.placeholder.innerHTML = '<span style="color: white;">✓ Moved</span>';
                
                setTimeout(() => {
                    if (this.placeholder) {
                        this.placeholder.style.opacity = '0';
                    }
                }, 500);
            }
        },

        // Animate to original position
        animateToOriginalPosition() {
            if (!this.dragPreview || !this.draggedElement) {
                this.cleanup();
                return;
            }
            
            const originalRect = this.draggedElement.getBoundingClientRect();
            const currentRect = this.dragPreview.getBoundingClientRect();
            
            this.dragPreview.style.transition = `all ${this.animationDuration}ms ease-out`;
            this.dragPreview.style.left = originalRect.left + 'px';
            this.dragPreview.style.top = originalRect.top + 'px';
            this.dragPreview.style.opacity = '0.5';
            
            setTimeout(() => {
                this.cleanup();
            }, this.animationDuration);
        },

        // Auto-scroll functionality
        setupAutoScroll() {
            // Auto-scroll timer
            setInterval(() => {
                if (this.autoScrolling && this.scrollDirection !== 0) {
                    window.scrollBy(0, this.scrollDirection * this.scrollSpeed);
                }
            }, 16); // ~60fps
        },

        handleAutoScroll(clientY) {
            const threshold = this.scrollSensitivity;
            const viewport = window.innerHeight;
            
            if (clientY < threshold) {
                // Scroll up
                this.autoScrolling = true;
                this.scrollDirection = -1;
                this.scrollSpeed = Math.max(1, (threshold - clientY) / 5);
            } else if (clientY > viewport - threshold) {
                // Scroll down
                this.autoScrolling = true;
                this.scrollDirection = 1;
                this.scrollSpeed = Math.max(1, (clientY - (viewport - threshold)) / 5);
            } else {
                this.autoScrolling = false;
                this.scrollDirection = 0;
                this.scrollSpeed = 0;
            }
        },

        // Focus item (for keyboard navigation)
        focusItem(index) {
            const items = document.querySelectorAll('[data-draggable-item]');
            if (items[index]) {
                items[index].focus();
            }
        },

        // Toggle selection (for keyboard navigation)
        toggleSelection(itemId) {
            // Implementation depends on your selection system
            this.$dispatch('toggle-item-selection', { itemId });
        },

        // Cleanup
        cleanup() {
            this.dragging = false;
            this.touching = false;
            this.autoScrolling = false;
            
            if (this.longPressTimer) {
                clearTimeout(this.longPressTimer);
                this.longPressTimer = null;
            }
            
            // Remove visual elements
            if (this.placeholder) {
                this.placeholder.remove();
                this.placeholder = null;
            }
            
            if (this.dragPreview) {
                this.dragPreview.style.display = 'none';
                this.dragPreview.innerHTML = '';
            }
            
            this.hideDropIndicator();
            
            // Remove classes
            document.body.classList.remove('dragging');
            if (this.draggedElement) {
                this.draggedElement.classList.remove('dragging');
                this.draggedElement.style.display = '';
            }
            
            // Reset state
            this.draggedItem = null;
            this.draggedElement = null;
            this.dropZone = null;
            this.initialPosition = null;
            this.touchIdentifier = null;
        },

        // Destroy component
        destroy() {
            this.cleanup();
            if (this.dragPreview) {
                this.dragPreview.remove();
            }
        },

        // Public API
        enableDragDrop() {
            this.enabled = true;
            document.body.classList.add('drag-drop-enabled');
        },

        disableDragDrop() {
            this.enabled = false;
            this.cleanup();
            document.body.classList.remove('drag-drop-enabled');
        },

        // Computed properties
        get isDragging() {
            return this.dragging;
        },

        get isTouching() {
            return this.touching;
        }
    }));

    // Global drag-drop store
    Alpine.store('dragDrop', {
        enabled: true,
        
        enable() {
            this.enabled = true;
        },
        
        disable() {
            this.enabled = false;
        }
    });
});