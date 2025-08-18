<div class="p-6">
    <div class="space-y-6">
        <!-- Role Management -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">User Roles & Permissions</h3>
                <button type="button" 
                        onclick="createRole()"
                        class="px-4 py-2 bg-blue-600-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-600-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    Create Role
                </button>
            </div>

            <!-- Roles List -->
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    <li>
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Admin
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <h4 class="text-sm font-medium text-gray-900">Super Administrator</h4>
                                        <p class="text-sm text-gray-500">Full system access and control</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-500">{{ $userCounts['super_admin'] ?? 1 }} users</span>
                                    <button onclick="editRole('super_admin')" class="text-blue-600-600 hover:text-blue-600-900 text-sm font-medium">
                                        Edit
                                    </button>
                                </div>
                            </div>
                        </div>
                    </li>

                    <li>
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Manager
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <h4 class="text-sm font-medium text-gray-900">Manager</h4>
                                        <p class="text-sm text-gray-500">Manage teams, clients, and projects</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-500">{{ $userCounts['manager'] ?? 3 }} users</span>
                                    <button onclick="editRole('manager')" class="text-primary-600 hover:text-primary-900 text-sm font-medium">
                                        Edit
                                    </button>
                                </div>
                            </div>
                        </div>
                    </li>

                    <li>
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Technician
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <h4 class="text-sm font-medium text-gray-900">Technician</h4>
                                        <p class="text-sm text-gray-500">Handle tickets and client support</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-500">{{ $userCounts['technician'] ?? 8 }} users</span>
                                    <button onclick="editRole('technician')" class="text-primary-600 hover:text-primary-900 text-sm font-medium">
                                        Edit
                                    </button>
                                </div>
                            </div>
                        </div>
                    </li>

                    <li>
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Client
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <h4 class="text-sm font-medium text-gray-900">Client User</h4>
                                        <p class="text-sm text-gray-500">View own tickets and submit requests</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-500">{{ $userCounts['client'] ?? 45 }} users</span>
                                    <button onclick="editRole('client')" class="text-primary-600 hover:text-primary-900 text-sm font-medium">
                                        Edit
                                    </button>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Permission Categories -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Permission Categories</h3>
            
            <div class="space-y-6">
                <!-- Core System Permissions -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-md font-medium text-gray-900 mb-3">Core System</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_system_admin" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_system_admin" class="ml-2 text-sm text-gray-700">System Administration</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_user_management" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_user_management" class="ml-2 text-sm text-gray-700">User Management</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_company_settings" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_company_settings" class="ml-2 text-sm text-gray-700">Company Settings</label>
                        </div>
                    </div>
                </div>

                <!-- Client Management Permissions -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-md font-medium text-gray-900 mb-3">Client Management</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_client_create" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_client_create" class="ml-2 text-sm text-gray-700">Create Clients</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_client_edit" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_client_edit" class="ml-2 text-sm text-gray-700">Edit Clients</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_client_delete" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_client_delete" class="ml-2 text-sm text-gray-700">Delete Clients</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_client_view_all" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_client_view_all" class="ml-2 text-sm text-gray-700">View All Clients</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_client_assets" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_client_assets" class="ml-2 text-sm text-gray-700">Manage Assets</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_client_contacts" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_client_contacts" class="ml-2 text-sm text-gray-700">Manage Contacts</label>
                        </div>
                    </div>
                </div>

                <!-- Ticket Management Permissions -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-md font-medium text-gray-900 mb-3">Ticket Management</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_ticket_create" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_ticket_create" class="ml-2 text-sm text-gray-700">Create Tickets</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_ticket_edit" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_ticket_edit" class="ml-2 text-sm text-gray-700">Edit Tickets</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_ticket_delete" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_ticket_delete" class="ml-2 text-sm text-gray-700">Delete Tickets</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_ticket_assign" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_ticket_assign" class="ml-2 text-sm text-gray-700">Assign Tickets</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_ticket_view_all" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_ticket_view_all" class="ml-2 text-sm text-gray-700">View All Tickets</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_ticket_billing" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_ticket_billing" class="ml-2 text-sm text-gray-700">Manage Billing</label>
                        </div>
                    </div>
                </div>

                <!-- Financial Permissions -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-md font-medium text-gray-900 mb-3">Financial Management</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_invoice_create" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_invoice_create" class="ml-2 text-sm text-gray-700">Create Invoices</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_invoice_edit" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_invoice_edit" class="ml-2 text-sm text-gray-700">Edit Invoices</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_invoice_delete" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_invoice_delete" class="ml-2 text-sm text-gray-700">Delete Invoices</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_payment_process" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_payment_process" class="ml-2 text-sm text-gray-700">Process Payments</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_financial_reports" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_financial_reports" class="ml-2 text-sm text-gray-700">View Financial Reports</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_pricing_management" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_pricing_management" class="ml-2 text-sm text-gray-700">Manage Pricing</label>
                        </div>
                    </div>
                </div>

                <!-- Project Management Permissions -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-md font-medium text-gray-900 mb-3">Project Management</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_project_create" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_project_create" class="ml-2 text-sm text-gray-700">Create Projects</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_project_edit" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_project_edit" class="ml-2 text-sm text-gray-700">Edit Projects</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_project_delete" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_project_delete" class="ml-2 text-sm text-gray-700">Delete Projects</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_project_assign" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_project_assign" class="ml-2 text-sm text-gray-700">Assign Team Members</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_time_tracking" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_time_tracking" class="ml-2 text-sm text-gray-700">Time Tracking</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_project_reports" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_project_reports" class="ml-2 text-sm text-gray-700">Project Reports</label>
                        </div>
                    </div>
                </div>

                <!-- Reporting Permissions -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-md font-medium text-gray-900 mb-3">Reporting & Analytics</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_view_dashboard" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_view_dashboard" class="ml-2 text-sm text-gray-700">View Dashboard</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_basic_reports" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_basic_reports" class="ml-2 text-sm text-gray-700">Basic Reports</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_advanced_reports" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_advanced_reports" class="ml-2 text-sm text-gray-700">Advanced Reports</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_export_data" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_export_data" class="ml-2 text-sm text-gray-700">Export Data</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_audit_logs" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_audit_logs" class="ml-2 text-sm text-gray-700">View Audit Logs</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_custom_reports" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="perm_custom_reports" class="ml-2 text-sm text-gray-700">Create Custom Reports</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Access Control Settings -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Access Control Settings</h3>
            <div class="space-y-4">
                <div class="flex items-center">
                    <input type="checkbox" 
                           id="enforce_ip_restrictions"
                           name="enforce_ip_restrictions"
                           value="1"
                           {{ old('enforce_ip_restrictions', $settings['enforce_ip_restrictions'] ?? false) ? 'checked' : '' }}
                           class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="enforce_ip_restrictions" class="ml-3 block text-sm font-medium text-gray-700">
                        Enforce IP Address Restrictions
                    </label>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" 
                           id="require_approval_new_users"
                           name="require_approval_new_users"
                           value="1"
                           {{ old('require_approval_new_users', $settings['require_approval_new_users'] ?? true) ? 'checked' : '' }}
                           class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="require_approval_new_users" class="ml-3 block text-sm font-medium text-gray-700">
                        Require approval for new user accounts
                    </label>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" 
                           id="auto_assign_client_role"
                           name="auto_assign_client_role"
                           value="1"
                           {{ old('auto_assign_client_role', $settings['auto_assign_client_role'] ?? true) ? 'checked' : '' }}
                           class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="auto_assign_client_role" class="ml-3 block text-sm font-medium text-gray-700">
                        Automatically assign client role to new users
                    </label>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" 
                           id="log_permission_changes"
                           name="log_permission_changes"
                           value="1"
                           {{ old('log_permission_changes', $settings['log_permission_changes'] ?? true) ? 'checked' : '' }}
                           class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="log_permission_changes" class="ml-3 block text-sm font-medium text-gray-700">
                        Log all permission and role changes
                    </label>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Form Actions -->
    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
        <button type="button" 
                onclick="exportPermissions()"
                class="px-4 py-2 bg-gray-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
            Export Roles
        </button>
        <button type="submit" 
                class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Save Permissions
        </button>
    </div>
</div>

<script>
function createRole() {
    console.log('Creating new role...');
    alert('Role creation dialog would be implemented here');
}

function editRole(roleKey) {
    console.log(`Editing role: ${roleKey}`);
    alert(`Role editor for ${roleKey} would be implemented here`);
}

function exportPermissions() {
    console.log('Exporting permissions...');
    alert('Permission export functionality would be implemented here');
}
</script>