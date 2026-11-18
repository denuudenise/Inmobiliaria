-- Ejecutar una sola vez en la base inmobiliaria.
-- Conserva el orden histórico de los adjuntos ya cargados.
ALTER TABLE solicitud_multimedia
    ADD COLUMN orden INT NOT NULL DEFAULT 0 AFTER tipo;

UPDATE solicitud_multimedia
SET orden = id_multimedia
WHERE orden = 0;
