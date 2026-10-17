<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';

auth_start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    auth_json_response(405, [
        'success' => false,
        'message' => 'Only POST requests are allowed.'
    ]);
}

$payload = auth_request_payload();
$username = trim((string) ($payload['username'] ?? ''));
$password = (string) ($payload['password'] ?? '');

if ($username === '' || $password === '' || strlen($username) > 80) {
    auth_json_response(400, [
        'success' => false,
        'message' => 'Username and password are required.'
    ]);
}

$connection = autoline_db_connect();

if ($connection === false) {
    auth_json_response(503, [
        'success' => false,
        'message' => 'Login is temporarily unavailable.'
    ]);
}

$statement = mysqli_prepare(
    $connection,
    'SELECT id, username, password_hash, role FROM users WHERE username = ? AND active = 1 LIMIT 1'
);

if ($statement === false) {
    error_log('AutoLine login statement preparation failed.');
    mysqli_close($connection);
    auth_json_response(503, [
        'success' => false,
        'message' => 'Login is temporarily unavailable.'
    ]);
}

mysqli_stmt_bind_param($statement, 's', $username);

if (!mysqli_stmt_execute($statement)) {
    error_log('AutoLine login statement execution failed.');
    mysqli_stmt_close($statement);
    mysqli_close($connection);
    auth_json_response(503, [
        'success' => false,
        'message' => 'Login is temporarily unavailable.'
    ]);
}

mysqli_stmt_bind_result($statement, $userId, $dbUsername, $passwordHash, $dbRole);
$userFound = mysqli_stmt_fetch($statement);
mysqli_stmt_close($statement);

if ($userFound !== true || !password_verify($password, $passwordHash)) {
    mysqli_close($connection);
    auth_json_response(401, [
        'success' => false,
        'message' => 'Invalid username or password.'
    ]);
}

if (password_needs_rehash($passwordHash, PASSWORD_DEFAULT)) {
    $newPasswordHash = password_hash($password, PASSWORD_DEFAULT);
    $rehashStatement = mysqli_prepare(
        $connection,
        'UPDATE users SET password_hash = ? WHERE id = ?'
    );

    if ($rehashStatement !== false) {
        $userId = (int) $userId;
        mysqli_stmt_bind_param($rehashStatement, 'si', $newPasswordHash, $userId);
        mysqli_stmt_execute($rehashStatement);
        mysqli_stmt_close($rehashStatement);
    }
}

mysqli_close($connection);
session_regenerate_id(true);

$_SESSION['user'] = [
    'id' => (int) $userId,
    'username' => (string) $dbUsername,
    'role' => (string) $dbRole,
];

auth_json_response(200, [
    'success' => true,
    'message' => 'Login successful.',
    'user' => [
        'username' => (string) $dbUsername,
        'role' => (string) $dbRole,
    ]
]);
