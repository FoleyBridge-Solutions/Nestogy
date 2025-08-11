import * as joint from '@joint/core';

export class ITDocumentationDiagram {
    constructor(containerId) {
        this.containerId = containerId;
        this.graph = null;
        this.paper = null;
        this.selectedElement = null;
        this.elements = new Map();
        this.init();
    }

    init() {
        const container = document.getElementById(this.containerId);
        if (!container) return;

        // Create JointJS graph and paper
        this.graph = new joint.dia.Graph();
        
        this.paper = new joint.dia.Paper({
            el: container,
            model: this.graph,
            width: container.offsetWidth || 1200,
            height: 600,
            gridSize: 10,
            drawGrid: true,
            background: {
                color: '#f8f9fa'
            },
            defaultConnectionPoint: { name: 'boundary' },
            defaultConnector: { name: 'rounded' },
            defaultLink: () => new joint.shapes.standard.Link({
                attrs: {
                    line: {
                        stroke: '#4B5563',
                        strokeWidth: 2,
                        targetMarker: {
                            type: 'path',
                            d: 'M 10 -5 0 0 10 5 Z',
                            fill: '#4B5563'
                        }
                    }
                }
            }),
            linkPinning: false,
            interactive: true,
            snapLinks: { radius: 30 },
            markAvailable: true,
            validateConnection: (cellViewS, magnetS, cellViewT, magnetT, end, linkView) => {
                // Prevent linking from input to output on same element
                if (cellViewS === cellViewT) return false;
                // Prevent linking to links
                if (magnetT && magnetT.getAttribute('port-group') === 'in') return false;
                return magnetT && magnetT.getAttribute('port-group') === 'out';
            }
        });

        // Handle element selection
        this.paper.on('element:pointerclick', (elementView) => {
            this.selectElement(elementView.model);
        });

        // Handle blank area click
        this.paper.on('blank:pointerclick', () => {
            this.clearSelection();
        });

        // Handle element deletion with Delete key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Delete' && this.selectedElement) {
                this.deleteElement(this.selectedElement);
            }
        });

        this.setupToolbar();
        this.setupShapes();
    }

    setupToolbar() {
        const toolbar = document.createElement('div');
        toolbar.className = 'diagram-toolbar bg-white border-b border-gray-200 p-3 flex items-center space-x-2';
        toolbar.innerHTML = `
            <button type="button" class="btn-add-server px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M5 20h.01"/>
                </svg>
                Server
            </button>
            <button type="button" class="btn-add-workstation px-3 py-2 bg-green-500 text-white rounded hover:bg-green-600 text-sm">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Workstation
            </button>
            <button type="button" class="btn-add-router px-3 py-2 bg-purple-500 text-white rounded hover:bg-purple-600 text-sm">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                </svg>
                Router
            </button>
            <button type="button" class="btn-add-switch px-3 py-2 bg-indigo-500 text-white rounded hover:bg-indigo-600 text-sm">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Switch
            </button>
            <button type="button" class="btn-add-firewall px-3 py-2 bg-red-500 text-white rounded hover:bg-red-600 text-sm">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                Firewall
            </button>
            <button type="button" class="btn-add-cloud px-3 py-2 bg-cyan-500 text-white rounded hover:bg-cyan-600 text-sm">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
                </svg>
                Cloud
            </button>
            <button type="button" class="btn-add-database px-3 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 text-sm">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                </svg>
                Database
            </button>
            <div class="flex-1"></div>
            <button type="button" class="btn-clear px-3 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 text-sm">
                Clear All
            </button>
            <button type="button" class="btn-export px-3 py-2 bg-gray-700 text-white rounded hover:bg-gray-800 text-sm">
                Export JSON
            </button>
        `;

        const container = document.getElementById(this.containerId);
        container.parentNode.insertBefore(toolbar, container);

        // Add event listeners
        toolbar.querySelector('.btn-add-server').addEventListener('click', () => this.addServer());
        toolbar.querySelector('.btn-add-workstation').addEventListener('click', () => this.addWorkstation());
        toolbar.querySelector('.btn-add-router').addEventListener('click', () => this.addRouter());
        toolbar.querySelector('.btn-add-switch').addEventListener('click', () => this.addSwitch());
        toolbar.querySelector('.btn-add-firewall').addEventListener('click', () => this.addFirewall());
        toolbar.querySelector('.btn-add-cloud').addEventListener('click', () => this.addCloud());
        toolbar.querySelector('.btn-add-database').addEventListener('click', () => this.addDatabase());
        toolbar.querySelector('.btn-clear').addEventListener('click', () => this.clearDiagram());
        toolbar.querySelector('.btn-export').addEventListener('click', () => this.exportDiagram());
    }

    setupShapes() {
        // Custom shapes are created inline using standard JointJS shapes
        // No need to extend base shapes as we're using standard.Rectangle and standard.Cylinder
    }

    addServer(x = 100, y = 100) {
        const server = new joint.shapes.standard.Rectangle({
            position: { x: x + Math.random() * 200, y: y + Math.random() * 100 },
            size: { width: 120, height: 80 },
            attrs: {
                body: {
                    fill: '#3B82F6',
                    stroke: '#1E40AF',
                    strokeWidth: 2,
                    rx: 5,
                    ry: 5
                },
                label: {
                    text: 'Server',
                    fill: '#FFFFFF',
                    fontSize: 14,
                    fontWeight: 'bold'
                }
            }
        });
        this.graph.addCell(server);
        this.elements.set(server.id, { type: 'server', element: server });
        return server;
    }

    addWorkstation(x = 100, y = 100) {
        const workstation = new joint.shapes.standard.Rectangle({
            position: { x: x + Math.random() * 200, y: y + Math.random() * 100 },
            size: { width: 120, height: 80 },
            attrs: {
                body: {
                    fill: '#10B981',
                    stroke: '#047857',
                    strokeWidth: 2,
                    rx: 5,
                    ry: 5
                },
                label: {
                    text: 'Workstation',
                    fill: '#FFFFFF',
                    fontSize: 14,
                    fontWeight: 'bold'
                }
            }
        });
        this.graph.addCell(workstation);
        this.elements.set(workstation.id, { type: 'workstation', element: workstation });
        return workstation;
    }

    addRouter(x = 100, y = 100) {
        const router = new joint.shapes.standard.Rectangle({
            position: { x: x + Math.random() * 200, y: y + Math.random() * 100 },
            size: { width: 120, height: 80 },
            attrs: {
                body: {
                    fill: '#8B5CF6',
                    stroke: '#6D28D9',
                    strokeWidth: 2,
                    rx: 5,
                    ry: 5
                },
                label: {
                    text: 'Router',
                    fill: '#FFFFFF',
                    fontSize: 14,
                    fontWeight: 'bold'
                }
            }
        });
        this.graph.addCell(router);
        this.elements.set(router.id, { type: 'router', element: router });
        return router;
    }

    addSwitch(x = 100, y = 100) {
        const switchElement = new joint.shapes.standard.Rectangle({
            position: { x: x + Math.random() * 200, y: y + Math.random() * 100 },
            size: { width: 120, height: 80 },
            attrs: {
                body: {
                    fill: '#6366F1',
                    stroke: '#4338CA',
                    strokeWidth: 2,
                    rx: 5,
                    ry: 5
                },
                label: {
                    text: 'Switch',
                    fill: '#FFFFFF',
                    fontSize: 14,
                    fontWeight: 'bold'
                }
            }
        });
        this.graph.addCell(switchElement);
        this.elements.set(switchElement.id, { type: 'switch', element: switchElement });
        return switchElement;
    }

    addFirewall(x = 100, y = 100) {
        const firewall = new joint.shapes.standard.Rectangle({
            position: { x: x + Math.random() * 200, y: y + Math.random() * 100 },
            size: { width: 120, height: 80 },
            attrs: {
                body: {
                    fill: '#EF4444',
                    stroke: '#B91C1C',
                    strokeWidth: 2,
                    rx: 5,
                    ry: 5
                },
                label: {
                    text: 'Firewall',
                    fill: '#FFFFFF',
                    fontSize: 14,
                    fontWeight: 'bold'
                }
            }
        });
        this.graph.addCell(firewall);
        this.elements.set(firewall.id, { type: 'firewall', element: firewall });
        return firewall;
    }

    addCloud(x = 100, y = 100) {
        const cloud = new joint.shapes.standard.Rectangle({
            position: { x: x + Math.random() * 200, y: y + Math.random() * 100 },
            size: { width: 140, height: 80 },
            attrs: {
                body: {
                    fill: '#06B6D4',
                    stroke: '#0891B2',
                    strokeWidth: 2,
                    rx: 20,
                    ry: 20
                },
                label: {
                    text: 'Cloud Service',
                    fill: '#FFFFFF',
                    fontSize: 14,
                    fontWeight: 'bold'
                }
            }
        });
        this.graph.addCell(cloud);
        this.elements.set(cloud.id, { type: 'cloud', element: cloud });
        return cloud;
    }

    addDatabase(x = 100, y = 100) {
        const database = new joint.shapes.standard.Cylinder({
            position: { x: x + Math.random() * 200, y: y + Math.random() * 100 },
            size: { width: 100, height: 100 },
            attrs: {
                body: {
                    fill: '#F59E0B',
                    stroke: '#D97706',
                    strokeWidth: 2
                },
                top: {
                    fill: '#FCD34D',
                    stroke: '#D97706',
                    strokeWidth: 2
                },
                label: {
                    text: 'Database',
                    fill: '#FFFFFF',
                    fontSize: 14,
                    fontWeight: 'bold'
                }
            }
        });
        this.graph.addCell(database);
        this.elements.set(database.id, { type: 'database', element: database });
        return database;
    }

    selectElement(element) {
        this.clearSelection();
        this.selectedElement = element;
        element.attr('body/stroke', '#FF0000');
        element.attr('body/strokeWidth', 3);
        this.showProperties(element);
    }

    clearSelection() {
        if (this.selectedElement) {
            const elementData = this.elements.get(this.selectedElement.id);
            if (elementData) {
                // Restore original stroke based on type
                const strokeColors = {
                    server: '#1E40AF',
                    workstation: '#047857',
                    router: '#6D28D9',
                    switch: '#4338CA',
                    firewall: '#B91C1C',
                    cloud: '#0891B2',
                    database: '#D97706'
                };
                this.selectedElement.attr('body/stroke', strokeColors[elementData.type] || '#374151');
                this.selectedElement.attr('body/strokeWidth', 2);
            }
            this.selectedElement = null;
            this.hideProperties();
        }
    }

    deleteElement(element) {
        element.remove();
        this.elements.delete(element.id);
        this.clearSelection();
    }

    showProperties(element) {
        const propertiesPanel = document.getElementById('properties-panel');
        if (!propertiesPanel) {
            this.createPropertiesPanel();
        }
        
        const panel = document.getElementById('properties-panel');
        const elementData = this.elements.get(element.id);
        const label = element.attr('label/text');
        
        panel.innerHTML = `
            <div class="p-4 bg-white border rounded-lg shadow-lg">
                <h3 class="text-lg font-semibold mb-3">Properties</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Type</label>
                        <input type="text" value="${elementData.type}" disabled class="mt-1 block w-full border-gray-300 rounded-md bg-gray-100 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Label</label>
                        <input type="text" id="element-label" value="${label}" class="mt-1 block w-full border-gray-300 rounded-md text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">IP Address</label>
                        <input type="text" id="element-ip" placeholder="192.168.1.1" class="mt-1 block w-full border-gray-300 rounded-md text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea id="element-notes" rows="3" class="mt-1 block w-full border-gray-300 rounded-md text-sm"></textarea>
                    </div>
                    <div class="flex space-x-2">
                        <button id="save-properties" class="px-3 py-2 bg-blue-500 text-white rounded text-sm hover:bg-blue-600">Save</button>
                        <button id="delete-element" class="px-3 py-2 bg-red-500 text-white rounded text-sm hover:bg-red-600">Delete</button>
                    </div>
                </div>
            </div>
        `;
        
        panel.style.display = 'block';
        
        // Add event listeners
        document.getElementById('save-properties').addEventListener('click', () => {
            const newLabel = document.getElementById('element-label').value;
            element.attr('label/text', newLabel);
        });
        
        document.getElementById('delete-element').addEventListener('click', () => {
            this.deleteElement(element);
        });
    }

    hideProperties() {
        const panel = document.getElementById('properties-panel');
        if (panel) {
            panel.style.display = 'none';
        }
    }

    createPropertiesPanel() {
        const panel = document.createElement('div');
        panel.id = 'properties-panel';
        panel.className = 'absolute top-20 right-4 w-64 z-10';
        panel.style.display = 'none';
        document.getElementById(this.containerId).parentNode.appendChild(panel);
    }

    clearDiagram() {
        if (confirm('Are you sure you want to clear the entire diagram?')) {
            this.graph.clear();
            this.elements.clear();
            this.clearSelection();
        }
    }

    exportDiagram() {
        const json = this.graph.toJSON();
        const dataStr = JSON.stringify(json, null, 2);
        const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
        
        const exportName = 'network-diagram-' + Date.now() + '.json';
        
        const linkElement = document.createElement('a');
        linkElement.setAttribute('href', dataUri);
        linkElement.setAttribute('download', exportName);
        linkElement.click();
    }

    importDiagram(jsonData) {
        try {
            this.graph.fromJSON(jsonData);
            // Rebuild elements map
            this.elements.clear();
            this.graph.getCells().forEach(cell => {
                if (cell.isElement()) {
                    const label = cell.attr('label/text');
                    let type = 'unknown';
                    if (label) {
                        type = label.toLowerCase();
                    }
                    this.elements.set(cell.id, { type: type, element: cell });
                }
            });
        } catch (error) {
            console.error('Error importing diagram:', error);
            alert('Failed to import diagram. Please check the JSON format.');
        }
    }

    getDiagramData() {
        return this.graph.toJSON();
    }
}

// Initialize when DOM is ready
if (typeof window !== 'undefined') {
    window.ITDocumentationDiagram = ITDocumentationDiagram;
}