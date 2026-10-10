<?php
declare(strict_types=1);
require __DIR__ . '/sistema/Nucleo/inicio.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$property = null;
$images = [];
$dbUnavailable = false;
if ($id) {
    try {
        $dao = new PropiedadDAO(Database::connection());
        $property = $dao->findById($id);
        if ($property) $images = $dao->findImages($id);
    } catch (PDOException $error) {
        error_log('No se pudo cargar la propiedad: ' . $error->getMessage());
        $dbUnavailable = true;
    }
}
$pageTitle = $property ? $property['titulo'] : 'Detalle de propiedad';
require __DIR__ . '/sistema/Vistas/Diseno/encabezado.php';
?>
<section class="dashboard">
  <?php if ($dbUnavailable): ?><div class="content-card"><h2>Detalle no disponible</h2><p>No hay conexión con la base de datos en este momento.</p><a class="btn" href="/Inmobiliaria/index.php">Volver al catálogo</a></div>
  <?php elseif (!$property): ?><div class="content-card"><h2>Propiedad no encontrada</h2><p>La propiedad puede haber sido retirada o ya no estar disponible.</p><a class="btn" href="/Inmobiliaria/index.php">Volver al catálogo</a></div>
  <?php else: ?>
    <?php $heroImage = $images[0]['url_archivo'] ?? 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1400&q=85'; ?>
    <a class="text-link" href="/Inmobiliaria/index.php#catalogo">← Volver a propiedades</a>
    <div class="content-card" style="margin-top:15px"><div class="property-photo" style="height:min(52vw,440px);border-radius:12px;background-image:url('<?= e($heroImage) ?>')"><span class="pill"><?= e($property['operacion']) ?></span></div>
      <?php if (count($images) > 1): ?><div style="display:flex;gap:8px;margin-top:10px;overflow:auto"><?php foreach (array_slice($images,1) as $media): ?><img src="<?= e($media['url_archivo']) ?>" alt="Foto de <?= e($property['titulo']) ?>" style="width:130px;height:84px;object-fit:cover;border-radius:8px"><?php endforeach; ?></div><?php endif; ?>
      <div class="data-grid" style="margin-top:22px"><div><h1 style="margin:0 0 8px"><?= e($property['titulo']) ?></h1><p class="property-place">⌖ &nbsp;<?= e($property['direccion']) ?>, <?= e($property['localidad']) ?></p><p style="line-height:1.7;color:#566c78"><?= nl2br(e($property['descripcion'] ?: 'Consultanos para conocer más sobre esta propiedad.')) ?></p></div><div class="data-box"><span class="status blue"><?= e($property['operacion']) ?> · <?= e($property['tipo']) ?></span><p class="price" style="font-size:25px;margin-top:17px"><?= e($property['moneda']) ?> <?= e(number_format((float)$property['precio'], 0, ',', '.')) ?></p><div class="data-row">▣ &nbsp; Superficie total: <?= e((string)$property['superficie_total_m2']) ?> m²</div><div class="data-row">▱ &nbsp; Ambientes: <?= e((string)($property['ambientes'] ?? '—')) ?></div><div class="data-row">♧ &nbsp; Dormitorios: <?= e((string)($property['dormitorios'] ?? '—')) ?></div><div class="data-row">♨ &nbsp; Baños: <?= e((string)($property['banos'] ?? '—')) ?></div><a class="btn" style="display:block;text-align:center;margin-top:14px" href="/Inmobiliaria/login.php">Consultar por esta propiedad</a></div></div>
    </div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/sistema/Vistas/Diseno/pie.php'; ?>

