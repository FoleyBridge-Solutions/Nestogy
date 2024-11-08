<?php

use TomorrowIdeas\Plaid\Plaid;

// Set working directory to the directory this cron script lives at.
chdir(dirname(__FILE__));

require_once "/var/www/portal.twe.tech/includes/tenant_db.php";

require_once "/var/www/portal.twe.tech/includes/config/config.php";

require_once "/var/www/portal.twe.tech/includes/functions/functions.php";

require_once "/var/www/portal.twe.tech/src/Model/Accounting.php";
require_once "/var/www/portal.twe.tech/src/Model/Client.php";
require_once "/var/www/portal.twe.tech/src/Database.php";


use Twetech\Nestogy\Database;
use Twetech\Nestogy\Model\Accounting;
$config = require __DIR__ . '/../config.php';
$database = new Database($config['db']);
$pdo = $database->getConnection();
$accounting = new Accounting($pdo);


$sql_companies = mysqli_query($mysqli, "SELECT * FROM companies, settings WHERE companies.company_id = settings.company_id AND companies.company_id = 1");

$row = mysqli_fetch_array($sql_companies);

// Company Details
$company_name = sanitizeInput($row['company_name']);
$company_phone = sanitizeInput(formatPhoneNumber($row['company_phone']));
$company_email = sanitizeInput($row['company_email']);
$company_website = sanitizeInput($row['company_website']);
$company_city = sanitizeInput($row['company_city']);
$company_state = sanitizeInput($row['company_state']);
$company_country = sanitizeInput($row['company_country']);
$company_locale = sanitizeInput($row['company_locale']);
$company_currency = sanitizeInput($row['company_currency']);

// Company Settings
$config_enable_cron = intval($row['config_enable_cron']);
$config_cron_key = $row['config_cron_key'];
$config_invoice_overdue_reminders = $row['config_invoice_overdue_reminders'];
$config_invoice_prefix = sanitizeInput($row['config_invoice_prefix']);
$config_invoice_from_email = sanitizeInput($row['config_invoice_from_email']);
$config_invoice_from_name = sanitizeInput($row['config_invoice_from_name']);
$config_invoice_late_fee_enable = intval($row['config_invoice_late_fee_enable']);
$config_invoice_late_fee_percent = floatval($row['config_invoice_late_fee_percent']);
$config_timezone = sanitizeInput($row['config_timezone']);

// Mail Settings
$config_smtp_host = $row['config_smtp_host'];
$config_smtp_username = $row['config_smtp_username'];
$config_smtp_password = $row['config_smtp_password'];
$config_smtp_port = intval($row['config_smtp_port']);
$config_smtp_encryption = $row['config_smtp_encryption'];
$config_mail_from_email = sanitizeInput($row['config_mail_from_email']);
$config_mail_from_name = sanitizeInput($row['config_mail_from_name']);
$config_recurring_auto_send_invoice = intval($row['config_recurring_auto_send_invoice']);

// Tickets
$config_ticket_prefix = sanitizeInput($row['config_ticket_prefix']);
$config_ticket_from_name = sanitizeInput($row['config_ticket_from_name']);
$config_ticket_from_email = sanitizeInput($row['config_ticket_from_email']);
$config_ticket_client_general_notifications = intval($row['config_ticket_client_general_notifications']);
$config_ticket_autoclose = intval($row['config_ticket_autoclose']);
$config_ticket_autoclose_hours = intval($row['config_ticket_autoclose_hours']);
$config_ticket_new_ticket_notification_email = sanitizeInput($row['config_ticket_new_ticket_notification_email']);

// Get Config for Telemetry
$config_theme = $row['config_theme'];
$config_ticket_email_parse = intval($row['config_ticket_email_parse']);
$config_module_enable_itdoc = intval($row['config_module_enable_itdoc']);
$config_module_enable_ticketing = intval($row['config_module_enable_ticketing']);
$config_module_enable_accounting = intval($row['config_module_enable_accounting']);
$config_telemetry = intval($row['config_telemetry']);

// Alerts
$config_enable_alert_domain_expire = intval($row['config_enable_alert_domain_expire']);
$config_send_invoice_reminders = intval($row['config_send_invoice_reminders']);

// Set Currency Format
$currency_format = numfmt_create($company_locale, NumberFormatter::CURRENCY);

// Set Timezone
date_default_timezone_set($config_timezone);

$argv = $_SERVER['argv'];

// Check cron is enabled
if ($config_enable_cron == 0) {
    exit("Cron: is not enabled -- Quitting..");
}

// Check Cron Key
if ( $argv[1] !== $config_cron_key ) {
    exit("Cron Key invalid  -- Quitting..");
}

/*
 * ###############################################################################################################
 *  STARTUP ACTIONS
 * ###############################################################################################################
 */

//Logging
mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Cron', log_action = 'Started', log_description = 'Cron Started'");



/*
 * ###############################################################################################################
 *  CLEAN UP (OLD) DATA
 * ###############################################################################################################
 */

// Clean-up ticket views table used for collision detection
mysqli_query($mysqli, "TRUNCATE TABLE ticket_views");

// Clean-up shared items that have been used
mysqli_query($mysqli, "DELETE FROM shared_items WHERE item_views = item_view_limit");

// Clean-up shared items that have expired
mysqli_query($mysqli, "DELETE FROM shared_items WHERE item_expire_at < NOW()");

// Invalidate any password reset links
mysqli_query($mysqli, "UPDATE contacts SET contact_password_reset_token = NULL WHERE contact_archived_at IS NULL");

// Clean-up old dismissed notifications
mysqli_query($mysqli, "DELETE FROM notifications WHERE notification_dismissed_at < CURDATE() - INTERVAL 90 DAY");

// Clean-up mail queue
mysqli_query($mysqli, "DELETE FROM email_queue WHERE email_queued_at < CURDATE() - INTERVAL 90 DAY");

// Clean-up old remember me tokens (2 or more days old)
mysqli_query($mysqli, "DELETE FROM remember_tokens WHERE remember_token_created_at < CURDATE() - INTERVAL 2 DAY");

//Logging
//mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Cron', log_action = 'Task', log_description = 'Cron cleaned up old data'");

/*
 * ###############################################################################################################
 *  ACTION DATA
 * ###############################################################################################################
 */


// AUTO CLOSE TICKET - CLOSE
//  Automatically silently closes tickets 22 hrs after the last chase

// Check to make sure auto-close is enabled
$sql_tickets_to_chase = mysqli_query(
    $mysqli,
    "SELECT * FROM tickets 
    WHERE ticket_status = 4
    AND ticket_updated_at < NOW() - INTERVAL $config_ticket_autoclose_hours HOUR"
);

while ($row = mysqli_fetch_array($sql_tickets_to_chase)) {

    $ticket_id = $row['ticket_id'];
    $ticket_prefix = sanitizeInput($row['ticket_prefix']);
    $ticket_number = intval($row['ticket_number']);
    $ticket_subject = sanitizeInput($row['ticket_subject']);
    $ticket_status = sanitizeInput($row['ticket_status']);
    $ticket_assigned_to = sanitizeInput($row['ticket_assigned_to']);
    $client_id = intval($row['ticket_client_id']);

    mysqli_query($mysqli,"UPDATE tickets SET ticket_status = 5, ticket_closed_at = NOW(), ticket_closed_by = $ticket_assigned_to WHERE ticket_id = $ticket_id");

    //Logging
    mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'Ticket', log_action = 'Closed', log_description = '$ticket_prefix$ticket_number auto closed', log_entity_id = $ticket_id");

}


// AUTO CLOSE TICKETS - CHASE
//  Automatically sends a chaser email after approx 48 hrs/2 days
$sql_tickets_to_chase = mysqli_query(
    $mysqli,
    "SELECT contact_name, contact_email, ticket_id, ticket_prefix, ticket_number, ticket_subject, ticket_status, ticket_client_id FROM tickets 
    LEFT JOIN clients ON ticket_client_id = client_id 
    LEFT JOIN contacts ON ticket_contact_id = contact_id
    WHERE ticket_status = 4
    AND ticket_updated_at < NOW() - INTERVAL 48 HOUR"
);

while ($row = mysqli_fetch_array($sql_tickets_to_chase)) {

    $contact_name = sanitizeInput($row['contact_name']);
    $contact_email = sanitizeInput($row['contact_email']);
    $ticket_id = intval($row['ticket_id']);
    $ticket_prefix = sanitizeInput($row['ticket_prefix']);
    $ticket_number = intval($row['ticket_number']);
    $ticket_subject = sanitizeInput($row['ticket_subject']);
    $ticket_status = sanitizeInput($row['ticket_status']);
    $client_id = intval($row['ticket_client_id']);

    $sql_ticket_reply = mysqli_query($mysqli, "SELECT ticket_reply FROM ticket_replies WHERE ticket_reply_type = 'Public' AND ticket_reply_ticket_id = $ticket_id ORDER BY ticket_reply_created_at DESC LIMIT 1");
    $ticket_reply_row = mysqli_fetch_array($sql_ticket_reply);

    // Check if there is a ticket reply
    if ($ticket_reply_row) {
        $ticket_reply = $ticket_reply_row['ticket_reply'];
    } else {
        $ticket_reply = ''; // or provide a default message
    }

    $subject = "Ticket pending closure - [$ticket_prefix$ticket_number] - $ticket_subject";

    $body = "<i style=\'color: #808080\'>##- Please type your reply above this line -##</i><br><br>Hello, $contact_name<br><br>This is an automatic friendly reminder that your ticket regarding \"$ticket_subject\" will be closed, unless you respond.<br><br>--------------------------------<br>$ticket_reply--------------------------------<br><br>If your issue is resolved, you can ignore this email - the ticket will automatically close. If you need further assistance, please respond to this email.  <br><br>Ticket: $ticket_prefix$ticket_number<br>Subject: $ticket_subject<br>Status: $ticket_status<br>Portal: https://$config_base_url/portal/ticket.php?id=$ticket_id<br><br>--<br>$company_name - Support<br>$config_ticket_from_email<br>$company_phone";

    $data = [
        [
            'from' => $config_ticket_from_email,
            'from_name' => $config_ticket_from_name,
            'recipient' => $contact_email,
            'recipient_name' => $contact_name,
            'subject' => $subject,
            'body' => $body
        ]
    ];
    $mail = addToMailQueue($mysqli, $data);

    if ($mail !== true) {
        mysqli_query($mysqli,"INSERT INTO notifications SET notification_type = 'Mail', notification = 'Failed to send email to $contact_email'");
        mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'Mail', log_action = 'Error', log_description = 'Failed to send email to $contact_email regarding $subject. $mail'");
    }

    mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'Ticket Reply', log_action = 'Create', log_description = 'Auto close chaser email sent to $contact_email for ticket $ticket_prefix$ticket_number - $ticket_subject', log_client_id = $client_id");

}


// PAST DUE INVOICE Notifications
//$invoiceAlertArray = [$config_invoice_overdue_reminders];
$invoiceAlertArray = [30,60,90,120,150,180,210,240,270,300,330,360,390,420,450,480,510,540,570,590,620,650,680,710,740];


foreach ($invoiceAlertArray as $day) {

    $sql = mysqli_query(
        $mysqli,
        "SELECT * FROM invoices
        LEFT JOIN clients ON invoice_client_id = client_id
        LEFT JOIN contacts ON clients.client_id = contacts.contact_client_id AND contact_primary = 1
        WHERE invoice_status != 'Draft'
        AND invoice_status != 'Paid'
        AND invoice_status != 'Cancelled'
        AND DATE_ADD(invoice_due, INTERVAL $day DAY) = CURDATE()
        ORDER BY invoice_number DESC"
    );

    while ($row = mysqli_fetch_array($sql)) {
        $invoice_id = intval($row['invoice_id']);
        $invoice_prefix = sanitizeInput($row['invoice_prefix']);
        $invoice_number = intval($row['invoice_number']);
        $invoice_status = sanitizeInput($row['invoice_status']);
        $invoice_date = sanitizeInput($row['invoice_date']);
        $invoice_due = sanitizeInput($row['invoice_due']);
        $invoice_url_key = sanitizeInput($row['invoice_url_key']);
        $invoice_amount = $accounting->getInvoiceTotal($invoice_id);
        $invoice_currency_code = sanitizeInput($row['invoice_currency_code']);
        $client_id = intval($row['client_id']);
        $client_name = sanitizeInput($row['client_name']);
        $contact_name = sanitizeInput($row['contact_name']);
        $contact_email = sanitizeInput($row['contact_email']);
        $invoice_balance = getInvoiceBalance( $invoice_id);

        //Check for overpaymenPt
        $overpayment = $invoice_balance - $invoice_amount;


        // exit loop if overpayment is greater than 0
        if ($overpayment > 0) {
            continue;
        }

        // Late Charges

        if ($config_invoice_late_fee_enable == 1) {

            $todays_date = date('Y-m-d');
            $late_fee_amount = ($invoice_balance * $config_invoice_late_fee_percent) / 100;

            //Insert Items into New Invoice
            mysqli_query($mysqli, "INSERT INTO invoice_items SET item_name = 'Late Fee', item_description = '$config_invoice_late_fee_percent% late fee applied on $todays_date', item_quantity = 1, item_price = $late_fee_amount, item_order = 998, item_invoice_id = $invoice_id");

            mysqli_query($mysqli, "INSERT INTO history SET history_status = 'Sent', history_description = 'Cron applied a late fee of $late_fee_amount', history_invoice_id = $invoice_id");

        }

        $subject = "$company_name Overdue Invoice $invoice_prefix$invoice_number";
        $body = "Hello $contact_name,<br><br>Our records indicate that we have not yet received payment for the invoice $invoice_prefix$invoice_number. We kindly request that you submit your payment as soon as possible. If you have any questions or concerns, please do not hesitate to contact us at $company_email or $company_phone.
            <br>
            <br>
            Please review the invoice details mentioned below at your soonest convenience.<br>
            <br>
            Invoice: $invoice_prefix$invoice_number<br>
            Issue Date: $invoice_date<br>
            Total: " . numfmt_format_currency($currency_format, $invoice_amount, $invoice_currency_code) . "<br>
            Due Date: $invoice_due<br>
            Over Due By: $day Days<br><br><br>
            To view your invoice, please click <a href=\"https://$config_base_url/portal/guest_view_invoice.php?invoice_id=$invoice_id&url_key=$invoice_url_key\">here</a>.<br>
            Or, to view your statement, please login <a href=\"https://$config_base_url/portal/login.php?last_page=guest_view_statement.php\">here</a>.<br>
            <br>
            <br>
            --<br>
            $company_name - Billing<br>
            $config_invoice_from_email<br>
            $company_phone";

        $mail = addToMailQueue($mysqli, [
            [
                'from' => $config_invoice_from_email,
                'from_name' => $config_invoice_from_name,
                'recipient' => $contact_email,
                'recipient_name' => $contact_name,
                'subject' => $subject,
                'body' => $body
            ]
            ]);

        if ($mail === true) {
            mysqli_query($mysqli, "INSERT INTO history SET history_status = 'Sent', history_description = 'Cron Emailed Overdue Invoice', history_invoice_id = $invoice_id");
        } else {
            mysqli_query($mysqli, "INSERT INTO history SET history_status = 'Sent', history_description = 'Cron Failed to send Overdue Invoice', history_invoice_id = $invoice_id");

            mysqli_query($mysqli, "INSERT INTO notifications SET notification_type = 'Mail', notification = 'Failed to send email to $contact_email'");
            mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Mail', log_action = 'Error', log_description = 'Failed to send email to $contact_email regarding $subject. $mail'");
        }

    }

}


// // Collections

// echo "Checking for clients that are past due and sending collections emails.\n";

// // Loop through all clients and check their past due months

// $sql_clients = mysqli_query($mysqli, "SELECT * FROM clients WHERE client_archived_at IS NULL AND client_net_terms > 0");

// while ($row = mysqli_fetch_array($sql_clients)) {
//     $client_id = intval($row['client_id']);
//     $client_name = sanitizeInput($row['client_name']);
//     $client_net_terms = intval($row['client_net_terms']);

//     // Get the past due in months
//     $months_past_due = getClientPastDueBalance($client_id);

//     // Check if the past due is greater than client net terms and if so, send a collections email threatening termination
//     // TODO: add setting to change when a client is considered past due (45 days, 60 days, etc.)
//     if ($months_past_due >= ($client_net_terms/30)) {

//         echo "Client $client_name is $months_past_due months past due. Sending collections email.\n";
//         clientSendDisconnect($client_id);

//         echo "Collections for $client_name finished.\n";

//     } else {
//         echo "Client $client_name is not past due: $months_past_due months past due.\n";
//     }
// }

// // Plaid Bank Transaction Sync using TomorrowIdeas Plaid SDK
// if ($config_plaid_enabled == 1) {

//     // instantiate Plaid SDK
//     require_once '/var/www/portal.twe.tech/vendor/autoload.php';

//     $plaid = new Plaid(
//         \getenv("PLAID_CLIENT_ID"),
//         \getenv("PLAID_CLIENT_SECRET"),
//         \getenv("PLAID_ENVIRONMENT")
//     );

//     $transactions = $plaid->transactions->sync(
//         $plaid_access_token
//     );
//     // add transactions to database
//     foreach ($transactions as $transaction) {
//         $transaction_id = $transaction->transaction_id;
//         $transaction_date = $transaction->date;
//         $transaction_amount = $transaction->amount;
//         $transaction_name = $transaction->name;
//         $transaction_category = $transaction->category;
//         $transaction_type = $transaction->type;
//         $transaction_pending = $transaction->pending;
//         $transaction_account_id = $transaction->account_id;
//         $transaction_account_name = $transaction->account_name;
//         $transaction_account_mask = $transaction->account_mask;
//         $transaction_account_type = $transaction->account_type;
//         $transaction_location = $transaction->location;
//         $transaction_payment_meta = $transaction->payment_meta;
//         $transaction_iso_currency_code = $transaction->iso_currency_code;
//         $transaction_unofficial_currency_code = $transaction->unofficial_currency_code;

//         // Check if transaction already exists
//         $sql = mysqli_query($mysqli, "SELECT * FROM bank_transactions WHERE transaction_id = '$transaction_id'");
//         if (mysqli_num_rows($sql) == 0) {
//             // Insert transaction into database
//             mysqli_query($mysqli, "INSERT INTO bank_transactions SET transaction_id = '$transaction_id', transaction_date = '$transaction_date', transaction_amount = '$transaction_amount', transaction_name = '$transaction_name', transaction_category = '$transaction_category', transaction_type = '$transaction_type', transaction_pending = '$transaction_pending', transaction_account_id = '$transaction_account_id', transaction_account_name = '$transaction_account_name', transaction_account_mask = '$transaction_account_mask', transaction_account_type = '$transaction_account_type', transaction_location = '$transaction_location', transaction_payment_meta = '$transaction_payment_meta', transaction_iso_currency_code = '$transaction_iso_currency_code', transaction_unofficial_currency_code = '$transaction_unofficial_currency_code'");
//         }
//     }

// }
    

/*
 * ###############################################################################################################
 *  FINISH UP
 * ###############################################################################################################
 */

// Logging
mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Cron', log_action = 'Ended', log_description = 'Cron executed successfully'");
