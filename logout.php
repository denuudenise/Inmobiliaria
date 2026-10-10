<?php
declare(strict_types=1);
require __DIR__ . '/sistema/Nucleo/inicio.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/Inmobiliaria/index.php');
verify_csrf();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
redirect('/Inmobiliaria/index.php');

