USE inmobiliaria;
ALTER TABLE solicitudes_publicacion ADD COLUMN IF NOT EXISTS id_agente INT NULL;
ALTER TABLE solicitudes_publicacion ADD COLUMN IF NOT EXISTS moneda ENUM('USD','ARS') NOT NULL DEFAULT 'USD';
ALTER TABLE propiedades ADD COLUMN IF NOT EXISTS id_solicitud INT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS ux_propiedad_solicitud ON propiedades(id_solicitud);
ALTER TABLE intereses ADD COLUMN IF NOT EXISTS es_favorito TINYINT(1) NOT NULL DEFAULT 0;
UPDATE intereses SET es_favorito=1 WHERE estado IN ('Interesado','Favorito');
ALTER TABLE contactos ADD COLUMN IF NOT EXISTS estado ENUM('Pendiente','Respondida','Cerrada') NOT NULL DEFAULT 'Pendiente';
ALTER TABLE contactos ADD COLUMN IF NOT EXISTS fecha_respuesta DATETIME NULL;
