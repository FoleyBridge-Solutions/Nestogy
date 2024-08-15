<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "/var/www/portal.twe.tech/includes/inc_all.php";
//this page is for testing purposes only

//there was an error with how invoice amounts were being calculated,
// i need to find any invoices that have a partial payment and display in a table

if (isset($_GET['create_credit'])) {
    $invoice_id = $_GET['invoice_id'];
    $amount = $_GET['amount'];
    
    createCreditForInvoice($invoice_id, $amount);
}

$invoices = mysqli_query($mysqli, "SELECT * FROM invoices ORDER BY invoice_client_id ASC, invoice_date ASC");
?>

<table class="table table-striped">
    <thead class="thead-dark text-center">
        <tr>
            <th>Client</th>
            <th>Invoice</th>
            <th>Amount</th>
            <th>Amount Paid</th>
            <th>Amount Remaining</th>
            <th>Invoice Date</th>
            <th>Most Recent Payment Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>

<?php


foreach ($invoices as $row) {
    $invoice_id = $row['invoice_id'];
    $amount = $row['invoice_amount'];
    $amount_paid = getInvoicePayments($invoice_id);
    $amount_remaining = $amount - $amount_paid;
    $invoice_date = $row['invoice_date'];
    
    //get client name
    $client_id = $row['invoice_client_id'];
    $client_name = getClientName($client_id);

    $most_recent_payment_date = getMostRecentPaymentDate($invoice_id);

    if ($most_recent_payment_date == null) {
        continue;
    }

    if ($amount_remaining < .01) {
        continue;
    }

    // If more than 45 days between most recent payment and invoice date, make payement date red
    $diff = strtotime($most_recent_payment_date) - strtotime($invoice_date);

    //make dates human readable
    $invoice_date = date("m/d/Y", strtotime($invoice_date));
    $most_recent_payment_date = date("m/d/Y", strtotime($most_recent_payment_date));

    $diff = $diff / (60 * 60 * 24);
    if ($diff > 45) {
        $most_recent_payment_date = "<span style='color:red'>" . $most_recent_payment_date . "</span>";
    }

    // if invoice is older than 3 months, make invoice date red
    $diff = strtotime(date("Y-m-d")) - strtotime($invoice_date);
    $diff = $diff / (60 * 60 * 24);
    if ($diff > 90) {
        $invoice_date = "<span style='color:red'>" . $invoice_date . "</span>";
    }


    if ($amount_remaining > 0) {
        echo "<tr>";
        echo "<td>" . $client_name . "</td>";
        echo "<td><a href='/old_pages/invoice.php?invoice_id=" . $invoice_id . "'>" . $invoice_id . "</a></td>";
        echo "<td>" . $amount . "</td>";
        echo "<td>" . $amount_paid . "</td>";
        echo "<td>" . $amount_remaining . "</td>";
        echo "<td>" . $invoice_date . "</td>";
        echo "<td>" . $most_recent_payment_date . "</td>";
        echo "<td><a href='/old_pages/blank.php?create_credit=true&invoice_id=" . $invoice_id . "&amount=" . $amount_remaining . "'>Create Credit</a></td>";
        echo "</tr>";
    } else {
        //echo "No partial payments found";
    }
}
?>



</tbody>
</table>

<?php
require "/var/www/portal.twe.tech/includes/footer.php";
?>
