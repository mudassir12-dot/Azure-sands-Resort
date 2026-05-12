<?php
/**
 * Azure Sands Resort - Booking API
 * Handles: create booking, get bookings, cancel booking
 */
require_once __DIR__ . '/../includes/common.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── CREATE BOOKING ────────────────────────────────────────
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
        }

        $name     = sanitize($_POST['name'] ?? '');
        $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $city     = sanitize($_POST['city'] ?? '');
        $roomId   = intval($_POST['rooms'] ?? 0);
        $checkIn  = sanitize($_POST['date'] ?? '');
        $nights   = max(1, intval($_POST['nights'] ?? 1));
        $requests = sanitize($_POST['special_requests'] ?? '');

        // Validation
        if (!$name || !$email || !$checkIn || !$roomId) {
            jsonResponse(['success' => false, 'message' => 'Please fill all required fields.']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['success' => false, 'message' => 'Invalid email address.']);
        }

        $checkInDate = new DateTime($checkIn);
        $today       = new DateTime('today');
        if ($checkInDate < $today) {
            jsonResponse(['success' => false, 'message' => 'Check-in date cannot be in the past.']);
        }

        $db = getDB();

        // Get room details
        $stmt = $db->prepare("SELECT id, room_number, room_type, price_per_night, is_available FROM rooms WHERE room_number = ? OR id = ?");
        $stmt->execute([$roomId, $roomId]);
        $room = $stmt->fetch();

        if (!$room) {
            jsonResponse(['success' => false, 'message' => 'Selected room not found.']);
        }
        if (!$room['is_available']) {
            jsonResponse(['success' => false, 'message' => 'Sorry, this room is currently unavailable.']);
        }

        // Check for conflicting bookings
        $checkOut = (clone $checkInDate)->modify("+{$nights} days")->format('Y-m-d');
        $conflict = $db->prepare(
            "SELECT id FROM bookings 
             WHERE room_id = ? 
               AND status NOT IN ('Cancelled','Checked Out')
               AND check_in < ? AND check_out > ?"
        );
        $conflict->execute([$room['id'], $checkOut, $checkIn]);
        if ($conflict->fetch()) {
            jsonResponse(['success' => false, 'message' => 'This room is already booked for the selected dates. Please choose different dates.']);
        }

        $totalPrice = $room['price_per_night'] * $nights;
        $bookingRef = generateRef('BK');
        $userId     = $_SESSION['user_id'] ?? null;

        $stmt = $db->prepare(
            "INSERT INTO bookings 
             (booking_ref, user_id, guest_name, guest_email, guest_city, room_id, check_in, nights, check_out, total_price, special_requests, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')"
        );
        $stmt->execute([$bookingRef, $userId, $name, $email, $city, $room['id'], $checkIn, $nights, $checkOut, $totalPrice, $requests]);

        // Award loyalty points (1 point per $10 spent) + auto-upgrade tier
        if ($userId) {
            $points = intval($totalPrice / 10);
            $db->prepare("UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?")->execute([$points, $userId]);
            $db->prepare("UPDATE memberships SET points = points + ? WHERE user_id = ?")->execute([$points, $userId]);

            // Auto-upgrade membership tier based on total cumulative points
            $totalPoints = $db->prepare("SELECT loyalty_points FROM users WHERE id = ?");
            $totalPoints->execute([$userId]);
            $totalPoints = (int)$totalPoints->fetchColumn();

            $newTier = 'Bronze';
            if ($totalPoints >= 1500) $newTier = 'Platinum';
            elseif ($totalPoints >= 500) $newTier = 'Gold';
            elseif ($totalPoints >= 100) $newTier = 'Silver';

            $db->prepare("UPDATE users SET membership_tier = ? WHERE id = ?")->execute([$newTier, $userId]);
            $db->prepare("UPDATE memberships SET tier = ? WHERE user_id = ?")->execute([$newTier, $userId]);
        }

        jsonResponse([
            'success'     => true,
            'message'     => "Booking confirmed! Reference: {$bookingRef}",
            'booking_ref' => $bookingRef,
            'total'       => '$' . number_format($totalPrice, 2),
            'room'        => $room['room_type'],
            'check_in'    => $checkIn,
            'check_out'   => $checkOut
        ]);
        break;

    // ── GET MY BOOKINGS ───────────────────────────────────────
    case 'my_bookings':
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'message' => 'Please sign in to view your bookings.']);
        }
        $db   = getDB();
        $stmt = $db->prepare(
            "SELECT b.*, r.room_type, r.room_number, r.price_per_night, r.image
             FROM bookings b
             JOIN rooms r ON b.room_id = r.id
             WHERE b.user_id = ?
             ORDER BY b.created_at DESC"
        );
        $stmt->execute([$_SESSION['user_id']]);
        $bookings = $stmt->fetchAll();
        jsonResponse(['success' => true, 'bookings' => $bookings]);
        break;

    // ── CANCEL BOOKING ────────────────────────────────────────
    case 'cancel':
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'message' => 'Please sign in first.']);
        }
        $bookingRef = sanitize($_POST['booking_ref'] ?? '');
        $db         = getDB();
        $stmt       = $db->prepare("SELECT id, status, user_id FROM bookings WHERE booking_ref = ?");
        $stmt->execute([$bookingRef]);
        $booking = $stmt->fetch();

        if (!$booking || $booking['user_id'] != $_SESSION['user_id']) {
            jsonResponse(['success' => false, 'message' => 'Booking not found.']);
        }
        if (in_array($booking['status'], ['Cancelled', 'Checked In', 'Checked Out'])) {
            jsonResponse(['success' => false, 'message' => 'This booking cannot be cancelled.']);
        }

        $db->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ?")->execute([$booking['id']]);
        jsonResponse(['success' => true, 'message' => 'Booking cancelled successfully.']);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
}
