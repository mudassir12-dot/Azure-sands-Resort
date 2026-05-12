<?php
/**
 * Azure Sands Resort – Member Dashboard
 */
require_once __DIR__ . '/../includes/common.php';
requireLogin();

$user = getCurrentUser();
$db   = getDB();

// Membership info
$membership = $db->prepare("SELECT * FROM memberships WHERE user_id = ?");
$membership->execute([$_SESSION['user_id']]);
$membership = $membership->fetch();

// Recent bookings
$bookings = $db->prepare(
    "SELECT b.*, r.room_type, r.room_number FROM bookings b
     JOIN rooms r ON b.room_id = r.id
     WHERE b.user_id = ? ORDER BY b.created_at DESC LIMIT 5"
);
$bookings->execute([$_SESSION['user_id']]);
$bookings = $bookings->fetchAll();

// Recent orders
$orders = $db->prepare(
    "SELECT o.*, GROUP_CONCAT(fi.name SEPARATOR ', ') AS items_list
     FROM food_orders o
     LEFT JOIN food_order_items foi ON o.id = foi.order_id
     LEFT JOIN food_items fi ON foi.food_item_id = fi.id
     WHERE o.user_id = ? GROUP BY o.id ORDER BY o.created_at DESC LIMIT 5"
);
$orders->execute([$_SESSION['user_id']]);
$orders = $orders->fetchAll();

// Tier benefits
$tierBenefits = [
    'Bronze'   => ['0% discount', 'Early check-in requests', 'Member newsletter'],
    'Silver'   => ['5% booking discount', 'Priority reservations', 'Welcome drink'],
    'Gold'     => ['10% booking discount', 'Free breakfast', 'Room upgrade (subject to availability)'],
    'Platinum' => ['15% booking discount', 'Airport transfer', 'Personal butler', 'Suite upgrade'],
];
$tier = $user['membership_tier'];
$nextTierThresholds = ['Bronze'=>100,'Silver'=>500,'Gold'=>1500,'Platinum'=>PHP_INT_MAX];
$nextTier = ['Bronze'=>'Silver','Silver'=>'Gold','Gold'=>'Platinum','Platinum'=>'Platinum'];
$pointsNeeded = max(0, $nextTierThresholds[$tier] - ($user['loyalty_points'] ?? 0));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard – Azure Sands Resort</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body{background:#f0f2f5;padding-top:0;}
        .member-header{background:linear-gradient(135deg,var(--primary),#1a3a8f);color:#fff;padding:40px 0 60px;}
        .member-content{max-width:1000px;margin:-40px auto 40px;padding:0 20px;}
        .member-card{background:#fff;border-radius:16px;padding:28px;box-shadow:0 4px 20px rgba(0,0,0,.08);margin-bottom:24px;}
        .tier-badge{display:inline-flex;align-items:center;gap:8px;background:var(--secondary);color:var(--primary);padding:6px 18px;border-radius:999px;font-weight:700;font-size:14px;}
        .points-bar-wrap{background:var(--light-gray);border-radius:999px;height:10px;margin:12px 0;}
        .points-bar{background:linear-gradient(90deg,var(--secondary),#f0c040);height:10px;border-radius:999px;transition:width .8s;}
        .benefit-item{display:flex;align-items:center;gap:10px;margin-bottom:8px;font-size:14px;}
        .benefit-item i{color:var(--secondary);font-size:18px;}
        .back-btn{display:inline-flex;align-items:center;gap:8px;color:rgba(255,255,255,.8);font-size:14px;margin-bottom:20px;}
        .back-btn:hover{color:#fff;}
        .data-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;}
        @media(max-width:640px){.data-grid{grid-template-columns:1fr;}}
        table{width:100%;border-collapse:collapse;font-size:13px;}
        th{background:var(--primary);color:#fff;padding:10px 14px;text-align:left;}
        td{padding:10px 14px;border-bottom:1px solid var(--light-gray);}
        tr:hover td{background:var(--light-gray);}
        .status{display:inline-block;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600;}
        .status-Pending{background:#fff3cd;color:#856404;}
        .status-Confirmed{background:#cce5ff;color:#004085;}
        .status-Delivered{background:#d4edda;color:#155724;}
        .status-Cancelled{background:#f8d7da;color:#721c24;}
    </style>
</head>
<body>

<div class="member-header">
    <div style="max-width:1000px;margin:0 auto;padding:0 20px;">
        <a href="../index.php" class="back-btn"><i class="bx bx-arrow-back"></i> Back to Resort</a>
        <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
            <div style="width:72px;height:72px;background:var(--secondary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;color:var(--primary);">
                <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
            </div>
            <div>
                <h1 style="font-family:'Playfair Display',serif;font-size:2rem;">Welcome back, <?= htmlspecialchars($user['full_name']) ?>!</h1>
                <p style="opacity:.8;margin-top:4px;"><?= htmlspecialchars($user['email']) ?></p>
                <span class="tier-badge" style="margin-top:10px;display:inline-flex;"><i class="bx bx-diamond"></i> <?= $tier ?> Member</span>
            </div>
        </div>
    </div>
</div>

<div class="member-content">
    <div class="data-grid">
        <!-- Membership Info -->
        <div class="member-card">
            <h2 style="font-family:'Playfair Display',serif;color:var(--primary);margin-bottom:20px;">Loyalty Status</h2>
            <div style="font-size:2.5rem;font-weight:700;color:var(--primary);"><?= number_format($user['loyalty_points']) ?> <span style="font-size:1rem;font-weight:400;color:var(--medium-gray);">points</span></div>
            <?php if ($tier !== 'Platinum'): ?>
                <p style="font-size:13px;color:var(--medium-gray);margin:8px 0;"><?= $pointsNeeded ?> points to reach <?= $nextTier[$tier] ?></p>
                <?php $pct = min(100, ($user['loyalty_points'] / $nextTierThresholds[$tier]) * 100); ?>
                <div class="points-bar-wrap"><div class="points-bar" style="width:<?= $pct ?>%;"></div></div>
            <?php else: ?>
                <p style="font-size:13px;color:var(--success);margin:8px 0;">🏆 You've reached the highest tier!</p>
            <?php endif; ?>

            <h3 style="margin:20px 0 12px;font-size:1rem;color:var(--dark);"><?= $tier ?> Benefits</h3>
            <?php foreach ($tierBenefits[$tier] as $benefit): ?>
                <div class="benefit-item"><i class="bx bx-check-circle"></i> <?= htmlspecialchars($benefit) ?></div>
            <?php endforeach; ?>
        </div>

        <!-- Quick Stats -->
        <div class="member-card">
            <h2 style="font-family:'Playfair Display',serif;color:var(--primary);margin-bottom:20px;">Your Activity</h2>
            <?php
            $totalBookings = $db->prepare("SELECT COUNT(*) FROM bookings WHERE user_id=?"); $totalBookings->execute([$_SESSION['user_id']]); $totalBookings=$totalBookings->fetchColumn();
            $totalOrders   = $db->prepare("SELECT COUNT(*) FROM food_orders WHERE user_id=?"); $totalOrders->execute([$_SESSION['user_id']]); $totalOrders=$totalOrders->fetchColumn();
            $totalSpent    = $db->prepare("SELECT COALESCE(SUM(total_price),0) FROM bookings WHERE user_id=? AND status NOT IN ('Cancelled')"); $totalSpent->execute([$_SESSION['user_id']]); $totalSpent=$totalSpent->fetchColumn();
            ?>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div style="text-align:center;padding:20px;background:var(--light-gray);border-radius:12px;">
                    <div style="font-size:2rem;font-weight:700;color:var(--primary);"><?= $totalBookings ?></div>
                    <div style="font-size:13px;color:var(--medium-gray);">Bookings</div>
                </div>
                <div style="text-align:center;padding:20px;background:var(--light-gray);border-radius:12px;">
                    <div style="font-size:2rem;font-weight:700;color:var(--primary);"><?= $totalOrders ?></div>
                    <div style="font-size:13px;color:var(--medium-gray);">Food Orders</div>
                </div>
                <div style="text-align:center;padding:20px;background:var(--light-gray);border-radius:12px;grid-column:1/-1;">
                    <div style="font-size:2rem;font-weight:700;color:var(--success);">$<?= number_format($totalSpent, 2) ?></div>
                    <div style="font-size:13px;color:var(--medium-gray);">Total Spent (Bookings)</div>
                </div>
            </div>
            <div style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap;">
                <a href="../index.php#rooms-section" class="btn-primary" style="display:inline-flex;align-items:center;gap:8px;padding:10px 22px;background:linear-gradient(135deg,var(--primary),#1a3a8f);color:#fff;border-radius:999px;font-size:14px;font-weight:600;text-decoration:none;">
                    <i class="bx bxs-bed"></i> Book a Room
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Bookings -->
    <div class="member-card">
        <h2 style="font-family:'Playfair Display',serif;color:var(--primary);margin-bottom:20px;">Recent Bookings</h2>
        <?php if (empty($bookings)): ?>
            <p style="color:var(--medium-gray);text-align:center;padding:20px;">No bookings yet. <a href="../index.php" style="color:var(--primary);">Book your first stay!</a></p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table>
                    <thead><tr><th>Ref</th><th>Room</th><th>Check-In</th><th>Nights</th><th>Total</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($b['booking_ref']) ?></strong></td>
                            <td><?= htmlspecialchars($b['room_type']) ?></td>
                            <td><?= htmlspecialchars($b['check_in']) ?></td>
                            <td><?= htmlspecialchars($b['nights']) ?></td>
                            <td><strong>$<?= number_format($b['total_price'], 2) ?></strong></td>
                            <td><span class="status status-<?= htmlspecialchars($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Orders -->
    <div class="member-card">
        <h2 style="font-family:'Playfair Display',serif;color:var(--primary);margin-bottom:20px;">Recent Food Orders</h2>
        <?php if (empty($orders)): ?>
            <p style="color:var(--medium-gray);text-align:center;padding:20px;">No orders yet.</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table>
                    <thead><tr><th>Ref</th><th>Items</th><th>Room</th><th>Total</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($o['order_ref']) ?></strong></td>
                            <td style="font-size:12px;"><?= htmlspecialchars($o['items_list'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($o['room_number']) ?></td>
                            <td><strong>$<?= number_format($o['total_amount'], 2) ?></strong></td>
                            <td><span class="status status-<?= htmlspecialchars($o['status']) ?>"><?= htmlspecialchars($o['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
