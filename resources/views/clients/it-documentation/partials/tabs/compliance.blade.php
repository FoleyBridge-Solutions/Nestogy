<div class="space-y-6">
    <!-- Compliance Frameworks -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-3">Applicable Compliance Frameworks</label>
        <div class="grid grid-cols-2 gap-4">
            <label class="inline-flex items-center">
                <input type="checkbox" name="compliance_frameworks[]" value="gdpr" 
                       @change="updateComplianceRequirements()"
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2">
                    <span class="font-medium">GDPR</span>
                    <span class="text-xs text-gray-500 block">General Data Protection Regulation</span>
                </span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" name="compliance_frameworks[]" value="hipaa"
                       @change="updateComplianceRequirements()"
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2">
                    <span class="font-medium">HIPAA</span>
                    <span class="text-xs text-gray-500 block">Health Insurance Portability and Accountability Act</span>
                </span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" name="compliance_frameworks[]" value="soc2"
                       @change="updateComplianceRequirements()"
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2">
                    <span class="font-medium">SOC 2 Type II</span>
                    <span class="text-xs text-gray-500 block">Service Organization Control 2</span>
                </span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" name="compliance_frameworks[]" value="pci_dss"
                       @change="updateComplianceRequirements()"
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2">
                    <span class="font-medium">PCI DSS 4.0</span>
                    <span class="text-xs text-gray-500 block">Payment Card Industry Data Security Standard</span>
                </span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" name="compliance_frameworks[]" value="iso27001"
                       @change="updateComplianceRequirements()"
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2">
                    <span class="font-medium">ISO 27001:2022</span>
                    <span class="text-xs text-gray-500 block">Information Security Management</span>
                </span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" name="compliance_frameworks[]" value="nist"
                       @change="updateComplianceRequirements()"
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2">
                    <span class="font-medium">NIST CSF 2.0</span>
                    <span class="text-xs text-gray-500 block">Cybersecurity Framework</span>
                </span>
            </label>
        </div>
    </div>

    <!-- Compliance Requirements (Dynamic) -->
    <div x-show="complianceRequirements.length > 0">
        <div class="flex justify-between items-center mb-2">
            <label class="block text-sm font-medium text-gray-700">Compliance Requirements</label>
            <button type="button" @click="addComplianceRequirement()" class="text-sm text-blue-600 hover:text-blue-800">+ Add Custom Requirement</button>
        </div>
        <div class="space-y-2">
            <template x-for="(req, index) in complianceRequirements" :key="index">
                <div class="border border-gray-200 rounded p-3" :class="req.auto_generated ? 'bg-blue-50' : 'bg-white'">
                    <div class="flex justify-between mb-2">
                        <span class="text-sm font-medium" x-text="req.framework"></span>
                        <button type="button" @click="removeComplianceRequirement(index)" 
                                x-show="!req.auto_generated"
                                class="text-red-600 hover:text-red-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <input type="text" x-model="req.requirement" placeholder="Requirement"
                           :name="`compliance_requirements[${index}][requirement]`"
                           :readonly="req.auto_generated"
                           class="w-full mb-2 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           :class="req.auto_generated ? 'bg-gray-100' : ''">
                    <textarea x-model="req.implementation" placeholder="How is this requirement implemented?"
                              :name="`compliance_requirements[${index}][implementation]`"
                              rows="2"
                              class="w-full mb-2 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                    <div class="grid grid-cols-3 gap-2">
                        <select x-model="req.status" :name="`compliance_requirements[${index}][status]`"
                                class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="compliant">Compliant</option>
                            <option value="partial">Partially Compliant</option>
                            <option value="non_compliant">Non-Compliant</option>
                            <option value="not_applicable">Not Applicable</option>
                        </select>
                        <input type="date" x-model="req.review_date" 
                               :name="`compliance_requirements[${index}][review_date]`"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <input type="text" x-model="req.evidence" placeholder="Evidence/Documentation"
                               :name="`compliance_requirements[${index}][evidence]`"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <input type="hidden" :name="`compliance_requirements[${index}][framework]`" :value="req.framework">
                    <input type="hidden" :name="`compliance_requirements[${index}][auto_generated]`" :value="req.auto_generated">
                </div>
            </template>
            <div x-show="complianceRequirements.length === 0" class="text-sm text-gray-500 italic">Select compliance frameworks above to see requirements</div>
        </div>
    </div>

    <!-- Data Classification -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Data Classification</label>
        <select name="data_classification" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            <option value="">Select Classification</option>
            <option value="public">Public</option>
            <option value="internal">Internal Use Only</option>
            <option value="confidential">Confidential</option>
            <option value="restricted">Restricted/Highly Confidential</option>
        </select>
    </div>

    <!-- Security Controls -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-3">Security Controls</label>
        <div class="grid grid-cols-2 gap-3">
            <label class="inline-flex items-center">
                <input type="checkbox" name="security_controls[]" value="encryption_at_rest" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm">Encryption at Rest</span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" name="security_controls[]" value="encryption_in_transit" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm">Encryption in Transit</span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" name="security_controls[]" value="mfa" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm">Multi-Factor Authentication</span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" name="security_controls[]" value="access_logging" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm">Access Logging</span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" name="security_controls[]" value="vulnerability_scanning" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm">Vulnerability Scanning</span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" name="security_controls[]" value="penetration_testing" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm">Penetration Testing</span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" name="security_controls[]" value="dlp" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm">Data Loss Prevention</span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" name="security_controls[]" value="backup_recovery" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm">Backup & Recovery</span>
            </label>
        </div>
    </div>

    <!-- Audit Information -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Last Audit Information</label>
        <div class="grid grid-cols-3 gap-3">
            <div>
                <label class="block text-xs text-gray-500">Audit Date</label>
                <input type="date" name="last_audit_date" 
                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500">Auditor</label>
                <input type="text" name="auditor" placeholder="Auditor/Firm Name"
                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500">Next Audit</label>
                <input type="date" name="next_audit_date" 
                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
        </div>
    </div>

    <!-- Compliance Notes -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Compliance Notes</label>
        <textarea name="compliance_notes" rows="4" 
                  placeholder="Additional compliance considerations, exceptions, or notes"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
    </div>

    <!-- Compliance Score Display -->
    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
        <h4 class="text-sm font-medium text-gray-700 mb-3">Compliance Score Overview</h4>
        <div class="space-y-2">
            <template x-for="framework in getSelectedFrameworks()" :key="framework">
                <div class="flex items-center justify-between">
                    <span class="text-sm" x-text="framework"></span>
                    <div class="flex items-center space-x-2">
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" :style="`width: ${getComplianceScore(framework)}%`"></div>
                        </div>
                        <span class="text-sm font-medium" x-text="`${getComplianceScore(framework)}%`"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>