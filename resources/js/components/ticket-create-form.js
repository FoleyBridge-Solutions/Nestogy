export function ticketCreateForm() {
    return {
        // Form data
        clientId: '',
        contactId: '',
        subject: '',
        priority: 'Medium',
        assignedTo: '',
        assetId: '',
        details: '',
        vendorId: '',
        vendorTicketNumber: '',
        billable: '0',
        
        // Loading states
        loadingContacts: false,
        loadingAssets: false,
        submitting: false,
        
        // Data arrays
        contacts: [],
        assets: [],
        
        init() {
            // Listen for client selection events
            this.$watch('clientId', (value) => {
                if (value) {
                    this.loadClientData(value);
                } else {
                    this.clearClientData();
                }
            });
            
            // Listen for Alpine client selection events
            window.addEventListener('client-selected', (event) => {
                const client = event.detail.client;
                if (client && client.id) {
                    this.clientId = client.id;
                    this.loadClientData(client.id);
                }
            });
            
            // Listen for client cleared events
            window.addEventListener('client-cleared', () => {
                this.clearClientData();
            });
        },
        
        async loadClientData(clientId) {
            if (!clientId) return;
            
            // Load contacts and assets in parallel
            await Promise.all([
                this.loadContacts(clientId),
                this.loadAssets(clientId)
            ]);
        },
        
        async loadContacts(clientId) {
            this.loadingContacts = true;
            this.contacts = [];
            this.contactId = '';
            
            try {
                const response = await fetch(`/clients/${clientId}/contacts`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (response.ok) {
                    this.contacts = await response.json();
                    this.updateContactSelect();
                    
                    // Auto-select primary contact if available
                    const primaryContact = this.contacts.find(c => c.primary);
                    if (primaryContact) {
                        this.contactId = primaryContact.id;
                    }
                } else {
                    console.error('Failed to load contacts:', response.status);
                    this.showError('Failed to load client contacts');
                }
            } catch (error) {
                console.error('Error loading contacts:', error);
                this.showError('Failed to load client contacts');
            } finally {
                this.loadingContacts = false;
            }
        },
        
        async loadAssets(clientId) {
            this.loadingAssets = true;
            this.assets = [];
            this.assetId = '';
            
            try {
                const response = await fetch(`/clients/${clientId}/assets`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (response.ok) {
                    this.assets = await response.json();
                    this.updateAssetSelect();
                } else {
                    console.error('Failed to load assets:', response.status);
                    this.showError('Failed to load client assets');
                }
            } catch (error) {
                console.error('Error loading assets:', error);
                this.showError('Failed to load client assets');
            } finally {
                this.loadingAssets = false;
            }
        },
        
        clearClientData() {
            this.contactId = '';
            this.assetId = '';
            this.contacts = [];
            this.assets = [];
            this.updateContactSelect();
            this.updateAssetSelect();
        },
        
        // Update the contact select options
        updateContactSelect() {
            const contactSelect = document.getElementById('contact_id');
            if (contactSelect) {
                contactSelect.innerHTML = '<option value="">Select Contact</option>';
                this.contacts.forEach(contact => {
                    const option = document.createElement('option');
                    option.value = contact.id;
                    option.textContent = contact.name + (contact.primary ? ' (Primary)' : '');
                    contactSelect.appendChild(option);
                });
            }
        },
        
        // Update the asset select options
        updateAssetSelect() {
            const assetSelect = document.getElementById('asset_id');
            if (assetSelect) {
                assetSelect.innerHTML = '<option value="">Select Asset</option>';
                this.assets.forEach(asset => {
                    const option = document.createElement('option');
                    option.value = asset.id;
                    option.textContent = asset.name + (asset.type ? ' (' + asset.type + ')' : '');
                    assetSelect.appendChild(option);
                });
            }
        },
        
        // Form validation
        get isFormValid() {
            return this.clientId && 
                   this.subject && 
                   this.subject.trim().length > 0 &&
                   this.details && 
                   this.details.trim().length > 0 &&
                   this.priority;
        },
        
        // Priority badge classes
        get priorityClass() {
            const classes = {
                'Low': 'text-blue-700 bg-blue-50 border-blue-200',
                'Medium': 'text-yellow-700 bg-yellow-50 border-yellow-200',
                'High': 'text-orange-700 bg-orange-50 border-orange-200',
                'Critical': 'text-red-700 bg-red-50 border-red-200'
            };
            return classes[this.priority] || classes['Medium'];
        },
        
        // Show error message
        showError(message) {
            if (window.showAlert) {
                window.showAlert('error', 'Error', message);
            } else {
                alert('Error: ' + message);
            }
        },
        
        // Show success message
        showSuccess(message) {
            if (window.showAlert) {
                window.showAlert('success', 'Success', message);
            } else {
                alert(message);
            }
        },
        
        // Form submission with validation
        async submitForm(event) {
            if (!this.isFormValid) {
                event.preventDefault();
                this.showError('Please fill in all required fields');
                return false;
            }
            
            this.submitting = true;
            // Form will submit naturally via browser
        },
        
        // Keyboard shortcuts
        onKeyDown(event) {
            // Ctrl/Cmd + Enter to submit
            if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                if (this.isFormValid) {
                    this.$refs.form.submit();
                }
            }
            
            // Escape to cancel
            if (event.key === 'Escape') {
                if (confirm('Are you sure you want to cancel? Any unsaved changes will be lost.')) {
                    window.location.href = '/tickets';
                }
            }
        }
    };
}