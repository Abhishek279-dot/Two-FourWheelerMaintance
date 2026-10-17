<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';

auth_start_session();
require_authenticated_user();

function customer_payload(array $payload, $requireId = false)
{
    $id = (int) ($payload['id'] ?? 0);
    $legacyKey = trim((string) ($payload['legacyKey'] ?? ''));
    $name = trim((string) ($payload['name'] ?? $payload['customerName'] ?? ''));
    $phone = trim((string) ($payload['phone'] ?? $payload['phoneNumber'] ?? ''));
    $address = trim((string) ($payload['address'] ?? $payload['customerAddress'] ?? ''));

    if (($requireId && $id < 1) || $name === '' || strlen($name) > 160 || strlen($legacyKey) > 255 ||
        strlen($phone) > 40 || strlen($address) > 255) {
        return [false, null, 'Customer fields are invalid.'];
    }

    return [true, [
        'id' => $id,
        'legacyKey' => $legacyKey,
        'name' => $name,
        'customerName' => $name,
        'phone' => $phone,
        'phoneNumber' => $phone,
        'address' => $address,
        'customerAddress' => $address,
    ], null];
}

function customer_row(array $row)
{
    return [
        'id' => (int) $row['id'],
        'legacyKey' => (string) ($row['legacy_key'] ?? ''),
        'name' => (string) $row['name'],
        'customerName' => (string) $row['name'],
        'phone' => (string) ($row['phone'] ?? ''),
        'phoneNumber' => (string) ($row['phone'] ?? ''),
        'address' => (string) ($row['address'] ?? ''),
        'customerAddress' => (string) ($row['address'] ?? ''),
        'vehicleCount' => (int) ($row['vehicle_count'] ?? 0),
    ];
}

function customer_find_by_id($connection, $id)
{
    $statement = mysqli_prepare($connection, 'SELECT id, legacy_key, name, phone, address FROM customers WHERE id = ? LIMIT 1');
    if ($statement === false) {
        return null;
    }
    mysqli_stmt_bind_param($statement, 'i', $id);
    mysqli_stmt_execute($statement);
    mysqli_stmt_bind_result($statement, $rowId, $legacyKey, $name, $phone, $address);
    $found = mysqli_stmt_fetch($statement) === true;
    mysqli_stmt_close($statement);
    return $found ? [
        'id' => $rowId,
        'legacy_key' => $legacyKey,
        'name' => $name,
        'phone' => $phone,
        'address' => $address,
    ] : null;
}

function customer_save($connection, array $customer, $updateExisting = false)
{
    if ($customer['legacyKey'] !== '') {
        $find = mysqli_prepare($connection, 'SELECT id FROM customers WHERE legacy_key = ? LIMIT 1');
        if ($find === false) {
            return false;
        }
        mysqli_stmt_bind_param($find, 's', $customer['legacyKey']);
        mysqli_stmt_execute($find);
        mysqli_stmt_bind_result($find, $existingId);
        $found = mysqli_stmt_fetch($find) === true;
        mysqli_stmt_close($find);
        if ($found) {
            $customer['id'] = (int) $existingId;
            $updateExisting = true;
        }
    }

    if ($customer['id'] > 0 && $updateExisting) {
        $statement = mysqli_prepare($connection, 'UPDATE customers SET legacy_key = NULLIF(?, \'\'), name = ?, phone = NULLIF(?, \'\'), address = NULLIF(?, \'\') WHERE id = ?');
        if ($statement === false) {
            return false;
        }
        mysqli_stmt_bind_param($statement, 'ssssi', $customer['legacyKey'], $customer['name'], $customer['phone'], $customer['address'], $customer['id']);
        $saved = mysqli_stmt_execute($statement);
        mysqli_stmt_close($statement);
        return $saved ? $customer['id'] : false;
    }

    $statement = mysqli_prepare($connection, 'INSERT INTO customers (legacy_key, name, phone, address) VALUES (NULLIF(?, \'\'), ?, NULLIF(?, \'\'), NULLIF(?, \'\'))');
    if ($statement === false) {
        return false;
    }
    mysqli_stmt_bind_param($statement, 'ssss', $customer['legacyKey'], $customer['name'], $customer['phone'], $customer['address']);
    $saved = mysqli_stmt_execute($statement);
    $id = mysqli_insert_id($connection);
    mysqli_stmt_close($statement);
    return $saved ? $id : false;
}

function customer_fetch_all($connection, $query = '')
{
    $query = trim($query);
    if ($query === '') {
        $statement = mysqli_prepare($connection, 'SELECT c.id, c.legacy_key, c.name, c.phone, c.address, COUNT(v.id) AS vehicle_count FROM customers c LEFT JOIN vehicles v ON v.customer_id = c.id GROUP BY c.id ORDER BY c.name ASC, c.id ASC');
    } else {
        $like = '%' . $query . '%';
        $statement = mysqli_prepare($connection, 'SELECT c.id, c.legacy_key, c.name, c.phone, c.address, COUNT(v.id) AS vehicle_count FROM customers c LEFT JOIN vehicles v ON v.customer_id = c.id WHERE c.name LIKE ? OR c.phone LIKE ? OR c.address LIKE ? GROUP BY c.id ORDER BY c.name ASC, c.id ASC');
        if ($statement !== false) {
            mysqli_stmt_bind_param($statement, 'sss', $like, $like, $like);
        }
    }

    if ($statement === false || !mysqli_stmt_execute($statement)) {
        if ($statement !== false) {
            mysqli_stmt_close($statement);
        }
        return false;
    }

    mysqli_stmt_bind_result($statement, $id, $legacyKey, $name, $phone, $address, $vehicleCount);
    $rows = [];
    while (mysqli_stmt_fetch($statement) === true) {
        $rows[] = customer_row([
            'id' => $id,
            'legacy_key' => $legacyKey,
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'vehicle_count' => $vehicleCount,
        ]);
    }
    mysqli_stmt_close($statement);
    return $rows;
}

$method = $_SERVER['REQUEST_METHOD'];
$payload = auth_request_payload();
$connection = autoline_db_connect();

if ($connection === false) {
    auth_json_response(503, ['success' => false, 'message' => 'Customer database is unavailable.']);
}

if ($method === 'GET') {
    $rows = customer_fetch_all($connection, $_GET['q'] ?? '');
    mysqli_close($connection);
    if ($rows === false) {
        auth_json_response(503, ['success' => false, 'message' => 'Customers could not be loaded.']);
    }
    auth_json_response(200, ['success' => true, 'items' => $rows]);
}

if ($method === 'POST' && isset($payload['items']) && is_array($payload['items'])) {
    if (count($payload['items']) > 1000) {
        mysqli_close($connection);
        auth_json_response(400, ['success' => false, 'message' => 'Too many customers in one import.']);
    }
    mysqli_begin_transaction($connection);
    $items = [];
    foreach ($payload['items'] as $rawCustomer) {
        if (!is_array($rawCustomer)) {
            mysqli_rollback($connection);
            mysqli_close($connection);
            auth_json_response(400, ['success' => false, 'message' => 'Customer import data is invalid.']);
        }
        [$valid, $customer, $error] = customer_payload($rawCustomer);
        if (!$valid) {
            mysqli_rollback($connection);
            mysqli_close($connection);
            auth_json_response(400, ['success' => false, 'message' => $error]);
        }
        $id = customer_save($connection, $customer, true);
        if ($id === false) {
            mysqli_rollback($connection);
            mysqli_close($connection);
            auth_json_response(409, ['success' => false, 'message' => 'Customer import could not be completed.']);
        }
        $customer['id'] = $id;
        $items[] = $customer;
    }
    mysqli_commit($connection);
    mysqli_close($connection);
    auth_json_response(201, ['success' => true, 'items' => $items, 'imported' => count($items)]);
}

if ($method === 'POST') {
    [$valid, $customer, $error] = customer_payload($payload);
    if (!$valid) {
        mysqli_close($connection);
        auth_json_response(400, ['success' => false, 'message' => $error]);
    }
    $id = customer_save($connection, $customer);
    mysqli_close($connection);
    if ($id === false) {
        auth_json_response(409, ['success' => false, 'message' => 'Customer could not be created.']);
    }
    $customer['id'] = $id;
    auth_json_response(201, ['success' => true, 'item' => $customer]);
}

if ($method === 'PUT') {
    [$valid, $customer, $error] = customer_payload($payload, true);
    if (!$valid) {
        mysqli_close($connection);
        auth_json_response(400, ['success' => false, 'message' => $error]);
    }
    if (customer_find_by_id($connection, $customer['id']) === null) {
        mysqli_close($connection);
        auth_json_response(404, ['success' => false, 'message' => 'Customer was not found.']);
    }
    $saved = customer_save($connection, $customer, true);
    mysqli_close($connection);
    if ($saved === false) {
        auth_json_response(503, ['success' => false, 'message' => 'Customer could not be updated.']);
    }
    auth_json_response(200, ['success' => true, 'item' => $customer]);
}

if ($method === 'DELETE') {
    $id = (int) ($payload['id'] ?? $_GET['id'] ?? 0);
    if ($id < 1) {
        mysqli_close($connection);
        auth_json_response(400, ['success' => false, 'message' => 'Customer ID is invalid.']);
    }
    $statement = mysqli_prepare($connection, 'DELETE FROM customers WHERE id = ?');
    mysqli_stmt_bind_param($statement, 'i', $id);
    $deleted = mysqli_stmt_execute($statement);
    $affectedRows = mysqli_stmt_affected_rows($statement);
    $errorCode = mysqli_stmt_errno($statement);
    mysqli_stmt_close($statement);
    mysqli_close($connection);
    if (!$deleted && $errorCode === 1451) {
        auth_json_response(409, ['success' => false, 'message' => 'Customer has vehicles and cannot be deleted.']);
    }
    if (!$deleted || $affectedRows < 1) {
        auth_json_response(404, ['success' => false, 'message' => 'Customer was not found.']);
    }
    auth_json_response(200, ['success' => true, 'message' => 'Customer deleted.']);
}

mysqli_close($connection);
auth_json_response(405, ['success' => false, 'message' => 'Method is not allowed.']);
