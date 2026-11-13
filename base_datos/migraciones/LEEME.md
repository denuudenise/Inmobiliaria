# Documentación privada e impuestos

Después de importar `inmobiliaria (5).sql`, ejecutar `004_documentacion_impuestos.sql` sobre la misma base. En esta computadora ya se aplicó con un respaldo previo. No reemplaza ni borra los datos existentes.

La migración permite documentos en solicitudes pendientes y crea `propiedad_impuestos`. Al aprobar, los registros quedan asociados a la propiedad. La tabla `documentos` existente se conserva.

Los documentos nuevos se guardan en `sistema/privados`, protegida por Apache y excluida de Git. Se descargan a través de `documento.php`, que valida la sesión y el acceso del propietario, administrador o agente actualmente responsable. Los archivos de otras instalaciones deben trasladarse por separado: Git no incluye documentos privados ni respaldos.

Se admiten PDF, JPG, PNG y WEBP, hasta 8 documentos de 2 MB por envío. También se aplican los límites totales de carga configurados en PHP. Los archivos públicos y los privados se guardan por separado.

Los impuestos registran concepto, base, porcentaje, importe calculado, moneda (ARS/USD), período mensual (AAAA-MM), usuario y fecha. El servidor recalcula el importe, con redondeo a dos decimales. Se agregan nuevos registros desde la ficha sin sobrescribir períodos anteriores.

Prueba de integración: `C:/xampp/php/php.exe herramientas/probar-expediente.php`. Crea y elimina una base temporal; requiere MySQL, Chrome y Playwright. Las rutas de Node y Playwright se pueden configurar con INMO_NODE e INMO_PLAYWRIGHT. También puede ejecutarse `herramientas/probar-modelo-compartido.php` para verificar los flujos existentes.

## Ofertas y características

Después de la migración 004, ejecutar `005_ofertas_caracteristicas.sql`. En esta computadora también está aplicada con respaldo previo. Conserva las ofertas existentes y permite asociar varias operaciones y características a las solicitudes, copiándolas al aprobar.

En carga y edición se pueden marcar Venta, Alquiler y Alquiler Temporario simultáneamente. Cada operación seleccionada requiere precio positivo y una moneda, ARS o USD. Los precios de alquiler son mensuales, incluido el temporario. Desmarcar una operación elimina esa oferta al guardar; no borra las otras. Los campos de precio/operación principales de la propiedad se mantienen por compatibilidad con la aplicación existente.

## Consultas sin registro

Después de la migración 005, ejecutar `006_consultas_sin_registro.sql`. Permite que cualquier visitante consulte desde la ficha de una propiedad indicando su nombre y un correo o teléfono, sin iniciar sesión. Los datos quedan en `contactos`, asignados al agente de la propiedad, y aparecen en Consultas en los paneles de agente y administrador.

## Documentación pendiente y carga de archivos

Después de importar el esquema de la base de datos, ejecutar `007_documentacion_pendiente.sql`. Permite indicar si faltan planos o escritura y guardar la aclaración del propietario en la solicitud y en la propiedad al aprobarla. La aclaración se muestra en la ficha privada de documentación. La solicitud permite adjuntar hasta 8 fotos/videos de 2 MB cada uno y hasta 8 documentos privados de 2 MB cada uno; los selectores permiten agregar archivos de forma acumulativa y muestran los nombres seleccionados.

Los cierres eligen una oferta concreta. La venta y el alquiler conservan su propia moneda y tipo de operación. Una operación cerrada retira la propiedad del catálogo según los estados comerciales existentes.
