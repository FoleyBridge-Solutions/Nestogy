<div class="space-y-6">
    <!-- View Mode Toggle -->
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">Procedure Steps</h3>
        <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-500">View Mode:</span>
            <button type="button" 
                    @click="procedureViewMode = 'text'" 
                    :class="procedureViewMode === 'text' ? 'bg-blue-100 text-blue-700' : 'bg-white text-gray-700'"
                    class="px-3 py-1 text-sm font-medium rounded-l-md border border-gray-300">
                Text
            </button>
            <button type="button" 
                    @click="procedureViewMode = 'visual'; $nextTick(() => initProcedureDiagram())" 
                    :class="procedureViewMode === 'visual' ? 'bg-blue-100 text-blue-700' : 'bg-white text-gray-700'"
                    class="px-3 py-1 text-sm font-medium rounded-r-md border border-gray-300">
                Visual
            </button>
        </div>
    </div>

    <!-- Text View -->
    <div x-show="procedureViewMode === 'text'" class="space-y-4">
        <template x-for="(step, index) in procedureSteps" :key="index">
            <div class="border border-gray-200 rounded-lg p-4 bg-white">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center space-x-3">
                        <span class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-600 font-semibold text-sm" x-text="step.order"></span>
                        <select x-model="step.type"
                                :name="`procedure_steps[${index}][type]`"
                                class="text-sm border-gray-300 rounded-md">
                            <option value="manual">Manual</option>
                            <option value="automated">Automated</option>
                            <option value="decision">Decision</option>
                            <option value="parallel">Parallel</option>
                            <option value="checkpoint">Checkpoint</option>
                        </select>
                    </div>
                    <button type="button" @click="removeProcedureStep(index)" class="text-red-600 hover:text-red-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
                
                <div class="space-y-3">
                    <input type="text" 
                           x-model="step.title"
                           :name="`procedure_steps[${index}][title]`"
                           placeholder="Step Title"
                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    
                    <textarea x-model="step.description"
                              :name="`procedure_steps[${index}][description]`"
                              rows="3"
                              placeholder="Detailed description"
                              class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                    
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500">Duration</label>
                            <input type="text" 
                                   x-model="step.duration"
                                   :name="`procedure_steps[${index}][duration]`"
                                   placeholder="e.g., 15 minutes"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Responsible</label>
                            <input type="text" 
                                   x-model="step.responsible"
                                   :name="`procedure_steps[${index}][responsible]`"
                                   placeholder="e.g., System Admin"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Risk Level</label>
                            <select x-model="step.risk_level"
                                    :name="`procedure_steps[${index}][risk_level]`"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Commands/Scripts (shown for manual and automated steps) -->
                    <div x-show="step.type === 'automated' || step.type === 'manual'">
                        <label class="block text-xs text-gray-500 mb-1">Commands/Scripts</label>
                        <textarea x-model="step.commands"
                                  :name="`procedure_steps[${index}][commands]`"
                                  rows="4"
                                  placeholder="Enter commands or scripts"
                                  class="block w-full border-gray-300 rounded-md shadow-sm font-mono text-sm bg-gray-50"></textarea>
                    </div>
                    
                    <!-- Success Criteria -->
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Success Criteria</label>
                        <input type="text" 
                               x-model="step.success_criteria"
                               :name="`procedure_steps[${index}][success_criteria]`"
                               placeholder="What indicates this step was successful?"
                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    
                    <input type="hidden" :name="`procedure_steps[${index}][order]`" :value="step.order">
                </div>
            </div>
        </template>
        
        <button type="button" @click="addProcedureStep()" 
                class="w-full py-3 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-blue-500 hover:text-blue-600 transition-colors">
            <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Add Procedure Step
        </button>
    </div>

    <!-- Visual View with JointJS -->
    <div x-show="procedureViewMode === 'visual'">
        <div id="procedure-diagram" class="border border-gray-300 rounded-lg bg-gray-50" style="height: 600px;"></div>
        <input type="hidden" name="procedure_diagram" id="procedure_diagram_data">
        <div class="mt-3 p-3 bg-blue-50 rounded-lg">
            <p class="text-sm text-blue-800">
                <strong>How to use:</strong> Click toolbar buttons to add shapes. Drag shapes to reposition. 
                Click connections to create flow. Double-click shapes to edit text. Press Delete to remove selected element.
            </p>
        </div>
    </div>

    <!-- Prerequisites Section -->
    <div class="mt-6">
        <div class="flex justify-between items-center mb-2">
            <label class="block text-sm font-medium text-gray-700">Prerequisites</label>
            <button type="button" @click="addPrerequisite()" class="text-sm text-blue-600 hover:text-blue-800">+ Add Prerequisite</button>
        </div>
        <div class="space-y-2">
            <template x-for="(prereq, index) in prerequisites" :key="index">
                <div class="flex gap-2">
                    <input type="text" x-model="prereq.requirement" placeholder="Prerequisite requirement"
                           :name="`prerequisites[${index}]`"
                           class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <button type="button" @click="removePrerequisite(index)" class="text-red-600 hover:text-red-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </template>
            <div x-show="prerequisites.length === 0" class="text-sm text-gray-500 italic">No prerequisites defined yet</div>
        </div>
    </div>

    <!-- Rollback Procedures -->
    <div class="mt-6">
        <div class="flex justify-between items-center mb-2">
            <label class="block text-sm font-medium text-gray-700">Rollback Procedures</label>
            <button type="button" @click="addRollbackProcedure()" class="text-sm text-blue-600 hover:text-blue-800">+ Add Rollback Step</button>
        </div>
        <div class="space-y-2">
            <template x-for="(rollback, index) in rollbackProcedures" :key="index">
                <div class="border border-gray-200 rounded p-3">
                    <div class="flex justify-between mb-2">
                        <span class="text-sm font-medium">Step <span x-text="index + 1"></span></span>
                        <button type="button" @click="removeRollbackProcedure(index)" class="text-red-600 hover:text-red-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <input type="text" x-model="rollback.title" placeholder="Rollback step title"
                           :name="`rollback_procedures[${index}][title]`"
                           class="w-full mb-2 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <textarea x-model="rollback.description" placeholder="Rollback instructions"
                              :name="`rollback_procedures[${index}][description]`"
                              rows="2"
                              class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                </div>
            </template>
            <div x-show="rollbackProcedures.length === 0" class="text-sm text-gray-500 italic">No rollback procedures defined yet</div>
        </div>
    </div>
</div>