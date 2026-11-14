-- Normaliza y mejora los registros del volcado inmobiliaria (1).sql.
-- Ejecutar una vez DESPUES de importar el volcado en la base inmobiliaria.
-- Conserva el historial de operaciones cerradas y no inventa datos del propietario.

DROP TEMPORARY TABLE IF EXISTS tmp_ajuste_agentes_catalogo;
CREATE TEMPORARY TABLE tmp_ajuste_agentes_catalogo (
    id_propiedad INT NOT NULL PRIMARY KEY,
    id_agente_nuevo INT NOT NULL
);

-- Reparte las publicaciones aprobadas y disponibles entre los siete agentes activos.
-- Al finalizar quedan entre 4 y 5 propiedades disponibles por agente.
INSERT INTO tmp_ajuste_agentes_catalogo (id_propiedad, id_agente_nuevo) VALUES
    (5, 11), (6, 11), (7, 11), (8, 11), (9, 11),
    (10, 52), (11, 52), (19, 52), (21, 52),
    (13, 12),
    (24, 55), (26, 55), (28, 55), (34, 55);

START TRANSACTION;

-- Retira del catálogo dos registros de prueba claramente incompletos.
-- Se conservan para que no se pierdan sus referencias e historial.
UPDATE propiedades
SET id_estado_verificacion = 5,
    id_estado_comercial = 6,
    fecha_verificacion = NOW(),
    motivo_rechazo = 'Registro de prueba con datos incompletos; retirado hasta corregir la ficha.'
WHERE (id_propiedad = 15 AND titulo = 'Mansión fachada estilo clásico en Palermo')
   OR (id_propiedad = 48 AND titulo = 'TEST');

-- Hace que el estado visible de la publicación coincida con la propiedad.
UPDATE publicaciones pub
JOIN propiedades p ON p.id_propiedad = pub.id_propiedad
SET pub.estado = CASE p.id_estado_verificacion
    WHEN 2 THEN 'Pendiente'
    WHEN 3 THEN 'Publicada'
    WHEN 4 THEN 'Rechazada'
    WHEN 5 THEN 'Inactiva'
    ELSE pub.estado
END,
pub.observaciones = CASE
    WHEN p.id_propiedad IN (15, 48) THEN 'Registro de prueba incompleto; retirado del catálogo.'
    ELSE pub.observaciones
END
WHERE p.id_estado_verificacion IN (2, 3, 4, 5)
  AND (pub.estado <> CASE p.id_estado_verificacion
        WHEN 2 THEN 'Pendiente'
        WHEN 3 THEN 'Publicada'
        WHEN 4 THEN 'Rechazada'
        WHEN 5 THEN 'Inactiva'
        ELSE pub.estado
      END OR p.id_propiedad IN (15, 48));

-- Marca documentación pendiente cuando el volcado no contiene archivos privados
-- de ese tipo vinculados directamente a la propiedad. No elimina observaciones existentes.
UPDATE propiedades p
SET p.faltan_planos = NOT EXISTS (
        SELECT 1 FROM documentos d
        WHERE d.id_propiedad = p.id_propiedad
          AND d.id_solicitud IS NULL
          AND LOWER(d.tipo) LIKE '%plano%'
    ),
    p.falta_escritura = NOT EXISTS (
        SELECT 1 FROM documentos d
        WHERE d.id_propiedad = p.id_propiedad
          AND d.id_solicitud IS NULL
          AND LOWER(d.tipo) LIKE '%escritura%'
    ),
    p.observaciones_documentacion = CASE
        WHEN (p.faltan_planos = 1 OR p.falta_escritura = 1)
             AND (p.observaciones_documentacion IS NULL OR TRIM(p.observaciones_documentacion) = '')
        THEN 'El volcado no contiene estos documentos asociados; confirmar con el propietario y adjuntarlos cuando estén disponibles.'
        ELSE p.observaciones_documentacion
    END
WHERE p.id_estado_verificacion = 3;

-- Si a una propiedad aprobada le falta su oferta principal, la reconstruye
-- desde los campos heredados de la propiedad. No reemplaza ofertas existentes.
INSERT INTO propiedad_ofertas (id_propiedad, id_tipo_transaccion, precio, moneda, precio_minimo)
SELECT p.id_propiedad, p.id_tipo_transaccion, p.precio, p.moneda, NULL
FROM propiedades p
WHERE p.id_estado_verificacion = 3
  AND p.precio > 0
  AND NOT EXISTS (
      SELECT 1 FROM propiedad_ofertas po
      WHERE po.id_propiedad = p.id_propiedad
        AND po.id_tipo_transaccion = p.id_tipo_transaccion
  );

-- Cambia la gestión solo de propiedades aprobadas y disponibles.
-- Las vendidas, alquiladas, reservadas y las consultas históricas quedan intactas.
INSERT INTO historial_asignaciones
    (tipo_entidad, id_entidad, agente_anterior, agente_nuevo, id_usuario_cambio, motivo)
SELECT 'propiedad', p.id_propiedad, p.id_agente_creador, m.id_agente_nuevo, actor.id_usuario,
       'Redistribución de propiedades disponibles para equilibrar la cartera inicial.'
FROM propiedades p
JOIN tmp_ajuste_agentes_catalogo m ON m.id_propiedad = p.id_propiedad
JOIN usuarios a ON a.id_usuario = m.id_agente_nuevo AND a.id_rol = 2 AND a.activo = 1
JOIN usuarios actor ON actor.id_usuario = 58 AND actor.id_rol = 1 AND actor.activo = 1
WHERE p.id_estado_verificacion = 3
  AND p.id_estado_comercial = 1
  AND p.id_agente_creador <> m.id_agente_nuevo;

UPDATE propiedades p
JOIN tmp_ajuste_agentes_catalogo m ON m.id_propiedad = p.id_propiedad
JOIN usuarios a ON a.id_usuario = m.id_agente_nuevo AND a.id_rol = 2 AND a.activo = 1
SET p.id_agente_creador = m.id_agente_nuevo
WHERE p.id_estado_verificacion = 3
  AND p.id_estado_comercial = 1
  AND p.id_agente_creador <> m.id_agente_nuevo;

COMMIT;

DROP TEMPORARY TABLE IF EXISTS tmp_ajuste_agentes_catalogo;
