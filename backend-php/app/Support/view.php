<?php

declare(strict_types=1);

function view(string $template, array $data = []): void
{
    $basePath = dirname(__DIR__) . '/Views/';
    $file = $basePath . str_replace('.', '/', $template) . '.php';

    if (!is_file($file)) {
        http_response_code(500);
        echo 'View not found: ' . htmlspecialchars($template, ENT_QUOTES, 'UTF-8');
        return;
    }

    extract($data, EXTR_SKIP);
    require $file;
}
