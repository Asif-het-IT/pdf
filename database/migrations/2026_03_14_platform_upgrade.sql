CREATE TABLE IF NOT EXISTS jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_uuid CHAR(24) NOT NULL UNIQUE,
    user_id INT UNSIGNED NOT NULL,
    tool_key VARCHAR(80) NOT NULL,
    status ENUM('queued', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'queued',
    stage ENUM('uploaded', 'queued', 'validating', 'processing', 'finalizing', 'completed', 'failed') NOT NULL DEFAULT 'uploaded',
    progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
    input_meta_json LONGTEXT NULL,
    output_meta_json LONGTEXT NULL,
    error_message TEXT NULL,
    attempt_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
    max_attempts TINYINT UNSIGNED NOT NULL DEFAULT 2,
    queued_at DATETIME NULL,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    expires_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_jobs_user_created (user_id, created_at),
    INDEX idx_jobs_status (status),
    INDEX idx_jobs_tool (tool_key),
    CONSTRAINT fk_jobs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS job_queue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_uuid CHAR(24) NOT NULL UNIQUE,
    user_id INT UNSIGNED NOT NULL,
    tool_key VARCHAR(80) NOT NULL,
    status ENUM('queued', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'queued',
    payload_json LONGTEXT NULL,
    error_message TEXT NULL,
    queued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    INDEX idx_job_queue_status (status),
    INDEX idx_job_queue_user (user_id),
    CONSTRAINT fk_job_queue_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS job_files (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    job_id BIGINT UNSIGNED NOT NULL,
    file_role ENUM('input', 'output', 'artifact') NOT NULL,
    relative_path VARCHAR(500) NOT NULL,
    original_name VARCHAR(255) NULL,
    mime_type VARCHAR(120) NULL,
    size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
    download_count INT UNSIGNED NOT NULL DEFAULT 0,
    last_download_at DATETIME NULL,
    expires_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_job_files_user (user_id),
    INDEX idx_job_files_job (job_id),
    INDEX idx_job_files_expiry (expires_at),
    CONSTRAINT fk_job_files_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_job_files_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    entity_type VARCHAR(80) NULL,
    entity_id VARCHAR(120) NULL,
    meta_json LONGTEXT NULL,
    ip_address VARCHAR(64) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_user (user_id),
    INDEX idx_audit_action (action),
    INDEX idx_audit_created (created_at),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS app_settings (
    setting_key VARCHAR(120) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO app_settings (setting_key, setting_value)
VALUES ('retention_days', '30')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

INSERT INTO app_settings (setting_key, setting_value)
VALUES ('queue_mode', 'db_fallback')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
