<?php
require dirname(__DIR__).'/sistema/Nucleo/inicio.php'; $user=require_client(); $id=(int)($_GET['id']??0);
$r=query_rows('SELECT s.*,ev.nombre AS estado FROM solicitudes_publicacion s JOIN estados_verificacion ev ON ev.id_estado_verificacion=s.id_estado_verificacion WHERE s.id_solicitud=? AND s.id_cliente=?',[$id,$user['id']])[0]??null;
if (!$r) { http_response_code(404); exit('Solicitud no encontrada.'); }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 verify_csrf();
 if (!in_array((int)$r['id_estado_verificacion'],[2,4],true)) $error='La solicitud ya fue aprobada o cerrada.';
 else {
  $title=trim($_POST['titulo']??''); $desc=trim($_POST['descripcion']??''); $price=$_POST['precio']??''; $currency=$_POST['moneda']??'';
  if (!$title || mb_strlen($title)>200 || mb_strlen($desc)<10 || !is_numeric($price) || (float)$price<=0 || (float)$price>9999999999 || !in_array($currency,['USD','ARS'],true)) $error='Revisá el título, descripción, moneda y precio.';
  else {
   $db=Database::connection(); $paths=[]; $db->beginTransaction();
   try {
    $check=query_rows('SELECT id_estado_verificacion FROM solicitudes_publicacion WHERE id_solicitud=? AND id_cliente=? FOR UPDATE',[$id,$user['id']])[0]??null;
    if (!$check || !in_array((int)$check['id_estado_verificacion'],[2,4],true)) throw new RuntimeException('La solicitud ya fue procesada.');
    $paths=Archivos::images('fotos');
    execute_sql('UPDATE solicitudes_publicacion SET titulo=?,descripcion=?,precio_estimado=?,moneda=?,id_estado_verificacion=2,motivo_rechazo=NULL WHERE id_solicitud=? AND id_cliente=?',[$title,$desc,$price,$currency,$id,$user['id']]);
    foreach($paths as $path)execute_sql("INSERT INTO solicitud_multimedia (id_solicitud,url_archivo,tipo) VALUES (?,?,'Imagen')",[$id,$path]);
    $db->commit(); flash('Solicitud actualizada y enviada a revisión.'); redirect('/Inmobiliaria/cliente/solicitud.php?id='.$id);
   } catch(Throwable $e) { if($db->inTransaction())$db->rollBack(); Archivos::remove($paths); $error=$e instanceof PDOException?'No se pudo guardar la solicitud.':$e->getMessage(); }
  }
 }
}
$pageTitle='Detalle de solicitud'; require dirname(__DIR__).'/sistema/Vistas/Diseno/encabezado.php';
?>
<section class="dashboard narrow"><a class="text-link" href="/Inmobiliaria/cliente/solicitudes.php">← Mis solicitudes</a><h1><?= e($r['titulo']) ?></h1><?php notice(); if($error): ?><div class="notice error" role="alert"><?= e($error) ?></div><?php endif; ?><div class="content-card"><span class="status blue"><?= e($r['estado']) ?></span><p><?= e($r['direccion'].', '.$r['localidad']) ?></p><?php if($r['motivo_rechazo']): ?><div class="notice error">Qué corregir: <?= e($r['motivo_rechazo']) ?></div><?php endif; ?>
<?php if (in_array((int)$r['id_estado_verificacion'],[2,4],true)): ?><form method="post" enctype="multipart/form-data" class="form-grid"><?php csrf_field(); ?><div class="field full"><label for="titulo">Título</label><input id="titulo" name="titulo" value="<?= e((string)($_POST['titulo']??$r['titulo'])) ?>" required maxlength="200"></div><div class="field full"><label for="descripcion">Descripción</label><textarea id="descripcion" name="descripcion" minlength="10" required><?= e((string)($_POST['descripcion']??$r['descripcion'])) ?></textarea></div><div class="field"><label for="precio">Precio</label><input id="precio" name="precio" type="number" min="0.01" step="0.01" value="<?= e((string)($_POST['precio']??$r['precio_estimado'])) ?>" required></div><div class="field"><label for="moneda">Moneda</label><select id="moneda" name="moneda"><?php foreach(['USD','ARS'] as $m): ?><option <?= ($_POST['moneda']??$r['moneda'])===$m?'selected':'' ?>><?= $m ?></option><?php endforeach; ?></select></div><div class="field full"><label for="fotos">Agregar fotos (JPG, PNG o WEBP, hasta 2 MB cada una)</label><input id="fotos" type="file" name="fotos[]" accept="image/jpeg,image/png,image/webp" multiple></div><div class="field full"><button class="btn">Guardar y enviar a revisión</button></div></form><?php else: ?><p><?= nl2br(e($r['descripcion'])) ?></p><p>El equipo ya procesó esta solicitud. Consultá la publicación en Mis propiedades.</p><a class="btn" href="/Inmobiliaria/cliente/actividad.php?seccion=propiedades">Mis propiedades</a><?php endif; ?></div></section>
<?php require dirname(__DIR__).'/sistema/Vistas/Diseno/pie.php'; ?>
