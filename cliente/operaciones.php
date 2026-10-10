<?php
declare(strict_types=1);
require dirname(__DIR__) . '/sistema/Nucleo/inicio.php';
$user = require_client();
$sales = [];
$rentals = [];
$dbUnavailable = false;
try {
    $dao = new PropiedadDAO(Database::connection());
    $sales = $dao->findClosedOperationsForClient((int)$user['id']);
    $rentals = $dao->findRentalsForClient((int)$user['id']);
} catch (PDOException $error) {
    error_log('No se pudieron cargar las operaciones del cliente: ' . $error->getMessage());
    $dbUnavailable = true;
}
$pageTitle = 'Mis operaciones';
$activePage = 'operaciones';
require dirname(__DIR__) . '/sistema/Vistas/Diseno/encabezado.php';
?>
<section class="dashboard"><div class="dashboard-title"><div><h1>Mis operaciones</h1><p>Consultá tus compras y alquileres.</p></div><a class="btn" href="/Inmobiliaria/index.php#catalogo">Explorar propiedades</a></div><div class="dash-layout"><?php require dirname(__DIR__) . '/sistema/Vistas/Cliente/navegacion.php'; ?><div class="content-card"><h2>Compras y alquileres</h2><p class="content-subtitle">Seguimiento de tus operaciones registradas en Nuevo Hogar.</p>
  <?php if ($dbUnavailable): ?><div class="not-implemented">No se pudo consultar el historial. Revisá que MySQL esté iniciado y que las migraciones del proyecto estén aplicadas.</div><?php else: ?>
    <div class="mini-stat-grid"><div class="mini-stat"><strong><?= count($sales) + count(array_filter($rentals, static fn($r) => $r['estado'] === 'Activo')) ?></strong><span>Operaciones activas o concretadas</span></div><div class="mini-stat"><strong><?= count($sales) ?></strong><span>Compras concretadas</span></div><div class="mini-stat"><strong><?= count(array_filter($rentals, static fn($r) => $r['estado'] === 'Activo')) ?></strong><span>Alquileres activos</span></div></div>
    <div class="data-box" style="margin-bottom:16px"><h3>Compras</h3><?php if (!$sales): ?><p style="color:var(--muted);margin:0">Todavía no hay compras registradas a tu nombre.</p><?php else: ?><div class="table-wrap"><table class="table"><thead><tr><th>Propiedad</th><th>Fecha de cierre</th><th>Precio final</th><th>Agente</th></tr></thead><tbody><?php foreach ($sales as $sale): ?><tr><td><strong><?= e($sale['titulo']) ?></strong><br><small><?= e($sale['localidad']) ?></small></td><td><?= e(date('d/m/Y', strtotime($sale['fecha_cierre']))) ?></td><td><?= e(number_format((float)$sale['precio_final'], 0, ',', '.')) ?></td><td><?= e($sale['agente_nombre'] . ' ' . $sale['agente_apellido']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div>
    <div class="data-box"><h3>Alquileres</h3><?php if (!$rentals): ?><p style="color:var(--muted);margin:0">Todavía no hay alquileres registrados a tu nombre.</p><?php else: ?><div class="table-wrap"><table class="table"><thead><tr><th>Propiedad</th><th>Período</th><th>Importe mensual</th><th>Estado</th></tr></thead><tbody><?php foreach ($rentals as $rental): ?><tr><td><strong><?= e($rental['titulo']) ?></strong><br><small><?= e($rental['localidad']) ?></small></td><td><?= e(date('d/m/Y', strtotime($rental['fecha_inicio']))) ?><?= $rental['fecha_fin'] ? ' – ' . e(date('d/m/Y', strtotime($rental['fecha_fin']))) : '' ?></td><td><?= e(number_format((float)$rental['monto_mensual'], 0, ',', '.')) ?></td><td><span class="status <?= $rental['estado'] === 'Activo' ? '' : 'gray' ?>">● <?= e($rental['estado']) ?></span></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div>
  <?php endif; ?>
</div></div></section>
<?php require dirname(__DIR__) . '/sistema/Vistas/Diseno/pie.php'; ?>

