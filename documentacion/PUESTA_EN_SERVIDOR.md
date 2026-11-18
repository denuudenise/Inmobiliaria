# Publicar en el hosting

## Requisitos

- PHP 8.1 o posterior, Apache con `.htaccess` y MySQL/MariaDB.
- Extensiones PHP: `pdo_mysql`, `mbstring`, `openssl` y `fileinfo`.
- El proyecto está configurado para vivir en `/Inmobiliaria`, por ejemplo `https://tu-dominio.com/Inmobiliaria/`.
- La cuenta del hosting debe permitir cargar archivos por lo menos de 20 MB y conexiones SMTP salientes por el puerto 587 si se usa recuperación por Gmail.

## Archivos y base de datos

1. Crear una base MySQL vacía y un usuario en el panel del hosting.
2. Importar el volcado completo `inmobiliaria-ivan.sql` desde phpMyAdmin **solo si la base está vacía**. Contiene datos de usuarios y propiedades; no subirlo a `public_html` ni importarlo sobre tablas que ya tengan datos.
3. Subir los archivos del proyecto a `public_html/Inmobiliaria/`. Confirmar que también se subieron los archivos ocultos `.htaccess` y copiar aparte la carpeta local `archivos/`, porque Git la excluye.
4. Crear `configuracion/mysql.local.php` a partir de `configuracion/mysql.local.ejemplo.php` y completar los datos creados en el panel. `configuracion/mysql.php` ya carga ese archivo local si existe.
5. Crear `configuracion/correo.local.php` a partir del ejemplo y completar el usuario SMTP, la contraseña de aplicación y la URL HTTPS pública terminada en `/Inmobiliaria`.
6. Crear las carpetas `archivos/propiedades`, `archivos/solicitudes` y `sistema/privados` si faltan. PHP debe poder escribir en ellas. Los documentos privados se guardan en `sistema/privados` y se entregan desde `documento.php` tras validar permisos.

## Seguridad y comprobación

- Mantener los volcados SQL, `mysql.local.php` y `correo.local.php` fuera de Git y de la carpeta pública.
- Conservar las reglas `.htaccess` de raíz, `configuracion`, `sistema` y `base_datos`.
- En una base existente, respaldarla primero y aplicar únicamente las migraciones pendientes; no importar el volcado completo.
- Abrir la portada, iniciar sesión, revisar una propiedad con fotos/video, probar una consulta y comprobar que `configuracion/`, `sistema/` y `base_datos/` devuelvan acceso denegado.
- Revisar `error.log` del hosting si una página devuelve un error 500. No dejar `display_errors` activado en producción.
