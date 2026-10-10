<?php
declare(strict_types=1);
require dirname(__DIR__) . '/sistema/Nucleo/inicio.php';
$user = require_client();
$errorMessage = '';
$successMessage = '';
if (isset($_SESSION['flash_success'])) {
    $successMessage = (string)$_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data = [
        'tipo' => trim((string)($_POST['tipo'] ?? '')),
        'operacion' => trim((string)($_POST['operacion'] ?? '')),
        'titulo' => trim((string)($_POST['titulo'] ?? '')),
        'direccion' => trim((string)($_POST['direccion'] ?? '')),
        'localidad' => trim((string)($_POST['localidad'] ?? '')),
        'descripcion' => trim((string)($_POST['descripcion'] ?? '')),
        'precio' => trim((string)($_POST['precio'] ?? '')),
        'superficie' => trim((string)($_POST['superficie'] ?? '')),
        'ambientes' => trim((string)($_POST['ambientes'] ?? '')),
        'dormitorios' => trim((string)($_POST['dormitorios'] ?? '')),
        'banos' => trim((string)($_POST['banos'] ?? '')),
        'antiguedad' => trim((string)($_POST['antiguedad'] ?? '')),
    ];
    $allowedTypes = ['Casa','Departamento','Local','Oficina','Terreno','Cochera'];
    $allowedOperations = ['Venta','Alquiler','Alquiler Temporario'];
    if (!in_array($data['tipo'], $allowedTypes, true) || !in_array($data['operacion'], $allowedOperations, true)
        || $data['titulo'] === '' || $data['direccion'] === '' || $data['localidad'] === ''
        || $data['descripcion'] === '' || !is_numeric($data['superficie']) || (float)$data['superficie'] <= 0
        || ($data['precio'] !== '' && (!is_numeric($data['precio']) || (float)$data['precio'] < 0))) {
        $errorMessage = 'Completá los campos obligatorios con valores válidos.';
    } else {
        $uploadedFiles = [];
        $targetDirectory = dirname(__DIR__) . '/archivos/solicitudes';
        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0750, true) && !is_dir($targetDirectory)) {
            $errorMessage = 'No se pudo preparar el espacio para los archivos. Revisá los permisos de uploads/.';
        }

        $mimeExtensions = [
            'image/jpeg' => ['jpg', 'Imagen'],
            'image/png' => ['png', 'Imagen'],
            'image/webp' => ['webp', 'Imagen'],
            'video/mp4' => ['mp4', 'Video'],
            'video/webm' => ['webm', 'Video'],
        ];
        if ($errorMessage === '' && isset($_FILES['multimedia']) && is_array($_FILES['multimedia']['name'])) {
            $fileCount = count($_FILES['multimedia']['name']);
            if ($fileCount > 8) {
                $errorMessage = 'Podés adjuntar hasta 8 archivos por solicitud.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                for ($i = 0; $i < $fileCount; $i++) {
                    $uploadError = (int)$_FILES['multimedia']['error'][$i];
                    if ($uploadError === UPLOAD_ERR_NO_FILE) continue;
                    if ($uploadError !== UPLOAD_ERR_OK || (int)$_FILES['multimedia']['size'][$i] > 20 * 1024 * 1024) {
                        $errorMessage = 'Cada archivo debe pesar hasta 20 MB y cargarse correctamente.';
                        break;
                    }
                    $tmp = (string)$_FILES['multimedia']['tmp_name'][$i];
                    $mime = $finfo->file($tmp) ?: '';
                    if (!isset($mimeExtensions[$mime]) || !is_uploaded_file($tmp)) {
                        $errorMessage = 'Formato de archivo no permitido. Usá JPG, PNG, WEBP, MP4 o WEBM.';
                        break;
                    }
                    [$extension, $mediaType] = $mimeExtensions[$mime];
                    $fileName = bin2hex(random_bytes(18)) . '.' . $extension;
                    $destination = $targetDirectory . '/' . $fileName;
                    if (!move_uploaded_file($tmp, $destination)) {
                        $errorMessage = 'No se pudo guardar uno de los archivos.';
                        break;
                    }
                    $uploadedFiles[] = ['path' => '/Inmobiliaria/archivos/solicitudes/' . $fileName, 'type' => $mediaType, 'disk' => $destination];
                }
            }
        }

        if ($errorMessage === '') {
            try {
                (new SolicitudPropiedadDAO(Database::connection()))->create((int)$user['id'], $data, $uploadedFiles);
                $_SESSION['flash_success'] = 'La solicitud se envió correctamente. El equipo revisará la información.';
                redirect('/Inmobiliaria/cliente/solicitudes.php');
            } catch (PDOException|RuntimeException $error) {
                error_log('No se pudo guardar la solicitud: ' . $error->getMessage());
                foreach ($uploadedFiles as $file) if (is_file($file['disk'])) unlink($file['disk']);
                $errorMessage = $error instanceof RuntimeException ? $error->getMessage() : 'No se pudo guardar la solicitud. Revisá que esté aplicado el esquema actualizado.';
            }
        } elseif ($uploadedFiles) {
            foreach ($uploadedFiles as $file) if (is_file($file['disk'])) unlink($file['disk']);
        }
    }
}
$pageTitle = 'Solicitar publicación';
$activePage = 'solicitar';
require dirname(__DIR__) . '/sistema/Vistas/Diseno/encabezado.php';
?>
<section class="dashboard"><div class="dashboard-title"><div><h1>Solicitar publicación</h1><p>Completá la información para que podamos evaluar tu propiedad.</p></div></div><div class="dash-layout"><?php require dirname(__DIR__) . '/sistema/Vistas/Cliente/navegacion.php'; ?><div class="content-card"><h2>Datos de la propiedad</h2><p class="content-subtitle">Un agente te acompañará durante el proceso de revisión.</p>
  <?php if ($successMessage): ?><div class="hint-box" role="status"><?= e($successMessage) ?></div><?php endif; ?><?php if ($errorMessage): ?><div class="not-implemented" role="alert"><?= e($errorMessage) ?></div><?php endif; ?>
  <div class="request-layout"><form class="form-panel" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="form-grid">
    <div class="field"><label for="tipo">Tipo de propiedad *</label><select id="tipo" name="tipo" required><option value="">Seleccionar</option><?php foreach (['Casa','Departamento','Local','Oficina','Terreno','Cochera'] as $type): ?><option value="<?= e($type) ?>" <?= ($_POST['tipo'] ?? '') === $type ? 'selected' : '' ?>><?= e($type) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="operacion">Operación *</label><select id="operacion" name="operacion" required><option value="">Seleccionar</option><?php foreach (['Venta','Alquiler','Alquiler Temporario'] as $operation): ?><option value="<?= e($operation) ?>" <?= ($_POST['operacion'] ?? '') === $operation ? 'selected' : '' ?>><?= e($operation) ?></option><?php endforeach; ?></select></div>
    <div class="field full"><label for="titulo">Título de la propiedad *</label><input id="titulo" name="titulo" required maxlength="200" value="<?= e((string)($_POST['titulo'] ?? '')) ?>" placeholder="Ej: Casa luminosa con jardín en San Isidro"></div>
    <div class="field"><label for="direccion">Dirección *</label><input id="direccion" name="direccion" required maxlength="255" value="<?= e((string)($_POST['direccion'] ?? '')) ?>" placeholder="Calle y altura"></div>
    <div class="field"><label for="localidad">Localidad *</label><input id="localidad" name="localidad" required maxlength="100" value="<?= e((string)($_POST['localidad'] ?? '')) ?>" placeholder="Barrio o localidad"></div>
    <div class="field full"><label for="descripcion">Descripción *</label><textarea id="descripcion" name="descripcion" required placeholder="Contanos un poco sobre la propiedad…"><?= e((string)($_POST['descripcion'] ?? '')) ?></textarea></div>
    <div class="field"><label for="precio">Precio aproximado (opcional)</label><input id="precio" name="precio" type="number" min="0" step="0.01" value="<?= e((string)($_POST['precio'] ?? '')) ?>" placeholder="250000"></div>
    <div class="field"><label for="superficie">Superficie total (m²) *</label><input id="superficie" name="superficie" type="number" min="1" step="0.01" required value="<?= e((string)($_POST['superficie'] ?? '')) ?>"></div>
    <div class="field"><label for="ambientes">Ambientes</label><input id="ambientes" name="ambientes" type="number" min="0" value="<?= e((string)($_POST['ambientes'] ?? '')) ?>"></div>
    <div class="field"><label for="dormitorios">Dormitorios</label><input id="dormitorios" name="dormitorios" type="number" min="0" value="<?= e((string)($_POST['dormitorios'] ?? '')) ?>"></div>
    <div class="field"><label for="banos">Baños</label><input id="banos" name="banos" type="number" min="0" value="<?= e((string)($_POST['banos'] ?? '')) ?>"></div>
    <div class="field"><label for="antiguedad">Antigüedad (años)</label><input id="antiguedad" name="antiguedad" type="number" min="0" value="<?= e((string)($_POST['antiguedad'] ?? '')) ?>"></div>
    <div class="field full"><label for="multimedia">Fotos y videos (hasta 8 archivos, 20 MB cada uno)</label><div class="file-drop">↑<br><strong>Adjuntá fotos o videos</strong><div class="help-text">Formatos permitidos: JPG, PNG, WEBP, MP4 y WEBM</div><input id="multimedia" name="multimedia[]" type="file" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm" multiple></div></div>
    <div class="field full"><label style="display:flex;gap:8px;align-items:flex-start;font-weight:400"><input type="checkbox" name="declaracion" required style="width:auto;margin:1px 0">Confirmo que los datos son correctos y autorizo al equipo a contactarme para coordinar una visita.</label></div>
    <div class="field full"><button class="btn" type="submit">Enviar solicitud</button></div>
  </div></form><aside class="timeline-panel"><h3>¿Qué pasa después?</h3><div class="timeline"><div class="timeline-step current"><span class="step-dot"></span><div><strong>Enviás la solicitud</strong><small>Recibimos los datos y archivos de la propiedad.</small></div></div><div class="timeline-step"><span class="step-dot"></span><div><strong>Revisión inicial</strong><small>Verificamos que la información esté completa.</small></div></div><div class="timeline-step"><span class="step-dot"></span><div><strong>Un agente te asesora</strong><small>Coordinamos una visita y revisamos la documentación.</small></div></div><div class="timeline-step"><span class="step-dot"></span><div><strong>Aprobación y publicación</strong><small>El administrador valida la publicación antes de mostrarla.</small></div></div></div><div class="hint-box">Podés revisar el estado y el motivo de rechazo, si lo hubiera, en “Mis solicitudes”.</div></aside></div>
</div></div></section>
<?php require dirname(__DIR__) . '/sistema/Vistas/Diseno/pie.php'; ?>

