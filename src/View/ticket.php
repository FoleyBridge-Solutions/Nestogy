<?php
$ticket_prefix = $ticket['ticket_prefix'];
$ticket_number = $ticket['ticket_number'];
$ticket_subject = $ticket['ticket_subject'];
$ticket_details = $ticket['ticket_details'];
$ticket_status = $ticket['ticket_status_name'];
$ticket_status_id = $ticket['ticket_status_id'];
$ticket_id = $ticket['ticket_id'];
$ticket_priority = $ticket['ticket_priority'];
$ticket_billable = $ticket['ticket_billable'];
$ticket_invoice_id = $ticket['ticket_invoice_id'];
$ticket_replies = $data['ticket_replies'];
$ticket_reply_num = count($ticket_replies);
$ticket_created_at = $ticket['ticket_created_at'];
isset($ticket['ticket_updated_at']) ? $ticket_updated_at = $ticket['ticket_updated_at'] : $ticket_updated_at = "Not Updated";
$ticket_schedule = $ticket['ticket_schedule'];
isset($ticket["user_name"]) ? $ticket_assigned_to = $ticket["user_name"] : $ticket_assigned_to = "Unassigned";
if(!$client_page) {
    $client_name = "";
}

empty($ticket['ticket_collaborators']) ? $ticket_collaborators = array() : $ticket_collaborators = $ticket['ticket_collaborators'];

isset($ticket['ticker_schedule']) ? $ticket_schedule = $ticket['ticket_schedule'] : $ticket_schedule = "No Schedule Set";

$completed_task_count = 0; // TODO: Implement completed task count
$tasks_completed_percent = 0; // TODO: Implement tasks completed percent
$task_count = 0; // TODO: Implement task count


?>




<div class="row" style="overflow: visible;">
    <!-- Left -->
    <div class="col-lg-9 col-md-12 order-2 order-md-1">
        <div class="row">
            <!-- Ticket Details -->
            <div class="card me-3 p-2">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-fw fa-info-circle mr-2"></i><?= $ticket_subject; ?>
                    </h5>
                </div>
                <div class="card-body prettyContent" id="ticketDetails">
                    <div class="row">
                        <div>
                            <?= $ticket_details; ?>
                        </div>
                    </div>
                    <div class="row">
                    </div>
                </div>
            </div>

            <!-- Ticket Responses -->
            <?php if ($ticket_reply_num > 0) { ?>
                <div class="card mb-3 card-action p-2">
                    <div class="card-header">
                        <div class="card-action-title">
                            <h5 class="mb-4">Responses (<?= $ticket_reply_num; ?>):</h5>
                        </div>
                        <div class="card-action-element">
                            <ul class="list-inline mb-0">
                                <li class="list-inline-item">
                                    <a href="javascript:void(0);" class="card-collapsible"><i class="tf-icons bx bx-chevron-up"></i></a>
                                </li>
                                <li class="list-inline-item">
                                    <a href="javascript:void(0);" class="card-expand"><i class="tf-icons bx bx-fullscreen"></i></a>
                                </li>
                                <li class="list-inline-item">
                                    <a href="javascript:void(0);" class="card-reload"><i class="tf-icons bx bx-rotate-left scaleX-n1-rtl"></i></a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="collapse">
                        <div class="card-body">
                            <!-- Ticket replies -->
                            <table class="datatables-basic table border-top">
                                <thead>
                                    <tr>
                                        <th data-priority="1">Reply</th>
                                        <th>Time</th>
                                        <th>Time Worked</th>
                                        <th data-priority="2">By</th>
                                        <?php if ($ticket_status_id != 5) {
                                            echo "<th data-priority='3'>Actions</th>";
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
                                                echo "<td><a href='/post.php?delete_ticket_reply=$reply_id' class='btn btn-danger btn-sm'><i class='fas fa-fw fa-trash'></i></a></td>";
                                            } ?>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <!-- Ticket Respond Field -->
            <div class="card card-action mb-3 p-2">
                <form class="mb-3 d-print-none" action="/post.php" method="post" autocomplete="off">
                    <div class="card-header">
                        <div class="card-action-title">
                            <h5 class="mb-4">Update Ticket:</h5>
                        </div>
                        <div class="card-action-element">
                            <ul class="list-inline mb-0">
                                <li class="list-inline-item">
                                    <a href="javascript:void(0);" class="card-collapsible"><i class="tf-icons bx bx-chevron-up"></i></a>
                                </li>
                                <li class="list-inline-item">
                                    <a href="javascript:void(0);" class="card-expand"><i class="tf-icons bx bx-fullscreen"></i></a>
                                </li>
                                <li class="list-inline-item">
                                    <a href="javascript:void(0);" class="card-reload"><i class="tf-icons bx bx-rotate-left scaleX-n1-rtl"></i></a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="collapse">
                        <div class="card-body">
                            <?php if ($ticket_status_id != 5) { ?>
                                <input type="hidden" name="ticket_id" id="ticket_id" value="<?= $ticket_id; ?>">
                                <input type="hidden" name="client_id" id="client_id" value="<?= $client_id; ?>">
                                <div class="row">
                                    <div class="col">
                                        <div class="form-group">
                                            <div class="form-group">
                                                <textarea id="ticket_reply_<?= $ticket_id; ?>" class="form-control tinymce" name="ticket_reply"
                                                    placeholder="Type a response"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col">
                                        <div class="input-group mb-3">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-fw fa-thermometer-half"></i></span>
                                            </div>
                                            <select class="form-control select2"  name="status" required>
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
                                            <input type="text" class="form-control" inputmode="numeric" id="hours" name="hours"
                                                placeholder="Hrs" min="0" max="23" pattern="0?[0-9]|1[0-9]|2[0-3]">
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
                                            <input type="checkbox" class="custom-control-input" id="ticket_reply_type_checkbox"
                                                name="public_reply_type" value="1" <?= $ticket_reply_button_check ?>>
                                            <label class="custom-control-label" for="ticket_reply_type_checkbox">Public Update<br>
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

            <!-- Ticket Tasks -->
            <!-- Tasks Card -->
            <div class="card card-body card-outline card-dark p-2">
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
                <table class="table table-sm">
                    <?php

                    ?>
                </table>
            </div>            
        </div>
    </div>

    
    <!-- Right -->
    <div class="col-lg-3 col-md-12 order-1 order-md-2 ticket-sidebar">
        <div class="row">
            <div class="card card-action mb-3">
                <div class="card-header">
                    <div class="card-action-title row">
                        <div class="col">
                            <h5 class="card-title">Ticket <?= $ticket_prefix . $ticket_number ?></h5>
                        </div>
                    </div>
                    <div class="card-action-element">
                        <ul class="list-inline mb-0">
                            <li class="list-inline-item">
                                <a href="javascript:void(0);" class="card-collapsible"><i class="tf-icons bx bx-chevron-up"></i></a>
                            </li>
                            <li class="list-inline-item">
                                <div class="dropdown dropleft text-center d-print-none">
                                    <button class="btn btn-light btn-sm float-right" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown">
                                        <i class="fas fa-fw fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <a href="#"  class="dropdown-item loadModalContentBtn"data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="ticket_edit_modal.php?ticket_id=<?= $ticket_id; ?>">
                                            <i class="fas fa-fw fa-edit mr-2"></i>Edit
                                        </a>
                                        <a href="#" class="dropdown-item loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="ticket_merge_modal.php?ticket_id=<?= $ticket_id; ?>">
                                
                                            <i class="fas fa-fw fa-clone mr-2"></i>Merge
                                        </a>
                                        <a href="#" class="dropdown-item loadModalContentBtn"  data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="ticket_edit_client_modal.php?ticket_id=<?= $ticket_id; ?>">
                                            <i class="fas fa-fw fa-people-carry mr-2"></i>Change Client
                                        </a>
                                        <a href="#" class="dropdown-item loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="ticket_edit_contact_modal.php?ticket_id=<?= $ticket_id; ?>">
                                            <i class="fas fa-fw fa-user mr-2"></i>Change Contact
                                        </a>

                                        <?php if ($user_role == 3) { ?>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item text-danger text-bold confirm-link" href="/post.php?delete_ticket=<?= $ticket_id; ?>">
                                            <i class="fas fa-fw fa-trash mr-2"></i>Delete
                                        </a>
                                        <?php } ?>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="collapse show">
                    <div class="card-body">
                        <div class="row">
                            <h5><strong><?= $client_name; ?></strong></h5>
                            <?php
                                    if (!empty($location_phone)) { ?>
                            <div class="mt-1">
                                <i class="fa fa-fw fa-phone text-secondary ml-1 mr-2 mb-2"></i><?= $location_phone; ?>
                            </div>
                            <?php } ?>

                        <?php if (!empty($contact_id)) { ?>

                            <!-- Contact table to replace card -->
                            <table class="table table-sm table-borderless table-striped table-hover table-responsive-md">
                                <thead>
                                    <tr>
                                        <th>Contact</th>
                                        <th><a class="loadModalContentBtn" href="#" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="ticket_edit_contact_modal.php?ticket_id=<?= $ticket_id; ?>">Edit</a></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Name:</td>
                                        <td><?= $contact_name; ?>
                                    </td>
                                    </tr>
                                    <?php if (!empty($location_name)) { ?>
                                    <tr>
                                        <td>Location:</td>
                                        <td><?= $location_name; ?></td>
                                    </tr>
                                    <?php }
                                    if (!empty($contact_email)) { ?>
                                    <tr>
                                        <td>Email:</td>
                                        <td><a href="mailto:<?= $contact_email; ?>"><?= $contact_email; ?></a></td>
                                    </tr>
                                    <?php }
                                    if (!empty($contact_phone)) { ?>
                                    <tr>
                                        <td>Phone:</td>
                                        <td><a href="tel:<?= $contact_phone; ?>"><?= $contact_phone; ?></a></td>
                                    </tr>
                                    <?php }
                                    if (!empty($contact_mobile)) { ?>
                                    <tr>
                                        <td>Mobile:</td>
                                        <td><a href="tel:<?= $contact_mobile; ?>"><?= $contact_mobile; ?></a></td>
                                    </tr>
                                    <?php } ?>
                                    <?php
                                    // Previous tickets
                                    $prev_ticket_id = false; //TODO: Add previous ticket ID to ticket table
                                    ?>
                                    <?php if ($prev_ticket_id) { ?>
                                    <tr>
                                        <td>Previous Ticket:</td>
                                        <td>
                                                <div class="row">
                                                    <div class="col-6 col-md-12">
                                                        <a href="/public/?page=ticket&ticket_id=<?= $prev_ticket_id; ?>" title="View Ticket #<?= $prev_ticket_id; ?>">
                                                            <?= $prev_ticket_subject; ?>
                                                        </a>
                                                    </div>
                                                    <div class="col-6 col-md-12">
                                                        <strong>Status:</strong> <?= $prev_ticket_status; ?>
                                                        <br>
                                                        <strong>Assigned to:</strong> <?= $prev_ticket_assigned_to; ?>
                                                    </div>
                                                </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                            <!-- End Contact table -->

                        <?php } ?>

                        </div>
                        <!-- End Client row -->
                            <div class="row small">
                                <table class="me-2 table table-sm table-borderless table-striped table-hover table-responsive-md">
                                    <thead>
                                        <tr>
                                            <th>Details</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Priority:</td>
                                            <td><span class="badge rounded-pill bg-label-secondary"><?= $ticket_priority; ?></span></td>
                                        </tr>
                                        <tr>
                                            <td>Status:</td>
                                            <td><span class="badge rounded-pill bg-label-secondary"><?= $ticket_status; ?></span></td>
                                        </tr>
                                        <tr>
                                            <td>Billable:</td>
                                            <td>
                                                <a href="/post.php?ticket_<?php if($ticket_billable == 1){?>un<?php }?>billable=<?= $ticket_id; ?>">
                                                    <?php if ($ticket_billable == 1) { ?>
                                                        <span class="badge rounded-pill bg-label-success p-2">$</span>
                                                    <?php } else { ?>
                                                        <span class="badge rounded-pill bg-label-secondary p-2">X</span>
                                                    <?php } ?>
                                                </a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Time Tracked:</td>
                                            <td><span class="badge rounded-pill bg-label-secondary"><?= $ticket_total_reply_time; ?></span></td>
                                            
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
                                                    <div class="progress-bar progress-bar-striped bg-primary" role="progressbar" style="width: <?= $tasks_completed_percent_display; ?>%;" aria-valuenow="<?= $tasks_completed_percent_display; ?>" aria-valuemin="0" aria-valuemax="100"><?= $tasks_completed_percent; ?>%</div>
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
                                                
                                                <a class="loadModalContentBtn" href="#" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="trip_add_modal.php?client_id=<?= $client_id; ?>">
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
                                                    <a class="loadModalContentBtn" href="#" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="ticket_add_collaborator_modal.php?ticket_id=<?= $ticket_id; ?>">
                                                        <span class="badge rounded-pill bg-label-secondary">Add a Collaborator</span>
                                                    </a>
                                                    <?php
                                                } else {
                                                    ?>
                                                    <span class="badge rounded-pill bg-label-secondary">
                                                    <?php
                                                    foreach ($ticket_collaborators as $collaborator) {
                                                        echo $collaborator;
                                                    }
                                                    ?>
                                                    </span>
                                                    <?php
                                                }
                                                ?>

                                            
                                            </td>
                                        <tr>
                                            <td>Created:</td>
                                            <td><span class="badge rounded-pill bg-label-secondary"><?= $ticket_created_at; ?></span></td>
                                        </tr>
                                        <tr>
                                            <td>Updated:</td>
                                            <td><span class="badge rounded-pill bg-label-secondary"><?= $ticket_updated_at; ?></span></td>
                                        </tr>
                                        <tr>
                                            <td>Scheduled:</td>
                                            <td>
                                                <a class="loadModalContentBtn" href="#" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="ticket_edit_schedule_modal.php?ticket_id=<?= $ticket_id; ?>">
                                                    <span class="badge rounded-pill bg-label-secondary"><?= $ticket_schedule ? $ticket_schedule : 'Add Schedule'; ?></span>
                                                </a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Assigned to:</td>
                                            <td><a class="loadModalContentBtn" href="#" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="ticket_assign_modal.php?ticket_id=<?= $ticket_id; ?>">
                                                <span class="badge rounded-pill bg-label-secondary"><?= $ticket_assigned_to; ?></span></a>
                                            </td>
                                        </tr>
                                        <?php if (empty($contact_id)) { ?> 
                                            <tr>
                                                <td>Contact:</td>
                                                <td>
                                                    <a class="loadModalContentBtn" href="#" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="ticket_edit_contact_modal.php?ticket_id=<?= $ticket_id; ?>">
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
                                            <?php if (empty($ticket_watcher_row))
                                            {
                                                ?>
                                                <a class="loadModalContentBtn" href="#" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="ticket_add_watcher_modal.php?ticket_id=<?= $ticket_id; ?>">
                                                    <span class="badge rounded-pill bg-label-secondary">Add a Watcher</span>
                                                </a>
                                                <?php
                                            }
                                            else
                                            {
                                                echo $ticket_watcher_row;
                                            }
                                            ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Asset:</td>
                                            <td>
                                            <?php if (empty($asset_id))
                                            {
                                                ?>
                                                <a class="loadModalContentBtn" href="#" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="ticket_edit_asset_modal.php?ticket_id=<?= $ticket_id; ?>">
                                                    <span class="badge rounded-pill bg-label-secondary">Add an Asset</span>
                                                </a>
                                                <?php
                                            }
                                            else
                                            {
                                                echo $asset_name;
                                            }
                                            ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Vendor:</td>
                                            <td>
                                            <?php if (empty($vendor_id))
                                            {
                                                ?>
                                                <a class="loadModalContentBtn" href="#" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="ticket_edit_vendor_modal.php?ticket_id=<?= $ticket_id; ?>">
                                                    <span class="badge rounded-pill bg-label-secondary">Add a Vendor</span>
                                                </a>
                                                <?php
                                            }
                                            else
                                            {
                                                echo $vendor_name;
                                            }
                                            ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Products:</td>
                                            <td>
                                            <?php if (empty($ticket_products_display))
                                            {
                                                ?>
                                                <a class="loadModalContentBtn" href="#" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="ticket_add_product_modal.php?ticket_id=<?= $ticket_id; ?>">
                                                    <span class="badge rounded-pill bg-label-secondary">Manage Products</span>
                                                </a>
                                                <?php
                                            }
                                            else
                                            {
                                                echo $ticket_products_display;
                                            }
                                            ?>
                                            </td>
                                        </tr>
                                        <!-- Ticket closure info -->
                                        <?php if ($ticket_status == "Closed") {
                                            ?>
                                            <tr>
                                                <td>Feedback:</td>
                                                <td><?= $ticket_feedback; ?></td>
                                            </tr>
                                        <?php } ?>
                                        <!-- END Ticket closure info -->
                                    </tbody>
                                </table>

                                                    <!-- Ticket Actions -->
                            <?php
                            if ($ticket_status_id != 5) {
                                $close_ticket_button = true;
                            }
                            if ($ticket_billable) {
                                $invoice_ticket_button = true;
                            }
                            if ($ticket_invoice_id > 0) {
                                $invoice_ticket_button = false;
                            }

                            if ($close_ticket_button || $invoice_ticket_button) {
                        ?>
                            <div class="mt-3">
                                <div class="row">
                                    <?php if (isset($invoice_ticket_button)) { ?>
                                        <div class="col">
                                            <a href="#" class="btn btn-primary btn-block mb-3 loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="ticket_invoice_add_modal.php?ticket_id=<?= $ticket_id; ?>&ticket_total_reply_time=<?= $ticket_total_reply_time; ?>">
                                                <i class="fas fa-fw fa-file-invoice mr-2"></i>Invoice Ticket
                                            </a>
                                        </div>
                                    <?php } ?>
                                    <?php if (isset($close_ticket_button)) { ?>
                                        <div class="col">
                                            <a href="/post.php?close_ticket=<?= $ticket_id; ?>" class="btn btn-secondary btn-block confirm-link" id="ticket_close">
                                                <i class="fas fa-fw fa-gavel mr-2"></i>Close Ticket
                                            </a>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                            <?php } ?>
                        <!-- End Ticket Actions -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>