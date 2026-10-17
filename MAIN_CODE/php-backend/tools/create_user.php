<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$roles = [
    'admin' => 'ADMIN',
    'manager' => 'MANAGER',
    'mechanic' => 'MECHANIC',
];

if ($argc !== 1) {
    fwrite(STDERR, "Run this script without arguments. It will ask for the account details.\n");
    exit(1);
}

fwrite(STDOUT, 'Username: ');
$username = trim((string) fgets(STDIN));

fwrite(STDOUT, 'Role (ADMIN, MANAGER, or MECHANIC): ');
$roleInput = strtolower(trim((string) fgets(STDIN)));
$role = $roles[$roleInput] ?? null;

if ($username === '' || strlen($username) > 80 || $role === null) {
    fwrite(STDERR, "Username or role is invalid.\n");
    exit(1);
}

fwrite(STDOUT, "Password: ");
$password = fgets(STDIN);
$password = $password === false ? '' : rtrim($password, "\r\n");

if (strlen($password) < 8) {
    fwrite(STDERR, "Password must contain at least 8 characters.\n");
    exit(1);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
if ($passwordHash === false) {
    fwrite(STDERR, "Password hashing failed.\n");
    exit(1);
}

$connection = autoline_db_connect();
if ($connection === false) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$statement = mysqli_prepare(
    $connection,
    'INSERT INTO users (username, password_hash, role, active) VALUES (?, ?, ?, 1)'
);

if ($statement === false) {
    mysqli_close($connection);
    fwrite(STDERR, "User creation statement could not be prepared.\n");
    exit(1);
}

mysqli_stmt_bind_param($statement, 'sss', $username, $passwordHash, $role);
$created = mysqli_stmt_execute($statement);
$errorNumber = mysqli_stmt_errno($statement);
mysqli_stmt_close($statement);
mysqli_close($connection);

if (!$created) {
    if ($errorNumber === 1062) {
        fwrite(STDERR, "That username already exists.\n");
    } else {
        fwrite(STDERR, "User creation failed.\n");
    }
    exit(1);
}

fwrite(STDOUT, "User created successfully.\n");
