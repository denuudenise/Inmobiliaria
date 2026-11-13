-- Guarda el monto mínimo aceptado para venta como dato interno.
ALTER TABLE solicitud_ofertas
    ADD COLUMN precio_minimo DECIMAL(12,2) NULL;

ALTER TABLE propiedad_ofertas
    ADD COLUMN precio_minimo DECIMAL(12,2) NULL;
