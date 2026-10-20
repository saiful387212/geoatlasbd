CREATE DATABASE IF NOT EXISTS geoatlas_bd
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE geoatlas_bd;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(32) NOT NULL DEFAULT 'admin',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    failed_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    last_login_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description VARCHAR(500) NULL,
    color CHAR(7) NULL,
    icon VARCHAR(50) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug),
    KEY idx_categories_sort (sort_order, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS projects (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    author_id BIGINT UNSIGNED NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    summary VARCHAR(500) NOT NULL,
    description MEDIUMTEXT NULL,
    objectives TEXT NULL,
    research_questions TEXT NULL,
    data_sources TEXT NULL,
    data_year VARCHAR(100) NULL,
    methodology MEDIUMTEXT NULL,
    tools_used TEXT NULL,
    findings MEDIUMTEXT NULL,
    limitations TEXT NULL,
    study_area VARCHAR(255) NULL,
    cover_image VARCHAR(500) NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    zoom TINYINT UNSIGNED NULL,
    geojson LONGTEXT NULL,
    tags VARCHAR(1000) NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    featured TINYINT(1) NOT NULL DEFAULT 0,
    views BIGINT UNSIGNED NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_projects_slug (slug),
    KEY idx_projects_status_published (status, published_at),
    KEY idx_projects_category_status (category_id, status),
    KEY idx_projects_featured (featured, status, published_at),
    FULLTEXT KEY ft_projects_search (title, summary, description, tags),
    CONSTRAINT fk_projects_author FOREIGN KEY (author_id)
        REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_projects_category FOREIGN KEY (category_id)
        REFERENCES categories (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_images (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    alt_text VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_project_images_project_order (project_id, sort_order),
    CONSTRAINT fk_project_images_project FOREIGN KEY (project_id)
        REFERENCES projects (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_files (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100) NOT NULL DEFAULT 'application/pdf',
    size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_project_files_project (project_id),
    CONSTRAINT fk_project_files_project FOREIGN KEY (project_id)
        REFERENCES projects (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL DEFAULT '',
    email VARCHAR(255) NOT NULL DEFAULT '',
    subject VARCHAR(150) NOT NULL DEFAULT '',
    message TEXT NOT NULL,
    ip_hash CHAR(64) NULL,
    user_agent VARCHAR(255) NULL,
    form_started_at DATETIME NULL,
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_spam TINYINT(1) NOT NULL DEFAULT 0,
    spam_reason VARCHAR(50) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_contact_messages_inbox (is_spam, is_read, submitted_at),
    KEY idx_contact_messages_ip_submitted (ip_hash, submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    target_type VARCHAR(80) NULL,
    target_id BIGINT UNSIGNED NULL,
    meta JSON NULL,
    ip_hash CHAR(64) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_action_created (action, created_at),
    KEY idx_audit_user_created (user_id, created_at),
    CONSTRAINT fk_audit_log_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
