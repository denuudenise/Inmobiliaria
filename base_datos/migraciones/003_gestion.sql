USE inmobiliaria;
ALTER TABLE solicitudes_publicacion ADD COLUMN IF NOT EXISTS id_agente INT NULL;
ALTER TABLE solicitudes_publicacion ADD COLUMN IF NOT EXISTS moneda ENUM('USD','ARS') NOT NULL DEFAULT 'USD';
ALTER TABLE propiedades ADD COLUMN IF NOT EXISTS id_solicitud INT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS ux_propiedad_solicitud ON propiedades(id_solicitud);
CREATE TABLE IF NOT EXISTS favoritos (
 id_usuario INT NOT NULL, id_propiedad INT NOT NULL, fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(id_usuario,id_propiedad),
 FOREIGN KEY(id_usuario) REFERENCES usuarios(id_usuario),
 FOREIGN KEY(id_propiedad) REFERENCES propiedades(id_propiedad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS consultas (
 id_consulta INT AUTO_INCREMENT PRIMARY KEY, id_cliente INT NOT NULL, id_propiedad INT NOT NULL,
 mensaje TEXT NOT NULL, respuesta TEXT NULL, estado ENUM('Pendiente','Respondida','Cerrada') NOT NULL DEFAULT 'Pendiente',
 fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, fecha_respuesta DATETIME NULL,
 FOREIGN KEY(id_cliente) REFERENCES usuarios(id_usuario), FOREIGN KEY(id_propiedad) REFERENCES propiedades(id_propiedad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
