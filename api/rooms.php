<?php
/**
 * Azure Sands Resort – Rooms API
 * GET  ?action=list            → all available rooms
 * GET  ?action=availability&room_id=X&check_in=Y&nights=Z → check availability
 * POST action=submit_review    → submit a guest review
 */
require_once __DIR__ . '/../includes/common.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

switch ($action) {

    // ── LIST ALL AVAILABLE ROOMS ─────────────────────────────
    case 'list':
        $db   = getDB();
        $stmt = $db->prepare(
            "SELECT id, room_number, room_type, price_per_night, capacity,
                    description, features, image, badge, is_available
             FROM rooms
             ORDER BY price_per_night ASC"
        );
        $stmt->execute();
        $rooms = $stmt->fetchAll();

        // Decode JSON features field
        foreach ($rooms as &$room) {
            $room['features'] = json_decode($room['features'] ?? '[]', true) ?: [];
        }

        jsonResponse(['success' => true, 'rooms' => $rooms]);
        break;

    // ── CHECK ROOM AVAILABILITY ──────────────────────────────
    case 'availability':
        $roomId  = sanitize($_GET['room_id'] ?? '');
        $checkIn = sanitize($_GET['check_in'] ?? '');
        $nights  = max(1, intval($_GET['nights'] ?? 1));

        if (!$roomId || !$checkIn) {
            jsonResponse(['success' => false, 'message' => 'room_id and check_in are required.'], 400);
        }

        $checkInDate  = DateTime::createFromFormat('Y-m-d', $checkIn);
        if (!$checkInDate) {
            jsonResponse(['success' => false, 'message' => 'Invalid check_in date format. Use YYYY-MM-DD.'], 400);
        }
        $checkOutDate = (clone $checkInDate)->modify("+{$nights} days");
        $checkOut     = $checkOutDate->format('Y-m-d');

        $db = getDB();

        // Get room
        $stmt = $db->prepare("SELECT id, room_type, price_per_night, is_available FROM rooms WHERE room_number = ? OR id = ?");
        $stmt->execute([$roomId, $roomId]);
        $room = $stmt->fetch();

        if (!$room) {
            jsonResponse(['success' => false, 'available' => false, 'message' => 'Room not found.']);
        }
        if (!$room['is_available']) {
            jsonResponse(['success' => true, 'available' => false, 'message' => 'Room is currently marked unavailable.']);
        }

        // Check booking conflicts
        $conflict = $db->prepare(
            "SELECT id FROM bookings
             WHERE room_id = ?
               AND status NOT IN ('Cancelled','Checked Out')
               AND check_in < ? AND check_out > ?"
        );
        $conflict->execute([$room['id'], $checkOut, $checkIn]);

        $isAvailable = !$conflict->fetch();
        $totalPrice  = $room['price_per_night'] * $nights;

        jsonResponse([
            'success'    => true,
            'available'  => $isAvailable,
            'room_type'  => $room['room_type'],
            'nights'     => $nights,
            'check_in'   => $checkIn,
            'check_out'  => $checkOut,
            'total'      => '$' . number_format($totalPrice, 2),
            'message'    => $isAvailable
                ? 'Room is available for those dates!'
                : 'Room is already booked for those dates. Please choose different dates.'
        ]);
        break;

    // ── SUBMIT REVIEW ────────────────────────────────────────
    case 'submit_review':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'POST required.'], 405);
        }

        $reviewerName = sanitize($_POST['reviewer_name'] ?? '');
        $rating       = intval($_POST['rating'] ?? 0);
        $comment      = sanitize($_POST['comment'] ?? '');
        $userId       = $_SESSION['user_id'] ?? null;

        if (!$reviewerName || $rating < 1 || $rating > 5) {
            jsonResponse(['success' => false, 'message' => 'Name and a rating between 1-5 are required.']);
        }

        $db = getDB();
        $db->prepare(
            "INSERT INTO reviews (user_id, reviewer_name, rating, comment, is_approved)
             VALUES (?, ?, ?, ?, 0)"
        )->execute([$userId, $reviewerName, $rating, $comment]);

        jsonResponse(['success' => true, 'message' => 'Thank you for your review! It will appear after approval.']);
        break;

    // ── GET APPROVED REVIEWS ─────────────────────────────────
    case 'reviews':
        $db   = getDB();
        $stmt = $db->prepare(
            "SELECT reviewer_name, rating, comment, created_at
             FROM reviews
             WHERE is_approved = 1
             ORDER BY created_at DESC
             LIMIT 10"
        );
        $stmt->execute();
        jsonResponse(['success' => true, 'reviews' => $stmt->fetchAll()]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
}
