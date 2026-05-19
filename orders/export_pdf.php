<?php
/**
 * Azure Sands Resort — Food Orders PDF Report
 */
require_once __DIR__ . '/../includes/common.php';
requireAdmin();

$db = getDB();

$status = sanitize($_GET['status'] ?? '');
$from   = sanitize($_GET['from']   ?? '');
$to     = sanitize($_GET['to']     ?? '');

$where  = [];
$params = [];
if ($status) { $where[] = 'o.status = ?';       $params[] = $status; }
if ($from)   { $where[] = 'o.created_at >= ?';  $params[] = $from . ' 00:00:00'; }
if ($to)     { $where[] = 'o.created_at <= ?';  $params[] = $to   . ' 23:59:59'; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare(
    "SELECT o.order_ref, o.guest_name, o.room_number, o.delivery_datetime,
            o.total_amount, o.status, o.created_at,
            GROUP_CONCAT(fi.name, ' x', foi.quantity ORDER BY fi.name SEPARATOR ', ') AS items_detail
     FROM food_orders o
     LEFT JOIN food_order_items foi ON o.id = foi.order_id
     LEFT JOIN food_items fi        ON foi.food_item_id = fi.id
     $whereSql
     GROUP BY o.id
     ORDER BY o.created_at DESC"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$totalRevenue = array_sum(array_column(
    array_filter($rows, fn($r) => $r['status'] !== 'Cancelled'),
    'total_amount'
));
$statusCounts   = array_count_values(array_column($rows, 'status'));
$generatedAt    = date('F j, Y  h:i A');
$filterLabel    = $status ? "Status: $status" : '';
if ($from || $to) $filterLabel .= ($filterLabel ? ' | ' : '') . ($from ?: '—') . ' to ' . ($to ?: 'today');

// Top items
$topItems = $db->query(
    "SELECT fi.name, SUM(foi.quantity) AS qty, SUM(foi.subtotal) AS revenue
     FROM food_order_items foi
     JOIN food_items fi ON foi.food_item_id = fi.id
     GROUP BY fi.id ORDER BY qty DESC LIMIT 5"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Food Orders Report — Azure Sands Resort</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial,sans-serif; font-size:11px; color:#1a1a2e; background:#fff; padding:20px; }

        .report-header {
            background: linear-gradient(135deg, #0A2463, #1a3a8f);
            color: white; padding: 24px 28px; border-radius: 8px;
            margin-bottom: 20px; display: flex;
            justify-content: space-between; align-items: center;
        }
        .report-header h1 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
        .report-header p  { font-size: 11px; opacity: .8; }
        .logo-text {
            font-size:13px; font-weight:600;
            background:rgba(255,255,255,.15);
            padding:6px 14px; border-radius:20px; text-align:center;
        }

        .summary-row { display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
        .summary-card {
            flex:1; min-width:120px; border:1px solid #e0e4f0;
            border-radius:8px; padding:12px 16px; text-align:center;
        }
        .summary-card .val { font-size:20px; font-weight:700; color:#0A2463; }
        .summary-card .lbl { font-size:10px; color:#6c757d; margin-top:2px; }
        .summary-card.gold .val  { color:#C9A84C; }
        .summary-card.green .val { color:#28a745; }

        .two-col { display:grid; grid-template-columns:1fr 280px; gap:16px; margin-bottom:20px; }
        .top-items h3 { font-size:12px; color:#0A2463; margin-bottom:10px; padding-bottom:6px; border-bottom:2px solid #C9A84C; }
        .top-items table { width:100%; }
        .top-items th { background:#0A2463; color:#fff; padding:6px 8px; font-size:10px; text-align:left; }
        .top-items td { padding:5px 8px; border-bottom:1px solid #eef0f6; font-size:10px; }
        .top-items tr:nth-child(even) td { background:#f8f9fd; }

        table { width:100%; border-collapse:collapse; margin-bottom:20px; }
        thead th {
            background:#0A2463; color:white;
            padding:8px 10px; text-align:left;
            font-size:10px; font-weight:600;
        }
        tbody td { padding:7px 10px; border-bottom:1px solid #eef0f6; font-size:10px; vertical-align:middle; }
        tbody tr:nth-child(even) td { background:#f8f9fd; }

        .badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:9px; font-weight:600; }
        .badge-Pending          { background:#fff3cd; color:#856404; }
        .badge-Preparing        { background:#ffeeba; color:#856404; }
        .badge-Out-for-Delivery { background:#d1ecf1; color:#0c5460; }
        .badge-Delivered        { background:#d4edda; color:#155724; }
        .badge-Cancelled        { background:#f8d7da; color:#721c24; }

        .report-footer {
            border-top:2px solid #0A2463; padding-top:12px;
            display:flex; justify-content:space-between;
            font-size:9px; color:#888;
        }
        .filter-info {
            background:#f0f4ff; border-left:4px solid #C9A84C;
            padding:8px 14px; border-radius:0 6px 6px 0;
            margin-bottom:16px; font-size:10px; color:#444;
        }

        .print-bar {
            position:fixed; top:0; left:0; right:0;
            background:#0A2463; color:white;
            padding:10px 20px; display:flex;
            align-items:center; justify-content:space-between;
            z-index:999; box-shadow:0 2px 8px rgba(0,0,0,.3);
        }
        .print-bar span { font-size:13px; font-weight:600; }
        .btn-print, .btn-back {
            padding:7px 18px; border-radius:20px; border:none;
            cursor:pointer; font-size:12px; font-weight:600;
        }
        .btn-print { background:#C9A84C; color:#0A2463; margin-left:10px; }
        .btn-back  { background:rgba(255,255,255,.15); color:white; text-decoration:none; }
        .spacer { height:52px; }

        /* ── Filter bar ── */
        .filter-bar-wrap {
            background:#f0f4ff; border:1px solid #d0d8f0;
            border-radius:8px; padding:12px 16px; margin-bottom:18px;
        }
        .filter-bar-form { display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
        .filter-ctrl {
            display:block; margin-top:3px; padding:5px 10px;
            border:1px solid #ccd; border-radius:6px;
            font-size:11px; font-family:inherit;
        }
        .btn-apply {
            padding:7px 18px; background:#0A2463; color:#fff;
            border:none; border-radius:20px; font-size:11px;
            font-weight:600; cursor:pointer; margin-top:18px;
        }
        .btn-apply:hover { background:#1a3a8f; }

        @media print {
            .print-bar,.spacer,.filter-bar-wrap { display:none !important; }
            body { padding:10px; }
            .report-header, thead th, .badge, tbody tr:nth-child(even) td,
            .top-items th, .top-items tr:nth-child(even) td {
                -webkit-print-color-adjust:exact; print-color-adjust:exact;
            }
        }
    </style>
</head>
<body>

<div class="print-bar">
    <span>🍽 Food Orders Report — <?= count($rows) ?> orders</span>
    <div>
        <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="btn-back" style="font-size:11px;">↺ Reset</a>
        <a href="../admin/orders.php" class="btn-back">← Back to Admin</a>
        <button class="btn-print" onclick="window.print()">🖨 Print / Save PDF</button>
    </div>
</div>
<div class="spacer"></div>

<!-- Filter bar (hidden on print) -->
<div class="filter-bar-wrap">
    <form method="GET" class="filter-bar-form">
        <span style="font-weight:600;font-size:12px;color:#0A2463;">Filter Report:</span>
        <label style="font-size:11px;">Status
            <select name="status" class="filter-ctrl">
                <option value="">All</option>
                <?php foreach(['Pending','Preparing','Out for Delivery','Delivered','Cancelled'] as $s): ?>
                    <option value="<?=$s?>" <?=$status===$s?'selected':''?>><?=$s?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label style="font-size:11px;">Date From
            <input type="date" name="from" value="<?=htmlspecialchars($from)?>" class="filter-ctrl">
        </label>
        <label style="font-size:11px;">Date To
            <input type="date" name="to" value="<?=htmlspecialchars($to)?>" class="filter-ctrl">
        </label>
        <button type="submit" class="btn-apply">Apply Filter</button>
    </form>
</div>

<div class="report-header">
    <div>
        <h1>Food Orders Report</h1>
        <p>Azure Sands Resort &nbsp;|&nbsp; Generated: <?= $generatedAt ?></p>
        <?php if($filterLabel): ?><p style="margin-top:4px;opacity:.7;font-size:10px;">Filters: <?= htmlspecialchars($filterLabel) ?></p><?php endif; ?>
    </div>
    <div class="logo-text">🏖 Azure Sands<br>Resort</div>
</div>

<div class="summary-row">
    <div class="summary-card">
        <div class="val"><?= count($rows) ?></div>
        <div class="lbl">Total Orders</div>
    </div>
    <div class="summary-card gold">
        <div class="val">$<?= number_format($totalRevenue, 2) ?></div>
        <div class="lbl">Total Revenue</div>
    </div>
    <?php foreach($statusCounts as $s => $c): ?>
    <div class="summary-card <?= $s === 'Delivered' ? 'green' : '' ?>">
        <div class="val"><?= $c ?></div>
        <div class="lbl"><?= $s ?></div>
    </div>
    <?php endforeach; ?>
</div>

<?php if($filterLabel): ?>
<div class="filter-info">Applied filters: <?= htmlspecialchars($filterLabel) ?></div>
<?php endif; ?>

<div class="two-col">
    <!-- Orders table -->
    <div>
        <table>
            <thead>
                <tr>
                    <th>Order Ref</th>
                    <th>Guest</th>
                    <th>Room</th>
                    <th>Items Ordered</th>
                    <th>Total</th>
                    <th>Delivery</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php if(empty($rows)): ?>
                <tr><td colspan="8" style="text-align:center;padding:20px;color:#888;">No orders found.</td></tr>
            <?php else: foreach($rows as $r): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($r['order_ref']) ?></strong></td>
                    <td><?= htmlspecialchars($r['guest_name']) ?></td>
                    <td style="text-align:center;"><?= htmlspecialchars($r['room_number']) ?></td>
                    <td style="font-size:9px;max-width:160px;"><?= htmlspecialchars($r['items_detail'] ?? '—') ?></td>
                    <td><strong>$<?= number_format($r['total_amount'],2) ?></strong></td>
                    <td style="font-size:9px;"><?= date('M d, H:i', strtotime($r['delivery_datetime'])) ?></td>
                    <td><span class="badge badge-<?= str_replace([' '],'-',$r['status']) ?>"><?= $r['status'] ?></span></td>
                    <td style="font-size:9px;"><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Top items sidebar -->
    <?php if(!empty($topItems)): ?>
    <div class="top-items">
        <h3>🏆 Top Ordered Items</h3>
        <table>
            <thead><tr><th>Item</th><th>Qty</th><th>Revenue</th></tr></thead>
            <tbody>
            <?php foreach($topItems as $i => $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td style="text-align:center;"><?= $item['qty'] ?></td>
                <td>$<?= number_format($item['revenue'],2) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div class="report-footer">
    <span>Azure Sands Resort — Confidential Report</span>
    <span>Total Orders: <?= count($rows) ?> &nbsp;|&nbsp; Revenue: $<?= number_format($totalRevenue,2) ?></span>
    <span>Generated: <?= $generatedAt ?></span>
</div>

</body>
</html>
