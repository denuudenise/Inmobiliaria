<?php
declare(strict_types=1);

require __DIR__ . '/sistema/Nucleo/inicio.php';

$pageTitle = 'Nosotros';
require __DIR__ . '/sistema/Vistas/Diseno/encabezado.php';
?>

<section class="about-page section">
    <div class="about-hero">
        <div class="about-visual" aria-label="Espacios que pueden convertirse en tu próximo hogar">
            <img class="about-image-main" src="/Inmobiliaria/recursos/imagenes/casa.jpg" alt="Fachada de una propiedad ofrecida por Nuevo Hogar">
            <img class="about-image-detail" src="/Inmobiliaria/recursos/imagenes/interior.jpg" alt="Interior luminoso de una vivienda">
            <span class="about-image-mark" aria-hidden="true">⌂</span>
        </div>
        <div class="about-copy">
            <span class="eyebrow">Inmobiliaria Nuevo Hogar</span>
            <h1>¿Quiénes somos?</h1>
            <p>Somos un equipo inmobiliario que conecta personas con lugares para vivir, crecer y proyectar.</p>
            <p>Trabajamos con atención cercana y profesional para acompañarte en cada etapa, ya sea que estés buscando una propiedad o quieras ofrecer la tuya.</p>
            <p>Te brindamos información clara, opciones para comparar y asesoramiento para que puedas avanzar con confianza.</p>
        </div>
    </div>

    <div class="about-values">
        <article class="about-card">
            <span class="about-icon" aria-hidden="true">♡</span>
            <h2>Escuchamos primero</h2>
            <p>Nos importa entender qué necesitás: la ubicación, el tipo de propiedad y el momento de cada búsqueda.</p>
        </article>
        <article class="about-card">
            <span class="about-icon" aria-hidden="true">⌂</span>
            <h2>Información clara</h2>
            <p>Reunimos los detalles de cada propiedad para que puedas comparar opciones y hacer tus consultas con confianza.</p>
        </article>
        <article class="about-card">
            <span class="about-icon" aria-hidden="true">↗</span>
            <h2>Acompañamiento cercano</h2>
            <p>Estamos para responder tus preguntas y orientarte en los próximos pasos de la operación.</p>
        </article>
    </div>

    <section class="about-process">
        <div class="section-heading">
            <div>
                <h2>Cómo trabajamos</h2>
                <p>Un proceso simple para acercarte a tu próximo hogar.</p>
            </div>
        </div>
        <div class="about-steps">
            <article class="about-step">
                <span>01</span>
                <h3>Nos contás qué buscás</h3>
                <p>Conversamos sobre la zona, el tipo de operación y tus preferencias.</p>
            </article>
            <article class="about-step">
                <span>02</span>
                <h3>Explorás opciones</h3>
                <p>Recorré el catálogo, compará propiedades y consultá por las que te interesan.</p>
            </article>
            <article class="about-step">
                <span>03</span>
                <h3>Te acompañamos</h3>
                <p>Coordinamos las consultas y te orientamos con los siguientes pasos.</p>
            </article>
        </div>
    </section>

    <div class="about-cta">
        <div>
            <h2>¿Empezamos?</h2>
            <p>Explorá las propiedades disponibles y contanos qué estás buscando.</p>
        </div>
        <a class="btn" href="/Inmobiliaria/index.php#catalogo">Ver el catálogo</a>
    </div>
</section>

<?php require __DIR__ . '/sistema/Vistas/Diseno/pie.php'; ?>
