-- ============================================================
-- Schema: pagos_sala
-- App: Registro de Pagos — Sala (ZarcoMecatronics)
-- Versión: 1.0 — junio 2026
-- Instrucciones: crear la BD pagos_sala, seleccionarla, y ejecutar este archivo.
-- ⚠️  Ajustar la constante UNIDAD en db.php con el nombre de la unidad/casino.
-- ⚠️  Ajustar las IPs de oficina en push_helpers.php (o desde el panel técnico).
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ------------------------------------------------------------
-- Pagos registrados en sala
-- ------------------------------------------------------------
CREATE TABLE `pagos` (
  `id`             int(11)        NOT NULL AUTO_INCREMENT,
  `pago_num`       int(11)        NOT NULL DEFAULT 1,
  `turno`          varchar(10)    NOT NULL,
  `asset`          varchar(20)    DEFAULT NULL,
  `modelo`         varchar(150)   DEFAULT NULL,
  `numero_serie`   varchar(150)   DEFAULT NULL,
  `unidad`         varchar(100)   DEFAULT 'Neza',
  `cantidad`       decimal(12,2)  DEFAULT NULL,
  `proveedor`      varchar(100)   DEFAULT NULL,
  `nivel`          varchar(50)    DEFAULT NULL,
  `cliente`        varchar(200)   DEFAULT NULL,
  `id_cliente`     varchar(50)    DEFAULT NULL,
  `apuesta`        decimal(12,2)  DEFAULT NULL,
  `fecha`          date           NOT NULL,
  `hora`           time           NOT NULL,
  `tecnico`        varchar(100)   DEFAULT NULL,
  `gerente`        varchar(100)   DEFAULT NULL,
  `fecha_registro` datetime       DEFAULT current_timestamp(),
  `cerrado`        tinyint(1)     DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- Cola de reintentos para cierres de día (push de inserts)
-- Un registro por fecha operativa — se reemplaza si ya existe
-- ------------------------------------------------------------
CREATE TABLE `push_pendiente` (
  `id`               int(11)                              NOT NULL AUTO_INCREMENT,
  `fecha_operativa`  date                                 NOT NULL,
  `archivo`          varchar(100)                         NOT NULL,
  `dia`              varchar(5)                           NOT NULL,
  `datos_json`       longtext                             NOT NULL,
  `intentos`         int(11)                              NOT NULL DEFAULT 0,
  `ultimo_intento`   datetime                             DEFAULT NULL,
  `creado`           datetime                             NOT NULL DEFAULT current_timestamp(),
  `estado`           enum('pendiente','enviado','error')  NOT NULL DEFAULT 'pendiente',
  `ultimo_error`     text                                 DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_fecha_operativa` (`fecha_operativa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- Cola de reintentos para ediciones y eliminaciones post-cierre
-- Un registro por (id_sala, tipo) — se reemplaza si ya existe
-- ------------------------------------------------------------
CREATE TABLE `push_evento` (
  `id`             int(11)                              NOT NULL AUTO_INCREMENT,
  `id_sala`        int(11)                              NOT NULL,
  `tipo`           enum('update','delete')              NOT NULL,
  `datos_json`     longtext                             NOT NULL,
  `intentos`       int(11)                              NOT NULL DEFAULT 0,
  `ultimo_intento` datetime                             DEFAULT NULL,
  `creado`         datetime                             NOT NULL DEFAULT current_timestamp(),
  `estado`         enum('pendiente','enviado','error')  NOT NULL DEFAULT 'pendiente',
  `ultimo_error`   text                                 DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_id_sala_tipo` (`id_sala`, `tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- Catálogo de técnicos (administrable desde panel técnico)
-- activo=0 oculta al técnico de los selects sin borrar historial
-- ------------------------------------------------------------
CREATE TABLE `tecnicos` (
  `id`     int(11)      NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1)   NOT NULL DEFAULT 1,
  `creado` datetime     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Técnicos iniciales — ajustar según el casino
INSERT INTO `tecnicos` (`nombre`) VALUES
('TECNICO 1'),
('TECNICO 2'),
('EVANDHER ZARCO');

-- ------------------------------------------------------------
-- Catálogo de gerentes (administrable desde panel técnico)
-- activo=0 oculta al gerente de los selects sin borrar historial
-- ------------------------------------------------------------
CREATE TABLE `gerentes` (
  `id`     int(11)      NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1)   NOT NULL DEFAULT 1,
  `creado` datetime     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Gerentes iniciales — ajustar según el casino
INSERT INTO `gerentes` (`nombre`) VALUES
('GERENTE 1'),
('GERENTE 3'),
('GERENTE 2');
