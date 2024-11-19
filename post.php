<?php

/*
 * ITFlow - Main GET/POST request handler
 */

require_once "/var/www/nestogy/includes/tenant_db.php";

require_once "/var/www/nestogy/includes/config/config.php";

require_once "/var/www/nestogy/includes/functions/functions.php";

require_once "/var/www/nestogy/includes/check_login.php";

requireOnceAll("/var/www/nestogy/includes/post");

echo "<pre>";
print_r($_POST);
echo "</pre>";



?>
