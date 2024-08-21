
<div class="row">
    <div class="col">
        <div class="card mb-2">
            <div class="card-header py-3">
                <h3 class="card-title"><i class="fas fa-fw fa-credit-card mr-2"></i>Add Payments</h3>
            </div>

            <div class="card-body">
                <form action="/old_pages/payment_add.php" method="post">
                    <div class="row">
                        <div class="col-5">
                            <div class="form-group">
                                <label for="Client">Client</label>
                                <select name="Client" id="Client" class="form-control select2" required>
                                    <option value="">Select Client</option>
                                    <?php
                                    foreach ($clients as $client) {
                                        echo "<option value='" . $client['client_id'] . "'>" . $client['client_name'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="form-group">
                                <a class="btn btn-primary" href="/old_pages/client_add.php">Add by Invoice Numbers</a>
                                <p>
                                    <small>No Invoice? <a href="/old_pages/invoice_add.php">Create Invoice</a></small>
                                </p>
                            </div>
                        </div>
                        <div class="col-2">
                        </div>
                        <div class="col-2">
                            <div class="form-group">
                                <h4>
                                    Amount Recieved
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
                        <div class="col-4">
                            <div class="form-group">
                                <label for="payment_date">Payment Date</label>
                                <input type="date" name="payment_date" id="payment_date" class="form-control" value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-2">
                            <div class="form-group">
                                <label for="payment_method">Payment Method</label>
                                <select name="payment_method" id="payment_method" class="form-control">
                                    <option value="">Select Payment Method</option>
                                    <?php

                                    foreach ($categories as $category) {
                                        $category_name = nullable_htmlentities($category['category_name']);
                                    ?>
                                        <option <?php if ($config_default_payment_method == $category_name) {
                                                    echo "selected";
                                                } ?>><?= $category_name; ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-2">
                            <div class="form-group">
                                <label for="payment_account">Deposit to</label>
                                <select name="payment_account" id="payment_account" class="form-control">
                                    <option value="">Select Account</option>
                                    <?php
                                    foreach ($accounts as $account) {
                                        $account_type = nullable_htmlentities($account['account_type']);
                                        $account_id = intval($account['account_id']);
                                        $account_name = nullable_htmlentities($account['account_name']);


                                    ?>
                                        <option <?php if ($config_default_payment_account == $account_id) {
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
                        <div class="col-2">
                            <div class="form-group">
                                <label for="payment_reference">Payment Reference</label>
                                <input type="text" name="payment_reference" id="payment_reference" class="form-control">
                            </div>
                        </div>
                        <div class="col-4">
                        </div>
                        <div class="col-2">
                            <div class="form-group">
                                <label for="payment_amount">Amount Recieved</label>
                                <input type="number" name="payment_amount" id="payment_amount" class="form-control" step="0.01" min="0.01" placeholder="<?= $payment_received ?>">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col">
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
                            <!-- Empty Table Placeholder -->
                            <tr class="text-center">
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
            return `
            <tr>
                <td><input type="checkbox" value="${invoice.invoice_id}" name="invoice_id[${invoice.invoice_id}]"></td>
                <td>${invoice.invoice_date}</td>
                <td>${invoice.invoice_number}</td>
                <td class="balance">${invoice.invoice_balance}</td>
                <td>${invoice.invoice_due}</td>
                <td><input type="numeric" name="invoice_payment_amount[${invoice.invoice_id}]" class="form-control" step="0.01" min="0.01" max="${parseFloat(invoice.invoice_balance.replace(/[^0-9.-]+/g, ''))}"></td>
            </tr>`;
        }

        function updateTotalAmount() {
            const total = $('input[name^="invoice_payment_amount"]').toArray().reduce((sum, input) => sum + (parseFloat($(input).val()) || 0), 0);
            const currencySymbol = $('#inputted_amount').data('currency-symbol');
            $('#inputted_amount').text(currencySymbol + total.toFixed(2));
        }

        function updateAmountToApply(checkbox) {
            const balance = $(checkbox).closest('tr').find('.balance').text().replace(/[^0-9.-]+/g, '');
            const amount = $(checkbox).prop('checked') ? balance : '';
            $(checkbox).closest('tr').find('input[type="numeric"]').val(amount);
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
            $.ajax({
                url: `/ajax/ajax.php?client_invoices=${clientId}`,
                type: 'GET',
                success: function(response) {
                    const data = JSON.parse(response);
                    const table = $('.table tbody').empty();
                    data.forEach(invoice => table.append(handleInvoiceSelection(invoice)));
                    attachInvoiceEventHandlers();
                },
                error: handleError
            });
        }

        function fetchClientCredits(clientId) {
            $.ajax({
                url: `/ajax/ajax.php?client_credits=${clientId}`,
                type: 'GET',
                success: function(response) {
                    if (response.length > 0) {
                        alert(`Credits available for client. [${response[0].credit_amount}]`);
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
                client: $('#Client').val()
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
        });

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