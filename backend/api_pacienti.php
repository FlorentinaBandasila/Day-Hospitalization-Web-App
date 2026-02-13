<?php

/**
 * Patients API Endpoint
 * Handles CRUD operations for patients (pacienti)
 * Simplified - only basic patient info
 */

require_once 'config.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    setJsonHeaders();
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = db();

    switch ($method) {
        case 'GET':
            handleGetPatients($pdo);
            break;

        case 'POST':
            handleCreatePatient($pdo);
            break;

        case 'PUT':
            handleUpdatePatient($pdo);
            break;

        case 'DELETE':
            handleDeletePatient($pdo);
            break;

        default:
            sendError('Method not allowed', 405);
    }
} catch (PDOException $e) {
    sendError('Database error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    sendError($e->getMessage(), 400);
}

/**
 * GET - Retrieve patients
 */
function handleGetPatients($pdo)
{
    $cnp = $_GET['cnp'] ?? null;

    if ($cnp) {
        // Get specific patient
        $stmt = $pdo->prepare("SELECT * FROM pacienti WHERE cnp = ?");
        $stmt->execute([$cnp]);
        $patient = $stmt->fetch();

        if (!$patient) {
            sendError('Patient not found', 404);
        }

        sendJsonResponse($patient);
    } else {
        // Get all patients
        $search = $_GET['search'] ?? '';
        $sql = "SELECT cnp, nume, prenume, sex, data_nasterii, varsta, 
                       DATE_FORMAT(data_adaugarii, '%d.%m.%Y') as data_adaugarii_formatted,
                       data_adaugarii
                FROM pacienti";
        if ($search) {
            $sql .= " WHERE nume LIKE ? OR prenume LIKE ? OR cnp LIKE ?";
            $searchParam = "%{$search}%";
            $stmt = $pdo->prepare($sql . " ORDER BY data_adaugarii DESC");
            $stmt->execute([$searchParam, $searchParam, $searchParam]);
        } else {
            $stmt = $pdo->prepare($sql . " ORDER BY data_adaugarii DESC");
            $stmt->execute();
        }
        $patients = $stmt->fetchAll();
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM pacienti";
        if ($search) {
            $countSql .= " WHERE nume LIKE ? OR prenume LIKE ? OR cnp LIKE ?";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute([$searchParam, $searchParam, $searchParam]);
        } else {
            $countStmt = $pdo->query($countSql);
        }
        $total = $countStmt->fetch()['total'];
        sendJsonResponse([
            'patients' => $patients,
            'total' => $total
        ]);
    }
}

/**
 * POST - Create new patient
 */
function handleCreatePatient($pdo)
{
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        sendError('Invalid JSON data');
    }

    // Validate required fields
    $required = ['cnp', 'nume', 'prenume', 'sex', 'data_nasterii'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            sendError("Missing required field: {$field}");
        }
    }

    // Validate CNP format (13 digits)
    if (!preg_match('/^[0-9]{13}$/', $data['cnp'])) {
        sendError('Invalid CNP format. Must be 13 digits.');
    }

    // Check if patient already exists
    $stmt = $pdo->prepare("SELECT cnp FROM pacienti WHERE cnp = ?");
    $stmt->execute([$data['cnp']]);
    if ($stmt->fetch()) {
        sendError('Patient with this CNP already exists', 409);
    }

    // Calculate age from CNP
    $varsta = calculateAgeFromCNP($data['cnp']);

    // Insert patient
    $sql = "INSERT INTO pacienti (cnp, nume, prenume, sex, data_nasterii, varsta) 
            VALUES (:cnp, :nume, :prenume, :sex, :data_nasterii, :varsta)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'cnp' => $data['cnp'],
        'nume' => $data['nume'],
        'prenume' => $data['prenume'],
        'sex' => $data['sex'],
        'data_nasterii' => $data['data_nasterii'],
        'varsta' => $varsta
    ]);

    // Get created patient
    $stmt = $pdo->prepare("SELECT * FROM pacienti WHERE cnp = ?");
    $stmt->execute([$data['cnp']]);
    $patient = $stmt->fetch();

    sendJsonResponse([
        'message' => 'Patient created successfully',
        'patient' => $patient
    ], 201);
}

/**
 * PUT - Update patient
 */
function handleUpdatePatient($pdo)
{
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || empty($data['cnp'])) {
        sendError('CNP is required');
    }

    // Check if patient exists
    $stmt = $pdo->prepare("SELECT cnp FROM pacienti WHERE cnp = ?");
    $stmt->execute([$data['cnp']]);
    if (!$stmt->fetch()) {
        sendError('Patient not found', 404);
    }

    // Build update query
    $allowedFields = ['nume', 'prenume', 'sex', 'data_nasterii'];
    $updates = [];
    $params = [];

    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updates[] = "{$field} = ?";
            $params[] = $data[$field];
        }
    }

    if (empty($updates)) {
        sendError('No fields to update');
    }

    // Recalculate age if data_nasterii changed
    if (isset($data['data_nasterii'])) {
        $varsta = calculateAgeFromCNP($data['cnp']);
        $updates[] = "varsta = ?";
        $params[] = $varsta;
    }

    $params[] = $data['cnp'];

    $sql = "UPDATE pacienti SET " . implode(', ', $updates) . " WHERE cnp = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Get updated patient
    $stmt = $pdo->prepare("SELECT * FROM pacienti WHERE cnp = ?");
    $stmt->execute([$data['cnp']]);
    $patient = $stmt->fetch();

    sendJsonResponse([
        'message' => 'Patient updated successfully',
        'patient' => $patient
    ]);
}

/**
 * DELETE - Delete patient
 */
function handleDeletePatient($pdo)
{
    $cnp = $_GET['cnp'] ?? null;

    if (!$cnp) {
        sendError('CNP is required');
    }

    // Check if patient exists
    $stmt = $pdo->prepare("SELECT cnp FROM pacienti WHERE cnp = ?");
    $stmt->execute([$cnp]);
    if (!$stmt->fetch()) {
        sendError('Patient not found', 404);
    }

    // Delete patient (cascade will delete related hospitalizations)
    $stmt = $pdo->prepare("DELETE FROM pacienti WHERE cnp = ?");
    $stmt->execute([$cnp]);

    sendJsonResponse([
        'message' => 'Patient deleted successfully',
        'cnp' => $cnp
    ]);
}

/**
 * Calculate age from CNP
 */
function calculateAgeFromCNP($cnp)
{
    if (strlen($cnp) !== 13) return null;

    $s = $cnp[0];
    $year = (int)substr($cnp, 1, 2);
    $month = (int)substr($cnp, 3, 2);
    $day = (int)substr($cnp, 5, 2);

    $century = 1900;
    if ($s === '1' || $s === '2') $century = 1900;
    elseif ($s === '3' || $s === '4') $century = 1800;
    elseif ($s === '5' || $s === '6' || $s === '7' || $s === '8') $century = 2000;

    $year = $century + $year;

    try {
        $birthDate = new DateTime("{$year}-{$month}-{$day}");
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;
        return $age;
    } catch (Exception $e) {
        return null;
    }
}
