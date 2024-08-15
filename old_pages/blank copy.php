<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "/var/www/portal.twe.tech/includes/inc_all.php";

// this page is to check for invoices with payments larger than the invoice amount
$invoices = mysqli_query($mysqli, "SELECT * FROM pages/blank.php
LEFT JOIN payments ON invoices.invoice_id = payments.payment_invoice_id 
LEFT JOIN clients ON invoices.invoice_client_id = clients.client_id
WHERE payments.payment_amount > invoices.invoice_amount
ORDER BY invoices.invoice_id DESC
");

?>


<h1>Invoices with Payments Larger than Invoice Amount</h1>

<table>
    <tr>
        <th>Invoice ID</th>
        <th>Invoice Amount</th>
        <th>Payment Amount</th>
        <th>Client Name</th>
        <th>Overpayment</th>
        <th>Create Credit</th>
    </tr>

<?php
while ($row = mysqli_fetch_array($invoices)) {
    $invoice_id = $row['invoice_id'];
    $invoice_amount = $row['invoice_amount'];
    $payment_amount = $row['payment_amount'];
    $client_id = $row['client_id'];
    $overpayment = round($payment_amount - $invoice_amount, 2);
?>
    <tr>
        <td><a href="/old_pages/invoice.php?invoice_id=<?php echo $row['invoice_id']; ?>"><?php echo $row['invoice_id']; ?></a></td>
        <td><?php echo $row['invoice_amount']; ?></td>
        <td><?php echo $row['payment_amount']; ?></td>
        <td><?php echo $row['client_name']; ?></td>
        <td><?php echo $overpayment; ?></td>
        <?php if ($invoice_amount != 0) { ?>
        <td>
            <a href="/post.php?create_credit_custom=<?= $overpayment; ?>&payment_id=<?= $row['payment_id']; ?>">Create Credit</a>
        </td>
        <?php } ?>
    </tr>
<?php
}
?>
</table>

<?php
require "/var/www/portal.twe.tech/includes/footer.php";
?>
