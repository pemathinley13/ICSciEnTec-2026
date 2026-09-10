-- ICSciEnTec Submission Portal — simplified schema.
-- All peer review, decisions, camera-ready, appeals and multi-edition
-- management are handled manually outside this system now — this app's job
-- is just: authors register and submit (title + abstract file + manuscript
-- file), and the Organizing Committee can see every submission and download
-- its files. Single import file. MySQL 5.7+/8.0 or MariaDB 10.3+.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────────────────────────
-- Users & roles — just two: author (self-registers) and organizing_committee
-- (granted by another admin, sees every submission).
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email          VARCHAR(190) NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  full_name      VARCHAR(150) NOT NULL,
  affiliation    VARCHAR(200) NULL,
  phone          VARCHAR(40) NULL,
  is_active      TINYINT(1) NOT NULL DEFAULT 1,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS roles (
  id    TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name  VARCHAR(40) NOT NULL UNIQUE
  -- expected values: author, organizing_committee
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_roles (
  id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id  INT UNSIGNED NOT NULL,
  role_id  TINYINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_role (user_id, role_id),
  CONSTRAINT fk_ur_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ur_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────────────
-- Tracks — a simple category label on a submission, nothing more.
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS tracks (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(150) NOT NULL,
  description  TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────────────
-- Submissions (core entity) & authors. Abstract is a FILE (submission_files
-- file_role = 'abstract'), not a text column — reviewing/deciding happens
-- manually outside this system, so `status` is just a plain, admin-editable
-- label, not a workflow engine.
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS submissions (
  id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  track_id                INT UNSIGNED NOT NULL,
  title                   VARCHAR(300) NOT NULL,
  keywords                VARCHAR(300) NULL,
  status                  VARCHAR(40) NOT NULL DEFAULT 'submitted',
  corresponding_author_id INT UNSIGNED NOT NULL,
  created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_submissions_status (status),
  KEY idx_submissions_track (track_id),
  CONSTRAINT fk_sub_track FOREIGN KEY (track_id) REFERENCES tracks(id) ON DELETE RESTRICT,
  CONSTRAINT fk_sub_corr_author FOREIGN KEY (corresponding_author_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS submission_authors (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  submission_id   INT UNSIGNED NOT NULL,
  user_id         INT UNSIGNED NULL,
  name            VARCHAR(150) NOT NULL,
  email           VARCHAR(190) NOT NULL,
  affiliation     VARCHAR(200) NULL,
  author_order    TINYINT UNSIGNED NOT NULL DEFAULT 1,
  is_corresponding TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT fk_sa_submission FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE,
  CONSTRAINT fk_sa_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS submission_files (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  submission_id     INT UNSIGNED NOT NULL,
  file_role         VARCHAR(20) NOT NULL COMMENT 'abstract, manuscript',
  stored_filename   VARCHAR(255) NOT NULL COMMENT 'random/hashed name on disk, never the user-supplied name',
  original_filename VARCHAR(255) NOT NULL,
  mime_type         VARCHAR(120) NOT NULL,
  size_bytes        INT UNSIGNED NOT NULL,
  uploaded_by       INT UNSIGNED NOT NULL,
  uploaded_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_files_submission (submission_id, file_role),
  CONSTRAINT fk_sf_submission FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE,
  CONSTRAINT fk_sf_uploader FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────────────
-- Audit trail & email log — kept, since they're simple and still useful for
-- a basic record of what happened and what was sent, even without a
-- workflow engine driving them.
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS audit_log (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  submission_id INT UNSIGNED NULL,
  actor_user_id INT UNSIGNED NULL,
  action        VARCHAR(60) NOT NULL,
  from_status   VARCHAR(40) NULL,
  to_status     VARCHAR(40) NULL,
  details_json  TEXT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_audit_submission (submission_id, created_at),
  CONSTRAINT fk_audit_submission FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE SET NULL,
  CONSTRAINT fk_audit_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS email_log (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  to_email        VARCHAR(190) NOT NULL,
  template_key    VARCHAR(60) NOT NULL,
  submission_id   INT UNSIGNED NULL,
  sent_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status          VARCHAR(20) NOT NULL DEFAULT 'sent' COMMENT 'sent, failed',
  error_message   TEXT NULL,
  KEY idx_email_submission (submission_id),
  CONSTRAINT fk_email_submission FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_resets (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  token_hash  VARCHAR(255) NOT NULL,
  expires_at  DATETIME NOT NULL,
  used_at     DATETIME NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
