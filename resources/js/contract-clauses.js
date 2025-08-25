// Simple contract clauses functionality
window.contractClauses = function() {
    return {
        viewMode: 'information',
        showCreateModal: false,
        bulkSelection: [],
        showBulkActions: false,
        editingClause: null,
        editingContent: '',
        
        init() {
            console.log('Contract clauses initialized');
        },
        
        startEditing(clause) {
            if (clause.is_system && !window.userIsSuperAdmin) {
                alert('System clauses cannot be edited.');
                return;
            }
            this.editingClause = clause.id;
            this.editingContent = clause.content;
            this.$nextTick(() => {
                const textarea = this.$refs['content-' + clause.id];
                if (textarea) {
                    textarea.focus();
                    this.autoResize(textarea);
                }
            });
        },
        
        cancelEditing() {
            this.editingClause = null;
            this.editingContent = '';
        },
        
        async saveContent(clause) {
            if (this.editingContent.trim() === '') {
                alert('Content cannot be empty.');
                return;
            }
            
            try {
                const response = await fetch('/settings/contract-clauses/' + clause.id + '/update-content', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        content: this.editingContent
                    })
                });
                
                if (response.ok) {
                    clause.content = this.editingContent;
                    this.editingClause = null;
                    this.editingContent = '';
                    alert('Clause updated successfully!');
                    location.reload(); // Simple refresh to show changes
                } else {
                    throw new Error('Failed to save');
                }
            } catch (error) {
                alert('Failed to save changes. Please try again.');
            }
        },
        
        autoResize(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        }
    };
};

// Simple keyboard shortcuts
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        // Trigger Alpine event to cancel editing
        window.dispatchEvent(new CustomEvent('cancel-editing'));
    }
});