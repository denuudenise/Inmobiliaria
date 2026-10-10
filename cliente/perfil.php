<?php
declare(strict_types=1);
require dirname(__DIR__) . '/sistema/Nucleo/inicio.php';
$user = require_client();
$errorMessage = '';
$successMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string)($_POST['nombre'] ?? ''));
    $surname = trim((string)($_POST['apellido'] ?? ''));
    $phone = trim((string)($_POST['telefono'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    if ($name === '' || $surname === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Revisá el nombre, apellido y correo electrónico.';
    } else {
        try {
            (new UsuarioDAO(Database::connection()))->updateProfile((int)$user['id'], $name, $surname, $phone, $email);
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['surname'] = $surname;
            $_SESSION['user']['phone'] = $phone;
            $_SESSION['user']['email'] = $email;
            $user = current_user();
            $successMessage = 'Tus datos se actualizaron correctamente.';
        } catch (PDOException $error) {
            error_log('No se pudo actualizar el perfil: ' . $error->getMessage());
            $errorMessage = (string)$error->getCode() === '23000' ? 'Ese correo ya está asociado a otra cuenta.' : 'No se pudo guardar el perfil.';
        }
    }
}
$pageTitle = 'Mi perfil';
$activePage = 'perfil';
require dirname(__DIR__) . '/sistema/Vistas/Diseno/encabezado.php';
?>
<section class="dashboard"><div class="dashboard-title"><div><h1>Mi perfil</h1><p>Administrá tus datos personales y de contacto.</p></div></div><div class="dash-layout"><?php require dirname(__DIR__) . '/sistema/Vistas/Cliente/navegacion.php'; ?><div class="content-card"><h2>Datos de tu cuenta</h2><p class="content-subtitle">Esta información nos ayuda a brindarte una mejor atención.</p>
  <?php if ($errorMessage): ?><div role="alert" class="not-implemented"><?= e($errorMessage) ?></div><?php endif; ?><?php if ($successMessage): ?><div class="hint-box" role="status"><?= e($successMessage) ?></div><?php endif; ?>
  <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="data-grid" style="margin-top:14px"><div class="data-box"><h3>Datos personales</h3><div class="form-grid"><div class="field"><label for="nombre">Nombre *</label><input id="nombre" name="nombre" value="<?= e($user['name']) ?>" required></div><div class="field"><label for="apellido">Apellido *</label><input id="apellido" name="apellido" value="<?= e($user['surname']) ?>" required></div><div class="field"><label>DNI</label><input value="<?= e($user['dni']) ?>" disabled></div><div class="field"><label for="telefono">Teléfono</label><input id="telefono" name="telefono" value="<?= e($user['phone']) ?>"></div><div class="field full"><label for="email">Correo electrónico *</label><input id="email" name="email" type="email" value="<?= e($user['email']) ?>" required></div></div></div><div class="data-box"><h3>Seguridad</h3><p style="color:var(--muted);line-height:1.6">El DNI se utiliza para identificar tu cuenta y no se puede modificar desde el perfil. Para actualizar tu contraseña, contactá al equipo de Nuevo Hogar.</p><div class="hint-box">Tus datos se almacenan de forma segura en tu cuenta.</div></div></div><button class="btn" style="margin-top:18px" type="submit">Guardar cambios</button></form>
</div></div></section>
<?php require dirname(__DIR__) . '/sistema/Vistas/Diseno/pie.php'; ?>

