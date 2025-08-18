/**
 * Modern Ticket Merge Component
 * 
 * A comprehensive component for merging tickets with enhanced UX:
 * - Search and select target tickets
 * - Preview merge details
 * - Validation and error handling
 * - Modern UI with Alpine.js and Tailwind
 */

export function ticketMerge() {
    return {
        // Component state
        isOpen: false,
        isLoading: false,
        isSearching: false,
        searchQuery: '',
        selectedTicket: null,
        searchResults: [],
        mergeComment: '',
        currentTicket: null,
        
        // Search debounce timer
        searchTimer: null,
        
        /**
         * Initialize the component
         */
        init() {
            // Get current ticket info from the DOM or props
            this.currentTicket = this.getCurrentTicketInfo();
        },
        
        /**
         * Open the merge modal
         */
        open() {
            this.isOpen = true;
            this.resetForm();
            // Focus on search input after modal animation
            this.$nextTick(() => {
                this.$refs.searchInput?.focus();
            });
        },
        
        /**
         * Close the merge modal
         */
        close() {
            this.isOpen = false;
            this.resetForm();
        },
        
        /**
         * Reset form to initial state
         */
        resetForm() {
            this.searchQuery = '';
            this.selectedTicket = null;
            this.searchResults = [];
            this.mergeComment = '';
            this.isSearching = false;
            if (this.searchTimer) {
                clearTimeout(this.searchTimer);
            }
        },
        
        /**
         * Handle search input with debouncing
         */
        handleSearchInput() {
            if (this.searchTimer) {
                clearTimeout(this.searchTimer);
            }
            
            if (this.searchQuery.length < 2) {
                this.searchResults = [];
                return;
            }
            
            this.searchTimer = setTimeout(() => {
                this.searchTickets();
            }, 300);
        },
        
        /**
         * Search for tickets to merge into
         */
        async searchTickets() {
            if (this.searchQuery.length < 2) return;
            
            this.isSearching = true;
            
            try {
                const response = await fetch(`/tickets/search?q=${encodeURIComponent(this.searchQuery)}&exclude=${this.currentTicket.id}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Search failed');
                }
                
                const data = await response.json();
                this.searchResults = data.tickets || [];
                
                // Auto-select if searching by exact ticket number
                if (this.searchQuery.match(/^\\d+$/) && this.searchResults.length === 1) {
                    const exactMatch = this.searchResults.find(t => t.number.toString() === this.searchQuery);
                    if (exactMatch) {
                        this.selectTicket(exactMatch);
                    }
                }
                
            } catch (error) {
                console.error('Search error:', error);
                showNotification('Failed to search tickets', 'error');
                this.searchResults = [];
            } finally {
                this.isSearching = false;
            }
        },
        
        /**
         * Select a ticket to merge into
         */
        selectTicket(ticket) {
            this.selectedTicket = ticket;
            this.searchQuery = `#${ticket.number} - ${ticket.subject}`;
            this.searchResults = [];
        },
        
        /**
         * Clear selected ticket
         */
        clearSelection() {
            this.selectedTicket = null;
            this.searchQuery = '';
            this.searchResults = [];
            this.$refs.searchInput?.focus();
        },
        
        /**
         * Submit the merge request
         */
        async submitMerge() {
            if (!this.selectedTicket) {
                showNotification('Please select a ticket to merge into', 'warning');
                return;
            }
            
            if (!this.mergeComment.trim()) {
                const shouldContinue = await confirmAction(
                    'No merge comment provided. Continue without a comment?',
                    {
                        title: 'Missing Comment',
                        confirmText: 'Continue',
                        cancelText: 'Add Comment',
                        type: 'warning'
                    }
                );
                
                if (!shouldContinue) {
                    this.$refs.commentInput?.focus();
                    return;
                }
            }
            
            this.isLoading = true;
            
            try {
                const formData = new FormData();
                formData.append('merge_into_number', this.selectedTicket.number);
                formData.append('merge_comment', this.mergeComment.trim());
                
                const response = await fetch(`/tickets/${this.currentTicket.id}/merge`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData
                });
                
                // Log response details for debugging
                console.log('Merge response status:', response.status);
                console.log('Merge response headers:', Object.fromEntries(response.headers.entries()));
                
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Merge error response:', errorText);
                    console.error('Response status:', response.status);
                    console.error('Response statusText:', response.statusText);
                    
                    try {
                        const errorData = JSON.parse(errorText);
                        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
                    } catch (jsonError) {
                        throw new Error(`HTTP error! status: ${response.status} - ${errorText}`);
                    }
                }
                
                const data = await response.json();
                console.log('Merge response data:', data);
                
                if (data.success) {
                    showNotification('Tickets merged successfully!', 'success');
                    this.close();
                    
                    // Redirect to the merged ticket using the provided URL or fallback
                    if (data.target_ticket_url) {
                        window.location.href = data.target_ticket_url;
                    } else {
                        window.location.href = `/tickets/${this.selectedTicket.id}`;
                    }
                } else {
                    // Show specific error message
                    let errorMessage = 'Failed to merge tickets';
                    
                    if (data.message) {
                        errorMessage = data.message;
                    } else if (data.errors) {
                        // Handle validation errors
                        const errors = Object.values(data.errors).flat();
                        errorMessage = errors.length > 0 ? errors[0] : errorMessage;
                    }
                    
                    showNotification(errorMessage, 'error');
                }
                
            } catch (error) {
                console.error('Merge error:', error);
                
                // Provide more specific error messages
                let errorMessage = 'Failed to merge tickets';
                if (error.message) {
                    errorMessage = error.message;
                } else if (error.toString().includes('Failed to fetch')) {
                    errorMessage = 'Network error - please check your connection';
                } else if (error.toString().includes('JSON')) {
                    errorMessage = 'Server response error - please try again';
                }
                
                showNotification(errorMessage, 'error');
            } finally {
                this.isLoading = false;
            }
        },
        
        /**
         * Get current ticket information
         */
        getCurrentTicketInfo() {
            // Try to get from meta tags or data attributes
            const ticketId = document.querySelector('meta[name="ticket-id"]')?.content ||
                           document.querySelector('[data-ticket-id]')?.dataset.ticketId;
            const ticketNumber = document.querySelector('meta[name="ticket-number"]')?.content ||
                               document.querySelector('[data-ticket-number]')?.dataset.ticketNumber;
            const ticketSubject = document.querySelector('meta[name="ticket-subject"]')?.content ||
                                document.querySelector('[data-ticket-subject]')?.dataset.ticketSubject;
            
            return {
                id: ticketId,
                number: ticketNumber,
                subject: ticketSubject
            };
        },
        
        /**
         * Format ticket display text
         */
        formatTicketDisplay(ticket) {
            return `#${ticket.number} - ${ticket.subject}`;
        },
        
        /**
         * Get ticket status badge class
         */
        getStatusBadgeClass(status) {
            const classes = {
                'open': 'bg-blue-100 text-blue-800',
                'in_progress': 'bg-yellow-100 text-yellow-800',
                'resolved': 'bg-green-100 text-green-800',
                'closed': 'bg-gray-100 text-gray-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },
        
        /**
         * Get priority badge class
         */
        getPriorityBadgeClass(priority) {
            const classes = {
                'low': 'bg-green-100 text-green-800',
                'normal': 'bg-blue-100 text-blue-800',
                'high': 'bg-orange-100 text-orange-800',
                'urgent': 'bg-red-100 text-red-800'
            };
            return classes[priority] || 'bg-gray-100 text-gray-800';
        }
    };
}