<?php
    $monthly_fractional_payment = $tax_report['monthly_fractional_payment'];
    $monthly_tax_owed = $tax_report['monthly_tax_owed'];
    $currency = 'USD';

?>
<div class="card">
    <div class="card-header py-2">
        <h3 class="card-title mt-2"><i class="fas fa-fw fa-balance-scale mr-2"></i>Collected Tax Summary</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-label-primary d-print-none" onclick="window.print();"><i class="fas fa-fw fa-print mr-2"></i>Print</button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="card-datatable table-responsive container-fluid pt-0">
            <table id=responsive class="responsive table table-striped table-hover table-sm">
                <thead>
                    <tr>
                        <th>Tax</th>
                        <?php
                        for ($i = 1; $i <= 12; $i++) {
                            echo "<th class='text-right'>" . date('M', mktime(0, 0, 0, $i, 10)) . "</th>";
                            //Stop table if month is greater than current month and year is current year
                            if ($i == date('n') && $year == date('Y')) {
                                break;
                            }
                        }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach (array_keys($monthly_fractional_payment[1]) as $tax_name) {
                        echo "<tr>";
                        echo "<td><div class='font-weight-bold'>Net " . $tax_name . "</div><div class='small'> Collected Tax</div></td>";
                        for ($i = 1; $i <= 12; $i++) {
                            $row_payments = isset($monthly_fractional_payment[$i][$tax_name]) ? $monthly_fractional_payment[$i][$tax_name] : 0;
                            $row_tax_due = isset($monthly_tax_owed[$i][$tax_name]) ? $monthly_tax_owed[$i][$tax_name] : 0;
                            ?>
                            <td class=''>
                                <div class="">
                                    <a href="breakdown.php?tax_name=<?= urlencode($tax_name); ?>&month=<?= $i; ?>&year=<?= $year; ?>&type=payments">
                                        <?= numfmt_format_currency($currency_format, $row_payments, $currency); ?>
                                    </a>
                                </div>
                                <div class="small">
                                    <a href="breakdown.php?tax_name=<?= urlencode($tax_name); ?>&month=<?= $i; ?>&year=<?= $year; ?>&type=taxes">
                                        <?= numfmt_format_currency($currency_format, $row_tax_due, $currency); ?>
                                    </a>
                                </div>
                            </td>
                            <?php
                            // Add to total payments and tax due for the year
                            $total_payments[$i] += $row_payments;
                            $total_tax_due[$i] += $row_tax_due;
                            // Stop table if month is greater than current month and year is current year
                            if ($i == date('n') && $year == date('Y')) {
                                break;
                            }
                        }
                        echo "</tr>";
                    }

                    // Display total monthly payments and tax row
                    echo "<tr><td><strong>Gross Total Payments Recieved</strong></td>";
                    for ($i = 1; $i <= 12; $i++) {
                        echo "<td class='text-right'>" . numfmt_format_currency($currency_format, $total_payments[$i] + $total_tax_due[$i], $currency) . "</td>";
                        // Stop table if month is greater than current month and year is current year
                        if ($i == date('n') && $year == date('Y')) {
                            break;
                        }
                    }

                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>