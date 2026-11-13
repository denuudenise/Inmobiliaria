<?php
require __DIR__ . '/sistema/Nucleo/inicio.php';

$id = (int)($_GET['id'] ?? 0);
$dao = new PropiedadDAO(Database::connection());

$property = $dao->findById($id);
$propertyOffers = $property ? (new OfertaDAO(Database::connection()))->read('propiedad',$id) : [];
$error = '';

if (!$property) {
    http_response_code(404);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (!$property) {
        $error = 'Esta propiedad ya no está disponible.';

    } elseif (($_POST['accion'] ?? '') === 'enviar_oferta') {
        $user = require_client();
        try {
            (new SeguimientoDAO(Database::connection()))->submitOffer($user,$id,(int)($_POST['operacion']??0),$_POST);
            flash('Tu oferta fue enviada. El equipo la revisará y podrás ver el estado en tu panel.');
            redirect('/Inmobiliaria/cliente/ofertas.php');
        } catch (RuntimeException $exception) {
            $error=$exception instanceof PDOException?'No se pudo enviar la oferta. Intentá nuevamente.':$exception->getMessage();
        }

    } elseif (($_POST['accion'] ?? '') === 'enviar_consulta') {
        $message = trim((string)($_POST['mensaje'] ?? ''));
        $name = trim((string)($_POST['nombre'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['telefono'] ?? ''));

        if (mb_strlen($name) < 2 || mb_strlen($name) > 120 || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))
            || mb_strlen($email) > 190 || mb_strlen($phone) > 40 || ($phone === '' && $email === '')
            || mb_strlen($message) > 3000) {
            $error = 'Ingresá tu nombre y un correo válido o un teléfono para que podamos contactarte.';

        } else {
            execute_sql(
                "INSERT INTO contactos (id_cliente, id_agente, id_propiedad, tipo, descripcion, nombre_contacto, email_contacto, telefono_contacto)
                 SELECT ?, p.id_agente_creador, p.id_propiedad, 'Consulta', ?, ?, ?, ?
                 FROM propiedades p
                 WHERE p.id_propiedad=?",
                [current_user() && current_user()['role'] === 'Cliente' ? current_user()['id'] : null, $message, $name, $email, $phone, $id]
            );

            flash('¡Gracias! El agente de esta propiedad se pondrá en contacto con vos.');
            redirect('/Inmobiliaria/propiedad.php?id=' . $id . '#contacto');
        }
    }
}


$images = $property ? $dao->findImages($id) : [];
$images = array_values(array_filter(
    $images,
    static fn(array $media): bool => Archivos::isLocalFile((string)$media['url_archivo'])
));

$photos = array_values(
    array_filter($images, fn($m) => $m['tipo'] === 'Imagen')
);

$videos = array_values(
    array_filter($images, fn($m) => $m['tipo'] === 'Video')
);

$pageTitle = $property ? $property['titulo'] : 'Propiedad no disponible';
require __DIR__ . '/sistema/Vistas/Diseno/encabezado.php';
?>

<section class="dashboard">

    <?php notice(); ?>

    <a class="text-link" href="/Inmobiliaria/index.php#catalogo">← Volver al catálogo</a>

    <?php if (!$property): ?>

        <div class="empty-state">
            <h1>Propiedad no disponible</h1>
            <p>Puede haber sido retirada, vendida o alquilada.</p>
        </div>

    <?php else: ?>

        
        <div class="property-detail">

          
            <div class="gallery">
                <img
                    id="galleryMain"
                    class="gallery-main"
                    src="<?= e($photos[0]['url_archivo'] ?? '/Inmobiliaria/recursos/imagenes/propiedad.svg') ?>"
                    alt="<?= e($property['titulo']) ?>"
                >

                <div class="gallery-thumbs">
                    <?php foreach ($photos as $i => $photo): ?>
                        <button
                            type="button"
                            data-gallery="<?= e($photo['url_archivo']) ?>"
                            aria-label="Ver foto <?= $i + 1 ?>"
                            aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>"
                        >
                            <img src="<?= e($photo['url_archivo']) ?>" alt="Vista <?= $i + 1 ?>">
                        </button>
                    <?php endforeach; ?>
                </div>

                <?php foreach ($videos as $video): ?>
                    <video controls preload="metadata" src="<?= e($video['url_archivo']) ?>"></video>
                <?php endforeach; ?>
            </div>

         
            <div class="content-card property-summary">
                <span class="status blue"><?= e($property['tipo']) ?></span>

                <h1><?= e($property['titulo']) ?></h1>
                <p><?= e($property['localidad']) ?></p>

                <div class="offer-summary">
<?php foreach ((new OfertaDAO(Database::connection()))->read('propiedad',$id) as $offer): ?><p><strong><?= e($offer['nombre']) ?>:</strong> <?= e($offer['moneda'].' '.number_format((float)$offer['precio'],2,',','.')) ?><?= (int)$offer['id_tipo_transaccion']!==1?' / mes':'' ?></p><?php endforeach; ?>
</div>

                <dl class="features">
                    <div>
                        <dt>Dormitorios</dt>
                        <dd><?= e((string)($property['dormitorios'] ?? '—')) ?></dd>
                    </div>
                    <div>
                        <dt>Baños</dt>
                        <dd><?= e((string)($property['banos'] ?? '—')) ?></dd>
                    </div>
                    <div>
                        <dt>Superficie</dt>
                        <dd><?= e((string)$property['superficie_total_m2']) ?> m²</dd>
                    </div>
                    <div>
                        <dt>Ambientes</dt>
                        <dd><?= e((string)($property['ambientes'] ?? '—')) ?></dd>
                    </div>
                </dl>

                <div class="actions">
                    <a class="btn" href="#contacto">Contactar</a>

                    <?php if (!current_user() || current_user()['role'] === 'Cliente'): ?>
                        <button
                            class="btn btn-outline"
                            data-favorite="<?= $id ?>"
                            aria-label="Guardar propiedad en favoritos"
                        >♡</button>
                    <?php endif; ?>
                </div>
            </div>

        </div>


      
        <div class="detail-lower">

           
            <div class="content-card">
                <h2>Sobre esta propiedad</h2>

                <p class="description"><?= nl2br(e($property['descripcion'])) ?></p>

                <?php
                $features = query_rows(
                    'SELECT c.nombre
                     FROM propiedad_caracteristicas pc
                     JOIN caracteristicas c ON c.id_caracteristica=pc.id_caracteristica
                     WHERE pc.id_propiedad=?',
                    [$id]
                );

                if ($features):
                ?>
                    <h3>Características</h3>

                    <div class="actions">
                        <?php foreach ($features as $f): ?>
                            <span class="status"><?= e($f['nombre']) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <details class="record">
                    <summary>Ubicación y otros datos</summary>

                    <div class="record-body">
                        <p><?= e($property['direccion'] . ', ' . $property['localidad']) ?></p>

                        <p>Antigüedad: <?= (int)$property['antiguedad_anos'] ?> años</p>

                        <a
                            class="text-link"
                            target="_blank"
                            rel="noopener"
                            href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($property['direccion'] . ', ' . $property['localidad'] . ', Argentina') ?>"
                        >Ver ubicación en el mapa ↗</a>
                    </div>
                </details>
            </div>


           
            <div class="content-card" id="contacto">
                <h2>¿Te interesa?</h2>
                <p class="content-subtitle">Consultá por una visita o pedí más información.</p>

                <?php if ($error): ?>
                    <div class="notice error" role="alert"><?= e($error) ?></div>
                <?php endif; ?>

                <?php if (current_user() && current_user()['role'] === 'Cliente'): ?>

                    <details class="record offer-submit-card">
                        <summary><strong>Hacer una oferta</strong></summary>
                        <div class="record-body">
                            <p class="help-text">La oferta queda pendiente de revisión. No reserva la propiedad automáticamente.</p>
                            <?php if ($propertyOffers): ?><form method="post" class="form-grid">
                                <?php csrf_field(); ?><input type="hidden" name="accion" value="enviar_oferta">
                                <div class="field"><label for="oferta-operacion">Operación</label><select id="oferta-operacion" name="operacion" required><?php foreach($propertyOffers as $offer): ?><option value="<?= (int)$offer['id_tipo_transaccion'] ?>"><?= e($offer['nombre'].' · '.$offer['moneda'].' '.number_format((float)$offer['precio'],2,',','.')) ?></option><?php endforeach; ?></select></div>
                                <div class="field"><label for="oferta-monto">Monto de tu oferta</label><input id="oferta-monto" name="monto" type="number" min="0.01" max="9999999999" step="0.01" required><small>Se usará la moneda de la operación seleccionada.</small></div>
                                <div class="field"><label for="oferta-vencimiento">Válida hasta (opcional)</label><input id="oferta-vencimiento" name="fecha_vencimiento" type="date" min="<?= e(date('Y-m-d')) ?>"></div>
                                <div class="field full"><label for="oferta-observaciones">Condiciones o comentario</label><textarea id="oferta-observaciones" name="observaciones" maxlength="3000"></textarea></div>
                                <div class="field full"><button class="btn">Enviar oferta</button></div>
                            </form><?php else: ?><p>No hay operaciones disponibles para ofertar.</p><?php endif; ?>
                        </div>
                    </details>

                    <form method="post" class="modal-form">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="accion" value="enviar_consulta">

                        <div class="field"><label for="nombre">Tu nombre</label><input id="nombre" name="nombre" required minlength="2" maxlength="120" value="<?= e(current_user()['name'] ?? '') ?>"></div>
                        <div class="field"><label for="email">Correo electrónico</label><input id="email" name="email" type="email" maxlength="190" value="<?= e(current_user()['email'] ?? '') ?>"></div>
                        <div class="field"><label for="telefono">Teléfono</label><input id="telefono" name="telefono" type="tel" maxlength="40" value="<?= e(current_user()['phone'] ?? '') ?>"><small>Dejanos un correo o un teléfono.</small></div>

                        <div class="field">
                            <label for="mensaje">Mensaje (opcional)</label>
                            <textarea
                                id="mensaje"
                                name="mensaje"
                                maxlength="3000"
                                placeholder="Hola, me gustaría coordinar una visita…"
                            ><?= e((string)($_POST['mensaje'] ?? '')) ?></textarea>
                        </div>

                        <button class="btn">Enviar consulta</button>
                    </form>

                    <?php if (current_user() && current_user()['role'] === 'Cliente'): ?><p><a class="text-link" href="/Inmobiliaria/cliente/actividad.php?seccion=consultas">Ver mis consultas</a></p><?php endif; ?>

                <?php elseif (!current_user()): ?>

                   
                    <p>Dejanos tus datos de contacto y el agente de la propiedad se comunicará con vos. No necesitás registrarte.</p>
                    <form method="post" class="modal-form">
                        <?php csrf_field(); ?><input type="hidden" name="accion" value="enviar_consulta">
                        <div class="field"><label for="nombre">Tu nombre</label><input id="nombre" name="nombre" required minlength="2" maxlength="120" value="<?= e((string)($_POST['nombre'] ?? '')) ?>"></div>
                        <div class="field"><label for="email">Correo electrónico</label><input id="email" name="email" type="email" maxlength="190" value="<?= e((string)($_POST['email'] ?? '')) ?>"></div>
                        <div class="field"><label for="telefono">Teléfono</label><input id="telefono" name="telefono" type="tel" maxlength="40" value="<?= e((string)($_POST['telefono'] ?? '')) ?>"><small>Dejanos un correo o un teléfono.</small></div>
                        <div class="field"><label for="mensaje">Mensaje (opcional)</label><textarea id="mensaje" name="mensaje" maxlength="3000" placeholder="Hola, me gustaría coordinar una visita…"><?= e((string)($_POST['mensaje'] ?? '')) ?></textarea></div>
                        <button class="btn">Enviar consulta</button>
                    </form>

                <?php else: ?>

                   
                    <p>Estás viendo el catálogo como <?= e(current_user()['role']) ?>.</p>
                    <a class="btn" href="<?= e(role_destination(current_user()['role'])) ?>?seccion=consultas">Gestionar consultas</a>

                <?php endif; ?>
            </div>

        </div>

    <?php endif; ?>

</section>

<?php require __DIR__ . '/sistema/Vistas/Diseno/pie.php'; ?>
