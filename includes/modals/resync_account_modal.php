<?php require_once "/var/www/portal.twe.tech/includes/inc_all_modal.php";

$account_id = $_GET['account_id'];
$plaid_status = $_GET['plaid_status'] ?? 'Unlinked';

?>

<div class="modal" id="resyncAccountModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-fw fa-<?php echo $plaid_status == 'Unlinked' ? 'plus' : 'sync'; ?> mr-2"></i>
                    <?php echo $plaid_status == 'Unlinked' ? 'Link' : 'Resync'; ?> Account
                </h5>
                <button type="button" class="close text-white" data-bs-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="/post.php" method="post" autocomplete="off">
                <div class="modal-body bg-white">
                    <div class="form-group">
                        <?php if ($plaid_status == 'Unlinked') { ?>
                            <button type="button" id="linkButton" class="btn btn-primary">Link Account</button>
                        <?php } ?>
                        <?php if ($plaid_status == 'Linked') { ?>
                            <button type="button" id="syncButton" class="btn btn-primary">Sync Transactions</button>
                        <?php } ?>
                        <?php if ($plaid_status != 'Unlinked' && $plaid_status != 'Linked') { ?>
                            broked
                        <?php } ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    <?php if ($plaid_status == 'Unlinked') { ?>
        // Get plaid link token
        let plaidLinkToken = '';
        $.ajax({
            url: '/ajax/ajax.php?plaid_link_token',
            type: 'GET',
            dataType: 'text', // Change to 'text' to get the raw response
            success: function(response) {
                console.log("Raw response:", response);
                try {
                    const jsonResponse = JSON.parse(response);
                    plaidLinkToken = jsonResponse.link_token;
                    console.log("Plaid link token:", plaidLinkToken);

                    // Create Plaid Link handler after successfully obtaining the token
                    createPlaidLinkHandler(plaidLinkToken);
                } catch (e) {
                    console.error("Error parsing JSON response:", e);
                    console.log("Response that caused the error:", response);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Error fetching Plaid link token:", textStatus, errorThrown);
                console.log("Response text:", jqXHR.responseText);
            }
        });
        // Function to create Plaid Link handler
        function createPlaidLinkHandler(token) {
            const handler = Plaid.create({
                token: token,
                onSuccess: (public_token, metadata) => {
                    console.log("Plaid Link success:", public_token, metadata);
                    $.ajax({
                        url: '/ajax/ajax.php?save_access_token&account_id=<?php echo $account_id; ?>',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            public_token: public_token
                        }),
                        success: function(response) {
                            console.log("Plaid Link token response:", response);
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error("Error saving access token:", textStatus, errorThrown);
                            console.log("Response text:", jqXHR.responseText);
                        }
                    });
                },
                onLoad: () => {
                    console.log("Plaid Link loaded");
                },
                onExit: (err, metadata) => {
                    console.log("Plaid Link exited:", err, metadata);
                },
                onEvent: (eventName, metadata) => {
                    console.log("Plaid Link event:", eventName, metadata);
                },
            });

            document.getElementById('linkButton').onclick = () => handler.open();
        }
    <?php } ?>
    <?php if ($plaid_status == 'Linked') { ?>
        document.getElementById('syncButton').onclick = () => {
            console.log("Sync button clicked");
            $.ajax({
                url: '/ajax/ajax.php?sync_plaid_transactions&account_id=<?php echo $account_id; ?>',
                type: 'GET',
                success: function(response) {
                    console.log("Plaid transactions synced:", response);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("Error syncing Plaid transactions:", textStatus, errorThrown);
                    console.log("Response text:", jqXHR.responseText);
                }
            });
        };
    <?php } ?>
</script>