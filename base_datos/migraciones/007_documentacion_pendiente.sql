-- Permite enviar solicitudes aunque el propietario todavía no tenga planos o escritura.
ALTER TABLE solicitudes_publicacion
    ADD COLUMN faltan_planos TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN falta_escritura TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN observaciones_documentacion TEXT NULL;

ALTER TABLE propiedades
    ADD COLUMN faltan_planos TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN falta_escritura TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN observaciones_documentacion TEXT NULL;
