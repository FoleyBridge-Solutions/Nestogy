<pre>
<?php
$config_currency_code = 'USD'; #TODO: Get from config
?>
</pre>
<div class="card">
    <div class="card-header">
        <h5>Collections Report</h5>
    </div>
    <div class="card-body p-0">
        <div>

                    <h5>Total AR: <span class="text-danger"><?= numfmt_format_currency($currency_format, $collections_report['total_balance'], $config_currency_code); ?></span></h5>

            <div class="card-datatable table-responsive container-fluid  pt-0">   
                <table class="datatables-basic responsive table table-bordered table-striped table-hover table-sm">
                    <thead class="text-dark">
                        <tr>
                            <th>Client Name</th>
                            <th>Billing Contact Phone</th>
                            <th>Balance</th>
                            <th>Monthly Recurring Amount</th>
                            <th>Months Past Due</th>
                            <th>Past Due Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($collections_report['clients'] as $client) {
                            $client_id = $client['client_id'];
                            $client_name = $client['client_name'];
                            $contact_phone = $client['contact_phone'];
                            $balance = $client['balance'];
                            $total_balance += $balance;
                            $monthly_recurring_amount = $client['monthly_recurring_amount'];
                            $past_due_amount = $client['past_due_amount'];

                            $months_past_due = $monthly_recurring_amount > 0 ? ($past_due_amount / $monthly_recurring_amount) : 0;

                            // if number of months past due ends with .0 precision, add .1 to it
                            if (strpos(number_format($months_past_due, 1), ".0") !== false || $balance > $monthly_recurring_amount) {
                                $months_past_due += .1;
                            }
                        ?>
                        <tr>
                            <td data-order="<?= $client_name; ?>">
                                <a href="/public/?page=statement&client_id=<?= $client_id; ?>">
                                    <?= $client_name; ?>
                                </a>
                            </td>
                            <td data-order="<?= $contact_phone; ?>">
                                <a href="tel:<?= $contact_phone; ?>">
                                    <?= $contact_phone; ?>
                                </a>
                            </td>
                            <td data-order="<?= $balance; ?>">
                                <?= numfmt_format_currency($currency_format, $balance, $config_currency_code); ?>
                            </td>
                            <td data-order="<?= $monthly_recurring_amount; ?>">
                                <?= numfmt_format_currency($currency_format, $monthly_recurring_amount, $config_currency_code); ?>
                            </td>
                            <td data-order="<?= $months_past_due; ?>">
                                <?= number_format($months_past_due, 1) ?>
                            </td>
                            <td data-order="<?= $past_due_amount; ?>">
                                <?= numfmt_format_currency($currency_format, $past_due_amount, $config_currency_code); ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>