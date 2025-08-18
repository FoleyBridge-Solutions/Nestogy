window.userSearchField = function(options = {}) {
    return {
        // Configuration
        name: options.name || 'assigned_to',
        selectedUser: options.selectedUser || null,
        
        // State
        searchQuery: '',
        selectedUserId: '',
        users: [],
        filteredUsers: [],
        open: false,
        selectedIndex: -1,
        loadingUsers: false,
        
        // Initialize
        init() {
            console.log('UserSearchField init');
            
            // Set initial values if user is pre-selected
            if (this.selectedUser && this.selectedUser.id) {
                console.log('Initial selected user:', this.selectedUser);
                this.searchQuery = this.selectedUser.name || '';
                this.selectedUserId = this.selectedUser.id;
            }
            
            // Load users on init
            this.loadUsers();
            
            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!this.$el.contains(e.target)) {
                    this.close();
                }
            });
        },
        
        // Load all active users
        async loadUsers() {
            this.loadingUsers = true;
            
            try {
                // Get users from the hidden fallback select element
                const existingSelect = document.querySelector('select[name="assigned_to_fallback"]');
                if (existingSelect) {
                    this.users = Array.from(existingSelect.options)
                        .filter(option => option.value && option.value !== '')
                        .map(option => ({
                            id: option.value,
                            name: option.textContent.trim(),
                            email: '', // Could be enhanced to include email
                            role: '' // Could be enhanced to include role
                        }));
                } else {
                    // Fallback: empty array if no select found
                    console.warn('No fallback select found for users');
                    this.users = [];
                }
                
                this.filteredUsers = [...this.users];
                console.log('Loaded users:', this.users.length);
                
            } catch (error) {
                console.error('Error loading users:', error);
                this.users = [];
            } finally {
                this.loadingUsers = false;
            }
        },
        
        // Open dropdown
        openDropdown() {
            if (this.users.length > 0) {
                this.open = true;
                this.filteredUsers = [...this.users];
                this.selectedIndex = -1;
            }
        },
        
        // Search users
        search() {
            if (!this.searchQuery.trim()) {
                this.filteredUsers = [...this.users];
                this.openDropdown();
                return;
            }
            
            const query = this.searchQuery.toLowerCase();
            this.filteredUsers = this.users.filter(user => 
                user.name.toLowerCase().includes(query) ||
                (user.email && user.email.toLowerCase().includes(query)) ||
                (user.role && user.role.toLowerCase().includes(query))
            );
            
            this.open = true; // Show dropdown even if no results to display "no results" message
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
            
            const totalItems = this.filteredUsers.length + 1; // +1 for "Unassigned" option
            
            switch (event.key) {
                case 'ArrowDown':
                    this.selectedIndex = Math.min(this.selectedIndex + 1, totalItems - 1);
                    if (this.selectedIndex >= this.filteredUsers.length) {
                        this.selectedIndex = -2; // "Unassigned" option
                    }
                    event.preventDefault();
                    break;
                case 'ArrowUp':
                    if (this.selectedIndex === -2) {
                        this.selectedIndex = this.filteredUsers.length - 1;
                    } else {
                        this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                    }
                    event.preventDefault();
                    break;
                case 'Enter':
                    if (this.selectedIndex === -2) {
                        this.clearSelection();
                    } else if (this.selectedIndex >= 0 && this.filteredUsers[this.selectedIndex]) {
                        this.selectUser(this.filteredUsers[this.selectedIndex]);
                    }
                    event.preventDefault();
                    break;
                case 'Escape':
                    this.close();
                    event.preventDefault();
                    break;
            }
        },
        
        // Select a user
        selectUser(user) {
            if (!user) return;
            
            this.selectedUser = user;
            this.selectedUserId = user.id;
            this.searchQuery = user.name;
            this.close();
            
            console.log('User selected:', user);
            
            // Dispatch change event
            this.$dispatch('user-selected', { user });
        },
        
        // Clear selection (set to unassigned)
        clearSelection() {
            this.selectedUser = null;
            this.selectedUserId = '';
            this.searchQuery = 'Unassigned';
            this.close();
            
            // Dispatch change event
            this.$dispatch('user-cleared');
        },
        
        // Close dropdown
        close() {
            this.open = false;
            this.selectedIndex = -1;
        }
    };
};