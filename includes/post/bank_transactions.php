<?php

if (isset($_GET['create_owners_draw'])) {
    // Create owners draw from transaction
    $transaction_id = $_GET['transaction_id'];

    // Create owners draw from transaction
    $sql = "UPDATE bank_transactions SET reconciled = 1 WHERE transaction_id = '$transaction_id'";
    $result = mysqli_query($mysqli, $sql);

    referWithAlert("Owners draw created successfully for transaction", "success");
}