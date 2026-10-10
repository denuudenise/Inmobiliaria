<?php
declare(strict_types=1);
require __DIR__ . '/sistema/Nucleo/inicio.php';
if (current_user()) redirect(role_destination(current_user()['role']));
$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    try {
        $user = (new UsuarioDAO(Database::connection()))->findByEmail($email);
        if (!$user || !(bool)$user['activo'] || !password_verify($password, $user['password_hash'])) {
            $errorMessage = 'El correo o la contraseña no son correctos.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int)$user['id_usuario'],
                'name' => $user['nombre'],
                'surname' => $user['apellido'],
                'dni' => $user['dni'],
                'phone' => $user['telefono'],
                'email' => $user['email'],
                'role' => $user['rol'],
            ];
            redirect(role_destination($user['rol']));
        }
    } catch (PDOException $error) {
        error_log('Error de login/base de datos: ' . $error->getMessage());
        $errorMessage = 'No se pudo conectar con la base de datos. Revisá que MySQL esté iniciado en XAMPP y que hayas cargado base_datos/esquema.sql.';
    }
}
$pageTitle = 'Iniciar sesión';
require __DIR__ . '/sistema/Vistas/Diseno/encabezado.php';
?>
<section class="dashboard" style="max-width:1050px;margin-top:45px;margin-bottom:45px">
  <div class="content-card" style="padding:0;overflow:hidden;display:grid;grid-template-columns:1fr 1fr;min-height:540px">
    <div style="padding:48px 38px;display:flex;flex-direction:column;justify-content:flex-end;background:linear-gradient(0deg,#0e354bd9,#0e354b19),url('https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1000&q=85') center/cover;color:white">
      <span style="font-size:13px;letter-spacing:.06em;text-transform:uppercase">Inmobiliaria Nuevo Hogar</span><h1 style="color:white;font-size:39px;line-height:1.1;margin:12px 0">Tu próximo hogar<br>empieza acá.</h1><p style="max-width:340px;line-height:1.6;color:#edf3f4">Ingresá para seguir tus operaciones, guardar propiedades y recibir atención personalizada.</p>
    </div>
    <div style="padding:48px 42px;display:flex;flex-direction:column;justify-content:center"><h1 class="serif" style="font-size:32px;margin:0 0 8px">Iniciar sesión</h1><p class="content-subtitle">Ingresá con tu cuenta para acceder a tus funcionalidades.</p>
      <?php if ($errorMessage): ?><div role="alert" style="background:#fff0ed;border:1px solid #f0c8bd;color:#8d4637;border-radius:8px;padding:11px;margin:0 0 15px;font-size:12px"><?= e($errorMessage) ?></div><?php endif; ?>
      <form method="post" class="modal-form" style="gap:16px"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="field"><label for="email">Correo electrónico</label><input id="email" name="email" type="email" value="<?= e((string)($_POST['email'] ?? '')) ?>" autocomplete="username" required></div><div class="field"><label for="password">Contraseña</label><input id="password" name="password" type="password" autocomplete="current-password" required></div><button class="btn" type="submit">Iniciar sesión</button></form>
      <div style="margin-top:20px;text-align:center;color:var(--muted);font-size:12px">¿No tenés una cuenta? <a class="text-link" href="/Inmobiliaria/registro.php">Registrate</a></div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/sistema/Vistas/Diseno/pie.php'; ?>

