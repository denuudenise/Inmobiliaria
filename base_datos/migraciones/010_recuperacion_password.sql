-- Ejecutar en la base seleccionada del servidor.
CREATE TABLE IF NOT EXISTS recuperaciones_password (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 id_usuario INT NOT NULL,
 token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 password_anterior_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 expira DATETIME NOT NULL,
 usado DATETIME NULL,
 creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_recuperacion_token (token_hash),
 KEY idx_recuperacion_usuario (id_usuario),
 CONSTRAINT fk_recuperacion_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS limites_recuperacion (
 clave CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
 inicio BIGINT UNSIGNED NOT NULL,
 intentos INT UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
