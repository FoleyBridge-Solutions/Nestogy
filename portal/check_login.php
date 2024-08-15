<?php

/*
 * Client Portal
 * Checks if the client is logged in or not
 */

if (!isset($_SESSION)) {
    // HTTP Only cookies
    ini_set("session.cookie_httponly", true);
    if ($config_https_only) {
        // Tell client to only send cookie(s) over HTTPS
        ini_set("session.cookie_secure", true);
    }
    session_start();
}

if (!isset($_SESSION['client_logged_in']) || !$_SESSION['client_logged_in']) {
    header("Location: /old_pages/login.php");
    die;
}

// User IP & UA
$ip = sanitizeInput(getIP());
$user_agent = sanitizeInput($_SERVER['HTTP_USER_AGENT']);


// Get info from session
$client_id = intval($_SESSION['client_id']);
$contact_id = intval($_SESSION['contact_id']);


// Get company info from database
$sql = mysqli_query($mysqli, "SELECT * FROM companies WHERE company_id = 1");
$row = mysqli_fetch_array($sql);

$company_name = $row['company_name'];
$company_country = $row['company_country'];
$company_locale = $row['company_locale'];
$company_currency = $row['company_currency'];
$currency_format = numfmt_create($company_locale, NumberFormatter::CURRENCY);


// Get contact info
$contact_sql = mysqli_query($mysqli, "SELECT * FROM contacts WHERE contact_id = $contact_id AND contact_client_id = $client_id");
$contact = mysqli_fetch_array($contact_sql);

$contact_name = sanitizeInput($contact['contact_name']);
$contact_initials = initials($contact_name);
$contact_title = sanitizeInput($contact['contact_title']);
$contact_email = sanitizeInput($contact['contact_email']);
$contact_photo = sanitizeInput($contact['contact_photo']);
$contact_pin = sanitizeInput($contact['contact_pin']);
$contact_primary = intval($contact['contact_primary']);

$contact_is_technical_contact = false;
$contact_is_billing_contact = false;
if ($contact['contact_technical'] == 1) {
    $contact_is_technical_contact = true;
}
if ($contact['contact_billing'] == 1) {
    $contact_is_billing_contact = true;
}

// Get client info
$client_sql = mysqli_query($mysqli, "SELECT * FROM clients WHERE client_id = $client_id");
$client = mysqli_fetch_array($client_sql);

$client_name = $client['client_name'];
