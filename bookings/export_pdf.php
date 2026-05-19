<?php
/**
 * Azure Sands Resort — Bookings PDF Report
 * Renders a print-ready HTML page. Browser's Ctrl+P / Print → Save as PDF
 * Works on every host — zero dependencies.
 */
require_once __DIR__ . '/../includes/common.php';
requireAdmin();

$db = getDB();

// Filters passed from admin bookings page
$status = sanitize($_GET['status'] ?? '');
$from   = sanitize($_GET['from']   ?? '');
$to     = sanitize($_GET['to']     ?? '');

$where  = [];
$params = [];
if ($status) { $where[] = 'b.status = ?';     $params[] = $status; }
if ($from)   { $where[] = 'b.check_in >= ?';  $params[] = $from;   }
if ($to)     { $where[] = 'b.check_in <= ?';  $params[] = $to;     }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$bookings = $db->prepare(
    "SELECT b.booking_ref, b.guest_name, b.guest_email, b.guest_city,
            r.room_type, r.room_number, b.check_in, b.check_out,
            b.nights, b.total_price, b.status, b.special_requests, b.created_at
     FROM bookings b
     JOIN rooms r ON b.room_id = r.id
     $whereSql
     ORDER BY b.created_at DESC"
);
$bookings->execute($params);
$rows = $bookings->fetchAll();

// Summary stats
$totalRevenue  = array_sum(array_column(
    array_filter($rows, fn($r) => $r['status'] !== 'Cancelled'),
    'total_price'
));
$statusCounts = array_count_values(array_column($rows, 'status'));

$generatedAt = date('F j, Y  h:i A');
$filterLabel = $status ? " — Status: $status" : '';
if ($from || $to) $filterLabel .= ' | ' . ($from ?: '—') . ' to ' . ($to ?: 'today');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bookings Report — Azure Sands Resort</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 11px;
            color: #1a1a2e;
            background: #fff;
            padding: 20px;
        }

        /* ── Header ── */
        .report-header {
            background: linear-gradient(135deg, #0A2463, #1a3a8f);
            color: white;
            padding: 24px 28px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .report-header h1 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
        .report-header p  { font-size: 11px; opacity: .8; }
        .report-header .logo-text {
            font-size: 13px; font-weight: 600;
            background: rgba(255,255,255,.15);
            padding: 6px 14px; border-radius: 20px;
            text-align: center;
        }

        /* ── Summary cards ── */
        .summary-row {
            display: flex; gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .summary-card {
            flex: 1; min-width: 120px;
            border: 1px solid #e0e4f0;
            border-radius: 8px;
            padding: 12px 16px;
            text-align: center;
        }
        .summary-card .val { font-size: 20px; font-weight: 700; color: #0A2463; }
        .summary-card .lbl { font-size: 10px; color: #6c757d; margin-top: 2px; }
        .summary-card.gold .val { color: #C9A84C; }
        .summary-card.green .val { color: #28a745; }

        /* ── Filter info ── */
        .filter-info {
            background: #f0f4ff;
            border-left: 4px solid #0A2463;
            padding: 8px 14px;
            border-radius: 0 6px 6px 0;
            margin-bottom: 16px;
            font-size: 10px;
            color: #444;
        }

        /* ── Table ── */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead th {
            background: #0A2463;
            color: white;
            padding: 8px 10px;
            text-align: left;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .3px;
        }
        tbody td {
            padding: 7px 10px;
            border-bottom: 1px solid #eef0f6;
            font-size: 10px;
            vertical-align: middle;
        }
        tbody tr:nth-child(even) td { background: #f8f9fd; }
        tbody tr:hover td { background: #eef2ff; }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 600;
        }
        .badge-Pending       { background:#fff3cd; color:#856404; }
        .badge-Confirmed     { background:#cce5ff; color:#004085; }
        .badge-Checked-In    { background:#d1ecf1; color:#0c5460; }
        .badge-Checked-Out   { background:#d4edda; color:#155724; }
        .badge-Cancelled     { background:#f8d7da; color:#721c24; }

        /* ── Footer ── */
        .report-footer {
            border-top: 2px solid #0A2463;
            padding-top: 12px;
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            color: #888;
        }

        /* ── Print button (hidden when printing) ── */
        .print-bar {
            position: fixed;
            top: 0; left: 0; right: 0;
            background: #0A2463;
            color: white;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 999;
            box-shadow: 0 2px 8px rgba(0,0,0,.3);
        }
        .print-bar span { font-size: 13px; font-weight: 600; }
        .btn-print, .btn-back {
            padding: 7px 18px;
            border-radius: 20px;
            border: none;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }
        .btn-print { background: #C9A84C; color: #0A2463; margin-left: 10px; }
        .btn-back  { background: rgba(255,255,255,.15); color: white; text-decoration:none; }
        .btn-print:hover { background: #b8961f; }

        .spacer { height: 52px; } /* offset for fixed print-bar */

        /* ── Filter bar (hidden on print) ── */
        .filter-bar-wrap {
            background: #f0f4ff;
            border: 1px solid #d0d8f0;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 18px;
        }
        .filter-bar-form {
            display: flex; align-items: center;
            gap: 14px; flex-wrap: wrap;
        }
        .filter-ctrl {
            display: block; margin-top: 3px;
            padding: 5px 10px; border: 1px solid #ccd;
            border-radius: 6px; font-size: 11px;
            font-family: inherit;
        }
        .btn-apply {
            padding: 7px 18px; background: #0A2463; color: #fff;
            border: none; border-radius: 20px; font-size: 11px;
            font-weight: 600; cursor: pointer; margin-top: 18px;
        }
        .btn-apply:hover { background: #1a3a8f; }

        /* ── Print media ── */
        @media print {
            .print-bar, .spacer, .filter-bar-wrap { display: none !important; }
            body       { padding: 10px; }
            .report-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            thead th   { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .badge     { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            tbody tr:nth-child(even) td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<!-- Print bar (hidden on print) -->
<div class="print-bar">
    <span>📄 Bookings Report — <?= count($rows) ?> records</span>
    <div>
        <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="btn-back" style="font-size:11px;">↺ Reset</a>
        <a href="../admin/bookings.php" class="btn-back">← Back to Admin</a>
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
                <?php foreach(['Pending','Confirmed','Checked In','Checked Out','Cancelled'] as $s): ?>
                    <option value="<?=$s?>" <?=$status===$s?'selected':''?>><?=$s?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label style="font-size:11px;">Check-in From
            <input type="date" name="from" value="<?=htmlspecialchars($from)?>" class="filter-ctrl">
        </label>
        <label style="font-size:11px;">Check-in To
            <input type="date" name="to" value="<?=htmlspecialchars($to)?>" class="filter-ctrl">
        </label>
        <button type="submit" class="btn-apply">Apply Filter</button>
    </form>
</div>

<!-- Report Header -->
<div class="report-header">
    <div>
        <h1>Bookings Report</h1>
        <p>Azure Sands Resort &nbsp;|&nbsp; Generated: <?= $generatedAt ?></p>
        <?php if ($filterLabel): ?>
            <p style="margin-top:4px;opacity:.7;font-size:10px;">Filters: <?= htmlspecialchars($filterLabel) ?></p>
        <?php endif; ?>
    </div>
    <div class="logo-text">🏖 Azure Sands<br>Resort</div>
</div>

<!-- Summary Cards -->
<div class="summary-row">
    <div class="summary-card">
        <div class="val"><?= count($rows) ?></div>
        <div class="lbl">Total Bookings</div>
    </div>
    <div class="summary-card gold">
        <div class="val">$<?= number_format($totalRevenue, 2) ?></div>
        <div class="lbl">Total Revenue</div>
    </div>
    <?php foreach ($statusCounts as $s => $c): ?>
    <div class="summary-card <?= $s === 'Confirmed' ? 'green' : '' ?>">
        <div class="val"><?= $c ?></div>
        <div class="lbl"><?= $s ?></div>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($filterLabel): ?>
<div class="filter-info">Applied filters: <?= htmlspecialchars(ltrim($filterLabel, ' — |')) ?></div>
<?php endif; ?>

<!-- Bookings Table -->
<table>
    <thead>
        <tr>
            <th>Booking Ref</th>
            <th>Guest Name</th>
            <th>Email</th>
            <th>Room</th>
            <th>Check-In</th>
            <th>Check-Out</th>
            <th>Nights</th>
            <th>Total</th>
            <th>Status</th>
            <th>Booked On</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($rows)): ?>
        <tr><td colspan="10" style="text-align:center;padding:20px;color:#888;">No bookings found for the selected filters.</td></tr>
    <?php else: foreach ($rows as $r): ?>
        <tr>
            <td><strong><?= htmlspecialchars($r['booking_ref']) ?></strong></td>
            <td><?= htmlspecialchars($r['guest_name']) ?></td>
            <td style="font-size:9px;"><?= htmlspecialchars($r['guest_email']) ?></td>
            <td><?= htmlspecialchars($r['room_type']) ?><br><span style="color:#888;font-size:9px;"><?= $r['room_number'] ?></span></td>
            <td><?= htmlspecialchars($r['check_in']) ?></td>
            <td><?= htmlspecialchars($r['check_out']) ?></td>
            <td style="text-align:center;"><?= $r['nights'] ?></td>
            <td><strong>$<?= number_format($r['total_price'], 2) ?></strong></td>
            <td><span class="badge badge-<?= str_replace(' ','-',$r['status']) ?>"><?= $r['status'] ?></span></td>
            <td style="font-size:9px;"><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<!-- Footer -->
<div class="report-footer">
    <span>Azure Sands Resort — Confidential Report</span>
    <span>Total Records: <?= count($rows) ?> &nbsp;|&nbsp; Revenue: $<?= number_format($totalRevenue,2) ?></span>
    <span>Generated: <?= $generatedAt ?></span>
</div>

</body>
</html>
