CREATE TABLE IF NOT EXISTS solicitud_ofertas (
 id_solicitud INT NOT NULL, id_tipo_transaccion INT NOT NULL,
 precio DECIMAL(12,2) NOT NULL, moneda VARCHAR(3) NOT NULL,
 PRIMARY KEY(id_solicitud,id_tipo_transaccion),
 FOREIGN KEY(id_solicitud) REFERENCES solicitudes_publicacion(id_solicitud) ON DELETE CASCADE,
 FOREIGN KEY(id_tipo_transaccion) REFERENCES tipos_transaccion(id_tipo_transaccion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS solicitud_caracteristicas (
 id_solicitud INT NOT NULL, id_caracteristica INT NOT NULL,
 PRIMARY KEY(id_solicitud,id_caracteristica),
 FOREIGN KEY(id_solicitud) REFERENCES solicitudes_publicacion(id_solicitud) ON DELETE CASCADE,
 FOREIGN KEY(id_caracteristica) REFERENCES caracteristicas(id_caracteristica)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO solicitud_ofertas SELECT id_solicitud,id_tipo_transaccion,precio_estimado,moneda FROM solicitudes_publicacion WHERE precio_estimado>0;
INSERT IGNORE INTO propiedad_ofertas SELECT id_propiedad,id_tipo_transaccion,precio,moneda FROM propiedades WHERE precio>0;
ALTER TABLE alquileres ADD COLUMN IF NOT EXISTS moneda VARCHAR(3) NULL;
ALTER TABLE alquileres ADD COLUMN IF NOT EXISTS id_tipo_transaccion INT NULL;
UPDATE alquileres a JOIN propiedades p ON p.id_propiedad=a.id_propiedad SET a.moneda=p.moneda,a.id_tipo_transaccion=p.id_tipo_transaccion WHERE a.moneda IS NULL;
