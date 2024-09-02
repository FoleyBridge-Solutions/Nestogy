<?php

require_once "/var/www/portal.twe.tech/src/Model/Accounting.php";
require_once "/var/www/portal.twe.tech/src/Model/Client.php";
require_once "/var/www/portal.twe.tech/src/Database.php";
require_once "/var/www/portal.twe.tech/portal/guest_header.php";


use Twetech\Nestogy\Database;
use Twetech\Nestogy\Model\Accounting;

$config = require __DIR__ . '/../config.php';
$database = new Database($config['db']);
$pdo = $database->getConnection();


// Define wording
DEFINE("WORDING_PAYMENT_FAILED", "<br><h2>There was an error verifying your payment. Please contact us for more information.</h2>");

// Setup Stripe
$stripe_vars = mysqli_fetch_array(mysqli_query($mysqli, "SELECT config_stripe_enable, config_stripe_publishable, config_stripe_secret, config_stripe_account, config_stripe_expense_vendor, config_stripe_expense_category, config_stripe_percentage_fee, config_stripe_flat_fee, config_stripe_client_pays_fees FROM settings WHERE company_id = 1"));
$config_stripe_enable = intval($stripe_vars['config_stripe_enable']);
$config_stripe_publishable = nullable_htmlentities($stripe_vars['config_stripe_publishable']);
$config_stripe_secret = nullable_htmlentities($stripe_vars['config_stripe_secret']);
$config_stripe_account = intval($stripe_vars['config_stripe_account']);
$config_stripe_expense_vendor = intval($stripe_vars['config_stripe_expense_vendor']);
$config_stripe_expense_category = intval($stripe_vars['config_stripe_expense_category']);
$config_stripe_percentage_fee = floatval($stripe_vars['config_stripe_percentage_fee']);
$config_stripe_flat_fee = floatval($stripe_vars['config_stripe_flat_fee']);
$config_stripe_client_pays_fees = intval($stripe_vars['config_stripe_client_pays_fees']);

if (isset($_GET['payment_method'])) {
    $payment_method = $_GET['payment_method'];
} else {
    $payment_method = 'card';
}

// Show payment form
//  Users are directed to this page with the invoice_id and url_key params to make a payment
if (isset($_GET['invoice_id'], $_GET['url_key']) && !isset($_GET['payment_intent'])) {

    $invoice_url_key = sanitizeInput($_GET['url_key']);
    $invoice_id = intval($_GET['invoice_id']);

    $accounting = new Accounting($pdo);
    // Query invoice details
    $invoice = $accounting->getInvoice($invoice_id);

    $invoice_prefix = nullable_htmlentities($invoice['invoice_prefix']);
    $invoice_number = intval($invoice['invoice_number']);
    $invoice_status = nullable_htmlentities($invoice['invoice_status']);
    $invoice_date = nullable_htmlentities($invoice['invoice_date']);
    $invoice_discount = floatval($invoice['invoice_discount_amount']);
    $invoice_amount = floatval($invoice['invoice_amount']);
    $invoice_currency_code = nullable_htmlentities($invoice['invoice_currency_code']);
    $client_id = intval($invoice['invoice_client_id']);
    
    $balance = $invoice['invoice_balance'];
    $amount_paid = $invoice_amount - $balance;

    if ($payment_method == "ach") {
        $fees = [
            "rate" => 0.008,
            "flat" => 0,
            "cap" => 5
        ];
        if ($balance * $fees['rate'] + $fees['flat'] > $fees['cap'] ) {
            $balance_to_pay = $fees['cap'] + $balance;
        } else {
            $balance_to_pay = $balance * $fees['rate'] + $fees['flat'] + $balance;
        }
    } elseif ($payment_method == "card") {
        $fees = [
            "rate" => 0.029,
            "flat" => 0.30
        ];
        $balance_to_pay = ($balance + $fees['flat']) / (1 - $fees['rate']);
    } elseif ($payment_method == "installments") {
        $fees = [
            "rate" => 0.06,
            "flat" => 0.30
        ];
        $balance_to_pay = ($balance + $fees['flat']) / (1 - $fees['rate']);
    }

    $gateway_fee = round($balance_to_pay - $balance, 2);
    $balance_to_pay = round($balance_to_pay, 2);

    // Get invoice items
    $sql_invoice_items = mysqli_query($mysqli, "SELECT * FROM invoice_items WHERE item_invoice_id = $invoice_id ORDER BY item_id ASC");

    // Set Currency Formatting
    $currency_format = numfmt_create("en_US", NumberFormatter::CURRENCY);

    $sql_taxes = mysqli_query($mysqli, "SELECT * FROM taxes");
    $taxes = [];
    while ($row = mysqli_fetch_array($sql_taxes)) {
        $taxes[] = $row;
    }
    ?>

    <!-- Include Stripe JS (must be Stripe-hosted, not local) -->
    <script src="https://js.stripe.com/v3/"></script>

    <!-- jQuery -->
    <script src="/includes/plugins/jquery/jquery.min.js"></script>

    <div class="row pt-5">

        <!-- Show invoice details -->
        <div class="col-sm">
            <h3>Payment for Invoice: <?= $invoice_prefix . $invoice_number ?></h3>
            <br>
                <div class="card-datatable table-responsive container-fluid  pt-0">               
                    <table id=responsive class="responsive table">
                    <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Total</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php

                    $item_total = 0;

                    foreach ($invoice['items'] as $item) {
                        $item_name = nullable_htmlentities($item['item_name']);
                        $item_quantity = floatval($item['item_quantity']);
                        $item_price = floatval($item['item_price']);
                        $item_discount = floatval($item['item_discount']);
                        $item_tax_id = intval($item['item_tax_id']);
                        $item_tax_rate = floatval($item['tax_percent']);

                        $sub_total = ($item_price * $item_quantity) - $item_discount;
                        
                        $item_tax = $sub_total * ($item_tax_rate / 100);
                        
                        $item_total = $sub_total + $item_tax;
                        ?>

                        <tr>
                            <td><?= $item_name; ?></td>
                            <td class="text-center"><?= $item_quantity; ?></td>
                            <td class="text-right"><?= numfmt_format_currency($currency_format, $item_total, $invoice_currency_code); ?></td>
                        </tr>

                    <?php }
                    if ($config_stripe_client_pays_fees == 1) { ?>
                    
                        <tr>
                            <td>Gateway Fees</td>
                            <td class="text-center">-</td>
                            <td class="text-right"><?= numfmt_format_currency($currency_format, $gateway_fee, $invoice_currency_code); ?></td>
                        </tr>
                    <?php } ?>



                    </tbody>
                </table>
            </div>
            <br>
            <i><?php if ($invoice_discount > 0){ echo "Discount: " . numfmt_format_currency($currency_format, $invoice_discount, $invoice_currency_code); } ?>
            </i>
            <br>
            <i><?php if (intval($amount_paid) > 0) { ?> Already paid: <?= numfmt_format_currency($currency_format, $amount_paid, $invoice_currency_code); } ?></i>
        </div>
        <!-- End invoice details-->

        <!-- Show Stripe payment form -->
        <div class="col-sm offset-sm-1">
            <h1>Payment Total:</h1>
            <form id="payment-form">
                <h1><?= numfmt_format_currency($currency_format, $balance_to_pay, $invoice_currency_code); ?></h1>
                <input type="hidden" id="stripe_publishable_key" value="<?= $config_stripe_publishable ?>">
                <input type="hidden" id="invoice_id" value="<?= $invoice_id ?>">
                <input type="hidden" id="url_key" value="<?= $invoice_url_key ?>">
                <br>
                <div id="link-authentication-element">
                    <!--Stripe.js injects the Link Authentication Element-->
                </div>
                <div id="payment-element">
                    <!--Stripe.js injects the Payment Element-->
                </div>
                <br>
                <button type="submit" id="submit" class="btn btn-label-primary btn-lg btn-block text-bold" hidden="hidden">
                    <div class="spinner hidden" id="spinner"></div>
                    <span id="button-text"><i class="fas fa-check mr-2"></i>Pay Invoice</span>
                </button>
                <div id="payment-message" class="hidden"></div>
            </form>
        </div>
        <!-- End Stripe payment form -->

    </div>

    <!-- Include local JS that powers stripe -->
    <script>
        const stripe = Stripe(document.getElementById("stripe_publishable_key").value);

        let elements;

        initialize();
        checkStatus();

        document
        .querySelector("#payment-form")
        .addEventListener("submit", handleSubmit);

        async function initialize() {
        const { clientSecret } = await fetch("guest_ajax.php?stripe_create_pi=true&payment_method=<?php echo $payment_method; ?>", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
            invoice_id: <?php echo $invoice_id; ?>,
            url_key: "<?php echo $invoice_url_key; ?>"
            }),
        }).then((r) => r.json());

        elements = stripe.elements({ clientSecret });

        const linkAuthenticationElement = elements.create("linkAuthentication");
        linkAuthenticationElement.mount("#link-authentication-element");

        const paymentElementOptions = {
            layout: "tabs",
        };

        const paymentElement = elements.create("payment", paymentElementOptions);
        paymentElement.mount("#payment-element");

        // Unhide the submit button once everything has loaded
        document.getElementById("submit").hidden = false;
        }

        async function handleSubmit(e) {
        e.preventDefault();
        setLoading(true);

        const { error } = await stripe.confirmPayment({
            elements,
            confirmParams: {
            return_url: window.location.href,
            receipt_email: emailAddress,
            },
        });

        // This point will only be reached if there is an immediate error when
        // confirming the payment. Otherwise, your customer will be redirected to
        // your `return_url`. For some payment methods like iDEAL, your customer will
        // be redirected to an intermediate site first to authorize the payment, then
        // redirected to the `return_url`.
        if (error.type === "card_error" || error.type === "validation_error") {
            showMessage(error.message);
        } else {
            showMessage("An unexpected error occurred.");
        }

        setLoading(false);
        }

        // Fetches the payment intent status after payment submission
        async function checkStatus() {
        const clientSecret = new URLSearchParams(window.location.search).get(
            "payment_intent_client_secret"
        );

        if (!clientSecret) {
            return;
        }

        const { paymentIntent } = await stripe.retrievePaymentIntent(clientSecret);

        switch (paymentIntent.status) {
            case "succeeded":
            showMessage("Payment succeeded!");
            break;
            case "processing":
            showMessage("Your payment is processing.");
            break;
            case "requires_payment_method":
            showMessage("Your payment was not successful, please try again.");
            break;
            default:
            showMessage("Something went wrong.");
            break;
        }
        }

        // ------- UI helpers -------

        function showMessage(messageText) {
        const messageContainer = document.querySelector("#payment-message");

        messageContainer.classList.remove("hidden");
        messageContainer.textContent = messageText;

        setTimeout(function () {
            messageContainer.classList.add("hidden");
            messageText.textContent = "";
        }, 4000);
        }

        // Show a spinner on payment submission
        function setLoading(isLoading) {
        if (isLoading) {
            // Disable the button and show a spinner
            document.querySelector("#submit").disabled = true;
            document.querySelector("#spinner").classList.remove("hidden");
            document.querySelector("#button-text").classList.add("hidden");
        } else {
            document.querySelector("#submit").disabled = false;
            document.querySelector("#spinner").classList.add("hidden");
            document.querySelector("#button-text").classList.remove("hidden");
        }
        }

    </script>

    <?php

// Process payment & redirect user back to invoice
//  (Stripe will redirect back to this page upon payment success with the payment_intent and payment_intent_client_secret params set
} elseif (isset($_GET['payment_intent'], $_GET['payment_intent_client_secret'])) {

    // Params from GET
    $pi_id = sanitizeInput($_GET['payment_intent']);
    $pi_cs = $_GET['payment_intent_client_secret'];

    // Initialize stripe
    require_once '/var/www/portal.twe.tech/includes/vendor/stripe-php-10.5.0/init.php';

    \Stripe\Stripe::setApiKey($config_stripe_secret);

    // Check details of the PI
    $pi_obj = \Stripe\PaymentIntent::retrieve($pi_id);

    if ($pi_obj->client_secret !== $pi_cs) {
        exit(WORDING_PAYMENT_FAILED);
    } elseif ($pi_obj->status !== "succeeded") {
        exit(WORDING_PAYMENT_FAILED);
    } elseif ($pi_obj->amount !== $pi_obj->amount_received) {
        // The invoice wasn't paid in full
        // this should be flagged for manual review as would indicate something weird happening
        exit(WORDING_PAYMENT_FAILED);
    }

    // Get details from PI
    $pi_date = date('Y-m-d', $pi_obj->created);
    $pi_invoice_id = intval($pi_obj->metadata->itflow_invoice_id);
    $pi_client_id = intval($pi_obj->metadata->itflow_client_id);
    $pi_amount_paid = floatval(($pi_obj->amount_received / 100));
    $pi_currency = strtoupper(sanitizeInput($pi_obj->currency));
    $pi_livemode = $pi_obj->livemode;

    // Get/Check invoice (& client/primary contact)
    $invoice_sql = mysqli_query(
        $mysqli,
        "SELECT * FROM invoices
        LEFT JOIN clients ON invoice_client_id = client_id
        LEFT JOIN contacts ON clients.client_id = contacts.contact_client_id AND contact_primary = 1
        WHERE invoice_id = $pi_invoice_id
        AND invoice_status != 'Draft'
        AND invoice_status != 'Paid'
        AND invoice_status != 'Cancelled'
        LIMIT 1"
    );
    if (!$invoice_sql || mysqli_num_rows($invoice_sql) !== 1) {
        exit(WORDING_PAYMENT_FAILED);
    }

    // Invoice exists - get details
    $row = mysqli_fetch_array($invoice_sql);
    $invoice_id = intval($row['invoice_id']);
    $invoice_prefix = sanitizeInput($row['invoice_prefix']);
    $invoice_number = intval($row['invoice_number']);
    $invoice_amount = floatval($row['invoice_amount']);
    $invoice_currency_code = sanitizeInput($row['invoice_currency_code']);
    $invoice_url_key = sanitizeInput($row['invoice_url_key']);
    $client_id = intval($row['client_id']);
    $client_name = sanitizeInput($row['client_name']);
    $contact_name = sanitizeInput($row['contact_name']);
    $contact_email = sanitizeInput($row['contact_email']);
    
    $sql_company = mysqli_query($mysqli, "SELECT * FROM companies WHERE company_id = 1");
    $row = mysqli_fetch_array($sql_company);

    $company_name = sanitizeInput($row['company_name']);
    $company_phone = sanitizeInput(formatPhoneNumber($row['company_phone']));
    $company_locale = sanitizeInput($row['company_locale']);

    // Set Currency Formatting
    $currency_format = numfmt_create($company_locale, NumberFormatter::CURRENCY);

    // Add up all the payments for the invoice and get the total amount paid to the invoice already (if any)
    $sql_amount_paid_previously = mysqli_query($mysqli, "SELECT SUM(payment_amount) AS amount_paid FROM payments WHERE payment_invoice_id = $invoice_id");
    $row = mysqli_fetch_array($sql_amount_paid_previously);
    $amount_paid_previously = $row['amount_paid'];
    $balance_to_pay = $invoice_amount - $amount_paid_previously;

    // Check config to see if client pays fees is enabled or if should expense it
    if ($config_stripe_client_pays_fees == 1) {
        $balance_before_fees = $balance_to_pay;
        // See here for passing costs on to client https://support.stripe.com/questions/passing-the-stripe-fee-on-to-customers
        // Calculate the amount to charge the client
        $balance_to_pay = ($balance_to_pay + $config_stripe_flat_fee) / (1 - $config_stripe_percentage_fee);
        // Calculate the fee amount
        $gateway_fee = round($balance_to_pay - $balance_before_fees, 2);
        
        // Add as line item to client Invoice
        mysqli_query($mysqli,"INSERT INTO invoice_items SET item_name = 'Gateway Fees', item_description = 'Payment Gateway Fees', item_quantity = 1, item_price = $gateway_fee, item_subtotal = $gateway_fee, item_total = $gateway_fee, item_order = 999, item_invoice_id = $invoice_id");
        // Update the Amount on the invoice to include the gateway fee
        $new_invoice_amount = $invoice_amount + $gateway_fee;
        mysqli_query($mysqli,"UPDATE invoices SET invoice_amount = $new_invoice_amount WHERE invoice_id = $invoice_id");
    }

    // Check to see if Expense Fields are configured and client pays fee is off then create expense
    if ($config_stripe_client_pays_fees == 0 && $config_stripe_expense_vendor > 0 && $config_stripe_expense_category > 0) {
        // Calculate gateway expense fee
        $gateway_fee = round($balance_to_pay * $config_stripe_percentage_fee + $config_stripe_flat_fee, 2);

        // Add Expense
        mysqli_query($mysqli,"INSERT INTO expenses SET expense_date = '$pi_date', expense_amount = $gateway_fee, expense_currency_code = '$invoice_currency_code', expense_account_id = $config_stripe_account, expense_vendor_id = $config_stripe_expense_vendor, expense_client_id = $client_id, expense_category_id = $config_stripe_expense_category, expense_description = 'Stripe Transaction for Invoice $invoice_prefix$invoice_number In the Amount of $balance_to_pay', expense_reference = 'Stripe - $pi_id'");
    }

    // Round balance to pay to 2 decimal places
    $balance_to_pay = round($balance_to_pay, 2);

    // Sanity check that the amount paid is exactly the invoice outstanding balance
    if (intval($balance_to_pay) !== intval($pi_amount_paid)) {
        exit("Something went wrong confirming this payment. Please get in touch.");
    }

    // Apply payment

    // Update Invoice Status
    mysqli_query($mysqli, "UPDATE invoices SET invoice_status = 'Paid' WHERE invoice_id = $invoice_id");

    // Add Payment to History
    mysqli_query($mysqli, "INSERT INTO payments SET payment_date = '$pi_date', payment_amount = $pi_amount_paid, payment_currency_code = '$pi_currency', payment_account_id = $config_stripe_account, payment_method = 'Stripe', payment_reference = 'Stripe - $pi_id', payment_invoice_id = $invoice_id");
    mysqli_query($mysqli, "INSERT INTO history SET history_status = 'Paid', history_description = 'Payment added - $ip - $os - $browser', history_invoice_id = $invoice_id");

    // Add Gateway fees to history if applicable
    if ($config_stripe_client_pays_fees == 1) {
        mysqli_query($mysqli, "INSERT INTO history SET history_status = 'Paid', history_description = 'Gateway fees of $gateway_fee has been billed', history_invoice_id = $invoice_id");
    }


    // Logging
    $extended_log_desc = '';
    if (!$pi_livemode) {
        $extended_log_desc = '(DEV MODE)';
    }
    if ($config_stripe_client_pays_fees == 1) {
        $extended_log_desc .= ' (Client Pays Fees [' . numfmt_format_currency($currency_format, $gateway_fee, $invoice_currency_code) . ']])';
    }
    mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Payment', log_action = 'Create', log_description = 'Stripe payment of $pi_currency $pi_amount_paid against invoice $invoice_prefix$invoice_number - $pi_id $extended_log_desc', log_ip = '$ip', log_user_agent = '$user_agent', log_client_id = $pi_client_id");

    

    // Send email receipt
    $sql_settings = mysqli_query($mysqli, "SELECT * FROM settings WHERE company_id = 1");
    $row = mysqli_fetch_array($sql_settings);

    $config_smtp_host = $row['config_smtp_host'];
    $config_smtp_port = intval($row['config_smtp_port']);
    $config_smtp_encryption = $row['config_smtp_encryption'];
    $config_smtp_username = $row['config_smtp_username'];
    $config_smtp_password = $row['config_smtp_password'];
    $config_invoice_from_name = sanitizeInput($row['config_invoice_from_name']);
    $config_invoice_from_email = sanitizeInput($row['config_invoice_from_email']);
    
    $config_base_url = sanitizeInput($config_base_url);

    if (!empty($config_smtp_host)) {
        $subject = "Payment Received - Invoice $invoice_prefix$invoice_number";
        $body = "Hello $contact_name,<br><br>We have received your payment in the amount of " . $pi_currency . $pi_amount_paid . " for invoice <a href=\'https://$config_base_url/portal/guest_view_invoice.php?invoice_id=$invoice_id&url_key=$invoice_url_key\'>$invoice_prefix$invoice_number</a>. Please keep this email as a receipt for your records.<br><br>Amount: " . numfmt_format_currency($currency_format, $pi_amount_paid, $invoice_currency_code) . "<br>Balance: " . numfmt_format_currency($currency_format, '0', $invoice_currency_code) . "<br><br>Thank you for your business!<br><br><br>~<br>$company_name - Billing<br>$config_invoice_from_email<br>$company_phone";

            $data = [
                [
                    'from' => $config_invoice_from_email,
                    'from_name' => $config_invoice_from_name,
                    'recipient' => $contact_email,
                    'recipient_name' => $contact_name,
                    'subject' => $subject,
                    'body' => $body,
                ]
            ];
        $mail = addToMailQueue($mysqli, $data);

        // Email Logging
        if ($mail === true) {
            mysqli_query($mysqli, "INSERT INTO history SET history_status = 'Sent', history_description = 'Emailed Receipt!', history_invoice_id = $invoice_id");
        } else {
            mysqli_query($mysqli, "INSERT INTO history SET history_status = 'Sent', history_description = 'Email Receipt Failed!', history_invoice_id = $invoice_id");

            mysqli_query($mysqli, "INSERT INTO notifications SET notification_type = 'Mail', notification = 'Failed to send email to $contact_email'");
            mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Mail', log_action = 'Error', log_description = 'Failed to send email to $contact_email regarding $subject. $mail'");
        }
    }

    // Redirect user to invoice
    header('Location: //' . $config_base_url . '/portal/guest_view_invoice.php?invoice_id=' . $pi_invoice_id . '&url_key=' . $invoice_url_key);

} else {
    echo "<br><h2>Oops, something went wrong! Please raise a ticket if you believe this is an error.</h2>";
}


require_once '/var/www/portal.twe.tech/portal/guest_footer.php';
