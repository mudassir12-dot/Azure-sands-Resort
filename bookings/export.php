<?php
require_once __DIR__ . '/../includes/common.php';
requireAdmin();

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="bookings_' . date('Ymd') . '.csv"');

$db = getDB();
$rows = $db->query(
    "SELECT b.booking_ref, b.guest_name, b.guest_email, b.guest_city,
            r.room_type, r.room_number, b.check_in, b.nights, b.check_out,
            b.total_price, b.status, b.special_requests, b.created_at
     FROM bookings b JOIN rooms r ON b.room_id = r.id
     ORDER BY b.created_at DESC"
)->fetchAll();

$out = fopen('php://output', 'w');
fputcsv($out, ['Booking Ref','Guest Name','Email','City','Room Type','Room #','Check-In','Nights','Check-Out','Total ($)','Status','Special Requests','Created At']);
foreach ($rows as $row) {
    fputcsv($out, $row);
}
fclose($out);
