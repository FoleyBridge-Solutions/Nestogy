<div class="p-6">
    <div class="space-y-6">
        <!-- Audit Log Settings -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Audit Log Configuration</h3>
            <div class="space-y-4">
                <div class="flex items-center">
                    <input type="checkbox" id="enable_audit_logging" name="enable_audit_logging" value="1" checked class="h-4 w-4 text-blue-600-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="enable_audit_logging" class="ml-3 block text-sm font-medium text-gray-700">Enable comprehensive audit logging</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="log_login_attempts" name="log_login_attempts" value="1" checked class="h-4 w-4 text-blue-600-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="log_login_attempts" class="ml-3 block text-sm font-medium text-gray-700">Log all login attempts</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="log_data_changes" name="log_data_changes" value="1" checked class="h-4 w-4 text-blue-600 dark:text-blue-400-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="log_data_changes" class="ml-3 block text-sm font-medium text-gray-700">Log all data modifications</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="log_file_access" name="log_file_access" value="1" class="h-4 w-4 text-blue-600 dark:text-blue-400-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="log_file_access" class="ml-3 block text-sm font-medium text-gray-700">Log file downloads and uploads</label>
                </div>
            </div>
        </div>

        <!-- Recent Audit Logs -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Activity</h3>
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    <li class="px-4 py-4">
                        <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">LOGIN</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900">User john.doe logged in successfully</p>
                                <p class="text-sm text-gray-500">IP: 192.168.1.100 • 2 minutes ago</p>
                            </div>
                        </div>
                    </li>
                    <li class="px-4 py-4">
                        <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">CREATE</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900">New client "Acme Corp" created</p>
                                <p class="text-sm text-gray-500">By: admin • 15 minutes ago</p>
                            </div>
                        </div>
                    </li>
                    <li class="px-4 py-4">
                        <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">UPDATE</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900">Invoice #1234 status changed to "Paid"</p>
                                <p class="text-sm text-gray-500">By: jane.smith • 1 hour ago</p>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Retention Settings -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Log Retention</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="audit_retention_days" class="block text-sm font-medium text-gray-700 mb-1">
                        Retention Period (Days)
                    </label>
                    <select id="audit_retention_days" name="audit_retention_days" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="30">30 Days</option>
                        <option value="90" selected>90 Days</option>
                        <option value="180">180 Days</option>
                        <option value="365">1 Year</option>
                        <option value="2555">7 Years (Compliance)</option>
                    </select>
                </div>
                <div>
                    <label for="audit_archive_location" class="block text-sm font-medium text-gray-700 mb-1">
                        Archive Location
                    </label>
                    <select id="audit_archive_location" name="audit_archive_location" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="local">Local Storage</option>
                        <option value="s3">Amazon S3</option>
                        <option value="azure">Azure Blob</option>
                        <option value="gcs">Google Cloud Storage</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
        <button type="button" class="px-4 py-2 bg-gray-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-gray-700">
            Export Logs
        </button>
        <button type="submit" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Save Audit Settings
        </button>
    </div>
</div>
