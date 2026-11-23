<?php
require dirname(__DIR__) . '/sistema/Nucleo/inicio.php';

$user = require_client();
$id = (int)($_GET['id'] ?? 0);


$r = query_rows(
    'SELECT s.*,
            ev.nombre AS estado
     FROM solicitudes_publicacion s
     JOIN estados_verificacion ev ON ev.id_estado_verificacion=s.id_estado_verificacion
     WHERE s.id_solicitud=?
       AND s.id_cliente=?',
    [$id, $user['id']]
)[0] ?? null;

if (!$r) {
    http_response_code(404);
    ui_error('Solicitud no encontrada.');
}

$error = '';
$requestMedia=query_rows('SELECT id_multimedia,url_archivo,tipo FROM solicitud_multimedia WHERE id_solicitud=? ORDER BY orden,id_multimedia',[$id]);
$requestPhotos=array_values(array_filter($requestMedia,static fn($m)=>$m['tipo']==='Imagen'&&Archivos::isLocalFile((string)$m['url_archivo'])));
$requestVideos=array_values(array_filter($requestMedia,static fn($m)=>$m['tipo']==='Video'&&Archivos::isLocalFile((string)$m['url_archivo'])));


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try { $_POST=(new OfertaDAO(Database::connection()))->normalize($_POST,true); } catch (RuntimeException $e) { $error=$e->getMessage(); }

  
    if (!in_array((int)$r['id_estado_verificacion'], [2, 4], true)) {
        $error = 'La solicitud ya fue aprobada o cerrada.';

    } else {
        $title    = trim($_POST['titulo'] ?? '');
        $desc     = trim($_POST['descripcion'] ?? '');
        $price    = $_POST['precio'] ?? '';
        $currency = $_POST['moneda'] ?? '';

    
        if (
            $error !== '' || !$title
            || mb_strlen($title) > 200
            || mb_strlen($desc) < 10
            || !is_numeric($price)
            || (float)$price <= 0
            || (float)$price > 9999999999
            || !in_array($currency, ['USD', 'ARS'], true)
        ) {
            $error = 'Revisá el título, descripción, moneda y precio.';

  
        } else {
            $db = Database::connection();
            $paths = []; $videoPath=null; $oldVideoPaths=[]; $oldPhotoPaths=[];
            $db->beginTransaction();

            try {
               
                $check = query_rows(
                    'SELECT id_estado_verificacion
                     FROM solicitudes_publicacion
                     WHERE id_solicitud=?
                       AND id_cliente=?
                     FOR UPDATE',
                    [$id, $user['id']]
                )[0] ?? null;

                if (!$check || !in_array((int)$check['id_estado_verificacion'], [2, 4], true)) {
                    throw new RuntimeException('La solicitud ya fue procesada.');
                }

                $paths = Archivos::images('fotos');
                $removePhotoIds=array_values(array_unique(array_map('intval',(array)($_POST['eliminar_fotos']??[]))));
                $availablePhotoIds=array_map(static fn($photo)=>(int)$photo['id_multimedia'],$requestPhotos);
                foreach($removePhotoIds as $removeId){if($removeId<1||!in_array($removeId,$availablePhotoIds,true))throw new RuntimeException('Una de las fotos seleccionadas ya no pertenece a esta solicitud.');}
                if($removePhotoIds){$removed=array_flip($removePhotoIds);$requestPhotos=array_values(array_filter($requestPhotos,static function($photo)use($removed,&$oldPhotoPaths){if(isset($removed[(int)$photo['id_multimedia']])){$oldPhotoPaths[]=$photo['url_archivo'];return false;}return true;}));foreach($removePhotoIds as $removeId)execute_sql('DELETE FROM solicitud_multimedia WHERE id_multimedia=? AND id_solicitud=?',[$removeId,$id]);}
                $ordered=Archivos::orderedImages((string)($_POST['orden_fotos']??''),$requestPhotos,count($paths));

                execute_sql(
                    'UPDATE solicitudes_publicacion
                     SET titulo=?,
                         descripcion=?,
                         precio_estimado=?,
                         moneda=?,
                         id_estado_verificacion=2,
                         motivo_rechazo=NULL
                     WHERE id_solicitud=?
                       AND id_cliente=?',
                    [$title, $desc, $price, $currency, $id, $user['id']]
                );

                foreach($ordered as $position=>$item){if($item['kind']==='existing')execute_sql('UPDATE solicitud_multimedia SET orden=? WHERE id_multimedia=? AND id_solicitud=?',[$position+1,$item['id'],$id]);else execute_sql("INSERT INTO solicitud_multimedia (id_solicitud,url_archivo,tipo,orden) VALUES (?,?,'Imagen',?)",[$id,$paths[$item['index']],$position+1]);}
                $videoPath=Archivos::video('video');
                if($videoPath||!empty($_POST['eliminar_video'])){foreach($requestVideos as $oldVideo){execute_sql('DELETE FROM solicitud_multimedia WHERE id_multimedia=? AND id_solicitud=?',[$oldVideo['id_multimedia'],$id]);$oldVideoPaths[]=$oldVideo['url_archivo'];}if($videoPath)execute_sql("INSERT INTO solicitud_multimedia (id_solicitud,url_archivo,tipo,orden) VALUES (?,?,'Video',?)",[$id,$videoPath,count($requestPhotos)+count($paths)+1]);}

                $offers=new OfertaDAO($db);
                $offers->save('solicitud',$id,$_POST);
                if (isset($_POST['ofertas_form'])) {
                    $selected=$offers->read('solicitud',$id)[0];
                    execute_sql('UPDATE solicitudes_publicacion SET id_tipo_transaccion=? WHERE id_solicitud=?',[$selected['id_tipo_transaccion'],$id]);
                }
                $db->commit();
                Archivos::removeUnreferenced($db,[...$oldVideoPaths,...$oldPhotoPaths]);

                flash('Solicitud actualizada y enviada a revisión.');
                redirect('/Inmobiliaria/cliente/solicitud.php?id=' . $id);

            } catch (Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }

                Archivos::remove([...$paths,...($videoPath?[$videoPath]:[])]);

                $error = $e instanceof PDOException
                    ? 'No se pudo guardar la solicitud.'
                    : $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Detalle de solicitud';
require dirname(__DIR__) . '/sistema/Vistas/Diseno/encabezado.php';
?>

<section class="dashboard narrow">

    <a class="text-link" href="/Inmobiliaria/cliente/solicitudes.php">← Mis solicitudes</a>

    <h1 translate="no"><?= e($r['titulo']) ?></h1><?php $offerKind='solicitud'; $offerId=$id; require dirname(__DIR__).'/sistema/Vistas/Diseno/ofertas-resumen.php'; ?><p><a class="btn btn-outline" href="/Inmobiliaria/ficha-privada.php?tipo=solicitud&amp;id=<?= $id ?>">Documentación privada e impuestos</a></p>

    <?php notice(); ?>

    <?php if ($error): ?>
        <div class="notice error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="content-card">

        <span class="status blue"><?= e($r['estado']) ?></span>

        <p translate="no"><?= e($r['direccion'] . ', ' . $r['localidad']) ?></p>

     
        <?php if ($r['motivo_rechazo']): ?>
            <div class="notice error">Qué corregir: <?= e($r['motivo_rechazo']) ?></div>
        <?php endif; ?>


        <?php if (in_array((int)$r['id_estado_verificacion'], [2, 4], true)): ?>

            <form method="post" enctype="multipart/form-data" class="form-grid">
                <?php csrf_field(); ?>

             
                <div class="field full">
                    <label for="titulo">Título</label>
                    <input
                        id="titulo"
                        name="titulo"
                        value="<?= e((string)($_POST['titulo'] ?? $r['titulo'])) ?>"
                        required
                        maxlength="200"
                    >
                </div>

         
                <div class="field full">
                    <label for="descripcion">Descripción</label>
                    <textarea
                        id="descripcion"
                        name="descripcion"
                        minlength="10"
                        required
                    ><?= e((string)($_POST['descripcion'] ?? $r['descripcion'])) ?></textarea>
                </div>


           

         


                <?php $offerKind='solicitud'; $offerId=$id; require dirname(__DIR__).'/sistema/Vistas/Diseno/ofertas-form.php'; ?>
<div class="field full media-manager" data-media-manager>
    <h3>Orden de las fotos</h3><p class="help-text">La primera foto será la portada. Después ordená los ambientes principales y dejá los detalles y exteriores para el final.</p>
    <ol class="media-sort-list" data-sort-list><?php foreach($requestPhotos as $position=>$photo): ?><li data-media-item="e:<?= (int)$photo['id_multimedia'] ?>"><img src="<?= e($photo['url_archivo']) ?>" alt="Foto <?= $position+1 ?>"><span data-position-label><?= $position===0?'Portada':'Foto '.($position+1) ?></span><button type="button" data-move="up" aria-label="Subir foto">↑</button><button type="button" data-move="down" aria-label="Bajar foto">↓</button><button type="button" data-remove-media class="media-remove" aria-label="Eliminar foto">×</button></li><?php endforeach; ?></ol><input type="hidden" name="orden_fotos" value="" data-order-value>
    <label for="fotos">Agregar fotos (hasta 8 por carga, 2 MB por foto)</label><input id="fotos" name="fotos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple data-new-photos>
    <div class="media-video-box"><label for="video">Video opcional (MP4, hasta 20 MB)</label><?php if($requestVideos): ?><video controls preload="metadata" src="<?= e($requestVideos[0]['url_archivo']) ?>"></video><label class="check-row"><input type="checkbox" name="eliminar_video" value="1"> Eliminar el video actual</label><?php endif; ?><input id="video" name="video" type="file" accept="video/mp4" data-property-video><?php if($requestVideos): ?><small>Al elegir otro MP4, reemplazará el actual.</small><?php endif; ?></div>
</div>

            
                <div class="field full">
                    <button class="btn">Guardar y enviar a revisión</button>
                </div>
            </form>

        <?php else: ?>

           
            <p translate="no"><?= nl2br(e($r['descripcion'])) ?></p>

            <p>El equipo ya procesó esta solicitud. Consultá la publicación en Mis propiedades.</p>

            <a class="btn" href="/Inmobiliaria/cliente/actividad.php?seccion=propiedades">Mis propiedades</a>

        <?php endif; ?>

    </div>
</section>

<?php require dirname(__DIR__) . '/sistema/Vistas/Diseno/pie.php'; ?>
