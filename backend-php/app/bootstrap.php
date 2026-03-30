<?php

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

require __DIR__ . '/Support/view.php';
require __DIR__ . '/Support/slug.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$config = require dirname(__DIR__) . '/config/app.php';
date_default_timezone_set($config['timezone']);
