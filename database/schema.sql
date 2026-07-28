CREATE DATABASE IF NOT EXISTS foundation_system
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE foundation_system;

CREATE TABLE roles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(120) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  module VARCHAR(80) NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  INDEX idx_permissions_module (module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_permissions (
  role_id BIGINT UNSIGNED NOT NULL,
  permission_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  CONSTRAINT fk_role_permissions_role
    FOREIGN KEY (role_id) REFERENCES roles(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_role_permissions_permission
    FOREIGN KEY (permission_id) REFERENCES permissions(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role_id BIGINT UNSIGNED NULL,
  status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  password_changed_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_users_role
    FOREIGN KEY (role_id) REFERENCES roles(id)
    ON DELETE SET NULL,
  INDEX idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_reset_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_password_reset_tokens_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE,
  INDEX idx_password_reset_token_hash (token_hash),
  INDEX idx_password_reset_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  INDEX idx_login_attempts_email_created (email, created_at),
  INDEX idx_login_attempts_success (success)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(80) NOT NULL,
  module VARCHAR(80) NOT NULL,
  target_type VARCHAR(80) NULL,
  target_id BIGINT UNSIGNED NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  detail_json JSON NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_audit_logs_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_audit_logs_module_created (module, created_at),
  INDEX idx_audit_logs_user_created (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE system_settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(120) NOT NULL UNIQUE,
  setting_value TEXT NULL,
  setting_type VARCHAR(40) NOT NULL DEFAULT 'string',
  updated_by BIGINT UNSIGNED NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_system_settings_updated_by
    FOREIGN KEY (updated_by) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE system_update_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  action VARCHAR(40) NOT NULL,
  status VARCHAR(40) NOT NULL,
  version_from VARCHAR(40) NULL,
  version_to VARCHAR(40) NULL,
  package_path VARCHAR(255) NULL,
  package_sha256 CHAR(64) NULL,
  message TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_system_update_logs_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_system_update_logs_created_at (created_at),
  INDEX idx_system_update_logs_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE schema_migrations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  migration VARCHAR(190) NOT NULL UNIQUE,
  applied_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE donors (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  donor_type ENUM('person', 'organization') NOT NULL DEFAULT 'person',
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(40) NULL,
  email VARCHAR(190) NULL,
  address VARCHAR(255) NULL,
  receipt_title VARCHAR(120) NULL,
  tax_id VARCHAR(30) NULL,
  notes TEXT NULL,
  status ENUM('active', 'archived') NOT NULL DEFAULT 'active',
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_donors_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_donors_name (name),
  INDEX idx_donors_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE donations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  donor_id BIGINT UNSIGNED NOT NULL,
  donated_at DATE NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  payment_method VARCHAR(40) NOT NULL,
  receipt_no VARCHAR(80) NULL,
  receipt_status ENUM('not_required', 'pending', 'issued', 'voided') NOT NULL DEFAULT 'pending',
  project_name VARCHAR(120) NULL,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_donations_donor
    FOREIGN KEY (donor_id) REFERENCES donors(id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_donations_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_donations_donated_at (donated_at),
  INDEX idx_donations_receipt_status (receipt_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activities (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(160) NOT NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NULL,
  location VARCHAR(160) NULL,
  status ENUM('draft', 'published', 'closed', 'cancelled') NOT NULL DEFAULT 'draft',
  description TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_activities_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_activities_starts_at (starts_at),
  INDEX idx_activities_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE volunteers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(40) NULL,
  email VARCHAR(190) NULL,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_volunteers_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_volunteers_name (name),
  INDEX idx_volunteers_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE volunteer_service_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  volunteer_id BIGINT UNSIGNED NOT NULL,
  activity_id BIGINT UNSIGNED NULL,
  served_on DATE NOT NULL,
  hours DECIMAL(5,2) NOT NULL,
  description VARCHAR(255) NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_service_logs_volunteer
    FOREIGN KEY (volunteer_id) REFERENCES volunteers(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_service_logs_activity
    FOREIGN KEY (activity_id) REFERENCES activities(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_service_logs_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_service_logs_served_on (served_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE documents (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(160) NOT NULL,
  category VARCHAR(80) NULL,
  original_name VARCHAR(255) NOT NULL,
  storage_path VARCHAR(255) NOT NULL,
  mime_type VARCHAR(120) NULL,
  file_size BIGINT UNSIGNED NULL,
  visibility ENUM('private', 'role') NOT NULL DEFAULT 'private',
  uploaded_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_documents_uploaded_by
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_documents_category (category),
  INDEX idx_documents_visibility (visibility)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE annual_budgets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fiscal_year SMALLINT UNSIGNED NOT NULL,
  title VARCHAR(160) NOT NULL,
  status ENUM('draft', 'submitted', 'approved') NOT NULL DEFAULT 'draft',
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  approved_by BIGINT UNSIGNED NULL,
  approved_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_annual_budgets_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_annual_budgets_approved_by
    FOREIGN KEY (approved_by) REFERENCES users(id)
    ON DELETE SET NULL,
  UNIQUE KEY uk_annual_budgets_fiscal_year (fiscal_year),
  INDEX idx_annual_budgets_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE annual_budget_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  annual_budget_id BIGINT UNSIGNED NOT NULL,
  item_type ENUM('income', 'expense') NOT NULL,
  category VARCHAR(120) NOT NULL,
  item_name VARCHAR(160) NOT NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  notes TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_annual_budget_items_budget
    FOREIGN KEY (annual_budget_id) REFERENCES annual_budgets(id)
    ON DELETE CASCADE,
  INDEX idx_annual_budget_items_type (item_type),
  INDEX idx_annual_budget_items_budget_sort (annual_budget_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
