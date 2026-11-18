<?php
declare(strict_types=1);
$config = [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => getenv('INMO_SMTP_USER') ?: '',
    'password' => getenv('INMO_SMTP_PASSWORD') ?: '',
    'from_name' => 'Inmobiliaria Nuevo Hogar',
    'base_url' => getenv('INMO_PUBLIC_URL') ?: '',
];
$local = __DIR__ . '/correo.local.php';
return is_file($local) ? array_replace($config, require $local) : $config;
