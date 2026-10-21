<?php
declare(strict_types=1);
require dirname(__DIR__) . '/sistema/Nucleo/inicio.php';
$user = require_client();
$pageTitle = 'Panel del cliente';
$activePage = 'inicio';
$properties = [];
$requests = [];
$dbUnavailable = false;
try {
    $db = Database::connection();
    $properties = (new PropiedadDAO($db))->findPublished([], 3);
    $requests = (new SolicitudPropiedadDAO($db))->findForClient((int)$user['id']);
} catch (PDOException $error) {
    error_log('No se pudo cargar el panel del cliente: ' . $error->getMessage());
    $dbUnavailable = true;
}
require dirname(__DIR__) . '/sistema/Vistas/Diseno/encabezado.php';
?>
<section class="dashboard"><?php notice(); ?><div class="dashboard-title"><div><h1>Hola, <?= e($user['name']) ?> 👋</h1><p>Este es el resumen de tu cuenta en Nuevo Hogar.</p></div><a class="btn" href="/Inmobiliaria/cliente/solicitar-propiedad.php">＋ &nbsp;Publicar propiedad</a></div>
  <div class="dash-layout"><?php require dirname(__DIR__) . '/sistema/Vistas/Cliente/navegacion.php'; ?><div class="content-card"><h2>Tu actividad</h2><p class="content-subtitle">Un vistazo rápido a tus operaciones y publicaciones.</p>
    <?php if ($dbUnavailable): ?><div class="not-implemented">No hay conexión con MySQL. Iniciá MySQL desde XAMPP y cargá el esquema <code>base_datos/esquema.sql</code>.</div><?php else: ?>
      <div class="mini-stat-grid"><div class="mini-stat"><strong><?= count($requests) ?></strong><span>Solicitudes de publicación</span></div><div class="mini-stat"><strong><?= (int)$db->query("SELECT COUNT(*) FROM intereses WHERE id_cliente=".(int)$user['id']." AND es_favorito=1")->fetchColumn() ?></strong><span>Propiedades favoritas</span></div><div class="mini-stat"><strong><?= count(array_filter($requests, static fn($request) => $request['estado'] === 'Pendiente de Verificación')) ?></strong><span>En revisión</span></div></div>
      <div class="data-box"><h3>Solicitudes recientes</h3><?php if (!$requests): ?><p style="color:var(--muted);margin:0 0 12px">Todavía no enviaste solicitudes de publicación.</p><a class="text-link" href="/Inmobiliaria/cliente/solicitar-propiedad.php">Publicá tu propiedad →</a><?php else: ?><div class="table-wrap"><table class="table"><thead><tr><th>Propiedad</th><th>Operación</th><th>Enviada</th><th>Estado</th></tr></thead><tbody><?php foreach (array_slice($requests, 0, 4) as $request): ?><tr><td><strong><?= e($request['titulo']) ?></strong><br><small><?= e($request['localidad']) ?></small></td><td><?= e($request['operacion']) ?></td><td><?= e(date('d/m/Y', strtotime($request['fecha_solicitud']))) ?></td><td><span class="status <?= $request['estado'] === 'Rechazado' ? 'amber' : 'blue' ?>">● <?= e($request['estado']) ?></span></td></tr><?php endforeach; ?></tbody></table></div><p style="margin-bottom:0"><a class="text-link" href="/Inmobiliaria/cliente/solicitudes.php">Ver todas las solicitudes →</a></p><?php endif; ?></div>
    <?php endif; ?>
  </div></div>
</section>
<?php require dirname(__DIR__) . '/sistema/Vistas/Diseno/pie.php'; ?>

