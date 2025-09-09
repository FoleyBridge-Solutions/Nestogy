window.contactSearchField = function(options = {}) {
    return {
        // Configuration
        name: options.name || 'contact_id',
        clientId: options.clientId || null,
        selectedContact: options.selectedContact || null,
        
        // State
        searchQuery: '',
        selectedContactId: '',
        contacts: [],
        filteredContacts: [],
        open: false,
        selectedIndex: -1,
        loadingContacts: false,
        
        // Initialize
        init() {
            console.log('ContactSearchField init with client:', this.clientId);
            
            // Set initial values if contact is pre-selected
            if (this.selectedContact && this.selectedContact.id) {
                console.log('Initial selected contact:', this.selectedContact);
                this.searchQuery = this.selectedContact.name || '';
                this.selectedContactId = this.selectedContact.id;
            }
            
            // Load contacts if client is already selected
            if (this.clientId) {
                this.loadContacts();
            }
            
            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!this.$el.contains(e.target)) {
                    this.close();
                }
            });
            
            // Listen for client selection events
            this.$watch('clientId', (newClientId) => {
                if (newClientId) {
                    this.loadContacts();
                } else {
                    this.clearAll();
                }
            });
            
            // Listen for client-selected events from client dropdown
            window.addEventListener('client-selected', (e) => {
                const newClient = e.detail.client;
                if (newClient && newClient.id) {
                    console.log('Contact field received client-selected event:', newClient.id);
                    this.clientId = newClient.id;
                } else {
                    console.log('Contact field received client-selected event: no client');
                    this.clientId = null;
                }
            });
        },
        
        // Load contacts for the current client
        async loadContacts() {
            if (!this.clientId) {
                this.contacts = [];
                return;
            }
            
            this.loadingContacts = true;
            
            try {
                const response = await fetch(`/clients/${this.clientId}/contacts`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (response.ok) {
                    this.contacts = await response.json();
                    this.filteredContacts = [...this.contacts];
                    console.log('Loaded contacts:', this.contacts.length);
                } else {
                    console.error('Failed to load contacts:', response.status);
                    this.contacts = [];
                }
            } catch (error) {
                console.error('Error loading contacts:', error);
                this.contacts = [];
            } finally {
                this.loadingContacts = false;
            }
        },
        
        // Open dropdown
        openDropdown() {
            if (this.contacts.length > 0) {
                this.open = true;
                this.filteredContacts = [...this.contacts];
                this.selectedIndex = -1;
            }
        },
        
        // Search contacts
        search() {
            if (!this.searchQuery.trim()) {
                this.filteredContacts = [...this.contacts];
                this.openDropdown();
                return;
            }
            
            const query = this.searchQuery.toLowerCase();
            this.filteredContacts = this.contacts.filter(contact => 
                contact.name.toLowerCase().includes(query) ||
                contact.email.toLowerCase().includes(query)
            );
            
            this.open = this.filteredContacts.length > 0;
            this.selectedIndex = -1;
        },
        
        // Handle keyboard navigation
        onKeyDown(event) {
            if (!this.open) {
                if (event.key === 'ArrowDown' || event.key === 'Enter') {
                    this.openDropdown();
                    event.preventDefault();
                }
                return;
            }
            
            switch (event.key) {
                case 'ArrowDown':
                    this.selectedIndex = Math.min(this.selectedIndex + 1, this.filteredContacts.length - 1);
                    event.preventDefault();
                    break;
                case 'ArrowUp':
                    this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                    event.preventDefault();
                    break;
                case 'Enter':
                    if (this.selectedIndex >= 0 && this.filteredContacts[this.selectedIndex]) {
                        this.selectContact(this.filteredContacts[this.selectedIndex]);
                    }
                    event.preventDefault();
                    break;
                case 'Escape':
                    this.close();
                    event.preventDefault();
                    break;
            }
        },
        
        // Select a contact
        selectContact(contact) {
            if (!contact) return;
            
            this.selectedContact = contact;
            this.selectedContactId = contact.id;
            this.searchQuery = contact.name;
            this.close();
            
            console.log('Contact selected:', contact);
            
            // Dispatch change event
            this.$dispatch('contact-selected', { contact });
        },
        
        // Clear selection
        clearSelection() {
            this.selectedContact = null;
            this.selectedContactId = '';
            this.searchQuery = '';
            this.close();
            
            // Dispatch change event
            this.$dispatch('contact-cleared');
        },
        
        // Clear all
        clearAll() {
            this.clearSelection();
            this.contacts = [];
            this.filteredContacts = [];
        },
        
        // Close dropdown
        close() {
            this.open = false;
            this.selectedIndex = -1;
        },
        
        // Update contacts when client changes (called from parent)
        updateClientId(newClientId) {
            this.clientId = newClientId;
        }
    };
};