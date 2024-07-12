<?php

// public/index.php

require '../bootstrap.php';

use Twetech\Nestogy\Core\Router;

$router = new Router();
$router->dispatch();