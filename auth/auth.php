<?php
/**
 * Azure Sands Resort - Authentication API
 * Handles: register, login, logout, forgot-password
 */
require_once __DIR__ . '/../includes/common.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── REGISTER ──────────────────────────────────────────────
    case 'register':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
        }

        $name     = sanitize($_POST['name'] ?? '');
        $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm'] ?? '';
        $gender   = sanitize($_POST['gender'] ?? '');
        $city     = sanitize($_POST['city'] ?? '');

        if (!$name || !$email || !$password || !$confirm) {
            jsonResponse(['success' => false, 'message' => 'All required fields must be filled.']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['success' => false, 'message' => 'Invalid email address.']);
        }
        if (strlen($password) < 8) {
            jsonResponse(['success' => false, 'message' => 'Password must be at least 8 characters.']);
        }
        if ($password !== $confirm) {
            jsonResponse(['success' => false, 'message' => 'Passwords do not match.']);
        }

        $db   = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'Email address is already registered.']);
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare("INSERT INTO users (full_name, email, password, gender, city) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hashedPassword, $gender, $city]);
        $userId = $db->lastInsertId();

        // Auto-create membership
        $db->prepare("INSERT INTO memberships (user_id, tier) VALUES (?, 'Bronze')")->execute([$userId]);

        // Auto-login after register
        $_SESSION['user_id']   = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        session_regenerate_id(true); // Regenerate on new login

        jsonResponse(['success' => true, 'message' => 'Account created successfully! Welcome to Azure Sands.', 'user' => ['name' => $name, 'email' => $email]]);
        break;

    // ── LOGIN ─────────────────────────────────────────────────
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
        }

        $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if (!$email || !$password) {
            jsonResponse(['success' => false, 'message' => 'Email and password are required.']);
        }

        $db   = getDB();
        $stmt = $db->prepare("SELECT id, full_name, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            jsonResponse(['success' => false, 'message' => 'Invalid email or password.']);
        }

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        session_regenerate_id(true); // Regenerate only on login to prevent session fixation

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $db->prepare("UPDATE users SET remember_token = ? WHERE id = ?")->execute([$token, $user['id']]);
            setcookie('remember_token', $token, time() + SESSION_LIFETIME, '/', '', false, true);
        }

        jsonResponse(['success' => true, 'message' => 'Welcome back, ' . $user['full_name'] . '!', 'user' => ['name' => $user['full_name'], 'email' => $user['email']]]);
        break;

    // ── LOGOUT ────────────────────────────────────────────────
    case 'logout':
        if (isLoggedIn()) {
            $db = getDB();
            $db->prepare("UPDATE users SET remember_token = NULL WHERE id = ?")->execute([$_SESSION['user_id']]);
        }
        session_destroy();
        setcookie('remember_token', '', time() - 3600, '/');
        jsonResponse(['success' => true, 'message' => 'Logged out successfully.']);
        break;

    // ── CHECK SESSION ─────────────────────────────────────────
    case 'check':
        if (isLoggedIn()) {
            $user = getCurrentUser();
            jsonResponse(['logged_in' => true, 'user' => $user]);
        } else {
            jsonResponse(['logged_in' => false]);
        }
        break;

    // ── FORGOT PASSWORD ───────────────────────────────────────
    case 'forgot_password':
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['success' => false, 'message' => 'Please enter a valid email address.']);
        }
        $db   = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $token  = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', time() + 3600);
            $db->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?")->execute([$token, $expiry, $email]);
            // In production: send email with reset link
        }
        // Always return success to prevent email enumeration
        jsonResponse(['success' => true, 'message' => 'If that email is registered, a password reset link has been sent.']);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
}
