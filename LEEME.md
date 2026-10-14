# Inmobiliaria Nuevo Hogar

Aplicación PHP 8.2 + MariaDB/MySQL, con catálogo público y paneles de Cliente, Agente y Administrador.

## En este equipo

1. Iniciar Apache y MySQL desde XAMPP.
2. Abrir http://localhost/Inmobiliaria/.
3. La carpeta `C:\xampp\htdocs\Inmobiliaria` enlaza al repositorio de este chat; no hay que copiar los archivos después de cada cambio.

La base utilizada es `inmobiliaria`, en `127.0.0.1:3306`. La base anterior `inmobiliaria_nuevo_hogar` no se modifica.

## Instalar en otro entorno

1. Colocar el proyecto en `htdocs/Inmobiliaria` (las rutas usan ese prefijo).
2. Importar `base_datos/esquema.sql` en una base nueva, o restaurar el SQL recibido si se quieren conservar esos datos.
3. Aplicar `base_datos/migraciones/003_gestion.sql`. Si se parte de un esquema anterior sin alquileres o solicitudes, aplicar antes 001 y 002.
4. Opcional: ejecutar desde la terminal `C:\xampp\php\php.exe herramientas\cargar-demo.php`.

El cargador demo es exclusivo de terminal y no sobrescribe contraseñas ni cuentas existentes. Las fotografías de demostración se incluyen en `recursos/imagenes`; son ilustrativas.

La conexión permite variables `INMOBILIARIA_DB_HOST`, `INMOBILIARIA_DB_PORT`, `INMOBILIARIA_DB_NAME`, `INMOBILIARIA_DB_USER`, `INMOBILIARIA_DB_PASSWORD`.

## Funciones por rol

- Visitante: catálogo con filtros por ubicación/dirección/título, tipo, operación, moneda y precio; detalle, galería y mapa; registro e ingreso.
- Cliente: favoritos guardados en base de datos, consultas y respuestas, solicitudes con imágenes y videos, corrección de solicitudes pendientes o rechazadas, propiedades publicadas, compras/alquileres, perfil y cambio de contraseña.
- Agente: propiedades propias, alta/edición con fotos (requiere aprobación), solicitudes asignadas y toma de solicitudes sin agente, consultas de sus propiedades, clientes vinculados, registro de operaciones y finalización de alquileres, reportes imprimibles.
- Administrador: gestión global; asignación, aprobación y rechazo de solicitudes; aprobación de propiedades; creación de usuarios, roles y activación/desactivación; consultas, operaciones y reportes.

Los cambios que requieren autenticación usan CSRF. La publicación y el cierre de operaciones usan transacciones; un mismo inmueble no se publica dos veces desde la misma solicitud ni se vende dos veces. Las consultas y favoritos se vinculan al cliente autenticado.

## Cuentas de demostración

| Rol | Correo | Contraseña inicial |
| --- | --- | --- |
| Administrador | admin@nuevohogar.test | AdminDemo!2026 |
| Agente | agente@nuevohogar.test | AgenteDemo!2026 |
| Cliente | cliente@nuevohogar.test | ClienteDemo!2026 |

Solo para pruebas locales. No son las cuentas originales importadas. Las contraseñas están almacenadas mediante `password_hash`.

## Organización

- `cliente/`: panel y actividad del cliente.
- `gestion/`: panel de agentes y administradores.
- `sistema/DAOs/`: acceso a datos y transacciones de gestión.
- `sistema/Nucleo/`: autenticación, CSRF, conexión y archivos.
- `sistema/Vistas/`: navegación y diseño compartido.
- `base_datos/migraciones/`: cambios aditivos al esquema.
- `herramientas/cargar-demo.php`: carga local repetible de ejemplos.
- `archivos/`: archivos subidos (fuera del control de versiones).

## Alcance de esta versión

Las consultas se responden dentro del sistema. No hay envío automático de correo, recuperación por email ni ingreso con Google. Se omitieron botones de esos servicios porque necesitan configuración externa. Los enlaces al mapa abren Google Maps. El SQL original puede referenciar fotos que no se adjuntaron al proyecto: el panel indica si falta el archivo y permite agregar nuevas fotos desde la solicitud.

El registro usa un formulario breve y solicita únicamente los datos usados por la aplicación. Los paneles muestran tres indicadores y tareas pendientes; el detalle está en secciones desplegables. Las tablas se desplazan dentro de su contenedor en celulares.
