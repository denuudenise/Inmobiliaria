<?php
declare(strict_types=1);
require __DIR__ . '/sistema/Nucleo/inicio.php';
if (current_user()) redirect(role_destination(current_user()['role']));
$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string)($_POST['nombre'] ?? ''));
    $surname = trim((string)($_POST['apellido'] ?? ''));
    $dni = trim((string)($_POST['dni'] ?? ''));
    $phone = trim((string)($_POST['telefono'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if ($name === '' || $surname === '' || !preg_match('/^[0-9]{7,10}$/', $dni) || strlen($name)>100 || strlen($surname)>100 || strlen($email)>150 || strlen($phone)>30) {
        $errorMessage = 'Completá nombre, apellido y un DNI válido de 7 a 10 dígitos.';
    } elseif ($password !== (string)($_POST['password_confirm'] ?? '')) {
        $errorMessage = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 8) {
        $errorMessage = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Ingresá un correo electrónico válido.';
    } else {
        try {
            $dao = new UsuarioDAO(Database::connection());
            $dao->createClient($name, $surname, $dni, $phone, $email, password_hash($password, PASSWORD_DEFAULT));
            $user = $dao->findByEmail($email);
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int)$user['id_usuario'], 'name' => $user['nombre'], 'surname' => $user['apellido'],
                'dni' => $user['dni'], 'phone' => $user['telefono'], 'email' => $user['email'], 'role' => $user['rol'],
            ];
            flash('¡Cuenta creada! Ya podés guardar propiedades y enviar consultas.');
            redirect(role_destination('Cliente'));
        } catch (PDOException $error) {
            error_log('Error de registro: ' . $error->getMessage());
            $errorMessage = (string)$error->getCode() === '23000'
                ? 'Ya existe una cuenta con ese correo o DNI.'
                : 'No se pudo guardar la cuenta. Revisá la conexión a MySQL.';
        } catch (RuntimeException $error) {
            $errorMessage = $error->getMessage();
        }
    }
}
$pageTitle = 'Crear cuenta';
require __DIR__ . '/sistema/Vistas/Diseno/encabezado.php';
?>
<section class="dashboard" style="max-width:900px;margin-top:40px;margin-bottom:45px"><div class="content-card"><h1 style="font-size:31px;margin:0">Crear cuenta</h1><p class="content-subtitle">Registrate para guardar propiedades y seguir tus solicitudes.</p>
  <?php if ($errorMessage): ?><div role="alert" style="background:#fff0ed;border:1px solid #f0c8bd;color:#8d4637;border-radius:8px;padding:11px;margin-bottom:15px;font-size:12px"><?= e($errorMessage) ?></div><?php endif; ?>
  <form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="field"><label for="nombre">Nombre *</label><input id="nombre" name="nombre" value="<?= e((string)($_POST['nombre'] ?? '')) ?>" required></div><div class="field"><label for="apellido">Apellido *</label><input id="apellido" name="apellido" value="<?= e((string)($_POST['apellido'] ?? '')) ?>" required></div><div class="field"><label for="dni">DNI *</label><input id="dni" name="dni" value="<?= e((string)($_POST['dni'] ?? '')) ?>" required></div><div class="field"><label for="telefono">Teléfono</label><input id="telefono" name="telefono" value="<?= e((string)($_POST['telefono'] ?? '')) ?>"></div><div class="field"><label for="email">Correo electrónico *</label><input id="email" name="email" type="email" value="<?= e((string)($_POST['email'] ?? '')) ?>" required></div><div class="field"><label for="password">Contraseña *</label><input id="password" name="password" type="password" minlength="8" required></div><div class="field"><label for="password_confirm">Confirmar contraseña *</label><input id="password_confirm" name="password_confirm" type="password" minlength="8" required></div><div class="field full"><button class="btn" type="submit">Crear cuenta</button></div></form>
  <p style="text-align:center;color:var(--muted);margin-bottom:0">¿Ya tenés una cuenta? <a class="text-link" href="/Inmobiliaria/login.php">Iniciá sesión</a></p></div></section>
<?php require __DIR__ . '/sistema/Vistas/Diseno/pie.php'; ?>

