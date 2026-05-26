<?php
// api.php - Basic API endpoints for the Hejama Management System
header('Content-Type: application/json');
require_once 'includes/db_connect.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_patients':
        try {
            $stmt = $pdo->query("SELECT * FROM patients ORDER BY created_at DESC");
            $patients = $stmt->fetchAll();
            echo json_encode($patients);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'add_patient':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("INSERT INTO patients (full_name, dob, phone, file_no, cpr) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $data['full_name'],
                    $data['dob'],
                    $data['phone'],
                    $data['file_no'] ?? null,
                    $data['cpr'] ?? null
                ]);
                $patient_id = $pdo->lastInsertId();

                // Insert medical history if provided
                if (isset($data['medical_history'])) {
                    $mh = $data['medical_history'];
                    $stmt = $pdo->prepare("INSERT INTO medical_history (patient_id, blood_pressure, diabetes, heart_disease, leukemia, other_diseases, previous_hejama, blood_thinners, pregnant, allergies, additional_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
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
                        $mh['additional_notes'] ?? ''
                    ]);
                }

                $pdo->commit();
                echo json_encode(['success' => true, 'patient_id' => $patient_id]);
            } catch (Exception $e) {
                $pdo->rollBack();
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
        }
        break;

    case 'get_appointments':
        try {
            $stmt = $pdo->query("SELECT a.*, p.full_name as patient_name, s.name as specialist_name 
                                 FROM appointments a 
                                 JOIN patients p ON a.patient_id = p.id 
                                 LEFT JOIN specialists s ON a.specialist_id = s.id 
                                 ORDER BY a.appointment_date ASC");
            $appointments = $stmt->fetchAll();
            echo json_encode($appointments);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>
