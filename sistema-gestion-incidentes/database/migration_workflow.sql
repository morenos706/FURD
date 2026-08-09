-- =====================================================================
-- Migracion: flujo por roles, aprobacion, firma digital, evidencias,
-- vinculo bombero-vehiculo.
--
-- Para instalaciones NUEVAS: schema.sql ya incluye todo esto, no hace
-- falta correr este archivo.
--
-- Para instalaciones EXISTENTES (que ya corrieron schema.sql antes de
-- este cambio): correr este archivo UNA VEZ despues de schema.sql:
--   mysql -u <user> -p <db> < database/migration_workflow.sql
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Roles del flujo operativo (reemplazan 'usuario' y 'consulta')
-- ---------------------------------------------------------------------
INSERT INTO roles (code, name, description) VALUES
('radio_operador',    'Radio Operador',       'Crea los casos y los asigna a un bombero'),
('bombero',           'Bombero',              'Diligencia los casos que le sean asignados'),
('coordinador_turno', 'Coordinador de Turno', 'Supervisa la asignacion y el avance de los casos del turno'),
('subcomandancia',    'Subcomandancia',       'Aprueba el cierre de los casos ya firmados')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Migra usuarios que tenian los roles genericos anteriores
UPDATE users u
  JOIN roles r_old ON u.role_id = r_old.id AND r_old.code = 'usuario'
  JOIN roles r_new ON r_new.code = 'bombero'
  SET u.role_id = r_new.id;

UPDATE users u
  JOIN roles r_old ON u.role_id = r_old.id AND r_old.code = 'consulta'
  JOIN roles r_new ON r_new.code = 'coordinador_turno'
  SET u.role_id = r_new.id;

DELETE FROM roles WHERE code IN ('usuario', 'consulta');

-- ---------------------------------------------------------------------
-- Estados del flujo (creado -> asignado -> en_atencion -> firmado/
-- pendiente_aprobacion -> cerrado)
-- ---------------------------------------------------------------------
INSERT INTO case_statuses (code, label, color, sort_order) VALUES
('asignado',              'Asignado',                'primary', 2),
('pendiente_aprobacion',  'Pendiente de Aprobacion',  'warning', 4)
ON DUPLICATE KEY UPDATE label = VALUES(label);

UPDATE case_statuses SET sort_order = 1 WHERE code = 'abierto';
UPDATE case_statuses SET sort_order = 3 WHERE code = 'en_atencion';
UPDATE case_statuses SET sort_order = 5 WHERE code = 'cerrado';

-- ---------------------------------------------------------------------
-- Columnas de flujo/asignacion/firma/aprobacion en cases
-- ---------------------------------------------------------------------
ALTER TABLE cases ADD COLUMN IF NOT EXISTS assigned_to     INT DEFAULT NULL AFTER responsible_user_id;
ALTER TABLE cases ADD COLUMN IF NOT EXISTS assigned_by     INT DEFAULT NULL AFTER assigned_to;
ALTER TABLE cases ADD COLUMN IF NOT EXISTS assigned_at     DATETIME DEFAULT NULL AFTER assigned_by;
ALTER TABLE cases ADD COLUMN IF NOT EXISTS signed_by       INT DEFAULT NULL AFTER assigned_at;
ALTER TABLE cases ADD COLUMN IF NOT EXISTS signed_at       DATETIME DEFAULT NULL AFTER signed_by;
ALTER TABLE cases ADD COLUMN IF NOT EXISTS sign_method     VARCHAR(20) DEFAULT NULL AFTER signed_at;
ALTER TABLE cases ADD COLUMN IF NOT EXISTS signature_path  VARCHAR(255) DEFAULT NULL AFTER sign_method;
ALTER TABLE cases ADD COLUMN IF NOT EXISTS approved_by     INT DEFAULT NULL AFTER signature_path;
ALTER TABLE cases ADD COLUMN IF NOT EXISTS approved_at     DATETIME DEFAULT NULL AFTER approved_by;

SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_cases_assigned_to');
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE cases ADD CONSTRAINT fk_cases_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(id)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_cases_signed_by');
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE cases ADD CONSTRAINT fk_cases_signed_by FOREIGN KEY (signed_by) REFERENCES users(id)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_cases_approved_by');
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE cases ADD CONSTRAINT fk_cases_approved_by FOREIGN KEY (approved_by) REFERENCES users(id)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE cases ADD INDEX IF NOT EXISTS idx_cases_assigned_to (assigned_to);

-- ---------------------------------------------------------------------
-- Adjuntos (fotos de evidencia, censo, firma subida/dibujada)
-- ---------------------------------------------------------------------
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
-- Vincula cada bombero de la emergencia con el vehiculo (de la flota
-- propia, catalogo list_vehiculos) en el que se desplazo.
-- ---------------------------------------------------------------------
ALTER TABLE case_firefighters ADD COLUMN IF NOT EXISTS vehicle_value VARCHAR(190) DEFAULT NULL AFTER role;

-- ---------------------------------------------------------------------
-- PIN de seguridad (segunda clave para acciones sensibles: firmar,
-- aprobar, editar un caso ya firmado/cerrado) y firma digital guardada
-- en el perfil de cada usuario.
-- ---------------------------------------------------------------------
ALTER TABLE users ADD COLUMN IF NOT EXISTS security_pin_hash VARCHAR(255) DEFAULT NULL AFTER password_hash;
ALTER TABLE users ADD COLUMN IF NOT EXISTS signature_path VARCHAR(255) DEFAULT NULL AFTER security_pin_hash;

-- ---------------------------------------------------------------------
-- SCI: objetivos, estrategias y tacticas repetibles (uno o varios por
-- caso, en vez de un solo texto libre).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS case_sci_objectives (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    case_id     INT NOT NULL,
    seq         INT NOT NULL DEFAULT 1,
    objective   TEXT DEFAULT NULL,
    strategy_tactic TEXT DEFAULT NULL,
    CONSTRAINT fk_sci_objectives_case FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    INDEX idx_sci_objectives_case (case_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Logo institucional y fondo de pantalla del login
-- ---------------------------------------------------------------------
INSERT INTO system_settings (setting_key, setting_value) VALUES
('login_bg_path', '')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

-- Logo y fondo por defecto (Bomberos Voluntarios Itagui), solo si el
-- admin todavia no subio uno propio desde Configuracion.
UPDATE system_settings SET setting_value = '/branding/logo.jpg'
    WHERE setting_key = 'logo_path' AND (setting_value IS NULL OR setting_value = '');
UPDATE system_settings SET setting_value = '/branding/login_bg.jpg'
    WHERE setting_key = 'login_bg_path' AND (setting_value IS NULL OR setting_value = '');
