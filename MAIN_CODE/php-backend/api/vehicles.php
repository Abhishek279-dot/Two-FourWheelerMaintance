<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';

auth_start_session();
require_authenticated_user();

function vehicle_payload(array $payload, $requireId = false)
{
    $id = (int) ($payload['id'] ?? 0);
    $registrationNumber = trim((string) ($payload['registrationNumber'] ?? ''));
    $vehicleModel = trim((string) ($payload['vehicleModel'] ?? ''));
    $purchaseDate = trim((string) ($payload['purchaseDate'] ?? ''));
    $dealerCode = trim((string) ($payload['dealerCode'] ?? ''));
    $dealerName = trim((string) ($payload['dealerName'] ?? ''));
    $customer = is_array($payload['customer'] ?? null) ? $payload['customer'] : $payload;
    $customerId = (int) ($payload['customerId'] ?? $customer['customerId'] ?? 0);
    $customerName = trim((string) ($customer['name'] ?? $customer['customerName'] ?? ''));
    $phone = trim((string) ($customer['phone'] ?? $customer['phoneNumber'] ?? ''));
    $address = trim((string) ($customer['address'] ?? $customer['customerAddress'] ?? ''));
    $legacyKey = trim((string) ($payload['customerLegacyKey'] ?? $customer['legacyKey'] ?? ''));

    $validDate = $purchaseDate === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $purchaseDate);
    if (($requireId && $id < 1) || $registrationNumber === '' || strlen($registrationNumber) > 32 ||
        $vehicleModel === '' || strlen($vehicleModel) > 160 || $customerName === '' || strlen($customerName) > 160 ||
        strlen($phone) > 40 || strlen($address) > 255 || strlen($dealerCode) > 80 || strlen($dealerName) > 160 ||
        strlen($legacyKey) > 255 || !$validDate) {
        return [false, null, 'Vehicle and customer fields are invalid.'];
    }

    return [true, [
        'id' => $id,
        'registrationNumber' => $registrationNumber,
        'vehicleModel' => $vehicleModel,
        'purchaseDate' => $purchaseDate,
        'dealerCode' => $dealerCode,
        'dealerName' => $dealerName,
        'customerId' => $customerId,
        'customerLegacyKey' => $legacyKey,
        'customerName' => $customerName,
        'phoneNumber' => $phone,
        'customerAddress' => $address,
    ], null];
}

function vehicle_row(array $row)
{
    $customer = [
        'id' => (int) $row['customer_id'],
        'name' => (string) ($row['customer_name'] ?? ''),
        'customerName' => (string) ($row['customer_name'] ?? ''),
        'phone' => (string) ($row['customer_phone'] ?? ''),
        'phoneNumber' => (string) ($row['customer_phone'] ?? ''),
        'address' => (string) ($row['customer_address'] ?? ''),
        'customerAddress' => (string) ($row['customer_address'] ?? ''),
    ];
    return [
        'id' => (int) $row['id'],
        'registrationNumber' => (string) $row['registration_number'],
        'vehicleModel' => (string) $row['vehicle_model'],
        'customerId' => $customer['id'],
        'customerName' => $customer['customerName'],
        'phoneNumber' => $customer['phoneNumber'],
        'customerAddress' => $customer['customerAddress'],
        'purchaseDate' => (string) ($row['purchase_date'] ?? ''),
        'dealerCode' => (string) ($row['dealer_code'] ?? ''),
        'dealerName' => (string) ($row['dealer_name'] ?? ''),
        'customer' => $customer,
    ];
}

function vehicle_find_customer($connection, array $vehicle)
{
    if ($vehicle['customerId'] > 0) {
        $statement = mysqli_prepare($connection, 'SELECT id FROM customers WHERE id = ? LIMIT 1');
        if ($statement === false) {
            return false;
        }
        mysqli_stmt_bind_param($statement, 'i', $vehicle['customerId']);
        mysqli_stmt_execute($statement);
        mysqli_stmt_bind_result($statement, $id);
        $found = mysqli_stmt_fetch($statement) === true;
        mysqli_stmt_close($statement);
        return $found ? (int) $id : false;
    }

    $customer = [
        'id' => 0,
        'legacyKey' => $vehicle['customerLegacyKey'],
        'name' => $vehicle['customerName'],
        'phone' => $vehicle['phoneNumber'],
        'address' => $vehicle['customerAddress'],
    ];

    if ($customer['legacyKey'] !== '') {
        $statement = mysqli_prepare($connection, 'SELECT id FROM customers WHERE legacy_key = ? LIMIT 1');
        if ($statement === false) {
            return false;
        }
        mysqli_stmt_bind_param($statement, 's', $customer['legacyKey']);
        mysqli_stmt_execute($statement);
        mysqli_stmt_bind_result($statement, $id);
        $found = mysqli_stmt_fetch($statement) === true;
        mysqli_stmt_close($statement);
        if ($found) {
            $customer['id'] = (int) $id;
        }
    } elseif ($customer['phone'] !== '') {
        $statement = mysqli_prepare($connection, 'SELECT id FROM customers WHERE phone = ? ORDER BY id ASC LIMIT 1');
        if ($statement === false) {
            return false;
        }
        mysqli_stmt_bind_param($statement, 's', $customer['phone']);
        mysqli_stmt_execute($statement);
        mysqli_stmt_bind_result($statement, $id);
        $found = mysqli_stmt_fetch($statement) === true;
        mysqli_stmt_close($statement);
        if ($found) {
            $customer['id'] = (int) $id;
        }
    } else {
        $statement = mysqli_prepare($connection, 'SELECT id FROM customers WHERE name = ? AND address <=> ? ORDER BY id ASC LIMIT 1');
        if ($statement === false) {
            return false;
        }
        mysqli_stmt_bind_param($statement, 'ss', $customer['name'], $customer['address']);
        mysqli_stmt_execute($statement);
        mysqli_stmt_bind_result($statement, $id);
        $found = mysqli_stmt_fetch($statement) === true;
        mysqli_stmt_close($statement);
        if ($found) {
            $customer['id'] = (int) $id;
        }
    }

    if ($customer['id'] > 0) {
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

function vehicle_save($connection, array $vehicle, $matchId = 0)
{
    $customerId = vehicle_find_customer($connection, $vehicle);
    if ($customerId === false) {
        return false;
    }

    if ($matchId > 0) {
        $find = mysqli_prepare($connection, 'SELECT id FROM vehicles WHERE id = ? LIMIT 1');
    } else {
        $find = mysqli_prepare($connection, 'SELECT id FROM vehicles WHERE registration_number = ? LIMIT 1');
    }
    if ($find === false) {
        return false;
    }
    if ($matchId > 0) {
        mysqli_stmt_bind_param($find, 'i', $matchId);
    } else {
        mysqli_stmt_bind_param($find, 's', $vehicle['registrationNumber']);
    }
    mysqli_stmt_execute($find);
    mysqli_stmt_bind_result($find, $existingId);
    $exists = mysqli_stmt_fetch($find) === true;
    mysqli_stmt_close($find);

    if ($matchId > 0 && !$exists) {
        return false;
    }

    if ($exists) {
        $statement = mysqli_prepare($connection, 'UPDATE vehicles SET vehicle_model = ?, customer_id = ?, purchase_date = NULLIF(?, \'\'), dealer_code = NULLIF(?, \'\'), dealer_name = NULLIF(?, \'\') WHERE id = ?');
        if ($statement === false) {
            return false;
        }
        mysqli_stmt_bind_param($statement, 'sisssi', $vehicle['vehicleModel'], $customerId, $vehicle['purchaseDate'], $vehicle['dealerCode'], $vehicle['dealerName'], $existingId);
        $saved = mysqli_stmt_execute($statement);
        mysqli_stmt_close($statement);
        return $saved ? (int) $existingId : false;
    }

    $statement = mysqli_prepare($connection, 'INSERT INTO vehicles (registration_number, vehicle_model, customer_id, purchase_date, dealer_code, dealer_name) VALUES (?, ?, ?, NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'))');
    if ($statement === false) {
        return false;
    }
    mysqli_stmt_bind_param($statement, 'ssisss', $vehicle['registrationNumber'], $vehicle['vehicleModel'], $customerId, $vehicle['purchaseDate'], $vehicle['dealerCode'], $vehicle['dealerName']);
    $saved = mysqli_stmt_execute($statement);
    $id = mysqli_insert_id($connection);
    mysqli_stmt_close($statement);
    return $saved ? $id : false;
}

function vehicle_fetch_all($connection, $query = '')
{
    $base = 'SELECT v.id, v.registration_number, v.vehicle_model, v.customer_id, v.purchase_date, v.dealer_code, v.dealer_name, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address FROM vehicles v INNER JOIN customers c ON c.id = v.customer_id';
    $query = trim($query);
    if ($query === '') {
        $statement = mysqli_prepare($connection, $base . ' ORDER BY v.registration_number ASC');
    } else {
        $like = '%' . $query . '%';
        $statement = mysqli_prepare($connection, $base . ' WHERE v.registration_number LIKE ? OR v.vehicle_model LIKE ? OR c.name LIKE ? OR c.phone LIKE ? ORDER BY v.registration_number ASC');
        if ($statement !== false) {
            mysqli_stmt_bind_param($statement, 'ssss', $like, $like, $like, $like);
        }
    }
    if ($statement === false || !mysqli_stmt_execute($statement)) {
        if ($statement !== false) {
            mysqli_stmt_close($statement);
        }
        return false;
    }
    mysqli_stmt_bind_result($statement, $id, $registrationNumber, $vehicleModel, $customerId, $purchaseDate, $dealerCode, $dealerName, $customerName, $customerPhone, $customerAddress);
    $rows = [];
    while (mysqli_stmt_fetch($statement) === true) {
        $rows[] = vehicle_row([
            'id' => $id,
            'registration_number' => $registrationNumber,
            'vehicle_model' => $vehicleModel,
            'customer_id' => $customerId,
            'purchase_date' => $purchaseDate,
            'dealer_code' => $dealerCode,
            'dealer_name' => $dealerName,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'customer_address' => $customerAddress,
        ]);
    }
    mysqli_stmt_close($statement);
    return $rows;
}

function vehicle_find_by_id($connection, $id)
{
    $statement = mysqli_prepare($connection, 'SELECT v.id, v.registration_number, v.vehicle_model, v.customer_id, v.purchase_date, v.dealer_code, v.dealer_name, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address FROM vehicles v INNER JOIN customers c ON c.id = v.customer_id WHERE v.id = ? LIMIT 1');
    if ($statement === false) {
        return null;
    }
    mysqli_stmt_bind_param($statement, 'i', $id);
    mysqli_stmt_execute($statement);
    mysqli_stmt_bind_result($statement, $vehicleId, $registrationNumber, $vehicleModel, $customerId, $purchaseDate, $dealerCode, $dealerName, $customerName, $customerPhone, $customerAddress);
    $found = mysqli_stmt_fetch($statement) === true;
    mysqli_stmt_close($statement);
    return $found ? vehicle_row([
        'id' => $vehicleId,
        'registration_number' => $registrationNumber,
        'vehicle_model' => $vehicleModel,
        'customer_id' => $customerId,
        'purchase_date' => $purchaseDate,
        'dealer_code' => $dealerCode,
        'dealer_name' => $dealerName,
        'customer_name' => $customerName,
        'customer_phone' => $customerPhone,
        'customer_address' => $customerAddress,
    ]) : null;
}

$method = $_SERVER['REQUEST_METHOD'];
$payload = auth_request_payload();
$connection = autoline_db_connect();

if ($connection === false) {
    auth_json_response(503, ['success' => false, 'message' => 'Vehicle database is unavailable.']);
}

if ($method === 'GET') {
    $rows = vehicle_fetch_all($connection, $_GET['q'] ?? '');
    mysqli_close($connection);
    if ($rows === false) {
        auth_json_response(503, ['success' => false, 'message' => 'Vehicles could not be loaded.']);
    }
    auth_json_response(200, ['success' => true, 'items' => $rows]);
}

if ($method === 'POST' && isset($payload['items']) && is_array($payload['items'])) {
    if (count($payload['items']) > 1000) {
        mysqli_close($connection);
        auth_json_response(400, ['success' => false, 'message' => 'Too many vehicles in one import.']);
    }
    mysqli_begin_transaction($connection);
    $items = [];
    foreach ($payload['items'] as $rawVehicle) {
        if (!is_array($rawVehicle)) {
            mysqli_rollback($connection);
            mysqli_close($connection);
            auth_json_response(400, ['success' => false, 'message' => 'Vehicle import data is invalid.']);
        }
        [$valid, $vehicle, $error] = vehicle_payload($rawVehicle);
        if (!$valid) {
            mysqli_rollback($connection);
            mysqli_close($connection);
            auth_json_response(400, ['success' => false, 'message' => $error]);
        }
        $id = vehicle_save($connection, $vehicle);
        if ($id === false) {
            mysqli_rollback($connection);
            mysqli_close($connection);
            auth_json_response(409, ['success' => false, 'message' => 'Vehicle import could not be completed.']);
        }
        $vehicle['id'] = $id;
        $items[] = $vehicle;
    }
    mysqli_commit($connection);
    $rows = vehicle_fetch_all($connection);
    mysqli_close($connection);
    auth_json_response(201, ['success' => true, 'items' => $rows, 'imported' => count($items)]);
}

if ($method === 'POST' || $method === 'PUT') {
    [$valid, $vehicle, $error] = vehicle_payload($payload, $method === 'PUT');
    if (!$valid) {
        mysqli_close($connection);
        auth_json_response(400, ['success' => false, 'message' => $error]);
    }
    mysqli_begin_transaction($connection);
    $id = vehicle_save($connection, $vehicle, $method === 'PUT' ? $vehicle['id'] : 0);
    if ($id === false) {
        mysqli_rollback($connection);
        mysqli_close($connection);
        auth_json_response(409, ['success' => false, 'message' => 'Vehicle could not be saved.']);
    }
    mysqli_commit($connection);
    $item = vehicle_find_by_id($connection, $id);
    mysqli_close($connection);
    if ($item === null) {
        auth_json_response(503, ['success' => false, 'message' => 'Vehicle was saved but could not be loaded.']);
    }
    auth_json_response($method === 'POST' ? 201 : 200, ['success' => true, 'item' => $item]);
}

if ($method === 'DELETE') {
    $id = (int) ($payload['id'] ?? $_GET['id'] ?? 0);
    if ($id < 1) {
        mysqli_close($connection);
        auth_json_response(400, ['success' => false, 'message' => 'Vehicle ID is invalid.']);
    }
    $statement = mysqli_prepare($connection, 'DELETE FROM vehicles WHERE id = ?');
    mysqli_stmt_bind_param($statement, 'i', $id);
    $deleted = mysqli_stmt_execute($statement);
    $affectedRows = mysqli_stmt_affected_rows($statement);
    mysqli_stmt_close($statement);
    mysqli_close($connection);
    if (!$deleted || $affectedRows < 1) {
        auth_json_response(404, ['success' => false, 'message' => 'Vehicle was not found.']);
    }
    auth_json_response(200, ['success' => true, 'message' => 'Vehicle deleted.']);
}

mysqli_close($connection);
auth_json_response(405, ['success' => false, 'message' => 'Method is not allowed.']);
