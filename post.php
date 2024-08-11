<?php

/*
 * ITFlow - Main GET/POST request handler
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

error_log("post.php");

error_log("post var_dump: " . var_dump($_POST));


require_once "/var/www/portal.twe.tech/includes/tenant_db.php";

require_once "/var/www/portal.twe.tech/includes/config/config.php";

require_once "/var/www/portal.twe.tech/includes/functions/functions.php";

require_once "/var/www/portal.twe.tech/includes/check_login.php";


requireOnceAll("/var/www/portal.twe.tech/includes/post");



?>
