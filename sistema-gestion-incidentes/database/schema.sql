-- =====================================================================
-- Sistema Web de Gestion, Analitica y Reportes
-- Esquema de base de datos - MySQL / MariaDB
--
-- DECISIONES DE DISEÑO (ver README.md seccion "Analisis de form.xlsx"):
--   El archivo form.xlsx es un formulario XLSForm (ArcGIS Survey123) de
--   249 campos usado por Bomberos para el Formato Unico de Recoleccion
--   de Datos (FURD) de atencion de incidentes. Contiene 33 grupos, de
--   los cuales 4 son grupos REPETIBLES (personas afectadas x8, animales
--   afectados x4, edificaciones/vehiculos afectados x3, bomberos que
--   atendieron). Y 95 listas de seleccion (2244 opciones) en la hoja
--   "choices".
--
--   Arquitectura de datos elegida (hibrida, no 249 columnas sueltas):
--     1) Campos "core" (identificacion, fechas, ubicacion principal,
--        estado, prioridad, responsable) => columnas reales, indexadas,
--        usadas para busqueda/filtros/KPIs.
--     2) El resto de los ~200 campos del formulario original (causa,
--        SCI, forestal, etc.) => columna JSON `form_data` en la tabla
--        `cases`. Se mantiene 100% fiel a los nombres de campo del
--        Excel, sin inventar estructura, y permite variaciones futuras
--        del formulario sin migraciones.
--     3) Los 4 grupos repetibles => tablas hijas normalizadas
--        (case_buildings, case_persons, case_animals, case_firefighters)
--        para poder contarlos/filtrarlos (ej. KPI "personas rescatadas").
--     4) Las 95 listas de opciones => tablas genericas catalog_items,
--        editables desde el modulo de Configuracion sin tocar el
--        esquema.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Roles y usuarios
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(20) NOT NULL UNIQUE,   -- admin | usuario | consulta
    name        VARCHAR(60) NOT NULL,
    description VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(50)  NOT NULL UNIQUE,
    email           VARCHAR(120) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    full_name       VARCHAR(150) NOT NULL,
    role_id         INT NOT NULL,
    active          TINYINT(1) NOT NULL DEFAULT 1,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    last_login_at   DATETIME DEFAULT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id),
    INDEX idx_users_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Catalogos (listas de seleccion del formulario original + config)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS catalog_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    list_name   VARCHAR(120) NOT NULL,   -- ej: list_servicio, list_barrio
    item_value  VARCHAR(190) NOT NULL,
    item_label  VARCHAR(255) NOT NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    active      TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_list_value (list_name, item_value),
    INDEX idx_catalog_list (list_name, active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS case_statuses (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    code    VARCHAR(30) NOT NULL UNIQUE,
    label   VARCHAR(60) NOT NULL,
    color   VARCHAR(20) NOT NULL DEFAULT 'secondary',
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Casos (tabla principal)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cases (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    case_number         VARCHAR(20) NOT NULL UNIQUE,      -- CAS-2026-000001
    service_type        VARCHAR(190) DEFAULT NULL,        -- list_servicio (valor)
    incident_date       DATE DEFAULT NULL,
    report_time         TIME DEFAULT NULL,
    departure_time      TIME DEFAULT NULL,
    arrival_time        TIME DEFAULT NULL,
    closure_time        TIME DEFAULT NULL,
    station_time        TIME DEFAULT NULL,
    incident_commander  VARCHAR(190) DEFAULT NULL,
    address             VARCHAR(255) DEFAULT NULL,
    comuna              VARCHAR(120) DEFAULT NULL,
    barrio              VARCHAR(190) DEFAULT NULL,
    latitude            DECIMAL(10,6) DEFAULT NULL,
    longitude           DECIMAL(10,6) DEFAULT NULL,
    description          TEXT DEFAULT NULL,
    shift_chief         VARCHAR(190) DEFAULT NULL,
    status              VARCHAR(30) NOT NULL DEFAULT 'abierto',
    priority            VARCHAR(20) NOT NULL DEFAULT 'media',
    responsible_user_id INT DEFAULT NULL,
    assigned_to         INT DEFAULT NULL,    -- bombero asignado por el radio operador
    assigned_by         INT DEFAULT NULL,
    assigned_at         DATETIME DEFAULT NULL,
    signed_by           INT DEFAULT NULL,
    signed_at           DATETIME DEFAULT NULL,
    sign_method         VARCHAR(20) DEFAULT NULL,   -- dibujo | foto | codigo
    signature_path      VARCHAR(255) DEFAULT NULL,
    approved_by         INT DEFAULT NULL,    -- subcomandancia
    approved_at         DATETIME DEFAULT NULL,
    form_data           JSON DEFAULT NULL,   -- resto de los ~200 campos del FURD
    is_demo             TINYINT(1) NOT NULL DEFAULT 0,
    created_by          INT DEFAULT NULL,
    updated_by          INT DEFAULT NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    closed_at           DATETIME DEFAULT NULL,
    deleted_at          DATETIME DEFAULT NULL,
    CONSTRAINT fk_cases_responsible FOREIGN KEY (responsible_user_id) REFERENCES users(id),
    CONSTRAINT fk_cases_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_cases_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(id),
    CONSTRAINT fk_cases_signed_by FOREIGN KEY (signed_by) REFERENCES users(id),
    CONSTRAINT fk_cases_approved_by FOREIGN KEY (approved_by) REFERENCES users(id),
    INDEX idx_cases_date (incident_date),
    INDEX idx_cases_status (status),
    INDEX idx_cases_service (service_type(100)),
    INDEX idx_cases_comuna (comuna),
    INDEX idx_cases_barrio (barrio(100)),
    INDEX idx_cases_responsible (responsible_user_id),
    INDEX idx_cases_assigned_to (assigned_to),
    FULLTEXT INDEX ft_cases_search (case_number, address, description, incident_commander)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS case_counters (
    year    INT PRIMARY KEY,
    last_seq INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Edificaciones o vehiculos afectados (grupo repetible, hasta 3 en el form original,
-- se permite N en el sistema)
CREATE TABLE IF NOT EXISTS case_buildings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    case_id     INT NOT NULL,
    seq         INT NOT NULL DEFAULT 1,
    property_type VARCHAR(190) DEFAULT NULL,
    address     VARCHAR(255) DEFAULT NULL,
    building_use VARCHAR(190) DEFAULT NULL,
    upper_levels DECIMAL(6,2) DEFAULT NULL,
    lower_levels DECIMAL(6,2) DEFAULT NULL,
    comuna      VARCHAR(120) DEFAULT NULL,
    barrio      VARCHAR(190) DEFAULT NULL,
    stratum     VARCHAR(10) DEFAULT NULL,
    owner_name  VARCHAR(190) DEFAULT NULL,
    owner_phone VARCHAR(40) DEFAULT NULL,
    occupant_name VARCHAR(190) DEFAULT NULL,
    occupant_phone VARCHAR(40) DEFAULT NULL,
    company_name VARCHAR(190) DEFAULT NULL,
    vehicle_type VARCHAR(190) DEFAULT NULL,
    vehicle_brand VARCHAR(120) DEFAULT NULL,
    vehicle_model VARCHAR(120) DEFAULT NULL,
    vehicle_plate VARCHAR(20) DEFAULT NULL,
    vehicle_color VARCHAR(60) DEFAULT NULL,
    extra_data  JSON DEFAULT NULL,
    CONSTRAINT fk_buildings_case FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    INDEX idx_buildings_case (case_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Personas afectadas (grupo repetible, hasta 8 en el form original)
CREATE TABLE IF NOT EXISTS case_persons (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    case_id     INT NOT NULL,
    seq         INT NOT NULL DEFAULT 1,
    full_name   VARCHAR(190) DEFAULT NULL,
    age         DECIMAL(5,1) DEFAULT NULL,
    sex         VARCHAR(60) DEFAULT NULL,
    rescued     VARCHAR(60) DEFAULT NULL,
    alive       VARCHAR(60) DEFAULT NULL,
    CONSTRAINT fk_persons_case FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    INDEX idx_persons_case (case_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Animales afectados (grupo repetible, hasta 4 en el form original)
CREATE TABLE IF NOT EXISTS case_animals (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    case_id     INT NOT NULL,
    seq         INT NOT NULL DEFAULT 1,
    animal_type VARCHAR(190) DEFAULT NULL,
    size        VARCHAR(60) DEFAULT NULL,
    sex         VARCHAR(60) DEFAULT NULL,
    rescued     VARCHAR(60) DEFAULT NULL,
    alive       VARCHAR(60) DEFAULT NULL,
    CONSTRAINT fk_animals_case FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    INDEX idx_animals_case (case_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Personal (bomberos) que actuo en la emergencia
CREATE TABLE IF NOT EXISTS case_firefighters (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    case_id     INT NOT NULL,
    seq         INT NOT NULL DEFAULT 1,
    firefighter_name VARCHAR(190) DEFAULT NULL,
    role        VARCHAR(120) DEFAULT NULL,
    vehicle_value VARCHAR(190) DEFAULT NULL,  -- item_value de list_vehiculos: en que vehiculo se desplazo
    CONSTRAINT fk_firefighters_case FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    INDEX idx_firefighters_case (case_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Adjuntos: fotos de evidencia, censo, firma subida/dibujada
CREATE TABLE IF NOT EXISTS case_attachments (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    case_id       INT NOT NULL,
    kind          VARCHAR(30) NOT NULL,   -- evidencia | censo | firma
    file_path     VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) DEFAULT NULL,
    uploaded_by   INT DEFAULT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_attachments_case FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    INDEX idx_attachments_case (case_id, kind)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Trazabilidad / auditoria
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS case_history (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    case_id     INT NOT NULL,
    user_id     INT DEFAULT NULL,
    action      VARCHAR(40) NOT NULL,          -- CREADO | EDITADO | CERRADO | REABIERTO | ELIMINADO
    summary     VARCHAR(255) DEFAULT NULL,
    changes     JSON DEFAULT NULL,             -- {campo: [antes, despues], ...}
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_history_case FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    INDEX idx_history_case (case_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_logs (
    id          BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT DEFAULT NULL,
    username    VARCHAR(50) DEFAULT NULL,
    action      VARCHAR(60) NOT NULL,
    entity_type VARCHAR(60) DEFAULT NULL,
    entity_id   VARCHAR(40) DEFAULT NULL,
    ip_address  VARCHAR(45) DEFAULT NULL,
    old_data    JSON DEFAULT NULL,
    new_data    JSON DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_user (user_id),
    INDEX idx_audit_entity (entity_type, entity_id),
    INDEX idx_audit_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Configuracion del sistema
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS system_settings (
    setting_key   VARCHAR(80) PRIMARY KEY,
    setting_value TEXT DEFAULT NULL,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS backups_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    filename    VARCHAR(255) NOT NULL,
    size_bytes  BIGINT DEFAULT NULL,
    created_by  INT DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- DATOS INICIALES
-- =====================================================================
INSERT INTO roles (code, name, description) VALUES
('admin',              'Administrador',        'Acceso total al sistema, usuarios y configuracion'),
('radio_operador',     'Radio Operador',       'Crea los casos y los asigna a un bombero'),
('bombero',            'Bombero',              'Diligencia los casos que le sean asignados'),
('coordinador_turno',  'Coordinador de Turno', 'Supervisa la asignacion y el avance de los casos del turno'),
('subcomandancia',     'Subcomandancia',       'Aprueba el cierre de los casos ya firmados')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO case_statuses (code, label, color, sort_order) VALUES
('abierto',               'Abierto',                  'secondary', 1),
('asignado',              'Asignado',                 'primary',   2),
('en_atencion',           'En Atencion',              'info',      3),
('pendiente_aprobacion',  'Pendiente de Aprobacion',  'warning',   4),
('cerrado',               'Cerrado',                  'success',   5)
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO system_settings (setting_key, setting_value) VALUES
('system_name', 'Sistema de Gestion de Incidentes'),
('entity_name', 'Cuerpo de Bomberos'),
('entity_nit', ''),
('logo_path', ''),
('primary_color', '#c0392b'),
('timezone', 'America/Bogota')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

-- El usuario administrador inicial NO se crea aqui por seguridad.
-- Ejecute: php database/seed_admin.php <usuario> <email> <clave> "<Nombre Completo>"
-- (ver README.md, seccion de instalacion, paso 7)
