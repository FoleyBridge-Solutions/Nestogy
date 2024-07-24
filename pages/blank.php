<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "/var/www/portal.twe.tech/includes/inc_all.php";

// this page is to check for recurring invoices that their amounts are different than the invoice amount based on adding the items in it
$recurring = mysqli_query($mysqli, "SELECT * FROM recurring");
?>

<table>
    <thead>
        <tr>
            <th>Recurring ID</th>
            <th>Recurring Amount</th>
            <th>Recurring Items Amount</th>
            <th>Difference</th>
        </tr>
    </thead>
    <tbody>

<?php


foreach ($recurring as $row) {
    $recurring_amount = getRecurringInvoiceAmount($row['recurring_id']);
    // check if the recurring amount is different than the recurring items amount by more than 1 cent

    if (abs($row['recurring_amount'] - $recurring_amount) > 0.01) {
        echo "<tr>";
        echo "<td><a href='/pages/recurring_invoice.php?recurring_id=" . $row['recurring_id'] . "'>" . $row['recurring_id'] . "</a></td>";
        echo "<td>" . $row['recurring_amount'] . "</td>";
        echo "<td>" . $recurring_amount . "</td>";
        echo "<td>" . ($row['recurring_amount'] - $recurring_amount);
        // find an product that has the same amount as the difference, 

        echo "</tr>";
    }
}
?>



</tbody>
</table>

<?php
require "/var/www/portal.twe.tech/includes/footer.php";
?>
