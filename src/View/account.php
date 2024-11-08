<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php echo $account['account_name']; ?></h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="card-title">Account Details</h5> 
                        <ul class="list-group">
                            <li class="list-group-item">Account Name: <?php echo $account['account_name']; ?></li>
                            <li class="list-group-item">Account Type: <?php echo $account['account_type']; ?></li>
                            <li class="list-group-item">Account Balance: <?php echo numfmt_format_currency($currency_format, $account['account_balance'], 'USD'); ?></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5 class="card-title">Account Settings</h5>
                        <ul class="list-group">
                            <li class="list-group-item">Linked to Plaid: <?php echo $account['plaid_id'] ? 'Yes' : 'No'; ?></li>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <?php if (isset($plaid_status)) { ?> 
                                        <span class="badge bg-<?php echo $plaid_status == 'Linked' ? 'success' : 'danger'; ?>">
                                            Plaid Status: <?php echo $plaid_status; ?>
                                        </span>
                                    <?php } ?>
                                    <?php if (!isset($plaid_status) || $plaid_status == 'Unlinked') { ?>
                                        <button class="btn btn-primary loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="resync_account_modal.php?account_id=<?php echo $account['account_id']; ?>&plaid_status=<?php echo $plaid_status; ?>">
                                            Link to Plaid
                                        </button>
                                    <?php } ?>
                                    <?php if ($plaid_status == 'Linked') { ?>
                                        <button class="btn btn-primary loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="resync_account_modal.php?account_id=<?php echo $account['account_id']; ?>">
                                            Sync Transactions
                                        </button>
                                    <?php } ?>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <h5 class="card-title">Account Transactions</h5>
                        <div class="table-responsive card-datatable">
                            <table class="table table-striped datatables-basic">
                                <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction) { ?>
                                    <tr>
                                        <td><?php echo $transaction['date']; ?></td>
                                        <td><?php echo $transaction['name']; ?></td>
                                        <td><?php echo numfmt_format_currency($currency_format, -1 * $transaction['amount'], 'USD'); ?></td>
                                        <td>
                                            <?php if($transaction['reconciled'] == 0) { ?>
                                                <button class="btn btn-label-warning loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="bank_transaction_reconcile_modal.php?transaction_id=<?php echo $transaction['transaction_id']; ?>">
                                                    Reconcile
                                                </button>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function sendPublicToken(public_token) {
        console.log(public_token);
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "https://portal.twe.tech/api/plaid.php?public_token", true);
        xhr.setRequestHeader("Content-Type", "application/json");
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                console.log(xhr.responseText);
            }
        };
        console.log("public_token: " + public_token);
        var data = JSON.stringify({
            public_token: public_token,
            account_id: '<?php echo $account['account_id']; ?>'
        });
        xhr.send(data);
    }

    const receivedRedirectUri = window.location.href.includes('oauth_state_id') ? window.location.href : null;

    const handler = Plaid.create({
        token: "<?= $link_token ?>",
        receivedRedirectUri: receivedRedirectUri,
        onSuccess: function(public_token, metadata) {
            // Send the public_token to api to exchange for access_token
            sendPublicToken(public_token);
            // Reload the page
        },
        onExit: function(err, metadata) {
            // The user exited the Link flow.
            if (err != null) {
                // The user encountered a Plaid API error prior to exiting.
                console.log(err);
            }
            // metadata contains information about the institution
            // that the user selected and the most recent API request IDs.
            // Storing this information can be helpful for support.
        },
        // Add the oauthStateId parameter if available
        oauthStateId: receivedRedirectUri ? new URLSearchParams(window.location.search).get('oauth_state_id') : null
    });

    var linkButton = document.getElementById('link-button');
    var syncButton = document.getElementById('sync-button');
    if (linkButton) {
        linkButton.addEventListener('click', function() {
            console.log('Link button clicked');
            handler.open();
        });
    } else {
        console.log('Link button not found');
    }
    if (syncButton) {
        syncButton.addEventListener('click', function() {
            console.log('Sync button clicked');
            // TODO: Implement sync functionality
        });
    } else {
        console.log('Sync button not found');
    }
});
</script>