<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';

function job_card_migration_column_exists($connection, $column)
{
    $statement = mysqli_prepare(
        $connection,
        "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'job_cards' AND column_name = ?"
    );
    mysqli_stmt_bind_param($statement, 's', $column);
    mysqli_stmt_execute($statement);
    mysqli_stmt_bind_result($statement, $count);
    mysqli_stmt_fetch($statement);
    mysqli_stmt_close($statement);
    return (int) $count > 0;
}

function job_card_migration_index_exists($connection, $index)
{
    $statement = mysqli_prepare($connection, 'SHOW INDEX FROM job_cards WHERE Key_name = ?');
    mysqli_stmt_bind_param($statement, 's', $index);
    mysqli_stmt_execute($statement);
    mysqli_stmt_store_result($statement);
    $exists = mysqli_stmt_num_rows($statement) > 0;
    mysqli_stmt_close($statement);
    return $exists;
}

function job_card_migration_constraint_exists($connection, $constraint)
{
    $statement = mysqli_prepare(
        $connection,
        "SELECT COUNT(*) FROM information_schema.table_constraints WHERE constraint_schema = DATABASE() AND table_name = 'job_cards' AND constraint_name = ?"
    );
    mysqli_stmt_bind_param($statement, 's', $constraint);
    mysqli_stmt_execute($statement);
    mysqli_stmt_bind_result($statement, $count);
    mysqli_stmt_fetch($statement);
    mysqli_stmt_close($statement);
    return (int) $count > 0;
}

function job_card_migration_run($connection, $sql)
{
    if (!mysqli_query($connection, $sql)) {
        $error = mysqli_error($connection);
        error_log('AutoLine job-card migration failed: ' . $error);
        fwrite(STDERR, "Job-card migration failed: {$error}\n");
        mysqli_close($connection);
        exit(1);
    }
}

$connection = autoline_db_connect();

if ($connection === false) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$columns = [
    'vehicle_id' => 'ALTER TABLE job_cards ADD COLUMN vehicle_id BIGINT UNSIGNED NULL AFTER registration_number',
    'customer_id' => 'ALTER TABLE job_cards ADD COLUMN customer_id BIGINT UNSIGNED NULL AFTER customer_name',
    'cancelled_from_status' => 'ALTER TABLE job_cards ADD COLUMN cancelled_from_status VARCHAR(40) NULL AFTER status',
];

foreach ($columns as $column => $sql) {
    if (!job_card_migration_column_exists($connection, $column)) {
        job_card_migration_run($connection, $sql);
    }
}

$statusResult = mysqli_query($connection, "SHOW COLUMNS FROM job_cards LIKE 'status'");
$statusColumn = $statusResult ? mysqli_fetch_assoc($statusResult) : null;
if ($statusResult) {
    mysqli_free_result($statusResult);
}
if (!$statusColumn || strpos((string) $statusColumn['Type'], "'Cancelled'") === false) {
    job_card_migration_run(
        $connection,
        "ALTER TABLE job_cards MODIFY COLUMN status ENUM('Job Created', 'Allocated', 'Job Completed', 'Closed', 'Cancelled') NOT NULL DEFAULT 'Job Created'"
    );
}

$indexes = [
    'idx_job_cards_vehicle_id' => 'ALTER TABLE job_cards ADD KEY idx_job_cards_vehicle_id (vehicle_id)',
    'idx_job_cards_customer_id' => 'ALTER TABLE job_cards ADD KEY idx_job_cards_customer_id (customer_id)',
];
foreach ($indexes as $index => $sql) {
    if (!job_card_migration_index_exists($connection, $index)) {
        job_card_migration_run($connection, $sql);
    }
}

$constraints = [
    'fk_job_cards_vehicle' => 'ALTER TABLE job_cards ADD CONSTRAINT fk_job_cards_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles (id) ON UPDATE CASCADE ON DELETE SET NULL',
    'fk_job_cards_customer' => 'ALTER TABLE job_cards ADD CONSTRAINT fk_job_cards_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON UPDATE CASCADE ON DELETE SET NULL',
];
foreach ($constraints as $constraint => $sql) {
    if (!job_card_migration_constraint_exists($connection, $constraint)) {
        job_card_migration_run($connection, $sql);
    }
}

job_card_migration_run(
    $connection,
    'UPDATE job_cards jc INNER JOIN vehicles v ON v.registration_number COLLATE utf8mb4_unicode_ci = jc.registration_number COLLATE utf8mb4_unicode_ci
     SET jc.vehicle_id = v.id, jc.customer_id = v.customer_id
     WHERE jc.vehicle_id IS NULL OR jc.customer_id IS NULL'
);

mysqli_close($connection);
fwrite(STDOUT, "Job-card migration completed.\n");
