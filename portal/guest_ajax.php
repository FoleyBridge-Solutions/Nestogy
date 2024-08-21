<?php

/*
 * portal/guest_ajax.php
 * Similar to post.php/ajax.php, but for unauthenticated requests using Asynchronous JavaScript
 * Always returns data in JSON format, unless otherwise specified
 */

require_once "/var/www/portal.twe.tech/includes/tenant_db.php";

require_once "/var/www/portal.twe.tech/includes/config/config.php";

require_once "/var/www/portal.twe.tech/includes/functions/functions.php";

require_once "/var/www/portal.twe.tech/includes/rfc6238.php";

require_once "/var/www/portal.twe.tech/src/Model/Accounting.php";
require_once "/var/www/portal.twe.tech/src/Model/Client.php";
require_once "/var/www/portal.twe.tech/src/Database.php";


use Twetech\Nestogy\Database;
use Twetech\Nestogy\Model\Accounting;

$config = require __DIR__ . '/../config.php';
$database = new Database($config['db']);
$pdo = $database->getConnection();

/*
 * Creates & Returns a Stripe Payment Intent for a particular invoice ID
 */

if (isset($_GET['stripe_create_pi'])) {

    // Response header
    header('Content-Type: application/json');

    // Params from POST (portal/guest_pay_invoice_stripe.js)
    $jsonStr = file_get_contents('php://input');
    $jsonObj = json_decode($jsonStr, true);
    $invoice_id = intval($jsonObj['invoice_id']);
    $url_key = sanitizeInput($jsonObj['url_key']);

    $accounting = new Accounting($pdo);
    // Query invoice details
    $invoice = $accounting->getInvoice($invoice_id);

    // Invoice exists - get details for payment
    $invoice_prefix = nullable_htmlentities($invoice['invoice_prefix']);
    $invoice_number = intval($invoice['invoice_number']);
    $invoice_amount = floatval($invoice['invoice_amount']);
    $invoice_currency_code = nullable_htmlentities($invoice['invoice_currency_code']);
    $client_id = intval($invoice['client_id']);
    $client_name = nullable_htmlentities($invoice['client_name']);

    $config_sql = mysqli_query($mysqli, "SELECT * FROM settings WHERE company_id = 1");
    $config_row = mysqli_fetch_array($config_sql);
    $config_stripe_client_pays_fees = intval($config_row['config_stripe_client_pays_fees']);
    $config_stripe_percentage_fee = floatval($config_row['config_stripe_percentage_fee']);
    $config_stripe_flat_fee = floatval($config_row['config_stripe_flat_fee']);

    // Check config to see if client pays fees is enabled
    if ($config_stripe_client_pays_fees == 1) {
        // Calculate the amount to charge the client
        $balance_to_pay = ($balance_to_pay + $config_stripe_flat_fee) / (1 - $config_stripe_percentage_fee);
    }

    $balance_to_pay = $invoice['invoice_balance'];


    if (intval($balance_to_pay) == 0) {
        exit("No balance outstanding");
    }

    // Setup Stripe
    require_once '/var/www/portal.twe.tech/includes/vendor/stripe-php-10.5.0/init.php';


    $row = mysqli_fetch_array(mysqli_query($mysqli, "SELECT config_stripe_enable, config_stripe_secret, config_stripe_account FROM settings WHERE company_id = 1"));
    if ($row['config_stripe_enable'] == 0 || $row['config_stripe_account'] == 0) {
        exit("Stripe not enabled / configured");
    }

    $config_stripe_secret = $row['config_stripe_secret'];
    $pi_description = "ITFlow: $client_name payment of $invoice_currency_code $balance_to_pay for $invoice_prefix$invoice_number";

    // Create a PaymentIntent with amount, currency and client details
    try {
        \Stripe\Stripe::setApiKey($config_stripe_secret);

        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => intval($balance_to_pay * 100), // Times by 100 as Stripe expects values in cents
            'currency' => $invoice_currency_code,
            'description' => $pi_description,
            'metadata' => [
                'itflow_client_id' => $client_id,
                'itflow_client_name' => $client_name,
                'itflow_invoice_number' => $invoice_prefix . $invoice_number,
                'itflow_invoice_id' => $invoice_id,
            ],
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);

        $output = [
            'clientSecret' => $paymentIntent->client_secret,
        ];

        echo json_encode($output);

    } catch (Error $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }

}

if (isset($_GET['get_totp_token'])) {
    $otp = TokenAuth6238::getTokenCode(strtoupper($_GET['totp_secret']));

    echo json_encode($otp);
}
