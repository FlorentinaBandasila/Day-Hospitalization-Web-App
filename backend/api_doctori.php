<?php
/**
 * Doctors API Endpoint
 * Handles CRUD operations for doctors (doctori)
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
            handleGetDoctors($pdo);
            break;
            
        case 'POST':
            handleCreateDoctor($pdo);
            break;
            
        case 'PUT':
            handleUpdateDoctor($pdo);
            break;
            
        case 'DELETE':
            handleDeleteDoctor($pdo);
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
 * GET - Retrieve doctors
 * Query params: id or cnp (optional) - get specific doctor
 */
function handleGetDoctors($pdo) {
    $id = $_GET['id'] ?? null;
    $cnp = $_GET['cnp'] ?? null;
    
    if ($id || $cnp) {
        // Get specific doctor
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM doctori WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM doctori WHERE cnp = ?");
            $stmt->execute([$cnp]);
        }
        
        $doctor = $stmt->fetch();
        
        if (!$doctor) {
            sendError('Doctor not found', 404);
        }
        
        sendJsonResponse($doctor);
    } else {
        // Get all doctors
        $search = $_GET['search'] ?? '';
        $activ = $_GET['activ'] ?? null;
        
        $sql = "SELECT id, cnp, nume, prenume, specializari, email, telefon, activ, 
                       DATE_FORMAT(data_angajarii, '%d.%m.%Y') as data_angajarii_formatted,
                       data_angajarii
                FROM doctori";
        
        $conditions = [];
        $params = [];
        
        if ($search) {
            $conditions[] = "(nume LIKE ? OR prenume LIKE ? OR cnp LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if ($activ !== null) {
            $conditions[] = "activ = ?";
            $params[] = (bool)$activ;
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $sql .= " ORDER BY nume, prenume";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $doctors = $stmt->fetchAll();
        
        sendJsonResponse([
            'doctors' => $doctors,
            'total' => count($doctors)
        ]);
    }
}

/**
 * POST - Create new doctor
 * Body: JSON with doctor data
 */
function handleCreateDoctor($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        sendError('Invalid JSON data');
    }
    
    // Validate required fields
    $required = ['cnp', 'nume', 'prenume'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            sendError("Missing required field: {$field}");
        }
    }
    
    // Validate CNP format (13 digits)
    if (!preg_match('/^[0-9]{13}$/', $data['cnp'])) {
        sendError('Invalid CNP format. Must be 13 digits.');
    }
    
    // Check if doctor already exists
    $stmt = $pdo->prepare("SELECT cnp FROM doctori WHERE cnp = ?");
    $stmt->execute([$data['cnp']]);
    if ($stmt->fetch()) {
        sendError('Doctor with this CNP already exists', 409);
    }
    
    // Handle specializari (can be array or string)
    $specializari = $data['specializari'] ?? '';
    if (is_array($specializari)) {
        $specializari = implode(', ', $specializari);
    }
    
    // Insert doctor
    $sql = "INSERT INTO doctori (
        cnp, nume, prenume, specializari, email, telefon, activ, data_angajarii
    ) VALUES (
        :cnp, :nume, :prenume, :specializari, :email, :telefon, :activ, :data_angajarii
    )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'cnp' => $data['cnp'],
        'nume' => $data['nume'],
        'prenume' => $data['prenume'],
        'specializari' => $specializari,
        'email' => $data['email'] ?? null,
        'telefon' => $data['telefon'] ?? null,
        'activ' => $data['activ'] ?? true,
        'data_angajarii' => $data['data_angajarii'] ?? null
    ]);
    
    $doctorId = $pdo->lastInsertId();
    
    // Get created doctor
    $stmt = $pdo->prepare("SELECT * FROM doctori WHERE id = ?");
    $stmt->execute([$doctorId]);
    $doctor = $stmt->fetch();
    
    sendJsonResponse([
        'message' => 'Doctor created successfully',
        'doctor' => $doctor
    ], 201);
}

/**
 * PUT - Update doctor
 * Body: JSON with doctor data (must include id)
 */
function handleUpdateDoctor($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['id'])) {
        sendError('Doctor ID is required');
    }
    
    // Check if doctor exists
    $stmt = $pdo->prepare("SELECT id FROM doctori WHERE id = ?");
    $stmt->execute([$data['id']]);
    if (!$stmt->fetch()) {
        sendError('Doctor not found', 404);
    }
    
    // Build update query dynamically
    $allowedFields = [
        'nume', 'prenume', 'specializari', 'email', 'telefon', 'activ', 'data_angajarii'
    ];
    
    $updates = [];
    $params = [];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            // Handle specializari array
            if ($field === 'specializari' && is_array($data[$field])) {
                $updates[] = "{$field} = ?";
                $params[] = implode(', ', $data[$field]);
            } else {
                $updates[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }
    }
    
    if (empty($updates)) {
        sendError('No fields to update');
    }
    
    $params[] = $data['id'];
    
    $sql = "UPDATE doctori SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Get updated doctor
    $stmt = $pdo->prepare("SELECT * FROM doctori WHERE id = ?");
    $stmt->execute([$data['id']]);
    $doctor = $stmt->fetch();
    
    sendJsonResponse([
        'message' => 'Doctor updated successfully',
        'doctor' => $doctor
    ]);
}

/**
 * DELETE - Delete doctor
 * Query param: id
 */
function handleDeleteDoctor($pdo) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        sendError('Doctor ID is required');
    }
    
    // Check if doctor exists
    $stmt = $pdo->prepare("SELECT id FROM doctori WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        sendError('Doctor not found', 404);
    }
    
    // Soft delete: set activ to false instead of deleting
    $stmt = $pdo->prepare("UPDATE doctori SET activ = FALSE WHERE id = ?");
    $stmt->execute([$id]);
    
    sendJsonResponse([
        'message' => 'Doctor deactivated successfully',
        'id' => $id
    ]);
}
