@extends('layouts.app')

@section('title', 'Workflow Designer')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Contract Workflow Designer</h1>
                    <p class="text-muted">Design custom workflows for contract processing</p>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-secondary me-2" id="loadWorkflowBtn">
                        <i class="fas fa-folder-open"></i> Load Workflow
                    </button>
                    <button type="button" class="btn btn-outline-info me-2" id="previewWorkflowBtn">
                        <i class="fas fa-eye"></i> Preview
                    </button>
                    <button type="button" class="btn btn-success me-2" id="saveWorkflowBtn">
                        <i class="fas fa-save"></i> Save Workflow
                    </button>
                    <button type="button" class="btn btn-primary" id="publishWorkflowBtn">
                        <i class="fas fa-rocket"></i> Publish
                    </button>
                </div>
            </div>

            <div class="row">
                {{-- Workflow Sidebar --}}
                <div class="col-lg-3">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Workflow Properties</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="workflowName" class="form-label">Workflow Name</label>
                                <input type="text" class="form-control form-control-sm" id="workflowName" 
                                       placeholder="Enter workflow name...">
                            </div>
                            <div class="mb-3">
                                <label for="workflowDescription" class="form-label">Description</label>
                                <textarea class="form-control form-control-sm" id="workflowDescription" rows="3"
                                          placeholder="Describe this workflow..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="contractType" class="form-label">Contract Type</label>
                                <select class="form-select form-select-sm" id="contractType">
                                    <option value="">All Contract Types</option>
                                    @foreach($contractTypes as $type)
                                        <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="isActive" checked>
                                <label class="form-check-label" for="isActive">Active</label>
                            </div>
                        </div>
                    </div>

                    {{-- Node Palette --}}
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Workflow Elements</h6>
                        </div>
                        <div class="card-body p-2">
                            <div class="row g-2">
                                <div class="col-12">
                                    <small class="text-muted fw-bold">STATES</small>
                                </div>
                                <div class="col-6">
                                    <div class="workflow-node-template" data-node-type="start"
                                         draggable="true">
                                        <div class="node node-start">
                                            <i class="fas fa-play"></i>
                                            <small>Start</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="workflow-node-template" data-node-type="end"
                                         draggable="true">
                                        <div class="node node-end">
                                            <i class="fas fa-stop"></i>
                                            <small>End</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="workflow-node-template" data-node-type="status"
                                         draggable="true">
                                        <div class="node node-status">
                                            <i class="fas fa-flag"></i>
                                            <small>Status</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="workflow-node-template" data-node-type="approval"
                                         draggable="true">
                                        <div class="node node-approval">
                                            <i class="fas fa-user-check"></i>
                                            <small>Approval</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3">

                            <div class="row g-2">
                                <div class="col-12">
                                    <small class="text-muted fw-bold">ACTIONS</small>
                                </div>
                                <div class="col-6">
                                    <div class="workflow-node-template" data-node-type="notification"
                                         draggable="true">
                                        <div class="node node-action">
                                            <i class="fas fa-bell"></i>
                                            <small>Notify</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="workflow-node-template" data-node-type="email"
                                         draggable="true">
                                        <div class="node node-action">
                                            <i class="fas fa-envelope"></i>
                                            <small>Email</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="workflow-node-template" data-node-type="webhook"
                                         draggable="true">
                                        <div class="node node-action">
                                            <i class="fas fa-link"></i>
                                            <small>Webhook</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="workflow-node-template" data-node-type="task"
                                         draggable="true">
                                        <div class="node node-action">
                                            <i class="fas fa-tasks"></i>
                                            <small>Task</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3">

                            <div class="row g-2">
                                <div class="col-12">
                                    <small class="text-muted fw-bold">LOGIC</small>
                                </div>
                                <div class="col-6">
                                    <div class="workflow-node-template" data-node-type="condition"
                                         draggable="true">
                                        <div class="node node-condition">
                                            <i class="fas fa-question"></i>
                                            <small>Condition</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="workflow-node-template" data-node-type="delay"
                                         draggable="true">
                                        <div class="node node-condition">
                                            <i class="fas fa-clock"></i>
                                            <small>Delay</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Workflow Canvas --}}
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0">Workflow Canvas</h6>
                                <div>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary" id="zoomOut">
                                            <i class="fas fa-search-minus"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" id="zoomReset">
                                            100%
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" id="zoomIn">
                                            <i class="fas fa-search-plus"></i>
                                        </button>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger ms-2" id="clearCanvas">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div id="workflowCanvas" class="workflow-canvas">
                                <svg id="connectionSvg" class="connection-layer">
                                    <defs>
                                        <marker id="arrowhead" markerWidth="10" markerHeight="7" 
                                                refX="10" refY="3.5" orient="auto">
                                            <polygon points="0 0, 10 3.5, 0 7" fill="#666" />
                                        </marker>
                                    </defs>
                                </svg>
                                <div class="canvas-content">
                                    <div class="canvas-grid"></div>
                                    <div class="drop-zone-message">
                                        <i class="fas fa-mouse-pointer fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">Design Your Workflow</h5>
                                        <p class="text-muted">Drag elements from the palette to create your contract workflow</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Properties Panel --}}
                <div class="col-lg-3">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Element Properties</h6>
                        </div>
                        <div class="card-body">
                            <div id="nodeProperties">
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-mouse-pointer fa-2x mb-2"></i>
                                    <p class="mb-0">Select an element to configure its properties</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Workflow Stats --}}
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Workflow Statistics</h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="text-primary">
                                        <i class="fas fa-circle fa-lg"></i>
                                    </div>
                                    <small class="text-muted">Nodes</small>
                                    <div class="fw-bold" id="nodeCount">0</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-success">
                                        <i class="fas fa-arrow-right fa-lg"></i>
                                    </div>
                                    <small class="text-muted">Connections</small>
                                    <div class="fw-bold" id="connectionCount">0</div>
                                </div>
                            </div>
                            <hr>
                            <div class="small">
                                <div class="d-flex justify-content-between">
                                    <span>Validation:</span>
                                    <span id="validationStatus" class="text-warning">
                                        <i class="fas fa-exclamation-triangle"></i> Incomplete
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Quick Actions --}}
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="addStartNode">
                                    <i class="fas fa-play"></i> Add Start Node
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success" id="addEndNode">
                                    <i class="fas fa-stop"></i> Add End Node
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info" id="autoLayout">
                                    <i class="fas fa-magic"></i> Auto Layout
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="exportWorkflow">
                                    <i class="fas fa-download"></i> Export JSON
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Load Workflow Modal --}}
<div class="modal fade" id="loadWorkflowModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Load Workflow</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <h6>Saved Workflows</h6>
                        <div id="savedWorkflows" class="list-group list-group-flush">
                            <!-- Saved workflows will be loaded here -->
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-12">
                        <h6>Import from File</h6>
                        <input type="file" class="form-control" id="workflowFileInput" accept=".json">
                        <div class="form-text">Upload a workflow JSON file</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="loadSelectedWorkflow">Load Workflow</button>
            </div>
        </div>
    </div>
</div>

{{-- Save Workflow Modal --}}
<div class="modal fade" id="saveWorkflowModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Save Workflow</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="saveWorkflowForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="saveWorkflowName" class="form-label">Workflow Name</label>
                        <input type="text" class="form-control" id="saveWorkflowName" required>
                    </div>
                    <div class="mb-3">
                        <label for="saveWorkflowVersion" class="form-label">Version</label>
                        <input type="text" class="form-control" id="saveWorkflowVersion" value="1.0">
                    </div>
                    <div class="mb-3">
                        <label for="saveWorkflowNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="saveWorkflowNotes" rows="3" 
                                  placeholder="Optional notes about this version..."></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="saveAsTemplate">
                        <label class="form-check-label" for="saveAsTemplate">
                            Save as template for new contracts
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Workflow</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Preview Modal --}}
<div class="modal fade" id="previewWorkflowModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Workflow Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Workflow Diagram</h6>
                        <div id="previewDiagram" class="border rounded p-3 bg-light" style="min-height: 300px;">
                            <!-- Workflow diagram will be rendered here -->
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Workflow Steps</h6>
                        <div id="previewSteps">
                            <!-- Workflow steps will be listed here -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="previewTestRun">Test Run</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.workflow-canvas {
    position: relative;
    height: 600px;
    background: #f8f9fa;
    overflow: hidden;
    border: 1px solid #dee2e6;
    user-select: none;
}

.canvas-grid {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
        linear-gradient(to right, rgba(0,0,0,0.1) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(0,0,0,0.1) 1px, transparent 1px);
    background-size: 20px 20px;
    opacity: 0.3;
}

.canvas-content {
    position: relative;
    width: 100%;
    height: 100%;
    transform-origin: 0 0;
    transition: transform 0.2s ease;
}

.drop-zone-message {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    pointer-events: none;
}

.workflow-node-template {
    cursor: grab;
    margin-bottom: 4px;
}

.workflow-node-template:active {
    cursor: grabbing;
}

.node {
    width: 80px;
    height: 60px;
    border-radius: 8px;
    border: 2px solid #ddd;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    font-size: 11px;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
    position: relative;
}

.node:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.node i {
    font-size: 16px;
    margin-bottom: 4px;
}

.node-start {
    border-color: #28a745;
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
}

.node-end {
    border-color: #dc3545;
    background: linear-gradient(135deg, #dc3545, #fd7e14);
    color: white;
}

.node-status {
    border-color: #007bff;
    background: linear-gradient(135deg, #007bff, #6610f2);
    color: white;
}

.node-approval {
    border-color: #ffc107;
    background: linear-gradient(135deg, #ffc107, #fd7e14);
    color: #212529;
}

.node-action {
    border-color: #17a2b8;
    background: linear-gradient(135deg, #17a2b8, #6f42c1);
    color: white;
}

.node-condition {
    border-color: #6c757d;
    background: linear-gradient(135deg, #6c757d, #495057);
    color: white;
    transform: rotate(45deg);
}

.node-condition i,
.node-condition small {
    transform: rotate(-45deg);
}

.workflow-node {
    position: absolute;
    cursor: move;
    z-index: 10;
}

.workflow-node.selected {
    outline: 3px solid #007bff;
    outline-offset: 2px;
}

.workflow-node.dragging {
    opacity: 0.8;
    z-index: 1000;
}

.connection-layer {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 5;
}

.connection-line {
    stroke: #666;
    stroke-width: 2;
    fill: none;
    marker-end: url(#arrowhead);
}

.connection-line.selected {
    stroke: #007bff;
    stroke-width: 3;
}

.node-connector {
    position: absolute;
    width: 12px;
    height: 12px;
    background: #007bff;
    border: 2px solid white;
    border-radius: 50%;
    cursor: crosshair;
    opacity: 0;
    transition: opacity 0.2s ease;
    z-index: 20;
}

.workflow-node:hover .node-connector {
    opacity: 1;
}

.node-connector.input {
    top: -6px;
    left: 50%;
    transform: translateX(-50%);
}

.node-connector.output {
    bottom: -6px;
    left: 50%;
    transform: translateX(-50%);
}

.node-connector:hover {
    background: #28a745;
    transform: translateX(-50%) scale(1.2);
}

.toolbar {
    position: absolute;
    top: 10px;
    left: 10px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 5px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    z-index: 100;
}

.minimap {
    position: absolute;
    bottom: 10px;
    right: 10px;
    width: 150px;
    height: 100px;
    background: rgba(255,255,255,0.9);
    border: 1px solid #ddd;
    border-radius: 6px;
    z-index: 100;
}

@media (max-width: 768px) {
    .workflow-canvas {
        height: 400px;
    }
    
    .node {
        width: 60px;
        height: 45px;
        font-size: 10px;
    }
    
    .node i {
        font-size: 14px;
        margin-bottom: 2px;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let workflowData = {
        nodes: [],
        connections: [],
        properties: {}
    };
    
    let selectedNode = null;
    let selectedConnection = null;
    let isDragging = false;
    let dragOffset = { x: 0, y: 0 };
    let isConnecting = false;
    let connectionStart = null;
    let canvas = document.getElementById('workflowCanvas');
    let canvasContent = canvas.querySelector('.canvas-content');
    let connectionSvg = document.getElementById('connectionSvg');
    let zoom = 1;
    let nodeCounter = 0;
    
    // Initialize workflow designer
    initializeWorkflowDesigner();
    
    function initializeWorkflowDesigner() {
        setupDragAndDrop();
        setupCanvasEvents();
        setupToolbarEvents();
        setupPropertyPanel();
        updateStats();
    }
    
    function setupDragAndDrop() {
        // Make node templates draggable
        document.querySelectorAll('.workflow-node-template').forEach(template => {
            template.addEventListener('dragstart', function(e) {
                e.dataTransfer.setData('text/plain', this.dataset.nodeType);
                e.dataTransfer.effectAllowed = 'copy';
            });
        });
        
        // Setup canvas drop zone
        canvasContent.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
        });
        
        canvasContent.addEventListener('drop', function(e) {
            e.preventDefault();
            const nodeType = e.dataTransfer.getData('text/plain');
            const rect = canvasContent.getBoundingClientRect();
            const x = (e.clientX - rect.left) / zoom;
            const y = (e.clientY - rect.top) / zoom;
            
            createNode(nodeType, x, y);
            hideDropZoneMessage();
        });
    }
    
    function setupCanvasEvents() {
        // Canvas click for deselection
        canvasContent.addEventListener('click', function(e) {
            if (e.target === this || e.target.classList.contains('canvas-grid')) {
                deselectAll();
            }
        });
        
        // Mouse events for node manipulation
        canvasContent.addEventListener('mousedown', handleMouseDown);
        document.addEventListener('mousemove', handleMouseMove);
        document.addEventListener('mouseup', handleMouseUp);
    }
    
    function setupToolbarEvents() {
        // Zoom controls
        document.getElementById('zoomIn').addEventListener('click', () => setZoom(zoom + 0.1));
        document.getElementById('zoomOut').addEventListener('click', () => setZoom(zoom - 0.1));
        document.getElementById('zoomReset').addEventListener('click', () => setZoom(1));
        
        // Clear canvas
        document.getElementById('clearCanvas').addEventListener('click', function() {
            if (confirm('Are you sure you want to clear the entire workflow?')) {
                clearWorkflow();
            }
        });
        
        // Quick actions
        document.getElementById('addStartNode').addEventListener('click', () => createNode('start', 100, 100));
        document.getElementById('addEndNode').addEventListener('click', () => createNode('end', 500, 300));
        document.getElementById('autoLayout').addEventListener('click', autoLayoutNodes);
        
        // Main toolbar
        document.getElementById('saveWorkflowBtn').addEventListener('click', showSaveModal);
        document.getElementById('loadWorkflowBtn').addEventListener('click', showLoadModal);
        document.getElementById('previewWorkflowBtn').addEventListener('click', showPreviewModal);
        document.getElementById('publishWorkflowBtn').addEventListener('click', publishWorkflow);
        document.getElementById('exportWorkflow').addEventListener('click', exportWorkflowJson);
    }
    
    function setupPropertyPanel() {
        // Property panel will be updated when nodes are selected
    }
    
    function createNode(nodeType, x, y) {
        const nodeId = `node_${++nodeCounter}`;
        const nodeElement = document.createElement('div');
        nodeElement.className = 'workflow-node';
        nodeElement.dataset.nodeId = nodeId;
        nodeElement.dataset.nodeType = nodeType;
        nodeElement.style.left = x + 'px';
        nodeElement.style.top = y + 'px';
        
        // Create node content based on type
        const nodeContent = createNodeContent(nodeType);
        nodeElement.innerHTML = nodeContent;
        
        // Add connectors
        if (nodeType !== 'start') {
            nodeElement.innerHTML += '<div class="node-connector input"></div>';
        }
        if (nodeType !== 'end') {
            nodeElement.innerHTML += '<div class="node-connector output"></div>';
        }
        
        // Add event listeners
        nodeElement.addEventListener('click', (e) => selectNode(nodeElement, e));
        nodeElement.addEventListener('dblclick', (e) => editNode(nodeElement, e));
        
        // Add connector events
        setupNodeConnectors(nodeElement);
        
        canvasContent.appendChild(nodeElement);
        
        // Add to workflow data
        const nodeData = {
            id: nodeId,
            type: nodeType,
            position: { x, y },
            properties: getDefaultNodeProperties(nodeType)
        };
        
        workflowData.nodes.push(nodeData);
        updateStats();
        
        return nodeElement;
    }
    
    function createNodeContent(nodeType) {
        const nodeConfigs = {
            start: { icon: 'fas fa-play', label: 'Start', class: 'node-start' },
            end: { icon: 'fas fa-stop', label: 'End', class: 'node-end' },
            status: { icon: 'fas fa-flag', label: 'Status', class: 'node-status' },
            approval: { icon: 'fas fa-user-check', label: 'Approval', class: 'node-approval' },
            notification: { icon: 'fas fa-bell', label: 'Notify', class: 'node-action' },
            email: { icon: 'fas fa-envelope', label: 'Email', class: 'node-action' },
            webhook: { icon: 'fas fa-link', label: 'Webhook', class: 'node-action' },
            task: { icon: 'fas fa-tasks', label: 'Task', class: 'node-action' },
            condition: { icon: 'fas fa-question', label: 'Condition', class: 'node-condition' },
            delay: { icon: 'fas fa-clock', label: 'Delay', class: 'node-condition' }
        };
        
        const config = nodeConfigs[nodeType] || nodeConfigs.status;
        
        return `
            <div class="node ${config.class}">
                <i class="${config.icon}"></i>
                <small>${config.label}</small>
            </div>
        `;
    }
    
    function getDefaultNodeProperties(nodeType) {
        const defaults = {
            start: { name: 'Start', description: 'Workflow starting point' },
            end: { name: 'End', description: 'Workflow ending point' },
            status: { name: 'Set Status', status: 'draft', description: 'Change contract status' },
            approval: { name: 'Approval Required', approver: '', description: 'Require approval to proceed' },
            notification: { name: 'Send Notification', recipients: [], message: '' },
            email: { name: 'Send Email', template: '', recipients: [] },
            webhook: { name: 'Call Webhook', url: '', method: 'POST' },
            task: { name: 'Create Task', assignee: '', description: '' },
            condition: { name: 'Check Condition', conditions: [] },
            delay: { name: 'Wait', duration: 1, unit: 'days' }
        };
        
        return defaults[nodeType] || defaults.status;
    }
    
    function setupNodeConnectors(nodeElement) {
        const inputConnector = nodeElement.querySelector('.node-connector.input');
        const outputConnector = nodeElement.querySelector('.node-connector.output');
        
        if (outputConnector) {
            outputConnector.addEventListener('mousedown', (e) => startConnection(nodeElement, e));
        }
        
        if (inputConnector) {
            inputConnector.addEventListener('mouseup', (e) => endConnection(nodeElement, e));
        }
    }
    
    function handleMouseDown(e) {
        if (e.target.classList.contains('workflow-node') || e.target.closest('.workflow-node')) {
            const node = e.target.closest('.workflow-node');
            startDragging(node, e);
        }
    }
    
    function handleMouseMove(e) {
        if (isDragging && selectedNode) {
            const rect = canvasContent.getBoundingClientRect();
            const x = (e.clientX - rect.left - dragOffset.x) / zoom;
            const y = (e.clientY - rect.top - dragOffset.y) / zoom;
            
            selectedNode.style.left = Math.max(0, x) + 'px';
            selectedNode.style.top = Math.max(0, y) + 'px';
            
            updateNodePosition(selectedNode, x, y);
            redrawConnections();
        }
    }
    
    function handleMouseUp(e) {
        isDragging = false;
        if (selectedNode) {
            selectedNode.classList.remove('dragging');
        }
    }
    
    function startDragging(node, e) {
        isDragging = true;
        selectedNode = node;
        node.classList.add('dragging');
        
        const rect = node.getBoundingClientRect();
        const canvasRect = canvasContent.getBoundingClientRect();
        dragOffset.x = (e.clientX - rect.left);
        dragOffset.y = (e.clientY - rect.top);
        
        selectNode(node, e);
        e.preventDefault();
    }
    
    function selectNode(node, e) {
        deselectAll();
        selectedNode = node;
        node.classList.add('selected');
        showNodeProperties(node);
        e.stopPropagation();
    }
    
    function deselectAll() {
        document.querySelectorAll('.workflow-node.selected').forEach(node => {
            node.classList.remove('selected');
        });
        document.querySelectorAll('.connection-line.selected').forEach(line => {
            line.classList.remove('selected');
        });
        selectedNode = null;
        selectedConnection = null;
        showDefaultProperties();
    }
    
    function showNodeProperties(node) {
        const nodeId = node.dataset.nodeId;
        const nodeData = workflowData.nodes.find(n => n.id === nodeId);
        
        if (!nodeData) return;
        
        const propertiesPanel = document.getElementById('nodeProperties');
        propertiesPanel.innerHTML = generatePropertiesForm(nodeData);
        
        // Add event listeners for property changes
        propertiesPanel.querySelectorAll('input, select, textarea').forEach(input => {
            input.addEventListener('change', function() {
                updateNodeProperty(nodeId, this.name, this.value);
            });
        });
    }
    
    function generatePropertiesForm(nodeData) {
        const baseForm = `
            <div class="mb-3">
                <label class="form-label">Node Name</label>
                <input type="text" class="form-control form-control-sm" name="name" 
                       value="${nodeData.properties.name || ''}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control form-control-sm" name="description" rows="2"
                          placeholder="Describe what this node does...">${nodeData.properties.description || ''}</textarea>
            </div>
        `;
        
        let specificForm = '';
        
        switch (nodeData.type) {
            case 'status':
                specificForm = `
                    <div class="mb-3">
                        <label class="form-label">New Status</label>
                        <select class="form-select form-select-sm" name="status">
                            <option value="draft" ${nodeData.properties.status === 'draft' ? 'selected' : ''}>Draft</option>
                            <option value="pending_review" ${nodeData.properties.status === 'pending_review' ? 'selected' : ''}>Pending Review</option>
                            <option value="approved" ${nodeData.properties.status === 'approved' ? 'selected' : ''}>Approved</option>
                            <option value="active" ${nodeData.properties.status === 'active' ? 'selected' : ''}>Active</option>
                            <option value="terminated" ${nodeData.properties.status === 'terminated' ? 'selected' : ''}>Terminated</option>
                        </select>
                    </div>
                `;
                break;
                
            case 'approval':
                specificForm = `
                    <div class="mb-3">
                        <label class="form-label">Approver</label>
                        <select class="form-select form-select-sm" name="approver">
                            <option value="">Select approver...</option>
                            <option value="manager">Manager</option>
                            <option value="owner">Account Owner</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                `;
                break;
                
            case 'notification':
                specificForm = `
                    <div class="mb-3">
                        <label class="form-label">Recipients</label>
                        <select class="form-select form-select-sm" name="recipients" multiple>
                            <option value="client">Client</option>
                            <option value="manager">Manager</option>
                            <option value="owner">Account Owner</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control form-control-sm" name="message" rows="3"
                                  placeholder="Notification message...">${nodeData.properties.message || ''}</textarea>
                    </div>
                `;
                break;
                
            case 'delay':
                specificForm = `
                    <div class="row">
                        <div class="col-8">
                            <label class="form-label">Duration</label>
                            <input type="number" class="form-control form-control-sm" name="duration" 
                                   min="1" value="${nodeData.properties.duration || 1}">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Unit</label>
                            <select class="form-select form-select-sm" name="unit">
                                <option value="minutes" ${nodeData.properties.unit === 'minutes' ? 'selected' : ''}>Minutes</option>
                                <option value="hours" ${nodeData.properties.unit === 'hours' ? 'selected' : ''}>Hours</option>
                                <option value="days" ${nodeData.properties.unit === 'days' ? 'selected' : ''}>Days</option>
                                <option value="weeks" ${nodeData.properties.unit === 'weeks' ? 'selected' : ''}>Weeks</option>
                            </select>
                        </div>
                    </div>
                `;
                break;
        }
        
        return baseForm + specificForm;
    }
    
    function showDefaultProperties() {
        const propertiesPanel = document.getElementById('nodeProperties');
        propertiesPanel.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="fas fa-mouse-pointer fa-2x mb-2"></i>
                <p class="mb-0">Select an element to configure its properties</p>
            </div>
        `;
    }
    
    function updateNodeProperty(nodeId, property, value) {
        const nodeData = workflowData.nodes.find(n => n.id === nodeId);
        if (nodeData) {
            nodeData.properties[property] = value;
        }
    }
    
    function updateNodePosition(nodeElement, x, y) {
        const nodeId = nodeElement.dataset.nodeId;
        const nodeData = workflowData.nodes.find(n => n.id === nodeId);
        if (nodeData) {
            nodeData.position = { x, y };
        }
    }
    
    function startConnection(fromNode, e) {
        isConnecting = true;
        connectionStart = fromNode;
        e.stopPropagation();
    }
    
    function endConnection(toNode, e) {
        if (isConnecting && connectionStart && connectionStart !== toNode) {
            createConnection(connectionStart, toNode);
        }
        isConnecting = false;
        connectionStart = null;
        e.stopPropagation();
    }
    
    function createConnection(fromNode, toNode) {
        const connectionId = `conn_${fromNode.dataset.nodeId}_${toNode.dataset.nodeId}`;
        
        // Check if connection already exists
        if (workflowData.connections.find(c => c.id === connectionId)) {
            return;
        }
        
        const connectionData = {
            id: connectionId,
            from: fromNode.dataset.nodeId,
            to: toNode.dataset.nodeId,
            properties: {}
        };
        
        workflowData.connections.push(connectionData);
        redrawConnections();
        updateStats();
    }
    
    function redrawConnections() {
        const svg = connectionSvg;
        svg.innerHTML = svg.innerHTML.replace(/<path.*?\/>/g, ''); // Clear existing paths but keep defs
        
        workflowData.connections.forEach(connection => {
            const fromNode = canvasContent.querySelector(`[data-node-id="${connection.from}"]`);
            const toNode = canvasContent.querySelector(`[data-node-id="${connection.to}"]`);
            
            if (fromNode && toNode) {
                drawConnection(fromNode, toNode, connection.id);
            }
        });
    }
    
    function drawConnection(fromNode, toNode, connectionId) {
        const fromRect = fromNode.getBoundingClientRect();
        const toRect = toNode.getBoundingClientRect();
        const canvasRect = canvasContent.getBoundingClientRect();
        
        const startX = (fromRect.left + fromRect.width / 2 - canvasRect.left) / zoom;
        const startY = (fromRect.bottom - canvasRect.top) / zoom;
        const endX = (toRect.left + toRect.width / 2 - canvasRect.left) / zoom;
        const endY = (toRect.top - canvasRect.top) / zoom;
        
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        const d = `M ${startX} ${startY} Q ${startX} ${startY + 50} ${endX} ${endY}`;
        
        path.setAttribute('d', d);
        path.setAttribute('class', 'connection-line');
        path.setAttribute('data-connection-id', connectionId);
        
        // Add click event for connection selection
        path.addEventListener('click', (e) => {
            selectConnection(connectionId, e);
        });
        
        connectionSvg.appendChild(path);
    }
    
    function selectConnection(connectionId, e) {
        deselectAll();
        selectedConnection = connectionId;
        const connectionElement = connectionSvg.querySelector(`[data-connection-id="${connectionId}"]`);
        if (connectionElement) {
            connectionElement.classList.add('selected');
        }
        e.stopPropagation();
    }
    
    function setZoom(newZoom) {
        zoom = Math.max(0.1, Math.min(2, newZoom));
        canvasContent.style.transform = `scale(${zoom})`;
        document.getElementById('zoomReset').textContent = Math.round(zoom * 100) + '%';
        redrawConnections();
    }
    
    function hideDropZoneMessage() {
        const message = canvas.querySelector('.drop-zone-message');
        if (message) {
            message.style.display = 'none';
        }
    }
    
    function showDropZoneMessage() {
        const message = canvas.querySelector('.drop-zone-message');
        if (message) {
            message.style.display = 'block';
        }
    }
    
    function updateStats() {
        document.getElementById('nodeCount').textContent = workflowData.nodes.length;
        document.getElementById('connectionCount').textContent = workflowData.connections.length;
        
        // Update validation status
        const hasStart = workflowData.nodes.some(n => n.type === 'start');
        const hasEnd = workflowData.nodes.some(n => n.type === 'end');
        const validationElement = document.getElementById('validationStatus');
        
        if (hasStart && hasEnd && workflowData.nodes.length > 0) {
            validationElement.innerHTML = '<i class="fas fa-check-circle"></i> Valid';
            validationElement.className = 'text-success';
        } else {
            validationElement.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Incomplete';
            validationElement.className = 'text-warning';
        }
        
        // Show/hide drop zone message
        if (workflowData.nodes.length === 0) {
            showDropZoneMessage();
        } else {
            hideDropZoneMessage();
        }
    }
    
    function clearWorkflow() {
        workflowData.nodes = [];
        workflowData.connections = [];
        canvasContent.querySelectorAll('.workflow-node').forEach(node => node.remove());
        redrawConnections();
        updateStats();
        showDefaultProperties();
        nodeCounter = 0;
    }
    
    function autoLayoutNodes() {
        if (workflowData.nodes.length === 0) return;
        
        // Simple automatic layout algorithm
        const padding = 150;
        const startX = padding;
        let currentY = 100;
        
        workflowData.nodes.forEach((nodeData, index) => {
            const x = startX + (index * 120);
            const y = currentY + (Math.floor(index / 5) * 120);
            
            nodeData.position = { x, y };
            
            const nodeElement = canvasContent.querySelector(`[data-node-id="${nodeData.id}"]`);
            if (nodeElement) {
                nodeElement.style.left = x + 'px';
                nodeElement.style.top = y + 'px';
            }
        });
        
        redrawConnections();
    }
    
    function showSaveModal() {
        const modal = new bootstrap.Modal(document.getElementById('saveWorkflowModal'));
        document.getElementById('saveWorkflowName').value = document.getElementById('workflowName').value;
        modal.show();
    }
    
    function showLoadModal() {
        const modal = new bootstrap.Modal(document.getElementById('loadWorkflowModal'));
        loadSavedWorkflows();
        modal.show();
    }
    
    function showPreviewModal() {
        const modal = new bootstrap.Modal(document.getElementById('previewWorkflowModal'));
        generateWorkflowPreview();
        modal.show();
    }
    
    function loadSavedWorkflows() {
        // This would typically load from a backend API
        const savedWorkflows = [
            { id: 1, name: 'Standard Service Agreement', version: '1.0', created_at: '2025-01-15' },
            { id: 2, name: 'MSP Contract Flow', version: '2.1', created_at: '2025-01-20' },
            { id: 3, name: 'Consulting Agreement', version: '1.5', created_at: '2025-01-22' }
        ];
        
        const container = document.getElementById('savedWorkflows');
        container.innerHTML = savedWorkflows.map(workflow => `
            <div class="list-group-item list-group-item-action" data-workflow-id="${workflow.id}">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">${workflow.name}</h6>
                    <small>v${workflow.version}</small>
                </div>
                <p class="mb-1">Created: ${workflow.created_at}</p>
            </div>
        `).join('');
    }
    
    function generateWorkflowPreview() {
        const previewDiagram = document.getElementById('previewDiagram');
        const previewSteps = document.getElementById('previewSteps');
        
        // Generate simplified diagram
        previewDiagram.innerHTML = '<p class="text-muted">Workflow diagram preview would be shown here</p>';
        
        // Generate steps list
        const steps = workflowData.nodes.map((node, index) => `
            <div class="d-flex align-items-center mb-2">
                <div class="badge bg-primary rounded-circle me-2" style="width: 24px; height: 24px; line-height: 24px;">${index + 1}</div>
                <div>
                    <strong>${node.properties.name || node.type}</strong>
                    ${node.properties.description ? `<br><small class="text-muted">${node.properties.description}</small>` : ''}
                </div>
            </div>
        `).join('');
        
        previewSteps.innerHTML = steps || '<p class="text-muted">No workflow steps defined</p>';
    }
    
    function exportWorkflowJson() {
        const exportData = {
            name: document.getElementById('workflowName').value || 'Untitled Workflow',
            description: document.getElementById('workflowDescription').value || '',
            contractType: document.getElementById('contractType').value || '',
            isActive: document.getElementById('isActive').checked,
            workflow: workflowData,
            exported_at: new Date().toISOString()
        };
        
        const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `workflow-${Date.now()}.json`;
        a.click();
        URL.revokeObjectURL(url);
    }
    
    function publishWorkflow() {
        if (!validateWorkflow()) {
            alert('Please fix validation errors before publishing.');
            return;
        }
        
        if (confirm('Are you sure you want to publish this workflow? It will become active for new contracts.')) {
            // This would typically send to backend API
            console.log('Publishing workflow:', workflowData);
            alert('Workflow published successfully!');
        }
    }
    
    function validateWorkflow() {
        const hasStart = workflowData.nodes.some(n => n.type === 'start');
        const hasEnd = workflowData.nodes.some(n => n.type === 'end');
        
        return hasStart && hasEnd && workflowData.nodes.length > 0;
    }
});
</script>
@endpush