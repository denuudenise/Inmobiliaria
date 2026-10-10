USE inmobiliaria;

CREATE TABLE IF NOT EXISTS solicitudes_publicacion (
  id_solicitud INT NOT NULL AUTO_INCREMENT,
  id_cliente INT NOT NULL,
  id_tipo_propiedad INT NOT NULL,
  id_tipo_transaccion INT NOT NULL,
  titulo VARCHAR(200) NOT NULL,
  direccion VARCHAR(255) NOT NULL,
  localidad VARCHAR(100) NOT NULL,
  descripcion TEXT NOT NULL,
  precio_estimado DECIMAL(12,2) DEFAULT NULL,
  superficie_total_m2 DECIMAL(8,2) NOT NULL,
  ambientes INT DEFAULT NULL,
  dormitorios INT DEFAULT NULL,
  banos INT DEFAULT NULL,
  antiguedad_anos INT DEFAULT 0,
  estado ENUM('Pendiente de Verificación','En revisión','En contacto','Aprobada','Rechazada') NOT NULL DEFAULT 'Pendiente de Verificación',
  motivo_rechazo TEXT DEFAULT NULL,
  fecha_solicitud DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_solicitud),
  KEY idx_solicitud_cliente_fecha (id_cliente, fecha_solicitud),
  KEY idx_solicitud_estado (estado),
  CONSTRAINT fk_solicitud_cliente FOREIGN KEY (id_cliente) REFERENCES usuarios (id_usuario) ON DELETE CASCADE,
  CONSTRAINT fk_solicitud_tipo FOREIGN KEY (id_tipo_propiedad) REFERENCES tipos_propiedad (id_tipo_propiedad),
  CONSTRAINT fk_solicitud_operacion FOREIGN KEY (id_tipo_transaccion) REFERENCES tipos_transaccion (id_tipo_transaccion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS solicitud_multimedia (
  id_multimedia INT NOT NULL AUTO_INCREMENT,
  id_solicitud INT NOT NULL,
  url_archivo VARCHAR(500) NOT NULL,
  tipo ENUM('Imagen','Video') NOT NULL DEFAULT 'Imagen',
  fecha_carga DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_multimedia),
  KEY idx_solicitud_multimedia (id_solicitud),
  CONSTRAINT fk_solicitud_multimedia FOREIGN KEY (id_solicitud) REFERENCES solicitudes_publicacion (id_solicitud) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

