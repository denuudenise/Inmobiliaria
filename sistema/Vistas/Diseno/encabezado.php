<?php
$currentUser = current_user();
$pageTitle = $pageTitle ?? 'Inmobiliaria Nuevo Hogar';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8f5ef">

    <title><?= e($pageTitle) ?> | Nuevo Hogar</title>

    <!-- Fuentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
        rel="stylesheet"
    >

    <!-- Estilos -->
    <link rel="stylesheet" href="/Inmobiliaria/recursos/estilos/estilos.css">
</head>

<body
    data-role="<?= e($currentUser['role'] ?? 'Invitado') ?>"
    data-csrf="<?= e(csrf_token()) ?>"
>

<a class="skip-link" href="#contenido">Saltar al contenido</a>


<!-- ================================================================
     BARRA SUPERIOR
     ================================================================ -->
<header class="topbar">

    <!-- ============ LOGO ============ -->
    <a class="brand" href="/Inmobiliaria/index.php" aria-label="Inmobiliaria Nuevo Hogar, inicio">
        <svg class="brand-mark" viewBox="0 0 48 48" fill="none" aria-hidden="true">
            <path
                d="M5 22.5 24 6l19 16.5M10 20v22h28V20M19 42V27h10v15"
                stroke="currentColor"
                stroke-width="3.2"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
            <path
                d="M3 23 24 4l21 19"
                stroke="currentColor"
                stroke-width="1.4"
                stroke-linecap="round"
            />
        </svg>

        <span>
            <span class="brand-name">Inmobiliaria</span>
            <span class="brand-tag">Tu próximo hogar, está aquí</span>
        </span>
    </a>

    <!-- Botón del menú móvil -->
    <button
        class="icon-btn mobile-toggle"
        id="mobileToggle"
        aria-expanded="false"
        aria-controls="mainNav"
        aria-label="Abrir menú"
    >☰</button>


   
    <nav class="nav" id="mainNav" aria-label="Navegación principal">

        <!-- Inicio -->
        <div class="nav-item">
            <a href="/Inmobiliaria/index.php" class="nav-link">Inicio</a>
        </div>

        <!-- Menú "Comprar" -->
        <div class="nav-item" id="buyNav">
            <button class="nav-link" id="buyMenuButton" aria-expanded="false">
                Comprar <span class="chevron">⌄</span>
            </button>

            <div class="mega-menu" id="buyMenu">

                <a class="mega-option" href="/Inmobiliaria/index.php?operacion=Venta#catalogo">
                    <span class="mega-icon">⌂</span>
                    <span>
                        <strong>Comprar propiedades</strong>
                        <small>Explorá casas, departamentos y más.</small>
                    </span>
                </a>

                <a class="mega-option" href="/Inmobiliaria/index.php?operacion=Alquiler#catalogo">
                    <span class="mega-icon">◇</span>
                    <span>
                        <strong>Alquileres</strong>
                        <small>Encontrá tu próximo hogar.</small>
                    </span>
                </a>

                <a class="mega-option" href="/Inmobiliaria/index.php#busqueda">
                    <span class="mega-icon">⌖</span>
                    <span>
                        <strong>Por ubicación</strong>
                        <small>Buscá por barrio, localidad o zona.</small>
                    </span>
                </a>

                <a class="mega-option" href="/Inmobiliaria/index.php#busqueda">
                    <span class="mega-icon">▦</span>
                    <span>
                        <strong>Por tipo</strong>
                        <small>Casas, departamentos, terrenos y más.</small>
                    </span>
                </a>

            </div>
        </div>

        <!-- Enlaces según el rol del usuario -->
        <?php if ($currentUser && ($currentUser['role'] ?? '') === 'Cliente'): ?>

            <div class="nav-item">
                <a href="/Inmobiliaria/cliente/solicitar-propiedad.php" class="nav-link">Vender</a>
            </div>
            <div class="nav-item">
                <a href="/Inmobiliaria/cliente/solicitudes.php" class="nav-link">Mis solicitudes</a>
            </div>

        <?php elseif ($currentUser): ?>

            <div class="nav-item">
                <a href="<?= e(role_destination(current_user()['role'])) ?>" class="nav-link">Gestión</a>
            </div>

        <?php else: ?>

            <div class="nav-item">
                <a href="/Inmobiliaria/login.php" class="nav-link">Vender</a>
            </div>
            <div class="nav-item">
                <a href="/Inmobiliaria/login.php" class="nav-link">Mis solicitudes</a>
            </div>

        <?php endif; ?>

        <!-- Sesión (visible en móvil) -->
        <div class="mobile-session">
            <?php if ($currentUser): ?>
                <form action="/Inmobiliaria/logout.php" method="post">
                    <?php csrf_field(); ?>
                    <button class="nav-link" type="submit">Cerrar sesión</button>
                </form>
            <?php else: ?>
                <a class="nav-link" href="/Inmobiliaria/login.php">Iniciar sesión</a>
            <?php endif; ?>
        </div>

    </nav>


    
    <div class="top-actions">

        <button
            type="button"
            class="icon-btn"
            id="searchToggle"
            aria-label="Buscar propiedades"
            aria-expanded="false"
            aria-controls="headerSearch"
        >⌕</button>

        <?php if ($currentUser): ?>

            <!-- Usuario con sesión -->
            <div class="account-wrap" id="accountWrap">
                <button
                    class="account-button"
                    id="accountButton"
                    aria-expanded="false"
                    aria-controls="accountMenu"
                >
                    <span class="avatar-small">♙</span>
                    <span>Mi cuenta</span>
                    <span class="chevron">⌄</span>
                </button>

                <div class="account-menu" id="accountMenu">
                    <a href="<?= e(role_destination($currentUser['role'])) ?>">
                        ♙ <span>Mi panel</span>
                    </a>

                    <form action="/Inmobiliaria/logout.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <button type="submit">↪ <span>Cerrar sesión</span></button>
                    </form>
                </div>
            </div>

            <a class="btn" href="<?= e(role_destination($currentUser['role'])) ?>">Mi panel</a>

        <?php else: ?>

            <!-- Visitante sin sesión -->
            <a class="account-button" href="/Inmobiliaria/login.php">
                <span class="avatar-small">♙</span>
                <span>Iniciar sesión</span>
            </a>

            <a class="btn" href="/Inmobiliaria/registro.php">Registrarse</a>

        <?php endif; ?>

    </div>

</header>



<form id="headerSearch" class="header-search" action="/Inmobiliaria/index.php" method="get" hidden>
    <label for="quickSearch">¿Dónde querés vivir?</label>

    <input
        id="quickSearch"
        name="ubicacion"
        type="search"
        placeholder="Ubicación, dirección o título"
        required
    >

    <button class="btn" type="submit">Buscar</button>
</form>


<main id="contenido">