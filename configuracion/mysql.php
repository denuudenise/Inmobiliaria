<?php
declare(strict_types=1);

$config = [
    'host' => getenv('INMOBILIARIA_DB_HOST') ?: '127.0.0.1',
    'port' => getenv('INMOBILIARIA_DB_PORT') ?: '3306',
    'database' => getenv('INMOBILIARIA_DB_NAME') ?: 'inmobiliaria',
    'username' => getenv('INMOBILIARIA_DB_USER') ?: 'root',
    'password' => getenv('INMOBILIARIA_DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
];

$local = __DIR__ . '/mysql.local.php';
if (is_file($local)) {
    $localConfig = require $local;
    if (!is_array($localConfig)) {
        throw new RuntimeException('La configuración local de MySQL debe devolver un array.');
    }
    $config = array_replace($config, $localConfig);
}

return $config;

