<?php
declare(strict_types=1);
date_default_timezone_set('America/Argentina/Buenos_Aires');

if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
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
        http_response_code(403);
        ui_error('La sesión del formulario venció. Volvé atrás y reintentá.');
    }
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_client(): array
{
    return require_user(['Cliente']);
}

function role_destination(string $role): string
{
    return match ($role) {
        'Cliente' => '/Inmobiliaria/cliente/index.php',
        'Administrador' => '/Inmobiliaria/administrador.php',
        'Agente' => '/Inmobiliaria/agente.php',
        default => '/Inmobiliaria/index.php',
    };
}

function require_user(array $roles = ['Cliente','Agente','Administrador']): array
{
    $user = current_user();
    if (!$user) redirect('/Inmobiliaria/login.php');
    $statement = Database::connection()->prepare('SELECT u.activo,u.password_hash,r.nombre AS rol FROM usuarios u JOIN roles r ON r.id_rol=u.id_rol WHERE u.id_usuario=?');
    $statement->execute([$user['id']]);
    $record = $statement->fetch();
    if (!$record || !$record['activo'] || !hash_equals(hash('sha256', $record['password_hash']), (string)($_SESSION['auth_fingerprint'] ?? ''))) { unset($_SESSION['user']); redirect('/Inmobiliaria/login.php'); }
    $_SESSION['user']['role'] = $record['rol'];
    $user = $_SESSION['user'];
    if (!in_array($user['role'], $roles, true)) { http_response_code(403); ui_error('No tenés permisos para acceder a esta sección.'); }
    return $user;
}
function query_rows(string $sql, array $params = []): array
{
    $q=Database::connection()->prepare($sql); $q->execute($params); return $q->fetchAll();
}
function execute_sql(string $sql, array $params = []): void
{
    $q=Database::connection()->prepare($sql); $q->execute($params);
}
function csrf_field(): void { echo '<input type="hidden" name="csrf_token" value="'.e(csrf_token()).'">'; }
function flash(string $message): void { $_SESSION['notice']=$message; }
function notice(): void {
    if (isset($_SESSION['notice'])) { echo '<div class="notice" role="status">'.e($_SESSION['notice']).'</div>'; unset($_SESSION['notice']); }
}


function ui_error(string $message): never
{
    $pageTitle = $message;
    require __DIR__ . '/../Vistas/Diseno/encabezado.php';
    echo '<section class="section"><p role="alert">' . e($message) . '</p></section>';
    require __DIR__ . '/../Vistas/Diseno/pie.php';
    exit;
}
