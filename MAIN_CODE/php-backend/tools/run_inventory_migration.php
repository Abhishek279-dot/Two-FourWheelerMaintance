<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$migrationPath = __DIR__ . '/../database/migrations/002_create_inventory_items.sql';
$migration = file_get_contents($migrationPath);

if ($migration === false) {
    fwrite(STDERR, "Inventory migration file could not be read.\n");
    exit(1);
}

$connection = autoline_db_connect();

if ($connection === false) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

if (!mysqli_query($connection, $migration)) {
    error_log('AutoLine inventory migration failed.');
    mysqli_close($connection);
    fwrite(STDERR, "Inventory migration failed.\n");
    exit(1);
}

mysqli_close($connection);
fwrite(STDOUT, "Inventory migration completed.\n");
