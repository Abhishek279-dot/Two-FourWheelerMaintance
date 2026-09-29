CREATE DATABASE IF NOT EXISTS autoline_dms
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE autoline_dms;

CREATE TABLE IF NOT EXISTS job_cards (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_card_no VARCHAR(30) NOT NULL,
  job_card_date DATE NULL,
  frame_number VARCHAR(60) NULL,
  registration_number VARCHAR(30) NOT NULL,
  vehicle_model VARCHAR(120) NOT NULL,
  customer_name VARCHAR(120) NOT NULL,
  phone_number VARCHAR(20) NOT NULL,
  customer_address VARCHAR(255) NULL,
  purchase_date DATE NULL,
  job_type VARCHAR(60) NOT NULL,
  kms_run INT UNSIGNED NULL,
  service_mode VARCHAR(40) NULL,
  received_date DATE NULL,
  received_time TIME NULL,
  taking_date DATE NULL,
  taking_time TIME NULL,
  delivery_date DATE NULL,
  delivery_time TIME NULL,
  service_advisor VARCHAR(120) NULL,
  mechanic_name VARCHAR(120) NULL,
  bay VARCHAR(40) NULL,
  supervisor VARCHAR(120) NULL,
  water_wash VARCHAR(120) NULL,
  vehicle_picked ENUM('Yes', 'No') NULL,
  status ENUM('Job Created', 'Allocated', 'Job Completed', 'Closed') NOT NULL DEFAULT 'Job Created',
  complaint_total DECIMAL(10, 2) NOT NULL DEFAULT 0,
  labour_total DECIMAL(10, 2) NOT NULL DEFAULT 0,
  parts_total DECIMAL(10, 2) NOT NULL DEFAULT 0,
  gst_total DECIMAL(10, 2) NOT NULL DEFAULT 0,
  grand_total DECIMAL(10, 2) NOT NULL DEFAULT 0,
  payment_mode VARCHAR(40) NULL,
  amount_received DECIMAL(10, 2) NOT NULL DEFAULT 0,
  payment_reference VARCHAR(100) NULL,
  received_by VARCHAR(120) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_job_cards_job_card_no (job_card_no),
  KEY idx_job_cards_registration_number (registration_number),
  KEY idx_job_cards_customer_name (customer_name),
  KEY idx_job_cards_status (status)
);

CREATE TABLE IF NOT EXISTS job_card_complaints (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_card_id BIGINT UNSIGNED NOT NULL,
  complaint_name VARCHAR(160) NOT NULL,
  price DECIMAL(10, 2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_complaints_job_card_id (job_card_id),
  CONSTRAINT fk_complaints_job_card
    FOREIGN KEY (job_card_id) REFERENCES job_cards (id)
    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS job_card_damages (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_card_id BIGINT UNSIGNED NOT NULL,
  damage_description VARCHAR(160) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_damages_job_card_id (job_card_id),
  CONSTRAINT fk_damages_job_card
    FOREIGN KEY (job_card_id) REFERENCES job_cards (id)
    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS job_card_labour (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_card_id BIGINT UNSIGNED NOT NULL,
  labour_code VARCHAR(40) NULL,
  description VARCHAR(255) NOT NULL,
  category VARCHAR(100) NULL,
  quantity DECIMAL(10, 2) NOT NULL DEFAULT 1,
  rate DECIMAL(10, 2) NOT NULL DEFAULT 0,
  charge DECIMAL(10, 2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_labour_job_card_id (job_card_id),
  CONSTRAINT fk_labour_job_card
    FOREIGN KEY (job_card_id) REFERENCES job_cards (id)
    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS job_card_parts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_card_id BIGINT UNSIGNED NOT NULL,
  part_number VARCHAR(80) NULL,
  description VARCHAR(255) NOT NULL,
  issue_mode VARCHAR(40) NULL,
  quantity DECIMAL(10, 2) NOT NULL DEFAULT 1,
  unit_price DECIMAL(10, 2) NOT NULL DEFAULT 0,
  mrp DECIMAL(10, 2) NOT NULL DEFAULT 0,
  total DECIMAL(10, 2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_parts_job_card_id (job_card_id),
  CONSTRAINT fk_parts_job_card
    FOREIGN KEY (job_card_id) REFERENCES job_cards (id)
    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS job_card_checks (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_card_id BIGINT UNSIGNED NOT NULL,
  check_name VARCHAR(160) NOT NULL,
  is_checked BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_checks_job_card_id (job_card_id),
  CONSTRAINT fk_checks_job_card
    FOREIGN KEY (job_card_id) REFERENCES job_cards (id)
    ON DELETE CASCADE
);
