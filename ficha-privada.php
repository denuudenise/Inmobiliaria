<?php
declare(strict_types=1);
require __DIR__.'/sistema/Nucleo/inicio.php';
$user=require_user(); $db=Database::connection(); $dao=new ExpedienteDAO($db);
$kind=(string)($_GET['tipo']??''); $id=(int)($_GET['id']??0); $error='';
try { $context=$dao->context($kind,$id,$user); }
catch (RuntimeException $e) { http_response_code(404); exit('Ficha no disponible para tu usuario.'); }
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    try {
        $db->beginTransaction(); $context=$dao->context($kind,$id,$user,true);
        $dao->save($context['property'],$context['request'],(int)$user['id'],$_POST);
        $db->commit(); flash('Documentación e impuestos guardados correctamente.');
        redirect('/Inmobiliaria/ficha-privada.php?tipo='.urlencode($kind).'&id='.$id);
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack(); $dao->cleanup();
        $error=$e instanceof PDOException?'No se pudo guardar la ficha.':$e->getMessage();
    }
}
$records=$dao->records($context); $pageTitle='Documentación e impuestos';
$documentState=$context;
require __DIR__.'/sistema/Vistas/Diseno/encabezado.php';
?>
<section class="dashboard narrow private-page">
<a class="text-link" href="<?= e(role_destination($user['role'])) ?>">Volver a mi panel</a>
<h1>Documentación e impuestos</h1><p><?= e($context['title']) ?></p>
<?php notice(); if ($error): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>
<section class="content-card"><h2>Documentación privada</h2>
<?php if (!empty($context['faltan_planos']) || !empty($context['falta_escritura'])): ?><div class="notice"><strong>Documentación pendiente:</strong> <?= !empty($context['faltan_planos'])?'planos':'' ?><?= !empty($context['faltan_planos']) && !empty($context['falta_escritura'])?' y ':'' ?><?= !empty($context['falta_escritura'])?'escritura':'' ?>. <?php if ($context['observaciones_documentacion']!==''): ?><?= e($context['observaciones_documentacion']) ?><?php endif; ?></div><?php endif; ?>
<?php if (!$records['documentos']): ?><p>No hay documentos cargados.</p><?php endif; ?>
<ul class="private-documents"><?php foreach ($records['documentos'] as $doc): ?><li><strong><?= e($doc['tipo']) ?></strong> — <a href="/Inmobiliaria/documento.php?id=<?= (int)$doc['id_documento'] ?>"><?= e($doc['nombre']) ?></a><small> · <?= e($doc['fecha']) ?></small></li><?php endforeach; ?></ul></section>
<section class="content-card"><h2>Impuestos registrados</h2>
<?php if (!$records['propiedad_impuestos']): ?><p>No hay impuestos cargados.</p><?php else: ?>
<div class="table-wrap"><table class="table"><thead><tr><th>Concepto</th><th>Período</th><th>Base</th><th>Porcentaje</th><th>Importe</th></tr></thead><tbody>
<?php foreach ($records['propiedad_impuestos'] as $tax): ?><tr><td><?= e($tax['concepto']) ?></td><td><?= e($tax['periodo']) ?></td><td><?= e($tax['moneda'].' '.number_format((float)$tax['base'],2,',','.')) ?></td><td><?= e($tax['porcentaje']) ?> %</td><td><?= e($tax['moneda'].' '.number_format((float)$tax['importe'],2,',','.')) ?></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>
<section class="content-card"><h2>Agregar documentación o impuestos</h2><form method="post" enctype="multipart/form-data" class="form-grid"><?php csrf_field(); require __DIR__.'/sistema/Vistas/Diseno/expediente-form.php'; ?><div class="full"><button class="btn">Guardar en la ficha</button></div></form></section>
</section>
<?php require __DIR__.'/sistema/Vistas/Diseno/pie.php'; ?>
