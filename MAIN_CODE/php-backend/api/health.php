<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

function health_response($statusCode, $payload)
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

$connection = autoline_db_connect();

if ($connection === false) {
    health_response(503, [
        'success' => false,
        'message' => 'Database connection is unavailable.'
    ]);
}

$result = mysqli_query($connection, 'SELECT 1 FROM job_cards LIMIT 1');

if ($result === false) {
    error_log('AutoLine health check query failed: ' . mysqli_error($connection));
    mysqli_close($connection);

    health_response(503, [
        'success' => false,
        'message' => 'The database is reachable, but the required table could not be verified.'
    ]);
}

mysqli_free_result($result);
mysqli_close($connection);

health_response(200, [
    'success' => true,
    'message' => 'Database connection and job_cards table are available.'
]);
