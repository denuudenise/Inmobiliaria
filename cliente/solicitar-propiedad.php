<?php
declare(strict_types=1);

require dirname(__DIR__) . '/sistema/Nucleo/inicio.php';

$user = require_client();


$allowedTypes = array_column(
    query_rows('SELECT nombre FROM tipos_propiedad ORDER BY id_tipo_propiedad'),
    'nombre'
);

$allowedOperations = array_column(
    query_rows('SELECT nombre FROM tipos_transaccion ORDER BY id_tipo_transaccion'),
    'nombre'
);

$errorMessage = '';
$fieldErrors = [];
$successMessage = '';
$documentState = [];

if (isset($_SESSION['flash_success'])) {
    $successMessage = (string)$_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try { $_POST=(new OfertaDAO(Database::connection()))->normalize($_POST,true); }
    catch (RuntimeException $e) { $fieldErrors['ofertas']=$e->getMessage(); }

    $data = [
        'moneda'      => (string)($_POST['moneda'] ?? 'USD'),
        'tipo'        => trim((string)($_POST['tipo'] ?? '')),
        'operacion'   => trim((string)($_POST['operacion'] ?? '')),
        'titulo'      => trim((string)($_POST['titulo'] ?? '')),
        'direccion'   => trim((string)($_POST['direccion'] ?? '')),
        'localidad'   => trim((string)($_POST['localidad'] ?? '')),
        'descripcion' => trim((string)($_POST['descripcion'] ?? '')),
        'precio'      => trim((string)($_POST['precio'] ?? '')),
        'superficie'  => trim((string)($_POST['superficie'] ?? '')),
        'ambientes'   => trim((string)($_POST['ambientes'] ?? '')),
        'dormitorios' => trim((string)($_POST['dormitorios'] ?? '')),
        'banos'       => trim((string)($_POST['banos'] ?? '')),
        'antiguedad'  => trim((string)($_POST['antiguedad'] ?? '')),
    ];


    if (!in_array($data['tipo'], $allowedTypes, true)) $fieldErrors['tipo']='Seleccioná un tipo de propiedad.';
    if ($data['titulo']==='' || mb_strlen($data['titulo'])<5 || mb_strlen($data['titulo'])>200 || preg_match('/[\p{L}\p{N}]/u',$data['titulo'])!==1 || preg_match('/^[\p{L}\p{M}\p{N}\s.,:;()\/#º°&+\'-]+$/uD',$data['titulo'])!==1) $fieldErrors['titulo']='Escribí un título de 5 a 200 caracteres con letras o números; evitá símbolos no permitidos.';
    if (!Validacion::direccion($data['direccion'])) $fieldErrors['direccion']='Ingresá una dirección de 3 a 255 caracteres con al menos una letra.';
    if (!Validacion::localidad($data['localidad'])) $fieldErrors['localidad']='Ingresá una localidad de 2 a 100 caracteres con al menos una letra.';
    if (mb_strlen($data['descripcion'])<10 || mb_strlen($data['descripcion'])>3000 || preg_match('/[\p{L}\p{N}]/u',$data['descripcion'])!==1) $fieldErrors['descripcion']='Describí la propiedad en 10 a 3000 caracteres.';
    if (!preg_match('/^\d{1,6}(?:\.\d{1,2})?$/D',$data['superficie']) || (float)$data['superficie']<=0 || (float)$data['superficie']>999999) $fieldErrors['superficie']='Ingresá una superficie mayor a 0 y de hasta 999.999 m².';
    foreach (['ambientes'=>'ambientes','dormitorios'=>'dormitorios','banos'=>'baños','antiguedad'=>'antigüedad'] as $field=>$label) {
        if ($data[$field]!=='' && (filter_var($data[$field],FILTER_VALIDATE_INT)===false || (int)$data[$field]<0 || (int)$data[$field]>1000)) $fieldErrors[$field]='Ingresá un número entero entre 0 y 1000 o dejá el campo vacío.';
    }
    if (!isset($_POST['declaracion'])) $fieldErrors['declaracion']='Confirmá que los datos son correctos para enviar la solicitud.';

    if ($fieldErrors) {
        $errorMessage = 'Hay campos para corregir. Te indicamos qué revisar debajo de cada uno.';
    } else {

      
        $uploadedFiles = [];
        $targetDirectory = dirname(__DIR__) . '/archivos/solicitudes';

        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0750, true) && !is_dir($targetDirectory)) {
            $errorMessage = 'No se pudo preparar el espacio para los archivos. Revisá los permisos de uploads/.';
        }

        $mimeExtensions = [
            'image/jpeg' => ['jpg', 'Imagen'],
            'image/png'  => ['png', 'Imagen'],
            'image/webp' => ['webp', 'Imagen'],
            'video/mp4'  => ['mp4', 'Video'],
            'video/webm' => ['webm', 'Video'],
        ];

        if ($errorMessage === '' && isset($_FILES['multimedia']) && is_array($_FILES['multimedia']['name'])) {
            $fileCount = count($_FILES['multimedia']['name']);

            if ($fileCount > 8) {
                $errorMessage = 'Podés adjuntar hasta 8 archivos por solicitud.';
                $fieldErrors['multimedia']=$errorMessage;

            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);

                for ($i = 0; $i < $fileCount; $i++) {
                    $uploadError = (int)$_FILES['multimedia']['error'][$i];

                    if ($uploadError === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }

                    if ($uploadError !== UPLOAD_ERR_OK || (int)$_FILES['multimedia']['size'][$i] > 2 * 1024 * 1024) {
                        $errorMessage = 'Cada archivo debe pesar hasta 2 MB y cargarse correctamente.';
                        $fieldErrors['multimedia']=$errorMessage;
                        break;
                    }

                    $tmp = (string)$_FILES['multimedia']['tmp_name'][$i];
                    $mime = $finfo->file($tmp) ?: '';

                    if (!isset($mimeExtensions[$mime]) || !is_uploaded_file($tmp)) {
                        $errorMessage = 'Formato de archivo no permitido. Usá JPG, PNG, WEBP, MP4 o WEBM.';
                        $fieldErrors['multimedia']=$errorMessage;
                        break;
                    }

                    [$extension, $mediaType] = $mimeExtensions[$mime];

                    $fileName = bin2hex(random_bytes(18)) . '.' . $extension;
                    $destination = $targetDirectory . '/' . $fileName;

                    if (!move_uploaded_file($tmp, $destination)) {
                        $errorMessage = 'No se pudo guardar uno de los archivos.';
                        $fieldErrors['multimedia']=$errorMessage;
                        break;
                    }

                    $uploadedFiles[] = [
                        'path' => '/Inmobiliaria/archivos/solicitudes/' . $fileName,
                        'type' => $mediaType,
                        'disk' => $destination,
                    ];
                }
            }
        }

        
        if ($errorMessage === '') {
            try {
                (new SolicitudPropiedadDAO(Database::connection()))->create(
                    (int)$user['id'],
                    $data,
                    $uploadedFiles,
                    $_POST
                );

                $_SESSION['flash_success'] = 'La solicitud se envió correctamente. El equipo revisará la información.';
                redirect('/Inmobiliaria/cliente/solicitudes.php');

            } catch (PDOException|RuntimeException $error) {
                error_log('No se pudo guardar la solicitud: ' . $error->getMessage());

                foreach ($uploadedFiles as $file) {
                    if (is_file($file['disk'])) {
                        unlink($file['disk']);
                    }
                }

                if ($error instanceof PDOException) {
                    $errorMessage='No se pudo guardar la solicitud. Revisá la conexión con la base de datos.';
                } elseif (str_contains($error->getMessage(),'planos')) {
                    $fieldErrors['planos']=$error->getMessage();
                } elseif (str_contains($error->getMessage(),'escritura')) {
                    $fieldErrors['escrituras']=$error->getMessage();
                } elseif (str_contains($error->getMessage(),'Si te falta algún documento')) {
                    $fieldErrors['observaciones_documentacion']=$error->getMessage();
                } elseif (str_contains($error->getMessage(),'documento')) {
                    $fieldErrors['planos']=$error->getMessage();
                } elseif (str_contains($error->getMessage(),'monto mínimo')) {
                    $fieldErrors['ofertas']=$error->getMessage();
                } elseif (str_contains($error->getMessage(),'archivos') || str_contains($error->getMessage(),'archivo')) {
                    $fieldErrors['multimedia']=$error->getMessage();
                } else {
                    $errorMessage=$error->getMessage();
                }
            }


        } elseif ($uploadedFiles) {
            foreach ($uploadedFiles as $file) {
                if (is_file($file['disk'])) {
                    unlink($file['disk']);
                }
            }
        }
    }
}

$pageTitle = 'Solicitar publicación';
$activePage = 'solicitar';

require dirname(__DIR__) . '/sistema/Vistas/Diseno/encabezado.php';
?>

<section class="dashboard">

    
    <div class="dashboard-title">
        <div>
            <h1>Solicitar publicación</h1>
            <p>Completá la información para que podamos evaluar tu propiedad.</p>
        </div>
    </div>

    <div class="dash-layout">

        
        <?php require dirname(__DIR__) . '/sistema/Vistas/Cliente/navegacion.php'; ?>

        
        <div class="content-card">
            <h2>Datos de la propiedad</h2>
            <p class="content-subtitle">Un agente te acompañará durante el proceso de revisión.</p>

            <?php if ($successMessage): ?>
                <div class="hint-box" role="status"><?= e($successMessage) ?></div>
            <?php endif; ?>

            <?php if ($errorMessage && !$fieldErrors): ?>
                <div class="not-implemented" role="alert"><?= e($errorMessage) ?></div>
            <?php endif; ?>
            <?php if ($fieldErrors): ?><div class="register-alert" role="alert"><span class="register-alert-icon">!</span><span><strong>Hay <?= count($fieldErrors) ?> campo<?= count($fieldErrors)===1?'':'s' ?> para revisar</strong><small>Encontrá el aviso debajo de cada campo.</small></span></div><?php endif; ?>

            <div class="request-layout">

            
                <form class="form-panel request-validation-form" id="solicitud-propiedad-form" method="post" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                    <div class="form-grid">

                    
                        <div class="field<?= isset($fieldErrors['tipo'])?' has-error':'' ?>">
                            <label for="tipo">Tipo de propiedad *</label>
                            <select id="tipo" name="tipo" required data-validate="tipo" data-hint="Elegí el tipo de inmueble que querés publicar." aria-invalid="<?= isset($fieldErrors['tipo'])?'true':'false' ?>" aria-describedby="ayuda-tipo">
                                <option value="">Seleccionar</option>
                                <?php foreach ($allowedTypes as $type): ?>
                                    <option value="<?= e($type) ?>" <?= ($_POST['tipo'] ?? '') === $type ? 'selected' : '' ?>>
                                        <?= e($type) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="field-feedback" id="ayuda-tipo" aria-live="polite"><?= e($fieldErrors['tipo']??'') ?></small>
                        </div>

                     


                        <div class="field full<?= isset($fieldErrors['titulo'])?' has-error':'' ?>">
                            <label for="titulo">Título de la propiedad *</label>
                            <input
                                id="titulo"
                                name="titulo"
                                type="text"
                                required
                                minlength="5"
                                maxlength="200"
                                data-validate="titulo"
                                data-hint="Usá de 5 a 200 caracteres con letras y números."
                                value="<?= e((string)($_POST['titulo'] ?? '')) ?>"
                                placeholder="Ej: Casa luminosa con jardín en San Isidro"
                                aria-invalid="<?= isset($fieldErrors['titulo'])?'true':'false' ?>"
                                aria-describedby="ayuda-titulo"
                            >
                            <small class="field-feedback" id="ayuda-titulo" aria-live="polite"><?= e($fieldErrors['titulo']??'') ?></small>
                        </div>

                 
                        <div class="field<?= isset($fieldErrors['direccion'])?' has-error':'' ?>">
                            <label for="direccion">Dirección *</label>
                            <input
                                id="direccion"
                                name="direccion"
                                type="text"
                                required
                                minlength="3"
                                maxlength="255"
                                data-validate="direccion"
                                data-hint="Ingresá la calle y altura; debe incluir al menos una letra."
                                value="<?= e((string)($_POST['direccion'] ?? '')) ?>"
                                placeholder="Calle y altura"
                                aria-invalid="<?= isset($fieldErrors['direccion'])?'true':'false' ?>"
                                aria-describedby="ayuda-direccion"
                            >
                            <small class="field-feedback" id="ayuda-direccion" aria-live="polite"><?= e($fieldErrors['direccion']??'') ?></small>
                        </div>

                   
                        <div class="field<?= isset($fieldErrors['localidad'])?' has-error':'' ?>">
                            <label for="localidad">Localidad *</label>
                            <input
                                id="localidad"
                                name="localidad"
                                type="text"
                                required
                                minlength="2"
                                maxlength="100"
                                data-validate="localidad"
                                data-hint="Ingresá un barrio o localidad con al menos una letra."
                                value="<?= e((string)($_POST['localidad'] ?? '')) ?>"
                                placeholder="Barrio o localidad"
                                aria-invalid="<?= isset($fieldErrors['localidad'])?'true':'false' ?>"
                                aria-describedby="ayuda-localidad"
                            >
                            <small class="field-feedback" id="ayuda-localidad" aria-live="polite"><?= e($fieldErrors['localidad']??'') ?></small>
                        </div>

                  
                        <div class="field full<?= isset($fieldErrors['descripcion'])?' has-error':'' ?>">
                            <label for="descripcion">Descripción *</label>
                            <textarea
                                id="descripcion"
                                name="descripcion"
                                minlength="10"
                                maxlength="3000"
                                required
                                data-validate="descripcion"
                                data-hint="Contá un poco sobre la propiedad; escribí al menos 10 caracteres."
                                aria-invalid="<?= isset($fieldErrors['descripcion'])?'true':'false' ?>"
                                aria-describedby="ayuda-descripcion"
                                placeholder="Contanos un poco sobre la propiedad…"
                            ><?= e((string)($_POST['descripcion'] ?? '')) ?></textarea>
                            <small class="field-feedback" id="ayuda-descripcion" aria-live="polite"><?= e($fieldErrors['descripcion']??'') ?></small>
                        </div>

                      

                 



                   
                        <div class="field<?= isset($fieldErrors['superficie'])?' has-error':'' ?>">
                            <label for="superficie">Superficie total (m²) *</label>
                            <input
                                id="superficie"
                                name="superficie"
                                type="number"
                                min="1"
                                max="999999"
                                step="0.01"
                                required
                                data-validate="superficie"
                                data-hint="Ingresá una superficie mayor que 0 m²."
                                value="<?= e((string)($_POST['superficie'] ?? '')) ?>"
                                aria-invalid="<?= isset($fieldErrors['superficie'])?'true':'false' ?>"
                                aria-describedby="ayuda-superficie"
                            >
                            <small class="field-feedback" id="ayuda-superficie" aria-live="polite"><?= e($fieldErrors['superficie']??'') ?></small>
                        </div>

                  
                        <div class="field<?= isset($fieldErrors['ambientes'])?' has-error':'' ?>">
                            <label for="ambientes">Ambientes</label>
                            <input
                                id="ambientes"
                                name="ambientes"
                                type="number"
                                min="0"
                                max="1000"
                                step="1"
                                data-validate="entero"
                                data-hint="Ingresá un número entero de 0 a 1000 o dejalo vacío."
                                value="<?= e((string)($_POST['ambientes'] ?? '')) ?>"
                                aria-invalid="<?= isset($fieldErrors['ambientes'])?'true':'false' ?>"
                                aria-describedby="ayuda-ambientes"
                            >
                            <small class="field-feedback" id="ayuda-ambientes" aria-live="polite"><?= e($fieldErrors['ambientes']??'') ?></small>
                        </div>

                     
                        <div class="field<?= isset($fieldErrors['dormitorios'])?' has-error':'' ?>">
                            <label for="dormitorios">Dormitorios</label>
                            <input
                                id="dormitorios"
                                name="dormitorios"
                                type="number"
                                min="0"
                                max="1000"
                                step="1"
                                data-validate="entero"
                                data-hint="Ingresá un número entero de 0 a 1000 o dejalo vacío."
                                value="<?= e((string)($_POST['dormitorios'] ?? '')) ?>"
                                aria-invalid="<?= isset($fieldErrors['dormitorios'])?'true':'false' ?>"
                                aria-describedby="ayuda-dormitorios"
                            >
                            <small class="field-feedback" id="ayuda-dormitorios" aria-live="polite"><?= e($fieldErrors['dormitorios']??'') ?></small>
                        </div>

                  
                        <div class="field<?= isset($fieldErrors['banos'])?' has-error':'' ?>">
                            <label for="banos">Baños</label>
                            <input
                                id="banos"
                                name="banos"
                                type="number"
                                min="0"
                                max="1000"
                                step="1"
                                data-validate="entero"
                                data-hint="Ingresá un número entero de 0 a 1000 o dejalo vacío."
                                value="<?= e((string)($_POST['banos'] ?? '')) ?>"
                                aria-invalid="<?= isset($fieldErrors['banos'])?'true':'false' ?>"
                                aria-describedby="ayuda-banos"
                            >
                            <small class="field-feedback" id="ayuda-banos" aria-live="polite"><?= e($fieldErrors['banos']??'') ?></small>
                        </div>

                    
                        <div class="field<?= isset($fieldErrors['antiguedad'])?' has-error':'' ?>">
                            <label for="antiguedad">Antigüedad (años)</label>
                            <input
                                id="antiguedad"
                                name="antiguedad"
                                type="number"
                                min="0"
                                max="1000"
                                step="1"
                                data-validate="entero"
                                data-hint="Ingresá un número entero de 0 a 1000 o dejalo vacío."
                                value="<?= e((string)($_POST['antiguedad'] ?? '')) ?>"
                                aria-invalid="<?= isset($fieldErrors['antiguedad'])?'true':'false' ?>"
                                aria-describedby="ayuda-antiguedad"
                            >
                            <small class="field-feedback" id="ayuda-antiguedad" aria-live="polite"><?= e($fieldErrors['antiguedad']??'') ?></small>
                        </div>

                     
                        <?php require dirname(__DIR__).'/sistema/Vistas/Diseno/expediente-form.php'; ?>
<?php $offerKind='solicitud'; $offerId=0; require dirname(__DIR__).'/sistema/Vistas/Diseno/ofertas-form.php'; ?>
<div class="field full<?= isset($fieldErrors['multimedia'])?' has-error':'' ?>">
                            <label for="multimedia">Fotos y videos (hasta 8 archivos en total, 2 MB cada uno)</label>

                            <div class="file-drop">
                                ↑<br>
                                <strong>Adjuntá fotos o videos</strong>
                                <div class="help-text">Formatos permitidos: JPG, PNG, WEBP, MP4 y WEBM</div>
                                <input
                                    id="multimedia"
                                    name="multimedia[]"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp,video/mp4,video/webm"
                                    multiple
                                    data-accumulate-files
                                >
                                <button class="btn btn-outline" type="button" data-add-files="multimedia">＋ Agregar más archivos</button>
                                <ul class="selected-files" data-selected-files="multimedia" aria-live="polite"></ul>
                                <small class="field-feedback field-error" id="ayuda-multimedia" aria-live="polite"><?= e($fieldErrors['multimedia']??'') ?></small>
                            </div>
                        </div>

                       
                        <div class="field full<?= isset($fieldErrors['declaracion'])?' has-error':'' ?>">
                            <label style="display:flex;gap:8px;align-items:flex-start;font-weight:400">
                                <input
                                    type="checkbox"
                                    name="declaracion"
                                    required
                                    data-validate="checkbox"
                                    data-hint="Confirmá que los datos son correctos para enviar la solicitud."
                                    aria-invalid="<?= isset($fieldErrors['declaracion'])?'true':'false' ?>"
                                    aria-describedby="ayuda-declaracion"
                                    style="width:auto;margin:1px 0"
                                >Confirmo que los datos son correctos y autorizo al equipo a contactarme para coordinar una visita.
                            </label>
                            <small class="field-feedback" id="ayuda-declaracion" aria-live="polite"><?= e($fieldErrors['declaracion']??'') ?></small>
                        </div>

                        <!-- Enviar -->
                        <div class="field full">
                            <button class="btn" type="submit">Enviar solicitud</button>
                        </div>

                    </div>

</form>


               
                <aside class="timeline-panel">
                    <h3>¿Qué pasa después?</h3>

                    <div class="timeline">

                        <div class="timeline-step current">
                            <span class="step-dot"></span>
                            <div>
                                <strong>Enviás la solicitud</strong>
                                <small>Recibimos los datos y archivos de la propiedad.</small>
                            </div>
                        </div>

                        <div class="timeline-step">
                            <span class="step-dot"></span>
                            <div>
                                <strong>Revisión inicial</strong>
                                <small>Verificamos que la información esté completa.</small>
                            </div>
                        </div>

                        <div class="timeline-step">
                            <span class="step-dot"></span>
                            <div>
                                <strong>Un agente te asesora</strong>
                                <small>Coordinamos una visita y revisamos la documentación.</small>
                            </div>
                        </div>

                        <div class="timeline-step">
                            <span class="step-dot"></span>
                            <div>
                                <strong>Aprobación y publicación</strong>
                                <small>El administrador valida la publicación antes de mostrarla.</small>
                            </div>
                        </div>

                    </div>

                    <div class="hint-box">
                        Podés revisar el estado y el motivo de rechazo, si lo hubiera, en “Mis solicitudes”.
                    </div>
                </aside>

            </div>
        </div>
    </div>
</section>

<?php require dirname(__DIR__) . '/sistema/Vistas/Diseno/pie.php'; ?>
