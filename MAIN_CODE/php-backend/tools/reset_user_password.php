<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';

if ($argc !== 1) {
    fwrite(STDERR, "Run this script without arguments. It will ask for the username and password.\n");
    exit(1);
}

fwrite(STDOUT, 'Username: ');
$username = trim((string) fgets(STDIN));

if ($username === '' || strlen($username) > 80) {
    fwrite(STDERR, "Username is invalid.\n");
    exit(1);
}

$connection = autoline_db_connect();

if ($connection === false) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$findStatement = mysqli_prepare(
    $connection,
    'SELECT id FROM users WHERE username = ? LIMIT 1'
);

if ($findStatement === false) {
    mysqli_close($connection);
    fwrite(STDERR, "User lookup statement could not be prepared.\n");
    exit(1);
}

mysqli_stmt_bind_param($findStatement, 's', $username);

if (!mysqli_stmt_execute($findStatement)) {
    mysqli_stmt_close($findStatement);
    mysqli_close($connection);
    fwrite(STDERR, "User lookup failed.\n");
    exit(1);
}

mysqli_stmt_bind_result($findStatement, $userId);
$userFound = mysqli_stmt_fetch($findStatement);
mysqli_stmt_close($findStatement);

if ($userFound !== true) {
    mysqli_close($connection);
    fwrite(STDERR, "User not found.\n");
    exit(1);
}

fwrite(STDOUT, "New password: ");
$newPassword = fgets(STDIN);
$newPassword = $newPassword === false ? '' : rtrim($newPassword, "\r\n");

fwrite(STDOUT, "Confirm new password: ");
$confirmedPassword = fgets(STDIN);
$confirmedPassword = $confirmedPassword === false ? '' : rtrim($confirmedPassword, "\r\n");

if (strlen($newPassword) < 8) {
    mysqli_close($connection);
    fwrite(STDERR, "Password must contain at least 8 characters.\n");
    exit(1);
}

if ($newPassword !== $confirmedPassword) {
    mysqli_close($connection);
    fwrite(STDERR, "Passwords do not match.\n");
    exit(1);
}

$passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

if ($passwordHash === false) {
    mysqli_close($connection);
    fwrite(STDERR, "Password hashing failed.\n");
    exit(1);
}

$updateStatement = mysqli_prepare(
    $connection,
    'UPDATE users SET password_hash = ? WHERE id = ?'
);

if ($updateStatement === false) {
    mysqli_close($connection);
    fwrite(STDERR, "Password update statement could not be prepared.\n");
    exit(1);
}

$userId = (int) $userId;
mysqli_stmt_bind_param($updateStatement, 'si', $passwordHash, $userId);
$updated = mysqli_stmt_execute($updateStatement);
mysqli_stmt_close($updateStatement);
mysqli_close($connection);

if (!$updated) {
    fwrite(STDERR, "Password update failed.\n");
    exit(1);
}

fwrite(STDOUT, "Password updated successfully.\n");
