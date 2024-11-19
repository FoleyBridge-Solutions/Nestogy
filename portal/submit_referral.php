<?php
require_once "/var/www/nestogy/includes/functions/functions.php";
require_once "/var/www/nestogy/includes/config/config.php";


$client_id = $_SESSION['client_id'];

$sender_name = $_POST['sender_name'];
$firstName = $_POST['firstName'];
$lastName = $_POST['lastName'];
$emailAddress = $_POST['emailAddress'];
$message = $_POST['message'];



$data = array(
[
    'from' => 'noreply@twe.tech',
    'from_name' => 'TWE Support',
    'recipient' => $emailAddress,
    'recipient_name' => $firstName . ' ' . $lastName,
    'subject' => 'Technical Support Company Referral from ' . $sender_name,
    'body' => $message
]
);

// Send email using function
// Add an email to the queue
addToMailQueue($mysqli, $data);

echo json_encode(array('status' => 'success', 'message' => 'Referral sent successfully!'));
?>