<?php


require_once "/var/www/portal.twe.tech/includes/inc_all.php";
// Send Recurring Invoices that have a next date that is today or before

//Loop through all recurring that match today's date and is active
$sql_recurring = mysqli_query($mysqli, "SELECT * FROM recurring LEFT JOIN clients ON client_id = recurring_client_id WHERE recurring_next_date <= CURDATE() AND recurring_status = 1");

while ($row = mysqli_fetch_array($sql_recurring)) {

    //if more than one month behind, find the the number of months behind
    $months_behind = date_diff(date_create($row['recurring_next_date']), date_create(date('Y-m-d')))->m;

    $recurring_id = intval($row['recurring_id']);
    $recurring_scope = sanitizeInput($row['recurring_scope']);
    $recurring_frequency = sanitizeInput($row['recurring_frequency']);
    $recurring_status = sanitizeInput($row['recurring_status']);
    $recurring_last_sent = sanitizeInput($row['recurring_last_sent']);
    $recurring_next_date = sanitizeInput($row['recurring_next_date']);
    $recurring_discount_amount = floatval($row['recurring_discount_amount']);
    $recurring_amount = floatval($row['recurring_amount']);
    $recurring_currency_code = sanitizeInput($row['recurring_currency_code']);
    $recurring_note = sanitizeInput($row['recurring_note']); //Escape SQL
    $category_id = intval($row['recurring_category_id']);
    $client_id = intval($row['recurring_client_id']);
    $client_name = sanitizeInput($row['client_name']); //Escape SQL just in case a name is like Safran's etc
    $client_net_terms = intval($row['client_net_terms']);


    //Get the last Invoice Number and add 1 for the new invoice number
    $sql_invoice_number = mysqli_query($mysqli, "SELECT * FROM settings WHERE company_id = 1");
    $row = mysqli_fetch_array($sql_invoice_number);
    $config_invoice_next_number = intval($row['config_invoice_next_number']);

    $new_invoice_number = $config_invoice_next_number;
    $new_config_invoice_next_number = $config_invoice_next_number + 1;
    mysqli_query($mysqli, "UPDATE settings SET config_invoice_next_number = $new_config_invoice_next_number WHERE company_id = 1");

    //Generate a unique URL key for clients to access
    $url_key = randomString(156);

    mysqli_query($mysqli, "INSERT INTO invoices SET invoice_prefix = '$config_invoice_prefix', invoice_number = $new_invoice_number, invoice_scope = '$recurring_scope', invoice_date = CURDATE(), invoice_due = DATE_ADD(CURDATE(), INTERVAL $client_net_terms day), invoice_discount_amount = $recurring_discount_amount, invoice_amount = $recurring_amount, invoice_currency_code = '$recurring_currency_code', invoice_note = '$recurring_note', invoice_category_id = $category_id, invoice_status = 'Sent', invoice_url_key = '$url_key', invoice_client_id = $client_id");

    $new_invoice_id = mysqli_insert_id($mysqli);

    //Copy Items from original recurring invoice to new invoice
    $sql_invoice_items = mysqli_query($mysqli, "SELECT * FROM invoice_items WHERE item_recurring_id = $recurring_id ORDER BY item_id ASC");

    while ($row = mysqli_fetch_array($sql_invoice_items)) {
        $item_id = intval($row['item_id']);
        $item_name = sanitizeInput($row['item_name']); //SQL Escape incase of ,
        $item_description = sanitizeInput($row['item_description']); //SQL Escape incase of ,
        $item_quantity = floatval($row['item_quantity']);
        $item_price = floatval($row['item_price']);
        $item_subtotal = floatval($row['item_subtotal']);
        $item_tax = floatval($row['item_tax']);
        $item_total = floatval($row['item_total']);
        $item_order = intval($row['item_order']);
        $tax_id = intval($row['item_tax_id']);
        $discount = floatval($row['item_discount']);
        $product_id = intval($row['item_product_id']);
        $category_id = intval($row['item_category_id']);

        //Insert Items into New Invoice
        mysqli_query($mysqli, "INSERT INTO invoice_items SET item_name = '$item_name', item_description = '$item_description', item_quantity = $item_quantity, item_price = $item_price, item_subtotal = $item_subtotal, item_tax = $item_tax, item_total = $item_total, item_order = $item_order, item_tax_id = $tax_id, item_invoice_id = $new_invoice_id, item_discount = $discount, item_product_id = $product_id, item_category_id = $category_id");

    }

    mysqli_query($mysqli, "INSERT INTO history SET history_status = 'Sent', history_description = 'Invoice Generated from Recurring!', history_invoice_id = $new_invoice_id");

    //Update recurring dates

    mysqli_query($mysqli, "UPDATE recurring SET recurring_last_sent = CURDATE(), recurring_next_date = DATE_ADD(CURDATE(), INTERVAL 1 $recurring_frequency) WHERE recurring_id = $recurring_id");

    if ($config_recurring_auto_send_invoice == 1) {
        $sql = mysqli_query(
            $mysqli,
            "SELECT * FROM invoices
            LEFT JOIN clients ON invoice_client_id = client_id
            LEFT JOIN contacts ON clients.client_id = contacts.contact_client_id AND contact_primary = 1
            WHERE invoice_id = $new_invoice_id"
        );

        $row = mysqli_fetch_array($sql);
        $invoice_prefix = sanitizeInput($row['invoice_prefix']);
        $invoice_number = intval($row['invoice_number']);
        $invoice_scope = sanitizeInput($row['invoice_scope']);
        $invoice_date = sanitizeInput($row['invoice_date']);
        $invoice_due = sanitizeInput($row['invoice_due']);
        $invoice_amount = floatval($row['invoice_amount']);
        $invoice_url_key = sanitizeInput($row['invoice_url_key']);
        $client_id = intval($row['client_id']);
        $client_name = sanitizeInput($row['client_name']);
        $contact_name = sanitizeInput($row['contact_name']);
        $contact_email = sanitizeInput($row['contact_email']);

        $subject = "$company_name Invoice $invoice_prefix$invoice_number";
        $body = "Hello $contact_name,<br><br>An invoice regarding \"$invoice_scope\" has been generated. Please view the details below.<br><br>Invoice: $invoice_prefix$invoice_number<br>Issue Date: $invoice_date<br>Total: " . numfmt_format_currency($currency_format, $invoice_amount, $recurring_currency_code) . "<br>Due Date: $invoice_due<br><br><br>To view your invoice, please click <a href=\'https://$config_base_url/portal/guest_view_invoice.php?invoice_id=$new_invoice_id&url_key=$invoice_url_key\'>here</a>.<br><br><br>--<br>$company_name - Billing<br>$config_invoice_from_email<br>$company_phone";

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
            mysqli_query($mysqli, "INSERT INTO history SET history_status = 'Sent', history_description = 'Cron Emailed Invoice!', history_invoice_id = $new_invoice_id");
            mysqli_query($mysqli, "UPDATE invoices SET invoice_status = 'Sent', invoice_client_id = $client_id WHERE invoice_id = $new_invoice_id");

        } else {
            mysqli_query($mysqli, "INSERT INTO history SET history_status = 'Draft', history_description = 'Cron Failed to send Invoice!', history_invoice_id = $new_invoice_id");

            mysqli_query($mysqli, "INSERT INTO notifications SET notification_type = 'Mail', notification = 'Failed to send email to $contact_email'");
            mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Mail', log_action = 'Error', log_description = 'Failed to send email to $contact_email regarding $subject. $mail'");
        }

        // Send copies of the invoice to any additional billing contacts
        $sql_billing_contacts = mysqli_query($mysqli, "SELECT contact_name, contact_email FROM contacts
            WHERE contact_billing = 1
            AND contact_email != '$contact_email'
            AND contact_client_id = $client_id"
        );

        while ($billing_contact = mysqli_fetch_array($sql_billing_contacts)) {
            $billing_contact_name = sanitizeInput($billing_contact['contact_name']);
            $billing_contact_email = sanitizeInput($billing_contact['contact_email']);

            $data = [
                [
                    'from' => $config_invoice_from_email,
                    'from_name' => $config_invoice_from_name,
                    'recipient' => $billing_contact_email,
                    'recipient_name' => $billing_contact_name,
                    'subject' => $subject,
                    'body' => $body
                ]
            ];

            addToMailQueue($mysqli, $data);
        }

    } //End if Autosend is on
} //End Recurring Invoices Loop
// Logging
//mysqli_query($mysqli, "INSERT INTO logs SET log_type = 'Cron', log_action = 'Task', log_description = 'Cron created invoices from recurring invoices and sent emails out'");