<?php
    $invoice_ticket_button = false;
    $close_ticket_button = false;

    // Check if the ticket is attached to an invoice
    $invoice_id = $ticket['invoice_id'];
    if ($invoice_id == 0) {
        // Ticket is not attached to an invoice
        // Check if it is billable
        if ($ticket['ticket_billable'] && $ticket['ticket_invoice_id'] == 0) {
            $invoice_ticket_button = true;
        }
    }
    if ($ticket['ticket_status'] != 5) {
        $close_ticket_button = true;
    }

    switch ($ticket['ticket_priority']) {
        case 'Low':
            $priority_class = 'bg-label-primary';
            $priority_icon = 'fa fa-fw fa-thermometer-half';
            break;
        case 'Medium':
            $priority_class = 'bg-label-warning';
            $priority_icon = 'fa fa-fw fa-exclamation-triangle';
            break;
        case 'High':
            $priority_class = 'bg-label-danger';
            $priority_icon = 'fa fa-fw fa-fire';
            break;
        default:
            $priority_class = 'bg-label-secondary';
            $priority_icon = 'fa fa-fw fa-question';
            break;
    }
    switch ($ticket['ticket_status']) {
        case 1: //New
            $status_class = 'bg-label-danger';
            $status_icon = 'fa fa-fw fa-exclamation-triangle';
            break;
        case 2: //Open
            $status_class = 'bg-label-primary';
            $status_icon = 'fa fa-fw fa-check';
            break;
        case 3: //On Hold
            $status_class = 'bg-label-warning';
            $status_icon = 'fa fa-fw fa-pause';
            break;
        case 4: //Resolved
            $status_class = 'bg-label-success';
            $status_icon = 'fa fa-fw fa-check-circle';
            break;
        case 5: //Closed
            $status_class = 'bg-label-secondary';
            $status_icon = 'fa fa-fw fa-times-circle';
            break;
    }

?>

<div class="row">
    <!-- Left Column -->
    <div class="col-lg-8 col-md-12 order-lg-1 order-md-2 order-2">
        <div class="row">
            <div class="col">
                <div class="card mb-3 p-1">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-fw fa-info-circle mr-2 mb-2"></i><?= $ticket['ticket_subject']; ?>
                        </h5>
                    </div>
                    <div class="card-body prettyContent" id="ticketDetails" style="overflow-y: auto;">
                        <div class="row">
                            <div>
                                <?= $ticket['ticket_details']; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col">
            <div class="card card-outline card-dark mb-3 p-2">
                <div class="card-body">

                    <h5 class="text-secondary">Tasks</h5>
                    <form action="/post.php" method="post" autocomplete="off">
                        <input type="hidden" name="ticket_id" value="<?= $ticket_id; ?>">
                        <div class="form-group">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-fw fa-tasks"></i></span>
                                </div>
                                <input type="text" class="form-control" name="name" placeholder="Create Task">
                                <div class="input-group-append">
                                    <button type="submit" name="add_task" class="btn btn-dark">
                                        <i class="fas fa-fw fa-check"></i>
                                    </button>
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
                <!-- Ticket Responses -->
                <?php if ($num_replies > 0) { ?>
                <div class="card mb-3 card-action p-1 mb-3">
                    <div class="card-header">
                        <div class="card-action-title">
                            <h5 class="mb-4">Responses (<?= $num_replies; ?>):</h5>
                        </div>
                        <div class="card-action-element">
                            <ul class="list-inline mb-0">
                                <li class="list-inline-item">
                                    <a href="javascript:void(0);" class="card-collapsible"><i
                                            class="tf-icons bx bx-chevron-up"></i></a>
                                </li>
                                <li class="list-inline-item">
                                    <a href="javascript:void(0);" class="card-expand"><i
                                            class="tf-icons bx bx-fullscreen"></i></a>
                                </li>
                                <li class="list-inline-item">
                                    <a href="javascript:void(0);" class="card-reload"><i
                                            class="tf-icons bx bx-rotate-left scaleX-n1-rtl"></i></a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="collapse show">
                        <div class="card-body">
                            <table id="ticketRepliesTable" class="table table-striped display w-100">
                                <thead>
                                    <tr>
                                        <th data-priority="1" style="width: 40%">Reply</th>
                                        <th style="width: 20%">Time</th>
                                        <th style="width: 15%">Time Worked</th>
                                        <th data-priority="2" style="width: 15%">By</th>
                                        <?php if ($ticket_status_id != 5) {
                                            echo "<th data-priority='3' style='width: 10%'>Actions</th>";
                                        } ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ticket_replies as $reply) {
                                        $reply_id = $reply['ticket_reply_id'];
                                        $reply_content = $reply['ticket_reply'];
                                        $reply_created_at = $reply['ticket_reply_created_at'];
                                        $reply_time_worked = $reply['ticket_reply_time_worked'];
                                        $reply_user = $reply['user_name'];
                                        ?>
                                    <tr>
                                        <td><?= $reply_content; ?></td>
                                        <td><?= $reply_created_at; ?></td>
                                        <td><?= $reply_time_worked; ?></td>
                                        <td><?= $reply_user; ?></td>
                                        <?php if ($ticket_status_id != 5) {
                                            echo "<td>
                                                    <a href='/post.php?delete_ticket_reply=$reply_id' class='btn btn-danger btn-sm'>
                                                        <i class='fas fa-fw fa-trash'></i>
                                                    </a>
                                                </td>";
                                        } ?>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <!-- Ticket Respond Field -->
                <div class="card card-action mb-3 p-1">
                    <form class="mb-3 d-print-none" action="/post.php" method="post" autocomplete="off">
                        <div class="card-header">
                            <div class="card-action-title">
                                <h5 class="mb-4">Update Ticket:</h5>
                            </div>
                            <div class="card-action-element">
                                <ul class="list-inline mb-0">
                                    <li class="list-inline-item">
                                        <a href="javascript:void(0);" class="card-collapsible"><i
                                                class="tf-icons bx bx-chevron-up"></i></a>
                                    </li>
                                    <li class="list-inline-item">
                                        <a href="javascript:void(0);" class="card-expand"><i
                                                class="tf-icons bx bx-fullscreen"></i></a>
                                    </li>
                                    <li class="list-inline-item">
                                        <a href="javascript:void(0);" class="card-reload"><i
                                                class="tf-icons bx bx-rotate-left scaleX-n1-rtl"></i></a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="collapse">
                            <div class="card-body">
                                <?php if ($ticket_status_id != 5) { ?>
                                <input type="hidden" name="ticket_id" id="ticket_id" value="<?= $ticket['ticket_id']; ?>">
                                <input type="hidden" name="client_id" id="client_id" value="<?= $ticket['client_id']; ?>">
                                <div class="row">
                                    <div class="col">
                                        <div class="form-group">
                                            <div class="form-group">
                                                <textarea id="ticket_reply_<?= $ticket_id; ?>" class="form-control tinymce"
                                                    name="ticket_reply" placeholder="Type a response"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col">
                                        <div class="input-group mb-3">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i
                                                        class="fa fa-fw fa-thermometer-half"></i></span>
                                            </div>
                                            <select class="form-control select2" name="status" required>
                                                <?php
                                                        $ticket_statuses = [
                                                            [
                                                                'ticket_status_id' => 2,
                                                                'ticket_status_name' => 'Open'
                                                            ],
                                                            [
                                                                'ticket_status_id' => 3,
                                                                'ticket_status_name' => 'On Hold'
                                                            ],
                                                            [
                                                                'ticket_status_id' => 4,
                                                                'ticket_status_name' => 'Resolved'
                                                            ]
                                                        ];
                                                        foreach ($ticket_statuses as $status) {
                                                            echo "<option value='".$status['ticket_status_id']."'>".$status['ticket_status_name']."</option>";
                                                        }

                                                        ?>

                                            </select>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <!-- Time Tracking -->
                                        <div class="input-group mb-3">
                                            <input type="text" class="form-control" inputmode="numeric" id="hours"
                                                name="hours" placeholder="Hrs" min="0" max="23"
                                                pattern="0?[0-9]|1[0-9]|2[0-3]">
                                            <input type="text" class="form-control" inputmode="numeric" id="minutes"
                                                name="minutes" placeholder="Mins" min="0" max="59" pattern="[0-5]?[0-9]">
                                            <input type="text" class="form-control" inputmode="numeric" id="seconds"
                                                name="seconds" placeholder="Secs" min="0" max="59" pattern="[0-5]?[0-9]">
                                        </div>
                                    </div>
                                    <!-- Timer Controls -->
                                    <div class="col">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-success" id="startStopTimer"><i
                                                    class="fas fa-fw fa-pause"></i></button>
                                            <button type="button" class="btn btn-danger" id="resetTimer"><i
                                                    class="fas fa-fw fa-redo-alt"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <p class="font-weight-light" id="ticket_collision_viewing"></p>
                            </div>
                            <div class="card-footer">
                                <div class="row">
                                    <?php
                                        // Public responses by default (maybe configurable in future?)
                                        $ticket_reply_button_wording = "Respond";
                                        $ticket_reply_button_check = "checked";
                                        $ticket_reply_button_icon = "paper-plane";

                                        // Internal responses by default if 1) the contact email is empty or 2) the contact email matches the agent responding
                                        if (empty($contact_email) || $contact_email == $email) {
                                            // Internal
                                            $ticket_reply_button_wording = "Add note";
                                            $ticket_reply_button_check = "";
                                            $ticket_reply_button_icon = "sticky-note";
                                        } ?>

                                    <div class="col col-lg-3">
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input"
                                                    id="ticket_reply_type_checkbox" name="public_reply_type" value="1"
                                                    <?= $ticket_reply_button_check ?>>
                                                <label class="custom-control-label" for="ticket_reply_type_checkbox">Public
                                                    Update<br>
                                                    <small class="text-secondary">(Emails contact)</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col col-lg-2">
                                        <button type="submit" id="ticket_add_reply" name="add_ticket_reply"
                                            class="btn btn-label-primary text-bold"><i
                                                class="fas fa-<?= $ticket_reply_button_icon ?> mr-2"></i><?= $ticket_reply_button_wording ?></button>
                                    </div>
                                    <!-- End IF for reply modal -->
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Ticket Tasks -->
    </div>


    <!-- Right Column -->
    <div class="col-lg-4 col-md-12 order-lg-2 order-md-1 order-1 mb-3">
        <div class="card mb-3">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="card-action-title">
                        <h5 class="card-title mb-0">Ticket <?= $ticket['ticket_prefix'] . $ticket['ticket_number'] ?></h5>
                    </div>
                    <div class="card-action-element">
                        <ul class="list-inline mb-0">
                            <li class="list-inline-item">
                                <a href="javascript:void(0);" class="card-collapsible">
                                    <i class="tf-icons bx bx-chevron-up"></i>
                                </a>
                            </li>
                            <li class="list-inline-item">
                                <div class="dropdown dropleft text-center d-print-none">
                                    <button class="btn btn-light btn-sm" type="button" id="dropdownMenuButton"
                                        data-bs-toggle="dropdown">
                                        <i class="fas fa-fw fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <a href="#" class="dropdown-item loadModalContentBtn" data-bs-toggle="modal"
                                            data-bs-target="#dynamicModal"
                                            data-modal-file="ticket_edit_modal.php?ticket_id=<?= $ticket['ticket_id']; ?>">
                                            <i class="fas fa-fw fa-edit mr-2"></i>Edit
                                        </a>
                                        <a href="#" class="dropdown-item loadModalContentBtn" data-bs-toggle="modal"
                                            data-bs-target="#dynamicModal"
                                            data-modal-file="ticket_merge_modal.php?ticket_id=<?= $ticket['ticket_id']; ?>">
                                            <i class="fas fa-fw fa-clone mr-2"></i>Merge
                                        </a>
                                        <a href="#" class="dropdown-item loadModalContentBtn" data-bs-toggle="modal"
                                            data-bs-target="#dynamicModal"
                                            data-modal-file="ticket_edit_client_modal.php?ticket_id=<?= $ticket['ticket_id']; ?>">
                                            <i class="fas fa-fw fa-people-carry mr-2"></i>Change Client
                                        </a>
                                        <a href="#" class="dropdown-item loadModalContentBtn" data-bs-toggle="modal"
                                            data-bs-target="#dynamicModal"
                                            data-modal-file="ticket_edit_contact_modal.php?ticket_id=<?= $ticket['ticket_id']; ?>">
                                            <i class="fas fa-fw fa-user mr-2"></i>Change Contact
                                        </a>

                                        <?php if ($user_role == 3) { ?>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item text-danger text-bold confirm-link"
                                            href="/post.php?delete_ticket=<?= $ticket['ticket_id']; ?>">
                                            <i class="fas fa-fw fa-trash mr-2"></i>Delete
                                        </a>
                                        <?php } ?>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="collapse show">
                <div class="card-body">
                    <div class="row small">
                        <div class="table-responsive card-datatable">
                            <table id="ticketDetailsTable" class="me-2 table table-sm table-borderless table-striped table-hover datatables-basic">
                                <thead>
                                    <tr>
                                        <th>Details</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Client:</td>
                                        <td><?= $ticket['client_name'] ?></td>
                                    </tr>
                                    <tr>
                                        <td>Contact:</td>
                                        <td><?= $ticket['contact_name'] ?></td>
                                    </tr>
                                    <tr>
                                        <td>Location:</td>
                                        <td><a class="button" href="https://www.google.com/maps/place/<?= urlencode($ticket['location_address']) ?>, <?= urlencode($ticket['location_city']) ?>, <?= urlencode($ticket['location_state']) ?> <?= $ticket['location_zip'] ?>" target="_blank">
                                        <i class="fas fa-fw fa-map-marker-alt"></i>
                                        <?= $ticket['location_name'] ?>
                                        </a></td>
                                    </tr>
                                    <tr>
                                        <td>Priority:</td>
                                        <td><a href="#" class="loadModalContentBtn" data-bs-toggle="modal"
                                                data-bs-target="#dynamicModal"
                                                data-modal-file="ticket_edit_priority_modal.php?ticket_id=<?= $ticket['ticket_id']; ?>">
                                                <span class="badge rounded-pill <?= $priority_class ?>">
                                                    <i class="fas fa-fw fa-<?= $priority_icon ?> mr-2"></i><?= $ticket['ticket_priority'] ?>
                                                </span>
                                        </a></td>
                                    </tr>
                                    <tr>
                                        <td>Status:</td>
                                        <td><span
                                                class="badge rounded-pill <?= $status_class ?>">
                                                    <i class="fas fa-fw fa-<?= $status_icon ?> mr-2"></i><?= $ticket['ticket_status_name'] ?>
                                                </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Billable:</td>
                                        <td>
                                            <a
                                                href="/post.php?ticket_<?php if($ticket['ticket_billable'] == 1){?>un<?php }?>billable=<?= $ticket['ticket_id']; ?>">
                                                <?php if ($ticket['ticket_billable'] == 1) { ?>
                                                <span class="badge rounded-pill bg-label-success p-2">$</span>
                                                <?php } else { ?>
                                                <span class="badge rounded-pill bg-label-warning p-2">X</span>
                                                <?php } ?>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Time Tracked:</td>
                                        <td><span
                                                class="badge rounded-pill bg-label-secondary"><?= $ticket_total_reply_time; ?></span>
                                        </td>

                                    </tr>
                                    <tr>
                                        <td>Tasks Completed:</td>
                                        <td>
                                            <div class="progress">
                                                <?php if ($tasks_completed_percent < 15) {
                                                            $tasks_completed_percent_display = 15;
                                                        } else {
                                                            $tasks_completed_percent_display = $tasks_completed_percent;
                                                        } 
                                                        if ($task_count == 0) {
                                                            $tasks_completed_percent_display = 100;
                                                            $tasks_completed_percent = 100;
                                                        } ?>
                                                <div class="progress-bar progress-bar-striped bg-primary"
                                                    role="progressbar"
                                                    style="width: <?= $tasks_completed_percent_display; ?>%;"
                                                    aria-valuenow="<?= $tasks_completed_percent_display; ?>"
                                                    aria-valuemin="0" aria-valuemax="100">
                                                    <?= $tasks_completed_percent; ?>%</div>
                                            </div>
                                            <div>
                                                <small>
                                                    <?= $completed_task_count; ?> of
                                                    <?= $task_count; ?> tasks completed
                                                </small>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Trips:</td>
                                        <td>

                                            <a class="loadModalContentBtn" href="#" data-bs-toggle="modal"
                                                data-bs-target="#dynamicModal"
                                                data-modal-file="trip_add_modal.php?client_id=<?= $client_id; ?>">
                                                <span class="badge rounded-pill bg-label-secondary">Add a Trip</span>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            Collaborators:
                                        </td>
                                        <td>
                                            <?php
                                                    if (empty($ticket_collaborators)) {
                                                        ?>

                                                <span class="badge rounded-pill bg-label-secondary">No Collaborators</span>
                                            <?php
                                                    } else {
                                                        foreach ($ticket_collaborators as $collaborator) {
                                                            echo "<span class='badge rounded-pill bg-label-primary'>$collaborator</span>";
                                                        }
                                                    }
                                                    ?>


                                        </td>
                                    <tr>
                                        <td>Created:</td>
                                        <td><span
                                                class="badge rounded-pill bg-label-secondary"><?= date('d/m/Y H:i', strtotime($ticket['ticket_created_at'])); ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Updated:</td>
                                        <td><span
                                                class="badge rounded-pill bg-label-secondary"><?= date('d/m/Y H:i', strtotime($ticket['ticket_updated_at'])); ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Scheduled:</td>
                                        <td>
                                            <a class="loadModalContentBtn" href="#" data-bs-toggle="modal"
                                                data-bs-target="#dynamicModal"
                                                data-modal-file="ticket_edit_schedule_modal.php?ticket_id=<?= $ticket['ticket_id']; ?>">
                                                <span
                                                    class="badge rounded-pill bg-label-<?= $ticket['ticket_schedule'] ? 'success' : 'warning' ?>"><?= $ticket['ticket_schedule'] ? $ticket['ticket_schedule'] : 'Create Appointment'; ?></span>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Assigned to:</td>
                                        <td><a class="loadModalContentBtn" href="#" data-bs-toggle="modal"
                                                data-bs-target="#dynamicModal"
                                                data-modal-file="ticket_assign_modal.php?ticket_id=<?= $ticket['ticket_id']; ?>">
                                                <span
                                                    class="badge rounded-pill bg-label-<?= $ticket['ticket_assigned_to'] == 0 ? 'warning' : 'primary' ?>"><?= $ticket['user_name'] ?? 'Unassigned'; ?></span></a>
                                        </td>
                                    </tr>
                                    <?php if (empty($ticket['contact_id'])) { ?>
                                    <tr>
                                        <td>Contact:</td>
                                        <td>
                                            <a class="loadModalContentBtn" href="#" data-bs-toggle="modal"
                                                data-bs-target="#dynamicModal"
                                                data-modal-file="ticket_edit_contact_modal.php?ticket_id=<?= $ticket['ticket_id']; ?>">
                                                <span class="badge rounded-pill bg-label-secondary">Add Contact</span>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                    <tr>
                                        <td>
                                            Watchers:
                                        </td>
                                        <td>
                                            <?php if (empty($ticket['ticket_watchers']))
                                                {
                                                    ?>
                                            <a class="loadModalContentBtn" href="#" data-bs-toggle="modal"
                                                data-bs-target="#dynamicModal"
                                                data-modal-file="ticket_add_watcher_modal.php?ticket_id=<?= $ticket['ticket_id']; ?>">
                                                <span class="badge rounded-pill bg-label-secondary">Add a Watcher</span>
                                            </a>
                                            <?php
                                                }
                                                else
                                                {
                                                    echo $ticket['ticket_watchers'];
                                                }
                                                ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Asset:</td>
                                        <td>
                                            <?php if (empty($ticket['asset_id']))
                                                {
                                                    ?>
                                            <a class="loadModalContentBtn" href="#" data-bs-toggle="modal"
                                                data-bs-target="#dynamicModal"
                                                data-modal-file="ticket_edit_asset_modal.php?ticket_id=<?= $ticket['ticket_id']; ?>">
                                                <span class="badge rounded-pill bg-label-primary">Add an Asset</span>
                                            </a>
                                            <?php
                                                }
                                                else
                                                {
                                                    echo $ticket['asset_name'];
                                                }
                                                ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Vendor:</td>
                                        <td>
                                            <?php if (empty($ticket['vendor_id']))
                                                {
                                                    ?>
                                            <a class="loadModalContentBtn" href="#" data-bs-toggle="modal"
                                                data-bs-target="#dynamicModal"
                                                data-modal-file="ticket_edit_vendor_modal.php?ticket_id=<?= $ticket['ticket_id']; ?>">
                                                <span class="badge rounded-pill bg-label-secondary">Add a Vendor</span>
                                            </a>
                                            <?php
                                                }
                                                else
                                                {
                                                    echo $ticket['vendor_name'];
                                                }
                                                ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Products:</td>
                                        <td>
                                            <?php if (empty($ticket['ticket_products_display']))
                                                {
                                                    ?>
                                            <a class="loadModalContentBtn" href="#" data-bs-toggle="modal"
                                                data-bs-target="#dynamicModal"
                                                data-modal-file="ticket_add_product_modal.php?ticket_id=<?= $ticket['ticket_id']; ?>">
                                                <span class="badge rounded-pill bg-label-secondary">Manage
                                                    Products</span>
                                            </a>
                                            <?php
                                                }
                                                else
                                                {
                                                    echo $ticket['ticket_products_display'];
                                                }
                                                ?>
                                        </td>
                                    </tr>
                                    <!-- Ticket closure info -->
                                    <?php if ($ticket['ticket_status'] == "Closed") {
                                                ?>
                                    <tr>
                                        <td>Feedback:</td>
                                        <td><?= $ticket['ticket_feedback']; ?></td>
                                    </tr>
                                    <?php } ?>
                                    <!-- END Ticket closure info -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Ticket Actions -->
                    <?php if ($ticket['ticket_status'] != 5 || $ticket['ticket_billable']) { ?>
                    <div class="mt-3">
                        <div class="row">
                            <?php if ($invoice_ticket_button) { ?>
                            <div class="col">
                                <a href="#" class="btn btn-primary btn-block mb-3 loadModalContentBtn"
                                    data-bs-toggle="modal" data-bs-target="#dynamicModal"
                                    data-modal-file="ticket_invoice_add_modal.php?ticket_id=<?= $ticket['ticket_id']; ?>&ticket_total_reply_time=<?= $ticket['ticket_total_reply_time']; ?>">
                                    <i class="fas fa-fw fa-file-invoice mr-2"></i>Invoice Ticket
                                </a>
                            </div>
                            <?php } ?>
                            <?php if ($close_ticket_button) { ?>
                            <div class="col">
                                <a href="/post.php?close_ticket=<?= $ticket['ticket_id']; ?>"
                                    class="btn btn-secondary btn-block confirm-link" id="ticket_close">
                                    <i class="fas fa-fw fa-gavel mr-2"></i>Close Ticket
                                </a>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                    <?php } ?>
                    <!-- End of Ticket Actions -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let repliesTable;

    document.addEventListener('DOMContentLoaded', function() {
        initializeRepliesTable();
    });

    function initializeRepliesTable() {
        if (!$.fn.DataTable.isDataTable('#ticketRepliesTable')) {
            repliesTable = $('#ticketRepliesTable').DataTable({
                responsive: true,
                stateSave: true,
                autoWidth: false,
                order: [[1, 'desc']],
                columnDefs: [
                    {
                        targets: 0,
                        width: '40%',
                        render: function(data, type, row) {
                            if (type === 'display' && data.length > 100) {
                                return `<div class="text-wrap width-200">${data}</div>`;
                            }
                            return data;
                        }
                    },
                    {
                        targets: 1,
                        width: '20%'
                    },
                    {
                        targets: 2,
                        width: '15%'
                    },
                    {
                        targets: 3,
                        width: '15%'
                    },
                    {
                        targets: -1,
                        width: '10%',
                        orderable: false,
                        searchable: false
                    }
                ],
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                language: {
                    search: '',
                    searchPlaceholder: 'Search replies...',
                    lengthMenu: '_MENU_ replies per page'
                },
                drawCallback: function() {
                    $(window).trigger('resize');
                    this.api().columns.adjust();
                }
            });

            $('.card-collapsible').on('click', function() {
                setTimeout(function() {
                    repliesTable.columns.adjust();
                }, 100);
            });

            $('.card-expand').on('click', function() {
                setTimeout(function() {
                    repliesTable.columns.adjust();
                }, 100);
            });

            $(window).on('resize', function() {
                if (repliesTable) {
                    repliesTable.columns.adjust();
                }
            });
        }
    }
</script>

<style>
    #ticketRepliesTable {
        width: 100% !important;
    }
    
    #ticketRepliesTable_wrapper {
        width: 100% !important;
    }

    .text-wrap {
        white-space: normal;
    }

    .width-200 {
        min-width: 200px;
    }
</style>
