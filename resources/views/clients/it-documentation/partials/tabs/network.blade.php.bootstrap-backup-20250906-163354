<div class="space-y-6">
    <!-- Network Diagram Section -->
    <div>
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Network Infrastructure Diagram</h3>
            <div class="flex items-center space-x-2">
                <button type="button" @click="importNetworkDiagram()" 
                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    Import
                </button>
            </div>
        </div>
        
        <!-- JointJS Network Diagram Container -->
        <div id="network-diagram" class="border border-gray-300 rounded-lg bg-gray-50" style="height: 600px;"></div>
        <input type="hidden" name="network_diagram" id="network_diagram_data">
        
        <div class="mt-3 p-3 bg-blue-50 rounded-lg">
            <p class="text-sm text-blue-800">
                <strong>How to use:</strong> Click toolbar buttons to add network components. 
                Drag components to reposition. Connect components by clicking and dragging between them. 
                Double-click to edit labels. Press Delete to remove selected element.
            </p>
        </div>
    </div>

    <!-- Network Segments -->
    <div>
        <div class="flex justify-between items-center mb-2">
            <label class="block text-sm font-medium text-gray-700">Network Segments</label>
            <button type="button" @click="addNetworkSegment()" class="text-sm text-blue-600 hover:text-blue-800">+ Add Segment</button>
        </div>
        <div class="space-y-2">
            <template x-for="(segment, index) in networkSegments" :key="index">
                <div class="border border-gray-200 rounded p-3">
                    <div class="grid grid-cols-3 gap-3">
                        <input type="text" x-model="segment.name" placeholder="Segment Name"
                               :name="`network_segments[${index}][name]`"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <input type="text" x-model="segment.subnet" placeholder="Subnet (e.g., 192.168.1.0/24)"
                               :name="`network_segments[${index}][subnet]`"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <input type="text" x-model="segment.vlan" placeholder="VLAN ID"
                               :name="`network_segments[${index}][vlan]`"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div class="mt-2 flex justify-between">
                        <input type="text" x-model="segment.description" placeholder="Description"
                               :name="`network_segments[${index}][description]`"
                               class="flex-1 mr-2 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <button type="button" @click="removeNetworkSegment(index)" class="text-red-600 hover:text-red-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
            <div x-show="networkSegments.length === 0" class="text-sm text-gray-500 italic">No network segments defined yet</div>
        </div>
    </div>

    <!-- Network Devices -->
    <div>
        <div class="flex justify-between items-center mb-2">
            <label class="block text-sm font-medium text-gray-700">Network Devices</label>
            <button type="button" @click="addNetworkDevice()" class="text-sm text-blue-600 hover:text-blue-800">+ Add Device</button>
        </div>
        <div class="space-y-2">
            <template x-for="(device, index) in networkDevices" :key="index">
                <div class="border border-gray-200 rounded p-3">
                    <div class="grid grid-cols-4 gap-3">
                        <input type="text" x-model="device.hostname" placeholder="Hostname"
                               :name="`network_devices[${index}][hostname]`"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <select x-model="device.type" :name="`network_devices[${index}][type]`"
                                class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Device Type</option>
                            <option value="router">Router</option>
                            <option value="switch">Switch</option>
                            <option value="firewall">Firewall</option>
                            <option value="server">Server</option>
                            <option value="workstation">Workstation</option>
                            <option value="printer">Printer</option>
                            <option value="ap">Access Point</option>
                            <option value="other">Other</option>
                        </select>
                        <input type="text" x-model="device.ip_address" placeholder="IP Address"
                               :name="`network_devices[${index}][ip_address]`"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <input type="text" x-model="device.mac_address" placeholder="MAC Address"
                               :name="`network_devices[${index}][mac_address]`"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div class="mt-2 grid grid-cols-3 gap-3">
                        <input type="text" x-model="device.manufacturer" placeholder="Manufacturer"
                               :name="`network_devices[${index}][manufacturer]`"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <input type="text" x-model="device.model" placeholder="Model"
                               :name="`network_devices[${index}][model]`"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <input type="text" x-model="device.location" placeholder="Location"
                               :name="`network_devices[${index}][location]`"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div class="mt-2 flex justify-between">
                        <input type="text" x-model="device.notes" placeholder="Notes"
                               :name="`network_devices[${index}][notes]`"
                               class="flex-1 mr-2 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <button type="button" @click="removeNetworkDevice(index)" class="text-red-600 hover:text-red-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
            <div x-show="networkDevices.length === 0" class="text-sm text-gray-500 italic">No network devices documented yet</div>
        </div>
    </div>

    <!-- Bandwidth Requirements -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Bandwidth Requirements</label>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-gray-500">Minimum Bandwidth</label>
                <input type="text" name="bandwidth_min" placeholder="e.g., 100 Mbps"
                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500">Recommended Bandwidth</label>
                <input type="text" name="bandwidth_recommended" placeholder="e.g., 1 Gbps"
                       class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
        </div>
    </div>

    <!-- Network Topology -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Network Topology Type</label>
        <select name="topology_type" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            <option value="">Select Topology</option>
            <option value="star">Star</option>
            <option value="mesh">Mesh</option>
            <option value="ring">Ring</option>
            <option value="bus">Bus</option>
            <option value="tree">Tree</option>
            <option value="hybrid">Hybrid</option>
        </select>
    </div>

    <!-- Network Protocols -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Network Protocols</label>
        <div class="grid grid-cols-3 gap-3">
            <label class="inline-flex items-center">
                <input type="checkbox" name="protocols[]" value="tcp_ip" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm">TCP/IP</span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" name="protocols[]" value="dhcp" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm">DHCP</span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" name="protocols[]" value="dns" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm">DNS</span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" name="protocols[]" value="snmp" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm">SNMP</span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" name="protocols[]" value="bgp" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm">BGP</span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" name="protocols[]" value="ospf" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm">OSPF</span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" name="protocols[]" value="vlan" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm">VLAN</span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" name="protocols[]" value="vpn" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm">VPN</span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" name="protocols[]" value="stp" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm">STP</span>
            </label>
        </div>
    </div>

    <!-- External Connections -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">External Connections</label>
        <textarea name="external_connections" rows="3" 
                  placeholder="ISP connections, VPN tunnels, cloud connections, etc."
                  class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
    </div>
</div>