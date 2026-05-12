<?php
/**
 * Azure Sands Resort - Career Application API
 * Handles: submit application, get positions
 */
require_once __DIR__ . '/../includes/common.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── GET POSITIONS ─────────────────────────────────────────
    case 'positions':
        $db   = getDB();
        $stmt = $db->prepare("SELECT position_title, position_key, department, description FROM careers WHERE is_active = 1");
        $stmt->execute();
        jsonResponse(['success' => true, 'positions' => $stmt->fetchAll()]);
        break;

    // ── SUBMIT APPLICATION ────────────────────────────────────
    case 'apply':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
        }

        $name      = sanitize($_POST['name'] ?? '');
        $email     = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $position  = sanitize($_POST['Job'] ?? '');
        $startDate = sanitize($_POST['date'] ?? '');

        if (!$name || !$email || !$position || !$startDate) {
            jsonResponse(['success' => false, 'message' => 'Please fill all required fields.']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['success' => false, 'message' => 'Invalid email address.']);
        }

        // Handle CV upload
        $cvFilename = null;
        if (!empty($_FILES['cv']['name'])) {
            $allowed    = ['pdf', 'doc', 'docx'];
            $ext        = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));
            $maxSize    = 5 * 1024 * 1024; // 5MB

            if (!in_array($ext, $allowed)) {
                jsonResponse(['success' => false, 'message' => 'CV must be PDF, DOC, or DOCX format.']);
            }
            if ($_FILES['cv']['size'] > $maxSize) {
                jsonResponse(['success' => false, 'message' => 'CV file size must not exceed 5MB.']);
            }

            $cvFilename = 'cv_' . uniqid() . '.' . $ext;
            $uploadPath = UPLOADS_PATH . 'cvs/';

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            if (!move_uploaded_file($_FILES['cv']['tmp_name'], $uploadPath . $cvFilename)) {
                $cvFilename = null; // Upload failed silently, still accept application
            }
        }

        $appRef = generateRef('AP');
        $db     = getDB();
        $stmt   = $db->prepare(
            "INSERT INTO applications (app_ref, applicant_name, applicant_email, position_applied, available_date, cv_filename, status)
             VALUES (?, ?, ?, ?, ?, ?, 'Received')"
        );
        $stmt->execute([$appRef, $name, $email, $position, $startDate, $cvFilename]);

        jsonResponse([
            'success' => true,
            'message' => "Application submitted! Your reference is {$appRef}. We'll be in touch within 5-7 business days.",
            'app_ref' => $appRef
        ]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
}
