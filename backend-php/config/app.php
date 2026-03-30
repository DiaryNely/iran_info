<?php

declare(strict_types=1);

return [
    'name' => 'Iran Info PHP',
    'env' => getenv('APP_ENV') ?: 'local',
    'debug' => (getenv('APP_DEBUG') ?: '1') === '1',
    'base_url' => getenv('APP_BASE_URL') ?: 'http://localhost:8080',
    'timezone' => getenv('APP_TIMEZONE') ?: 'Europe/Paris',
];
