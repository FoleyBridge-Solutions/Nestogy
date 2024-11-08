<!-- src/View/client.php -->
<?php
$datatable_settings = ",
    columnDefs: [{ visible: false, targets: 0 }]";
?>


<div class="card">
    <header class="card-header d-flex align-items-center">
        <h3 class="card-title mt-2"><i class="fa fa-fw fa-user-friends mr-2"></i>Client Management</h3>
        <ul class="list-inline ml-auto mb0">
            <li class="list-inline-item mr3">
                <a href="#!" data-bs-toggle="modal" data-bs-target="#dynamicModal" class="text-dark loadModalContentBtn" data-modal-file="client_add_modal.php?leads=0">
                    <i class="fa fa-fw fa-plus mr-2"></i><!-- Add Client -->
                </a>
            </li>
            <li class="list-inline-item">
                <a href="#" data-bs-toggle="modal" data-bs-target="#exportClientModal" class="text-dark">
                    <i class="fa fa-fw fa-download mr-2"></i><!-- Export Clients -->
                </a>
            </li>
        </ul>
    </header>

    <div class="card-body p-2 p-md-3">

        <div class="card-datatable table-responsive  pt-0">
            <table class="datatables-basic table border-top" id="clientsTable">
                <thead>
                    <tr>
                        <th style="display: none;">Accessed At</th>
                        <th data-priority="1">Name</th>
                        <th data-priority="2">Primary Location</th>
                        <th>Primary Contact</th>
                        <th class="text-right " data-priority="3">Billing (Past Due)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client):
                        $client_id = intval($client['client_id']);
                        $client_name = sanitizeInput($client['client_name']);
                        $client_type = sanitizeInput($client['client_type']);
                        $client_tags = $client['tag_names'];
                        $client_tags_display = '';
                        if (!empty($client_tags)) {
                            $client_tags_array = explode(',', $client_tags);
                            foreach ($client_tags_array as $tag) {
                                $client_tags_display .= "<span class='badge bg-label-secondary'>$tag</span> ";
                            }
                        }
                        $client_created_at = sanitizeInput($client['client_created_at']);
                        $client_accessed_at = sanitizeInput($client['client_accessed_at']);
                        $location_address = sanitizeInput($client['location_address']);
                        $location_zip = sanitizeInput($client['location_zip']);
                        $location_address_display = $location_address;
                        if (!empty($location_zip)) {
                            $location_address_display .= ", $location_zip";
                        }
                        $contact_name = sanitizeInput($client['contact_name']);
                        $contact_phone = sanitizeInput($client['contact_phone']);
                        $contact_extension = sanitizeInput($client['contact_extension']);
                        $contact_mobile = sanitizeInput($client['contact_mobile']);
                        $contact_email = sanitizeInput($client['contact_email']);
                        $amount_paid = floatval($client['client_payments']);
                        $recurring_monthly = floatval($client['client_recurring_monthly']);
                        $client_rate = floatval($client['client_rate']);
                        $client_past_due_amount = floatval($client['client_past_due_amount']);
                        if ($client_past_due_amount > 0) {
                            $past_due_text_color = 'text-danger';
                        } else {
                            $past_due_text_color = 'text-secondary';
                        }

                    ?>
                        <tr>
                            <td style="display: none;" data-order="<?= $client_accessed_at; ?>">
                                <?= $client_accessed_at; ?>
                            </td>
                            <td data-order="<?= $client_name; ?>">
                                <a href="/public/?page=client&action=show&client_id=<?= $client_id; ?>">
                                    <h4><i class="bx bx-right-arrow me-1"></i><?= $client_name; ?></h4>
                                </a>

                                <?php
                                if (!empty($client_type)) {
                                ?>
                                    <div class="text-secondary mt-1">
                                        <?= $client_type; ?>
                                    </div>
                                <?php } ?>

                                <div class="mt-1">
                                    <?= $client_tags_display; ?>
                                </div>


                                <div class="mt-1 text-secondary">
                                    <small><strong>Created:</strong> <?= $client_created_at; ?></small>
                                </div>

                            </td>
                            <td data-order="<?= $location_address_display; ?>">
                                <a href="//maps.google.com/?q=<?= urlencode($location_address . ' ' . $location_zip) ?>" target="_blank">
                                    <?= $location_address_display; ?>
                                </a>
                            </td>
                            <td data-order="<?= $contact_name; ?>">
                                <?php
                                if (empty($contact_name) && empty($contact_phone) && empty($contact_mobile) && empty($client_email)) {
                                    echo "-";
                                }

                                if (!empty($contact_name)) { ?>
                                    <div class="text-bold">
                                        <i class="fa fa-fw fa-user text-secondary mr-2 mb-2"></i><?= $contact_name; ?>
                                    </div>
                                <?php } else {
                                    echo "-";
                                }

                                if (!empty($contact_phone)) { ?>
                                    <div class="mt-1">
                                        <i class="fa fa-fw fa-phone text-secondary mr-2 mb-2"></i>
                                        <?= $contact_phone; ?>
                                        <?php if (!empty($contact_extension)) {
                                            echo "x$contact_extension";
                                        } ?>
                                    </div>
                                <?php }

                                if (!empty($contact_mobile)) { ?>
                                    <div class="mt-1">
                                        <i class="fa fa-fw fa-mobile-alt text-secondary mr-2"></i><?= $contact_mobile; ?>
                                    </div>
                                <?php }

                                if (!empty($contact_email)) { ?>
                                    <div class="mt-1">
                                        <i class="fa fa-fw fa-envelope text-secondary mr-2"></i><a href="mailto:<?= $contact_email; ?>"><?= $contact_email; ?></a><button class='btn btn-sm clipboardjs' data-clipboard-text='<?= $contact_email; ?>'><i class='far fa-copy text-secondary'></i></button>
                                    </div>
                                <?php } ?>
                            </td>

                            <!-- Show Billing for Admin/Accountant roles only and if accounting module is enabled -->
                            <td class="text-right" data-order="<?= $client_past_due_amount; ?>">
                                <div class="mt-1">
                                    <span class="text-secondary">Past Due: </span> <span class="<?= $past_due_text_color; ?>"><?= numfmt_format_currency($GLOBALS['currency_format'], $client_past_due_amount, "USD"); ?></span>
                                </div>
                                <div class="mt-1">
                                    <span class="text-secondary">Paid (YTD):</span> <?= numfmt_format_currency($GLOBALS['currency_format'], $amount_paid, "USD"); ?>
                                </div>
                                <div class="mt-1">
                                    <span class="text-secondary">Monthly: </span> <?= numfmt_format_currency($GLOBALS['currency_format'], $recurring_monthly, "USD"); ?>
                                </div>
                                <div class="mt-1">
                                    <span class="text-secondary">Hourly Rate: </span> <?= numfmt_format_currency($GLOBALS['currency_format'], $client_rate, "USD"); ?>
                                </div>
                            </td>


                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
