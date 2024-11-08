<?php
//src/View/reports/tax.php
$currency = 'USD';
$currency_format = numfmt_create('en_US', \NumberFormatter::CURRENCY);
$desired_sales_types = ['Taxable Sales', 'Tax Exempt Sales'];
$year = date('Y');
//Hardcode the sales total for july 2024
$monthly_sales[7]['Taxable Sales'] = 47502.42;
$monthly_sales[7]['Tax Exempt Sales'] = 537.68;
$monthly_sales[8]['Taxable Sales'] = 43601.84;
$monthly_sales[9]['Taxable Sales'] = $monthly_sales[9]['Taxable Sales'] - 1001.84 - $monthly_sales[9]['Tax Exempt Sales'];

?>

<div class="card">
    <div class="card-header py-2">
        <h3 class="card-title mt-2">
            <i class="fas fa-fw fa-balance-scale mr-2"></i>Sales Summary
        </h3>
        <div class="card-tools">
            <button type="button" class="btn btn-label-primary d-print-none" onclick="window.print();">
                <i class="fas fa-fw fa-print mr-2"></i>Print
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="card-datatable table-responsive container-fluid pt-0">
            <table id="responsive" class="responsive table table-striped table-hover table-sm">
                <thead>
                    <tr>
                        <th>Month</th>
                        <?php
                        // Display sales types as columns
                        foreach ($desired_sales_types as $sales_type) {
                            echo "<th class='text-right'>" . htmlspecialchars($sales_type) . "</th>";
                        }
                        // Optionally, add Total column
                        echo "<th class='text-right'><strong>Total Sales</strong></th>";
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_sales_per_type = [];
                    for ($i = 1; $i <= 12; $i++) {
                        // Stop table if month is greater than current month and year is current year
                        if ($i > date('n') && $year == date('Y')) {
                            break;
                        }

                        echo "<tr>";
                        // Display month name
                        echo "<td><div class='font-weight-bold'>" . date('F', mktime(0, 0, 0, $i, 10)) . "</div></td>";
                        
                        $total_sales_per_month = 0;
                        foreach ($desired_sales_types as $sales_type) {
                            // Retrieve sales data from $monthly_sales
                            $sales_amount = isset($monthly_sales[$i][$sales_type]) ? $monthly_sales[$i][$sales_type] : 0;

                            echo "<td class='text-right'>";
                            echo numfmt_format_currency($currency_format, $sales_amount, $currency);
                            echo "</td>";

                            // Accumulate total sales per sales type
                            if (!isset($total_sales_per_type[$sales_type])) {
                                $total_sales_per_type[$sales_type] = 0;
                            }
                            $total_sales_per_type[$sales_type] += $sales_amount;

                            // Accumulate total sales per month
                            $total_sales_per_month += $sales_amount;
                        }
                        // Display total sales for the month
                        echo "<td class='text-right'><strong>" . numfmt_format_currency($currency_format, $total_sales_per_month, $currency) . "</strong></td>";
                        echo "</tr>";
                    }

                    // Display total sales per sales type and grand total
                    echo "<tr>";
                    echo "<td><strong>Total</strong></td>";
                    $grand_total = 0;
                    foreach ($desired_sales_types as $sales_type) {
                        $type_total = isset($total_sales_per_type[$sales_type]) ? $total_sales_per_type[$sales_type] : 0;
                        echo "<td class='text-right'><strong>" . numfmt_format_currency($currency_format, $type_total, $currency) . "</strong></td>";
                        $grand_total += $type_total;
                    }
                    // Display grand total
                    echo "<td class='text-right'><strong>" . numfmt_format_currency($currency_format, $grand_total, $currency) . "</strong></td>";
                    echo "</tr>";
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>