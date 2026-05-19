<?php
/**
 * Azure Sands Resort — Overall Resort Report PDF
 * Comprehensive business intelligence report for management.
 */
require_once __DIR__ . '/../includes/common.php';
requireAdmin();

$db = getDB();

// ── Core stats ───────────────────────────────────────────────────────────────
$totalUsers     = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalBookings  = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalOrders    = $db->query("SELECT COUNT(*) FROM food_orders")->fetchColumn();
$totalRevenue   = $db->query("SELECT COALESCE(SUM(total_price),0) FROM bookings WHERE status NOT IN ('Cancelled')")->fetchColumn();
$foodRevenue    = $db->query("SELECT COALESCE(SUM(total_amount),0) FROM food_orders WHERE status NOT IN ('Cancelled')")->fetchColumn();
$totalRooms     = $db->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$availableRooms = $db->query("SELECT COUNT(*) FROM rooms WHERE is_available=1")->fetchColumn();
$totalApps      = $db->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$newApps        = $db->query("SELECT COUNT(*) FROM applications WHERE status='Received'")->fetchColumn();
$totalReviews   = $db->query("SELECT COUNT(*) FROM reviews WHERE is_approved=1")->fetchColumn();
$avgRating      = $db->query("SELECT ROUND(AVG(rating),1) FROM reviews WHERE is_approved=1")->fetchColumn();
$totalSubs      = $db->query("SELECT COUNT(*) FROM newsletter_subscribers")->fetchColumn();

// ── Booking status breakdown ─────────────────────────────────────────────────
$bookingStatus = $db->query(
    "SELECT status, COUNT(*) AS cnt, COALESCE(SUM(total_price),0) AS revenue
     FROM bookings GROUP BY status ORDER BY cnt DESC"
)->fetchAll();

// ── Monthly revenue (last 6 months) ─────────────────────────────────────────
$monthlyRevenue = $db->query(
    "SELECT DATE_FORMAT(created_at,'%b %Y') AS month,
            SUM(total_price) AS revenue, COUNT(*) AS bookings
     FROM bookings WHERE status NOT IN ('Cancelled')
       AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY YEAR(created_at), MONTH(created_at)
     ORDER BY created_at ASC"
)->fetchAll();

// ── Top performing rooms ─────────────────────────────────────────────────────
$topRooms = $db->query(
    "SELECT r.room_type, r.room_number, r.price_per_night,
            COUNT(b.id) AS total_bookings,
            COALESCE(SUM(b.total_price),0) AS revenue
     FROM rooms r
     LEFT JOIN bookings b ON r.id = b.room_id AND b.status NOT IN ('Cancelled')
     GROUP BY r.id ORDER BY revenue DESC LIMIT 6"
)->fetchAll();

// ── Top food items ───────────────────────────────────────────────────────────
$topFood = $db->query(
    "SELECT fi.name, fi.category, fi.price,
            COUNT(foi.id) AS order_count,
            SUM(foi.quantity) AS total_qty,
            SUM(foi.subtotal) AS revenue
     FROM food_items fi
     LEFT JOIN food_order_items foi ON fi.id = foi.food_item_id
     GROUP BY fi.id ORDER BY total_qty DESC LIMIT 6"
)->fetchAll();

// ── Membership tier distribution ─────────────────────────────────────────────
$memberTiers = $db->query(
    "SELECT membership_tier, COUNT(*) AS cnt FROM users GROUP BY membership_tier ORDER BY cnt DESC"
)->fetchAll();

// ── Recent bookings (last 10) ────────────────────────────────────────────────
$recentBookings = $db->query(
    "SELECT b.booking_ref, b.guest_name, r.room_type, b.check_in, b.total_price, b.status
     FROM bookings b JOIN rooms r ON b.room_id = r.id
     ORDER BY b.created_at DESC LIMIT 10"
)->fetchAll();

// ── Recent orders (last 10) ──────────────────────────────────────────────────
$recentOrders = $db->query(
    "SELECT o.order_ref, o.guest_name, o.room_number, o.total_amount, o.status
     FROM food_orders o ORDER BY o.created_at DESC LIMIT 10"
)->fetchAll();

$generatedAt   = date('F j, Y  h:i A');
$totalRevAll   = (float)$totalRevenue + (float)$foodRevenue;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Overall Resort Report — Azure Sands Resort</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial,sans-serif; font-size:11px; color:#1a1a2e; background:#fff; padding:20px; }

        /* ── Print bar ── */
        .print-bar {
            position:fixed; top:0; left:0; right:0; background:#0A2463;
            color:white; padding:10px 20px; display:flex;
            align-items:center; justify-content:space-between;
            z-index:999; box-shadow:0 2px 8px rgba(0,0,0,.3);
        }
        .print-bar span { font-size:13px; font-weight:600; }
        .btn-print,.btn-back {
            padding:7px 18px; border-radius:20px; border:none;
            cursor:pointer; font-size:12px; font-weight:600;
        }
        .btn-print { background:#C9A84C; color:#0A2463; margin-left:10px; }
        .btn-back  { background:rgba(255,255,255,.15); color:white; text-decoration:none; }
        .spacer { height:52px; }

        /* ── Cover ── */
        .cover {
            background:linear-gradient(135deg,#0A2463,#1a3a8f);
            color:white; padding:40px 36px; border-radius:12px;
            margin-bottom:24px; text-align:center;
        }
        .cover h1 { font-size:28px; font-weight:700; letter-spacing:1px; margin-bottom:8px; }
        .cover h2 { font-size:16px; font-weight:400; opacity:.8; margin-bottom:16px; }
        .cover-meta {
            display:inline-flex; gap:32px;
            background:rgba(255,255,255,.12); padding:12px 28px;
            border-radius:8px; margin-top:12px;
        }
        .cover-meta div { text-align:center; }
        .cover-meta .val { font-size:13px; font-weight:700; color:#C9A84C; display:block; }
        .cover-meta .lbl { font-size:10px; opacity:.75; }

        /* ── Section header ── */
        .section-hdr {
            background:#0A2463; color:white;
            padding:8px 14px; border-radius:6px;
            font-size:13px; font-weight:700;
            margin:20px 0 12px;
            display:flex; align-items:center; gap:8px;
        }

        /* ── KPI grid ── */
        .kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
        .kpi {
            border:1px solid #e0e4f0; border-radius:8px;
            padding:14px 16px; text-align:center;
            border-top:3px solid #0A2463;
        }
        .kpi.gold  { border-top-color:#C9A84C; }
        .kpi.green { border-top-color:#28a745; }
        .kpi.red   { border-top-color:#dc3545; }
        .kpi.teal  { border-top-color:#17a2b8; }
        .kpi .val  { font-size:22px; font-weight:700; color:#0A2463; display:block; }
        .kpi.gold .val  { color:#C9A84C; }
        .kpi.green .val { color:#28a745; }
        .kpi.red .val   { color:#dc3545; }
        .kpi.teal .val  { color:#17a2b8; }
        .kpi .lbl  { font-size:10px; color:#6c757d; margin-top:3px; }

        /* ── Two-col layout ── */
        .two-col { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px; }
        .three-col { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; margin-bottom:20px; }

        /* ── Tables ── */
        .tbl { width:100%; border-collapse:collapse; }
        .tbl thead th {
            background:#0A2463; color:white;
            padding:7px 10px; text-align:left;
            font-size:10px; font-weight:600;
        }
        .tbl tbody td { padding:6px 10px; border-bottom:1px solid #eef0f6; font-size:10px; }
        .tbl tbody tr:nth-child(even) td { background:#f8f9fd; }

        /* ── Mini cards ── */
        .card { border:1px solid #e0e4f0; border-radius:8px; overflow:hidden; }
        .card-hdr { background:#f0f4ff; padding:8px 12px; font-size:11px; font-weight:700; color:#0A2463; border-bottom:1px solid #e0e4f0; }

        /* ── Monthly revenue bar chart (CSS only) ── */
        .bar-chart { padding:12px 0; }
        .bar-row { display:flex; align-items:center; gap:8px; margin-bottom:6px; }
        .bar-label { width:60px; font-size:9px; color:#6c757d; text-align:right; flex-shrink:0; }
        .bar-track { flex:1; background:#eef0f6; border-radius:4px; height:16px; }
        .bar-fill  { height:16px; background:linear-gradient(90deg,#0A2463,#1a3a8f); border-radius:4px; min-width:2px; }
        .bar-val   { width:70px; font-size:9px; font-weight:700; color:#0A2463; }

        /* ── Status breakdown ── */
        .status-row { display:flex; align-items:center; justify-content:space-between; padding:5px 0; border-bottom:1px solid #f0f0f0; }
        .status-row:last-child { border-bottom:none; }
        .status-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
        .status-name { flex:1; font-size:10px; margin-left:8px; }
        .status-cnt  { font-size:10px; font-weight:700; color:#0A2463; }
        .status-rev  { font-size:10px; color:#28a745; min-width:70px; text-align:right; }

        /* ── Badges ── */
        .badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:9px; font-weight:600; }
        .badge-Pending,.badge-Received { background:#fff3cd; color:#856404; }
        .badge-Confirmed               { background:#cce5ff; color:#004085; }
        .badge-Checked-In,.badge-Delivering { background:#d1ecf1; color:#0c5460; }
        .badge-Checked-Out,.badge-Delivered { background:#d4edda; color:#155724; }
        .badge-Cancelled               { background:#f8d7da; color:#721c24; }

        /* ── Tier pills ── */
        .tier-pill { display:inline-block; padding:2px 10px; border-radius:10px; font-size:9px; font-weight:700; }
        .tier-Bronze   { background:#f5e6c8; color:#8b6914; }
        .tier-Silver   { background:#e8e8e8; color:#555; }
        .tier-Gold     { background:#fff3cd; color:#856404; }
        .tier-Platinum { background:#d6eaff; color:#004085; }

        /* ── Footer ── */
        .report-footer {
            border-top:2px solid #0A2463; padding-top:12px; margin-top:24px;
            display:flex; justify-content:space-between; font-size:9px; color:#888;
        }

        /* ── Stars ── */
        .stars { color:#f0c040; }

        @media print {
            .print-bar,.spacer { display:none !important; }
            body { padding:8px; }
            .cover,.section-hdr,.kpi,.tbl thead th,.bar-fill,
            .tbl tbody tr:nth-child(even) td,.card-hdr,.badge,.tier-pill {
                -webkit-print-color-adjust:exact; print-color-adjust:exact;
            }
            .two-col,.three-col,.kpi-grid { break-inside:avoid; }
        }
    </style>
</head>
<body>

<!-- Print bar -->
<div class="print-bar">
    <span>📊 Overall Resort Report — <?= $generatedAt ?></span>
    <div>
        <a href="index.php" class="btn-back">← Back to Dashboard</a>
        <button class="btn-print" onclick="window.print()">🖨 Print / Save PDF</button>
    </div>
</div>
<div class="spacer"></div>

<!-- Cover -->
<div class="cover">
    <div style="font-size:11px;opacity:.7;letter-spacing:2px;text-transform:uppercase;margin-bottom:8px;">Management Report</div>
    <h1>⚓ Azure Sands Resort</h1>
    <h2>Overall Business Performance Report</h2>
    <div class="cover-meta">
        <div><span class="val">$<?= number_format($totalRevAll,2) ?></span><span class="lbl">Total Revenue</span></div>
        <div><span class="val"><?= number_format($totalBookings) ?></span><span class="lbl">Bookings</span></div>
        <div><span class="val"><?= number_format($totalOrders) ?></span><span class="lbl">Food Orders</span></div>
        <div><span class="val"><?= number_format($totalUsers) ?></span><span class="lbl">Members</span></div>
        <div><span class="val"><?= $avgRating ?: '—' ?> ★</span><span class="lbl">Avg Rating</span></div>
    </div>
    <div style="margin-top:18px;font-size:10px;opacity:.6;">Generated: <?= $generatedAt ?></div>
</div>

<!-- ══ KPI OVERVIEW ══════════════════════════════════════════════════════════ -->
<div class="section-hdr">📈 Key Performance Indicators</div>
<div class="kpi-grid">
    <div class="kpi gold">
        <span class="val">$<?= number_format($totalRevenue,2) ?></span>
        <div class="lbl">Booking Revenue</div>
    </div>
    <div class="kpi gold">
        <span class="val">$<?= number_format($foodRevenue,2) ?></span>
        <div class="lbl">Food Revenue</div>
    </div>
    <div class="kpi gold">
        <span class="val">$<?= number_format($totalRevAll,2) ?></span>
        <div class="lbl">Combined Revenue</div>
    </div>
    <div class="kpi green">
        <span class="val"><?= number_format($totalUsers) ?></span>
        <div class="lbl">Registered Members</div>
    </div>
    <div class="kpi">
        <span class="val"><?= number_format($totalBookings) ?></span>
        <div class="lbl">Total Bookings</div>
    </div>
    <div class="kpi teal">
        <span class="val"><?= number_format($totalOrders) ?></span>
        <div class="lbl">Food Orders</div>
    </div>
    <div class="kpi">
        <span class="val"><?= $availableRooms ?>/<?= $totalRooms ?></span>
        <div class="lbl">Rooms Available</div>
    </div>
    <div class="kpi <?= $newApps > 0 ? 'red' : 'green' ?>">
        <span class="val"><?= $totalApps ?></span>
        <div class="lbl">Job Applications <?= $newApps > 0 ? "($newApps new)" : '' ?></div>
    </div>
</div>

<!-- ══ BOOKING ANALYSIS ══════════════════════════════════════════════════════ -->
<div class="section-hdr">🛏 Booking Analysis</div>
<div class="two-col">

    <!-- Booking status breakdown -->
    <div class="card">
        <div class="card-hdr">Booking Status Breakdown</div>
        <div style="padding:12px;">
            <?php
            $dotColors = ['Pending'=>'#f0c040','Confirmed'=>'#1a6ca8','Checked In'=>'#17a2b8','Checked Out'=>'#28a745','Cancelled'=>'#dc3545'];
            foreach($bookingStatus as $row):
                $dot = $dotColors[$row['status']] ?? '#888';
            ?>
            <div class="status-row">
                <div class="status-dot" style="background:<?= $dot ?>;"></div>
                <div class="status-name"><?= htmlspecialchars($row['status']) ?></div>
                <div class="status-cnt"><?= $row['cnt'] ?> bookings</div>
                <div class="status-rev">$<?= number_format($row['revenue'],2) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Monthly revenue chart -->
    <div class="card">
        <div class="card-hdr">Monthly Booking Revenue (Last 6 Months)</div>
        <div class="bar-chart" style="padding:12px;">
            <?php
            $maxRev = !empty($monthlyRevenue) ? max(array_column($monthlyRevenue,'revenue')) : 1;
            foreach($monthlyRevenue as $m):
                $pct = $maxRev > 0 ? round(($m['revenue'] / $maxRev) * 100) : 0;
            ?>
            <div class="bar-row">
                <div class="bar-label"><?= htmlspecialchars($m['month']) ?></div>
                <div class="bar-track">
                    <div class="bar-fill" style="width:<?= $pct ?>%;"></div>
                </div>
                <div class="bar-val">$<?= number_format($m['revenue'],0) ?></div>
            </div>
            <?php endforeach; ?>
            <?php if(empty($monthlyRevenue)): ?>
                <p style="color:#888;font-size:10px;text-align:center;padding:10px;">No revenue data yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ══ TOP PERFORMING ROOMS ══════════════════════════════════════════════════ -->
<div class="section-hdr">🏆 Top Performing Rooms</div>
<table class="tbl" style="margin-bottom:20px;">
    <thead>
        <tr><th>Room Type</th><th>Room #</th><th>Price/Night</th><th>Total Bookings</th><th>Revenue Generated</th></tr>
    </thead>
    <tbody>
    <?php foreach($topRooms as $r): ?>
        <tr>
            <td><strong><?= htmlspecialchars($r['room_type']) ?></strong></td>
            <td><?= htmlspecialchars($r['room_number']) ?></td>
            <td>$<?= number_format($r['price_per_night'],2) ?>/night</td>
            <td style="text-align:center;"><?= $r['total_bookings'] ?></td>
            <td style="color:#28a745;font-weight:700;">$<?= number_format($r['revenue'],2) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<!-- ══ FOOD ORDERING ANALYSIS ════════════════════════════════════════════════ -->
<div class="section-hdr">🍽 Food Ordering Analysis</div>
<table class="tbl" style="margin-bottom:20px;">
    <thead>
        <tr><th>Menu Item</th><th>Category</th><th>Price</th><th>Times Ordered</th><th>Total Qty Sold</th><th>Revenue</th></tr>
    </thead>
    <tbody>
    <?php foreach($topFood as $f): ?>
        <tr>
            <td><strong><?= htmlspecialchars($f['name']) ?></strong></td>
            <td><?= htmlspecialchars($f['category']) ?></td>
            <td>$<?= number_format($f['price'],2) ?></td>
            <td style="text-align:center;"><?= $f['order_count'] ?: 0 ?></td>
            <td style="text-align:center;"><?= $f['total_qty'] ?: 0 ?></td>
            <td style="color:#28a745;font-weight:700;">$<?= number_format($f['revenue'] ?? 0,2) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<!-- ══ MEMBERS, REVIEWS, SUBSCRIBERS ═════════════════════════════════════════ -->
<div class="section-hdr">👥 Membership & Guest Satisfaction</div>
<div class="three-col">

    <!-- Membership tiers -->
    <div class="card">
        <div class="card-hdr">Membership Tier Distribution</div>
        <div style="padding:12px;">
            <?php foreach($memberTiers as $t): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <span class="tier-pill tier-<?= $t['membership_tier'] ?>"><?= $t['membership_tier'] ?></span>
                <span style="font-size:11px;font-weight:700;color:#0A2463;"><?= $t['cnt'] ?> members</span>
            </div>
            <?php endforeach; ?>
            <?php if(empty($memberTiers)): ?><p style="color:#888;font-size:10px;">No members yet.</p><?php endif; ?>
        </div>
    </div>

    <!-- Guest reviews -->
    <div class="card">
        <div class="card-hdr">Guest Reviews</div>
        <div style="padding:12px;text-align:center;">
            <div style="font-size:36px;font-weight:700;color:#0A2463;"><?= $avgRating ?: '—' ?></div>
            <div class="stars" style="font-size:20px;margin:6px 0;">
                <?php $avg = floatval($avgRating);
                for($i=1;$i<=5;$i++) echo $i<=$avg ? '★' : '☆'; ?>
            </div>
            <div style="font-size:10px;color:#6c757d;">Based on <?= $totalReviews ?> approved reviews</div>
        </div>
    </div>

    <!-- Newsletter -->
    <div class="card">
        <div class="card-hdr">Newsletter & Engagement</div>
        <div style="padding:12px;text-align:center;">
            <div style="font-size:36px;font-weight:700;color:#17a2b8;"><?= number_format($totalSubs) ?></div>
            <div style="font-size:10px;color:#6c757d;margin-top:6px;">Newsletter Subscribers</div>
            <div style="margin-top:12px;padding-top:12px;border-top:1px solid #eef0f6;">
                <div style="font-size:20px;font-weight:700;color:#0A2463;"><?= $totalApps ?></div>
                <div style="font-size:10px;color:#6c757d;">Job Applications</div>
                <?php if($newApps): ?>
                <div style="margin-top:4px;font-size:9px;background:#f8d7da;color:#721c24;padding:3px 8px;border-radius:10px;display:inline-block;"><?= $newApps ?> awaiting review</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ══ RECENT ACTIVITY ════════════════════════════════════════════════════════ -->
<div class="section-hdr">🕐 Recent Activity</div>
<div class="two-col">

    <div class="card">
        <div class="card-hdr">Latest 10 Bookings</div>
        <table class="tbl">
            <thead><tr><th>Ref</th><th>Guest</th><th>Room</th><th>Check-In</th><th>Total</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach($recentBookings as $b): ?>
            <tr>
                <td style="font-size:9px;"><?= htmlspecialchars($b['booking_ref']) ?></td>
                <td><?= htmlspecialchars($b['guest_name']) ?></td>
                <td style="font-size:9px;"><?= htmlspecialchars($b['room_type']) ?></td>
                <td style="font-size:9px;"><?= $b['check_in'] ?></td>
                <td>$<?= number_format($b['total_price'],2) ?></td>
                <td><span class="badge badge-<?= str_replace(' ','-',$b['status']) ?>"><?= $b['status'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($recentBookings)): ?>
            <tr><td colspan="6" style="text-align:center;color:#888;padding:10px;">No bookings yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-hdr">Latest 10 Food Orders</div>
        <table class="tbl">
            <thead><tr><th>Ref</th><th>Guest</th><th>Room</th><th>Total</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach($recentOrders as $o): ?>
            <tr>
                <td style="font-size:9px;"><?= htmlspecialchars($o['order_ref']) ?></td>
                <td><?= htmlspecialchars($o['guest_name']) ?></td>
                <td style="text-align:center;"><?= htmlspecialchars($o['room_number']) ?></td>
                <td>$<?= number_format($o['total_amount'],2) ?></td>
                <td><span class="badge badge-<?= str_replace([' '],'-',$o['status']) ?>"><?= $o['status'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($recentOrders)): ?>
            <tr><td colspan="5" style="text-align:center;color:#888;padding:10px;">No orders yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Footer -->
<div class="report-footer">
    <span>Azure Sands Resort — Confidential Management Report</span>
    <span>Combined Revenue: $<?= number_format($totalRevAll,2) ?> &nbsp;|&nbsp; Members: <?= $totalUsers ?> &nbsp;|&nbsp; Bookings: <?= $totalBookings ?></span>
    <span>Generated: <?= $generatedAt ?></span>
</div>

</body>
</html>
