-- Permite recibir consultas desde la ficha de una propiedad sin crear una cuenta.
-- Ejecutar una sola vez sobre la base inmobiliaria existente.
ALTER TABLE contactos
    MODIFY id_cliente INT(11) NULL,
    ADD COLUMN nombre_contacto VARCHAR(120) NULL AFTER descripcion,
    ADD COLUMN email_contacto VARCHAR(190) NULL AFTER nombre_contacto,
    ADD COLUMN telefono_contacto VARCHAR(40) NULL AFTER email_contacto;
