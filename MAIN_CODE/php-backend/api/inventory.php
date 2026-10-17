<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';

auth_start_session();
require_authenticated_user();

function inventory_generate_id()
{
    return 'INV-' . date('YmdHis') . '-' . random_int(1000, 9999);
}

function inventory_item_from_payload(array $payload, $requireId = false)
{
    $id = trim((string) ($payload['id'] ?? ''));
    $image = trim((string) ($payload['image'] ?? ''));
    $partName = trim((string) ($payload['partName'] ?? ''));
    $partCode = trim((string) ($payload['partCode'] ?? ''));
    $category = trim((string) ($payload['category'] ?? ''));
    $compatibleWith = trim((string) ($payload['compatibleWith'] ?? ''));
    $status = trim((string) ($payload['status'] ?? ''));

    if (!$requireId && $id === '') {
        $id = inventory_generate_id();
    }

    if ($id === '' || strlen($id) > 40 || $partName === '' || strlen($partName) > 160 ||
        $partCode === '' || strlen($partCode) > 80 || $category === '' || strlen($category) > 100 ||
        $compatibleWith === '' || strlen($compatibleWith) > 160 || strlen($image) > 255) {
        return [false, null, 'Required inventory fields are invalid.'];
    }

    if (!array_key_exists('quantity', $payload) || filter_var($payload['quantity'], FILTER_VALIDATE_INT) === false || (int) $payload['quantity'] < 0) {
        return [false, null, 'Quantity is invalid.'];
    }

    if (!array_key_exists('minStock', $payload) || filter_var($payload['minStock'], FILTER_VALIDATE_INT) === false || (int) $payload['minStock'] < 0) {
        return [false, null, 'Minimum stock is invalid.'];
    }

    if (!array_key_exists('price', $payload) || !is_numeric($payload['price']) || (float) $payload['price'] < 0) {
        return [false, null, 'Price is invalid.'];
    }

    if (!in_array($status, ['In Stock', 'Low Stock', 'Out of Stock'], true)) {
        return [false, null, 'Status is invalid.'];
    }

    return [true, [
        'id' => $id,
        'image' => $image,
        'partName' => $partName,
        'partCode' => $partCode,
        'category' => $category,
        'compatibleWith' => $compatibleWith,
        'quantity' => (int) $payload['quantity'],
        'minStock' => (int) $payload['minStock'],
        'price' => (float) $payload['price'],
        'status' => $status,
    ], null];
}

function inventory_prepare_insert($connection)
{
    return mysqli_prepare(
        $connection,
        'INSERT INTO inventory_items
            (id, image, part_name, part_code, category, compatible_with, quantity, min_stock, price, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
}

function inventory_bind_insert($statement, array $item)
{
    mysqli_stmt_bind_param(
        $statement,
        'ssssssiids',
        $item['id'],
        $item['image'],
        $item['partName'],
        $item['partCode'],
        $item['category'],
        $item['compatibleWith'],
        $item['quantity'],
        $item['minStock'],
        $item['price'],
        $item['status']
    );
}

function inventory_fetch_all($connection)
{
    $statement = mysqli_prepare(
        $connection,
        'SELECT id, image, part_name, part_code, category, compatible_with,
                quantity, min_stock, price, status
         FROM inventory_items
         ORDER BY created_at ASC, id ASC'
    );

    if ($statement === false || !mysqli_stmt_execute($statement)) {
        if ($statement !== false) {
            mysqli_stmt_close($statement);
        }
        return false;
    }

    mysqli_stmt_bind_result(
        $statement,
        $id,
        $image,
        $partName,
        $partCode,
        $category,
        $compatibleWith,
        $quantity,
        $minStock,
        $price,
        $status
    );

    $items = [];
    while (mysqli_stmt_fetch($statement) === true) {
        $items[] = [
            'id' => (string) $id,
            'image' => (string) $image,
            'partName' => (string) $partName,
            'partCode' => (string) $partCode,
            'category' => (string) $category,
            'compatibleWith' => (string) $compatibleWith,
            'quantity' => (int) $quantity,
            'minStock' => (int) $minStock,
            'price' => (float) $price,
            'status' => (string) $status,
        ];
    }

    mysqli_stmt_close($statement);
    return $items;
}

$method = $_SERVER['REQUEST_METHOD'];
$payload = auth_request_payload();
$connection = autoline_db_connect();

if ($connection === false) {
    auth_json_response(503, [
        'success' => false,
        'message' => 'Inventory database is unavailable.'
    ]);
}

if ($method === 'GET') {
    $items = inventory_fetch_all($connection);
    mysqli_close($connection);

    if ($items === false) {
        error_log('AutoLine inventory fetch failed.');
        auth_json_response(503, [
            'success' => false,
            'message' => 'Inventory could not be loaded.'
        ]);
    }

    auth_json_response(200, [
        'success' => true,
        'items' => $items
    ]);
}

if ($method === 'POST' && isset($payload['items']) && is_array($payload['items'])) {
    if (count($payload['items']) > 500) {
        mysqli_close($connection);
        auth_json_response(400, [
            'success' => false,
            'message' => 'Too many inventory items in one import.'
        ]);
    }

    $statement = inventory_prepare_insert($connection);

    if ($statement === false) {
        mysqli_close($connection);
        error_log('AutoLine inventory import statement preparation failed.');
        auth_json_response(503, [
            'success' => false,
            'message' => 'Inventory import is unavailable.'
        ]);
    }

    mysqli_begin_transaction($connection);

    foreach ($payload['items'] as $rawItem) {
        if (!is_array($rawItem)) {
            mysqli_rollback($connection);
            mysqli_stmt_close($statement);
            mysqli_close($connection);
            auth_json_response(400, [
                'success' => false,
                'message' => 'Inventory import data is invalid.'
            ]);
        }

        [$valid, $item, $error] = inventory_item_from_payload($rawItem);

        if (!$valid) {
            mysqli_rollback($connection);
            mysqli_stmt_close($statement);
            mysqli_close($connection);
            auth_json_response(400, [
                'success' => false,
                'message' => $error
            ]);
        }

        inventory_bind_insert($statement, $item);

        if (!mysqli_stmt_execute($statement)) {
            mysqli_rollback($connection);
            mysqli_stmt_close($statement);
            mysqli_close($connection);
            error_log('AutoLine inventory import execution failed.');
            auth_json_response(409, [
                'success' => false,
                'message' => 'Inventory import could not be completed.'
            ]);
        }
    }

    mysqli_commit($connection);
    mysqli_stmt_close($statement);
    mysqli_close($connection);

    auth_json_response(201, [
        'success' => true,
        'message' => 'Inventory imported successfully.',
        'imported' => count($payload['items'])
    ]);
}

if ($method === 'POST') {
    [$valid, $item, $error] = inventory_item_from_payload($payload);

    if (!$valid) {
        mysqli_close($connection);
        auth_json_response(400, [
            'success' => false,
            'message' => $error
        ]);
    }

    $statement = inventory_prepare_insert($connection);

    if ($statement === false) {
        mysqli_close($connection);
        error_log('AutoLine inventory insert statement preparation failed.');
        auth_json_response(503, [
            'success' => false,
            'message' => 'Inventory item could not be created.'
        ]);
    }

    inventory_bind_insert($statement, $item);
    $created = mysqli_stmt_execute($statement);
    mysqli_stmt_close($statement);
    mysqli_close($connection);

    if (!$created) {
        error_log('AutoLine inventory insert failed.');
        auth_json_response(409, [
            'success' => false,
            'message' => 'Inventory item could not be created.'
        ]);
    }

    auth_json_response(201, [
        'success' => true,
        'item' => $item
    ]);
}

if ($method === 'PUT') {
    [$valid, $item, $error] = inventory_item_from_payload($payload, true);

    if (!$valid) {
        mysqli_close($connection);
        auth_json_response(400, [
            'success' => false,
            'message' => $error
        ]);
    }

    $statement = mysqli_prepare(
        $connection,
        'UPDATE inventory_items
         SET image = ?, part_name = ?, part_code = ?, category = ?, compatible_with = ?,
             quantity = ?, min_stock = ?, price = ?, status = ?
         WHERE id = ?'
    );

    if ($statement === false) {
        mysqli_close($connection);
        error_log('AutoLine inventory update statement preparation failed.');
        auth_json_response(503, [
            'success' => false,
            'message' => 'Inventory item could not be updated.'
        ]);
    }

    mysqli_stmt_bind_param(
        $statement,
        'sssssiidss',
        $item['image'],
        $item['partName'],
        $item['partCode'],
        $item['category'],
        $item['compatibleWith'],
        $item['quantity'],
        $item['minStock'],
        $item['price'],
        $item['status'],
        $item['id']
    );
    $updated = mysqli_stmt_execute($statement);
    mysqli_stmt_close($statement);
    mysqli_close($connection);

    if (!$updated) {
        error_log('AutoLine inventory update failed.');
        auth_json_response(503, [
            'success' => false,
            'message' => 'Inventory item could not be updated.'
        ]);
    }

    auth_json_response(200, [
        'success' => true,
        'item' => $item
    ]);
}

if ($method === 'DELETE') {
    $id = trim((string) ($payload['id'] ?? $_GET['id'] ?? ''));

    if ($id === '' || strlen($id) > 40) {
        mysqli_close($connection);
        auth_json_response(400, [
            'success' => false,
            'message' => 'Inventory item ID is invalid.'
        ]);
    }

    $statement = mysqli_prepare($connection, 'DELETE FROM inventory_items WHERE id = ?');

    if ($statement === false) {
        mysqli_close($connection);
        error_log('AutoLine inventory delete statement preparation failed.');
        auth_json_response(503, [
            'success' => false,
            'message' => 'Inventory item could not be deleted.'
        ]);
    }

    mysqli_stmt_bind_param($statement, 's', $id);
    $deleted = mysqli_stmt_execute($statement);
    $affectedRows = mysqli_stmt_affected_rows($statement);
    mysqli_stmt_close($statement);
    mysqli_close($connection);

    if (!$deleted || $affectedRows < 1) {
        auth_json_response(404, [
            'success' => false,
            'message' => 'Inventory item was not found.'
        ]);
    }

    auth_json_response(200, [
        'success' => true,
        'message' => 'Inventory item deleted.'
    ]);
}

mysqli_close($connection);
auth_json_response(405, [
    'success' => false,
    'message' => 'Method is not allowed.'
]);
