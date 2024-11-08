<?php

global $mysqli, $name, $ip, $user_agent, $user_id;


if (isset($_POST["add_subscription"])) {
    $subscription_product_id = $_POST['subscription_product_id'];
    $subscription_client_id = $_POST['subscription_client_id'];
    $subscription_product_quantity = $_POST['subscription_product_quantity'];

    $mysqli->query("INSERT INTO subscriptions (subscription_product_id, subscription_client_id, subscription_product_quantity) VALUES ($subscription_product_id, $subscription_client_id, $subscription_product_quantity)");

    referWithAlert("Subscription added successfully", "success");
}
if (isset($_POST["update_subscription"])) {

    $subscription_id = $_POST['subscription_id'];
    $subscription_product_quantity = $_POST['subscription_product_quantity'];
    $subscription_product_term = $_POST['subscription_product_term'];

    $mysqli->query("UPDATE subscriptions SET subscription_product_quantity = $subscription_product_quantity, subscription_term = '$subscription_product_term' WHERE subscription_id = $subscription_id");

    referWithAlert("Subscription updated successfully", "success");
}
if (isset($_POST["bill_subscription"])) {
    $term = $_POST['term'];
    $client_id = $_POST['client_id'];
    $last_billed = $_POST['last_billed'];
    //Get the last invoice number in the invoice table
    $last_invoice = $mysqli->query("SELECT MAX(invoice_number) FROM invoices");
    $last_invoice = $last_invoice->fetch_assoc();
    $last_invoice = $last_invoice['MAX(invoice_number)'];
    $invoice_number = (int)$last_invoice + 1;

    //Create a blank invoice for the subscription
    $mysqli->query("INSERT INTO invoices 
        (
            invoice_client_id, 
            invoice_prefix, 
            invoice_number,
            invoice_currency_code, 
            invoice_date,
            invoice_due,
            invoice_scope,
            invoice_status,
            invoice_category_id
        ) VALUES (
            $client_id, 
            'INV', 
            $invoice_number, 
            'USD', 
            NOW(), 
            DATE_ADD(NOW(), INTERVAL 1 MONTH), 
            'Subscription',
            'Sent',
            '1'
        )"
    );
    //Get the invoice id
    $invoice_id = $mysqli->insert_id;

    //Find all the subscriptions for the client, where the term and the last_billed are the same as the term and the last_billed in the ajax call
    $sql = "SELECT * FROM subscriptions WHERE subscription_client_id = $client_id AND subscription_term = '$term'";
    if ($last_billed != 'Never') {
        $sql .= " AND subscription_last_billed = '$last_billed'";
    }
    $subscriptions = $mysqli->query($sql);
    $subscriptions = $subscriptions->fetch_all(MYSQLI_ASSOC);
    $subscription_count = count($subscriptions);

    if ($subscription_count == 0) {
        echo json_encode(array("success" => false, "message" => "No subscriptions found for the client. SQL: " . $sql));
        exit;
    }

    $product_order = 1;
    //Loop through the subscriptions
    foreach ($subscriptions as $subscription) {
        $product_id = $subscription['subscription_product_id'];
        $product_quantity = $subscription['subscription_product_quantity'];

        $product = $mysqli->query("SELECT * FROM products WHERE product_id = $product_id");
        $product = $product->fetch_assoc();
        $product_name = nullable_htmlentities($product['product_name']);
        $product_price = $product['product_price'];
        $product_tax_id = $product['product_tax_id'];
        $product_description = nullable_htmlentities($product['product_description']);
        $product_category_id = $product['product_category_id'];



        $tax = $mysqli->query("SELECT * FROM taxes WHERE tax_id = $product_tax_id");
        $tax = $tax->fetch_assoc();
        $tax_percent = $tax['tax_percent'];

        $subscription_total = ($product_price * $product_quantity) * (1 + $tax_percent / 100);

        //Add the subscription products to the invoice
        $mysqli->query("INSERT INTO invoice_items 
            (
                item_name,
                item_description,
                item_quantity,
                item_price,
                item_discount,
                item_order,
                item_tax_id,
                item_invoice_id,
                item_category_id,
                item_product_id
            ) VALUES (
                '$product_name',
                '$product_description',
                '$product_quantity',
                '$product_price',
                '0', #Discount not supported for subscriptions yet
                '$product_order',
                '$product_tax_id',
                '$invoice_id',
                '$product_category_id',
                '$product_id'
            )"
        );
        $product_order++;
    }

    //Update the last billed date for the subscription
    $sql = "UPDATE subscriptions SET subscription_last_billed = NOW() WHERE subscription_client_id = $client_id AND subscription_term = '$term'";
    if ($last_billed != 'Never') {
        $sql .= " AND subscription_last_billed = '$last_billed'";
    }
    $mysqli->query($sql);

    //Email the invoice to the client
    emailInvoice($invoice_id);

    echo json_encode(array("success" => true, "message" => "Subscription billed successfully"));
}
if (isset($_GET["delete_subscription"])) {
    $subscription_id = $_GET['delete_subscription'];
    $mysqli->query("DELETE FROM subscriptions WHERE subscription_id = $subscription_id");
    referWithAlert("Subscription deleted successfully", "success");
}