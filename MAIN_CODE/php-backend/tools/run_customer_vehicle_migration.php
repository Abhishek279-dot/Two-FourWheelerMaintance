<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$migrationPath = __DIR__ . '/../database/migrations/003_create_customers_vehicles.sql';
$migration = file_get_contents($migrationPath);

if ($migration === false) {
    fwrite(STDERR, "Customer and vehicle migration file could not be read.\n");
    exit(1);
}

$connection = autoline_db_connect();

if ($connection === false) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

if (!mysqli_multi_query($connection, $migration)) {
    error_log('AutoLine customer and vehicle migration failed.');
    mysqli_close($connection);
    fwrite(STDERR, "Customer and vehicle migration failed.\n");
    exit(1);
}

while (mysqli_more_results($connection) && mysqli_next_result($connection)) {
    if ($result = mysqli_store_result($connection)) {
        mysqli_free_result($result);
    }
}

if (mysqli_errno($connection) !== 0) {
    error_log('AutoLine customer and vehicle migration returned an error.');
    mysqli_close($connection);
    fwrite(STDERR, "Customer and vehicle migration failed.\n");
    exit(1);
}

mysqli_close($connection);
fwrite(STDOUT, "Customer and vehicle migration completed.\n");
