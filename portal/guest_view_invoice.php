<?php

require_once "/var/www/portal.twe.tech/portal/guest_header.php";

require_once "/var/www/portal.twe.tech/portal/portal_header.php";

if (!isset($_GET['invoice_id'], $_GET['url_key'])) {
    echo "<br><h2>Oops, something went wrong! Please raise a ticket if you believe this is an error.</h2>";
    require_once "portal/guest_footer.php";

    exit();
}

$url_key = sanitizeInput($_GET['url_key']);
$invoice_id = intval($_GET['invoice_id']);

$sql = mysqli_query(
    $mysqli,
    "SELECT * FROM invoices
    LEFT JOIN clients ON invoice_client_id = client_id
    LEFT JOIN locations ON clients.client_id = locations.location_client_id AND location_primary = 1
    LEFT JOIN contacts ON clients.client_id = contacts.contact_client_id AND contact_primary = 1
    WHERE invoice_id = $invoice_id
    AND invoice_url_key = '$url_key'"
);

if (mysqli_num_rows($sql) !== 1) {
    // Invalid invoice/key
    echo "<br><h2>Oops, something went wrong! Please raise a ticket if you believe this is an error.</h2>";
    require_once "portal/guest_footer.php";

    exit();
}

$row = mysqli_fetch_array($sql);

$invoice_id = intval($row['invoice_id']);
$invoice_prefix = nullable_htmlentities($row['invoice_prefix']);
$invoice_number = intval($row['invoice_number']);
$invoice_status = nullable_htmlentities($row['invoice_status']);
$invoice_date = nullable_htmlentities($row['invoice_date']);
$invoice_due = nullable_htmlentities($row['invoice_due']);
$invoice_discount = floatval($row['invoice_discount_amount']);
$invoice_amount = floatval($row['invoice_amount']);
$invoice_currency_code = nullable_htmlentities($row['invoice_currency_code']);
$invoice_note = nullable_htmlentities($row['invoice_note']);
$invoice_category_id = intval($row['invoice_category_id']);
$client_id = intval($row['client_id']);
$client_name = nullable_htmlentities($row['client_name']);
$client_name_escaped = sanitizeInput($row['client_name']);
$location_address = nullable_htmlentities($row['location_address']);
$location_city = nullable_htmlentities($row['location_city']);
$location_state = nullable_htmlentities($row['location_state']);
$location_zip = nullable_htmlentities($row['location_zip']);
$contact_email = nullable_htmlentities($row['contact_email']);
$contact_phone = formatPhoneNumber($row['contact_phone']);
$contact_extension = nullable_htmlentities($row['contact_extension']);
$contact_mobile = formatPhoneNumber($row['contact_mobile']);
$client_website = nullable_htmlentities($row['client_website']);
$client_currency_code = nullable_htmlentities($row['client_currency_code']);
$client_net_terms = intval($row['client_net_terms']);
if ($client_net_terms == 0) {
    $client_net_terms = intval($row['config_default_net_terms']);
}

$sql = mysqli_query($mysqli, "SELECT * FROM companies, settings WHERE companies.company_id = settings.company_id AND companies.company_id = 1");
$row = mysqli_fetch_array($sql);

$company_name = nullable_htmlentities($row['company_name']);
$company_address = nullable_htmlentities($row['company_address']);
$company_city = nullable_htmlentities($row['company_city']);
$company_state = nullable_htmlentities($row['company_state']);
$company_zip = nullable_htmlentities($row['company_zip']);
$company_phone = formatPhoneNumber($row['company_phone']);
$company_email = nullable_htmlentities($row['company_email']);
$company_website = nullable_htmlentities($row['company_website']);
$company_logo = nullable_htmlentities($row['company_logo']);

if (!empty($company_logo)) {
    $company_logo_base64 = base64_encode(file_get_contents("/var/www/portal.twe.tech/uploads/settings/$company_logo"));
}
$company_locale = nullable_htmlentities($row['company_locale']);
$config_invoice_footer = nullable_htmlentities($row['config_invoice_footer']);
$config_stripe_enable = intval($row['config_stripe_enable']);
$config_stripe_percentage_fee = floatval($row['config_stripe_percentage_fee']);
$config_stripe_flat_fee = floatval($row['config_stripe_flat_fee']);
$config_stripe_client_pays_fees = intval($row['config_stripe_client_pays_fees']);

//Set Currency Format
$currency_format = numfmt_create($company_locale, NumberFormatter::CURRENCY);

$invoice_tally_total = 0; // Default

//Set Badge color based off of invoice status
$invoice_badge_color = getInvoiceBadgeColor($invoice_status);

//Update status to Viewed only if invoice_status = "Sent"
if ($invoice_status == 'Sent') {
    mysqli_query($mysqli, "UPDATE invoices SET invoice_status = 'Viewed' WHERE invoice_id = $invoice_id");
}

//Mark viewed in history
mysqli_query($mysqli, "INSERT INTO history SET history_status = '$invoice_status', history_description = 'Invoice viewed', history_invoice_id = $invoice_id");

$sql_payments = mysqli_query($mysqli, "SELECT * FROM payments, accounts WHERE payment_account_id = account_id AND payment_invoice_id = $invoice_id ORDER BY payments.payment_id DESC");

//Add up all the payments for the invoice and get the total amount paid to the invoice
$sql_amount_paid = mysqli_query($mysqli, "SELECT SUM(payment_amount) AS amount_paid FROM payments WHERE payment_invoice_id = $invoice_id");
$row = mysqli_fetch_array($sql_amount_paid);
$amount_paid = floatval($row['amount_paid']);

// Calculate the balance owed
$balance = $invoice_amount - $amount_paid;

// Calculate Gateway Fee
if ($config_stripe_client_pays_fees == 1) {
    $balance_before_fees = $balance;
    // See here for passing costs on to client https://support.stripe.com/questions/passing-the-stripe-fee-on-to-customers
    // Calculate the amount to charge the client
    $balance_to_pay = ($balance + $config_stripe_flat_fee) / (1 - $config_stripe_percentage_fee);
    // Calculate the fee amount
    $gateway_fee = round($balance_to_pay - $balance_before_fees, 2);
}

//check to see if overdue
$invoice_color = $invoice_badge_color; // Default
if ($invoice_status !== "Paid" && $invoice_status !== "Draft" && $invoice_status !== "Cancelled") {
    $unixtime_invoice_due = strtotime($invoice_due) + 86400;
    if ($unixtime_invoice_due < time()) {
        $invoice_color = "text-danger";
    }
}

// Invoice individual items
$sql_invoice_items = mysqli_query($mysqli, "SELECT * FROM invoice_items WHERE item_invoice_id = $invoice_id ORDER BY item_order ASC");
?>




<!-- Content wrapper -->
<div class="content-wrapper">

    <!-- Content -->

    <div class="container-xxl flex-grow-1 container-p-y">



        <div class="row invoice-preview">
            <!-- Invoice -->
            <div class="col-xl-9 col-md-8 col-12 mb-md-0 mb-4">
                <div class="card invoice-preview-card">
                    <?php if ($invoice_status == "Paid") {
                        ?>
                        <svg height="200px" width="200px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 485 485" xml:space="preserve">
                            <g>
                                <g>
                                    <path style="stroke:#000000;stroke-miterlimit:10;" d="M138.853,274.822v-64.61h27.573c3.094,0,5.929,0.637,8.508,1.911    c2.578,1.274,4.792,2.943,6.643,5.005c1.85,2.063,3.306,4.399,4.368,7.007c1.061,2.609,1.593,5.248,1.593,7.917    c0,2.853-0.5,5.583-1.501,8.19c-1.001,2.609-2.397,4.945-4.186,7.007c-1.79,2.063-3.958,3.701-6.506,4.914    c-2.548,1.214-5.369,1.82-8.463,1.82h-13.104v20.839H138.853z M153.776,240.97h12.194c1.759,0,3.276-0.758,4.55-2.275    c1.274-1.516,1.911-3.731,1.911-6.643c0-1.516-0.198-2.821-0.592-3.913c-0.395-1.092-0.925-2.002-1.592-2.73    c-0.668-0.728-1.426-1.258-2.275-1.592c-0.851-0.333-1.699-0.5-2.548-0.5h-11.648V240.97z"/>
                                    <path style="stroke:#000000;stroke-miterlimit:10;" d="M213.563,210.212h13.468l23.569,64.61h-15.288l-5.005-14.469h-20.111    l-4.914,14.469h-15.288L213.563,210.212z M227.85,250.07l-7.553-22.841l-7.735,22.841H227.85z"/>
                                    <path style="stroke:#000000;stroke-miterlimit:10;" d="M261.52,274.822v-64.61h14.924v64.61H261.52z"/>
                                    <path style="stroke:#000000;stroke-miterlimit:10;" d="M293.369,274.822v-64.61h24.114c5.338,0,10.011,0.85,14.015,2.548    c4.004,1.699,7.354,4.004,10.055,6.916c2.699,2.912,4.732,6.325,6.098,10.237c1.365,3.913,2.047,8.085,2.047,12.513    c0,4.914-0.759,9.359-2.274,13.332c-1.518,3.974-3.686,7.371-6.507,10.192c-2.821,2.821-6.219,5.005-10.191,6.552    c-3.975,1.547-8.388,2.32-13.241,2.32H293.369z M334.501,242.426c0-2.851-0.38-5.444-1.138-7.78    c-0.76-2.335-1.865-4.353-3.321-6.052c-1.456-1.698-3.246-3.003-5.369-3.913c-2.124-0.91-4.521-1.365-7.189-1.365h-9.19v38.402    h9.19c2.73,0,5.156-0.485,7.28-1.456c2.123-0.97,3.897-2.32,5.323-4.049c1.425-1.729,2.517-3.761,3.276-6.097    C334.121,247.781,334.501,245.217,334.501,242.426z"/>
                                </g>
                                <g>
                                    <path d="M485,371.939H0V113.061h485V371.939z M30,341.939h425V143.061H30V341.939z"/>
                                </g>
                            </g>
                            </svg>
                    <?php } ?>
                    <div class="card-body">
                        <div class="d-flex justify-content-between flex-xl-row flex-md-column flex-sm-row flex-column p-sm-3 p-0">
                            <div class="row">
                                <div class="col-4">
                                    <div class="d-flex svg-illustration mb-3 gap-2">
                                        <img src="data:image/png;base64,<?= $company_logo_base64; ?>" class="w-75 m-4 center-text" alt="logo" />
                                    </div>
                                </div>
                                <div class="col-3">
                                    <h4><?= $company_name; ?></h4>
                                    <p class="mb-1"><?= $company_address; ?></p>
                                    <p class="mb-1"><?= "$company_city $company_state $company_zip"; ?></p>
                                    <p class="mb-1"><?= $company_phone; ?></p>
                                    <p class="mb-0"><?= $company_email; ?></p>
                                </div>
                                <div class="col-5">
                                    <div class="d-flex justify-content-end">
                                        <div class="d-flex flex-column text-end">
                                            <h4>Invoice <?= "$invoice_prefix$invoice_number"; ?></h4>
                                            <div class="mb-2">
                                                <span class="me-1">Date Issued:</span>
                                                <span class="fw-medium">
                                                    <div class="">
                                                        <?= $invoice_date; ?>
                                                    </div>
                                                </span>
                                            </div>
                                            <div>
                                                <span class="me-1">Date Due:</span>
                                                <span class="fw-medium">
                                                    <div class="<?= $invoice_color; ?>"><?= $invoice_due; ?></div>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>
                    <hr class="my-0" />
                    <div class="card-body">
                        <div class="row">
                            <div class="col-7">
                                <h6 class="pb-2 m-1 text-end">Invoice To:</h6>
                            </div>
                            <div class="col text-end">
                                <strong class="truncate-text"><?= $client_name; ?></strong><br>
                                <?= $location_address; ?><br>
                                <?= $location_city . ", " . $location_state . " " . $location_zip; ?><br>
                                <a href="mailto:<?= $contact_email; ?>"><?= $contact_email; ?></a><br>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table border-top m-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Description</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-right">Price</th>
                                    <th class="text-right">Tax</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php

                                $total_tax = 0.00;
                                $sub_total = 0.00 - $invoice_discount;

                                while ($row = mysqli_fetch_array($sql_invoice_items)) {
                                    $item_id = intval($row['item_id']);
                                    $item_name = nullable_htmlentities($row['item_name']);
                                    $item_description = $row['item_description'];
                                    $item_quantity = floatval($row['item_quantity']);
                                    $item_price = floatval($row['item_price']);
                                    $item_tax = floatval($row['item_tax']);
                                    $item_total = floatval($row['item_total']);
                                    $total_tax = $item_tax + $total_tax;
                                    $sub_total = $item_price * $item_quantity + $sub_total;
                                ?>

                                    <tr>
                                        <td><?= $item_name; ?></td>
                                        <td><?= nl2br($item_description); ?></td>
                                        <td class="text-center"><?= $item_quantity; ?></td>
                                        <td class="text-right"><?= numfmt_format_currency($currency_format, $item_price, $invoice_currency_code); ?></td>
                                        <td class="text-right"><?= numfmt_format_currency($currency_format, $item_tax, $invoice_currency_code); ?></td>
                                        <td class="text-right"><?= numfmt_format_currency($currency_format, $item_total, $invoice_currency_code); ?></td>
                                    </tr>

                                <?php } ?>
                                <tr>
                                    <td colspan="3" class="align-top px-4 py-5">
                                        <h4 class=" text-center me-2">
                                            <?php
                                            $due_date = date('Y-m-d', strtotime($invoice_due));
                                            $current_date = date('Y-m-d');
                                            $days_until_due = floor((strtotime($due_date) - strtotime($current_date)) / (60 * 60 * 24));
                                            if ($balance > 0) {
                                                if ($days_until_due > 0) {
                                                    echo "Due in $days_until_due days";
                                                } elseif ($days_until_due == 0) {
                                                    echo "Due today";
                                                } else {
                                                    echo "Past due";
                                                }
                                            } else {
                                                echo "Paid";
                                            }
                                            ?>
                                        </h4>

                                        <?php if ($invoice_note !== "") { ?>
                                            <span>Note:</span>
                                            <?= $invoice_note; ?>
                                        <?php } ?>

                    </div>
                    <div class="text-center"><?= nl2br($config_invoice_footer); ?></div>
                    </td>
                    <td colspan="2" class="text-end px-4 py-5">
                        <p class="mb-2">Subtotal:</p>
                        <p class="mb-2">Discount:</p>
                        <p class="mb-2">Tax:</p>
                        <p class="mb-<?= $amount_paid > 0 ? 4 : 0 ?>">Total:</p>
                        <?php
                        if ($amount_paid > 0) { ?>
                            <p class="mb-2">Amount Paid:</p>
                            <p class="mb-0">Balance Due:</p>
                        <?php } ?>
                    </td>
                    <td class="px-4 py-5">
                        <p class="fw-medium mb-2"><?= numfmt_format_currency($currency_format, $sub_total, $invoice_currency_code); ?></p>
                        <p class="fw-medium mb-2"><?= numfmt_format_currency($currency_format, $invoice_discount, $invoice_currency_code); ?></p>
                        <p class="fw-medium mb-2"><?= numfmt_format_currency($currency_format, $total_tax, $invoice_currency_code); ?></p>
                        <p class="fw-medium mb-<?= $amount_paid > 0 ? 4 : 0 ?>"><?= numfmt_format_currency($currency_format, $invoice_amount, $invoice_currency_code); ?></p>
                        <?php
                        if ($amount_paid > 0) { ?>
                            <p class="fw-medium mb-2"><?= numfmt_format_currency($currency_format, $amount_paid, $invoice_currency_code); ?></p>
                            <p class="fw-medium mb-2"><?= numfmt_format_currency($currency_format, $balance, $invoice_currency_code); ?></p>
                        <?php } ?>
                    </td>
                    </tr>
                    </tbody>
                    </table>
                </div>

            </div>
        </div>
        <!-- /Invoice -->

        <!-- Invoice Actions -->
        <div class="col-xl-3 col-md-4 col-12 invoice-actions">
            <div class="card">
                <div class="card-body d-print-none">
                    <button class="btn btn-label-secondary d-grid w-100 mb-3">
                        Download
                    </button>
                    <a class="btn btn-label-secondary d-grid w-100 mb-3" target="_blank" onclick="window.print();">Print</a>
                    <?php
                    if ($balance > 0) { ?>
                        <a class="btn btn-primary d-grid w-100" href="/portal/guest_pay_invoice_stripe.php?invoice_id=<?= $invoice_id; ?>&url_key=<?= $url_key; ?>">
                            <span class="d-flex align-items-center justify-content-center text-nowrap"><i class="bx bx-dollar bx-xs me-1"></i>
                                Pay Online <?php if ($config_stripe_client_pays_fees == 1) {
                                                echo "(Gateway Fee: " .  numfmt_format_currency($currency_format, $gateway_fee, $invoice_currency_code) . ")";
                                            } ?>
                            </span>
                        </a>
                    <?php } ?>
                </div>
            </div>
        </div>
        <!-- /Invoice Actions -->


    </div>



    <?php
    require_once "portal/guest_footer.php";
    ?>