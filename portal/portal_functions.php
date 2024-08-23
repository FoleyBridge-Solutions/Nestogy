<?php
/*
 * Client Portal
 * Functions
 */

/*
 * Verifies a contact has access to a particular ticket ID, and that the ticket is in the correct state (open/closed) to perform an action
 */
function verifyContactTicketAccess($requested_ticket_id, $expected_ticket_state)
{
    // Access the global variables
    global $mysqli, $contact_id, $contact_primary, $contact_is_technical_contact, $client_id;

    // Setup
    if ($expected_ticket_state == "5") {
        // Closed tickets
        $ticket_state_snippet = "ticket_status = 5";
    } else {
        // Open (working/hold) tickets
        $ticket_state_snippet = "ticket_status != 5";
    }

    // Verify the contact has access to the provided ticket ID
    $sql = "SELECT * FROM tickets WHERE ticket_id = $requested_ticket_id AND $ticket_state_snippet AND ticket_client_id = $client_id LIMIT 1";
    error_log($sql);
    $sql = mysqli_query($mysqli, $sql);
    $row = mysqli_fetch_array($sql);
    $ticket_id = $row['ticket_id'];

    if (intval($ticket_id) && ($contact_id == $row['ticket_contact_id'] || $contact_primary == 1 || $contact_is_technical_contact)) {
        // Client is ticket owner, primary contact, or a technical contact
        return true;
    }

    // Client is NOT ticket owner or primary/tech contact
    return false;
}
function sendNotification($notification_type, $notification, $action = '', $client_id = 0){
    global $mysqli;
    mysqli_query($mysqli,
        "INSERT INTO notifications SET
        notification_type = '$notification_type',
        notification = '$notification',
        notification_action = '$action',
        notification_client_id = $client_id,
        notification_is_webpush = 1"
);
}
