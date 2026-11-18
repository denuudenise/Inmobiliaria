<?php
declare(strict_types=1);
require __DIR__.'/sistema/Nucleo/inicio.php';
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
// Remove the secret from the URL before loading any page resources.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token'])) {
    $_SESSION['reset_token'] = is_string($_GET['token']) && preg_match('/^[a-f0-9]{64}$/D', $_GET['token']) ? $_GET['token'] : '';
    redirect('/Inmobiliaria/restablecer-password.php');
}
$error = ''; $valid = false; $success = false;
$token = (string)($_SESSION['reset_token'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') verify_csrf();
try {
    $dao = new RecuperacionDAO(Database::connection());
    $valid = $dao->buscar($token) !== null;
    if ($valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = (string)($_POST['password'] ?? '');
        $confirmation = (string)($_POST['confirmation'] ?? '');
        if (!Validacion::contrasena($password)) $error = 'Usá entre 8 y 255 caracteres, con al menos una letra, un número y un símbolo.';
        elseif ($password !== $confirmation) $error = 'Las contraseñas no coinciden.';
        else {
            $success = $dao->restablecer($token, $password);
            $valid = false;
            if ($success) {
                unset($_SESSION['reset_token'], $_SESSION['user'], $_SESSION['auth_fingerprint']);
                session_regenerate_id(true);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
        }
    }
} catch (Throwable $exception) {
    error_log('No se pudo procesar un restablecimiento de contraseña.');
    $error = 'No pudimos procesar la solicitud. Intentá nuevamente más tarde.';
}
$pageTitle = 'Crear nueva contraseña';
require __DIR__.'/sistema/Vistas/Diseno/encabezado.php';
?>
<section class="section" style="max-width:600px;margin:40px auto;padding:20px">
<div class="content-card" style="padding:28px">
<h1>Crear nueva contraseña</h1>
<?php if ($success): ?>
<p class="notice" role="status">Tu contraseña fue actualizada. Ya podés iniciar sesión con la nueva contraseña.</p>
<?php elseif ($valid): ?>
<?php if ($error): ?><p class="field-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post" class="modal-form">
<?php csrf_field(); ?>
<p id="password-help">Usá entre 8 y 255 caracteres, con al menos una letra, un número y un símbolo.</p>
<div class="field"><label for="new-password">Nueva contraseña</label><input id="new-password" name="password" type="password" minlength="8" maxlength="255" autocomplete="new-password" aria-describedby="password-help" required></div>
<div class="field"><label for="confirmation">Confirmar contraseña</label><input id="confirmation" name="confirmation" type="password" minlength="8" maxlength="255" autocomplete="new-password" required></div>
<button type="submit" class="btn">Guardar nueva contraseña</button>
</form>
<?php else: ?>
<p role="alert"><?= e($error ?: 'El enlace no es válido, ya fue utilizado o venció.') ?></p>
<p><a class="text-link" href="/Inmobiliaria/recuperar-password.php">Solicitar un nuevo enlace</a></p>
<?php endif; ?>
<p><a class="text-link" href="/Inmobiliaria/login.php">Volver a iniciar sesión</a></p>
</div></section>
<?php require __DIR__.'/sistema/Vistas/Diseno/pie.php'; ?>
