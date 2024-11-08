<?php


require_once "/var/www/portal.twe.tech/includes/inc_portal.php";



    $client_id = $client_id;


    $sql_client_details = "
    SELECT
        client_name,
        client_type,
        client_website,
        client_net_terms
    FROM
        clients
    WHERE
        client_id = $client_id";

    $result_client_details = mysqli_query($mysqli, $sql_client_details);
    $row_client_details = mysqli_fetch_assoc($result_client_details);

    $client_name = nullable_htmlentities($row_client_details['client_name']);
    $client_type = nullable_htmlentities($row_client_details['client_type']);
    $client_website = nullable_htmlentities($row_client_details['client_website']);
    $client_net_terms = intval($row_client_details['client_net_terms']);
    $client_balance = getClientBalance($client_id);

    $max_rows = intval($_GET['max_rows'] ?? 0);

    $sql_client_unpaid_invoices = "
    SELECT
        invoice_id,
        invoice_number,
        invoice_prefix,
        invoice_date,
        invoice_due
    FROM
        invoices
    WHERE
        invoice_client_id = $client_id
        AND invoice_status NOT LIKE 'Draft'
        AND invoice_status NOT LIKE 'Cancelled'
        AND invoice_status NOT LIKE 'Paid'";

    $result_client_unpaid_invoices = mysqli_query($mysqli, $sql_client_unpaid_invoices);

    $currency_code = getSettingValue("company_currency");

    $transaction_invoices = [];

    if (isset($_GET['max_rows'])) {
        $outstanding_wording = strval($_GET['max_rows']) . " Most Recent";
    } else {
        $outstanding_wording = "Outstanding";
    }

    $aging_balances = [
     '0-30' => getClientAgingBalance($client_id, 0, 30),
     '31-60' => getClientAgingBalance($client_id, 31, 60),
     '61-90' => getClientAgingBalance($client_id, 61, 90),
     '90+' => getClientAgingBalance($client_id, 91, 9999)
    ];


    $past_due_balance = $aging_balances['90+'] + $aging_balances['61-90'] + $aging_balances['31-60'];
    $balance_due = $client_balance;
    ?>

    <div class="card">
    <div class="card-header py-2">
        <h3 class="card-title mt-2"><i class="fas fa-fw fa-balance-scale mr-2"></i>Statement for <?= $client_name ?></h3>
        <div class="card-tools">
            <button type="button" class="btn btn-label-primary d-print-none" onclick="window.print();"><i class="fas fa-fw fa-print mr-2"></i>Print</button>
            <!-- <?php if ($client_balance > 0) { ?>
                <button type="button" class="btn btn-label-primary d-print-none" data-bs-toggle="modal" data-bs-target="#pay_balance_modal"><i class="fas fa-fw fa-money-bill-wave mr-2"></i>Pay Balance</button>
            <?php } ?> -->
        </div>
    </div>
    <div class="card-body">
        <div>
            <div class="row">
                <div class="col-md-6 col-12">
                    <div class="me-4">
                        <h4>Client Details</h4>
                        <table class="table table-sm" id="client_details_table">
                            <tr>
                                <td>Client Name:</td>
                                <td><?= $client_name; ?></td>
                            </tr>
                            <tr>
                                <td>Net Terms:</td>
                                <td><?= $client_net_terms; ?> days</td>
                            </tr>
                            <tr>
                                <td>Current Balance:</td>
                                <td><?= numfmt_format_currency($currency_format, $client_balance, $currency_code); ?></td>
                            </tr>
                            <tr>
                                <td>Past Due:</td>
                                <td class="<?php if ($past_due_balance > 0) { echo 'text-danger'; } ?>">
                                    <?= numfmt_format_currency($currency_format, $past_due_balance, $currency_code); ?>
                                </td>
                            </tr>
                        </table>                        
                    </div>

                </div>
                <div class="col-md-6 col-12">
                    <!-- 0-30. 30-60, 60-90, 90+ -->
                    <div class="me-4">
                        <h4>Aging Summary</h4>
                        <table class="table table-sm" id="aging_summary_table">
                            <tr>
                                <td>0-30 Days:</td>
                                <td>
                                    <?= numfmt_format_currency($currency_format, $aging_balances['0-30'], $currency_code); ?></td>
                            </tr>
                            <tr>
                                <td>31-60 Days:</td>
                                <td class="<?php if ($aging_balances['31-60'] > 0) { echo 'text-danger'; } ?>">
                                    <?= numfmt_format_currency($currency_format, $aging_balances['31-60'], $currency_code); ?></td>
                            </tr>
                            <tr>
                                <td>61-90 Days:</td>
                                <td class="<?php if ($aging_balances['61-90'] > 0) { echo 'text-danger'; } ?>">
                                    <?= numfmt_format_currency($currency_format, $aging_balances['61-90'], $currency_code); ?></td>
                            </tr>
                            <tr>
                                <td>90+ Days:</td>
                                <td class="<?php if ($aging_balances['90+'] > 0) { echo 'text-danger'; } ?>">
                                    <?= numfmt_format_currency($currency_format, $aging_balances['90+'], $currency_code); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="coTmd-12">
                    <div class="m-3">
                        <h4>
                            Unpaid Invoices and Associated Payments
                        </h4>
                        <div class="table-responsive">
                            <table class="table border-top table-sm table-hover" id="client_statement_table">
                                <thead>
                                    <tr>
                                        <th>Transaction</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Balance</th>
                                    </tr>
                                </thead>
                                <?php
                                $sql_client_transactions = "SELECT * FROM invoices 
                                    LEFT JOIN payments ON invoices.invoice_id = payments.payment_invoice_id
                                    WHERE invoices.invoice_client_id = $client_id
                                    AND invoices.invoice_status NOT LIKE 'Draft'
                                    AND invoices.invoice_status NOT LIKE 'Cancelled'
                                    ORDER BY invoices.invoice_date DESC";
                                $result_client_transactions = mysqli_query($mysqli, $sql_client_transactions);
                                $default_order = 0;
                                $invoice_count = 0;
                                while ($row = mysqli_fetch_assoc($result_client_transactions)) {
                                    $invoice_count ++;
                                    if (in_array($row['invoice_id'], $transaction_invoices)) {
                                        continue;
                                    } else {
                                        array_push($transaction_invoices, $row['invoice_id']);
                                    }

                                    $transaction_date = nullable_htmlentities($row['invoice_date']);
                                    $transaction_type = "Invoice:" . "<a href='/guest_view_invoice.php?invoice_id=" . $row['invoice_id'] . "&url_key=" . $row['invoice_url_key'] . "'> " . $row['invoice_prefix'] . $row['invoice_number'] . "</a>";
                                    $transaction_amount = getInvoiceAmount($row['invoice_id']);
                                    $transaction_balance = getInvoiceBalance($row['invoice_id']);
                                    $transaction_due_date = sanitizeInput($row['invoice_due']);
                                    $invoice_id = intval($row['invoice_id']);

                                    if ($invoice_count > $max_rows && $transaction_balance <= 0) {
                                        continue;
                                    }

                                    if ($transaction_balance <= 0) {
                                        $transaction_balance = 0;
                                    }
                                    if ($transaction_balance < 0.02) {
                                        continue;
                                    }


                                    // IF due date has passed, add a warning class
                                    if ((strtotime($transaction_due_date) < strtotime(date("Y-m-d"))) && ($transaction_balance > 0)) {
                                        $transaction_balance = "<span class='text-danger'>" . numfmt_format_currency($currency_format, $transaction_balance, $currency_code) . " <small>(Past Due)</small></span>";
                                    } else {
                                        $transaction_balance = numfmt_format_currency($currency_format, $transaction_balance, $currency_code);
                                    }
                                    $default_order ++;
                                    $payments = getPaymentsForInvoice($row['invoice_id']) ?? [];

                                    if (count($payments) == 0) {
                                        ?>
                                        <tr>
                                            <td>
                                                <h5>
                                                    <?php if (strtotime($transaction_due_date) < strtotime(date("Y-m-d"))) { ?>
                                                        <i class="bx bx-error text-danger"></i>
                                                        <i class="bx bx-receipt"></i>
                                                    <?php } else { ?>
                                                        <i class="bx bx-receipt"></i>
                                                    <?php } ?>
                                                    <?= $transaction_type; ?>
                                                </h5>
                                            </td>
                                            <td><?= $transaction_date; ?></td>
                                            <td><?= numfmt_format_currency($currency_format, $transaction_amount, $currency_code); ?></td>
                                            <td rowspan="2" class="align-bottom"><?= $transaction_balance ?></td>
                                        </tr>
                                        <?php
                                    } else {
                                        ?>
                                        <tr>
                                            <td>
                                                <h5>
                                                    <i class="bx bx-receipt"></i>
                                                    <?= $transaction_type; ?>                                                    
                                                </h5></td>
                                            <td><?= $transaction_date; ?></td>
                                            <td><?= numfmt_format_currency($currency_format, $transaction_amount, $currency_code); ?></td>
                                            <td rowspan="<?= count($payments) + 1 ?>" class="align-bottom"><?= $transaction_balance ?></td>
                                        </tr>
                                        <?php
                                    }
                                    foreach ($payments as $payment) {

                                        $transaction_date = nullable_htmlentities($payment['payment_date']);
                                        $transaction_type = $payment['payment_method'];
                                        $transaction_amount = floatval($payment['payment_amount']) *-1;
                                        if ($payment['payment_method'] != "Stripe") {
                                            $transaction_type = $transaction_type. " " . $payment['payment_reference'];
                                        } else {
                                            $stripe_ref_last_4 = "...".substr($payment['payment_reference'], -4);
                                            $transaction_type = "Online Payment";
                                        }
                                        $default_order ++;

                                        // if transaction date is from a previous year, then save the year to $transaction_year
                                        if (date("Y", strtotime($transaction_date)) < date("Y")) {
                                            $transaction_year = date("Y", strtotime($transaction_date));
                                        }
                                        ?>
                                        <tr class="small">
                                            <td class="text-end">
                                                <i class="bx bx-credit-card"></i>
                                                Payment: 
                                                <a href="/old_pages/report/payments_report.php?client_id=<?= $client_id; ?>&payment_reference=<?= $payment['payment_reference']; ?>&year=<?= date("Y", strtotime($transaction_date)); ?>">
                                                    <?= $transaction_type; ?>
                                                </a>
                                            </td>
                                            <td><?= $transaction_date; ?></td>
                                            <td><?= numfmt_format_currency($currency_format, $transaction_amount, $currency_code); ?></td>
                                        </tr>
                                        <?php
                                        
                                    }
                                    //if no payments, then add a row saying no payments found for invoice
                                    if (count($payments) == 0) {
                                        ?>
                                        <tr>
                                            <td class="text-muted small text-end">No payments found for invoice <?php echo $row['invoice_number']; ?></td>
                                            <td colspan="3"></td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                    <tr class="dark-line">
                                        <td colspan="4"></td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class='modal' id='pay_balance_modal'>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-fw fa-money-bill-wave mr-2"></i>Pay Balance</h5>
            </div>
            <div class="modal-body bg-white">
                <form action="guest_pay_balance_stripe.php" method="post">
                    <input type="hidden" name="client_id" value="<?= $client_id; ?>">
                    <input type="hidden" name="balance" value="<?= $client_balance; ?>">
                    <input type="hidden" name="past_due_balance" value="<?= $past_due_balance; ?>">
                    <input type="radio" name="balance_due" value="balance_due" checked> Balance Due (<?= numfmt_format_currency($currency_format, $balance_due, $currency_code); ?>)<br>
                    <?php if (round($past_due_balance, 2) != round($client_balance, 2)) { ?>
                        <input type="radio" name="balance_due" value="past_due_balance" <?php if ($past_due_balance > 0) { echo 'checked'; } ?>> Past Due Balance (<?= numfmt_format_currency($currency_format, $past_due_balance, $currency_code); ?>)<br>
                    <?php } ?>

                    <button type="submit" class="btn btn-primary">Continue to Payment</button>
                </form>
            </div>
        </div>
    </div>
</div>
<style>
    .dark-line {
        background-color: #999; /* Lighter shade of black */
        height: 2px;
    }
</style>

<?php require_once '/var/www/portal.twe.tech/includes/footer.php';

?>
