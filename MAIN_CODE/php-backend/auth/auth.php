<?php

function auth_start_session()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    session_name('AUTOLINE_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function auth_json_response($statusCode, array $payload)
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
}

function auth_request_payload()
{
    $rawBody = file_get_contents('php://input');
    $payload = json_decode($rawBody, true);

    if (!is_array($payload)) {
        $payload = $_POST;
    }

    return is_array($payload) ? $payload : [];
}

function auth_current_user()
{
    auth_start_session();

    if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
        return null;
    }

    $user = $_SESSION['user'];

    if (!isset($user['id'], $user['username'], $user['role'])) {
        return null;
    }

    return [
        'id' => (int) $user['id'],
        'username' => (string) $user['username'],
        'role' => (string) $user['role'],
    ];
}

function require_authenticated_user()
{
    $user = auth_current_user();

    if ($user === null) {
        auth_json_response(401, [
            'success' => false,
            'message' => 'Authentication is required.'
        ]);
    }

    return $user;
}
