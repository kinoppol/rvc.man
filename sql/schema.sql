-- ============================================================
-- ระบบคู่มือการปฏิบัติงาน วิทยาลัยอาชีวศึกษาร้อยเอ็ด
-- MariaDB 10.4+ / utf8mb4
-- Destructive: install.php drops and recreates every table below.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS favorites;
DROP TABLE IF EXISTS attachments;
DROP TABLE IF EXISTS procedure_steps;
DROP TABLE IF EXISTS procedure_flows;
DROP TABLE IF EXISTS procedures;
DROP TABLE IF EXISTS sections;
DROP TABLE IF EXISTS divisions;
DROP TABLE IF EXISTS info_pages;
DROP TABLE IF EXISTS search_logs;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS users;

-- ---------------------------------------------------------- ฝ่าย
CREATE TABLE divisions (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(150) NOT NULL,
    short_name  VARCHAR(60)  NOT NULL DEFAULT '',
    description VARCHAR(255) NOT NULL DEFAULT '',
    icon        VARCHAR(30)  NOT NULL DEFAULT 'folder',
    sort_order  SMALLINT     NOT NULL DEFAULT 0,
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_divisions_name (name),
    KEY idx_divisions_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------- งาน
CREATE TABLE sections (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    division_id INT UNSIGNED NOT NULL,
    name        VARCHAR(180) NOT NULL,
    description VARCHAR(255) NOT NULL DEFAULT '',
    sort_order  SMALLINT     NOT NULL DEFAULT 0,
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sections (division_id, name),
    CONSTRAINT fk_sections_division FOREIGN KEY (division_id)
        REFERENCES divisions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------- เรื่อง/ขั้นตอนการปฏิบัติงาน
CREATE TABLE procedures (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    division_id INT UNSIGNED NOT NULL,
    section_id  INT UNSIGNED NOT NULL,
    code        VARCHAR(30)  NOT NULL DEFAULT '',
    title       VARCHAR(300) NOT NULL,
    purpose     TEXT         NULL,
    content     MEDIUMTEXT   NULL,
    -- Thai has no word breaks, so MariaDB FULLTEXT cannot tokenise it.
    -- Search is LIKE-based over this pre-normalised column instead.
    search_text MEDIUMTEXT   NULL,
    page_start  SMALLINT     NOT NULL DEFAULT 0,
    page_end    SMALLINT     NOT NULL DEFAULT 0,
    status      ENUM('เผยแพร่','ฉบับร่าง') NOT NULL DEFAULT 'เผยแพร่',
    views       INT UNSIGNED NOT NULL DEFAULT 0,
    sort_order  SMALLINT     NOT NULL DEFAULT 0,
    updated_by  INT UNSIGNED NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_procedures_section (section_id, sort_order),
    KEY idx_procedures_division (division_id),
    KEY idx_procedures_status (status),
    KEY idx_procedures_views (views),
    CONSTRAINT fk_procedures_division FOREIGN KEY (division_id)
        REFERENCES divisions (id) ON DELETE CASCADE,
    CONSTRAINT fk_procedures_section FOREIGN KEY (section_id)
        REFERENCES sections (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------- ขั้นตอน
CREATE TABLE procedure_steps (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    procedure_id INT UNSIGNED NOT NULL,
    step_no      SMALLINT     NOT NULL DEFAULT 0,
    sub_no       SMALLINT     NULL,
    detail       TEXT         NOT NULL,
    sort_order   SMALLINT     NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_steps_procedure (procedure_id, sort_order),
    CONSTRAINT fk_steps_procedure FOREIGN KEY (procedure_id)
        REFERENCES procedures (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------- ผังงาน: ผู้รับผิดชอบ / หลักฐานอ้างอิง
CREATE TABLE procedure_flows (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    procedure_id INT UNSIGNED NOT NULL,
    stage        VARCHAR(400) NOT NULL DEFAULT '',
    responsible  VARCHAR(255) NOT NULL DEFAULT '',
    evidence     VARCHAR(500) NOT NULL DEFAULT '',
    sort_order   SMALLINT     NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_flows_procedure (procedure_id, sort_order),
    CONSTRAINT fk_flows_procedure FOREIGN KEY (procedure_id)
        REFERENCES procedures (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------- ไฟล์แนบ (แบบฟอร์ม ฯลฯ)
CREATE TABLE attachments (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    procedure_id  INT UNSIGNED NOT NULL,
    stored_name   VARCHAR(120) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime          VARCHAR(120) NOT NULL DEFAULT '',
    size_bytes    INT UNSIGNED NOT NULL DEFAULT 0,
    uploaded_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_attachments_procedure (procedure_id),
    CONSTRAINT fk_attachments_procedure FOREIGN KEY (procedure_id)
        REFERENCES procedures (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------- หน้าข้อมูลทั่วไป (บทนำของคู่มือ)
CREATE TABLE info_pages (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title      VARCHAR(255) NOT NULL,
    body       MEDIUMTEXT   NOT NULL,
    sort_order SMALLINT     NOT NULL DEFAULT 0,
    is_active  TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_info_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------- ผู้ใช้งาน
CREATE TABLE users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username      VARCHAR(80)  NOT NULL,
    name          VARCHAR(150) NOT NULL,
    email         VARCHAR(150) NOT NULL DEFAULT '',
    position      VARCHAR(150) NOT NULL DEFAULT '',
    role          ENUM('ผู้ดูแลระบบ','เจ้าหน้าที่') NOT NULL DEFAULT 'เจ้าหน้าที่',
    password_hash VARCHAR(255) NOT NULL,
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    last_login_at DATETIME     NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------- รายการที่บันทึกไว้
CREATE TABLE favorites (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id      INT UNSIGNED NOT NULL,
    procedure_id INT UNSIGNED NOT NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_favorites (user_id, procedure_id),
    CONSTRAINT fk_favorites_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_favorites_procedure FOREIGN KEY (procedure_id)
        REFERENCES procedures (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------- คำค้นยอดนิยม (ใช้แนะนำหน้าค้นหา)
CREATE TABLE search_logs (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    term       VARCHAR(150) NOT NULL,
    hits       INT UNSIGNED NOT NULL DEFAULT 0,
    searched_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_search_term (term)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------- ค่าตั้งระบบ
CREATE TABLE settings (
    name  VARCHAR(60)  NOT NULL,
    value TEXT         NULL,
    PRIMARY KEY (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
