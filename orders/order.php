<?php
/**
 * Azure Sands Resort - Food Order API
 * Handles: place order, get menu, get my orders
 */
require_once __DIR__ . '/../includes/common.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── GET MENU ──────────────────────────────────────────────
    case 'menu':
        $db   = getDB();
        $stmt = $db->prepare("SELECT id, item_code, name, description, price, category, image FROM food_items WHERE is_available = 1 ORDER BY category, name");
        $stmt->execute();
        $items = $stmt->fetchAll();
        jsonResponse(['success' => true, 'items' => $items]);
        break;

    // ── PLACE ORDER ───────────────────────────────────────────
    case 'place':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
        }

        $name     = sanitize($_POST['name'] ?? '');
        $room     = sanitize($_POST['Room'] ?? '');
        $food1    = sanitize($_POST['Food1'] ?? '');
        $food2    = sanitize($_POST['Food2'] ?? '');
        $qty1     = max(1, intval($_POST['Q1'] ?? 1));
        $qty2     = max(0, intval($_POST['Q2'] ?? 0));
        $time     = sanitize($_POST['Time'] ?? '');
        $date     = sanitize($_POST['date'] ?? '');

        if (!$name || !$room || !$food1) {
            jsonResponse(['success' => false, 'message' => 'Please fill all required fields (name, room, first item).']);
        }

        // Build delivery datetime — prefer datetime-local, then date+noon, then 1 hour from now
        if ($time && strtotime($time) !== false) {
            $deliveryDatetime = date('Y-m-d H:i:s', strtotime($time));
        } elseif ($date && strtotime($date) !== false) {
            $deliveryDatetime = date('Y-m-d', strtotime($date)) . ' 12:00:00';
        } else {
            $deliveryDatetime = date('Y-m-d H:i:s', strtotime('+1 hour'));
        }

        $db    = getDB();
        $total = 0.0;
        $items = [];

        // Fetch food item 1
        $stmt = $db->prepare("SELECT id, name, price FROM food_items WHERE item_code = ? AND is_available = 1");
        $stmt->execute([$food1]);
        $item1 = $stmt->fetch();
        if (!$item1) {
            jsonResponse(['success' => false, 'message' => 'Selected food item not found.']);
        }
        $items[] = ['id' => $item1['id'], 'qty' => $qty1, 'price' => $item1['price']];
        $total  += $item1['price'] * $qty1;

        // Fetch food item 2 (optional)
        if ($food2 && $qty2 > 0) {
            $stmt->execute([$food2]);
            $item2 = $stmt->fetch();
            if ($item2) {
                $items[] = ['id' => $item2['id'], 'qty' => $qty2, 'price' => $item2['price']];
                $total  += $item2['price'] * $qty2;
            }
        }

        $orderRef = generateRef('OR');
        $userId   = $_SESSION['user_id'] ?? null;

        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                "INSERT INTO food_orders (order_ref, user_id, guest_name, room_number, delivery_datetime, total_amount, status)
                 VALUES (?, ?, ?, ?, ?, ?, 'Pending')"
            );
            $stmt->execute([$orderRef, $userId, $name, $room, $deliveryDatetime, $total]);
            $orderId = $db->lastInsertId();

            $itemStmt = $db->prepare(
                "INSERT INTO food_order_items (order_id, food_item_id, quantity, unit_price, subtotal)
                 VALUES (?, ?, ?, ?, ?)"
            );
            foreach ($items as $item) {
                $subtotal = $item['price'] * $item['qty'];
                $itemStmt->execute([$orderId, $item['id'], $item['qty'], $item['price'], $subtotal]);
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['success' => false, 'message' => 'Order placement failed. Please try again.'], 500);
        }

        jsonResponse([
            'success'   => true,
            'message'   => "Order placed! Reference: {$orderRef}. Expected delivery time noted.",
            'order_ref' => $orderRef,
            'total'     => '$' . number_format($total, 2)
        ]);
        break;

    // ── GET MY ORDERS ─────────────────────────────────────────
    case 'my_orders':
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'message' => 'Please sign in to view your orders.']);
        }
        $db   = getDB();
        $stmt = $db->prepare(
            "SELECT fo.*, 
                    GROUP_CONCAT(fi.name ORDER BY fi.name SEPARATOR ', ') AS items_summary
             FROM food_orders fo
             LEFT JOIN food_order_items foi ON fo.id = foi.order_id
             LEFT JOIN food_items fi ON foi.food_item_id = fi.id
             WHERE fo.user_id = ?
             GROUP BY fo.id
             ORDER BY fo.created_at DESC"
        );
        $stmt->execute([$_SESSION['user_id']]);
        $orders = $stmt->fetchAll();
        jsonResponse(['success' => true, 'orders' => $orders]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
}
