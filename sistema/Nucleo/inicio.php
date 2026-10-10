<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

spl_autoload_register(static function (string $class): void {
    $folders = ['Nucleo', 'DAOs', 'Controladores'];
    foreach ($folders as $folder) {
        $file = __DIR__ . '/../' . $folder . '/' . $class . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('La sesión del formulario venció. Volvé atrás y reintentá.');
    }
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_client(): array
{
    $user = current_user();
    if (!$user) {
        redirect('/Inmobiliaria/login.php');
    }
    if (($user['role'] ?? '') !== 'Cliente') {
        http_response_code(403);
        exit('Esta sección es exclusiva para clientes.');
    }
    return $user;
}

function role_destination(string $role): string
{
    if ($role === 'Cliente') return '/Inmobiliaria/cliente/index.php';
    if (in_array($role, ['Agente', 'Administrador'], true)) {
        $_SESSION['role_notice'] = $role;
        return '/Inmobiliaria/index.php?ver_rol=1';
    }
    return '/Inmobiliaria/index.php';
}

