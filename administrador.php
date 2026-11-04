<?php
declare(strict_types=1);

require __DIR__ . '/sistema/Nucleo/inicio.php';

$user = require_user(['Administrador']);
$admin = true;
$panelUrl = '/Inmobiliaria/administrador.php';


$section = (string)($_GET['seccion'] ?? 'resumen');

$sections = [
    'resumen'     => 'Resumen',
    'propiedades' => 'Propiedades',
    'solicitudes' => 'Solicitudes',
    'consultas'   => 'Consultas',
    'seguimiento' => 'Seguimiento y ofertas',
    'clientes'    => 'Clientes',
    'operaciones' => 'Ventas y alquileres',
    'reportes'    => 'Reportes',
];

if ($admin) {
    $sections['usuarios'] = 'Usuarios y agentes';
    $sections['auditoria'] = 'Auditoría';
}

$sections['perfil'] = 'Mi perfil';

if (!isset($sections[$section])) {
    http_response_code(404);
    exit('Sección no encontrada.');
}

$db = Database::connection();
$dao = new GestionDAO($db);
$seguimientoDAO = new SeguimientoDAO($db);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        $action = $_POST['accion'] ?? '';

        
        if (in_array($action, ['asignar', 'aprobar', 'rechazar'], true)) {
            $dao->moderate(
                (int)($_POST['id'] ?? 0),
                $action,
                $user,
                trim($_POST['motivo'] ?? ''),
                (int)($_POST['agente'] ?? 0)
            );

        // --- Guardar propiedad ---
        } elseif ($action === 'guardar_propiedad') {
            $dao->saveProperty($_POST, $user);

        // --- Registrar venta o alquiler ---
        } elseif ($action === 'operacion') {
            $dao->closeOperation($_POST, $user);

        // --- Finalizar alquiler ---
        } elseif ($action === 'finalizar_alquiler') {
            $id = (int)($_POST['id'] ?? 0);
            $db->beginTransaction();

            try {
                $r = query_rows(
                    'SELECT * FROM alquileres WHERE id_alquiler=? FOR UPDATE',
                    [$id]
                )[0] ?? null;

                if (
                    !$r
                    || (!$admin && (int)$r['id_agente'] !== $user['id'])
                    || $r['estado'] !== 'Activo'
                ) {
                    throw new RuntimeException('El alquiler no está activo o no está a tu cargo.');
                }

                execute_sql(
                    "UPDATE alquileres SET estado='Finalizado' WHERE id_alquiler=?",
                    [$id]
                );
                execute_sql(
                    'UPDATE propiedades SET id_estado_comercial=1 WHERE id_propiedad=?',
                    [$r['id_propiedad']]
                );

                $db->commit();
            } catch (Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                throw $e;
            }

        // --- Crear usuario (solo administrador) ---
        } elseif ($action === 'crear_usuario' && $admin) {
            $name     = trim($_POST['nombre'] ?? '');
            $surname  = trim($_POST['apellido'] ?? '');
            $dni      = trim($_POST['dni'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $password = (string)($_POST['password'] ?? '');
            $role     = (int)($_POST['rol'] ?? 0);

            if (
                !$name
                || !$surname
                || mb_strlen($name) > 100
                || mb_strlen($surname) > 100
                || !preg_match('/^[0-9]{7,10}$/', $dni)
                || strlen($email) > 150
                || !filter_var($email, FILTER_VALIDATE_EMAIL)
                || strlen($password) < 8
                || !in_array($role, [1, 2, 3], true)
            ) {
                throw new RuntimeException('Revisá los datos de la cuenta y usá una contraseña de al menos 8 caracteres.');
            }

            $db->beginTransaction();

            try {
                $newId = (new UsuarioDAO($db))->createUser(
                    $name,
                    $surname,
                    $dni,
                    '',
                    $email,
                    password_hash($password, PASSWORD_DEFAULT),
                    $role
                );
                $db->commit();
            } catch (Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                throw $e;
            }

        // --- Responder consulta ---
        } elseif ($action === 'responder') {
            $id    = (int)($_POST['id'] ?? 0);
            $reply = trim($_POST['respuesta'] ?? '');

            if (mb_strlen($reply) < 5 || mb_strlen($reply) > 5000) {
                throw new RuntimeException('La respuesta debe tener entre 5 y 5000 caracteres.');
            }

            $q = query_rows(
                'SELECT c.id_contacto
                 FROM contactos c
                 JOIN propiedades p ON p.id_propiedad=c.id_propiedad
                 WHERE c.id_contacto=?
                   AND c.tipo="Consulta"
                   AND (?=1 OR p.id_agente_creador=?)',
                [$id, (int)$admin, $user['id']]
            );

            if (!$q) {
                throw new RuntimeException('Consulta no disponible.');
            }

            execute_sql(
                "UPDATE contactos
                 SET resultado=?, estado='Respondida', fecha_respuesta=NOW()
                 WHERE id_contacto=? AND tipo='Consulta'",
                [$reply, $id]
            );

        // --- Cambiar rol / estado de un usuario (solo administrador) ---
        } elseif ($action === 'interaccion') {
            $seguimientoDAO->recordInteraction($user, $_POST);

        } elseif ($action === 'programar_visita') {
            $seguimientoDAO->scheduleVisit($user, $_POST);

        } elseif ($action === 'crear_tarea') {
            $seguimientoDAO->createTask($user, $_POST);

        } elseif ($action === 'completar_tarea') {
            $seguimientoDAO->completeTask($user, (int)($_POST['id'] ?? 0));

        } elseif ($action === 'revisar_oferta') {
            $seguimientoDAO->reviewOffer($user, (int)($_POST['id'] ?? 0), (string)($_POST['estado'] ?? ''), trim((string)($_POST['observaciones'] ?? '')));

        } elseif ($action === 'crear_reserva') {
            $seguimientoDAO->createReservation($user, $_POST);

        } elseif ($action === 'actualizar_reserva' && $admin) {
            $seguimientoDAO->updateReservation($user, (int)($_POST['id'] ?? 0), (string)($_POST['reserva_accion'] ?? ''), (string)($_POST['estado_pago'] ?? 'Pendiente'));

        } elseif ($action === 'usuario' && $admin) {
            $id     = (int)($_POST['id'] ?? 0);
            $role   = (int)($_POST['rol'] ?? 0);
            $active = (int)($_POST['activo'] ?? 0);

            if ($id === $user['id']) {
                throw new RuntimeException('No podés cambiar tu propio rol o desactivar tu cuenta.');
            }

            if (!in_array($role, [1, 2, 3], true) || !in_array($active, [0, 1], true)) {
                throw new RuntimeException('Rol o estado inválido.');
            }

            $old = query_rows(
                'SELECT id_rol FROM usuarios WHERE id_usuario=?',
                [$id]
            );

            if (!$old) {
                throw new RuntimeException('Usuario inexistente.');
            }

            if (
                (int)$old[0]['id_rol'] !== $role
                && (
                    query_rows('SELECT id_propiedad FROM propiedades WHERE id_agente_creador=? LIMIT 1', [$id])
                    || query_rows('SELECT id_solicitud FROM solicitudes_publicacion WHERE id_cliente=? OR id_agente=? LIMIT 1', [$id, $id])
                    || query_rows('SELECT id_contacto FROM contactos WHERE id_cliente=? OR id_agente=? LIMIT 1', [$id, $id])
                )
            ) {
                throw new RuntimeException('Este usuario tiene actividad vinculada. Conservá su rol para mantener el historial.');
            }

            $db->beginTransaction();

            try {
                execute_sql(
                    'UPDATE usuarios SET id_rol=?, activo=? WHERE id_usuario=?',
                    [$role, $active, $id]
                );

                if ($role === 3) {
                    (new ModeloCompartidoDAO($db))->client($id);
                }

                $db->commit();
            } catch (Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                throw $e;
            }

        } else {
            throw new RuntimeException('Acción no permitida.');
        }

        flash('Los cambios se guardaron correctamente.');
        redirect($panelUrl . '?seccion=' . urlencode($section));

    } catch (RuntimeException $e) {
        $error = $e instanceof PDOException
            ? 'No se pudo guardar. Revisá los datos e intentá nuevamente.'
            : $e->getMessage();

        error_log($e->getMessage());
    }
}

/* ------------------------------------------------------------------
 | CONSULTAS DE DATOS PARA LAS VISTAS
 ------------------------------------------------------------------ */
$q = trim((string)($_GET['q'] ?? ''));
$like = '%' . $q . '%';

$agents = query_rows(
    'SELECT id_usuario, nombre, apellido
     FROM usuarios
     WHERE id_rol=2 AND activo=1'
);

$clients = query_rows(
    'SELECT id_usuario, nombre, apellido
     FROM usuarios
     WHERE id_rol=3 AND activo=1'
);

$props = query_rows(
    'SELECT p.*,
            ev.nombre AS verificacion,
            ec.nombre AS estado,
            tp.nombre AS tipo,
            (SELECT GROUP_CONCAT(ot.nombre ORDER BY ot.id_tipo_transaccion SEPARATOR " · ") FROM propiedad_ofertas po JOIN tipos_transaccion ot ON ot.id_tipo_transaccion=po.id_tipo_transaccion WHERE po.id_propiedad=p.id_propiedad) AS operacion
     FROM propiedades p
     JOIN estados_verificacion ev ON ev.id_estado_verificacion=p.id_estado_verificacion
     JOIN estados_comerciales ec  ON ec.id_estado_comercial=p.id_estado_comercial
     JOIN tipos_propiedad tp      ON tp.id_tipo_propiedad=p.id_tipo_propiedad
     JOIN tipos_transaccion tt    ON tt.id_tipo_transaccion=p.id_tipo_transaccion
     WHERE (?=1 OR p.id_agente_creador=?)
       AND (p.titulo LIKE ? OR p.localidad LIKE ?)
     ORDER BY p.fecha_creacion DESC',
    [(int)$admin, $user['id'], $like, $like]
);

$requests = query_rows(
    'SELECT s.*,
            u.nombre AS cliente,
            u.apellido,
            u.email,
            ev.nombre AS estado
     FROM solicitudes_publicacion s
     JOIN usuarios u ON u.id_usuario=s.id_cliente
     JOIN estados_verificacion ev ON ev.id_estado_verificacion=s.id_estado_verificacion
     WHERE (?=1 OR s.id_agente=? OR s.id_agente IS NULL)
       AND (s.titulo LIKE ? OR u.nombre LIKE ?)
     ORDER BY s.id_estado_verificacion=2 DESC, s.fecha_solicitud DESC',
    [(int)$admin, $user['id'], $like, $like]
);

$consults = query_rows(
    'SELECT c.id_contacto AS id_consulta,
            c.descripcion AS mensaje,
            c.resultado AS respuesta,
            c.estado,
            c.fecha,
            c.fecha_respuesta,
            p.titulo,
            u.nombre,
            u.apellido
     FROM contactos c
     JOIN propiedades p ON p.id_propiedad=c.id_propiedad
     JOIN usuarios u ON u.id_usuario=c.id_cliente
     WHERE c.tipo="Consulta"
       AND (?=1 OR p.id_agente_creador=?)
       AND (p.titulo LIKE ? OR u.nombre LIKE ?)
     ORDER BY c.estado="Pendiente" DESC, c.fecha DESC',
    [(int)$admin, $user['id'], $like, $like]
);

$crmData = $section === 'seguimiento' ? $seguimientoDAO->panelData($user, $admin) : [];
$crmClients = $section === 'seguimiento' ? $seguimientoDAO->clientsFor($user, $admin) : [];
$auditEvents = $section === 'auditoria' ? (new AuditoriaDAO($db))->auditLog() : [];
$stateHistory = $section === 'auditoria' ? query_rows('SELECT h.*,p.titulo,u.nombre,u.apellido,ea.nombre AS anterior,en.nombre AS nuevo FROM historial_estados h JOIN propiedades p ON p.id_propiedad=h.id_propiedad JOIN usuarios u ON u.id_usuario=h.id_usuario JOIN estados_comerciales ea ON ea.id_estado_comercial=h.estado_anterior JOIN estados_comerciales en ON en.id_estado_comercial=h.estado_nuevo ORDER BY h.fecha DESC LIMIT 50') : [];
$priceHistory = $section === 'auditoria' ? query_rows('SELECT h.*,p.titulo,u.nombre,u.apellido,t.nombre AS operacion FROM historial_precios h JOIN propiedades p ON p.id_propiedad=h.id_propiedad JOIN usuarios u ON u.id_usuario=h.id_usuario JOIN tipos_transaccion t ON t.id_tipo_transaccion=h.id_tipo_transaccion ORDER BY h.fecha DESC LIMIT 50') : [];
$assignmentHistory = $section === 'auditoria' ? query_rows('SELECT h.*,u.nombre AS autor_nombre,u.apellido AS autor_apellido,ua.nombre AS anterior_nombre,ua.apellido AS anterior_apellido,un.nombre AS nuevo_nombre,un.apellido AS nuevo_apellido FROM historial_asignaciones h JOIN usuarios u ON u.id_usuario=h.id_usuario_cambio LEFT JOIN usuarios ua ON ua.id_usuario=h.agente_anterior LEFT JOIN usuarios un ON un.id_usuario=h.agente_nuevo ORDER BY h.fecha DESC LIMIT 50') : [];

$pageTitle = $sections[$section];
require __DIR__ . '/sistema/Vistas/Diseno/encabezado.php';

/* ------------------------------------------------------------------
 | HELPERS
 ------------------------------------------------------------------ */
function person_options(array $people, int $selected = 0): void
{
    foreach ($people as $person) {
        echo '<option value="' . $person['id_usuario'] . '" '
            . ($selected === (int)$person['id_usuario'] ? 'selected' : '')
            . '>'
            . e($person['nombre'] . ' ' . $person['apellido'])
            . '</option>';
    }
}
?>

<section class="dashboard staff-dashboard">

  
    <div class="dashboard-title">
        <div>
            <span class="eyebrow"><?= e($user['role']) ?> / Nuevo Hogar</span>
            <h1><?= $section === 'resumen' ? 'Hola, ' . e($user['name']) : e($sections[$section]) ?></h1>
            <p>Tu trabajo, organizado en un solo lugar.</p>
        </div>
        <a class="btn btn-outline" href="/Inmobiliaria/index.php">Ver sitio público ↗</a>
    </div>

    <div class="dash-layout">

        <!--  MENÚ LATERAL -->
        <aside class="side-card staff-side">
            <div class="side-user">
                <strong><?= e($user['name'] . ' ' . $user['surname']) ?></strong>
            </div>

            <nav class="side-nav" aria-label="Panel de gestión">
                <?php foreach ($sections as $key => $label): ?>
                    <a class="<?= $section === $key ? 'active' : '' ?>" href="?seccion=<?= $key ?>">
                        <?= e($label) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>

      
        <div class="workspace">

            <?php notice(); ?>

            <?php if ($error): ?>
                <div class="notice error" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

         
            <?php if (!in_array($section, ['perfil', 'resumen'], true)): ?>
                <form method="get" class="toolbar">
                    <input type="hidden" name="seccion" value="<?= e($section) ?>">
                    <input
                        type="search"
                        name="q"
                        aria-label="Buscar en esta sección"
                        placeholder="Buscar en <?= e(mb_strtolower($sections[$section])) ?>…"
                        value="<?= e($q) ?>"
                    >
                    <button class="btn">Buscar</button>
                    <?php if ($q): ?>
                        <a class="text-link" href="?seccion=<?= e($section) ?>">Limpiar</a>
                    <?php endif; ?>
                </form>
            <?php endif; ?>


            <?php /* ================================================
                   | RESUMEN
                   ================================================ */ ?>
            <?php if ($section === 'resumen'): ?>

                <div class="mini-stat-grid">
                    <a class="mini-stat" href="?seccion=propiedades">
                        <strong><?= count($props) ?></strong>
                        <span>Propiedades a tu cargo</span>
                    </a>

                    <a class="mini-stat" href="?seccion=solicitudes">
                        <strong><?= count(array_filter($requests, fn($r) => (int)$r['id_estado_verificacion'] === 2)) ?></strong>
                        <span>Solicitudes por revisar</span>
                    </a>

                    <a class="mini-stat" href="?seccion=consultas">
                        <strong><?= count(array_filter($consults, fn($r) => $r['estado'] === 'Pendiente')) ?></strong>
                        <span>Consultas sin responder</span>
                    </a>
                </div>

                <div class="content-card">
                    <h2>Próximas tareas</h2>
                    <p class="content-subtitle">Atendé lo pendiente y después seguí con el catálogo.</p>

                    <?php
                    $pending = array_filter($requests, fn($r) => (int)$r['id_estado_verificacion'] === 2);

                    foreach (array_slice($pending, 0, 4) as $r):
                    ?>
                        <a class="task-row" href="?seccion=solicitudes#solicitud-<?= $r['id_solicitud'] ?>">
                            <span>
                                <strong><?= e($r['titulo']) ?></strong>
                                <small><?= e($r['cliente'] . ' ' . $r['apellido']) ?></small>
                            </span>
                            <span class="status amber">Revisar →</span>
                        </a>
                    <?php endforeach; ?>

                    <?php if (!$pending): ?>
                        <p>No hay solicitudes pendientes. Podés revisar las consultas de tus clientes.</p>
                    <?php endif; ?>

                    <div class="actions">
                        <a class="btn" href="?seccion=propiedades&editar=0">Nueva propiedad</a>
                        <a class="btn btn-outline" href="?seccion=consultas">Atender consultas</a>
                    </div>
                </div>


            <?php /* ================================================
                   | PROPIEDADES
                   ================================================ */ ?>
            <?php elseif ($section === 'propiedades'): ?>

                <?php if (isset($_GET['editar'])): ?>
                    <?php
                    $id = (int)$_GET['editar'];
                    $p = [];

                    foreach ($props as $item) {
                        if ((int)$item['id_propiedad'] === $id) {
                            $p = $item;
                        }
                    }

                    if ($id && !$p):
                    ?>
                        <div class="notice error">Propiedad no disponible para editar.</div>

                    <?php else: ?>
                        <?php
                        function val(array $p, string $key, string $fallback = ''): string
                        {
                            return e((string)($_POST[$key] ?? $p[$key] ?? $fallback));
                        }
                        ?>

                        <div class="content-card">
                            <h2><?= $id ? 'Editar propiedad' : 'Nueva propiedad' ?></h2><?php if ($id): ?><p><a href="/Inmobiliaria/ficha-privada.php?tipo=propiedad&amp;id=<?= $id ?>" class="text-link">Ver documentación e impuestos guardados</a></p><?php endif; ?>
                            <p class="content-subtitle">
                                <?= $admin
                                    ? 'Elegí el estado de publicación y el agente responsable.'
                                    : 'Los cambios se envían a revisión antes de publicarse.' ?>
                            </p>

                            <form method="post" enctype="multipart/form-data" class="form-grid">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="accion" value="guardar_propiedad">
                                <input type="hidden" name="id" value="<?= $id ?>">

                                
                                <?php
                                $textFields = [
                                    'titulo'    => 'Título',
                                    'direccion' => 'Dirección',
                                    'localidad' => 'Localidad',
                                ];
                                foreach ($textFields as $field => $label):
                                ?>
                                    <div class="field">
                                        <label for="<?= $field ?>"><?= $label ?></label>
                                        <input
                                            id="<?= $field ?>"
                                            name="<?= $field ?>"
                                            value="<?= val($p, $field) ?>"
                                            required
                                            <?= $field === 'precio' ? 'type="number" min="0.01" step="0.01"' : '' ?>
                                        >
                                    </div>
                                <?php endforeach; ?>

                               
                                <div class="field full">
                                    <label for="descripcion">Descripción</label>
                                    <textarea id="descripcion" name="descripcion" required><?= val($p, 'descripcion') ?></textarea>
                                </div>

                              
                                <?php
                                $numberFields = [
                                    'superficie'  => 'Superficie (m²)',
                                    'ambientes'   => 'Ambientes',
                                    'dormitorios' => 'Dormitorios',
                                    'banos'       => 'Baños',
                                ];
                                foreach ($numberFields as $field => $label):
                                ?>
                                    <div class="field">
                                        <label for="<?= $field ?>"><?= $label ?></label>
                                        <input
                                            type="number"
                                            id="<?= $field ?>"
                                            name="<?= $field ?>"
                                            min="<?= $field === 'superficie' ? '0.01' : '0' ?>"
                                            step="<?= $field === 'superficie' ? '0.01' : '1' ?>"
                                            value="<?= e((string)($_POST[$field] ?? $p[$field === 'superficie' ? 'superficie_total_m2' : $field] ?? '0')) ?>"
                                            required
                                        >
                                    </div>
                                <?php endforeach; ?>

                                
                                <?php
                                $selects = [
                                    'tipo'      => ['tipos_propiedad', 'id_tipo_propiedad'],
                                ];
                                foreach ($selects as $key => $cfg):
                                ?>
                                    <div class="field">
                                        <label for="<?= $key ?>"><?= ucfirst($key) ?></label>
                                        <select name="<?= $key ?>" id="<?= $key ?>">
                                            <?php foreach (query_rows('SELECT * FROM ' . $cfg[0]) as $o): ?>
                                                <option
                                                    value="<?= $o[$cfg[1]] ?>"
                                                    <?= (int)($_POST[$key] ?? $p[$cfg[1]] ?? 1) === (int)$o[$cfg[1]] ? 'selected' : '' ?>
                                                >
                                                    <?= e($o['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endforeach; ?>

                                


                               
                                <div class="field">
                                    <label for="estado">Estado comercial</label>
                                    <select id="estado" name="estado">
                                        <?php
                                        $estados = [
                                            1 => 'Disponible',
                                            2 => 'Reservada',
                                            5 => 'En trámite',
                                            6 => 'Fuera de mercado',
                                        ];
                                        foreach ($estados as $k => $v):
                                        ?>
                                            <option
                                                value="<?= $k ?>"
                                                <?= (int)($_POST['estado'] ?? $p['id_estado_comercial'] ?? 1) === $k ? 'selected' : '' ?>
                                            >
                                                <?= $v ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="field full"><label for="motivo_cambio">Motivo del cambio de estado</label><input id="motivo_cambio" name="motivo_cambio" maxlength="500" placeholder="Obligatorio cuando cambiás el estado"></div>
                                <div class="field full"><label for="motivo_precio">Motivo del cambio de precio (opcional)</label><input id="motivo_precio" name="motivo_precio" maxlength="500"></div>

                                
                                <?php if ($admin): ?>
                                    <div class="field">
                                        <label for="agente">Agente</label>
                                        <select id="agente" name="agente" required>
                                            <option value="">Seleccionar</option>
                                            <?php person_options($agents, (int)($_POST['agente'] ?? $p['id_agente_creador'] ?? 0)); ?>
                                        </select>
                                    </div>

                                    <div class="field">
                                        <label for="verificacion">Publicación</label>
                                        <select id="verificacion" name="verificacion">
                                            <?php
                                            $verificaciones = [
                                                2 => 'Pendiente',
                                                3 => 'Aprobado',
                                                4 => 'Rechazado',
                                                5 => 'Inactivo',
                                            ];
                                            foreach ($verificaciones as $k => $v):
                                            ?>
                                                <option
                                                    value="<?= $k ?>"
                                                    <?= (int)($_POST['verificacion'] ?? $p['id_estado_verificacion'] ?? 2) === $k ? 'selected' : '' ?>
                                                >
                                                    <?= $v ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>

                                
                                <?php $offerKind='propiedad'; $offerId=$id; require __DIR__.'/sistema/Vistas/Diseno/ofertas-form.php'; ?>
<?php require __DIR__.'/sistema/Vistas/Diseno/expediente-form.php'; ?>
<div class="field full">
                                    <label for="fotos">Agregar fotos (hasta 8, máximo 2 MB cada una)</label>
                                    <input
                                        id="fotos"
                                        name="fotos[]"
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        multiple
                                    >
                                    <small>Se agregan a las fotos existentes.</small>
                                </div>

                                <div class="actions full">
                                    <button class="btn">Guardar propiedad</button>
                                    <a class="btn btn-outline" href="?seccion=propiedades">Cancelar</a>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>

                <?php else: ?>

                   
                    <div class="content-card">
                        <div class="section-heading">
                            <h2>Catálogo de gestión</h2>
                            <a class="btn" href="?seccion=propiedades&editar=0">＋ Nueva propiedad</a>
                        </div>

                        <div class="table-wrap">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Propiedad</th>
                                        <th>Precio</th>
                                        <th>Publicación</th>
                                        <th>Estado</th>
                                        <th>Ficha privada</th><th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($props as $p): ?>
                                        <tr>
                                            <td>
                                                <strong><?= e($p['titulo']) ?></strong><br>
                                                <small><?= e($p['localidad']) ?></small>
                                            </td>
                                            <td><?php foreach ((new OfertaDAO($db))->read('propiedad',(int)$p['id_propiedad']) as $listedOffer): ?><div><?= e($listedOffer['nombre'].' · '.$listedOffer['moneda'].' '.number_format((float)$listedOffer['precio'],2,',','.')) ?></div><?php endforeach; ?></td>
                                            <td><?= e($p['verificacion']) ?></td>
                                            <td><?= e($p['estado']) ?></td><td><a class="text-link" href="/Inmobiliaria/ficha-privada.php?tipo=propiedad&amp;id=<?= (int)$p['id_propiedad'] ?>">Documentación e impuestos</a></td>
                                            <td>
                                                <?php if (!in_array((int)$p['id_estado_comercial'], [3, 4], true)): ?>
                                                    <a class="text-link" href="?seccion=propiedades&editar=<?= $p['id_propiedad'] ?>">Editar</a>
                                                <?php else: ?>
                                                    Cerrada
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if (!$props): ?>
                            <p>No hay propiedades con esa búsqueda.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>


            <?php /* ================================================
                   | SOLICITUDES
                   ================================================ */ ?>
            <?php elseif ($section === 'solicitudes'): ?>

                <div class="content-card">
                    <h2>Revisión de publicaciones</h2>
                    <p class="content-subtitle">Abrí una solicitud para revisar sus datos y archivos.</p>

                    <?php foreach ($requests as $r): ?>
                        <details class="record" id="solicitud-<?= $r['id_solicitud'] ?>">
                            <summary>
                                <span>
                                    <strong><?= e($r['titulo']) ?></strong>
                                    <small><?= e($r['cliente'] . ' ' . $r['apellido'] . ' · ' . $r['localidad']) ?></small>
                                </span>
                                <span class="status blue"><?= e($r['estado']) ?></span>
                            </summary>

                            <div class="record-body">
                                <p><?= nl2br(e($r['descripcion'])) ?></p>

                                <p>
                                    <?= e($r['direccion']) ?> ·
                                    <?= e($r['moneda']) ?> <?= e((string)$r['precio_estimado']) ?> ·
                                    <?= e((string)$r['superficie_total_m2']) ?> m²
                                </p>

                                
                                <?php $offerKind='solicitud'; $offerId=(int)$r['id_solicitud']; require __DIR__.'/sistema/Vistas/Diseno/ofertas-resumen.php'; ?>
<?php if ($admin || (int)$r['id_agente']===(int)$user['id']): ?><p><a class="btn btn-outline" href="/Inmobiliaria/ficha-privada.php?tipo=solicitud&amp;id=<?= (int)$r['id_solicitud'] ?>">Revisar documentación e impuestos</a></p><?php endif; ?>
<?php
                                $multimedia = query_rows(
                                    'SELECT * FROM solicitud_multimedia WHERE id_solicitud=?',
                                    [$r['id_solicitud']]
                                );

                                foreach ($multimedia as $m):
                                    if (
                                        str_starts_with($m['url_archivo'], '/Inmobiliaria/')
                                        && !is_file(__DIR__ . substr($m['url_archivo'], strlen('/Inmobiliaria')))
                                    ) {
                                        echo '<p class="help-text">Un archivo de esta solicitud no está disponible en este equipo. Pedile al cliente que lo vuelva a adjuntar.</p>';
                                        continue;
                                    }
                                ?>
                                    <a class="text-link media-link" href="<?= e($m['url_archivo']) ?>" target="_blank" rel="noopener">
                                        Ver <?= e($m['tipo']) ?> ↗
                                    </a>
                                <?php endforeach; ?>

                               
                                <?php if ($r['motivo_rechazo']): ?>
                                    <p class="notice error"><?= e($r['motivo_rechazo']) ?></p>
                                <?php endif; ?>

                             
                                <?php if ((int)$r['id_estado_verificacion'] === 2): ?>

                                    <form method="post" class="toolbar">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?= $r['id_solicitud'] ?>">
                                        <input type="hidden" name="accion" value="asignar">

                                        <?php if ($admin): ?>
                                            <select name="agente" aria-label="Agente responsable" required>
                                                <option value="">Elegir agente</option>
                                                <?php person_options($agents, (int)$r['id_agente']); ?>
                                            </select>
                                            <button class="btn btn-outline">Asignar agente</button>

                                        <?php elseif (!$r['id_agente']): ?>
                                            <button class="btn">Tomar solicitud</button>

                                        <?php else: ?>
                                            <p>Asignada a vos. Pendiente de aprobación del administrador.</p>
                                        <?php endif; ?>
                                    </form>

                                    <?php if ($admin): ?>
                                        <form method="post" class="actions">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?= $r['id_solicitud'] ?>">

                                            <button class="btn" name="accion" value="aprobar">Aprobar y publicar</button>

                                            <input
                                                name="motivo"
                                                aria-label="Motivo de rechazo"
                                                placeholder="Motivo (obligatorio al rechazar)"
                                            >

                                            <button class="btn btn-outline" name="accion" value="rechazar">Rechazar</button>
                                        </form>
                                    <?php endif; ?>

                                <?php endif; ?>
                            </div>
                        </details>
                    <?php endforeach; ?>

                    <?php if (!$requests): ?>
                        <p>No hay solicitudes para mostrar.</p>
                    <?php endif; ?>
                </div>


            <?php /* ================================================
                   | CONSULTAS
                   ================================================ */ ?>
            <?php elseif ($section === 'consultas'): ?>

                <div class="content-card">
                    <h2>Conversaciones con clientes</h2>

                    <?php foreach ($consults as $c): ?>
                        <details class="record">
                            <summary>
                                <span>
                                    <strong><?= e($c['titulo']) ?></strong>
                                    <small><?= e($c['nombre'] . ' ' . $c['apellido']) ?></small>
                                </span>
                                <span class="status blue"><?= e($c['estado']) ?></span>
                            </summary>

                            <div class="record-body">
                                <p><?= nl2br(e($c['mensaje'])) ?></p>

                                <?php if ($c['respuesta']): ?>
                                    <p class="notice"><?= nl2br(e($c['respuesta'])) ?></p>
                                <?php endif; ?>

                                <form method="post" class="modal-form">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="accion" value="responder">
                                    <input type="hidden" name="id" value="<?= $c['id_consulta'] ?>">

                                    <div class="field">
                                        <label for="respuesta-<?= $c['id_consulta'] ?>">Respuesta</label>
                                        <textarea
                                            id="respuesta-<?= $c['id_consulta'] ?>"
                                            name="respuesta"
                                            required
                                            minlength="5"
                                            maxlength="5000"
                                        ><?= e($c['respuesta']) ?></textarea>
                                    </div>

                                    <button class="btn">Guardar respuesta</button>
                                </form>
                            </div>
                        </details>
                    <?php endforeach; ?>

                    <?php if (!$consults): ?>
                        <p>No hay consultas para mostrar.</p>
                    <?php endif; ?>
                </div>


            <?php /* ================================================
                   | CLIENTES / USUARIOS
                   ================================================ */ ?>
            <?php elseif ($section === 'seguimiento'): ?>
                <?php require __DIR__ . '/sistema/Vistas/Diseno/seguimiento-panel.php'; ?>

            <?php elseif ($section === 'auditoria' && $admin): ?>
                <div class="content-card">
                    <h2>Registro de auditoría</h2>
                    <p class="content-subtitle">Acciones y cambios recientes con usuario, fecha y detalle.</p>
                    <?php if ($auditEvents): ?><div class="table-wrap"><table class="table"><thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Elemento</th><th>Detalle</th><th>Cambios registrados</th></tr></thead><tbody>
                        <?php foreach ($auditEvents as $event): $eventChanges=query_rows('SELECT campo,valor_anterior,valor_nuevo,motivo FROM auditoria_cambios WHERE id_evento=? ORDER BY id_cambio',[(int)$event['id_evento']]); ?>
                            <tr><td><?= e(date('d/m/Y H:i',strtotime($event['fecha']))) ?></td><td><?= e($event['nombre'].' '.$event['apellido']) ?></td><td><?= e($event['accion']) ?></td><td><?= e($event['entidad'].' #'.$event['id_registro']) ?></td><td><?= e($event['detalle']??'') ?></td><td><?php foreach($eventChanges as $change): ?><div><strong><?= e($change['campo']) ?>:</strong> <?= e((string)$change['valor_anterior']) ?> → <?= e((string)$change['valor_nuevo']) ?><?php if($change['motivo']): ?><small> · <?= e($change['motivo']) ?></small><?php endif; ?></div><?php endforeach; ?></td></tr>
                        <?php endforeach; ?>
                    </tbody></table></div><?php else: ?><p>No hay eventos registrados todavía.</p><?php endif; ?>
                </div>
                <div class="content-card"><h2>Historial de estados y precios</h2>
                    <h3>Estados de propiedades</h3><?php if($stateHistory): ?><div class="table-wrap"><table class="table"><thead><tr><th>Fecha</th><th>Propiedad</th><th>Usuario</th><th>Cambio</th><th>Motivo</th></tr></thead><tbody><?php foreach($stateHistory as $h): ?><tr><td><?= e(date('d/m/Y H:i',strtotime($h['fecha']))) ?></td><td><?= e($h['titulo']) ?></td><td><?= e($h['nombre'].' '.$h['apellido']) ?></td><td><?= e($h['anterior'].' → '.$h['nuevo']) ?></td><td><?= e($h['motivo']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p>Sin cambios de estado registrados.</p><?php endif; ?>
                    <h3>Precios</h3><?php if($priceHistory): ?><div class="table-wrap"><table class="table"><thead><tr><th>Fecha</th><th>Propiedad</th><th>Operación</th><th>Precio anterior</th><th>Precio nuevo</th><th>Usuario / motivo</th></tr></thead><tbody><?php foreach($priceHistory as $h): ?><tr><td><?= e(date('d/m/Y H:i',strtotime($h['fecha']))) ?></td><td><?= e($h['titulo']) ?></td><td><?= e($h['operacion']) ?></td><td><?= e($h['moneda'].' '.number_format((float)$h['precio_anterior'],2,',','.')) ?></td><td><?= e($h['moneda'].' '.number_format((float)$h['precio_nuevo'],2,',','.')) ?></td><td><?= e($h['nombre'].' '.$h['apellido']) ?><?= $h['motivo']?' · '.e($h['motivo']):'' ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p>Sin cambios de precio registrados.</p><?php endif; ?>
                    <h3>Asignaciones</h3><?php if($assignmentHistory): ?><div class="table-wrap"><table class="table"><thead><tr><th>Fecha</th><th>Elemento</th><th>Agente anterior</th><th>Agente nuevo</th><th>Registrado por</th><th>Motivo</th></tr></thead><tbody><?php foreach($assignmentHistory as $h): ?><tr><td><?= e(date('d/m/Y H:i',strtotime($h['fecha']))) ?></td><td><?= e($h['tipo_entidad'].' #'.$h['id_entidad']) ?></td><td><?= e(trim(($h['anterior_nombre']??'').' '.($h['anterior_apellido']??''))?:'Sin asignar') ?></td><td><?= e(trim(($h['nuevo_nombre']??'').' '.($h['nuevo_apellido']??''))?:'Sin asignar') ?></td><td><?= e($h['autor_nombre'].' '.$h['autor_apellido']) ?></td><td><?= e($h['motivo']??'') ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p>Sin reasignaciones registradas.</p><?php endif; ?>
                </div>

            <?php elseif ($section === 'clientes' || $section === 'usuarios'): ?>

                <?php
                $people = query_rows(
                    'SELECT u.id_usuario,
                            u.nombre,
                            u.apellido,
                            u.email,
                            u.telefono,
                            u.id_rol,
                            u.activo,
                            r.nombre AS rol
                     FROM usuarios u
                     JOIN roles r ON r.id_rol=u.id_rol
                     WHERE (u.nombre LIKE ? OR u.apellido LIKE ? OR u.email LIKE ?)
                       AND (?=1 OR u.id_rol=3)
                       AND (
                            ?=1
                            OR EXISTS(
                                SELECT 1
                                FROM contactos c
                                JOIN propiedades p ON p.id_propiedad=c.id_propiedad
                                WHERE c.id_cliente=u.id_usuario
                                  AND c.tipo="Consulta"
                                  AND p.id_agente_creador=?
                            )
                            OR EXISTS(
                                SELECT 1
                                FROM solicitudes_publicacion s
                                WHERE s.id_cliente=u.id_usuario
                                  AND s.id_agente=?
                            )
                       )
                     ORDER BY u.nombre',
                    [
                        $like,
                        $like,
                        $like,
                        (int)($section === 'usuarios'),
                        (int)$admin,
                        $user['id'],
                        $user['id'],
                    ]
                );
                ?>

            
                <?php if ($section === 'usuarios'): ?>
                    <details class="content-card">
                        <summary><strong>Crear usuario</strong></summary>

                        <form method="post" class="form-grid security-form">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="accion" value="crear_usuario">

                            <?php
                            $newFields = [
                                'nombre'   => 'Nombre',
                                'apellido' => 'Apellido',
                                'dni'      => 'DNI',
                                'email'    => 'Correo',
                                'password' => 'Contraseña inicial',
                            ];
                            foreach ($newFields as $key => $label):
                            ?>
                                <div class="field">
                                    <label for="nuevo-<?= $key ?>"><?= $label ?></label>
                                    <input
                                        id="nuevo-<?= $key ?>"
                                        name="<?= $key ?>"
                                        type="<?= $key === 'password' ? 'password' : ($key === 'email' ? 'email' : 'text') ?>"
                                        required
                                        <?= $key === 'password' ? 'minlength="8" autocomplete="new-password"' : '' ?>
                                    >
                                </div>
                            <?php endforeach; ?>

                            <div class="field">
                                <label for="nuevo-rol">Rol</label>
                                <select name="rol" id="nuevo-rol">
                                    <option value="3">Cliente</option>
                                    <option value="2">Agente</option>
                                    <option value="1">Administrador</option>
                                </select>
                            </div>

                            <div class="field full">
                                <button class="btn">Crear usuario</button>
                            </div>
                        </form>
                    </details>
                <?php endif; ?>

             
                <div class="content-card">
                    <h2><?= $section === 'clientes' ? 'Directorio de clientes' : 'Acceso de usuarios' ?></h2>
                    <p class="content-subtitle">
                        <?= $admin
                            ? 'Administrá las cuentas y sus permisos.'
                            : 'Clientes vinculados a tus propiedades o solicitudes.' ?>
                    </p>

                    <?php foreach ($people as $person): ?>
                        <details class="record">
                            <summary>
                                <span>
                                    <strong><?= e($person['nombre'] . ' ' . $person['apellido']) ?></strong>
                                    <small><?= e($person['email']) ?></small>
                                </span>
                                <span class="status <?= $person['activo'] ? 'blue' : 'gray' ?>">
                                    <?= e($person['rol']) ?> · <?= $person['activo'] ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </summary>

                            <div class="record-body">
                                <p>Teléfono: <?= e($person['telefono'] ?: 'Sin informar') ?></p>

                                <a class="text-link" href="mailto:<?= e($person['email']) ?>">Enviar correo</a>

                                <?php if ($section === 'usuarios' && (int)$person['id_usuario'] !== $user['id']): ?>
                                    <form method="post" class="toolbar">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="accion" value="usuario">
                                        <input type="hidden" name="id" value="<?= $person['id_usuario'] ?>">

                                        <select name="rol" aria-label="Rol">
                                            <?php foreach ([1 => 'Administrador', 2 => 'Agente', 3 => 'Cliente'] as $k => $v): ?>
                                                <option value="<?= $k ?>" <?= (int)$person['id_rol'] === $k ? 'selected' : '' ?>>
                                                    <?= $v ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <select name="activo" aria-label="Estado de cuenta">
                                            <option value="1">Activo</option>
                                            <option value="0" <?= !$person['activo'] ? 'selected' : '' ?>>Inactivo</option>
                                        </select>

                                        <button class="btn">Guardar acceso</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </details>
                    <?php endforeach; ?>

                    <?php if (!$people): ?>
                        <p>No se encontraron usuarios.</p>
                    <?php endif; ?>
                </div>


            <?php /* ================================================
                   | OPERACIONES / REPORTES
                   ================================================ */ ?>
            <?php elseif ($section === 'operaciones' || $section === 'reportes'): ?>

                <?php
                $sales = query_rows(
                    'SELECT p.titulo,
                            o.moneda,
                            o.precio_final AS monto,
                            o.fecha_cierre AS fecha,
                            u.nombre,
                            u.apellido
                     FROM propiedades_cerradas o
                     JOIN propiedades p ON p.id_propiedad=o.id_propiedad
                     JOIN usuarios u ON u.id_usuario=o.id_cliente_comprador
                     WHERE (?=1 OR o.id_agente_cierre=?)
                       AND (p.titulo LIKE ? OR u.nombre LIKE ?)
                     ORDER BY fecha DESC',
                    [(int)$admin, $user['id'], $like, $like]
                );

                $rentals = query_rows(
                    'SELECT a.id_alquiler,
                            p.titulo,
                            COALESCE((SELECT po.moneda FROM propiedad_ofertas po WHERE po.id_propiedad=a.id_propiedad AND po.id_tipo_transaccion IN (2,3) ORDER BY po.id_tipo_transaccion LIMIT 1),p.moneda) AS moneda,
                            a.monto_mensual AS monto,
                            a.fecha_inicio AS fecha,
                            a.fecha_fin,
                            a.estado,
                            u.nombre,
                            u.apellido
                     FROM alquileres a
                     JOIN propiedades p ON p.id_propiedad=a.id_propiedad
                     JOIN usuarios u ON u.id_usuario=a.id_cliente
                     WHERE (?=1 OR a.id_agente=?)
                       AND (p.titulo LIKE ? OR u.nombre LIKE ?)
                     ORDER BY fecha DESC',
                    [(int)$admin, $user['id'], $like, $like]
                );
                ?>

                <?php if ($section === 'operaciones'): ?>

                    <!-- Registrar operación -->
                    <details class="content-card">
                        <summary><strong>Registrar una venta o alquiler</strong></summary>
                        <p>El tipo de operación se toma de la propiedad. Al confirmar, sale del catálogo público.</p>

                        <form method="post" class="form-grid">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="accion" value="operacion">

                            <div class="field">
                                <label for="propiedad">Propiedad</label>
                                <select id="propiedad" name="propiedad" required>
                                    <option value="">Seleccionar</option>
                                    <?php foreach ($props as $p): ?>
                                        <?php
                                        if (
                                            (int)$p['id_estado_verificacion'] !== 3
                                            || !in_array((int)$p['id_estado_comercial'], [1, 2, 5], true)
                                        ) {
                                            continue;
                                        }
                                        ?>
                                        <?php foreach ((new OfertaDAO($db))->read('propiedad',(int)$p['id_propiedad']) as $offer): ?>
                                        <option value="<?= (int)$p['id_propiedad'] ?>:<?= (int)$offer['id_tipo_transaccion'] ?>"><?= e($p['titulo'].' · '.$offer['nombre'].' · '.$offer['moneda'].' '.$offer['precio']) ?></option>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="field">
                                <label for="cliente">Cliente</label>
                                <select id="cliente" name="cliente" required>
                                    <option value="">Seleccionar</option>
                                    <?php person_options($clients); ?>
                                </select>
                            </div>

                            <div class="field">
                                <label for="monto">Importe final (moneda de la operación elegida)</label>
                                <input id="monto" name="monto" type="number" min="0.01" step="0.01" required>
                            </div>

                            <div class="field">
                                <label for="inicio">Inicio (solo alquileres)</label>
                                <input id="inicio" name="inicio" type="date">
                            </div>

                            <div class="field">
                                <label for="fin">Fin (solo alquileres)</label>
                                <input id="fin" name="fin" type="date">
                            </div>

                            <div class="field full">
                                <button class="btn">Confirmar operación</button>
                            </div>
                        </form>
                    </details>

                <?php else: ?>

                    
                    <div class="mini-stat-grid">
                        <div class="mini-stat">
                            <strong><?= count($props) ?></strong>
                            <span>Propiedades</span>
                        </div>

                        <div class="mini-stat">
                            <strong><?= count($sales) ?></strong>
                            <span>Ventas concretadas</span>
                        </div>

                        <div class="mini-stat">
                            <strong><?= count(array_filter($rentals, fn($r) => $r['estado'] === 'Activo')) ?></strong>
                            <span>Alquileres activos</span>
                        </div>
                    </div>

                    <button class="btn btn-outline" type="button" data-print>Imprimir / guardar PDF</button>

                <?php endif; ?>

              
                <?php foreach (['Ventas' => $sales, 'Alquileres' => $rentals] as $label => $rows): ?>
                    <div class="content-card">
                        <h2><?= $label ?></h2>

                        <div class="table-wrap">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Propiedad</th>
                                        <th>Cliente</th>
                                        <th>Importe</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td><?= e($row['titulo']) ?></td>
                                            <td><?= e($row['nombre'] . ' ' . $row['apellido']) ?></td>
                                            <td><?= e($row['moneda'] . ' ' . number_format((float)$row['monto'], 0, ',', '.')) ?></td>
                                            <td><?= e(date('d/m/Y', strtotime($row['fecha']))) ?></td>
                                            <td>
                                                <?= e($row['estado'] ?? 'Concretada') ?>

                                                <?php if ($section === 'operaciones' && ($row['estado'] ?? '') === 'Activo'): ?>
                                                    <form method="post">
                                                        <?php csrf_field(); ?>
                                                        <input type="hidden" name="accion" value="finalizar_alquiler">
                                                        <input type="hidden" name="id" value="<?= $row['id_alquiler'] ?>">
                                                        <button class="text-link" type="submit">Finalizar alquiler</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if (!$rows): ?>
                            <p>No hay operaciones para mostrar.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>


            <?php /* ================================================
                   | PERFIL
                   ================================================ */ ?>
            <?php elseif ($section === 'perfil'): ?>

                <div class="content-card">
                    <h2>Tu cuenta</h2>
                    <p><?= e($user['name'] . ' ' . $user['surname'] . ' · ' . $user['email']) ?></p>
                    <a class="btn" href="/Inmobiliaria/cuenta.php">Editar perfil y contraseña</a>
                </div>

            <?php endif; ?>

        </div>
    </div>
</section>

<?php require __DIR__ . '/sistema/Vistas/Diseno/pie.php'; ?>
