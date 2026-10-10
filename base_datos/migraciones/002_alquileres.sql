USE inmobiliaria;

CREATE TABLE IF NOT EXISTS alquileres (
  id_alquiler INT NOT NULL AUTO_INCREMENT,
  id_propiedad INT NOT NULL,
  id_cliente INT NOT NULL,
  id_agente INT NOT NULL,
  monto_mensual DECIMAL(12,2) NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE DEFAULT NULL,
  estado ENUM('Pendiente','Activo','Finalizado','Cancelado') NOT NULL DEFAULT 'Pendiente',
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_alquiler),
  KEY idx_alquiler_cliente (id_cliente, fecha_inicio),
  KEY idx_alquiler_estado (estado),
  CONSTRAINT fk_alquiler_propiedad FOREIGN KEY (id_propiedad) REFERENCES propiedades (id_propiedad),
  CONSTRAINT fk_alquiler_cliente FOREIGN KEY (id_cliente) REFERENCES usuarios (id_usuario),
  CONSTRAINT fk_alquiler_agente FOREIGN KEY (id_agente) REFERENCES usuarios (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

