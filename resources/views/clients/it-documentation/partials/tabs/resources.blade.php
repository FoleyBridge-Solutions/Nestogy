<div class="space-y-6">
    <!-- File Attachments -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">File Attachments</label>
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
            <input type="file" name="attachments[]" multiple class="hidden" id="file-upload">
            <label for="file-upload" class="cursor-pointer">
                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <p class="mt-2 text-sm text-gray-600">
                    <span class="font-medium text-blue-600 hover:text-blue-500">Click to upload</span> or drag and drop
                </p>
                <p class="text-xs text-gray-500">PDF, DOC, XLS, PNG, JPG up to 10MB each</p>
            </label>
        </div>
        <div class="mt-2" x-show="uploadedFiles.length > 0">
            <h4 class="text-sm font-medium text-gray-700 mb-2">Uploaded Files</h4>
            <ul class="space-y-1">
                <template x-for="(file, index) in uploadedFiles" :key="index">
                    <li class="flex items-center justify-between py-2 px-6 bg-gray-50 rounded">
                        <span class="text-sm text-gray-700" x-text="file.name"></span>
                        <button type="button" @click="removeUploadedFile(index)" class="text-red-600 hover:text-red-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </li>
                </template>
            </ul>
        </div>
    </div>

    <!-- External Resources -->
    <div>
        <div class="flex justify-between items-center mb-2">
            <label class="block text-sm font-medium text-gray-700">External Resources</label>
            <button type="button" @click="addExternalResource()" class="text-sm text-blue-600 hover:text-blue-800">+ Add Resource</button>
        </div>
        <div class="space-y-2">
            <template x-for="(resource, index) in externalResources" :key="index">
                <div class="border border-gray-200 rounded p-6">
                    <div class="grid grid-cols-2 gap-3">
                        <input type="text" x-model="resource.title" placeholder="Resource Title"
                               :name="`external_resources[${index}][title]`"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <select x-model="resource.type" :name="`external_resources[${index}][type]`"
                                class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="documentation">Documentation</option>
                            <option value="vendor_guide">Vendor Guide</option>
                            <option value="knowledge_base">Knowledge Base</option>
                            <option value="video">Video Tutorial</option>
                            <option value="tool">Tool/Software</option>
                            <option value="api_docs">API Documentation</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mt-2 grid grid-cols-2 gap-3">
                        <input type="url" x-model="resource.url" placeholder="URL"
                               :name="`external_resources[${index}][url]`"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <input type="text" x-model="resource.credentials" placeholder="Access Credentials (if any)"
                               :name="`external_resources[${index}][credentials]`"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div class="mt-2 flex justify-between">
                        <textarea x-model="resource.description" placeholder="Description"
                                  :name="`external_resources[${index}][description]`"
                                  rows="2"
                                  class="flex-1 mr-2 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                        <button type="button" @click="removeExternalResource(index)" class="text-red-600 hover:text-red-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
            <div x-show="externalResources.length === 0" class="text-sm text-gray-500 italic">No external resources added yet</div>
        </div>
    </div>

    <!-- Contact Information -->
    <div>
        <div class="flex justify-between items-center mb-2">
            <label class="block text-sm font-medium text-gray-700">Support Contacts</label>
            <button type="button" @click="addSupportContact()" class="text-sm text-blue-600 hover:text-blue-800">+ Add Contact</button>
        </div>
        <div class="space-y-2">
            <template x-for="(contact, index) in supportContacts" :key="index">
                <div class="border border-gray-200 rounded p-6">
                    <div class="grid grid-cols-3 gap-3">
                        <input type="text" x-model="contact.name" placeholder="Contact Name"
                               :name="`support_contacts[${index}][name]`"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <input type="text" x-model="contact.role" placeholder="Role/Title"
                               :name="`support_contacts[${index}][role]`"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <select x-model="contact.type" :name="`support_contacts[${index}][type]`"
                                class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="internal">Internal</option>
                            <option value="vendor">Vendor</option>
                            <option value="consultant">Consultant</option>
                            <option value="emergency">Emergency</option>
                        </select>
                    </div>
                    <div class="mt-2 grid grid-cols-3 gap-3">
                        <input type="email" x-model="contact.email" placeholder="Email"
                               :name="`support_contacts[${index}][email]`"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <input type="tel" x-model="contact.phone" placeholder="Phone"
                               :name="`support_contacts[${index}][phone]`"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <input type="text" x-model="contact.availability" placeholder="Availability (e.g., 24/7, M-F 9-5)"
                               :name="`support_contacts[${index}][availability]`"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div class="mt-2 flex justify-between">
                        <input type="text" x-model="contact.notes" placeholder="Notes"
                               :name="`support_contacts[${index}][notes]`"
                               class="flex-1 mr-2 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <button type="button" @click="removeSupportContact(index)" class="text-red-600 hover:text-red-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
            <div x-show="supportContacts.length === 0" class="text-sm text-gray-500 italic">No support contacts added yet</div>
        </div>
    </div>

    <!-- License Information -->
    <div>
        <div class="flex justify-between items-center mb-2">
            <label class="block text-sm font-medium text-gray-700">License Information</label>
            <button type="button" @click="addLicense()" class="text-sm text-blue-600 hover:text-blue-800">+ Add License</button>
        </div>
        <div class="space-y-2">
            <template x-for="(license, index) in licenses" :key="index">
                <div class="border border-gray-200 rounded p-6">
                    <div class="grid grid-cols-3 gap-3">
                        <input type="text" x-model="license.software" placeholder="Software Name"
                               :name="`licenses[${index}][software]`"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <input type="text" x-model="license.key" placeholder="License Key"
                               :name="`licenses[${index}][key]`"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <input type="text" x-model="license.seats" placeholder="Number of Seats"
                               :name="`licenses[${index}][seats]`"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div class="mt-2 grid grid-cols-3 gap-3">
                        <input type="date" x-model="license.purchase_date" 
                               :name="`licenses[${index}][purchase_date]`"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <input type="date" x-model="license.expiry_date" 
                               :name="`licenses[${index}][expiry_date]`"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <input type="text" x-model="license.cost" placeholder="Cost"
                               :name="`licenses[${index}][cost]`"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div class="mt-2 flex justify-between">
                        <input type="text" x-model="license.vendor" placeholder="Vendor"
                               :name="`licenses[${index}][vendor]`"
                               class="flex-1 mr-2 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <button type="button" @click="removeLicense(index)" class="text-red-600 hover:text-red-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
            <div x-show="licenses.length === 0" class="text-sm text-gray-500 italic">No license information added yet</div>
        </div>
    </div>

    <!-- Related Documentation -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Related Documentation Links</label>
        <textarea name="related_documentation" rows="3" 
                  placeholder="Links to related procedures, runbooks, or documentation"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
    </div>

    <!-- Training Materials -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Training Materials</label>
        <textarea name="training_materials" rows="3" 
                  placeholder="Links to training videos, documentation, or courses"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
    </div>
</div>
