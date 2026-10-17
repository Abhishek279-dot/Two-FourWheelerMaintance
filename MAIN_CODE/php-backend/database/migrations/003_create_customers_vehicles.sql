CREATE TABLE IF NOT EXISTS customers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    legacy_key VARCHAR(255) NULL,
    name VARCHAR(160) NOT NULL,
    phone VARCHAR(40) NULL,
    address VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_customers_legacy_key (legacy_key),
    KEY idx_customers_name (name),
    KEY idx_customers_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vehicles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    registration_number VARCHAR(32) NOT NULL,
    vehicle_model VARCHAR(160) NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    purchase_date DATE NULL,
    dealer_code VARCHAR(80) NULL,
    dealer_name VARCHAR(160) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_vehicles_registration_number (registration_number),
    KEY idx_vehicles_model (vehicle_model),
    KEY idx_vehicles_customer_id (customer_id),
    CONSTRAINT fk_vehicles_customer
        FOREIGN KEY (customer_id) REFERENCES customers (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
