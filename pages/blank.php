<?php

require "/var/www/portal.twe.tech/includes/inc_all.php";

// this page is to check for invoices where the amount row does not equal the sum of the line items using the getInvoiceAmount function
// we will just start with a table of invoices that have a discrepancy so we can investigate further as we noticed a few invoices that were not adding up correctly

function getInvoices(){
    global $mysqli;
    //fetch invoice_id and invoice_amount from invoices table
    $sql = "SELECT invoice_id, invoice_amount FROM invoices";
    $result = mysqli_query($mysqli, $sql);

    $invoices = array();

    while($row = mysqli_fetch_assoc($result)){
        $invoices[] = $row;
    }

    return  $invoices;
}


//start by getting all invoices
$invoices = getInvoices();

?>

<table>
    <tr>
        <th>Invoice ID</th>
        <th>Invoice Amount</th>
        <th>Line Item Amount</th>
        <th>Discrepancy</th>
    </tr>


<?php

//loop through invoices and check if the amount row equals the sum of the line items
foreach($invoices as $invoice){
    $invoice_id = $invoice['invoice_id'];
    $invoice_amount = $invoice['invoice_amount'];
    $line_item_amount = getInvoiceAmount($invoice_id);

    $discrepancy = false;

    // check if they are off by more than 1 cent
    if(abs($invoice_amount - $line_item_amount) > 0.01){
        $discrepancy = true;
    }

    if($discrepancy) {
        echo "<tr>";
        echo "<td><a href='/pages/invoice.php?invoice_id=".$invoice_id."'>".$invoice_id."</a></td>";
        echo "<td>".$invoice_amount."</td>";
        echo "<td>".$line_item_amount."</td>";
        echo "<td>".($invoice_amount - $line_item_amount)."</td>";
        echo "</tr>";
    }
}

?>

</table>



<?php
require "/var/www/portal.twe.tech/includes/footer.php";
