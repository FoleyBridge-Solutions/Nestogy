@props([
    'companies' => [],
    'rootCompanyId' => null,
    'selectedCompanyId' => null,
    'showActions' => true,
    'interactive' => true
])

<div {{ $attributes->merge(['class' => 'hierarchy-tree']) }}>
    @if(count($companies) > 0)
        <div class="space-y-2">
            @foreach($companies as $company)
                @if(!$company['parent_id'] || $company['parent_id'] == $rootCompanyId)
                    <x-subsidiary.hierarchy-node 
                        :company="$company"
                        :companies="$companies"
                        :selected-company-id="$selectedCompanyId"
                        :show-actions="$showActions"
                        :interactive="$interactive"
                        :level="0"
                    />
                @endif
            @endforeach
        </div>
    @else
        <div class="text-center py-8 text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No subsidiaries</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating your first subsidiary company.</p>
            @if($showActions)
                <div class="mt-6">
                    <a href="{{ route('subsidiaries.create') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Create Subsidiary
                    </a>
                </div>
            @endif
        </div>
    @endif
</div>

@if($interactive)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize hierarchy tree interactions
            initializeHierarchyTree();
        });

        function initializeHierarchyTree() {
            // Company selection handling
            document.querySelectorAll('.hierarchy-node').forEach(node => {
                node.addEventListener('click', function(e) {
                    if (e.target.closest('.action-button')) {
                        return; // Don't select when clicking action buttons
                    }
                    
                    // Remove previous selection
                    document.querySelectorAll('.hierarchy-node').forEach(n => n.classList.remove('selected'));
                    
                    // Add selection to current node
                    this.classList.add('selected');
                    
                    // Emit selection event
                    const companyId = this.dataset.companyId;
                    const event = new CustomEvent('companySelected', {
                        detail: { companyId: companyId }
                    });
                    document.dispatchEvent(event);
                });
            });

            // Expand/collapse handling
            document.querySelectorAll('.expand-toggle').forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    
                    const node = this.closest('.hierarchy-node');
                    const children = node.querySelector('.child-nodes');
                    const icon = this.querySelector('.expand-icon');
                    
                    if (children) {
                        const isExpanded = !children.classList.contains('hidden');
                        
                        if (isExpanded) {
                            children.classList.add('hidden');
                            icon.style.transform = 'rotate(0deg)';
                        } else {
                            children.classList.remove('hidden');
                            icon.style.transform = 'rotate(90deg)';
                        }
                    }
                });
            });
        }
    </script>

    <style>
        .hierarchy-tree .hierarchy-node {
            @apply cursor-pointer transition-colors duration-150;
        }
        
        .hierarchy-tree .hierarchy-node:hover {
            @apply bg-gray-50;
        }
        
        .hierarchy-tree .hierarchy-node.selected {
            @apply bg-blue-50 border-blue-300;
        }
        
        .hierarchy-tree .expand-icon {
            transition: transform 0.2s ease-in-out;
        }
        
        .hierarchy-tree .child-nodes {
            @apply border-l-2 border-gray-200 ml-4 pl-4;
        }
    </style>
@endif