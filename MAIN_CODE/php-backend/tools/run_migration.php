<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$migrationPath = __DIR__ . '/../database/migrations/001_create_users.sql';
$migration = file_get_contents($migrationPath);

if ($migration === false) {
    fwrite(STDERR, "Migration file could not be read.\n");
    exit(1);
}

$connection = autoline_db_connect();
if ($connection === false) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

if (!mysqli_query($connection, $migration)) {
    error_log('AutoLine users migration failed.');
    mysqli_close($connection);
    fwrite(STDERR, "Users migration failed.\n");
    exit(1);
}

mysqli_close($connection);
fwrite(STDOUT, "Users migration completed.\n");
