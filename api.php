<?php
header('Content-Type: application/json');
require_once 'auth.php';
require_once 'includes/db_connect.php';

$action = $_GET['action'] ?? '';

switch ($action) {

    // ─── AUTH ──────────────────────────────────────────────────────────────

    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { bad(); }
        $data = json_input();
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
        if (!$username || !$password) { err('Username and password required.'); }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Invalid username or password.']);
            exit;
        }

        $_SESSION['user_id']       = $user['id'];
        $_SESSION['username']      = $user['username'];
        $_SESSION['full_name']     = $user['full_name'];
        $_SESSION['role']          = $user['role'];
        $_SESSION['specialist_id'] = $user['specialist_id'];

        echo json_encode([
            'success'       => true,
            'role'          => $user['role'],
            'full_name'     => $user['full_name'],
            'specialist_id' => $user['specialist_id'],
        ]);
        break;

    case 'logout':
        session_destroy();
        echo json_encode(['success' => true]);
        break;

    case 'auth_check':
        $user = getCurrentUser();
        if ($user) {
            echo json_encode(['authenticated' => true] + $user);
        } else {
            echo json_encode(['authenticated' => false]);
        }
        break;

    // ─── PATIENTS ──────────────────────────────────────────────────────────

    case 'get_patients':
        requireRole(['super_admin', 'reception', 'specialist']);
        $q = $_GET['q'] ?? '';
        if ($q) {
            $like = "%$q%";
            $stmt = $pdo->prepare("SELECT * FROM patients WHERE full_name LIKE ? OR phone LIKE ? OR cpr LIKE ? ORDER BY created_at DESC");
            $stmt->execute([$like, $like, $like]);
        } else {
            $stmt = $pdo->query("SELECT * FROM patients ORDER BY created_at DESC");
        }
        echo json_encode($stmt->fetchAll());
        break;

    case 'get_patient':
        requireRole(['super_admin', 'reception', 'specialist']);
        $id = intval($_GET['id'] ?? 0);
        if (!$id) { err('Missing id'); }
        $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
        $stmt->execute([$id]);
        $patient = $stmt->fetch();
        if (!$patient) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }

        $stmt2 = $pdo->prepare("SELECT * FROM medical_history WHERE patient_id = ? LIMIT 1");
        $stmt2->execute([$id]);
        $patient['medical_history'] = $stmt2->fetch() ?: null;

        echo json_encode($patient);
        break;

    case 'add_patient':
        requireRole(['super_admin', 'reception']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { bad(); }
        $data = json_input();

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO patients (full_name, dob, phone, file_no, cpr, email) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['full_name'],
                $data['dob'] ?: null,
                $data['phone'],
                $data['file_no'] ?? null,
                $data['cpr'] ?? null,
                $data['email'] ?? null,
            ]);
            $patient_id = $pdo->lastInsertId();

            if (isset($data['medical_history'])) {
                $mh = $data['medical_history'];
                $stmt = $pdo->prepare("INSERT INTO medical_history (patient_id, blood_pressure, diabetes, heart_disease, leukemia, other_diseases, previous_hejama, blood_thinners, pregnant, allergies, additional_notes) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([
                    $patient_id,
                    $mh['blood_pressure'] ? 1 : 0,
                    $mh['diabetes'] ? 1 : 0,
                    $mh['heart_disease'] ? 1 : 0,
                    $mh['leukemia'] ? 1 : 0,
                    $mh['other_diseases'] ?? '',
                    $mh['previous_hejama'] ? 1 : 0,
                    $mh['blood_thinners'] ? 1 : 0,
                    $mh['pregnant'] ? 1 : 0,
                    $mh['allergies'] ?? '',
                    $mh['additional_notes'] ?? '',
                ]);
            }
            $pdo->commit();
            echo json_encode(['success' => true, 'patient_id' => $patient_id]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'update_patient':
        requireRole(['super_admin', 'reception']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { bad(); }
        $data = json_input();
        $id = intval($data['id'] ?? 0);
        if (!$id) { err('Missing id'); }

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE patients SET full_name=?, dob=?, phone=?, file_no=?, cpr=?, email=? WHERE id=?");
            $stmt->execute([$data['full_name'], $data['dob'] ?: null, $data['phone'], $data['file_no'] ?? null, $data['cpr'] ?? null, $data['email'] ?? null, $id]);

            if (isset($data['medical_history'])) {
                $mh = $data['medical_history'];
                $exists = $pdo->prepare("SELECT id FROM medical_history WHERE patient_id=?");
                $exists->execute([$id]);
                if ($exists->fetch()) {
                    $stmt = $pdo->prepare("UPDATE medical_history SET blood_pressure=?,diabetes=?,heart_disease=?,leukemia=?,other_diseases=?,previous_hejama=?,blood_thinners=?,pregnant=?,allergies=?,additional_notes=? WHERE patient_id=?");
                    $stmt->execute([$mh['blood_pressure']?1:0,$mh['diabetes']?1:0,$mh['heart_disease']?1:0,$mh['leukemia']?1:0,$mh['other_diseases']??'',$mh['previous_hejama']?1:0,$mh['blood_thinners']?1:0,$mh['pregnant']?1:0,$mh['allergies']??'',$mh['additional_notes']??'',$id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO medical_history (patient_id,blood_pressure,diabetes,heart_disease,leukemia,other_diseases,previous_hejama,blood_thinners,pregnant,allergies,additional_notes) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                    $stmt->execute([$id,$mh['blood_pressure']?1:0,$mh['diabetes']?1:0,$mh['heart_disease']?1:0,$mh['leukemia']?1:0,$mh['other_diseases']??'',$mh['previous_hejama']?1:0,$mh['blood_thinners']?1:0,$mh['pregnant']?1:0,$mh['allergies']??'',$mh['additional_notes']??'']);
                }
            }
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ─── APPOINTMENTS ──────────────────────────────────────────────────────

    case 'get_appointments':
        requireRole(['super_admin', 'reception', 'specialist']);
        $date   = $_GET['date'] ?? null;
        $specId = intval($_GET['specialist_id'] ?? 0);
        $status = $_GET['status'] ?? null;

        $where = [];
        $params = [];

        if ($date) {
            $where[] = "DATE(a.appointment_date) = ?";
            $params[] = $date;
        }
        if ($specId) {
            $where[] = "a.specialist_id = ?";
            $params[] = $specId;
        }
        if ($status) {
            $where[] = "a.status = ?";
            $params[] = $status;
        }

        $sql = "SELECT a.*, p.full_name AS patient_name, p.phone AS patient_phone, p.cpr AS patient_cpr,
                       s.name AS specialist_name
                FROM appointments a
                JOIN patients p ON a.patient_id = p.id
                LEFT JOIN specialists s ON a.specialist_id = s.id"
             . ($where ? (' WHERE ' . implode(' AND ', $where)) : '')
             . " ORDER BY a.appointment_date ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll());
        break;

    case 'add_appointment':
        requireRole(['super_admin', 'reception']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { bad(); }
        $data = json_input();
        $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, appointment_date, status, specialist_id, notes) VALUES (?,?,?,?,?)");
        $stmt->execute([
            intval($data['patient_id']),
            $data['appointment_date'],
            $data['status'] ?? 'Scheduled',
            $data['specialist_id'] ? intval($data['specialist_id']) : null,
            $data['notes'] ?? null,
        ]);
        echo json_encode(['success' => true, 'appointment_id' => $pdo->lastInsertId()]);
        break;

    case 'update_appointment':
        requireRole(['super_admin', 'reception', 'specialist']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { bad(); }
        $data = json_input();
        $id = intval($data['id'] ?? 0);
        if (!$id) { err('Missing id'); }

        $fields = [];
        $params = [];
        foreach (['status', 'specialist_id', 'notes', 'appointment_date'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $params[] = $data[$f];
            }
        }
        if (!$fields) { err('Nothing to update'); }
        $params[] = $id;
        $pdo->prepare("UPDATE appointments SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);
        echo json_encode(['success' => true]);
        break;

    case 'get_today_stats':
        requireRole(['super_admin', 'reception', 'specialist']);
        $today = date('Y-m-d');
        $stats = [];

        $stats['total_today'] = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE DATE(appointment_date)=?");
        $stats['total_today']->execute([$today]);
        $stats['total_today'] = (int)$stats['total_today']->fetchColumn();

        $stats['waiting'] = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE status='Waiting' AND DATE(appointment_date)=?");
        $stats['waiting']->execute([$today]);
        $stats['waiting'] = (int)$stats['waiting']->fetchColumn();

        $stats['completed_today'] = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE status='Completed' AND DATE(appointment_date)=?");
        $stats['completed_today']->execute([$today]);
        $stats['completed_today'] = (int)$stats['completed_today']->fetchColumn();

        $stats['new_patients_today'] = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE DATE(created_at)=?");
        $stats['new_patients_today']->execute([$today]);
        $stats['new_patients_today'] = (int)$stats['new_patients_today']->fetchColumn();

        echo json_encode($stats);
        break;

    // ─── SPECIALISTS ───────────────────────────────────────────────────────

    case 'get_specialists':
        requireRole(['super_admin', 'reception', 'specialist']);
        $stmt = $pdo->query("SELECT * FROM specialists ORDER BY name");
        echo json_encode($stmt->fetchAll());
        break;

    // ─── TREATMENT SESSIONS ────────────────────────────────────────────────

    case 'save_treatment_session':
        requireRole(['super_admin', 'specialist']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { bad(); }
        $data = json_input();
        $apptId = intval($data['appointment_id'] ?? 0);
        if (!$apptId) { err('Missing appointment_id'); }

        $user = getCurrentUser();
        $specId = $data['specialist_id'] ? intval($data['specialist_id']) : $user['specialist_id'];

        // Save treatment session
        $stmt = $pdo->prepare("INSERT INTO treatment_sessions (appointment_id, specialist_id, cup_positions, cupping_types, cup_count, blood_density, blood_color, specialist_notes, patient_notes) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $apptId,
            $specId,
            json_encode($data['cup_positions'] ?? []),
            $data['cupping_types'] ?? '',
            intval($data['cup_count'] ?? 0),
            $data['blood_density'] ?? 'Normal',
            $data['blood_color'] ?? 'Bright Red',
            $data['specialist_notes'] ?? '',
            $data['patient_notes'] ?? '',
        ]);
        $sessionId = $pdo->lastInsertId();

        // Mark appointment as Completed
        $pdo->prepare("UPDATE appointments SET status='Completed', specialist_id=? WHERE id=?")->execute([$specId, $apptId]);

        echo json_encode(['success' => true, 'session_id' => $sessionId]);
        break;

    case 'get_patient_sessions':
        requireRole(['super_admin', 'reception', 'specialist']);
        $patientId = intval($_GET['patient_id'] ?? 0);
        if (!$patientId) { err('Missing patient_id'); }

        $stmt = $pdo->prepare("
            SELECT ts.*, a.appointment_date, a.patient_id,
                   s.name AS specialist_name
            FROM treatment_sessions ts
            JOIN appointments a ON ts.appointment_id = a.id
            LEFT JOIN specialists s ON ts.specialist_id = s.id
            WHERE a.patient_id = ?
            ORDER BY ts.completed_at DESC
        ");
        $stmt->execute([$patientId]);
        $sessions = $stmt->fetchAll();

        // Decode cup_positions JSON
        foreach ($sessions as &$sess) {
            $sess['cup_positions'] = json_decode($sess['cup_positions'] ?? '[]', true);
        }
        echo json_encode($sessions);
        break;

    case 'get_session':
        requireRole(['super_admin', 'reception', 'specialist']);
        $id = intval($_GET['id'] ?? 0);
        if (!$id) { err('Missing id'); }
        $stmt = $pdo->prepare("SELECT ts.*, s.name AS specialist_name FROM treatment_sessions ts LEFT JOIN specialists s ON ts.specialist_id=s.id WHERE ts.id=?");
        $stmt->execute([$id]);
        $sess = $stmt->fetch();
        if (!$sess) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
        $sess['cup_positions'] = json_decode($sess['cup_positions'] ?? '[]', true);
        echo json_encode($sess);
        break;

    // ─── GUEST PORTAL ──────────────────────────────────────────────────────
    // Public endpoint — no session required. Only returns non-sensitive data.

    case 'guest_lookup':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { bad(); }
        $data = json_input();
        $cpr   = trim($data['national_id'] ?? '');
        $phone = trim($data['phone'] ?? '');
        if (!$cpr || !$phone) { err('National ID and phone are required.'); }

        // Find patient by CPR + phone (no personal name returned)
        $stmt = $pdo->prepare("SELECT id FROM patients WHERE cpr = ? AND phone = ?");
        $stmt->execute([$cpr, $phone]);
        $patient = $stmt->fetch();

        if (!$patient) {
            http_response_code(404);
            echo json_encode(['found' => false, 'error' => 'No records found for this National ID and phone number.']);
            exit;
        }

        $pid = $patient['id'];

        // Upcoming appointments (Scheduled or Waiting)
        $stmt = $pdo->prepare("
            SELECT a.id, a.appointment_date, a.status, s.name AS specialist_name
            FROM appointments a
            LEFT JOIN specialists s ON a.specialist_id = s.id
            WHERE a.patient_id = ? AND a.status IN ('Scheduled','Waiting')
            ORDER BY a.appointment_date ASC
        ");
        $stmt->execute([$pid]);
        $upcoming = $stmt->fetchAll();

        // Past completed sessions (only patient_notes + body map)
        $stmt = $pdo->prepare("
            SELECT ts.id, ts.completed_at, ts.cup_positions, ts.cupping_types,
                   ts.cup_count, ts.patient_notes, a.appointment_date,
                   s.name AS specialist_name
            FROM treatment_sessions ts
            JOIN appointments a ON ts.appointment_id = a.id
            LEFT JOIN specialists s ON ts.specialist_id = s.id
            WHERE a.patient_id = ?
            ORDER BY ts.completed_at DESC
        ");
        $stmt->execute([$pid]);
        $sessions = $stmt->fetchAll();
        foreach ($sessions as &$sess) {
            $sess['cup_positions'] = json_decode($sess['cup_positions'] ?? '[]', true);
            unset($sess['specialist_notes']); // specialist-only
        }

        echo json_encode([
            'found'    => true,
            'upcoming' => $upcoming,
            'sessions' => $sessions,
        ]);
        break;

    // ─── USER MANAGEMENT (super_admin only) ────────────────────────────────

    case 'get_users':
        requireRole(['super_admin']);
        $stmt = $pdo->query("SELECT id, username, full_name, role, is_active, created_at, specialist_id FROM users ORDER BY created_at DESC");
        echo json_encode($stmt->fetchAll());
        break;

    case 'create_user':
        requireRole(['super_admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { bad(); }
        $data = json_input();
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role, specialist_id) VALUES (?,?,?,?,?)");
        $stmt->execute([
            $data['username'],
            $hash,
            $data['full_name'],
            $data['role'],
            $data['specialist_id'] ? intval($data['specialist_id']) : null,
        ]);
        echo json_encode(['success' => true, 'user_id' => $pdo->lastInsertId()]);
        break;

    case 'update_user':
        requireRole(['super_admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { bad(); }
        $data = json_input();
        $id = intval($data['id'] ?? 0);
        if (!$id) { err('Missing id'); }

        $fields = [];
        $params = [];
        foreach (['full_name','role','is_active','specialist_id'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $params[] = $data[$f];
            }
        }
        if (!empty($data['password'])) {
            $fields[] = "password_hash = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        $params[] = $id;
        $pdo->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id=?")->execute($params);
        echo json_encode(['success' => true]);
        break;

    // ─── DEFAULT ───────────────────────────────────────────────────────────

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

// ─── HELPERS ───────────────────────────────────────────────────────────────

function json_input(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

function err(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

function bad(): never {
    err('Method not allowed', 405);
}
?>
