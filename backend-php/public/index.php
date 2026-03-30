<?php

declare(strict_types=1);

use App\Core\Router;

$root = dirname(__DIR__);

require $root . '/app/bootstrap.php';

$router = new Router();

(require $root . '/routes/web.php')($router);
(require $root . '/routes/api.php')($router);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
