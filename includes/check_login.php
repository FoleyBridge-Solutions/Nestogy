<?php

if (!isset($_SESSION)) {
    // HTTP Only cookies
    ini_set("session.cookie_httponly", true);
    if ($config_https_only) {
        // Tell client to only send cookie(s) over HTTPS
        ini_set("session.cookie_secure", true);
    }
    session_start();
}


//Check to see if setup is enabled
if (!isset($config_enable_setup) || $config_enable_setup == 1) {
    echo "Setup is enabled, please disable setup in the config.php file to continue.";
    exit;
}


// Check user is logged in with a valid session
if (!isset($_SESSION['logged']) || !$_SESSION['logged']) {
    if ($_SERVER["REQUEST_URI"] == "/") {
        header("Location: /pages/login.php");
    } else {
        header("Location: /pages/login.php?last_visited=" . base64_encode($_SERVER["REQUEST_URI"]) );
    }
    exit;
}

// User IP & UA
$ip = sanitizeInput(getIP());
$user_agent = sanitizeInput($_SERVER['HTTP_USER_AGENT']);

$user_id = intval($_SESSION['user_id']);

$sql = mysqli_query($mysqli, "SELECT * FROM users, user_settings WHERE users.user_id = user_settings.user_id AND users.user_id = $user_id");
$row = mysqli_fetch_array($sql);
$name = sanitizeInput($row['user_name']);
$email = $row['user_email'];
$avatar = $row['user_avatar'];
$token = $row['user_token'];
$user_role = intval($row['user_role']);
if ($user_role == 3) {
    $user_role_display = "Administrator";
} elseif ($user_role == 2) {
    $user_role_display = "Technician";
} else {
    $user_role_display = "Accountant";
}
$user_config_force_mfa = intval($row['user_config_force_mfa']);
$user_config_records_per_page = intval($row['user_config_records_per_page']);

$sql = mysqli_query($mysqli, "SELECT * FROM companies, settings WHERE settings.company_id = companies.company_id AND companies.company_id = 1");
$row = mysqli_fetch_array($sql);

$company_name = $row['company_name'];
$company_country = $row['company_country'];
$company_locale = $row['company_locale'];
$company_currency = $row['company_currency'];
$timezone = $row['config_timezone'];

// Set Timezone to the companies timezone
// 2024-02-08 JQ - The option to set the timezone in PHP was disabled to prevent inconsistencies with MariaDB/MySQL, which utilize the system's timezone, It is now consdered best practice to set the timezone on system itself
//date_default_timezone_set($timezone);

// 2024-03-21 JQ - Re-Enabled Timezone setting as new PHP update does not respect System Time but defaulted to UTC
date_default_timezone_set($timezone);

//Set Currency Format
$currency_format = numfmt_create($company_locale, NumberFormatter::CURRENCY);

require_once "/var/www/portal.twe.tech/includes/get_settings.php";


//Detects if using an Apple device and uses Apple Maps instead of google
$iPod = stripos($_SERVER['HTTP_USER_AGENT'], "iPod");
$iPhone = stripos($_SERVER['HTTP_USER_AGENT'], "iPhone");
$iPad = stripos($_SERVER['HTTP_USER_AGENT'], "iPad");

if ($iPod || $iPhone || $iPad) {
    $map_source = "apple";
} else {
    $map_source = "google";
}

//Check if mobile device
$mobile = isMobile();

//Get Notification Count for the badge on the top nav
$row = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT COUNT('notification_id') AS num FROM notifications WHERE (notification_user_id = $user_id OR notification_user_id = 0) AND notification_dismissed_at IS NULL"));
$num_notifications = $row['num'];

