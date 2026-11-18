# Recuperación de contraseña

## Instalación en el servidor
1. Hacer un respaldo de la base actual.
2. Seleccionar la base del servidor en phpMyAdmin e importar únicamente `base_datos/migraciones/010_recuperacion_password.sql`. Agrega dos tablas y no modifica usuarios ni propiedades. Puede ejecutarse nuevamente.
3. Subir los archivos PHP modificados, las páginas recuperar-password.php y restablecer-password.php, configuracion/correo.php, sistema/DAOs/RecuperacionDAO.php, sistema/Nucleo/CorreoRecuperacion.php y sistema/Externos/PHPMailer completo. Incluir el diccionario de idiomas y los archivos de traducción locales.
4. Copiar configuracion/correo.local.ejemplo.php como configuracion/correo.local.php. Completar username, password y base_url.
   - username: Gmail del proyecto.
   - password: contraseña de aplicación de Google, NO la contraseña habitual. Activar verificación en dos pasos. Instrucciones: https://support.google.com/accounts/answer/185833?hl=es
   - base_url: URL pública HTTPS exacta donde está instalada la aplicación, sin barra final. El enlace enviado se construye desde esta configuración, no desde el encabezado Host.
5. SMTP usa smtp.gmail.com, puerto 587 y STARTTLS. El alojamiento debe permitir conexiones salientes, PHP OpenSSL y PDO MySQL.
6. Probar con una cuenta propia registrada: recibir correo, abrir enlace, cambiar clave e ingresar. Revisar spam si no llega.

La copia “s30_Inmobiliaria con recuperacion.sql” en Descargas contiene el dump original más las nuevas tablas: usarla para instalar en una base VACÍA. Para una base que ya tiene datos, usar únicamente la migración 010.

correo.local.php está excluido de Git y configuracion/ está bloqueada por .htaccess. Si el servidor no utiliza Apache, configurar esa protección en el servidor web. Nunca subir contraseñas al repositorio.

## Funcionamiento
- Enlace “¿Olvidaste tu contraseña?” en el ingreso.
- Mensaje público idéntico para correos existentes, inexistentes o limitados.
- Token aleatorio de 256 bits: se guarda únicamente su hash; vence a los 30 minutos y se utiliza una sola vez.
- Máximo 3 solicitudes por correo y 20 por IP en una hora.
- Al recuperar, se invalidan los demás enlaces y las sesiones anteriores en su siguiente acceso protegido. Las sesiones previas a esta actualización también deberán iniciar sesión nuevamente.
- Cambiar la contraseña desde el perfil invalida los enlaces pendientes.
- Formulario protegido con CSRF. El token se retira de la URL antes de cargar recursos de la página.
- Correo y pantallas en español o inglés, según idioma seleccionado.
- Si falla SMTP no se cambia ninguna contraseña y se elimina el token que no pudo enviarse. El registro del servidor informa el fallo sin guardar claves ni enlaces.
- El envío SMTP es síncrono; el mensaje es genérico, pero los tiempos de respuesta pueden variar. Para un despliegue de alto volumen, usar una cola de correo y un trabajador separado.

## Mantenimiento opcional
Eliminar periódicamente los tokens vencidos o usados de hace más de un día y los límites viejos:
```sql
DELETE FROM recuperaciones_password WHERE expira < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY);
DELETE FROM limites_recuperacion WHERE inicio < UNIX_TIMESTAMP() - 86400;
```

## Dependencia
PHPMailer v7.2.0, fuentes oficiales y licencia LGPL incluidas en sistema/Externos/PHPMailer.
https://github.com/PHPMailer/PHPMailer/tree/v7.2.0
