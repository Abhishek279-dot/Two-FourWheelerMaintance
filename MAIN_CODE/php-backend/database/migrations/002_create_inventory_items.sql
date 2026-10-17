CREATE TABLE IF NOT EXISTS inventory_items (
    id VARCHAR(40) NOT NULL,
    image VARCHAR(255) NULL,
    part_name VARCHAR(160) NOT NULL,
    part_code VARCHAR(80) NOT NULL,
    category VARCHAR(100) NOT NULL,
    compatible_with VARCHAR(160) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 0,
    min_stock INT UNSIGNED NOT NULL DEFAULT 0,
    price DECIMAL(12, 2) NOT NULL DEFAULT 0,
    status ENUM('In Stock', 'Low Stock', 'Out of Stock') NOT NULL DEFAULT 'In Stock',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_inventory_part_code (part_code),
    KEY idx_inventory_category (category),
    KEY idx_inventory_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
