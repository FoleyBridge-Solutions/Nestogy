<div class="card card-action d-print-none mb-3">
    <div class="card-header">
        <div class="card-action-title">
            <h4>
                <?php if ($client_page) {
                    echo ucwords($client_name);
                } else {
                    echo $page_name;
                } ?>
            </h4>
        </div>
        <div class="card-action-element">
            <ul class="list-inline mb-0">
                <li class="list-inline-item">
                    <a href="javascript:void(0);" data-bs-toggle='tooltip' data-bs-placement='top' title='More Information Toggle' class="card-collapsible"><i class="tf-icons bx bx-chevron-up"></i></a>
                </li>
                <li class="list-inline-item">
                    <div class="dropdown dropleft text-center" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit, Export or Archive <?= initials($client_name); ?>">
                        <button class="btn btn-dark btn-sm float-right" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-fw fa-ellipsis-v"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a href="#!" data-bs-toggle="modal" data-bs-target="#dynamicModal" class="dropdown-item loadModalContentBtn" data-modal-file="client_edit_modal.php?client_id=<?= $client_id; ?>">
                                <i class="fas fa-fw fa-edit mr-2"></i>Edit Client
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="#!" data-bs-toggle="modal" data-bs-target="#dynamicModal" class="dropdown-item loadModalContentBtn" data-modal-file="client_export_modal.php?client_id=<?= $client_id; ?>">
                                <i class="fas fa-fw fa-file-pdf mr-2"></i>Export Data
                            </a>
                            <div class="dropdown-divider"></div>

                            <a href="#!" data-bs-toggle="modal" data-bs-target="#dynamicModal" class="dropdown-item loadModalContentBtn" data-modal-file="client_archive_modal.php?client_id=<?= $client_id; ?>">
                                <i class="fas fa-fw fa-archive mr-2"></i>Archive Client
                            </a>
                            <?php if ($user_role == "admin") { ?>
                                <div class="dropdown-divider"></div>
                                <a href="#!" data-bs-toggle="modal" data-bs-target="#dynamicModal" class="dropdown-item loadModalContentBtn" data-modal-file="client_delete_modal.php?client_id=<?= $client_id; ?>">
                                    <i class="fas fa-fw fa-trash mr-2"></i>Delete Client
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
    <div class="card-body py-2">
        <div class="collapse <?php if (basename($_SERVER["PHP_SELF"]) == "client_overview.php") {
                                    echo "show";
                                } ?>" id="clientHeader">
            <div class="row">
                <div class="col-md border-top">
                    <h5 class="text-secondary mt-1">Primary Location</h5>
                    <?php if (!empty($location_address)) { ?>
                        <div>
                            <a href="//maps.<?= $map_source; ?>.com/?q=<?= "$location_address $location_zip"; ?>" target="_blank">
                                <i class="fa fa-fw fa-map-marker-alt text-secondary ml-1 mr-2"></i><?= $location_address; ?>
                                <div><i class="fa fa-fw ml-1 mr-2"></i><?= "$location_city $location_state $location_zip"; ?></div>
                            </a>
                        </div>
                    <?php }

                    if (!empty($location_phone)) { ?>
                        <div class="mt-1">
                            <i class="fa fa-fw fa-phone text-secondary ml-1 mr-2"></i><a href="tel:<?= $location_phone ?>"><?= $location_phone; ?></a>
                        </div>
                        <hr class="my-2">
                    <?php }

                    if (!empty($client_website)) { ?>
                        <div class="mt-1">
                            <i class="fa fa-fw fa-globe text-secondary ml-1 mr-2"></i><a target="_blank" href="//<?= $client_website; ?>"><?= $client_website; ?></a>
                        </div>
                    <?php } ?>

                </div>

                <div class="col-md border-left border-top">
                    <h5 class="text-secondary mt-1">Primary Contact</h5>
                    <?php

                    if (!empty($contact_name)) { ?>
                        <div>
                            <i class="fa fa-fw fa-user text-secondary ml-1 mr-2"></i> <?= $contact_name; ?>
                        </div>
                    <?php }

                    if (!empty($contact_email)) { ?>
                        <div class="mt-1">
                            <i class="fa fa-fw fa-envelope text-secondary ml-1 mr-2"></i>
                            <a href="mailto:<?= $contact_email; ?>"> <?= $contact_email; ?></a>
                        </div>
                    <?php
                    }

                    if (!empty($contact_phone)) { ?>
                        <div class="mt-1">
                            <i class="fa fa-fw fa-phone text-secondary ml-1 mr-2"></i>
                            <a href="tel:<?= $contact_phone; ?>"><?= $contact_phone; ?></a>

                            <?php
                            if (!empty($contact_extension)) {
                                echo "<small>x$contact_extension</small>";
                            }
                            ?>
                        </div>
                    <?php
                    }

                    if (!empty($contact_mobile)) { ?>
                        <div class="mt-1">
                            <i class="fa fa-fw fa-mobile-alt text-secondary ml-1 mr-2"></i>
                            <a href="tel:<?= $contact_mobile; ?>"><?= $contact_mobile; ?></a>
                        </div>
                    <?php } ?>

                </div>

                <div class="col-md border-left border-top">
                    <h5 class="text-secondary mt-1">Billing</h5>
                    <table>
                        <tr>
                            <td class="text-secondary">Hourly Rate:</td>
                            <td class="text-dark"><?= numfmt_format_currency($GLOBALS['currency_format'], $client_rate, $client_currency_code); ?></td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Paid (YTD):</td>
                            <td class="text-dark"><?= numfmt_format_currency($GLOBALS['currency_format'], $client_amount_paid, $client_currency_code); ?></td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Balance:</td>
                            <td class="<?php if ($client_balance > 0 || $client_balance < 0) {
                                            echo "text-danger";
                                        } else {
                                            echo "text-dark";
                                        } ?>"><?= numfmt_format_currency($GLOBALS['currency_format'], $client_balance, $client_currency_code); ?></td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Monthly Recurring:</td>
                            <td class="text-dark"><?= numfmt_format_currency($GLOBALS['currency_format'], $client_recurring_monthly, $client_currency_code); ?></td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Net Terms:</td>
                            <td class="text-dark"><?= $client_net_terms; ?><small class="text-secondary ml-1">Days</small></td>
                        </tr>
                        <?php if (!empty($client_tax_id_number)) { ?>
                            <tr>
                                <td class="text-secondary">Tax ID:</td>
                                <td class="text-dark"><?= $client_tax_id_number; ?></td>
                            </tr>
                        <?php } ?>
                    </table>
                </div>


                <div class="col-md border-left border-top">
                    <h5 class="text-secondary mt-1">Support</h5>
                    <div class="ml-1 text-secondary">Open Tickets
                        <span class="text-dark float-right"><?= $client_open_tickets; ?></span>
                    </div>
                    <div class="ml-1 text-secondary mt-1">Closed Tickets
                        <span class="text-dark float-right"><?= $client_closed_tickets; ?></span>
                    </div>
                    <?php
                    if (!empty($client_tag_name_display_array)) { ?>
                        <hr>
                        <?= $client_tags_display; ?>
                    <?php } ?>
                </div>

            </div>
        </div>
    </div>
</div>
<div class="nav-align-top">
    <span class="text-secondary">
        <a href="/public/?page=<?= $data['return_page']['link']; ?>">
            <?= $data['return_page']['name']; ?>
        </a>
        <span class="text-secondary">
            /
        </span>
        <a href="/public/?page=client&client_id=<?= $client_id; ?>">
            <?= $client_name; ?>
        </a>
        <span class="text-secondary">
            /
        </span>
        <?= ucwords($template); ?>
    </span>
</div>
<!-- End of Client Header -->
