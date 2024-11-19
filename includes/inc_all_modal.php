<?php

use Twetech\Nestogy\Database; 

require_once "/var/www/portal.twe.tech/includes/config/config.php";

require_once "/var/www/portal.twe.tech/includes/functions/functions.php";

require_once "/var/www/portal.twe.tech/includes/check_login.php";

$domain = $_SERVER['HTTP_HOST'];
$config = require "/var/www/portal.twe.tech/config/$domain/config.php";


$database = new Database($config['db']);
$pdo = $database->getConnection();


?>
