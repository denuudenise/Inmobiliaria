<?php
declare(strict_types=1);

require __DIR__ . '/sistema/Nucleo/inicio.php';

if (current_user()) {
    redirect(role_destination(current_user()['role']));
}

$errorMessage = '';
$fieldErrors = [];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name     = trim((string)($_POST['nombre'] ?? ''));
    $surname  = trim((string)($_POST['apellido'] ?? ''));
    $dni      = trim((string)($_POST['dni'] ?? ''));
    $phone    = trim((string)($_POST['telefono'] ?? ''));
    $address  = trim((string)($_POST['direccion'] ?? ''));
    $city     = trim((string)($_POST['localidad'] ?? ''));
    $birthDate= trim((string)($_POST['fecha_nacimiento'] ?? ''));
    $gender   = trim((string)($_POST['genero'] ?? ''));
    $email    = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if (!Validacion::nombre($name)) $fieldErrors['nombre']='Usá entre 2 y 100 letras. Se permiten espacios, apóstrofes y guiones; no números.';
    if (!Validacion::nombre($surname)) $fieldErrors['apellido']='Usá entre 2 y 100 letras. Se permiten espacios, apóstrofes y guiones; no números.';
    if (!Validacion::dni($dni)) $fieldErrors['dni']='Ingresá entre 7 y 10 dígitos, sin puntos ni letras.';
    if (!Validacion::telefono($phone)) $fieldErrors['telefono']='Ingresá un teléfono válido con al menos 7 números; podés dejarlo vacío.';
    if (!Validacion::direccion($address)) $fieldErrors['direccion']='Ingresá una dirección de 3 a 255 caracteres con al menos una letra.';
    if (!Validacion::localidad($city)) $fieldErrors['localidad']='Ingresá una localidad de 2 a 100 caracteres con al menos una letra.';
    if (!Validacion::fechaNacimiento($birthDate)) $fieldErrors['fecha_nacimiento']='Ingresá una fecha válida que no sea futura.';
    if (!Validacion::genero($gender)) $fieldErrors['genero']='Seleccioná una opción de la lista.';
    if (!Validacion::email($email)) $fieldErrors['email']='Ingresá un correo válido, por ejemplo nombre@dominio.com.';
    if (!Validacion::contrasena($password)) $fieldErrors['password']='Usá al menos 8 caracteres con una letra, un número y un símbolo (por ejemplo ! o #).';
    if ($password !== (string)($_POST['password_confirm'] ?? '')) $fieldErrors['password_confirm']='Las contraseñas no coinciden.';

    if ($fieldErrors) {
        $errorMessage='Revisá los campos marcados antes de crear la cuenta.';
    } else {
        try {
            $dao = new UsuarioDAO(Database::connection());

            $dao->createClient(
                $name,
                $surname,
                $dni,
                $phone,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $address,
                $city,
                $birthDate,
                $gender
            );

            $user = $dao->findByEmail($email);

            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id'      => (int)$user['id_usuario'],
                'name'    => $user['nombre'],
                'surname' => $user['apellido'],
                'dni'     => $user['dni'],
                'phone'   => $user['telefono'],
                'email'   => $user['email'],
                'role'    => $user['rol'],
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

<section class="dashboard register-page">
    <div class="content-card register-card">
        <header class="register-heading">
            <span class="register-eyebrow">INMOBILIARIA NUEVO HOGAR</span>
            <h1>Creá tu cuenta</h1>
            <p>Completá tus datos para guardar propiedades y seguir tus consultas y operaciones.</p>
        </header>

        <?php if ($fieldErrors): ?>
            <div class="register-alert" role="alert"><span class="register-alert-icon">!</span><span><strong>Hay <?= count($fieldErrors) ?> campos para revisar</strong><small>Te indicamos qué corregir debajo de cada campo.</small></span></div>
        <?php elseif ($errorMessage): ?>
            <div class="register-alert" role="alert"><span class="register-alert-icon">!</span><span><?= e($errorMessage) ?></span></div>
        <?php endif; ?>

        <form method="post" class="register-form" id="registro-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <h2 class="register-section-title"><span>01</span> Datos personales</h2>
            <div class="register-fields">
                <div class="field<?= isset($fieldErrors['nombre'])?' has-error':'' ?>"><label for="nombre">Nombre <i>*</i></label><input id="nombre" name="nombre" type="text" minlength="2" maxlength="100" pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ .'-]{2,100}" value="<?= e((string)($_POST['nombre'] ?? '')) ?>" autocomplete="given-name" required aria-invalid="<?= isset($fieldErrors['nombre'])?'true':'false' ?>" aria-describedby="ayuda-nombre"><small class="field-feedback" id="ayuda-nombre" aria-live="polite"><?= e($fieldErrors['nombre'] ?? '') ?></small></div>
                <div class="field<?= isset($fieldErrors['apellido'])?' has-error':'' ?>"><label for="apellido">Apellido <i>*</i></label><input id="apellido" name="apellido" type="text" minlength="2" maxlength="100" pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ .'-]{2,100}" value="<?= e((string)($_POST['apellido'] ?? '')) ?>" autocomplete="family-name" required aria-invalid="<?= isset($fieldErrors['apellido'])?'true':'false' ?>" aria-describedby="ayuda-apellido"><small class="field-feedback" id="ayuda-apellido" aria-live="polite"><?= e($fieldErrors['apellido'] ?? '') ?></small></div>
                <div class="field<?= isset($fieldErrors['dni'])?' has-error':'' ?>"><label for="dni">DNI <i>*</i></label><input id="dni" name="dni" type="text" inputmode="numeric" minlength="7" maxlength="10" pattern="[0-9]{7,10}" value="<?= e((string)($_POST['dni'] ?? '')) ?>" autocomplete="off" required aria-invalid="<?= isset($fieldErrors['dni'])?'true':'false' ?>" aria-describedby="ayuda-dni"><small class="field-feedback" id="ayuda-dni" aria-live="polite"><?= e($fieldErrors['dni'] ?? '') ?></small></div>
                <div class="field<?= isset($fieldErrors['fecha_nacimiento'])?' has-error':'' ?>"><label for="fecha_nacimiento">Fecha de nacimiento <i>*</i></label><input id="fecha_nacimiento" name="fecha_nacimiento" type="date" max="<?= e(date('Y-m-d')) ?>" value="<?= e((string)($_POST['fecha_nacimiento'] ?? '')) ?>" autocomplete="bday" required aria-invalid="<?= isset($fieldErrors['fecha_nacimiento'])?'true':'false' ?>" aria-describedby="ayuda-fecha_nacimiento"><small class="field-feedback" id="ayuda-fecha_nacimiento" aria-live="polite"><?= e($fieldErrors['fecha_nacimiento'] ?? '') ?></small></div>
                <div class="field<?= isset($fieldErrors['genero'])?' has-error':'' ?>"><label for="genero">Género <i>*</i></label><select id="genero" name="genero" required aria-invalid="<?= isset($fieldErrors['genero'])?'true':'false' ?>" aria-describedby="ayuda-genero"><option value="">Seleccioná una opción</option><?php foreach (['Femenino','Masculino','No binario','Otro','Prefiero no decirlo'] as $genderOption): ?><option value="<?= e($genderOption) ?>" <?= (($_POST['genero'] ?? '') === $genderOption) ? 'selected' : '' ?>><?= e($genderOption) ?></option><?php endforeach; ?></select><small class="field-feedback" id="ayuda-genero" aria-live="polite"><?= e($fieldErrors['genero'] ?? '') ?></small></div>
            </div>

            <h2 class="register-section-title"><span>02</span> Contacto y domicilio</h2>
            <div class="register-fields">
                <div class="field<?= isset($fieldErrors['email'])?' has-error':'' ?>"><label for="email">Correo electrónico <i>*</i></label><input id="email" name="email" type="email" maxlength="150" value="<?= e((string)($_POST['email'] ?? '')) ?>" autocomplete="email" required aria-invalid="<?= isset($fieldErrors['email'])?'true':'false' ?>" aria-describedby="ayuda-email"><small class="field-feedback" id="ayuda-email" aria-live="polite"><?= e($fieldErrors['email'] ?? '') ?></small></div>
                <div class="field<?= isset($fieldErrors['telefono'])?' has-error':'' ?>"><label for="telefono">Teléfono <span class="optional-tag">Opcional</span></label><input id="telefono" name="telefono" type="tel" maxlength="30" pattern="\+?[0-9(). -]{6,30}" value="<?= e((string)($_POST['telefono'] ?? '')) ?>" autocomplete="tel" aria-invalid="<?= isset($fieldErrors['telefono'])?'true':'false' ?>" aria-describedby="ayuda-telefono"><small class="field-feedback" id="ayuda-telefono" aria-live="polite"><?= e($fieldErrors['telefono'] ?? '') ?></small></div>
                <div class="field<?= isset($fieldErrors['direccion'])?' has-error':'' ?>"><label for="direccion">Dirección <i>*</i></label><input id="direccion" name="direccion" type="text" minlength="3" maxlength="255" pattern="(?=.*[A-Za-zÁÉÍÓÚÜÑáéíóúüñ])[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ .,#º°/'-]{3,255}" value="<?= e((string)($_POST['direccion'] ?? '')) ?>" autocomplete="street-address" required aria-invalid="<?= isset($fieldErrors['direccion'])?'true':'false' ?>" aria-describedby="ayuda-direccion"><small class="field-feedback" id="ayuda-direccion" aria-live="polite"><?= e($fieldErrors['direccion'] ?? '') ?></small></div>
                <div class="field<?= isset($fieldErrors['localidad'])?' has-error':'' ?>"><label for="localidad">Localidad <i>*</i></label><input id="localidad" name="localidad" type="text" minlength="2" maxlength="100" pattern="(?=.*[A-Za-zÁÉÍÓÚÜÑáéíóúüñ])[A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ .,'-]{2,100}" value="<?= e((string)($_POST['localidad'] ?? '')) ?>" autocomplete="address-level2" required aria-invalid="<?= isset($fieldErrors['localidad'])?'true':'false' ?>" aria-describedby="ayuda-localidad"><small class="field-feedback" id="ayuda-localidad" aria-live="polite"><?= e($fieldErrors['localidad'] ?? '') ?></small></div>
            </div>

            <h2 class="register-section-title"><span>03</span> Seguridad de la cuenta</h2>
            <div class="register-fields">
                <div class="field<?= isset($fieldErrors['password'])?' has-error':'' ?>"><label for="password">Contraseña <i>*</i></label><input id="password" name="password" type="password" minlength="8" maxlength="255" required autocomplete="new-password" aria-invalid="<?= isset($fieldErrors['password'])?'true':'false' ?>" aria-describedby="ayuda-password"><small class="field-feedback" id="ayuda-password" aria-live="polite"><?= e($fieldErrors['password'] ?? '') ?></small><ul class="password-rules" id="password-rules"><li data-rule="length">8 caracteres o más</li><li data-rule="letter-number">Una letra y un número</li><li data-rule="symbol">Un símbolo, por ejemplo ! o #</li></ul></div>
                <div class="field<?= isset($fieldErrors['password_confirm'])?' has-error':'' ?>"><label for="password_confirm">Confirmar contraseña <i>*</i></label><input id="password_confirm" name="password_confirm" type="password" minlength="8" maxlength="255" required autocomplete="new-password" aria-invalid="<?= isset($fieldErrors['password_confirm'])?'true':'false' ?>" aria-describedby="ayuda-password_confirm"><small class="field-feedback" id="ayuda-password_confirm" aria-live="polite"><?= e($fieldErrors['password_confirm'] ?? '') ?></small></div>
            </div>

            <div class="register-submit"><span><i>*</i> Campos obligatorios</span><button class="btn" type="submit">Crear mi cuenta <span aria-hidden="true">→</span></button></div>
        </form>

        <p class="register-login">¿Ya tenés una cuenta? <a class="text-link" href="/Inmobiliaria/login.php">Iniciá sesión</a></p>
    </div>
</section>

<script>
(() => {
    const form = document.getElementById('registro-form');
    if (!form) return;
    const controls = [...form.querySelectorAll('input:not([type="hidden"])[name], select[name]')];
    let submitted = false;
    const messages = {
        nombre: 'Ingresá un nombre de 2 a 100 letras, sin números.',
        apellido: 'Ingresá un apellido de 2 a 100 letras, sin números.',
        dni: 'El DNI debe tener entre 7 y 10 dígitos.',
        telefono: 'Ingresá un teléfono con al menos 7 números o dejalo vacío.',
        fecha_nacimiento: 'Elegí una fecha válida que no sea futura.',
        genero: 'Seleccioná una opción de la lista.',
        email: 'Ingresá un correo válido, por ejemplo nombre@dominio.com.',
        direccion: 'Ingresá una dirección con al menos una letra.',
        localidad: 'Ingresá una localidad con al menos una letra.',
        password: 'La contraseña debe tener 8 caracteres, una letra, un número y un símbolo.',
        password_confirm: 'Las contraseñas deben coincidir.'
    };
    const hints = {
        nombre: 'Solo letras; podés usar espacios o guiones.',
        apellido: 'Solo letras; podés usar espacios o guiones.',
        dni: 'Solo números, entre 7 y 10 dígitos.',
        telefono: 'Incluí al menos 7 números.',
        fecha_nacimiento: 'No puede ser una fecha futura.',
        genero: 'Elegí la opción que prefieras.',
        email: 'Usaremos este correo para identificar tu cuenta.',
        direccion: 'Incluí la calle y, si corresponde, la altura.',
        localidad: 'Por ejemplo, San Isidro o Villa Urquiza.',
        password: 'La contraseña debe cumplir estos requisitos:',
        password_confirm: 'Volvé a escribir la misma contraseña.'
    };
    const successes = {
        nombre: 'Nombre válido.', apellido: 'Apellido válido.', dni: 'DNI válido.',
        telefono: 'Teléfono válido.', fecha_nacimiento: 'Fecha válida.', genero: 'Opción seleccionada.',
        email: 'Correo válido.', direccion: 'Dirección válida.', localidad: 'Localidad válida.',
        password: 'Contraseña segura.', password_confirm: 'Las contraseñas coinciden.'
    };
    const checks = {
        nombre: v => /^[\p{L}\p{M}]+(?:[ .'-][\p{L}\p{M}]+)*$/u.test(v) && [...v].length >= 2 && [...v].length <= 100,
        apellido: v => /^[\p{L}\p{M}]+(?:[ .'-][\p{L}\p{M}]+)*$/u.test(v) && [...v].length >= 2 && [...v].length <= 100,
        dni: v => /^[0-9]{7,10}$/.test(v),
        telefono: v => v === '' || (/^\+?[0-9\s().-]{6,30}$/.test(v) && (v.match(/[0-9]/g) || []).length >= 7),
        direccion: v => v.length >= 3 && v.length <= 255 && /\p{L}/u.test(v) && /^[\p{L}\p{M}\p{N}\s.,#º°/'-]+$/u.test(v),
        localidad: v => v.length >= 2 && v.length <= 100 && /\p{L}/u.test(v) && /^[\p{L}\p{M}\p{N}\s.,'-]+$/u.test(v),
        fecha_nacimiento: (v, el) => Boolean(v) && v <= el.max,
        genero: v => ['Femenino','Masculino','No binario','Otro','Prefiero no decirlo'].includes(v),
        email: (v, el) => Boolean(v) && el.validity.typeMismatch === false && v.length <= 150,
        password: v => v.length >= 8 && v.length <= 255 && /\p{L}/u.test(v) && /\d/.test(v) && /[^\p{L}\d\s]/u.test(v),
        password_confirm: v => Boolean(v) && v === form.elements.password.value
    };
    function validate(el, force = false) {
        const name = el.name;
        const value = el.value.trim();
        const valid = checks[name] ? checks[name](value, el) : true;
        const emptyRequired = el.required && value === '';
        const invalid = emptyRequired || !valid;
        const feedback = document.getElementById(`ayuda-${name}`);
        const show = force || el.dataset.touched === 'true' || submitted;
        el.setAttribute('aria-invalid', show && invalid ? 'true' : 'false');
        el.setCustomValidity(show && invalid ? (emptyRequired ? 'Completá este campo.' : messages[name]) : '');
        el.closest('.field').classList.toggle('has-error', show && invalid);
        el.closest('.field').classList.toggle('is-valid', show && !invalid && value !== '');
        if (feedback && show) feedback.textContent = invalid ? (emptyRequired ? 'Este campo es obligatorio.' : messages[name]) : (value ? successes[name] : hints[name]) || '';
        return !invalid;
    }
    function updatePasswordRules() {
        const value = form.elements.password.value;
        const rules = {
            length: value.length >= 8 && value.length <= 255,
            'letter-number': /\p{L}/u.test(value) && /\d/.test(value),
            symbol: /[^\p{L}\d\s]/u.test(value)
        };
        for (const [name, ok] of Object.entries(rules)) document.querySelector(`#password-rules [data-rule="${name}"]`)?.classList.toggle('passed', ok);
    }
    controls.forEach(el => {
        if (el.getAttribute('aria-invalid') === 'true') el.dataset.touched = 'true';
        const feedback = document.getElementById(`ayuda-${el.name}`);
        el.addEventListener('focus', () => {
            el.dataset.touched = 'true';
            const field = el.closest('.field');
            field.classList.add('is-touched');
            if (el.value.trim() || el.getAttribute('aria-invalid') === 'true') validate(el);
            else if (feedback) feedback.textContent = hints[el.name] || '';
        });
        el.addEventListener('blur', () => { el.dataset.touched = 'true'; validate(el); });
        el.addEventListener('input', () => { if (el.dataset.touched === 'true' || submitted) validate(el); if (el.name === 'password' || el.name === 'password_confirm') { updatePasswordRules(); validate(form.elements.password_confirm); } });
        el.addEventListener('change', () => { el.dataset.touched = 'true'; validate(el); });
    });
    form.addEventListener('submit', event => {
        submitted = true;
        const invalid = controls.filter(el => !validate(el, true));
        if (invalid.length) { event.preventDefault(); invalid[0].focus(); invalid[0].reportValidity(); }
    });
    updatePasswordRules();
})();
</script>

<?php require __DIR__ . '/sistema/Vistas/Diseno/pie.php'; ?>
