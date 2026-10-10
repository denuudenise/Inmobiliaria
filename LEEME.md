# Inmobiliaria Nuevo Hogar

## Cómo iniciar el sitio

1. En el panel de XAMPP, iniciá **Apache** y **MySQL**.
2. En phpMyAdmin, importá `base_datos/esquema.sql`. Esto crea la base `inmobiliaria`, las tablas principales y dos tablas adicionales: solicitudes para publicar propiedades y alquileres.
3. Abrí `http://localhost/Inmobiliaria/` y registrá una cuenta. Las cuentas creadas desde la web quedan como clientes.

La conexión local por defecto usa `127.0.0.1:3306`, base `inmobiliaria`, usuario `root` sin contraseña. Si tu instalación usa otros datos, configurá `INMOBILIARIA_DB_HOST`, `INMOBILIARIA_DB_PORT`, `INMOBILIARIA_DB_NAME`, `INMOBILIARIA_DB_USER` e `INMOBILIARIA_DB_PASSWORD` en Apache.

## Organización de carpetas

- `cliente/`: páginas propias del panel del cliente: resumen, perfil, operaciones, solicitudes y formulario de publicación.
- `sistema/DAOs/`: consultas parametrizadas que leen y guardan los datos en MySQL.
- `sistema/Nucleo/`: conexión PDO, sesión, control de acceso, protección CSRF y funciones compartidas.
- `sistema/Vistas/`: partes visuales compartidas, como el encabezado, pie y menú del cliente.
- `configuracion/`: datos de conexión a MySQL.
- `base_datos/`: esquema de instalación y migraciones SQL.
- `recursos/`: estilos CSS y JavaScript.
- `archivos/`: fotos o videos que se adjuntan a las solicitudes.

Los roles Agente y Administrador se reconocen durante el login y muestran un aviso en el inicio. En esta etapa solo está desarrollado el panel del cliente.

## Nota sobre el esquema

El SQL original no tenía tablas para solicitudes de publicación ni alquileres. Se agregaron `solicitudes_publicacion`, `solicitud_multimedia` y `alquileres`. En una base ya instalada, ejecutá los archivos de `base_datos/migraciones/`; para una instalación nueva, alcanza con importar `base_datos/esquema.sql`.
