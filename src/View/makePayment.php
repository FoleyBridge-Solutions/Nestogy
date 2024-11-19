<?php
$payment_received = -1  *  $transaction['amount'];
?>
<div class="row">
    <div class="col-12">
        <div class="card mb-2">
            <div class="card-header py-3">
                <h3 class="card-title"><i class="fas fa-fw fa-credit-card mr-2"></i>Add Payments</h3>
            </div>

            <div class="card-body">
                <div id="credit-notification" class="alert alert-info" style="display: none;"></div>
                <form action="/old_pages/payment_add.php" method="post">
                    <?php if (isset($transaction)) { ?>
                        <input type="hidden" name="bank_transaction_id" value="<?= $transaction['transaction_id'] ?>">
                    <?php } ?>
                    <div class="row">
                        <div class="col-md-5 col-12">
                            <div class="form-group">
                                <label for="Client">Client</label>
                                <select name="Client" id="Client" class="form-control select2" required autocomplete="off">
                                    <option value="">Select Client</option>
                                    <?php
                                    foreach ($clients as $client) {
                                        echo "<option value='" . $client['client_id'] . "'>" . $client['client_name'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 col-12">
                            <div class="form-group">
                                <a class="btn btn-primary" href="/old_pages/client_add.php">Add by Invoice Numbers</a>
                                <p>
                                    <small>No Invoice? <a href="/old_pages/invoice_add.php">Create Invoice</a></small>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-2 col-12">
                        </div>
                        <div class="col-md-2 col-12">
                            <div class="form-group">
                                <h4>
                                    Amount Received
                                    <br>
                                    <b id="inputted_amount" class="text-success">
                                        <?= $payment_received ?>
                                    </b>
                                </h4>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <?php if ($alert) { ?>
                            <div class="col-12">
                                <div class="alert alert-danger">
                                    <?= $alert ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="row">
                        <div class="col-md-4 col-12">
                            <div class="form-group">
                                <label for="payment_date">Payment Date</label>
                                <input type="date" name="payment_date" id="payment_date" class="form-control" autocomplete="off" value="<?= $transaction['date'] ? date('Y-m-d', strtotime($transaction['date'])) : date('Y-m-d') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2 col-12">
                            <div class="form-group">
                                <label for="payment_method">Payment Method</label>
                                <select name="payment_method" id="payment_method" class="form-control" autocomplete="off">
                                    <option value="">Select Payment Method</option>
                                    <?php
                                    foreach ($categories as $category) {
                                        $category_name = nullable_htmlentities($category['category_name']);
                                    ?>
                                        <option <?php if ($category_name == "Check") {
                                                    echo "selected";
                                                } ?>><?= $category_name; ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 col-12">
                            <div class="form-group">
                                <label for="payment_account">Deposit to</label>
                                <select name="payment_account" id="payment_account" class="form-control" autocomplete="off">
                                    <option value="">Select Account</option>
                                    <?php
                                    foreach ($accounts as $account) {
                                        $account_type = nullable_htmlentities($account['account_type']);
                                        $account_id = intval($account['account_id']);
                                        $account_name = nullable_htmlentities($account['account_name']);
                                    ?>
                                        <option <?php if ($account_id == 13) {
                                                    echo "selected";
                                                } ?> value="<?= $account_id; ?>">
                                            <?= $account_name; ?>
                                        </option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 col-12">
                            <div class="form-group">
                                <label for="payment_reference">Payment Reference</label>
                                <input type="text" name="payment_reference" id="payment_reference" class="form-control" autocomplete="off"
                                <?php if (isset($transaction)) {
                                    echo "value='" . $transaction['name'] . " - " . date('F j, Y', strtotime($transaction['date'])) . "'";
                                } ?>
                                >
                            </div>
                        </div>
                        <div class="col-md-4 col-12">
                        </div>
                        <div class="col-md-2 col-12">
                            <div class="form-group">
                                <label for="payment_amount">Amount Received</label>
                                <input type="number" name="payment_amount" id="payment_amount" class="form-control" step="0.01" min="0.01" placeholder="<?= $payment_received ?>" autocomplete="off"
                                <?php if (isset($transaction)) {
                                    echo "value='" . $payment_received . "'";
                                } ?>
                                >
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card mb-2">
            <div class="card-header py-3">
                <h3 class="card-title"><i class="fas fa-fw fa-bill mr-2"></i>Invoices</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive pt-0">
                    <table class="table border-top">
                        <thead class="text-dark">
                            <tr>
                                <th>
                                    <input type="checkbox" id="check_all">
                                </th>
                                <th>Invoice Date</th>
                                <th>Invoice Number</th>
                                <th>Balance</th>
                                <th>Due Date</th>
                                <th>Amount to Apply</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Loading Animation -->
                            <tr id="loading-animation" style="display: none;">
                                <td colspan="7" class="text-center">
                                    <div class="spinner-grow text-primary" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </td>
                            </tr>
                            <!-- Empty Table Placeholder -->
                            <tr class="text-center empty-placeholder">
                                <td colspan="7">No Invoices Found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <btn id="apply_payment" class="btn btn-primary">Apply Payment</btn>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        function handleInvoiceSelection(invoice) {
            const balance = parseFloat(invoice.invoice_balance.replace(/[^0-9.-]+/g, ''));
            if (balance < 0.01) return ''; // Skip invoices with balance less than one penny

            return `
            <tr>
                <td><input type="checkbox" value="${invoice.invoice_id}" name="invoice_id[${invoice.invoice_id}]"></td>
                <td>${invoice.invoice_date}</td>
                <td>${invoice.invoice_number}</td>
                <td class="balance">${invoice.invoice_balance}</td>
                <td>${invoice.invoice_due}</td>
                <td><input type="numeric" name="invoice_payment_amount[${invoice.invoice_id}]" class="form-control" step="0.01" min="0.01" max="${balance}"></td>
            </tr>`;
        }

        function updateTotalAmount() {
            const total = $('input[name^="invoice_payment_amount"]').toArray().reduce((sum, input) => sum + (parseFloat($(input).val()) || 0), 0);
            const currencySymbol = $('#inputted_amount').data('currency-symbol');
            $('#inputted_amount').text(currencySymbol + total.toFixed(2));
        }

        function updateAmountToApply(checkbox) {
            const $row = $(checkbox).closest('tr');
            const balance = parseFloat($row.find('.balance').text().replace(/[^0-9.-]+/g, ''));
            const amountInput = $row.find('input[type="numeric"]');
            let remainingAmount = parseFloat($('#payment_amount').val()) || 0;

            $('input[name^="invoice_payment_amount"]').each(function() {
                remainingAmount -= parseFloat($(this).val()) || 0;
            });

            if ($(checkbox).prop('checked')) {
                const amountToApply = Math.min(balance, remainingAmount);
                amountInput.val(amountToApply.toFixed(2));
            } else {
                amountInput.val('');
            }

            recalculateAmounts(); // Recalculate amounts after updating
        }

        function recalculateAmounts() {
            let paymentAmount = parseFloat($('#payment_amount').val()) || 0;
            let remainingAmount = paymentAmount;

            $('tbody tr').each(function() {
                const $row = $(this);
                const checkbox = $row.find('input[type="checkbox"]');
                const balance = parseFloat($row.find('.balance').text().replace(/[^0-9.-]+/g, ''));
                const amountInput = $row.find('input[type="numeric"]');

                if (checkbox.prop('checked')) {
                    const amountToApply = Math.min(balance, remainingAmount);
                    amountInput.val(amountToApply.toFixed(2));
                    remainingAmount -= amountToApply;
                } else {
                    amountInput.val('');
                }
            });

            updateTotalAmount();
        }

        function autoPopulateAmounts() {
            let paymentAmount = parseFloat($('#payment_amount').val()) || 0;
            let remainingAmount = paymentAmount;

            $('input[type="checkbox"]').prop('checked', false);
            $('input[name^="invoice_payment_amount"]').val('');

            $('tbody tr').each(function() {
                if (remainingAmount <= 0) return false;

                const $row = $(this);
                const balance = parseFloat($row.find('.balance').text().replace(/[^0-9.-]+/g, ''));
                const amountToApply = Math.min(balance, remainingAmount);

                $row.find('input[type="checkbox"]').prop('checked', true);
                $row.find('input[name^="invoice_payment_amount"]').val(amountToApply.toFixed(2));

                remainingAmount -= amountToApply;
            });

            updateTotalAmount();
        }

        function handleError(xhr) {
            console.log(xhr.responseText);
        }

        function fetchClientInvoices(clientId) {
            // Show loading animation and hide empty placeholder
            $('.empty-placeholder').hide();
            $('#loading-animation').show();

            setTimeout(() => {
                $.ajax({
                    url: `/ajax/ajax.php?client_invoices=${clientId}`,
                    type: 'GET',
                    success: function(response) {
                        const data = JSON.parse(response);
                        const table = $('.table tbody').empty();
                        data.forEach(invoice => {
                            const invoiceRow = handleInvoiceSelection(invoice);
                            if (invoiceRow) table.append(invoiceRow);
                        });
                        attachInvoiceEventHandlers();
                        $('#loading-animation').hide();
                    },
                    error: function(xhr) {
                        handleError(xhr);
                        $('#loading-animation').hide();
                        $('.empty-placeholder').show();
                    }
                });
            }, 750); // 0.75 second delay
        }

        function fetchClientCredits(clientId) {
            $.ajax({
                url: `/ajax/ajax.php?client_credits=${clientId}`,
                type: 'GET',
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.length > 0 && data[0].credit_amount !== undefined) {
                        $('#credit-notification').text(`Credits available for client: ${data[0].credit_amount}`).show();
                    } else {
                        $('#credit-notification').hide();
                    }
                },
                error: handleError
            });
        }

        function attachInvoiceEventHandlers() {
            $('input[name^="invoice_payment_amount"]').on('input', function() {
                const amount = parseFloat($(this).val()) || 0;
                $(this).closest('tr').find('input[type="checkbox"]').prop('checked', amount > 0);
                updateTotalAmount();
            });

            $('input[type="checkbox"]').on('change', function() {
                updateAmountToApply(this);
            });
        }

        function applyPayment() {
            const invoices = $('input[type="checkbox"]:checked').map(function() {
                return {
                    invoice_id: $(this).val(),
                    invoice_payment_amount: $(`input[name="invoice_payment_amount[${$(this).val()}]"]`).val()
                };
            }).get();

            const data = {
                invoices: invoices,
                payment_amount: $('#payment_amount').val(),
                payment_date: $('#payment_date').val(),
                payment_method: $('#payment_method').val(),
                payment_reference: $('#payment_reference').val(),
                payment_account: $('#payment_account').val(),
                client: $('#Client').val(),
                link_payment_to_transaction: $('#bank_transaction_id').val()
            };

            if (!data.payment_date) return alert('Payment date is required');
            if (!data.payment_method) return alert('Payment method is required');
            if (!data.payment_account) return alert('Payment account is required');
            if (!data.client) return alert('Client is required');
            if (!invoices.length) return alert('Please select at least one invoice');

            $.ajax({
                url: '/ajax/ajax.php?apply_payment',
                type: 'POST',
                data: JSON.stringify(data),
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        alert('Payment applied successfully');
                        location.reload();
                    } else {
                        alert('Failed to apply payment');
                    }
                },
                error: handleError
            });
        }

        $('#Client').select2().on('change', function() {
            const clientId = $(this).val();
            fetchClientInvoices(clientId);
            fetchClientCredits(clientId);
            
            // Add focus to payment reference after client selection
            setTimeout(() => {
                $('#payment_reference').focus();
            }, 100);
        });

        setTimeout(() => {
            $('#Client').select2('open');
        }, 100);

        $('#check_all').on('change', function() {
            const isChecked = $(this).prop('checked');
            $('input[type="checkbox"]').prop('checked', isChecked).each(function() {
                updateAmountToApply(this);
            });
        });

        $('#apply_payment').on('click', applyPayment);

        $('#payment_amount').on('input', autoPopulateAmounts);

        attachInvoiceEventHandlers();

        const initialCurrencySymbol = $('#inputted_amount').text().replace(/[0-9.,]/g, '').trim();
        $('#inputted_amount').data('currency-symbol', initialCurrencySymbol);
    });
</script>