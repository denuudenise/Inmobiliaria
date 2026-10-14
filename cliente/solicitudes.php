<?php
declare(strict_types=1);
require dirname(__DIR__) . '/sistema/Nucleo/inicio.php';
$user = require_client();
$requests = [];
$dbUnavailable = false;
try {
    $requests = (new SolicitudPropiedadDAO(Database::connection()))->findForClient((int)$user['id']);
} catch (PDOException $error) {
    error_log('No se pudieron cargar las solicitudes del cliente: ' . $error->getMessage());
    $dbUnavailable = true;
}
$pageTitle = 'Mis solicitudes';
$activePage = 'solicitudes';
require dirname(__DIR__) . '/sistema/Vistas/Diseno/encabezado.php';
?>
<section class="dashboard"><div class="dashboard-title"><div><h1>Mis solicitudes</h1><p>Seguí el estado de las propiedades que enviaste para publicar.</p></div><a class="btn" href="/Inmobiliaria/cliente/solicitar-propiedad.php">＋ &nbsp;Nueva solicitud</a></div><div class="dash-layout"><?php require dirname(__DIR__) . '/sistema/Vistas/Cliente/navegacion.php'; ?><div class="content-card"><h2>Solicitudes enviadas</h2><p class="content-subtitle">Te avisaremos cuando haya novedades sobre tu propiedad.</p>
  <?php if ($dbUnavailable): ?><div class="not-implemented">No se pudo consultar el historial. Revisá que MySQL esté iniciado y que hayas aplicado el esquema actualizado.</div>
  <?php elseif (!$requests): ?><div class="empty-state"><div class="empty-icon">▤</div><h3>Todavía no tenés solicitudes</h3><p>Cuando envíes una propiedad para publicar, vas a poder revisar su estado y los pasos de revisión desde acá.</p><a class="btn" href="/Inmobiliaria/cliente/solicitar-propiedad.php">Solicitar publicación</a></div>
  <?php else: ?><div class="table-wrap"><table class="table"><thead><tr><th>Propiedad</th><th>Operación</th><th>Enviada</th><th>Estado</th></tr></thead><tbody><?php foreach ($requests as $request): ?><tr><td><a class="text-link" href="/Inmobiliaria/cliente/solicitud.php?id=<?= $request['id_solicitud'] ?>"><?= e($request['titulo']) ?> →</a><br><small><?= e($request['localidad']) ?></small><?php if ($request['estado'] === 'Rechazado' && $request['motivo_rechazo']): ?><br><small>Motivo: <?= e($request['motivo_rechazo']) ?></small><?php endif; ?></td><td><?= e($request['operacion']) ?></td><td><?= e(date('d/m/Y', strtotime($request['fecha_solicitud']))) ?></td><td><span class="status <?= $request['estado'] === 'Rechazado' ? 'amber' : ($request['estado'] === 'Aprobado' ? '' : 'blue') ?>">● <?= e($request['estado']) ?></span></td></tr><?php endforeach; ?></tbody></table></div>
  <?php endif; ?>
</div></div></section>
<?php require dirname(__DIR__) . '/sistema/Vistas/Diseno/pie.php'; ?>

