<?php

require_once __DIR__ . '/../auth/auth.php';

auth_start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    auth_json_response(405, [
        'success' => false,
        'message' => 'Only POST requests are allowed.'
    ]);
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $cookieParameters = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $cookieParameters['path'],
        'domain' => $cookieParameters['domain'],
        'secure' => $cookieParameters['secure'],
        'httponly' => $cookieParameters['httponly'],
        'samesite' => $cookieParameters['samesite'] ?? 'Lax',
    ]);
}

session_destroy();

auth_json_response(200, [
    'success' => true,
    'message' => 'Logout successful.'
]);
