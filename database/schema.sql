-- ============================================================
--  DentaHistoria · Esquema de base de datos
--  MySQL 5.7+ / MariaDB 10.3+
--  Importar desde phpMyAdmin en la base de datos ya creada.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Usuarios del sistema
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre        VARCHAR(120) NOT NULL,
    email         VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol           ENUM('admin', 'odontologo') NOT NULL DEFAULT 'odontologo',
    activo        TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_acceso DATETIME NULL,
    creado_en     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Pacientes
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pacientes (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo            VARCHAR(20) NULL,
    nombres           VARCHAR(100) NOT NULL,
    apellidos         VARCHAR(100) NOT NULL,
    documento         VARCHAR(30) NULL,
    fecha_nacimiento  DATE NULL,
    sexo              ENUM('M', 'F', 'O') NOT NULL DEFAULT 'O',
    telefono          VARCHAR(30) NULL,
    email             VARCHAR(150) NULL,
    direccion         VARCHAR(255) NULL,
    ocupacion         VARCHAR(100) NULL,
    alergias          TEXT NULL,
    antecedentes      TEXT NULL,
    notas             TEXT NULL,
    creado_por        INT UNSIGNED NULL,
    creado_en         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pacientes_codigo (codigo),
    UNIQUE KEY uq_pacientes_documento (documento),
    KEY idx_pacientes_nombre (apellidos, nombres),
    KEY idx_pacientes_telefono (telefono),
    CONSTRAINT fk_pacientes_usuario FOREIGN KEY (creado_por) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Catálogo de procedimientos odontológicos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS procedimientos (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre       VARCHAR(150) NOT NULL,
    categoria    VARCHAR(60) NOT NULL DEFAULT 'General',
    descripcion  TEXT NULL,
    precio       DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    duracion_min INT UNSIGNED NOT NULL DEFAULT 30,
    activo       TINYINT(1) NOT NULL DEFAULT 1,
    creado_en    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_procedimientos_categoria (categoria),
    KEY idx_procedimientos_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Atenciones / registros de historia clínica
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS atenciones (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    paciente_id    INT UNSIGNED NOT NULL,
    usuario_id     INT UNSIGNED NOT NULL,
    fecha          DATE NOT NULL,
    hora           TIME NULL,
    motivo         VARCHAR(255) NULL,
    diagnostico    TEXT NULL,
    tratamiento    TEXT NULL,
    observaciones  TEXT NULL,
    dientes        VARCHAR(180) NULL,
    estado         ENUM('realizado', 'pendiente', 'seguimiento') NOT NULL DEFAULT 'realizado',
    proxima_cita   DATE NULL,
    creado_en      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_atenciones_paciente (paciente_id, fecha),
    KEY idx_atenciones_fecha (fecha),
    KEY idx_atenciones_estado (estado),
    CONSTRAINT fk_atenciones_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes (id) ON DELETE CASCADE,
    CONSTRAINT fk_atenciones_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Procedimientos realizados en cada atención
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS atencion_procedimientos (
    atencion_id      INT UNSIGNED NOT NULL,
    procedimiento_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (atencion_id, procedimiento_id),
    CONSTRAINT fk_ap_atencion FOREIGN KEY (atencion_id) REFERENCES atenciones (id) ON DELETE CASCADE,
    CONSTRAINT fk_ap_procedimiento FOREIGN KEY (procedimiento_id) REFERENCES procedimientos (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Odontograma: estado actual de cada pieza dental (FDI)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS odontograma (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    paciente_id    INT UNSIGNED NOT NULL,
    pieza          VARCHAR(3) NOT NULL,
    estado         ENUM('sano', 'caries', 'obturado', 'corona', 'endodoncia', 'extraccion', 'ausente', 'implante', 'sellante') NOT NULL DEFAULT 'sano',
    notas          VARCHAR(180) NULL,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_odontograma_pieza (paciente_id, pieza),
    KEY idx_odontograma_estado (estado),
    CONSTRAINT fk_odontograma_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Bitácora de auditoría
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS auditoria (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id INT UNSIGNED NULL,
    accion     VARCHAR(50) NOT NULL,
    entidad    VARCHAR(50) NOT NULL,
    entidad_id INT UNSIGNED NULL,
    detalle    VARCHAR(255) NULL,
    ip         VARCHAR(45) NULL,
    creado_en  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_auditoria_fecha (creado_en),
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
