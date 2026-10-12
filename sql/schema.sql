-- Demo Reservation schema only (tables).
-- Do not create MySQL login accounts here. Create the database user by hand
-- using the steps in deploy/README.md, then grant DML on this database.

CREATE DATABASE IF NOT EXISTS demo_reservation
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE demo_reservation;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(64) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin', 'standard', 'view') NOT NULL DEFAULT 'standard',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS demo_vms (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(64) NOT NULL,
  description TEXT NULL,
  ip_hostname VARCHAR(255) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_demo_vms_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reservations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  vm_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  start_utc DATETIME NOT NULL,
  end_utc DATETIME NOT NULL,
  duration_label VARCHAR(64) NOT NULL,
  notes VARCHAR(500) NULL,
  status ENUM('active', 'expired', 'released') NOT NULL DEFAULT 'active',
  released_by_user_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ended_at DATETIME NULL,
  CONSTRAINT fk_res_vm FOREIGN KEY (vm_id) REFERENCES demo_vms (id),
  CONSTRAINT fk_res_user FOREIGN KEY (user_id) REFERENCES users (id),
  CONSTRAINT fk_res_released_by FOREIGN KEY (released_by_user_id) REFERENCES users (id),
  KEY idx_res_vm_status_time (vm_id, status, start_utc, end_utc),
  KEY idx_res_user (user_id),
  KEY idx_res_status_end (status, end_utc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS watch_global (
  user_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id),
  CONSTRAINT fk_watch_global_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS watch_vm (
  user_id INT UNSIGNED NOT NULL,
  vm_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, vm_id),
  CONSTRAINT fk_watch_vm_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_watch_vm_vm FOREIGN KEY (vm_id) REFERENCES demo_vms (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  username VARCHAR(64) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_login_ip_time (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
