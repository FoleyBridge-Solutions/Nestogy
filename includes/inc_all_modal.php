<?php

use Twetech\Nestogy\Database; 

require_once "/var/www/nestogy/includes/config/config.php";

require_once "/var/www/nestogy/includes/functions/functions.php";

require_once "/var/www/nestogy/includes/check_login.php";

$domain = $_SERVER['HTTP_HOST'];
$config = require "/var/www/nestogy/config/$domain/config.php";


$database = new Database($config['db']);
$pdo = $database->getConnection();


?>
