@props(['field', 'value' => null, 'errors' => []])

@php
    $fieldSlug = $field['field_slug'];
    $hasError = !empty($errors[$fieldSlug]);
    $uiConfig = $field['ui_config'] ?? [];
    $selectClass = 'block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm' . ($hasError ? ' border-red-500' : '');
    
    // Extract UI configuration
    $placeholder = $field['placeholder'] ?? 'Search for users...';
    $multiple = $uiConfig['multiple'] ?? false;
    $ajaxUrl = $field['ajax_url'] ?? route('api.users.search');
    $roles = $uiConfig['roles'] ?? []; // Filter by specific roles
    $departments = $uiConfig['departments'] ?? []; // Filter by departments
    $excludeSelf = $uiConfig['exclude_self'] ?? false; // Exclude current user
    $includeInactive = $uiConfig['include_inactive'] ?? false; // Include inactive users
    $showAvatar = $uiConfig['show_avatar'] ?? true; // Show user avatars
    $showOnlineStatus = $uiConfig['show_online_status'] ?? false; // Show online indicator
    
    // Get selected users if value is provided
    $selectedUsers = collect();
    if ($value) {
        if ($multiple) {
            $userIds = is_array($value) ? $value : [$value];
        } else {
            $userIds = [$value];
        }
        
        $selectedUsers = \App\Domains\Core\Models\User::where('company_id', auth()->user()->company_id)
            ->whereIn('id', $userIds)
            ->get();
    }
@endphp

<div class="mb-4 mb-6">
    <label for="{{ $fieldSlug }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
        {{ $field['label'] }}
        @if($field['is_required'])
            <span class="text-red-600 dark:text-red-400">*</span>
        @endif
    </label>
    
    <div class="user-selector-container mx-auto">
        <select 
            id="{{ $fieldSlug }}"
            name="{{ $fieldSlug }}{{ $multiple ? '[]' : '' }}"
            class="{{ $selectClass }} user-selector"
            @if($field['is_required']) required @endif
            @if($multiple) multiple @endif
            data-placeholder="{{ $placeholder }}"
            data-ajax-url="{{ $ajaxUrl }}"
            @if(!empty($roles)) data-roles="{{ implode(',', $roles) }}" @endif
            @if(!empty($departments)) data-departments="{{ implode(',', $departments) }}" @endif
            @if($excludeSelf) data-exclude-self="true" @endif
            @if($includeInactive) data-include-inactive="true" @endif
        >
            @foreach($selectedUsers as $user)
                <option value="{{ $user->id }}" selected>
                    {{ $user->name }}
                    @if($user->email)
                        ({{ $user->email }})
                    @endif
                </option>
            @endforeach
            
            @if(!$field['is_required'] && !$multiple)
                <option value="">{{ $placeholder }}</option>
            @endif
        </select>
        
        {{-- Quick User Buttons (for common selections) --}}
        @if($uiConfig['show_quick_select'] ?? false)
            <div class="quick-select-users mt-2">
                <small class="text-gray-600 dark:text-gray-400">Quick select:</small>
                <div class="px-6 py-2 font-medium rounded-md transition-colors-group-sm mt-1">
                    <button type="button" class="btn border border-gray-600 text-gray-600 hover:bg-gray-50 px-6 py-2 font-medium rounded-md transition-colors-sm mr-1" data-user-type="managers">
                        <i class="fas fa-user-tie"></i> Managers
                    </button>
                    <button type="button" class="btn border border-gray-600 text-gray-600 hover:bg-gray-50 px-6 py-2 font-medium rounded-md transition-colors-sm mr-1" data-user-type="technicians">
                        <i class="fas fa-tools"></i> Technicians
                    </button>
                    <button type="button" class="btn border border-gray-600 text-gray-600 hover:bg-gray-50 px-6 py-2 font-medium rounded-md transition-colors-sm" data-user-type="all">
                        <i class="fas fa-users"></i> All Active
                    </button>
                </div>
            </div>
        @endif
    </div>
    
    @if($field['help_text'])
        <small class="form-text text-gray-600 dark:text-gray-400">{{ $field['help_text'] }}</small>
    @endif
    
    @if($hasError)
        <div class="text-red-600 text-sm mt-1">
            @foreach($errors[$fieldSlug] as $error)
                {{ $error }}
            @endforeach
        </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const userSelect = document.getElementById('{{ $fieldSlug }}');
        const roles = userSelect.dataset.roles ? userSelect.dataset.roles.split(',') : [];
        const departments = userSelect.dataset.departments ? userSelect.dataset.departments.split(',') : [];
        const excludeSelf = userSelect.hasAttribute('data-exclude-self');
        const includeInactive = userSelect.hasAttribute('data-include-inactive');
        const showAvatar = {{ $showAvatar ? 'true' : 'false' }};
        const showOnlineStatus = {{ $showOnlineStatus ? 'true' : 'false' }};
        
        const tomSelect = new TomSelect(userSelect, {
            valueField: 'id',
            labelField: 'name',
            searchField: ['name', 'email', 'job_title'],
            placeholder: '{{ $placeholder }}',
            @if($multiple)
            plugins: ['remove_button'],
            @endif
            load: function(query, callback) {
                if (!query.length) return callback();
                
                let url = `{{ $ajaxUrl }}?search=${encodeURIComponent(query)}`;
                
                if (roles.length > 0) {
                    url += `&roles=${roles.join(',')}`;
                }
                
                if (departments.length > 0) {
                    url += `&departments=${departments.join(',')}`;
                }
                
                if (excludeSelf) {
                    url += `&exclude_self=1`;
                }
                
                if (includeInactive) {
                    url += `&include_inactive=1`;
                }
                
                fetch(url)
                    .then(response => response.json())
                    .then(json => {
                        callback(json.data || json);
                    })
                    .catch(() => {
                        callback();
                    });
            },
            render: {
                option: function(item, escape) {
                    let html = '<div class="flex items-center py-1">';
                    
                    // Avatar
                    if (showAvatar) {
                        const avatarUrl = item.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(item.name)}&size=32&background=random`;
                        html += `<img src="${avatarUrl}" class="rounded-full mr-2" width="32" height="32" alt="${escape(item.name)}">`;
                    }
                    
                    html += '<div class="flex-grow-1">';
                    html += `<div class="fw-medium">${escape(item.name)}`;
                    
                    // Online status
                    if (showOnlineStatus && item.is_online !== undefined) {
                        const statusClass = item.is_online ? 'text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400';
                        const statusIcon = item.is_online ? 'circle' : 'circle';
                        html += ` <i class="fas fa-${statusIcon} ${statusClass}" style="font-size: 0.5rem;"></i>`;
                    }
                    
                    html += '</div>';
                    
                    // User details
                    const details = [];
                    if (item.email) details.push(escape(item.email));
                    if (item.job_title) details.push(escape(item.job_title));
                    if (item.department) details.push(escape(item.department));
                    
                    if (details.length > 0) {
                        html += `<small class="text-gray-600 dark:text-gray-400">${details.join(' • ')}</small>`;
                    }
                    
                    html += '</div>';
                    
                    // Status badges
                    html += '<div class="text-end">';
                    if (item.role) {
                        html += `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary mr-1">${escape(item.role)}</span>`;
                    }
                    if (!item.is_active) {
                        html += '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-secondary">Inactive</span>';
                    }
                    html += '</div>';
                    
                    html += '</div>';
                    return html;
                },
                item: function(item, escape) {
                    let html = '<div class="flex items-center">';
                    
                    if (showAvatar) {
                        const avatarUrl = item.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(item.name)}&size=20&background=random`;
                        html += `<img src="${avatarUrl}" class="rounded-full mr-1" width="20" height="20" alt="${escape(item.name)}">`;
                    }
                    
                    html += `<span>${escape(item.name)}</span>`;
                    
                    if (showOnlineStatus && item.is_online !== undefined) {
                        const statusClass = item.is_online ? 'text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400';
                        html += ` <i class="fas fa-circle ${statusClass}" style="font-size: 0.4rem;"></i>`;
                    }
                    
                    html += '</div>';
                    return html;
                }
            }
        });
        
        // Quick select functionality
        const quickSelectButtons = document.querySelectorAll('[data-user-type]');
        quickSelectButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userType = this.dataset.userType;
                let url = '{{ $ajaxUrl }}?quick_select=' + userType;
                
                if (excludeSelf) {
                    url += '&exclude_self=1';
                }
                
                fetch(url)
                    .then(response => response.json())
                    .then(json => {
                        const users = json.data || json;
                        
                        if (!{{ $multiple ? 'true' : 'false' }}) {
                            tomSelect.clear();
                        }
                        
                        users.forEach(user => {
                            tomSelect.addOption(user);
                            tomSelect.addItem(user.id, true);
                        });
                    })
                    .catch(error => {
                        console.error('Quick select failed:', error);
                    });
            });
        });
        
        // Real-time online status updates (if enabled)
        @if($showOnlineStatus)
        function updateOnlineStatus() {
            const selectedUsers = tomSelect.items;
            if (selectedUsers.length === 0) return;
            
            fetch('{{ route('api.users.online-status') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ user_ids: selectedUsers })
            })
            .then(response => response.json())
            .then(data => {
                // Update the displayed items with new online status
                data.forEach(user => {
                    const option = tomSelect.options[user.id];
                    if (option) {
                        option.is_online = user.is_online;
                        tomSelect.updateOption(user.id, option);
                    }
                });
            })
            .catch(error => {
                console.error('Online status update failed:', error);
            });
        }
        
        // Update online status every 30 seconds
        setInterval(updateOnlineStatus, 30000);
        @endif
        
        // Form validation for required field
        @if($field['is_required'])
        const form = userSelect.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (tomSelect.items.length === 0) {
                    e.preventDefault();
                    
                    // Show validation error
                    const container = userSelect.closest('.mb-4');
                    let errorDiv = container.querySelector('.user-required-error');
                    
                    if (!errorDiv) {
                        errorDiv = document.createElement('div');
                        errorDiv.className = 'user-required-error text-red-600 text-sm mt-1 block';
                        errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please select at least one user.';
                        container.appendChild(errorDiv);
                    }
                    
                    userSelect.focus();
                    return false;
                } else {
                    // Remove validation error
                    const container = userSelect.closest('.mb-4');
                    const errorDiv = container.querySelector('.user-required-error');
                    if (errorDiv) {
                        errorDiv.remove();
                    }
                }
            });
        }
        @endif
        
        // Accessibility improvements
        tomSelect.control_input.setAttribute('aria-label', '{{ $field['label'] }}');
        tomSelect.control_input.setAttribute('aria-describedby', '{{ $fieldSlug }}_help');
        
        // Keyboard shortcuts
        userSelect.addEventListener('keydown', function(e) {
            // Ctrl+A or Cmd+A to select all (if multiple)
            @if($multiple)
            if ((e.ctrlKey || e.metaKey) && e.key === 'a') {
                e.preventDefault();
                
                // Load all users and select them
                fetch('{{ $ajaxUrl }}?all=1')
                    .then(response => response.json())
                    .then(json => {
                        const users = json.data || json;
                        users.forEach(user => {
                            tomSelect.addOption(user);
                            tomSelect.addItem(user.id, true);
                        });
                    });
            }
            @endif
        });
    });
</script>
@endpush
