<?php

declare(strict_types=1);

return [
    'host' => getenv('DB_HOST') ?: 'db',
    'port' => (int) (getenv('DB_PORT') ?: 5432),
    'name' => getenv('DB_NAME') ?: 'iran_info',
    'user' => getenv('DB_USER') ?: 'iran_user',
    'pass' => getenv('DB_PASSWORD') ?: 'iran_pass',
    'charset' => 'utf8',
];
