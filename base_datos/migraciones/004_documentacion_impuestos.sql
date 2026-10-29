ALTER TABLE documentos MODIFY id_propiedad INT NULL;
ALTER TABLE documentos ADD COLUMN IF NOT EXISTS id_solicitud INT NULL;
CREATE TABLE IF NOT EXISTS propiedad_impuestos (
 id_impuesto INT AUTO_INCREMENT PRIMARY KEY,
 id_propiedad INT NULL,
 id_solicitud INT NULL,
 concepto VARCHAR(100) NOT NULL,
 base DECIMAL(12,2) NOT NULL,
 porcentaje DECIMAL(5,2) NOT NULL,
 importe DECIMAL(12,2) NOT NULL,
 moneda VARCHAR(3) NOT NULL,
 periodo CHAR(7) NOT NULL,
 id_usuario INT NOT NULL,
 fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (id_propiedad) REFERENCES propiedades(id_propiedad),
 FOREIGN KEY (id_solicitud) REFERENCES solicitudes_publicacion(id_solicitud),
 FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
