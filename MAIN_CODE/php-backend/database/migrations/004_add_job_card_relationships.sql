ALTER TABLE job_cards
    ADD COLUMN vehicle_id BIGINT UNSIGNED NULL AFTER registration_number,
    ADD COLUMN customer_id BIGINT UNSIGNED NULL AFTER customer_name,
    ADD COLUMN cancelled_from_status VARCHAR(40) NULL AFTER status,
    MODIFY COLUMN status ENUM('Job Created', 'Allocated', 'Job Completed', 'Closed', 'Cancelled') NOT NULL DEFAULT 'Job Created',
    ADD KEY idx_job_cards_vehicle_id (vehicle_id),
    ADD KEY idx_job_cards_customer_id (customer_id),
    ADD CONSTRAINT fk_job_cards_vehicle
        FOREIGN KEY (vehicle_id) REFERENCES vehicles (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    ADD CONSTRAINT fk_job_cards_customer
        FOREIGN KEY (customer_id) REFERENCES customers (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL;
