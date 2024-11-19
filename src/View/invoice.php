<!-- src/view/invoice.php -->
<?php
// src/View/invoice.php
// This view is used to display the invoice, quote, and recurring invoice pages


if (isset($invoice)) {
    $wording = 'Invoice';
    $invoice_items = $invoice['items'];
    $invoice_number = $invoice['invoice_number'];
    $invoice_date = $invoice['invoice_date'];
    $invoice_due = $invoice['invoice_due'];
    $invoice_status = $invoice['invoice_status'];
    $invoice_id = $invoice['invoice_id'];
    $invoice_prefix = $invoice['invoice_prefix'];
    $invoice_currency_code = $invoice['invoice_currency_code'];
    $invoice_url_key = $invoice['invoice_url_key'];
    $invoice_add_item_modal = 'invoice_add_item_modal.php?invoice_id=' . $invoice_id;
    $invoice_note = $invoice['invoice_note'];
    $invoice_deposit_amount = $invoice['invoice_deposit_amount'];
} else if (isset($quote)) {
    $wording = 'Quote';
    $invoice_items = $quote['items'];
    $invoice_number = $quote['quote_number'];
    $invoice_date = $quote['quote_date'];
    $invoice_due = $wording;
    $invoice_status = $quote['quote_status'];
    $invoice_id = $quote['quote_id'];
    $invoice_prefix = $quote['quote_prefix'];
    $invoice_currency_code = $quote['quote_currency_code'];
    $invoice_url_key = $quote['quote_url_key'];
    $invoice_add_item_modal = 'quote_add_item_modal.php?quote_id=' . $invoice_id;
    $invoice_note = $quote['quote_note'];
    $quote_url_key = $quote['quote_url_key'];
}

$subtotal = 0;
$discount_total = 0;
$tax_total = 0;

$company_name = $company['company_name'];
$company_address = $company['company_address'];
$company_city = $company['company_city'];
$company_state = $company['company_state'];
$company_zip = $company['company_zip'];
$company_phone = $company['company_phone'];
$company_email = $company['company_email'];
$company_website = $company['company_website'];

$taxes = $all_taxes;
$products = $all_products
?>

<div class="row">
    <!-- Invoice Edit-->
    <div class="col-lg-9 col-md-12 mb-4 order-lg-1 order-md-2 order-2">
        <div class="card invoice-preview-card">
            <div class="card-body">
                <div class="row p-sm-3 p-0">
                    <div class="col-md-6 mb-md-0 mb-4">
                        <div class="d-flex svg-illustration mb-4 gap-2">
                            <span class="app-brand-text demo text-body fw-bold"><?= $company_name; ?></span>
                        </div>
                        <p class="mb-1"><?= $company_address; ?></p>
                        <p class="mb-1"><?= "$company_city $company_state $company_zip"; ?></p>
                        <p class="mb-1"><?= "$company_phone $company_email"; ?></p>
                        <p class="mb-0"><?= $company_website; ?></p>
                    </div>
                    <div class="col-md-6">
                        <dl class="row mb-2">
                            <dt class="col-sm-6 mb-2 mb-sm-0 text-md-end">
                                <span class="h4 text-capitalize mb-0 text-nowrap"><?= $wording ?> <?= $invoice_prefix ?></span>
                            </dt>
                            <dd class="col-sm-6 d-flex justify-content-md-end">
                                <div class="w-px-150">
                                    <input type="text" class="form-control" disabled placeholder="<?= "$invoice_number"; ?>" value="<?= "$invoice_number"; ?>" id="invoiceId" />
                                </div>
                            </dd>
                            <dt class="col-sm-6 mb-2 mb-sm-0 text-md-end">
                                <span class="fw-normal">Status:</span>
                            </dt>
                            <dd class="col-sm-6 d-flex justify-content-md-end">
                                <div class="w-px-150">
                                    <select class="form-select invoice-status" id="invoiceStatus">
                                        <option value="Draft" <?= $invoice_status == 'Draft' ? 'selected' : 'disabled' ?>>Draft</option>
                                        <option value="Sent" <?= $invoice_status == 'Sent' ? 'selected' : 'disabled' ?>>Sent</option>
                                        <option value="Viewed" <?= $invoice_status == 'Viewed' ? 'selected' : 'disabled' ?>>Viewed</option>
                                        <option value="Paid" <?= $invoice_status == 'Paid' ? 'selected' : 'disabled' ?>>Paid</option>
                                        <option value="Partial" <?= $invoice_status == 'Partial' ? 'selected' : 'disabled' ?>>Partial</option>
                                        <option value="Overdue" <?= $invoice_status == 'Overdue' ? 'selected' : 'disabled' ?>>Overdue</option>
                                        <option value="Cancelled" <?= $invoice_status == 'Cancelled' ? 'selected' : 'disabled' ?>>Cancelled</option>
                                    </select>
                                </div>
                            </dd>
                            <dt class="col-sm-6 mb-2 mb-sm-0 text-md-end">
                                <span class="fw-normal">Date:</span>
                            </dt>
                            <dd class="col-sm-6 d-flex justify-content-md-end">
                                <div class="w-px-150">
                                    <input type="text" class="form-control invoice-date" placeholder="YYYY-MM-DD" value="<?= $invoice_date ?>" />
                                </div>
                            </dd>
                            <dt class="col-sm-6 mb-2 mb-sm-0 text-md-end">
                                <span class="fw-normal">Due Date:</span>
                            </dt>
                            <dd class="col-sm-6 d-flex justify-content-md-end">
                                <div class="w-px-150">
                                    <input type="text" class="form-control due-date" placeholder="YYYY-MM-DD" value="<?= $invoice_due ?>" />
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>

                <hr class="my-4 mx-n4" />

                <div class="row p-sm-3 p-0">
                    <div class="col-md-6 col-sm-5 col-12 mb-sm-0 mb-4">
                        <h6 class="pb-2"> <?= $wording ?> To:</h6>
                        <p class="mb-1"><strong><?= $contact_name; ?></strong></p>
                        <p class="mb-1"><strong><?= $client_name; ?></strong></p>
                        <p class="mb-1"><?= $location_address; ?></p>
                        <p class="mb-1"><?= "$location_city $location_state $location_zip"; ?></p>
                        <p class="mb-1"><?= "$contact_phone $contact_extension"; ?></p>
                        <p class="mb-1"><?= $contact_mobile; ?></p>
                        <p class="mb-0"><?= $contact_email; ?></p>
                    </div>
                </div>
                <div class="mb-3">
                    <div id="invoiceItemsContainer">
                        <?php
                        foreach ($invoice_items as $item) {
                            $item_id = $item['item_id'];
                            $item_name = $item['item_name'];
                            $item_description = $item['item_description'];

                            $item_price = $item['item_price'];
                            $item_qty = $item['item_quantity'];
                            $item_discount = $item['item_discount'];
                            $item_subtotal = ($item_price * $item_qty) - $item_discount;
                            $subtotal += $item_subtotal;

                            $item_tax_id = $item['item_tax_id'];
                            $item_product_id = $item['item_product_id'];
                            $item_tax_percent = $item['tax_percent'];
                            $item_tax = $item_subtotal * ($item_tax_percent / 100);

                            $tax_total += $item_tax;
                            $item_total = $item_subtotal + $item_tax;


                            $item_tax_percent = $item['tax_percent'];
                            $item_discount = $item['item_discount'];
                            if ($item_discount > 0) {
                                if ($item_price > 0) {
                                    $item_discount_percent = (($item_total / $item_price) * 100) - 1;
                                } else {
                                    $item_discount_percent = 0;
                                }
                            } else {
                                $item_discount_percent = 0;
                            }

                            $profit = 0;
                            $total += $item_total;
                        ?>

                            <hr class="mx-n4" />
                            <div class="pt-0 pt-md-4 mb-4 item-container" id="item<?= $item_id ?>">
                                <form action="/post.php" method="post" autocomplete="off" enctype="multipart/form-data">
                                    <?php if ($wording === 'Invoice') { ?>
                                        <input type="hidden" name="invoice_id" value="<?= $invoice_id ?>" />
                                    <?php } ?>
                                    <?php if ($wording === 'Quote') { ?>
                                        <input type="hidden" name="quote_id" value="<?= $invoice_id ?>" />
                                    <?php } ?>
                                    <input type="hidden" name="item_id" value="<?= $item_id ?>" />
                                    <div class="d-flex border rounded position-relative pe-0">
                                        <div class="d-flex flex-column align-items-center justify-content-between border-start p-2">
                                            <i class="fas fa-arrows-alt drag-handle cursor-pointer cursor-handle"></i> <!-- Drag handle icon -->
                                        </div>
                                        <div class="row w-100 m-0 p-3">
                                            <div class="col-md-7 col-12 mb-md-0 mb-3 ps-md-0">
                                                <p class="mb-2 repeater-title">Item</p>
                                                <input type="text" class="form-control invoice-item-name mb-2" value="<?= $item_name ?>" name="name" />
                                                <textarea class="form-control" rows="1" id="item_<?= $item_id ?>_description" name="description">
                                            <?= $item_description ?>
                                        </textarea>
                                            </div>
                                            <div class="col-md-2 col-12 mb-md-0 mb-3">
                                                <p class="mb-2 repeater-title">Unit Price</p>
                                                <input name="price" pattern="-?[0-9]*\.?[0-9]{0,2}" class="form-control invoice-item-price mb-2" value="<?= $item_price ?>" placeholder="" />
                                                <div class="d-flex me-1">
                                                    <span class="discount me-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Discount: <?= $item_discount_percent ?>%"> <?php echo $item_discount_percent ?>%</span>
                                                    <span class="tax me-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Tax: <?= $item_tax_percent ?>%"> <?php echo $item_tax_percent ?>%</span>
                                                </div>
                                                <div class="d-flex me-1">
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12 mb-md-0 mb-3">
                                                <p class="mb-2 repeater-title">Qty</p>
                                                <input name="qty" pattern="-?[0-9]*\.?[0-9]{0,2}" class="form-control" value="<?= $item_qty ?>" placeholder="<?= $item_qty ?>" min="" max="" />
                                            </div>
                                            <div class="col-md-2 col-12 pe-0">
                                                <p class="mb-2 repeater-title">Line Total</p>
                                                <p class="mb-0"><?= numfmt_format_currency($currency_format, $item_total, $invoice_currency_code) ?></p>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-column align-items-center justify-content-between border-start p-2">
                                            <a href="/post.php?delete_invoice_item=<?= $item_id ?>&invoice_id=<?= $invoice_id ?>" class="confirm-link">
                                                <i class="bx bx-x fs-4 text-muted cursor-pointer"></i>
                                            </a>
                                            <button id="SaveItem<?= $item_id ?>" type="submit" name="edit_item" class="btn btn-link text-primary p-0" data-bs-toggle="tooltip" data-bs-placement="top" title="Save Changes" hidden>
                                                <i class="bx bx-check fs-4"></i>
                                            </button>
                                            <div class="dropdown">
                                                <i class="bx bx-cog bx-xs text-muted cursor-pointer more-options-dropdown" role="button" id="dropdownMenuButton" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"></i>
                                                <div class="dropdown-menu dropdown-menu-end w-px-300 p-3" aria-labelledby="dropdownMenuButton">
                                                    <div class="row g-3">
                                                        <div class="col-6" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= $invoice_currency_code; ?> or end with % ">
                                                            <label for="discountInput" class="form-label">Discount (<?= $invoice_currency_code; ?>) </label>
                                                            <input class="form-control" name="discount" id="discount" <?= $item_discount ? 'value="' . $item_discount . '"' : 'placeholder="0%"' ?> />
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="taxInput1" class="form-label">Tax</label>
                                                            <select class="form-select select2 invoice-item-tax mb-2" name="tax_id" id="tax" style="width: 100%;">
                                                                <option value="0">No Tax</option>
                                                                <?php foreach ($taxes as $tax) { ?>
                                                                    <option value="<?= $tax['tax_id'] ?>" <?= $tax['tax_id'] == $item_tax_id ? 'selected' : '' ?>><?= $tax['tax_name'] ?></option>
                                                                <?php } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php } ?>
                    </div>

                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <button type="button" class="btn btn-primary loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="<?php echo $invoice_add_item_modal; ?>">
                        <i class="bx bx-plus me-1"></i>Add Item
                    </button>
                </div>


                <hr class="my-4 mx-n4" />

                <div class="row py-sm-3">
                    <div class="col-md-8 mb-md-0 mb-3">
                        <div class="mb-3">
                            <form action="/post.php" method="post" autocomplete="off">
                                <?php if ($wording === 'Invoice') { ?>
                                    <input type="hidden" name="invoice_id" value="<?= $invoice_id ?>" />
                                <?php } ?>
                                <?php if ($wording === 'Quote') { ?>
                                    <input type="hidden" name="quote_id" value="<?= $invoice_id ?>" />
                                <?php } ?>
                                <label for="note" class="form-label fw-medium">Note:</label>
                                <textarea class="form-control" rows="2" id="note" name="note"><?= $invoice_note ?></textarea>
                                <button type="submit" name="edit_<?= $wording === 'Invoice' ? 'invoice' : 'quote' ?>_note" class="btn btn-primary mt-2">
                                    <i class="bx bx-save"></i> Save Note
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex justify-content-end">
                        <div class="invoice-calculations">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="w-px-100">Subtotal:</span>
                                <span class="fw-medium"><?= numfmt_format_currency($currency_format, $subtotal, $invoice_currency_code) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="w-px-100">Discount:</span>
                                <span class="fw-medium"><?= numfmt_format_currency($currency_format, $discount_total, $invoice_currency_code) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="w-px-100">Tax:</span>
                                <span class="fw-medium"><?= numfmt_format_currency($currency_format, $tax_total, $invoice_currency_code) ?></span>
                            </div>
                            <hr />
                            <div class="d-flex justify-content-between">
                                <span class="w-px-100">Total:</span>
                                <span class="fw-medium"><?= numfmt_format_currency($currency_format, $total - $discount_total, $invoice_currency_code) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Invoice Edit-->

    <!-- Include the sidebar component -->
    <?php include __DIR__ . '/Components/invoice_sidebar.php'; ?>
</div>

<!-- Include jQuery UI -->
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>

<script>
    $(document).ready(function() {
        $(document).on('modalContentLoaded', function() {
            // Bind event handlers to the inputs after the modal content has been loaded
            // Get the description of the selected product

            $(function() {
                var availableProducts = <?= json_encode($all_products) ?>;
                var zIndex = $('#name').css('z-index');

                $("#name").autocomplete({
                    source: availableProducts,
                    select: function(event, ui) {
                        $("#name").val(ui.item.label); // Product name field - this seemingly has to referenced as label
                        $("#desc").val(ui.item.description); // Product description field
                        $("#qty").val(1); // Product quantity field automatically make it a 1
                        $("#price").val(ui.item.price); // Product price field
                        $("#product_id").val(ui.item.productId); // Product ID field
                        $(".invoice-item-tax-modal").val(ui.item.tax).trigger('change'); // Product tax field
                        if (tinymce.get("desc")) { // Check if the TinyMCE instance for 'desc' exists
                            tinymce.get("desc").setContent(ui.item.description);
                        }
                        updateLineTotal();
                        return false;
                    }
                });

                // Event listeners for when the inputs are changed
                $('#price, #qty, .invoice-item-discount').on('input', function() {
                    updateLineTotal(); // Call the update function when price, qty, or discount changes
                });

                $('.invoice-item-tax-modal').on('change', function() {
                    updateLineTotal();
                });

                console.log("Length: ", $('.invoice-item-tax').length); // Check how many selects with this class are present
                $('.invoice-item-tax').each(function() {
                    console.log("Num Options: ", $(this).find('option:selected').length); // Check how many options are selected in each
                    console.log("Data Rata: ", $(this).find('option:selected').data('rate')); // Log the data rate of selected options
                });

                function updateLineTotal() {
                    var price = parseFloat($('#price').val()) || 0; // Get the price or 0 if empty
                    var qty = parseFloat($('#qty').val()) || 0; // Get the quantity or 0 if empty
                    var discountInput = $('.invoice-item-discount').val().trim(); // Get the discount value
                    var taxRate = $('.invoice-item-tax-modal').find(':selected').data('rate') || 0;

                    var subtotal = price * qty; // Calculate the subtotal
                    var taxAmount = subtotal * (taxRate / 100); // Calculate the tax amount
                    var discount = 0; // Initialize discount

                    if (discountInput.endsWith('%')) {
                        var discountPercentage = parseFloat(discountInput) || 0; // Parse the percentage number
                        discount = (subtotal * discountPercentage / 100); // Calculate percentage-based discount
                    } else {
                        discount = parseFloat(discountInput) || 0; // Otherwise, treat it as a fixed amount
                    }

                    var total = subtotal + taxAmount - discount; // Calculate the total after tax and discount
                    $('.invoice-item-total').val(total.toFixed(2)); // Set the calculated total, formatted to 2 decimal places
                }
            });
        });





        // Find all input, textarea, and select elements within any 'item-container' div
        document.querySelectorAll('.item-container input, .item-container textarea, .item-container select').forEach(function(element) {
            element.addEventListener('focus', function() {
                // Find the closest parent element with the class 'item-container'
                var itemContainer = this.closest('.item-container');

                // Find the save button within this container and show it
                var saveButton = itemContainer.querySelector('.btn[data-bs-original-title="Save Changes"]');
                if (saveButton) {
                    saveButton.hidden = false;
                }
            });
        });

    });

    document.addEventListener('DOMContentLoaded', function() {
        initializeSortable();
    });

    function initializeSortable() {
        if (typeof Sortable !== 'undefined') {
            Sortable.create(document.getElementById('invoiceItemsContainer'), {
                animation: 150,
                handle: '.drag-handle', // Use the drag handle
                onEnd: function(evt) {
                    var itemElements = Array.from(evt.from.children);
                    var itemOrder = itemElements.map(function(itemElement) {
                        var inputElement = itemElement.querySelector('input[name="item_id"]');
                        return inputElement ? inputElement.value : null;
                    }).filter(function(value) {
                        return value !== null;
                    });

                    // You can send this itemOrder array to the server to save the new order
                    saveOrder(itemOrder);

                    console.log(itemOrder); // Debugging: see the new order
                }
            });
        }
    }

    function saveOrder(order) {
        fetch('/post.php?save_invoice_item_order=<?= $invoice_id ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    order: order
                })
            })
            .then(response => response.json())
            .then(data => console.log(data))
            .catch(error => console.error('Error:', error));
    }

    // Re-initialize SortableJS after dynamic content update
    function updateInvoiceItems(newItems) {
        document.getElementById('invoiceItemsContainer').innerHTML = newItems;
        initializeSortable();
    }
</script>


<style>
    .ui-autocomplete {
        z-index: 9999999;
    }
</style>

<!-- Add this closing div -->
