<div class="p-6">
    <div class="space-y-6">
        <!-- Logo & Branding -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Logo & Branding</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Company Logo</label>
                    <div class="border-2 border-gray-300 border-dashed rounded-lg p-6 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-600">Upload your company logo</p>
                        <button type="button" class="mt-2 px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">Browse Files</button>
                    </div>
                </div>
                <div>
                    <label for="primary_color" class="block text-sm font-medium text-gray-700 mb-1">Primary Brand Color</label>
                    <input type="color" id="primary_color" name="primary_color" value="#3B82F6" class="h-10 w-20 rounded border border-gray-300">
                    <p class="mt-1 text-sm text-gray-500">Used for buttons, links, and highlights</p>
                </div>
            </div>
        </div>

        <!-- Theme Settings -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Theme Settings</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="theme_mode" class="block text-sm font-medium text-gray-700 mb-1">Theme Mode</label>
                    <select id="theme_mode" name="theme_mode" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="light">Light Theme</option>
                        <option value="dark">Dark Theme</option>
                        <option value="auto">Auto (System Preference)</option>
                    </select>
                </div>
                <div>
                    <label for="sidebar_style" class="block text-sm font-medium text-gray-700 mb-1">Sidebar Style</label>
                    <select id="sidebar_style" name="sidebar_style" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="expanded">Always Expanded</option>
                        <option value="collapsed">Always Collapsed</option>
                        <option value="auto">Auto (Responsive)</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
        <button type="submit" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Save Branding Settings
        </button>
    </div>
</div>