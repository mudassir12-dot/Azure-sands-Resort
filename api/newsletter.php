<?php
/**
 * Azure Sands Resort – Newsletter & Contact API
 */
require_once __DIR__ . '/../includes/common.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'subscribe':
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['success' => false, 'message' => 'Invalid email address.']);
        }
        $db = getDB();
        try {
            $db->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)")->execute([$email]);
            jsonResponse(['success' => true, 'message' => 'Thank you for subscribing!']);
        } catch (Exception $e) {
            jsonResponse(['success' => true, 'message' => 'You are already subscribed!']);
        }
        break;

    case 'contact':
        $name    = sanitize($_POST['name'] ?? '');
        $email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $subject = sanitize($_POST['subject'] ?? '');
        $message = sanitize($_POST['message'] ?? '');
        if (!$name || !$email || !$message) {
            jsonResponse(['success' => false, 'message' => 'Please fill all required fields.']);
        }
        $db = getDB();
        $db->prepare(
            "INSERT INTO contact_messages (sender_name, sender_email, subject, message) VALUES (?,?,?,?)"
        )->execute([$name, $email, $subject, $message]);
        jsonResponse(['success' => true, 'message' => 'Message sent! We\'ll get back to you within 24 hours.']);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
}
