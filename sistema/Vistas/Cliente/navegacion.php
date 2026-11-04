<aside class="side-card">
  <div class="side-user"><div class="avatar">♙</div><div><strong><?= e($user['name'] . ' ' . $user['surname']) ?></strong><small><?= e($user['email']) ?></small></div></div>
  <div class="side-nav">
    <a class="<?= ($activePage ?? '') === 'inicio' ? 'active' : '' ?>" href="/Inmobiliaria/cliente/index.php"><span class="nav-icon">⌂</span>Resumen</a>
    <a class="<?= ($activePage ?? '') === 'perfil' ? 'active' : '' ?>" href="/Inmobiliaria/cuenta.php"><span class="nav-icon">♙</span>Mi perfil</a>
    <a class="<?= ($activePage ?? '') === 'operaciones' ? 'active' : '' ?>" href="/Inmobiliaria/cliente/operaciones.php"><span class="nav-icon">▤</span>Mis operaciones</a>
    <a class="<?= ($activePage ?? '') === 'ofertas' ? 'active' : '' ?>" href="/Inmobiliaria/cliente/ofertas.php"><span class="nav-icon">＄</span>Mis ofertas</a>
    <a class="<?= ($activePage ?? '') === 'solicitudes' ? 'active' : '' ?>" href="/Inmobiliaria/cliente/solicitudes.php"><span class="nav-icon">◷</span>Mis solicitudes</a>
    <a class="<?= ($activePage ?? '') === 'solicitar' ? 'active' : '' ?>" href="/Inmobiliaria/cliente/solicitar-propiedad.php"><span class="nav-icon">＋</span>Publicar propiedad</a>
    <?php foreach (['propiedades'=>'Mis propiedades','favoritos'=>'Favoritos','consultas'=>'Mis consultas'] as $key=>$label): ?><a class="<?= ($activePage??'')===$key?'active':'' ?>" href="/Inmobiliaria/cliente/actividad.php?seccion=<?= $key ?>"><?= $label ?></a><?php endforeach; ?>
  </div>
</aside>

