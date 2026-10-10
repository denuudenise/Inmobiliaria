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
  <?php else: ?><div class="table-wrap"><table class="table"><thead><tr><th>Propiedad</th><th>Operación</th><th>Enviada</th><th>Estado</th></tr></thead><tbody><?php foreach ($requests as $request): ?><tr><td><strong><?= e($request['titulo']) ?></strong><br><small><?= e($request['localidad']) ?></small><?php if ($request['estado'] === 'Rechazada' && $request['motivo_rechazo']): ?><br><small>Motivo: <?= e($request['motivo_rechazo']) ?></small><?php endif; ?></td><td><?= e($request['operacion']) ?></td><td><?= e(date('d/m/Y', strtotime($request['fecha_solicitud']))) ?></td><td><span class="status <?= $request['estado'] === 'Rechazada' ? 'amber' : ($request['estado'] === 'Aprobada' ? '' : 'blue') ?>">● <?= e($request['estado']) ?></span></td></tr><?php endforeach; ?></tbody></table></div>
    <div class="timeline-panel" style="margin-top:18px"><h3>Etapas de revisión</h3><div class="timeline"><div class="timeline-step done"><span class="step-dot"></span><div><strong>Solicitud enviada</strong><small>Recibimos tu información y archivos.</small></div></div><div class="timeline-step current"><span class="step-dot"></span><div><strong>Revisión del equipo</strong><small>Validamos los datos antes de coordinar la visita.</small></div></div><div class="timeline-step"><span class="step-dot"></span><div><strong>Contacto con un agente</strong><small>Un agente te asesorará y revisará los detalles.</small></div></div><div class="timeline-step"><span class="step-dot"></span><div><strong>Aprobación y publicación</strong><small>El administrador aprueba la publicación para el catálogo.</small></div></div></div></div>
  <?php endif; ?>
</div></div></section>
<?php require dirname(__DIR__) . '/sistema/Vistas/Diseno/pie.php'; ?>

