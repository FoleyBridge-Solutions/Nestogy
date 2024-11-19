<?php

if (isset($_GET['create_owners_draw'])) {
    // Create owners draw from transaction
    $transaction_id = $_GET['transaction_id'];

    // Create owners draw from transaction
    $sql = "UPDATE bank_transactions SET reconciled = 1 WHERE transaction_id = '$transaction_id'";
    $result = mysqli_query($mysqli, $sql);

    referWithAlert("Owners draw created successfully for transaction", "success");
}

if (isset($_POST['link_payment_to_transaction'])) {
    // Link payment to transaction
    $payment_id = $_POST['payment_id'];
    $transaction_id = $_POST['bank_transaction_id'];

    //Set bank_transactions reconciled to 1
    $sql = "UPDATE bank_transactions SET reconciled = 1 WHERE transaction_id = '$transaction_id'";
    $result = mysqli_query($mysqli, $sql);

    //Set payment plaid_transaction_id to transaction_id
    $sql = "UPDATE payments SET plaid_transaction_id = '$transaction_id' WHERE payment_id = '$payment_id'";
    $result = mysqli_query($mysqli, $sql);

    referWithAlert("Payment linked to transaction successfully, " . $payment_id . " -> " . $transaction_id, "success");
}
