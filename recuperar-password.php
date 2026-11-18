<?php
declare(strict_types=1);
require __DIR__.'/sistema/Nucleo/inicio.php';
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
$message = ''; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim((string)($_POST['email'] ?? ''));
    if (!Validacion::email($email)) {
        $error = 'Ingresá un correo válido con formato nombre@dominio.com.';
    } else {
        try {
            $mailer = new CorreoRecuperacion(require __DIR__.'/configuracion/correo.php');
            $mailer->comprobar();
            (new RecuperacionDAO(Database::connection()))->solicitar($email, $_SERVER['REMOTE_ADDR'] ?? 'local',
                static fn(string $to, string $token) => $mailer->enviar($to, $token, ($_COOKIE['inmo_lang'] ?? 'es') === 'en' ? 'en' : 'es'));
            $message = 'Si el correo corresponde a una cuenta activa, recibirás un enlace para cambiar tu contraseña. Revisá también la carpeta de spam.';
        } catch (Throwable $exception) {
            error_log('Recuperación no disponible: ' . get_class($exception) . ' — ' . $exception->getMessage());
            $error = 'No pudimos procesar la solicitud. Intentá nuevamente más tarde.';
        }
    }
}
$pageTitle = 'Recuperar contraseña';
require __DIR__.'/sistema/Vistas/Diseno/encabezado.php';
?>
<section class="section" style="max-width:600px;margin:40px auto;padding:20px">
<div class="content-card" style="padding:28px">
<h1>Recuperar contraseña</h1>
<p>Ingresá el correo de tu cuenta. Te enviaremos un enlace válido por 30 minutos.</p>
<?php if ($message): ?><p class="notice" role="status"><?= e($message) ?></p><?php else: ?>
<?php if ($error): ?><p class="field-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post" class="modal-form">
<?php csrf_field(); ?>
<div class="field"><label for="email">Correo electrónico</label><input id="email" name="email" type="email" maxlength="150" autocomplete="email" required value="<?= e((string)($_POST['email'] ?? '')) ?>"></div>
<button class="btn" type="submit">Enviar enlace de recuperación</button>
</form>
<?php endif; ?>
<p><a class="text-link" href="/Inmobiliaria/login.php">Volver a iniciar sesión</a></p>
</div></section>
<?php require __DIR__.'/sistema/Vistas/Diseno/pie.php'; ?>
