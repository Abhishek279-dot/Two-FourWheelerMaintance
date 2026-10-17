<?php

mysqli_report(MYSQLI_REPORT_OFF);

function autoline_db_connect()
{
    $localConfigPath = __DIR__ . '/database.local.php';

    if (!is_readable($localConfigPath)) {
        error_log('AutoLine database local configuration is missing.');
        return false;
    }

    $config = require $localConfigPath;

    if (!is_array($config)) {
        error_log('AutoLine database local configuration is invalid.');
        return false;
    }

    $requiredKeys = ['host', 'username', 'password', 'database', 'port'];
    foreach ($requiredKeys as $key) {
        if (!array_key_exists($key, $config)) {
            error_log('AutoLine database local configuration is incomplete.');
            return false;
        }
    }

    $port = (int) $config['port'];

    if ($port < 1 || $port > 65535) {
        error_log('AutoLine database port configuration is invalid.');
        return false;
    }

    $connection = mysqli_connect(
        $config['host'],
        $config['username'],
        $config['password'],
        $config['database'],
        $port
    );

    if ($connection === false) {
        error_log('AutoLine database connection failed (MySQLi error ' . mysqli_connect_errno() . ').');
        return false;
    }

    if (!mysqli_set_charset($connection, 'utf8mb4')) {
        error_log('AutoLine database charset configuration failed.');
        mysqli_close($connection);
        return false;
    }

    return $connection;
}
