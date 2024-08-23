<?php

$total_tickets_open = $support_header_numbers['open_tickets'];
$total_tickets_closed = $support_header_numbers['closed_tickets'];
$total_tickets_unassigned = $support_header_numbers['unassigned_tickets'];
$total_scheduled_tickets = $support_header_numbers['scheduled_tickets'];

$mobile = false;

function time_elapsed_string($datetime, $full = false) {

    if ($datetime == 'N/A') {
        return $datetime;
    }
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $units = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];

    // Calculate weeks manually
    $weeks = floor($diff->d / 7);
    $diff->d -= $weeks * 7;

    $string = [];
    foreach ($units as $k => $v) {
        if ($k == 'w' && $weeks) {
            $string[] = $weeks . ' ' . $v . ($weeks > 1 ? 's' : '');
        } elseif ($k != 'w' && $diff->$k) {
            $string[] = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
$ticket_type = isset($_GET['ticket_type']) ? $_GET['ticket_type'] : 'support';

?>

<div class="card">
    <div class="card-header header-elements">
        <h3 class="me-2">
            <form method="get">
                <?php
                foreach ($_GET as $key => $value) {
                    if ($key != 'ticket_type') {
                        echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                    }
                }
                ?>
                <select name="ticket_type" onchange="this.form.submit()">
                    <option <?= $ticket_type == 'support' ? 'selected' : '' ?> value="support">Support</option>
                    <option <?= $ticket_type == 'alert' ? 'selected' : '' ?> value="alert">Alert</option>
                    <option <?= $ticket_type == 'accounting' ? 'selected' : '' ?> value="accounting">Accounting</option>
                </select>
                Tickets
            </form>
        </h3>
        <div class="card-header-elements">
            <span class="badge rounded-pill bg-label-secondary p-2">Total: <?=$total_tickets_open + $total_tickets_closed?></span> |
            <a href="/public/?page=tickets<?php if(isset($client_id)) echo "&client_id=$client_id"; ?>" class="badge rounded-pill bg-label-primary p-2">
                Open: <?=$total_tickets_open?>
            </a> |
            <a href="/public/?page=tickets&status=5<?php if(isset($client_id)) echo "&client_id=$client_id"; ?>" class="badge rounded-pill bg-label-danger p-2">
                Closed: <?=$total_tickets_closed?>
            </a>
        </div>
        <div class="card-header-elements ms-auto">
            <div class="btn-group">
                <div class="btn-group" role="group">
                    <button class="btn btn-label-dark dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown">
                        <?=$mobile ? "" : "My Tickets"?>
                        <i class="fa fa-fw fa-envelope m-2"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="?page=tickets&user_id=<?= $user_id ?>">Active tickets</a>
                        <a class="dropdown-item " href="?page=tickets&status=5&user_id=<?= $user_id ?>">Closed tickets</a>
                    </div>
                </div>
                <?php if (!isset($_GET['client_id'])) { ?>
                    <a href="?assigned=unassigned" class="btn btn-label-danger">
                        <strong>Unassigned:</strong>
                        <span class="tf-icons fa fa-fw fa-exclamation-triangle mr-2"></span>
                    </a>
                <?php } ?>
                <a href="#!" class="btn btn-label-secondary loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="ticket_add_modal.php<?php if(isset($client_id)) echo "?client_id=$client_id"; ?>">
                    Add Ticket
                    <i class="fa fa-fw fa-plus mr-2"></i>
                </a>

            </div>

        </div>
    </div>

    <div class="card-body">
        <form id="bulkActions" action="/post/" method="post">
            <div class="card-datatable table-responsive">
                <table class="datatables-basic table border-top">
                    <thead class="text-dark">
                        <tr>
                            <?php
                            // table head
                            if (!$mobile) {
                                $rows = [ 'Number', 'Subject', 'Client / Contact', 'Priority', 'Status', 'Assigned', 'Last Response', 'Created' ];
                                $datatable_order = "[[7,'desc']]";
                                $datatable_priority = [
                                    'Number' => 1,
                                    'Subject' => 2,
                                    'Assigned' => 3
                                ];

                            } else {
                                $rows = [ 'Subject', 'Client', 'Number', 'Status', 'Assigned', 'Last Response', 'Created' ];
                                $datatable_order = "[[6,'desc']]";
                                $datatable_priority = [
                                    'Status' => 1,
                                    'Number' => 3,
                                    'Subject' => 2,
                                    'Assigned' => 4,
                                    'Client' => 5,
                                    'Last Response' => 6
                                ];
                            }
                            
                                $rows[] = 'Billable';

                            // Add actions to the end of the table
                            $rows[] = 'Actions';

                            foreach ($rows as $row) {
                                if (isset($datatable_priority[$row])) {
                                    echo "<th data-priority='" . $datatable_priority[$row] . "'>$row</th>";
                                } else {
                                    echo "<th>$row</th>";
                                }
                            }
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // table body
                        foreach ($tickets as $ticket) {
                            $ticket_id = $ticket['ticket_id'];
                            $client_name = $ticket['client_name'];
                            $contact_name = $ticket['contact_name'];
                            $subject = $ticket['ticket_subject'];
                            $priority = $ticket['ticket_priority'];
                            $ticket_status = $ticket['ticket_status_name'];
                            $status_color = $ticket['ticket_status_color'];
                            $assigned_id = $ticket['ticket_assigned_to'];
                            $assigned = $ticket['user_name'];
                            $created = $ticket['ticket_created_at'];
                            $billable = $ticket['ticket_billable'];
                            $last_response = $ticket['ticket_last_response'];

                            if ($billable == 1) {
                                $billable = "<a href='/post.php?ticket_unbillable=$ticket_id' class='badge rounded-pill bg-label-success' data-bs-toggle='tooltip' data-bs-placement='top' title='Mark ticket as unbillable'>$</a>";
                            } else {
                                $billable = "<a href='/post.php?ticket_billable=$ticket_id' class='badge rounded-pill bg-label-danger' data-bs-toggle='tooltip' data-bs-placement='top' title='Mark ticket as billable'>X</a>";
                            }
                            $ticket_priority = $priority == 1 ? 'High' : ($priority == 2 ? 'Medium' : 'Low');
                            $ticket_priority_color = $priority == 1 ? 'danger' : ($priority == 2 ? 'warning' : 'success');

                            $ticket_last_response = $last_response ? date('Y-m-d H:i', strtotime($last_response)) : 'N/A';
                            $ticket_created = date('Y-m-d H:i', strtotime($created));

                            $ticket_subject = $subject;

                            $ticket_number = $ticket['ticket_number'];

                            $ticket_assigned = $assigned_id == 0 ? 'Unassigned' : $assigned;
                            $ticket_assigned_color = $assigned_id == 0 ? 'secondary' : 'primary';

                            $ticket_actions = [
                                'View' => [
                                    'modal' => false,
                                    'icon' => 'fa-eye',
                                    'url' => '/public/?page=ticket&action=show&ticket_id=' . $ticket_id
                                ],
                                'Edit' => [
                                    'modal' => true,
                                    'icon' => 'fa-edit',
                                    'modal_file' => 'ticket_edit_modal.php?ticket_id=' . $ticket_id
                                
                                ],
                                'Change Client' => [
                                    'modal' => true,
                                    'icon' => 'fa-exchange-alt',
                                    'modal_file' => 'ticket_edit_client_modal.php?ticket_id=' . $ticket_id
                                ],
                                'Delete' => [
                                    'modal' => false,
                                    'icon' => 'fa-trash',
                                    'url' => "/post.php?delete_ticket=".$ticket_id
                                ]
                            ];
                        ?>

                            <tr class="<?= empty($ticket_updated_at) ? "text-bold" : "" ?>">
                                <td data-order="<?= $ticket_number; ?>">
                                    <small>
                                        <a href="/public/?page=ticket&action=show&ticket_id=<?=$ticket_id?>" data-bs-toggle="tooltip" data-bs-placement="top" title="View ticket">
                                            <span class="badge rounded-pill bg-label-secondary p-3"><?=$ticket_number?></span>
                                        </a>
                                    </small>
                                </td>
                                <td data-order="<?= $ticket_subject; ?>">
                                    <a href="/public/?page=ticket&action=show&ticket_id=<?=$ticket_id?>" data-bs-toggle="tooltip" data-bs-placement="top" title="View ticket">
                                        <?=$ticket_subject?>
                                    </a>
                                </td>
                                <td data-order="<?= $client_name; ?>">
                                    <a href="/public/?page=tickets&client_id=<?=$ticket['client_id']?>" data-bs-toggle="tooltip" data-bs-placement="top" title="View client tickets">
                                        <?=$client_name?>
                                    </a>
                                    <br>
                                    <small>
                                        <a href="/public/?page=contact&action=show&contact_id=<?=$ticket['client_id']?>" data-bs-toggle="tooltip" data-bs-placement="top" title="View contact">
                                            <?php if (isset($contact_name)) {
                                                echo "<span class='small badge rounded-pill bg-label-secondary'>";
                                                echo $contact_name;
                                                echo "</span>";
                                            } ?>
                                        </a>
                                    </small>
                                </td>
                                <td data-order="<?= $ticket_priority; ?>">
                                    <a href="#" class="loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="ticket_edit_priority_modal.php?ticket_id=<?= $ticket_id; ?>&client_id=<?= $ticket['client_id']; ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit ticket priority">
                                        <span class='p-2 badge rounded-pill bg-label-<?= $ticket_priority_color; ?>'>
                                            <?= $ticket_priority; ?>
                                        </span>
                                    </a>
                                </td>
                                <td data-order="<?= $ticket_status; ?>">
                                    <a href="#" class="loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="ticket_edit_status_modal.php?ticket_id=<?= $ticket_id; ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit ticket status">
                                        <span class='p-2 badge rounded-pill bg-label-<?= $status_color; ?>'>
                                            <?= $ticket_status; ?>
                                        </span>
                                    </a>
                                </td>
                                <td data-order="<?= $ticket_assigned; ?>">
                                    <a href="#" class="loadModalContentBtn" data-bs-toggle="modal" data-bs-target="#dynamicModal" data-modal-file="ticket_assign_modal.php?ticket_id=<?= $ticket_id; ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit ticket assigned">
                                        <span class='p-2 badge rounded-pill bg-label-<?= $ticket_assigned_color; ?>'>
                                            <?= $ticket_assigned; ?>
                                        </span>
                                    </a>
                                </td>
                                <td data-order="<?= $ticket_last_response; ?>">
                                    <span class="badge rounded-pill bg-label-secondary">
                                        <?= time_elapsed_string($ticket_last_response); ?>
                                    </span>
                                    <br>
                                    <small>
                                        <span class="badge rounded-pill bg-secondary">
                                            <?= $ticket_last_response; ?>
                                        </span>
                                    </small>
                                </td>
                                <td data-order="<?= $ticket_created; ?>">
                                    <span class="badge rounded-pill bg-label-secondary">
                                        <?= time_elapsed_string($ticket_created); ?>
                                    </span>
                                    <br>
                                    <small>
                                        <span class="badge rounded-pill bg-secondary">
                                            <?= date('Y-m-d H:i', strtotime($ticket_created)); ?>
                                        </span>
                                    </small>
                                </td>
                                <td data-order="<?= $billable; ?>">
                                    <?= $billable; ?>
                                </td>
                                <td data-order="<?= $ticket_id; ?>">
                                    <div class="btn-group">
                                        <button type="button" class=" btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="small fa fa-fw fa-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <?php
                                                foreach ($ticket_actions as $action => $data) {
                                                    if (isset($data['url']) && isset($action)) {

                                                        if ($data['modal']) {
                                                            echo "<a class='dropdown-item loadModalContentBtn' href='" . $data['url'] . "' data-bs-toggle='modal' data-bs-target='#dynamicModal' data-modal-file='" . $data['modal_file'] . "'>" . $action . "</a>";
                                                        } else {
                                                            echo "<a class='dropdown-item' href='" . $data['url'] . "'>" . $action . "</a>";
                                                        }
                                                    }
                                                }
                                            ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>