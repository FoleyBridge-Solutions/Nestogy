<?php

require_once "/var/www/nestogy/src/Model/Accounting.php";
require_once "/var/www/nestogy/src/Model/Client.php";
require_once "/var/www/nestogy/src/Database.php";
require_once "/var/www/nestogy/portal/guest_header.php";
require_once "/var/www/nestogy/portal/portal_header.php";


use Twetech\Nestogy\Database;
use Twetech\Nestogy\Model\Accounting;

$config = require '/var/www/nestogy/config/nestogy/config.php';
$database = new Database($config['db']);
$pdo = $database->getConnection();


if (!isset($_GET['invoice_id'], $_GET['url_key'])) {
    echo "<br><h2>Oops, something went wrong! Please raise a ticket if you believe this is an error.</h2>";
    require_once "portal/guest_footer.php";

    exit();
}

$url_key = sanitizeInput($_GET['url_key']);
$invoice_id = intval($_GET['invoice_id']);

$accounting = new Accounting($pdo);
$invoice = $accounting->getInvoice($invoice_id);

$invoice_prefix = $invoice['invoice_prefix'];
$invoice_number = intval($invoice['invoice_number']);
$invoice_status = nullable_htmlentities($invoice['invoice_status']);
$invoice_date = nullable_htmlentities($invoice['invoice_date']);
$invoice_due = nullable_htmlentities($invoice['invoice_due']);
$invoice_discount = floatval($invoice['invoice_discount_amount']);
$invoice_currency_code = nullable_htmlentities($invoice['invoice_currency_code']);
$invoice_note = nullable_htmlentities($invoice['invoice_note']);
$invoice_category_id = intval($invoice['invoice_category_id']);
$invoice_deposit_amount = floatval($invoice['invoice_deposit_amount']);
$client_id = intval($invoice['client_id']);
$client_name = nullable_htmlentities($invoice['client_name']);
$client_name_escaped = sanitizeInput($invoice['client_name']);

$location_address = nullable_htmlentities($invoice['location_address']);
$location_city = nullable_htmlentities($invoice['location_city']);
$location_state = nullable_htmlentities($invoice['location_state']);
$location_zip = nullable_htmlentities($invoice['location_zip']);
$contact_email = nullable_htmlentities($invoice['contact_email']);
$contact_phone = formatPhoneNumber($invoice['contact_phone']);
$contact_extension = nullable_htmlentities($invoice['contact_extension']);
$contact_mobile = formatPhoneNumber($invoice['contact_mobile']);
$client_website = nullable_htmlentities($invoice['client_website']);
$client_currency_code = nullable_htmlentities($invoice['client_currency_code']);
$client_net_terms = intval($invoice['client_net_terms']);
if ($client_net_terms == 0) {
    $client_net_terms = intval($invoice['config_default_net_terms']);
}

$sql = mysqli_query($mysqli, "SELECT  * FROM companies, settings WHERE companies.company_id = settings.company_id AND companies.company_id = 1");
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
    $company_logo_base64 = base64_encode(file_get_contents("/var/www/nestogy/uploads/settings/$company_logo"));
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

$sql_payments = mysqli_query($mysqli, "SELECT  * FROM payments, accounts WHERE payment_account_id = account_id AND payment_invoice_id = $invoice_id ORDER BY payments.payment_id DESC");

//Add up all the payments for the invoice and get the total amount paid to the invoice
$sql_amount_paid = mysqli_query($mysqli, "SELECT  SUM(payment_amount) AS amount_paid FROM payments WHERE payment_invoice_id = $invoice_id");
$row = mysqli_fetch_array($sql_amount_paid);
$amount_paid = floatval($row['amount_paid']);

// Calculate the balance owed
$balance = $invoice['invoice_balance'];

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
                    <svg height="200px" width="200px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg"
                        xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 485 485" xml:space="preserve">
                        <g>
                            <g>
                                <path style="stroke:#FF0000;stroke-miterlimit:10;fill:#FF0000;"
                                    d="M138.853,274.822v-64.61h27.573c3.094,0,5.929,0.637,8.508,1.911    c2.578,1.274,4.792,2.943,6.643,5.005c1.85,2.063,3.306,4.399,4.368,7.007c1.061,2.609,1.593,5.248,1.593,7.917    c0,2.853-0.5,5.583-1.501,8.19c-1.001,2.609-2.397,4.945-4.186,7.007c-1.79,2.063-3.958,3.701-6.506,4.914    c-2.548,1.214-5.369,1.82-8.463,1.82h-13.104v20.839H138.853z M153.776,240.97h12.194c1.759,0,3.276-0.758,4.55-2.275    c1.274-1.516,1.911-3.731,1.911-6.643c0-1.516-0.198-2.821-0.592-3.913c-0.395-1.092-0.925-2.002-1.592-2.73    c-0.668-0.728-1.426-1.258-2.275-1.592c-0.851-0.333-1.699-0.5-2.548-0.5h-11.648V240.97z" />
                                <path style="stroke:#FF0000;stroke-miterlimit:10;"
                                    d="M213.563,210.212h13.468l23.569,64.61h-15.288l-5.005-14.469h-20.111    l-4.914,14.469h-15.288L213.563,210.212z M227.85,250.07l-7.553-22.841l-7.735,22.841H227.85z" />
                                <path style="stroke:#000000;stroke-miterlimit:10;"
                                    d="M261.52,274.822v-64.61h14.924v64.61H261.52z" />
                                <path style="stroke:#000000;stroke-miterlimit:10;"
                                    d="M293.369,274.822v-64.61h24.114c5.338,0,10.011,0.85,14.015,2.548    c4.004,1.699,7.354,4.004,10.055,6.916c2.699,2.912,4.732,6.325,6.098,10.237c1.365,3.913,2.047,8.085,2.047,12.513    c0,4.914-0.759,9.359-2.274,13.332c-1.518,3.974-3.686,7.371-6.507,10.192c-2.821,2.821-6.219,5.005-10.191,6.552    c-3.975,1.547-8.388,2.32-13.241,2.32H293.369z M334.501,242.426c0-2.851-0.38-5.444-1.138-7.78    c-0.76-2.335-1.865-4.353-3.321-6.052c-1.456-1.698-3.246-3.003-5.369-3.913c-2.124-0.91-4.521-1.365-7.189-1.365h-9.19v38.402    h9.19c2.73,0,5.156-0.485,7.28-1.456c2.123-0.97,3.897-2.32,5.323-4.049c1.425-1.729,2.517-3.761,3.276-6.097    C334.121,247.781,334.501,245.217,334.501,242.426z" />
                            </g>
                            <g>
                                <path d="M485,371.939H0V113.061h485V371.939z M30,341.939h425V143.061H30V341.939z" />
                            </g>
                        </g>
                    </svg>
                    <?php } ?>
                    <div class="card-body">
                        <div
                            class="d-flex justify-content-between flex-xl-row flex-md-column flex-sm-row flex-column p-sm-3 p-0">
                            <div class="row">
                                <div class="col-4">
                                    <div class="d-flex svg-illustration mb-3 gap-2">
                                        <img src="data:image/png;base64,<?= $company_logo_base64; ?>"
                                            class="w-75 m-4 center-text" alt="logo" />
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

                                $invoice_sub_total = 0.00;
                                $invoice_tax = 0.00;
                                $invoice_total = 0.00;

                                foreach ($invoice['items'] as $item) {
                                    $item_id = intval($item['item_id']);
                                    $item_name = nullable_htmlentities($item['item_name']);
                                    $item_description = html_entity_decode(html_entity_decode($item['item_description'])); // Decode HTML entities
                                    $item_description = strip_tags($item_description); // Remove HTML tags
                                    $item_quantity = floatval($item['item_quantity']);
                                    $item_price = floatval($item['item_price']);
                                    $item_discount = floatval($item['item_discount']);
                                    $item_tax_id = intval($item['item_tax_id']);
                                    $item_tax_rate = floatval($item['tax_percent']);

                                    $sub_total = ($item_price * $item_quantity) - $item_discount;
                                    $invoice_sub_total += $sub_total;

                                    $item_tax = $sub_total * ($item_tax_rate / 100);
                                    $invoice_tax += $item_tax;

                                    $item_total = $sub_total + $item_tax;
                                    $invoice_total += $item_total;

                                ?>

                                <tr>
                                    <td><?= $item_name; ?></td>
                                    <td><?= $item_description; ?></td>
                                    <td class="text-center"><?= $item_quantity; ?></td>
                                    <td class="text-right">
                                        <?= numfmt_format_currency($currency_format, $item_price, $invoice_currency_code); ?>
                                    </td>
                                    <td class="text-right">
                                        <?= numfmt_format_currency($currency_format, $item_tax, $invoice_currency_code); ?>
                                    </td>
                                    <td class="text-right">
                                        <?= numfmt_format_currency($currency_format, $item_total, $invoice_currency_code); ?>
                                    </td>
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
                                                    echo "<br><span class='text-muted'>(As of " . date('F j, Y') . ")</span>";
                                                } elseif ($days_until_due == 0) {
                                                    echo "Due today";
                                                    echo "<br><span class='text-muted'>(As of " . date('F j, Y') . ")</span>";
                                                } else {
                                                    echo "Past due";
                                                }
                                            } else {
                                                echo "Paid";
                                            }
                                            ?>
                                        </h4>
                                        <p>
                                            <?php if ($invoice_note !== "") { ?>
                                            <span>Note:</span>
                                            <?= html_entity_decode(html_entity_decode($invoice_note)); ?>
                                            <?php } ?>
                                        </p>

                    </div>
                    <div class="text-center">
                        <span>Invoice Terms:</span> <br>
                        <?= nl2br($config_invoice_footer); ?>
                    </div>
                    </td>
                    <td colspan="2" class="text-end px-4 py-5">
                        <p class="mb-2">Subtotal:</p>
                        <p class="mb-2">Discount:</p>
                        <p class="mb-2">Tax:</p>
                        <p class="mb-<?= $amount_paid > 0 || $invoice_deposit_amount > 0 ? 4 : 0 ?>">Total:</p>
                        <?php if ($invoice_deposit_amount > 0 && $amount_paid < $invoice_deposit_amount) { ?>
                            <p class="text-danger mb-<?= $amount_paid > 0 || $invoice_deposit_amount > 0 ? 4 : 0 ?>">Deposit Required:</p>
                            <p class="mb-0">Remainder after deposit:</p>
                        <?php } ?>
                        <?php
                        if ($amount_paid > 0) { ?>
                        <p class="mb-2">Amount Paid:</p>
                        <p class="mb-0">Balance Due:</p>
                        <?php } ?>
                    </td>
                    <td class="px-4 py-5">
                        <p class="fw-medium mb-2">
                            <?= numfmt_format_currency($currency_format, $invoice_sub_total, $invoice_currency_code); ?>
                        </p>
                        <p class="fw-medium mb-2">
                            <?= numfmt_format_currency($currency_format, $invoice_discount, $invoice_currency_code); ?>
                        </p>
                        <p class="fw-medium mb-2">
                            <?= numfmt_format_currency($currency_format, $invoice_tax, $invoice_currency_code); ?></p>
                        <p class="fw-medium mb-<?= $amount_paid > 0 || $invoice_deposit_amount > 0 ? 4 : 0 ?>">
                            <?= numfmt_format_currency($currency_format, $invoice_total, $invoice_currency_code); ?></p>
                        <?php if ($invoice_deposit_amount > 0) { ?>
                            <p class="fw-medium text-danger mb-<?= $invoice_deposit_amount > 0 ? 4 : 0 ?>">
                                <?= numfmt_format_currency($currency_format, $invoice_deposit_amount, $invoice_currency_code); ?></p>
                            <p class="fw-medium mb-0">
                                <?= numfmt_format_currency($currency_format, $balance - $invoice_deposit_amount, $invoice_currency_code); ?></p>
                        <?php } ?>
                        <?php
                        if ($amount_paid > 0) { ?>
                        <p class="fw-medium mb-2">
                            <?= numfmt_format_currency($currency_format, $amount_paid, $invoice_currency_code); ?></p>
                        <p class="fw-medium  mb-2">
                            <?= numfmt_format_currency($currency_format, $balance, $invoice_currency_code); ?></p>
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
                <?php
            $current_date = date('Y-m-d');
            $end_date = '2024-10-31';
            if ($current_date < $end_date) {
            ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Good news!</strong><br> The download functionality is now working again.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php
            }
            ?>

                    <button class="btn btn-label-secondary d-grid w-100 mb-3" id="downloadBtn">
                        Download
                    </button>
                    <a class="btn btn-label-secondary d-grid w-100 mb-3" target="_blank"
                        onclick="window.print();">Print</a>
                        <div class="dropdown">
                            <button class="btn btn-primary d-grid w-100 dropdown-toggle" type="button" id="paymentDropdown"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                Pay Online
                            </button>
                            <ul class="dropdown-menu w-100" aria-labelledby="paymentDropdown">
                                <li>
                                    <a class="dropdown-item"
                                        href="/portal/guest_pay_invoice_stripe.php?invoice_id=<?= $invoice_id; ?>&url_key=<?= $url_key; ?>&payment_method=card">
                                        <span class="d-flex align-items-center justify-content-center text-nowrap">
                                            <i class="bx bx-credit-card bx-xs me-1"></i> Card (2.9% + 0.30¢)
                                        </span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item"
                                        href="/portal/guest_pay_invoice_stripe.php?invoice_id=<?= $invoice_id; ?>&url_key=<?= $url_key; ?>&payment_method=ach">
                                        <span class="d-flex align-items-center justify-content-center text-nowrap">
                                            <i class="bx bxs-bank bx-xs me-1"></i> ACH (0.8%, $5 cap on fee)
                                        </span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item"
                                        href="/portal/guest_pay_invoice_stripe.php?invoice_id=<?= $invoice_id; ?>&url_key=<?= $url_key; ?>&payment_method=installments">
                                        <span class="d-flex align-items-center justify-content-center text-nowrap">
                                            <i class="bx bx-calendar bx-xs me-1"></i> Installments (6% + 0.30¢)
                                        </span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                </div>
            </div>
        </div>
        <!-- /Invoice Actions -->


        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.68/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.68/vfs_fonts.js"></script>
        <script>
        var docDefinition = {
            info: {
                title: <?= json_encode(html_entity_decode($company_name) . " Invoice") ?>,
                author: <?= json_encode(html_entity_decode($company_name)) ?>
            },

            watermark: {text: '<?= $invoice_status; ?>', color: 'lightgrey', opacity: 0.3, bold: true, italics: false},

            content: [
                // Header
                {
                    columns: [
                        <?php if (!empty($company_logo_base64)) { ?> {
                            image: <?= json_encode("data:image;base64,$company_logo_base64") ?>,
                            width: 120
                        },
                        <?php } ?>

                        [{
                            text: 'Invoice',
                            style: 'invoiceTitle',
                            width: '*'
                        }, {
                            text: <?= json_encode(html_entity_decode("$invoice_prefix$invoice_number")) ?>,
                            style: 'invoiceNumber',
                            width: '*'
                        }],
                    ],
                },
                // Billing Headers
                {
                    columns: [{
                            text: <?= json_encode(html_entity_decode($company_name)) ?>,
                            style: 'invoiceBillingTitle'
                        },
                        {
                            text: <?= json_encode(html_entity_decode($client_name)) ?>,
                            style: 'invoiceBillingTitleClient'
                        },
                    ]
                },
                // Billing Address
                {
                    columns: [{
                            text: <?= json_encode(html_entity_decode("$company_address \n $company_city $company_state $company_zip \n $company_phone \n $company_website")) ?>,
                            style: 'invoiceBillingAddress'
                        },
                        {
                            text: <?= json_encode(html_entity_decode("$location_address \n $location_city $location_state $location_zip \n $contact_email \n $contact_phone")) ?>,
                            style: 'invoiceBillingAddressClient'
                        },
                    ]
                },
                //Invoice Dates Table
                {
                    table: {
                        // headers are automatically repeated if the table spans over multiple pages
                        // you can declare how many rows should be treated as headers
                        headerRows: 0,
                        widths: ['*', 80, 80],

                        body: [
                            // Total
                            [{
                                    text: '',
                                    rowSpan: 3
                                },
                                {},
                                {},
                            ],
                            [{},
                                {
                                    text: 'Date',
                                    style: 'invoiceDateTitle'
                                },
                                {
                                    text: <?= json_encode(html_entity_decode($invoice_date)) ?>,
                                    style: 'invoiceDateValue'
                                },
                            ],
                            [{},
                                {
                                    text: 'Expire',
                                    style: 'invoiceDueDateTitle'
                                },
                                {
                                    text: <?= json_encode(html_entity_decode($invoice_expire)) ?>,
                                    style: 'invoiceDueDateValue'
                                },
                            ],
                        ]
                    }, // table
                    layout: 'lightHorizontalLines'
                },
                // Line breaks
                '\n\n',
                // Items
                {
                    table: {
                        // headers are automatically repeated if the table spans over multiple pages
                        // you can declare how many rows should be treated as headers
                        headerRows: 1,
                        widths: ['*', 40, 'auto', 'auto', 80],

                        body: [
                            // Table Header
                            [
                                { text: 'Product', style: ['itemsHeader', 'left'] },
                                { text: 'Qty', style: ['itemsHeader', 'center'] },
                                { text: 'Price', style: ['itemsHeader', 'right'] },
                                { text: 'Tax', style: ['itemsHeader', 'right'] },
                                { text: 'Total', style: ['itemsHeader', 'right'] }
                            ],
                            // Items
                            <?php
                            $total_tax = 0;
                            $sub_total = 0;

                            foreach ($invoice['items'] as $item) {
                                $item_name = nullable_htmlentities($item['item_name']);
                                $item_description = html_entity_decode(html_entity_decode($item['item_description']));
                                $item_description = strip_tags($item_description);
                                $item_quantity = floatval($item['item_quantity']);
                                $item_price = floatval($item['item_price']);
                                $item_discount = floatval($item['item_discount']);
                                $item_tax_id = intval($item['item_tax_id']);
                                $item_tax_rate = floatval($item['tax_percent']);

                                $sub_total = ($item_price * $item_quantity) - $item_discount;
                                $item_tax = $sub_total * ($item_tax_rate / 100);
                                $item_total = $sub_total + $item_tax;

                                // ... existing code ...
                            ?>
                            // Item
                            [
                                [
                                    { text: <?= json_encode($item_name) ?>, style: 'itemTitle' },
                                    { text: <?= json_encode($item_description) ?>, style: 'itemDescription' }
                                ],
                                { text: <?= json_encode($item_quantity) ?>, style: 'itemQty' },
                                { text: <?= json_encode(numfmt_format_currency($currency_format, $item_price, $invoice_currency_code)) ?>, style: 'itemNumber' },
                                { text: <?= json_encode(numfmt_format_currency($currency_format, $item_tax, $invoice_currency_code)) ?>, style: 'itemNumber' },
                                { text: <?= json_encode(numfmt_format_currency($currency_format, $item_total, $invoice_currency_code)) ?>, style: 'itemNumber' }
                            ],
                            <?php
                            }
                            ?>
                            // END Items
                        ]
                    }, // table
                    layout: 'lightHorizontalLines'
                },
                // TOTAL
                {
                    table: {
                        // headers are automatically repeated if the table spans over multiple pages
                        // you can declare how many rows should be treated as headers
                        headerRows: 0,
                        widths: ['*', 'auto', 80],

                        body: [
                            // Total
                            [{
                                    text: 'Notes',
                                    style: 'notesTitle'
                                },
                                {},
                                {}
                            ],
                            [{
                                    rowSpan: '*',
                                    text: <?= json_encode(html_entity_decode($invoice_note)) ?>,
                                    style: 'notesText'
                                },
                                {
                                    text: 'Subtotal',
                                    style: 'itemsFooterSubTitle'
                                },
                                {
                                    text: <?= json_encode(numfmt_format_currency($currency_format, $sub_total, $invoice_currency_code)) ?>,
                                    style: 'itemsFooterSubValue'
                                }
                            ],
                            <?php if ($invoice_discount > 0) { ?>[{}, {
                                text: 'Discount',
                                style: 'itemsFooterSubTitle'
                            }, {
                                text: <?= json_encode(numfmt_format_currency($currency_format, -$invoice_discount, $invoice_currency_code)) ?>,
                                style: 'itemsFooterSubValue'
                            }],
                            <?php } ?>
                            <?php if ($invoice_deposit_amount > 0) { ?>[{}, {
                                text: 'Deposit',
                                style: 'itemsFooterSubTitle'
                            }, {
                                text: <?= json_encode(numfmt_format_currency($currency_format, $invoice_deposit_amount, $invoice_currency_code)) ?>,
                                style: 'itemsFooterSubValue'
                            }],<?php } ?>
                            <?php if ($total_tax > 0) { ?>[{}, {
                                text: 'Tax',
                                style: 'itemsFooterSubTitle'
                            }, {
                                text: <?= json_encode(numfmt_format_currency($currency_format, $total_tax, $invoice_currency_code)) ?>,
                                style: 'itemsFooterSubValue'
                            }],
                            <?php } ?>[{}, {
                                text: 'Total',
                                style: 'itemsFooterTotalTitle'
                            }, {
                                text: <?= json_encode(numfmt_format_currency($currency_format, $invoice_total, $invoice_currency_code)) ?>,
                                style: 'itemsFooterTotalValue'
                            }],
                        ]
                    }, // table
                    layout: 'lightHorizontalLines'
                },
                // TERMS / FOOTER
                {
                    text: <?= json_encode("$config_invoice_footer"); ?>,
                    style: 'documentFooterCenter'
                }
            ], //End Content,
            styles: {
                // Document Footer
                documentFooterCenter: {
                    fontSize: 9,
                    margin: [10, 50, 10, 10],
                    alignment: 'center'
                },
                // Invoice Title
                invoiceTitle: {
                    fontSize: 18,
                    bold: true,
                    alignment: 'right',
                    margin: [0, 0, 0, 3]
                },
                // Invoice Number
                invoiceNumber: {
                    fontSize: 14,
                    alignment: 'right'
                },
                // Billing Headers
                invoiceBillingTitle: {
                    fontSize: 14,
                    bold: true,
                    alignment: 'left',
                    margin: [0, 20, 0, 5]
                },
                invoiceBillingTitleClient: {
                    fontSize: 14,
                    bold: true,
                    alignment: 'right',
                    margin: [0, 20, 0, 5]
                },
                // Billing Details
                invoiceBillingAddress: {
                    fontSize: 10,
                    lineHeight: 1.2
                },
                invoiceBillingAddressClient: {
                    fontSize: 10,
                    lineHeight: 1.2,
                    alignment: 'right',
                    margin: [0, 0, 0, 30]
                },
                // Invoice Dates
                invoiceDateTitle: {
                    fontSize: 10,
                    alignment: 'left',
                    margin: [0, 5, 0, 5]
                },
                invoiceDateValue: {
                    fontSize: 10,
                    alignment: 'right',
                    margin: [0, 5, 0, 5]
                },
                // Invoice Due Dates
                invoiceDueDateTitle: {
                    fontSize: 10,
                    bold: true,
                    alignment: 'left',
                    margin: [0, 5, 0, 5]
                },
                invoiceDueDateValue: {
                    fontSize: 10,
                    bold: true,
                    alignment: 'right',
                    margin: [0, 5, 0, 5]
                },
                // Items Header
                itemsHeader: {
                    fontSize: 10,
                    margin: [0, 5, 0, 5],
                    bold: true,
                    alignment: 'right'
                },
                // Item Title
                itemTitle: {
                    fontSize: 10,
                    bold: true,
                    margin: [0, 5, 0, 3]
                },
                itemDescription: {
                    italics: true,
                    fontSize: 9,
                    lineHeight: 1.1,
                    margin: [0, 3, 0, 5]
                },
                itemQty: {
                    fontSize: 10,
                    margin: [0, 5, 0, 5],
                    alignment: 'center'
                },
                itemNumber: {
                    fontSize: 10,
                    margin: [0, 5, 0, 5],
                    alignment: 'right'
                },
                itemTotal: {
                    fontSize: 10,
                    margin: [0, 5, 0, 5],
                    bold: true,
                    alignment: 'right'
                },
                // Items Footer (Subtotal, Total, Tax, etc)
                itemsFooterSubTitle: {
                    fontSize: 10,
                    margin: [0, 5, 0, 5],
                    alignment: 'right'
                },
                itemsFooterSubValue: {
                    fontSize: 10,
                    margin: [0, 5, 0, 5],
                    bold: false,
                    alignment: 'right'
                },
                itemsFooterTotalTitle: {
                    fontSize: 10,
                    margin: [0, 5, 0, 5],
                    bold: true,
                    alignment: 'right'
                },
                itemsFooterTotalValue: {
                    fontSize: 10,
                    margin: [0, 5, 0, 5],
                    bold: true,
                    alignment: 'right'
                },
                notesTitle: {
                    fontSize: 10,
                    bold: true,
                    margin: [0, 5, 0, 5]
                },
                notesText: {
                    fontSize: 9,
                    margin: [0, 5, 50, 5]
                },
                left: {
                    alignment: 'left'
                },
                center: {
                    alignment: 'center'
                },
            },
            defaultStyle: {
                columnGap: 20,
            }
        }

        // Add this new code
        document.getElementById('downloadBtn').addEventListener('click', function() {
            pdfMake.createPdf(docDefinition).download('invoice_<?= $invoice_prefix . $invoice_number ?>.pdf');
        });
        </script>

    </div>



    <?php
    require_once "guest_footer.php";
    ?>

