<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';

auth_start_session();
require_authenticated_user();

$job_card_last_error = '';

function job_card_pick(array $payload, array $details, array $keys, $default = '')
{
    foreach ($keys as $key) {
        foreach ([$payload, $details] as $source) {
            if (array_key_exists($key, $source) && $source[$key] !== null && $source[$key] !== '') {
                return $source[$key];
            }
        }
    }
    return $default;
}

function job_card_array_pick(array $payload, array $details, $key)
{
    if (isset($payload[$key]) && is_array($payload[$key])) {
        return $payload[$key];
    }
    return isset($details[$key]) && is_array($details[$key]) ? $details[$key] : [];
}

function job_card_number($value, $default = 0)
{
    if ($value === '' || $value === null || !is_numeric($value)) {
        return $default;
    }
    return max(0, (float) $value);
}

function job_card_date($value)
{
    $value = trim((string) $value);
    return $value === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
}

function job_card_time($value)
{
    $value = trim((string) $value);
    return $value === '' || preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value) ? $value : null;
}

function job_card_child_complaints(array $items)
{
    $result = [];
    foreach ($items as $item) {
        $name = is_array($item) ? trim((string) ($item['name'] ?? $item['text'] ?? '')) : trim((string) $item);
        if ($name === '') {
            continue;
        }
        $result[] = ['name' => $name, 'price' => job_card_number(is_array($item) ? ($item['price'] ?? 0) : 0)];
    }
    return $result;
}

function job_card_child_damages(array $items)
{
    $result = [];
    foreach ($items as $item) {
        $value = is_array($item) ? ($item['description'] ?? $item['name'] ?? '') : $item;
        $value = trim((string) $value);
        if ($value !== '') {
            $result[] = $value;
        }
    }
    return $result;
}

function job_card_child_labour(array $items)
{
    $result = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $description = trim((string) ($item['description'] ?? $item['desc'] ?? ''));
        if ($description === '') {
            continue;
        }
        $result[] = [
            'code' => trim((string) ($item['code'] ?? $item['labourCode'] ?? '')),
            'description' => $description,
            'category' => trim((string) ($item['category'] ?? $item['cat'] ?? '')),
            'quantity' => job_card_number($item['quantity'] ?? $item['qty'] ?? 1, 1),
            'rate' => job_card_number($item['rate'] ?? 0),
            'charge' => job_card_number($item['charge'] ?? 0),
        ];
    }
    return $result;
}

function job_card_child_parts(array $items)
{
    $result = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $description = trim((string) ($item['description'] ?? $item['desc'] ?? ''));
        if ($description === '') {
            continue;
        }
        $result[] = [
            'partNumber' => trim((string) ($item['partNumber'] ?? $item['part'] ?? '')),
            'description' => $description,
            'issueMode' => trim((string) ($item['issueMode'] ?? $item['issue'] ?? '')),
            'quantity' => job_card_number($item['quantity'] ?? $item['qty'] ?? 1, 1),
            'unitPrice' => job_card_number($item['unitPrice'] ?? $item['unit'] ?? 0),
            'mrp' => job_card_number($item['mrp'] ?? 0),
            'total' => job_card_number($item['total'] ?? (($item['qty'] ?? 0) * ($item['unit'] ?? 0))),
        ];
    }
    return $result;
}

function job_card_child_checks(array $items)
{
    $result = [];
    foreach ($items as $item) {
        if (is_string($item)) {
            $item = ['name' => $item, 'checked' => false];
        }
        if (!is_array($item)) {
            continue;
        }
        $name = trim((string) ($item['name'] ?? $item['checkName'] ?? ''));
        if ($name !== '') {
            $result[] = ['name' => $name, 'checked' => !empty($item['checked'])];
        }
    }
    return $result;
}

function job_card_normalize(array $payload, $import = false)
{
    $details = isset($payload['details']) && is_array($payload['details']) ? $payload['details'] : [];
    $complaints = job_card_child_complaints(job_card_array_pick($payload, $details, 'complaints'));
    $damages = job_card_child_damages(job_card_array_pick($payload, $details, 'damages'));
    $labour = job_card_child_labour(job_card_array_pick($payload, $details, 'labour'));
    $parts = job_card_child_parts(job_card_array_pick($payload, $details, 'parts'));
    $checks = job_card_child_checks(job_card_array_pick($payload, $details, 'checks'));
    $status = trim((string) job_card_pick($payload, $details, ['status'], 'Job Created'));
    $validStatuses = ['Job Created', 'Allocated', 'Job Completed', 'Closed', 'Cancelled'];
    $cancelledFromStatus = trim((string) job_card_pick($payload, $details, ['cancelledFromStatus'], ''));
    if ($status === 'Cancelled' && !in_array($cancelledFromStatus, $validStatuses, true)) {
        $cancelledFromStatus = 'Job Created';
    }
    if ($status !== 'Cancelled') {
        $cancelledFromStatus = '';
    }

    $jobCardNo = trim((string) job_card_pick($payload, $details, ['jobCardNo', 'id'], ''));
    if ($jobCardNo === '') {
        $jobCardNo = 'JC-' . date('YmdHis') . random_int(100, 999);
    }
    $registrationNumber = trim((string) job_card_pick($payload, $details, ['registrationNumber', 'regNo'], ''));
    $vehicleModel = trim((string) job_card_pick($payload, $details, ['vehicleModel', 'model'], ''));
    $customerName = trim((string) job_card_pick($payload, $details, ['customerName', 'customer'], ''));
    $phoneNumber = trim((string) job_card_pick($payload, $details, ['phoneNumber', 'mobileNumber', 'phone'], ''));
    $customerAddress = trim((string) job_card_pick($payload, $details, ['customerAddress', 'address'], ''));
    $jobType = trim((string) job_card_pick($payload, $details, ['jobType'], ''));
    $jobDate = job_card_date(job_card_pick($payload, $details, ['jobCardDate', 'date', 'receivedDate'], ''));
    $receivedDate = job_card_date(job_card_pick($payload, $details, ['receivedDate', 'date'], ''));
    $takingDate = job_card_date(job_card_pick($payload, $details, ['takingDate'], ''));
    $deliveryDate = job_card_date(job_card_pick($payload, $details, ['deliveryDate'], ''));
    $receivedTime = job_card_time(job_card_pick($payload, $details, ['receivedTime'], ''));
    $takingTime = job_card_time(job_card_pick($payload, $details, ['takingTime'], ''));
    $deliveryTime = job_card_time(job_card_pick($payload, $details, ['deliveryTime'], ''));
    $purchaseDate = job_card_date(job_card_pick($payload, $details, ['purchaseDate'], ''));

    if ($jobCardNo === '' || strlen($jobCardNo) > 30 || $registrationNumber === '' || strlen($registrationNumber) > 30 ||
        $vehicleModel === '' || strlen($vehicleModel) > 120 || $customerName === '' || strlen($customerName) > 120 ||
        (!$import && $phoneNumber === '') || strlen($phoneNumber) > 20 || strlen($customerAddress) > 255 ||
        $jobType === '' || strlen($jobType) > 60 || $jobDate === null || $receivedDate === null || $takingDate === null ||
        $deliveryDate === null || $receivedTime === null || $takingTime === null || $deliveryTime === null ||
        $purchaseDate === null || !in_array($status, $validStatuses, true)) {
        return [false, null, 'Job-card fields are invalid.'];
    }

    $labourTotal = job_card_number(job_card_pick($payload, $details, ['labourTotal'], 0));
    $partsTotal = job_card_number(job_card_pick($payload, $details, ['partsTotal'], 0));
    $complaintTotal = array_reduce($complaints, function ($total, $item) {
        return $total + $item['price'];
    }, 0.0);

    return [true, [
        'jobCardNo' => $jobCardNo,
        'frameNo' => trim((string) job_card_pick($payload, $details, ['frameNo', 'frameNumber'], '')),
        'registrationNumber' => $registrationNumber,
        'vehicleModel' => $vehicleModel,
        'customerName' => $customerName,
        'phoneNumber' => $phoneNumber,
        'customerAddress' => $customerAddress,
        'purchaseDate' => $purchaseDate,
        'jobType' => $jobType,
        'kmsRun' => (int) job_card_number(job_card_pick($payload, $details, ['kmsRun'], 0)),
        'serviceMode' => trim((string) job_card_pick($payload, $details, ['serviceMode'], '')),
        'receivedDate' => $receivedDate,
        'receivedTime' => $receivedTime,
        'takingDate' => $takingDate,
        'takingTime' => $takingTime,
        'deliveryDate' => $deliveryDate,
        'deliveryTime' => $deliveryTime,
        'serviceAdvisor' => trim((string) job_card_pick($payload, $details, ['serviceAdvisor'], '')),
        'mechanic' => trim((string) job_card_pick($payload, $details, ['mechanic', 'vehicleAllocation'], '')),
        'bay' => trim((string) job_card_pick($payload, $details, ['bay'], '')),
        'supervisor' => trim((string) job_card_pick($payload, $details, ['supervisor'], '')),
        'waterWash' => trim((string) job_card_pick($payload, $details, ['waterWash'], '')),
        'vehiclePicked' => trim((string) job_card_pick($payload, $details, ['vehiclePicked'], '')),
        'status' => $status,
        'cancelledFromStatus' => $cancelledFromStatus,
        'complaintTotal' => $complaintTotal,
        'labourTotal' => $labourTotal,
        'partsTotal' => $partsTotal,
        'gstTotal' => job_card_number(job_card_pick($payload, $details, ['gstTotal'], 0)),
        'grandTotal' => job_card_number(job_card_pick($payload, $details, ['grandTotal'], 0)),
        'paymentMode' => trim((string) job_card_pick($payload, $details, ['paymentMode'], '')),
        'amountReceived' => job_card_number(job_card_pick($payload, $details, ['amountReceived'], 0)),
        'paymentReference' => trim((string) job_card_pick($payload, $details, ['paymentReference'], '')),
        'receivedBy' => trim((string) job_card_pick($payload, $details, ['receivedBy'], '')),
        'dealerCode' => trim((string) job_card_pick($payload, $details, ['dealerCode'], '')),
        'dealerName' => trim((string) job_card_pick($payload, $details, ['dealerName'], '')),
        'customerId' => (int) job_card_pick($payload, $details, ['customerId'], 0),
        'vehicleId' => (int) job_card_pick($payload, $details, ['vehicleId'], 0),
        'customerLegacyKey' => trim((string) job_card_pick($payload, $details, ['customerLegacyKey'], '')),
        'complaints' => $complaints,
        'damages' => $damages,
        'labour' => $labour,
        'parts' => $parts,
        'checks' => $checks,
    ], null];
}

function job_card_execute_typed($connection, $sql, array $typedValues)
{
    global $job_card_last_error;
    $statement = mysqli_prepare($connection, $sql);
    if ($statement === false) {
        $job_card_last_error = mysqli_error($connection);
        return [false, 0];
    }
    $types = '';
    $values = [];
    foreach ($typedValues as $typedValue) {
        $types .= $typedValue[0];
        $values[] = $typedValue[1];
    }
    $references = [$statement, $types];
    foreach ($values as $index => &$value) {
        $references[] =& $value;
    }
    if (count($values) !== substr_count($sql, '?')) {
        $job_card_last_error = 'Parameter count mismatch: ' . count($values) . ' values for ' . substr_count($sql, '?') . ' placeholders.';
        mysqli_stmt_close($statement);
        return [false, 0];
    }
    $bound = call_user_func_array('mysqli_stmt_bind_param', $references);
    $executed = $bound && mysqli_stmt_execute($statement);
    if (!$executed) {
        $job_card_last_error = mysqli_stmt_error($statement);
        error_log('AutoLine job-card statement failed: ' . $job_card_last_error);
    }
    $insertId = mysqli_stmt_insert_id($statement);
    mysqli_stmt_close($statement);
    return [$executed, $insertId];
}

function job_card_find_customer($connection, array $jobCard)
{
    if ($jobCard['customerId'] > 0) {
        $statement = mysqli_prepare($connection, 'SELECT id FROM customers WHERE id = ? LIMIT 1');
        mysqli_stmt_bind_param($statement, 'i', $jobCard['customerId']);
        mysqli_stmt_execute($statement);
        mysqli_stmt_bind_result($statement, $id);
        $found = mysqli_stmt_fetch($statement) === true;
        mysqli_stmt_close($statement);
        if ($found) {
            return (int) $id;
        }
    }

    if ($jobCard['customerLegacyKey'] !== '') {
        $statement = mysqli_prepare($connection, 'SELECT id FROM customers WHERE legacy_key = ? LIMIT 1');
        mysqli_stmt_bind_param($statement, 's', $jobCard['customerLegacyKey']);
        mysqli_stmt_execute($statement);
        mysqli_stmt_bind_result($statement, $id);
        $found = mysqli_stmt_fetch($statement) === true;
        mysqli_stmt_close($statement);
        if ($found) {
            return (int) $id;
        }
    }

    $statement = mysqli_prepare($connection, 'SELECT id FROM customers WHERE name = ? AND phone <=> NULLIF(?, \'\') AND address <=> NULLIF(?, \'\') ORDER BY id ASC LIMIT 1');
    mysqli_stmt_bind_param($statement, 'sss', $jobCard['customerName'], $jobCard['phoneNumber'], $jobCard['customerAddress']);
    mysqli_stmt_execute($statement);
    mysqli_stmt_bind_result($statement, $id);
    $found = mysqli_stmt_fetch($statement) === true;
    mysqli_stmt_close($statement);
    if ($found) {
        $customerId = (int) $id;
        [$saved] = job_card_execute_typed($connection, 'UPDATE customers SET name = ?, phone = NULLIF(?, \'\'), address = NULLIF(?, \'\') WHERE id = ?', [
            ['s', $jobCard['customerName']], ['s', $jobCard['phoneNumber']], ['s', $jobCard['customerAddress']], ['i', $customerId]
        ]);
        return $saved ? $customerId : false;
    }

    [$saved, $newId] = job_card_execute_typed($connection, 'INSERT INTO customers (legacy_key, name, phone, address) VALUES (NULLIF(?, \'\'), ?, NULLIF(?, \'\'), NULLIF(?, \'\'))', [
        ['s', $jobCard['customerLegacyKey']], ['s', $jobCard['customerName']], ['s', $jobCard['phoneNumber']], ['s', $jobCard['customerAddress']]
    ]);
    return $saved ? (int) $newId : false;
}

function job_card_resolve_relationships($connection, array $jobCard)
{
    $vehicleId = 0;
    $customerId = 0;
    $statement = mysqli_prepare($connection, 'SELECT id, customer_id FROM vehicles WHERE registration_number = ? LIMIT 1');
    mysqli_stmt_bind_param($statement, 's', $jobCard['registrationNumber']);
    mysqli_stmt_execute($statement);
    mysqli_stmt_bind_result($statement, $existingVehicleId, $existingCustomerId);
    $vehicleFound = mysqli_stmt_fetch($statement) === true;
    mysqli_stmt_close($statement);

    if ($jobCard['vehicleId'] > 0 && !$vehicleFound) {
        $statement = mysqli_prepare($connection, 'SELECT id, customer_id, registration_number FROM vehicles WHERE id = ? LIMIT 1');
        mysqli_stmt_bind_param($statement, 'i', $jobCard['vehicleId']);
        mysqli_stmt_execute($statement);
        mysqli_stmt_bind_result($statement, $existingVehicleId, $existingCustomerId, $existingRegistration);
        $vehicleFound = mysqli_stmt_fetch($statement) === true;
        mysqli_stmt_close($statement);
        if ($vehicleFound && $existingRegistration !== $jobCard['registrationNumber']) {
            return false;
        }
    }

    if ($vehicleFound) {
        $vehicleId = (int) $existingVehicleId;
        $customerId = (int) $existingCustomerId;
        [$saved] = job_card_execute_typed($connection, 'UPDATE customers SET name = ?, phone = NULLIF(?, \'\'), address = NULLIF(?, \'\') WHERE id = ?', [
            ['s', $jobCard['customerName']], ['s', $jobCard['phoneNumber']], ['s', $jobCard['customerAddress']], ['i', $customerId]
        ]);
        if (!$saved) {
            return false;
        }
        [$saved] = job_card_execute_typed($connection, 'UPDATE vehicles SET vehicle_model = ?, customer_id = ?, purchase_date = NULLIF(?, \'\'), dealer_code = NULLIF(?, \'\'), dealer_name = NULLIF(?, \'\') WHERE id = ?', [
            ['s', $jobCard['vehicleModel']], ['i', $customerId], ['s', $jobCard['purchaseDate'] ?? ''], ['s', $jobCard['dealerCode']], ['s', $jobCard['dealerName']], ['i', $vehicleId]
        ]);
        return $saved ? [$customerId, $vehicleId] : false;
    }

    $customerId = job_card_find_customer($connection, $jobCard);
    if ($customerId === false) {
        return false;
    }
    [$saved, $vehicleId] = job_card_execute_typed($connection, 'INSERT INTO vehicles (registration_number, vehicle_model, customer_id, purchase_date, dealer_code, dealer_name) VALUES (?, ?, ?, NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'))', [
        ['s', $jobCard['registrationNumber']], ['s', $jobCard['vehicleModel']], ['i', $customerId], ['s', $jobCard['purchaseDate'] ?? ''], ['s', $jobCard['dealerCode']], ['s', $jobCard['dealerName']]
    ]);
    return $saved ? [$customerId, (int) $vehicleId] : false;
}

function job_card_parent_values(array $jobCard, $customerId, $vehicleId)
{
    return [
        ['s', $jobCard['jobCardNo']], ['s', $jobCard['receivedDate']], ['s', $jobCard['frameNo']],
        ['s', $jobCard['registrationNumber']], ['s', $jobCard['vehicleModel']], ['s', $jobCard['customerName']],
        ['i', $customerId], ['s', $jobCard['phoneNumber']], ['s', $jobCard['customerAddress']], ['s', $jobCard['purchaseDate'] ?? ''],
        ['s', $jobCard['jobType']], ['i', $jobCard['kmsRun']], ['s', $jobCard['serviceMode']], ['s', $jobCard['receivedDate']],
        ['s', $jobCard['receivedTime']], ['s', $jobCard['takingDate']], ['s', $jobCard['takingTime']], ['s', $jobCard['deliveryDate']],
        ['s', $jobCard['deliveryTime']], ['s', $jobCard['serviceAdvisor']], ['s', $jobCard['mechanic']], ['s', $jobCard['bay']],
        ['s', $jobCard['supervisor']], ['s', $jobCard['waterWash']], ['s', $jobCard['vehiclePicked']], ['s', $jobCard['status']],
        ['s', $jobCard['cancelledFromStatus']], ['d', $jobCard['complaintTotal']], ['d', $jobCard['labourTotal']], ['d', $jobCard['partsTotal']],
        ['d', $jobCard['gstTotal']], ['d', $jobCard['grandTotal']], ['s', $jobCard['paymentMode']], ['d', $jobCard['amountReceived']],
        ['s', $jobCard['paymentReference']], ['s', $jobCard['receivedBy']], ['i', $vehicleId]
    ];
}

function job_card_save($connection, array $jobCard)
{
    $relationships = job_card_resolve_relationships($connection, $jobCard);
    if ($relationships === false) {
        return false;
    }
    [$customerId, $vehicleId] = $relationships;

    $statement = mysqli_prepare($connection, 'SELECT id FROM job_cards WHERE job_card_no = ? LIMIT 1');
    mysqli_stmt_bind_param($statement, 's', $jobCard['jobCardNo']);
    mysqli_stmt_execute($statement);
    mysqli_stmt_bind_result($statement, $existingId);
    $exists = mysqli_stmt_fetch($statement) === true;
    mysqli_stmt_close($statement);

    if ($exists) {
        $sql = 'UPDATE job_cards SET job_card_date = NULLIF(?, \'\'), frame_number = NULLIF(?, \'\'), registration_number = ?, vehicle_model = ?, customer_name = ?, customer_id = ?, phone_number = ?, customer_address = NULLIF(?, \'\'), purchase_date = NULLIF(?, \'\'), job_type = ?, kms_run = NULLIF(?, 0), service_mode = NULLIF(?, \'\'), received_date = NULLIF(?, \'\'), received_time = NULLIF(?, \'\'), taking_date = NULLIF(?, \'\'), taking_time = NULLIF(?, \'\'), delivery_date = NULLIF(?, \'\'), delivery_time = NULLIF(?, \'\'), service_advisor = NULLIF(?, \'\'), mechanic_name = NULLIF(?, \'\'), bay = NULLIF(?, \'\'), supervisor = NULLIF(?, \'\'), water_wash = NULLIF(?, \'\'), vehicle_picked = NULLIF(?, \'\'), status = ?, cancelled_from_status = NULLIF(?, \'\'), complaint_total = ?, labour_total = ?, parts_total = ?, gst_total = ?, grand_total = ?, payment_mode = NULLIF(?, \'\'), amount_received = ?, payment_reference = NULLIF(?, \'\'), received_by = NULLIF(?, \'\'), vehicle_id = ? WHERE id = ?';
        $values = job_card_parent_values($jobCard, $customerId, $vehicleId);
        array_shift($values);
        $values[] = ['i', $existingId];
        [$saved] = job_card_execute_typed($connection, $sql, $values);
        $jobCardId = (int) $existingId;
    } else {
        $sql = 'INSERT INTO job_cards (job_card_no, job_card_date, frame_number, registration_number, vehicle_model, customer_name, customer_id, phone_number, customer_address, purchase_date, job_type, kms_run, service_mode, received_date, received_time, taking_date, taking_time, delivery_date, delivery_time, service_advisor, mechanic_name, bay, supervisor, water_wash, vehicle_picked, status, cancelled_from_status, complaint_total, labour_total, parts_total, gst_total, grand_total, payment_mode, amount_received, payment_reference, received_by, vehicle_id) VALUES (?, NULLIF(?, \'\'), NULLIF(?, \'\'), ?, ?, ?, ?, ?, NULLIF(?, \'\'), NULLIF(?, \'\'), ?, NULLIF(?, 0), NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), ?, NULLIF(?, \'\'), ?, ?, ?, ?, ?, NULLIF(?, \'\'), ?, NULLIF(?, \'\'), NULLIF(?, \'\'), ?)';
        [$saved, $jobCardId] = job_card_execute_typed($connection, $sql, job_card_parent_values($jobCard, $customerId, $vehicleId));
        $jobCardId = (int) $jobCardId;
    }

    if (!$saved || $jobCardId < 1) {
        return false;
    }

    foreach ([
        'job_card_complaints' => 'DELETE FROM job_card_complaints WHERE job_card_id = ?',
        'job_card_damages' => 'DELETE FROM job_card_damages WHERE job_card_id = ?',
        'job_card_labour' => 'DELETE FROM job_card_labour WHERE job_card_id = ?',
        'job_card_parts' => 'DELETE FROM job_card_parts WHERE job_card_id = ?',
        'job_card_checks' => 'DELETE FROM job_card_checks WHERE job_card_id = ?',
    ] as $deleteSql) {
        [$deleted] = job_card_execute_typed($connection, $deleteSql, [['i', $jobCardId]]);
        if (!$deleted) {
            return false;
        }
    }

    foreach ($jobCard['complaints'] as $item) {
        [$saved] = job_card_execute_typed($connection, 'INSERT INTO job_card_complaints (job_card_id, complaint_name, price) VALUES (?, ?, ?)', [['i', $jobCardId], ['s', $item['name']], ['d', $item['price']]]);
        if (!$saved) return false;
    }
    foreach ($jobCard['damages'] as $item) {
        [$saved] = job_card_execute_typed($connection, 'INSERT INTO job_card_damages (job_card_id, damage_description) VALUES (?, ?)', [['i', $jobCardId], ['s', $item]]);
        if (!$saved) return false;
    }
    foreach ($jobCard['labour'] as $item) {
        [$saved] = job_card_execute_typed($connection, 'INSERT INTO job_card_labour (job_card_id, labour_code, description, category, quantity, rate, charge) VALUES (?, NULLIF(?, \'\'), ?, NULLIF(?, \'\'), ?, ?, ?)', [['i', $jobCardId], ['s', $item['code']], ['s', $item['description']], ['s', $item['category']], ['d', $item['quantity']], ['d', $item['rate']], ['d', $item['charge']]]);
        if (!$saved) return false;
    }
    foreach ($jobCard['parts'] as $item) {
        [$saved] = job_card_execute_typed($connection, 'INSERT INTO job_card_parts (job_card_id, part_number, description, issue_mode, quantity, unit_price, mrp, total) VALUES (?, NULLIF(?, \'\'), ?, NULLIF(?, \'\'), ?, ?, ?, ?)', [['i', $jobCardId], ['s', $item['partNumber']], ['s', $item['description']], ['s', $item['issueMode']], ['d', $item['quantity']], ['d', $item['unitPrice']], ['d', $item['mrp']], ['d', $item['total']]]);
        if (!$saved) return false;
    }
    foreach ($jobCard['checks'] as $item) {
        [$saved] = job_card_execute_typed($connection, 'INSERT INTO job_card_checks (job_card_id, check_name, is_checked) VALUES (?, ?, ?)', [['i', $jobCardId], ['s', $item['name']], ['i', $item['checked'] ? 1 : 0]]);
        if (!$saved) return false;
    }
    return $jobCardId;
}

function job_card_fetch_children($connection, $jobCardId)
{
    $children = ['complaints' => [], 'damages' => [], 'labour' => [], 'parts' => [], 'checks' => []];
    $queries = [
        'complaints' => 'SELECT complaint_name AS name, price FROM job_card_complaints WHERE job_card_id = ? ORDER BY id ASC',
        'damages' => 'SELECT damage_description AS description FROM job_card_damages WHERE job_card_id = ? ORDER BY id ASC',
        'labour' => 'SELECT labour_code AS code, description, category, quantity, rate, charge FROM job_card_labour WHERE job_card_id = ? ORDER BY id ASC',
        'parts' => 'SELECT part_number AS partNumber, description, issue_mode AS issueMode, quantity, unit_price AS unitPrice, mrp, total FROM job_card_parts WHERE job_card_id = ? ORDER BY id ASC',
        'checks' => 'SELECT check_name AS name, is_checked AS checked FROM job_card_checks WHERE job_card_id = ? ORDER BY id ASC',
    ];
    foreach ($queries as $key => $sql) {
        $statement = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($statement, 'i', $jobCardId);
        mysqli_stmt_execute($statement);
        $result = mysqli_stmt_get_result($statement);
        while ($row = mysqli_fetch_assoc($result)) {
            if ($key === 'damages') {
                $children[$key][] = $row['description'];
            } else {
                if (isset($row['checked'])) $row['checked'] = (bool) $row['checked'];
                if (isset($row['price'])) $row['price'] = (float) $row['price'];
                foreach (['quantity', 'rate', 'charge', 'unitPrice', 'mrp', 'total'] as $number) {
                    if (isset($row[$number])) $row[$number] = (float) $row[$number];
                }
                $children[$key][] = $row;
            }
        }
        mysqli_free_result($result);
        mysqli_stmt_close($statement);
    }
    return $children;
}

function job_card_row($connection, array $row)
{
    $children = job_card_fetch_children($connection, (int) $row['db_id']);
    $result = [
        'dbId' => (int) $row['db_id'],
        'customerId' => (int) ($row['customer_id'] ?? 0),
        'vehicleId' => (int) ($row['vehicle_id'] ?? 0),
        'id' => (string) $row['job_card_no'],
        'jobCardNo' => (string) $row['job_card_no'],
        'frameNo' => (string) ($row['frame_number'] ?? ''),
        'regNo' => (string) $row['registration_number'],
        'registrationNumber' => (string) $row['registration_number'],
        'customer' => (string) $row['customer_name'],
        'customerName' => (string) $row['customer_name'],
        'phoneNumber' => (string) ($row['phone_number'] ?? ''),
        'customerAddress' => (string) ($row['customer_address'] ?? ''),
        'model' => (string) $row['vehicle_model'],
        'vehicleModel' => (string) $row['vehicle_model'],
        'purchaseDate' => (string) ($row['purchase_date'] ?? ''),
        'jobType' => (string) $row['job_type'],
        'status' => (string) $row['status'],
        'cancelledFromStatus' => (string) ($row['cancelled_from_status'] ?? ''),
        'date' => (string) ($row['job_card_date'] ?? ''),
        'mechanic' => (string) ($row['mechanic_name'] ?? ''),
        'vehicleAllocation' => (string) ($row['mechanic_name'] ?? ''),
        'serviceAdvisor' => (string) ($row['service_advisor'] ?? ''),
        'dealerCode' => (string) ($row['dealer_code'] ?? ''),
        'dealerName' => (string) ($row['dealer_name'] ?? ''),
        'labourTotal' => (float) $row['labour_total'],
        'partsTotal' => (float) $row['parts_total'],
        'complaintTotal' => (float) $row['complaint_total'],
        'details' => [
            'registrationNumber' => (string) $row['registration_number'],
            'vehicleModel' => (string) $row['vehicle_model'],
            'customerName' => (string) $row['customer_name'],
            'phoneNumber' => (string) ($row['phone_number'] ?? ''),
            'customerAddress' => (string) ($row['customer_address'] ?? ''),
            'purchaseDate' => (string) ($row['purchase_date'] ?? ''),
            'jobType' => (string) $row['job_type'],
            'receivedDate' => (string) ($row['received_date'] ?? ''),
            'receivedTime' => (string) ($row['received_time'] ?? ''),
            'takingDate' => (string) ($row['taking_date'] ?? ''),
            'takingTime' => (string) ($row['taking_time'] ?? ''),
            'deliveryDate' => (string) ($row['delivery_date'] ?? ''),
            'deliveryTime' => (string) ($row['delivery_time'] ?? ''),
            'vehicleAllocation' => (string) ($row['mechanic_name'] ?? ''),
            'serviceAdvisor' => (string) ($row['service_advisor'] ?? ''),
            'vehiclePicked' => (string) ($row['vehicle_picked'] ?? ''),
            'dealerCode' => (string) ($row['dealer_code'] ?? ''),
            'dealerName' => (string) ($row['dealer_name'] ?? ''),
            'complaints' => $children['complaints'],
            'damages' => $children['damages'],
            'labour' => $children['labour'],
            'parts' => $children['parts'],
            'checks' => $children['checks'],
        ],
    ];
    return $result;
}

function job_card_fetch_one($connection, $where, $type, $value)
{
    $sql = "SELECT jc.id AS db_id, jc.job_card_no, jc.job_card_date, jc.frame_number, jc.registration_number, jc.vehicle_id, jc.vehicle_model, jc.customer_name, jc.customer_id, jc.phone_number, jc.customer_address, jc.purchase_date, jc.job_type, jc.received_date, TIME_FORMAT(jc.received_time, '%H:%i') AS received_time, jc.taking_date, TIME_FORMAT(jc.taking_time, '%H:%i') AS taking_time, jc.delivery_date, TIME_FORMAT(jc.delivery_time, '%H:%i') AS delivery_time, jc.service_advisor, jc.mechanic_name, jc.vehicle_picked, jc.status, jc.cancelled_from_status, jc.complaint_total, jc.labour_total, jc.parts_total, v.dealer_code, v.dealer_name FROM job_cards jc LEFT JOIN vehicles v ON v.id = jc.vehicle_id WHERE {$where} LIMIT 1";
    $statement = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($statement, $type, $value);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);
    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    mysqli_stmt_close($statement);
    return $row ? job_card_row($connection, $row) : null;
}

$method = $_SERVER['REQUEST_METHOD'];
$payload = auth_request_payload();
$connection = autoline_db_connect();
if ($connection === false) {
    auth_json_response(503, ['success' => false, 'message' => 'Job-card database is unavailable.']);
}

if ($method === 'GET') {
    $requestedId = trim((string) ($_GET['id'] ?? ''));
    if ($requestedId !== '') {
        $item = ctype_digit($requestedId)
            ? job_card_fetch_one($connection, 'jc.id = ?', 'i', (int) $requestedId)
            : job_card_fetch_one($connection, 'jc.job_card_no = ?', 's', $requestedId);
        mysqli_close($connection);
        if ($item === null) auth_json_response(404, ['success' => false, 'message' => 'Job card was not found.']);
        auth_json_response(200, ['success' => true, 'item' => $item]);
    }

    $query = trim((string) ($_GET['q'] ?? ''));
    $sql = 'SELECT id FROM job_cards';
    if ($query !== '') {
        $sql .= ' WHERE job_card_no LIKE ? OR registration_number LIKE ? OR customer_name LIKE ? OR phone_number LIKE ? OR vehicle_model LIKE ?';
    }
    $sql .= ' ORDER BY created_at DESC, id DESC';
    $statement = mysqli_prepare($connection, $sql);
    if ($query !== '') {
        $like = '%' . $query . '%';
        mysqli_stmt_bind_param($statement, 'sssss', $like, $like, $like, $like, $like);
    }
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $item = job_card_fetch_one($connection, 'jc.id = ?', 'i', (int) $row['id']);
        if ($item !== null) $items[] = $item;
    }
    mysqli_free_result($result);
    mysqli_stmt_close($statement);
    mysqli_close($connection);
    auth_json_response(200, ['success' => true, 'items' => $items]);
}

if ($method === 'PUT' && isset($payload['status'])) {
    $jobCardNo = trim((string) ($payload['jobCardNo'] ?? $payload['id'] ?? ''));
    $status = trim((string) $payload['status']);
    $allowed = ['Job Created', 'Allocated', 'Job Completed', 'Closed', 'Cancelled'];
    if ($jobCardNo === '' || !in_array($status, $allowed, true)) {
        mysqli_close($connection);
        auth_json_response(400, ['success' => false, 'message' => 'Job-card status update is invalid.']);
    }
    $current = job_card_fetch_one($connection, 'jc.job_card_no = ?', 's', $jobCardNo);
    if ($current === null) {
        mysqli_close($connection);
        auth_json_response(404, ['success' => false, 'message' => 'Job card was not found.']);
    }
    $cancelledFromStatus = '';
    if ($status === 'Cancelled') {
        $cancelledFromStatus = in_array($current['status'], $allowed, true) && $current['status'] !== 'Cancelled'
            ? $current['status']
            : ($payload['cancelledFromStatus'] ?? 'Job Created');
    } elseif ($current['status'] === 'Cancelled') {
        $status = in_array(($payload['cancelledFromStatus'] ?? ''), $allowed, true) ? $payload['cancelledFromStatus'] : 'Job Created';
    }
    [$saved] = job_card_execute_typed($connection, 'UPDATE job_cards SET status = ?, cancelled_from_status = NULLIF(?, \'\') WHERE job_card_no = ?', [['s', $status], ['s', $cancelledFromStatus], ['s', $jobCardNo]]);
    $item = $saved ? job_card_fetch_one($connection, 'jc.job_card_no = ?', 's', $jobCardNo) : null;
    mysqli_close($connection);
    if (!$saved || $item === null) auth_json_response(503, ['success' => false, 'message' => 'Job-card status could not be updated.']);
    auth_json_response(200, ['success' => true, 'item' => $item]);
}

if ($method === 'POST' && isset($payload['items']) && is_array($payload['items'])) {
    if (count($payload['items']) > 500) {
        mysqli_close($connection);
        auth_json_response(400, ['success' => false, 'message' => 'Too many job cards in one import.']);
    }
    mysqli_begin_transaction($connection);
    $savedItems = [];
    foreach ($payload['items'] as $rawItem) {
        if (!is_array($rawItem)) {
            mysqli_rollback($connection);
            mysqli_close($connection);
            auth_json_response(400, ['success' => false, 'message' => 'Job-card import data is invalid.']);
        }
        [$valid, $jobCard, $error] = job_card_normalize($rawItem, true);
        if (!$valid) {
            mysqli_rollback($connection);
            mysqli_close($connection);
            auth_json_response(400, ['success' => false, 'message' => $error]);
        }
        $id = job_card_save($connection, $jobCard);
        if ($id === false) {
            mysqli_rollback($connection);
            mysqli_close($connection);
            auth_json_response(409, ['success' => false, 'message' => 'Job-card import could not be completed.']);
        }
        $savedItems[] = (int) $id;
    }
    mysqli_commit($connection);
    $items = [];
    foreach ($savedItems as $id) {
        $items[] = job_card_fetch_one($connection, 'jc.id = ?', 'i', $id);
    }
    mysqli_close($connection);
    auth_json_response(201, ['success' => true, 'items' => $items, 'imported' => count($items)]);
}

if ($method === 'POST') {
    [$valid, $jobCard, $error] = job_card_normalize($payload);
    if (!$valid) {
        mysqli_close($connection);
        auth_json_response(400, ['success' => false, 'message' => $error]);
    }
    mysqli_begin_transaction($connection);
    $id = job_card_save($connection, $jobCard);
    if ($id === false) {
        mysqli_rollback($connection);
        mysqli_close($connection);
        auth_json_response(409, ['success' => false, 'message' => 'Job card could not be saved.']);
    }
    mysqli_commit($connection);
    $item = job_card_fetch_one($connection, 'jc.id = ?', 'i', $id);
    mysqli_close($connection);
    if ($item === null) auth_json_response(503, ['success' => false, 'message' => 'Job card was saved but could not be loaded.']);
    auth_json_response(201, ['success' => true, 'item' => $item]);
}

mysqli_close($connection);
auth_json_response(405, ['success' => false, 'message' => 'Method is not allowed.']);
