<?php
declare(strict_types=1);
require __DIR__ . '/sistema/Nucleo/inicio.php';

$pageTitle = 'Encontrá tu próximo hogar';
$roleNotice = '';
if (isset($_GET['ver_rol']) && isset($_SESSION['role_notice'])) {
    $roleNotice = (string)$_SESSION['role_notice'];
    unset($_SESSION['role_notice']);
}
$filters = [
    'location' => trim((string)($_GET['ubicacion'] ?? '')),
    'type' => trim((string)($_GET['tipo'] ?? '')),
    'operation' => trim((string)($_GET['operacion'] ?? '')),
];
$properties = [];
$dbUnavailable = false;
try {
    $properties = (new PropiedadDAO(Database::connection()))->findPublished($filters);
} catch (PDOException $error) {
    error_log('No se pudo cargar el catálogo: ' . $error->getMessage());
    $dbUnavailable = true;
}
require __DIR__ . '/sistema/Vistas/Diseno/encabezado.php';
?>
<section class="home-hero"><div class="hero-inner">
  <h1>Encontrá la propiedad<br>que estás buscando</h1>
  <p>Casas, departamentos, terrenos y mucho más.</p>
  <form class="search-box" action="/Inmobiliaria/index.php" method="get">
    <label class="search-location"><span>⌕</span><input name="ubicacion" value="<?= e($filters['location']) ?>" placeholder="Buscar por ubicación, barrio o localidad…" aria-label="Ubicación"></label>
    <select name="tipo" aria-label="Tipo de propiedad"><option value="">Tipo de propiedad</option><?php foreach (['Casa','Departamento','Local','Oficina','Terreno','Cochera'] as $type): ?><option value="<?= e($type) ?>" <?= $filters['type'] === $type ? 'selected' : '' ?>><?= e($type) ?></option><?php endforeach; ?></select>
    <select name="operacion" aria-label="Operación"><option value="">Comprar o alquilar</option><?php foreach (['Venta','Alquiler','Alquiler Temporario'] as $operation): ?><option value="<?= e($operation) ?>" <?= $filters['operation'] === $operation ? 'selected' : '' ?>><?= e($operation) ?></option><?php endforeach; ?></select>
    <button class="btn" type="submit">⌕ &nbsp;Buscar</button>
  </form>
</div></section>
<?php if ($roleNotice !== ''): ?><section class="section" style="margin-bottom:0"><div class="hint-box" role="status">Inicio de sesión correcto. El sistema detectó el rol <strong><?= e($roleNotice) ?></strong> y redirigió al inicio. En esta etapa está desarrollado únicamente el panel del cliente.</div></section><?php endif; ?>
<section class="section" id="catalogo">
  <div class="section-heading"><div><h2>Propiedades destacadas</h2><p>Opciones seleccionadas para encontrar tu próximo hogar.</p></div><a class="text-link" href="/Inmobiliaria/index.php#catalogo">Ver todas →</a></div>
  <?php if ($dbUnavailable): ?>
    <div class="content-card"><h3>Estamos preparando el catálogo</h3><p>La conexión con MySQL todavía no está disponible. Iniciá MySQL desde el panel de XAMPP y cargá el esquema de <code>base_datos/esquema.sql</code>.</p></div>
  <?php elseif (!$properties): ?>
    <div class="empty-state" style="background:#fffefa;border:1px solid var(--line);border-radius:13px"><div class="empty-icon">⌂</div><h3>No hay propiedades publicadas con esos filtros</h3><p>Las propiedades aparecen aquí luego de ser aprobadas por el equipo. Probá modificar la búsqueda.</p><a class="btn" href="/Inmobiliaria/index.php#catalogo">Ver catálogo</a></div>
  <?php else: ?>
    <div class="property-grid">
      <?php foreach ($properties as $property):
        $id = (int)$property['id_propiedad'];
        $image = $property['imagen'] ?: 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=900&q=82';
        $currency = $property['moneda'] === 'ARS' ? 'ARS' : 'USD';
        $price = number_format((float)$property['precio'], 0, ',', '.');
      ?>
        <article class="property-card">
          <div class="property-photo" style="background-image:url('<?= e($image) ?>')"><a href="/Inmobiliaria/propiedad.php?id=<?= $id ?>" aria-label="Ver <?= e($property['titulo']) ?>"><span class="pill"><?= e($property['operacion']) ?></span></a><button class="heart" data-favorite="<?= $id ?>" aria-label="Guardar en favoritos">♡</button></div>
          <div class="property-info"><h3><?= e($property['titulo']) ?></h3><div class="property-place">⌖ &nbsp;<?= e($property['localidad']) ?></div>
            <div class="property-features"><span>▱ &nbsp;<?= e((string)($property['ambientes'] ?? '—')) ?> amb.</span><span>♧ &nbsp;<?= e((string)($property['dormitorios'] ?? '—')) ?> dorm.</span><span>▣ &nbsp;<?= e((string)$property['superficie_total_m2']) ?> m²</span></div>
            <div class="price"><?= e($currency) ?> <?= e($price) ?><?= $property['operacion'] === 'Alquiler' ? ' <small>/mes</small>' : '' ?></div>
            <a class="btn" style="display:block;text-align:center" href="/Inmobiliaria/propiedad.php?id=<?= $id ?>">Ver detalle</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<div class="trust-strip"><div class="trust-item"><span>⌂</span><div><strong>Compra y venta</strong><small>Encontrá tu lugar</small></div></div><div class="trust-item"><span>♢</span><div><strong>Transacciones seguras</strong><small>Te acompañamos en cada paso</small></div></div><div class="trust-item"><span>☆</span><div><strong>Atención personalizada</strong><small>Estamos para ayudarte</small></div></div></div>
<?php require __DIR__ . '/sistema/Vistas/Diseno/pie.php'; ?>

