// Enhanced contract clauses functionality with template variable support
window.contractClauses = function() {
    return {
        viewMode: 'information',
        showCreateModal: false,
        bulkSelection: [],
        showBulkActions: false,
        editingClause: null,
        editingContent: '',
        
        init() {
            this.initializeVariableReference();
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
        },
        
        initializeVariableReference() {
            // Initialize after DOM is loaded
            document.addEventListener('DOMContentLoaded', () => {
                this.setupVariableReferencePanel();
            });
            
            // If DOM is already loaded
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    this.setupVariableReferencePanel();
                });
            } else {
                this.setupVariableReferencePanel();
            }
        },
        
        setupVariableReferencePanel() {
            // Toggle panel visibility
            const toggleBtn = document.getElementById('toggleVariableReference');
            const panel = document.getElementById('variableReferencePanel');
            const searchInput = document.getElementById('variableSearch');
            
            if (toggleBtn && panel) {
                toggleBtn.addEventListener('click', () => {
                    panel.classList.toggle('hidden');
                    if (!panel.classList.contains('hidden')) {
                        searchInput?.focus();
                    }
                });
            }
            
            // Setup search functionality
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    this.filterVariables(e.target.value);
                });
            }
            
            // Setup variable insertion
            this.setupVariableInsertion();
        },
        
        setupVariableInsertion() {
            const panel = document.getElementById('variableReferencePanel');
            const contentTextarea = document.getElementById('content');
            
            if (!panel || !contentTextarea) return;
            
            // Variable items
            panel.addEventListener('click', (e) => {
                const variableItem = e.target.closest('.variable-item');
                const formatterItem = e.target.closest('.formatter-item');
                const conditionalItem = e.target.closest('.conditional-item');
                
                if (variableItem) {
                    const variableName = variableItem.dataset.variable;
                    this.insertAtCursor(contentTextarea, `{{${variableName}}}`);
                } else if (formatterItem) {
                    // Show example for formatter
                    const formatter = formatterItem.dataset.formatter;
                    this.showFormatterExample(formatter);
                } else if (conditionalItem) {
                    const syntax = conditionalItem.dataset.syntax;
                    this.insertAtCursor(contentTextarea, syntax);
                }
            });
            
            // Double-click to insert with cursor selection
            panel.addEventListener('dblclick', (e) => {
                const variableItem = e.target.closest('.variable-item');
                if (variableItem) {
                    const variableName = variableItem.dataset.variable;
                    this.insertAtCursor(contentTextarea, `{{${variableName}}}`, true);
                }
            });
        },
        
        insertAtCursor(textarea, text, selectInserted = false) {
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const currentValue = textarea.value;
            
            // Insert text
            const newValue = currentValue.substring(0, start) + text + currentValue.substring(end);
            textarea.value = newValue;
            
            // Position cursor
            if (selectInserted) {
                textarea.setSelectionRange(start, start + text.length);
            } else {
                textarea.setSelectionRange(start + text.length, start + text.length);
            }
            
            textarea.focus();
            this.autoResize(textarea);
            
            // Trigger change event
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
        },
        
        filterVariables(searchTerm) {
            const categories = document.querySelectorAll('.variable-category');
            const lowerSearch = searchTerm.toLowerCase();
            
            categories.forEach(category => {
                const variables = category.querySelectorAll('.variable-item');
                let hasVisibleItems = false;
                
                variables.forEach(variable => {
                    const variableName = variable.dataset.variable.toLowerCase();
                    const label = variable.dataset.label.toLowerCase();
                    const example = variable.dataset.example.toLowerCase();
                    
                    const isVisible = !searchTerm || 
                                    variableName.includes(lowerSearch) || 
                                    label.includes(lowerSearch) || 
                                    example.includes(lowerSearch);
                    
                    variable.style.display = isVisible ? 'block' : 'none';
                    if (isVisible) hasVisibleItems = true;
                });
                
                // Hide category if no visible items
                category.style.display = hasVisibleItems ? 'block' : 'none';
            });
            
            // Also filter formatters and conditionals
            this.filterFormatterItems(searchTerm);
            this.filterConditionalItems(searchTerm);
        },
        
        filterFormatterItems(searchTerm) {
            const formatters = document.querySelectorAll('.formatter-item');
            const lowerSearch = searchTerm.toLowerCase();
            
            formatters.forEach(formatter => {
                const formatterName = formatter.dataset.formatter?.toLowerCase() || '';
                const textContent = formatter.textContent.toLowerCase();
                
                const isVisible = !searchTerm || 
                                formatterName.includes(lowerSearch) || 
                                textContent.includes(lowerSearch);
                
                formatter.style.display = isVisible ? 'block' : 'none';
            });
        },
        
        filterConditionalItems(searchTerm) {
            const conditionals = document.querySelectorAll('.conditional-item');
            const lowerSearch = searchTerm.toLowerCase();
            
            conditionals.forEach(conditional => {
                const textContent = conditional.textContent.toLowerCase();
                const isVisible = !searchTerm || textContent.includes(lowerSearch);
                conditional.style.display = isVisible ? 'block' : 'none';
            });
        },
        
        showFormatterExample(formatter) {
            // Create a tooltip or modal showing formatter usage
            const examples = {
                'upper': 'Example: {{client_name|upper}} → ACME CORP',
                'lower': 'Example: {{client_name|lower}} → acme corp',
                'currency': 'Example: {{monthly_rate|currency}} → $2,500.00',
                'date': 'Example: {{effective_date|date}} → January 1, 2024',
                // Add more examples as needed
            };
            
            const example = examples[formatter] || `Formatter: |${formatter}`;
            
            // Simple alert for now - could be enhanced with proper tooltips
            alert(example);
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