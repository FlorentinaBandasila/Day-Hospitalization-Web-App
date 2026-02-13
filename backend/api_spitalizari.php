<?php

/**
 * Spitalizari de Zi API Endpoint
 * Handles CRUD operations for day hospitalizations
 * Patient details are stored in spitalizari_de_zi table
 * Only basic patient info (CNP, name, sex, birthdate) in pacienti table
 */

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    setJsonHeaders();
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = db();

    switch ($method) {
        case 'GET':
            handleGetSpitalizari($pdo);
            break;
        case 'POST':
            handleCreateSpitalizare($pdo);
            break;
        case 'PUT':
            handleUpdateSpitalizare($pdo);
            break;
        case 'DELETE':
            handleDeleteSpitalizare($pdo);
            break;
        default:
            sendError('Method not allowed', 405);
    }
} catch (PDOException $e) {
    sendError('Database error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    sendError($e->getMessage(), 400);
}

function handleGetSpitalizari($pdo)
{
    $id = $_GET['id'] ?? null;
    $cnp = $_GET['cnp'] ?? null;

    if ($id) {
        $spitalizare = getSpitalizareDetails($pdo, $id);
        if (!$spitalizare) {
            sendError('Hospitalization record not found', 404);
        }
        sendJsonResponse($spitalizare);
    } elseif ($cnp) {
        $stmt = $pdo->prepare("
            SELECT s.*, p.nume, p.prenume,
                   DATE_FORMAT(s.data_spitalizare, '%d.%m.%Y') as data_formatted
            FROM spitalizari_de_zi s
            JOIN pacienti p ON s.cnp_pacient = p.cnp
            WHERE s.cnp_pacient = ?
            ORDER BY s.data_spitalizare DESC
        ");
        $stmt->execute([$cnp]);
        $records = $stmt->fetchAll();

        sendJsonResponse([
            'spitalizari' => $records,
            'total' => count($records)
        ]);
    } else {
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? null;
        $sectie = $_GET['sectie'] ?? null;

        $sql = "
            SELECT s.id, s.cnp_pacient, s.data_spitalizare, s.sectie, s.status,
                   s.ultima_modificare, p.nume, p.prenume,
                   DATE_FORMAT(s.data_spitalizare, '%d.%m.%Y') as data_formatted,
                   CONCAT(p.nume, ' ', p.prenume) as nume_complet
            FROM spitalizari_de_zi s
            JOIN pacienti p ON s.cnp_pacient = p.cnp
        ";

        $conditions = [];
        $params = [];

        if ($search) {
            $conditions[] = "(p.nume LIKE ? OR p.prenume LIKE ? OR s.cnp_pacient LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        if ($status) {
            $conditions[] = "s.status = ?";
            $params[] = $status;
        }

        if ($sectie) {
            $conditions[] = "s.sectie = ?";
            $params[] = $sectie;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        $sql .= " ORDER BY s.data_spitalizare DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll();

        $countSql = "SELECT COUNT(*) as total FROM spitalizari_de_zi s JOIN pacienti p ON s.cnp_pacient = p.cnp";
        if (!empty($conditions)) {
            $countSql .= " WHERE " . implode(" AND ", array_slice($conditions, 0, count($conditions)));
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute(array_slice($params, 0, count($params) - 2));
        } else {
            $countStmt = $pdo->query($countSql);
        }
        $total = $countStmt->fetch()['total'];

        sendJsonResponse([
            'spitalizari' => $records,
            'total' => $total
        ]);
    }
}

function handleCreateSpitalizare($pdo)
{
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || empty($data['cnp'])) {
        sendError('Patient CNP is required');
    }

    if (empty($data['data_spitalizare'])) {
        $data['data_spitalizare'] = date('Y-m-d');
    }

    $pdo->beginTransaction();

    try {
        // Check if patient exists, if not create basic patient record
        $stmt = $pdo->prepare("SELECT cnp FROM pacienti WHERE cnp = ?");
        $stmt->execute([$data['cnp']]);

        if (!$stmt->fetch()) {
            createBasicPatient($pdo, $data);
        }

        $spitalizareId = createSpitalizareRecord($pdo, $data);

        // Add related data
        if (!empty($data['explorari_functionale'])) {
            addExplorariFunctionale($pdo, $spitalizareId, $data['explorari_functionale']);
        }

        if (!empty($data['investigatii_radiologice'])) {
            addInvestigatiiRadiologice($pdo, $spitalizareId, $data['investigatii_radiologice']);
        }

        if (!empty($data['alte_proceduri'])) {
            addAlteProceduri($pdo, $spitalizareId, $data['alte_proceduri']);
        }

        if (!empty($data['analize_laborator'])) {
            addAnalizeLaborator($pdo, $spitalizareId, $data['analize_laborator']);
        }

        if (!empty($data['tratamente'])) {
            addTratamente($pdo, $spitalizareId, $data['tratamente']);
        }

        $pdo->commit();

        $spitalizare = getSpitalizareDetails($pdo, $spitalizareId);

        sendJsonResponse([
            'message' => 'Hospitalization created successfully',
            'spitalizare' => $spitalizare
        ], 201);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function handleUpdateSpitalizare($pdo)
{
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || empty($data['id'])) {
        sendError('Hospitalization ID is required');
    }

    $stmt = $pdo->prepare("SELECT id FROM spitalizari_de_zi WHERE id = ?");
    $stmt->execute([$data['id']]);
    if (!$stmt->fetch()) {
        sendError('Hospitalization record not found', 404);
    }

    $pdo->beginTransaction();

    try {
        updateSpitalizareRecord($pdo, $data);

        if (isset($data['explorari_functionale'])) {
            $pdo->prepare("DELETE FROM explorari_functionale WHERE id_spitalizare = ?")->execute([$data['id']]);
            if (!empty($data['explorari_functionale'])) {
                addExplorariFunctionale($pdo, $data['id'], $data['explorari_functionale']);
            }
        }

        if (isset($data['investigatii_radiologice'])) {
            $pdo->prepare("DELETE FROM investigatii_radiologice WHERE id_spitalizare = ?")->execute([$data['id']]);
            if (!empty($data['investigatii_radiologice'])) {
                addInvestigatiiRadiologice($pdo, $data['id'], $data['investigatii_radiologice']);
            }
        }

        if (isset($data['alte_proceduri'])) {
            $pdo->prepare("DELETE FROM alte_proceduri WHERE id_spitalizare = ?")->execute([$data['id']]);
            if (!empty($data['alte_proceduri'])) {
                addAlteProceduri($pdo, $data['id'], $data['alte_proceduri']);
            }
        }

        if (isset($data['analize_laborator'])) {
            $pdo->prepare("DELETE FROM analize_laborator WHERE id_spitalizare = ?")->execute([$data['id']]);
            if (!empty($data['analize_laborator'])) {
                addAnalizeLaborator($pdo, $data['id'], $data['analize_laborator']);
            }
        }

        if (isset($data['tratamente'])) {
            $pdo->prepare("DELETE FROM tratamente WHERE id_spitalizare = ?")->execute([$data['id']]);
            if (!empty($data['tratamente'])) {
                addTratamente($pdo, $data['id'], $data['tratamente']);
            }
        }

        $pdo->commit();

        $spitalizare = getSpitalizareDetails($pdo, $data['id']);

        sendJsonResponse([
            'message' => 'Hospitalization updated successfully',
            'spitalizare' => $spitalizare
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function handleDeleteSpitalizare($pdo)
{
    $id = $_GET['id'] ?? null;

    if (!$id) {
        sendError('Hospitalization ID is required');
    }

    $stmt = $pdo->prepare("SELECT id FROM spitalizari_de_zi WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        sendError('Hospitalization record not found', 404);
    }

    $stmt = $pdo->prepare("DELETE FROM spitalizari_de_zi WHERE id = ?");
    $stmt->execute([$id]);

    sendJsonResponse([
        'message' => 'Hospitalization deleted successfully',
        'id' => $id
    ]);
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function createBasicPatient($pdo, $data)
{
    $varsta = calculateAgeFromCNP($data['cnp']);

    $sql = "INSERT INTO pacienti (cnp, nume, prenume, sex, data_nasterii, varsta) 
            VALUES (:cnp, :nume, :prenume, :sex, :data_nasterii, :varsta)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'cnp' => $data['cnp'],
        'nume' => $data['nume'] ?? '',
        'prenume' => $data['prenume'] ?? '',
        'sex' => $data['sex'] ?? '',
        'data_nasterii' => $data['data_nasterii'] ?? null,
        'varsta' => $varsta
    ]);
}

function createSpitalizareRecord($pdo, $data)
{
    $sql = "INSERT INTO spitalizari_de_zi (
        cnp_pacient, id_doctor, data_spitalizare, judet, localitate, spital, sectie,
        nr_registru, tip_servicii, status,
        grup_sanguin, rh, alergic_la,
        domiciliu_judet, domiciliu_localitate, domiciliu_mediu, domiciliu_strada, domiciliu_numar,
        resedinta_same_domiciliu, resedinta_judet, resedinta_localitate, resedinta_mediu, resedinta_strada, resedinta_numar,
        cetatenie, ocupatia, loc_de_munca, nivel_instruire,
        statut_asigurat, categorie_asigurat,
        diagnostic_principal, cod_icd, diagnostice_secundare, epicriza, ultima_modificare
    ) VALUES (
        :cnp_pacient, :id_doctor, :data_spitalizare, :judet, :localitate, :spital, :sectie,
        :nr_registru, :tip_servicii, :status,
        :grup_sanguin, :rh, :alergic_la,
        :domiciliu_judet, :domiciliu_localitate, :domiciliu_mediu, :domiciliu_strada, :domiciliu_numar,
        :resedinta_same_domiciliu, :resedinta_judet, :resedinta_localitate, :resedinta_mediu, :resedinta_strada, :resedinta_numar,
        :cetatenie, :ocupatia, :loc_de_munca, :nivel_instruire,
        :statut_asigurat, :categorie_asigurat,
        :diagnostic_principal, :cod_icd, :diagnostice_secundare, :epicriza, :ultima_modificare
    )";

    $diagnosticeSecundare = null;
    if (!empty($data['diagnostice_secundare'])) {
        $diagnosticeSecundare = json_encode($data['diagnostice_secundare'], JSON_UNESCAPED_UNICODE);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'cnp_pacient' => $data['cnp'],
        'id_doctor' => $data['id_doctor'] ?? null,
        'data_spitalizare' => $data['data_spitalizare'],
        'judet' => $data['judet'] ?? null,
        'localitate' => $data['localitate'] ?? null,
        'spital' => $data['spital'] ?? null,
        'sectie' => $data['sectie'] ?? null,
        'nr_registru' => $data['nr_registru'] ?? null,
        'tip_servicii' => $data['tip_servicii'] ?? null,
        'status' => $data['status'] ?? 'draft',
        'grup_sanguin' => $data['grup_sanguin'] ?? '',
        'rh' => $data['rh'] ?? '',
        'alergic_la' => $data['alergic_la'] ?? null,
        'domiciliu_judet' => $data['domiciliu_judet'] ?? null,
        'domiciliu_localitate' => $data['domiciliu_localitate'] ?? null,
        'domiciliu_mediu' => $data['domiciliu_mediu'] ?? '',
        'domiciliu_strada' => $data['domiciliu_strada'] ?? null,
        'domiciliu_numar' => $data['domiciliu_numar'] ?? null,
        'resedinta_same_domiciliu' => $data['resedinta_same_domiciliu'] ?? false,
        'resedinta_judet' => $data['resedinta_judet'] ?? null,
        'resedinta_localitate' => $data['resedinta_localitate'] ?? null,
        'resedinta_mediu' => $data['resedinta_mediu'] ?? '',
        'resedinta_strada' => $data['resedinta_strada'] ?? null,
        'resedinta_numar' => $data['resedinta_numar'] ?? null,
        'cetatenie' => $data['cetatenie'] ?? 'romana',
        'ocupatia' => $data['ocupatia'] ?? null,
        'loc_de_munca' => $data['loc_de_munca'] ?? null,
        'nivel_instruire' => $data['nivel_instruire'] ?? '',
        'statut_asigurat' => $data['statut_asigurat'] ?? '',
        'categorie_asigurat' => $data['categorie_asigurat'] ?? null,
        'diagnostic_principal' => $data['diagnostic_principal'] ?? null,
        'cod_icd' => $data['cod_icd'] ?? null,
        'diagnostice_secundare' => $diagnosticeSecundare,
        'epicriza' => $data['epicriza'] ?? null,
        'ultima_modificare' => $data['ultima_modificare'] ?? date('d.m.Y') . ' - System'
    ]);

    return $pdo->lastInsertId();
}

function updateSpitalizareRecord($pdo, $data)
{
    $allowedFields = [
        'data_spitalizare',
        'judet',
        'localitate',
        'spital',
        'sectie',
        'nr_registru',
        'tip_servicii',
        'status',
        'grup_sanguin',
        'rh',
        'alergic_la',
        'domiciliu_judet',
        'domiciliu_localitate',
        'domiciliu_mediu',
        'domiciliu_strada',
        'domiciliu_numar',
        'resedinta_same_domiciliu',
        'resedinta_judet',
        'resedinta_localitate',
        'resedinta_mediu',
        'resedinta_strada',
        'resedinta_numar',
        'cetatenie',
        'ocupatia',
        'loc_de_munca',
        'nivel_instruire',
        'statut_asigurat',
        'categorie_asigurat',
        'diagnostic_principal',
        'cod_icd',
        'diagnostice_secundare',
        'epicriza',
        'ultima_modificare'
    ];

    $updates = [];
    $params = [];

    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            if ($field === 'diagnostice_secundare') {
                $updates[] = "{$field} = ?";
                $params[] = json_encode($data[$field], JSON_UNESCAPED_UNICODE);
            } else {
                $updates[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }
    }

    if (empty($updates)) {
        return;
    }

    $params[] = $data['id'];

    $sql = "UPDATE spitalizari_de_zi SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

function addExplorariFunctionale($pdo, $spitalizareId, $explorari)
{
    $sql = "INSERT INTO explorari_functionale (id_spitalizare, denumire, cod, numar) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    foreach ($explorari as $item) {
        if (empty($item['denumire'])) continue;
        $stmt->execute([
            $spitalizareId,
            $item['denumire'],
            $item['cod'] ?? null,
            $item['numar'] ?? 1
        ]);
    }
}

function addInvestigatiiRadiologice($pdo, $spitalizareId, $investigatii)
{
    $sql = "INSERT INTO investigatii_radiologice (id_spitalizare, denumire, cod, numar) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    foreach ($investigatii as $item) {
        if (empty($item['denumire'])) continue;
        $stmt->execute([
            $spitalizareId,
            $item['denumire'],
            $item['cod'] ?? null,
            $item['numar'] ?? 1
        ]);
    }
}

function addAlteProceduri($pdo, $spitalizareId, $proceduri)
{
    $sql = "INSERT INTO alte_proceduri (id_spitalizare, denumire, cod, numar) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    foreach ($proceduri as $item) {
        if (empty($item['denumire'])) continue;
        $stmt->execute([
            $spitalizareId,
            $item['denumire'],
            $item['cod'] ?? null,
            $item['numar'] ?? 1
        ]);
    }
}

function addAnalizeLaborator($pdo, $spitalizareId, $analize)
{
    $sql = "INSERT INTO analize_laborator (id_spitalizare, denumire, cod, numar) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    foreach ($analize as $item) {
        if (empty($item['denumire'])) continue;
        $stmt->execute([
            $spitalizareId,
            $item['denumire'],
            $item['cod'] ?? null,
            $item['numar'] ?? 1
        ]);
    }
}

function addTratamente($pdo, $spitalizareId, $tratamente)
{
    $sql = "INSERT INTO tratamente (id_spitalizare, data_tratament, descriere) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    foreach ($tratamente as $item) {
        if (empty($item['descriere'])) continue;
        $stmt->execute([
            $spitalizareId,
            $item['data'] ?? date('Y-m-d'),
            $item['descriere']
        ]);
    }
}

function getSpitalizareDetails($pdo, $id)
{
    $stmt = $pdo->prepare("
        SELECT s.*, p.nume, p.prenume, p.sex as pacient_sex, p.data_nasterii as pacient_data_nasterii,
               DATE_FORMAT(s.data_spitalizare, '%d.%m.%Y') as data_formatted
        FROM spitalizari_de_zi s
        JOIN pacienti p ON s.cnp_pacient = p.cnp
        WHERE s.id = ?
    ");
    $stmt->execute([$id]);
    $spitalizare = $stmt->fetch();

    if (!$spitalizare) {
        return null;
    }

    if ($spitalizare['diagnostice_secundare']) {
        $spitalizare['diagnostice_secundare'] = json_decode($spitalizare['diagnostice_secundare'], true);
    }

    $stmt = $pdo->prepare("SELECT * FROM explorari_functionale WHERE id_spitalizare = ?");
    $stmt->execute([$id]);
    $spitalizare['explorari_functionale'] = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM investigatii_radiologice WHERE id_spitalizare = ?");
    $stmt->execute([$id]);
    $spitalizare['investigatii_radiologice'] = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM alte_proceduri WHERE id_spitalizare = ?");
    $stmt->execute([$id]);
    $spitalizare['alte_proceduri'] = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM analize_laborator WHERE id_spitalizare = ?");
    $stmt->execute([$id]);
    $spitalizare['analize_laborator'] = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT *, DATE_FORMAT(data_tratament, '%d.%m.%Y') as data_formatted 
        FROM tratamente 
        WHERE id_spitalizare = ? 
        ORDER BY data_tratament
    ");
    $stmt->execute([$id]);
    $spitalizare['tratamente'] = $stmt->fetchAll();

    return $spitalizare;
}

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
