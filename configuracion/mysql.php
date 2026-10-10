<?php
declare(strict_types=1);

return [
    'host' => getenv('INMOBILIARIA_DB_HOST') ?: '127.0.0.1',
    'port' => getenv('INMOBILIARIA_DB_PORT') ?: '3306',
    'database' => getenv('INMOBILIARIA_DB_NAME') ?: 'inmobiliaria',
    'username' => getenv('INMOBILIARIA_DB_USER') ?: 'root',
    'password' => getenv('INMOBILIARIA_DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
];

