<div class="card card-datatable">
    <table class="table table-striped datatables-basic">
        <thead>
            <tr>
                <th>Item Name</th>
                <th>Location</th>
                <th>Asset Tag</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item) { ?>
                <tr>
                    <td><?php echo $item['product_name']; ?></td>
                    <td><?php echo $item['inventory_location_name']; ?></td>
                    <td>
                        <div class="barcode-container" id="label-<?php echo $item['inventory_asset_tag']; ?>">
                            <svg id="barcode-<?php echo $item['inventory_asset_tag']; ?>"></svg>
                            <button class="btn btn-sm btn-primary" onclick="showLabelPositionModal('<?php echo $item['inventory_asset_tag']; ?>')">
                                <i class="fa fa-print"></i>
                            </button>
                        </div>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-secondary loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="inventory_item_edit_modal.php?inventory_id=<?php echo $item['inventory_id']; ?>">
                            <i class="fa fa-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-label-success loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="inventory_item_assign_client_modal.php?inventory_id=<?php echo $item['inventory_id']; ?>">
                            Assign to Client
                        </button>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
<!-- Modal -->
<div class="modal fade" id="labelPositionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Label Position</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Select which label position to print to (1-30):</p>
                <div class="label-grid">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .barcode-container {
        padding: 5px;
    }
    .barcode-container svg {
        max-width: 450px;
    }
    .label-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        padding: 15px;
    }
    .label-grid button {
        padding: 10px;
        text-align: center;
    }
    @media print {
        body * {
            visibility: hidden;
        }
        .barcode-container.print-me, .barcode-container.print-me * {
            visibility: visible;
        }
    }
</style>

<!-- Include required JavaScript libraries -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    let itemsTable; // Declare the table variable globally

    // Initialize barcodes and setup table when page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize barcodes
        <?php foreach ($items as $item): ?>
            JsBarcode("#barcode-<?php echo $item['inventory_asset_tag']; ?>", 
                "<?php echo $item['inventory_asset_tag']; ?>", {
                format: "CODE128",
                width: 1.5,
                height: 40,
                displayValue: true,
                fontSize: 12,
                margin: 10
            });
        <?php endforeach; ?>

        // Create the grid of position buttons
        const grid = document.querySelector('.label-grid');
        for (let i = 1; i <= 30; i++) {
            const button = document.createElement('button');
            button.className = 'btn btn-outline-primary';
            button.textContent = i;
            grid.appendChild(button);
        }

        // Initialize DataTable when the tab is shown
        document.querySelector('button[data-bs-target="#navs-left-align-inventory-items"]').addEventListener('shown.bs.tab', function (e) {
            if (!itemsTable) {
                itemsTable = $('.datatables-basic').DataTable({
                    responsive: true,
                    stateSave: true,
                    destroy: true,
                    // Add any other DataTable options you need
                });
            }
            // Adjust columns when tab is shown
            itemsTable.columns.adjust().responsive.recalc();
        });
    });

    // Initialize the modal
    const labelModal = new bootstrap.Modal(document.getElementById('labelPositionModal'));
    let currentAssetTag = '';

    function showLabelPositionModal(assetTag) {
        currentAssetTag = assetTag;
        
        // Update click handlers for all grid buttons
        const buttons = document.querySelectorAll('.label-grid button');
        buttons.forEach(button => {
            const position = parseInt(button.textContent);
            button.onclick = () => {
                printLabel(currentAssetTag, position);
                labelModal.hide();
            };
        });
        
        labelModal.show();
    }

    function printLabel(assetTag, position) {
        const labelContent = document.getElementById(`label-${assetTag}`).innerHTML;
        const printWindow = window.open('', '', 'height=400,width=800');
        
        // Calculate position (3 columns, 10 rows)
        const row = Math.floor((position - 1) / 3);
        const col = (position - 1) % 3;
        
        const top = row * 0.75; // Adjust if labels print too high/low
        const left = col * 2;   // Adjust if labels print too left/right

        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
                <head>
                    <title>Print Label</title>
                    <style>
                        @page {
                            size: letter;
                            margin: 0.5in 0.219in;
                        }
                        body {
                            margin: 0;
                            padding: 0;
                        }
                        .label-container {
                            position: absolute;
                            top: ${top}in;
                            left: ${left}in;
                            width: 2in;
                            height: 0.75in;
                            text-align: center;
                            overflow: hidden;
                        }
                        .barcode-container {
                            transform: scale(0.8);
                            transform-origin: top center;
                        }
                        .barcode-container svg {
                            max-width: 100%;
                            height: auto;
                        }
                    </style>
                </head>
                <body>
                    <div class="label-container">
                        ${labelContent}
                    </div>
                    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script>
                    <script>
                        window.onload = function() {
                            JsBarcode("#barcode-${assetTag}", "${assetTag}", {
                                format: "CODE128",
                                width: 1.2,
                                height: 30,
                                displayValue: true,
                                fontSize: 8,
                                margin: 5
                            });
                            setTimeout(function() {
                                window.print();
                                window.onafterprint = function() {
                                    window.close();
                                };
                            }, 500);
                        };
                    <\/script>
                </body>
            </html>
        `);
        printWindow.document.close();
    }
</script>
