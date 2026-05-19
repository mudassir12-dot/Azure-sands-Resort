<?php
$pageTitle = 'Bookings Management';
require_once 'header.php';

$msg = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $bookingId = intval($_POST['booking_id']);
    $newStatus = sanitize($_POST['status']);
    $allowed   = ['Pending','Confirmed','Checked In','Checked Out','Cancelled'];
    if (in_array($newStatus, $allowed)) {
        $db->prepare("UPDATE bookings SET status=? WHERE id=?")->execute([$newStatus, $bookingId]);
        $msg = 'Booking status updated.';
    }
}

// Filters
$statusFilter = sanitize($_GET['status'] ?? '');
$search       = sanitize($_GET['q'] ?? '');
$page         = max(1, intval($_GET['page'] ?? 1));
$perPage      = 15;
$offset       = ($page - 1) * $perPage;

$where  = [];
$params = [];
if ($statusFilter) { $where[] = 'b.status = ?'; $params[] = $statusFilter; }
if ($search)       { $where[] = '(b.booking_ref LIKE ? OR b.guest_name LIKE ? OR b.guest_email LIKE ?)'; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = $db->prepare("SELECT COUNT(*) FROM bookings b $whereSql");
$total->execute($params);
$totalRows = $total->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$stmt = $db->prepare(
    "SELECT b.*, r.room_type, r.room_number
     FROM bookings b JOIN rooms r ON b.room_id = r.id
     $whereSql ORDER BY b.created_at DESC LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
$statuses = ['Pending','Confirmed','Checked In','Checked Out','Cancelled'];
?>

<?php if($msg): ?><div class="alert alert-success"><i class="bx bx-check-circle"></i><?=htmlspecialchars($msg)?></div><?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Bookings (<?=number_format($totalRows)?>)</h3>
        <a href="../bookings/export_pdf.php<?= $statusFilter || $search ? '?status='.urlencode($statusFilter).'&q='.urlencode($search) : '' ?>"
           target="_blank" class="btn btn-danger btn-sm">
            <i class="bx bx-file-pdf"></i> Export PDF
        </a>
    </div>
    <div class="card-body">
        <form method="GET" class="filter-bar">
            <input type="text" name="q" class="search-input" placeholder="Search ref, name, email…" value="<?=htmlspecialchars($search)?>">
            <select name="status" class="filter-select">
                <option value="">All Statuses</option>
                <?php foreach($statuses as $s): ?>
                    <option value="<?=$s?>" <?=$statusFilter===$s?'selected':''?>><?=$s?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-search"></i> Filter</button>
            <?php if($search||$statusFilter): ?><a href="bookings.php" class="btn btn-sm btn-outline">Clear</a><?php endif; ?>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Ref</th><th>Guest</th><th>Room</th><th>Check-In</th><th>Nights</th><th>Check-Out</th><th>Total</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if(empty($bookings)): ?>
                    <tr><td colspan="9"><div class="empty-state"><i class="bx bx-calendar-x"></i>No bookings found.</div></td></tr>
                <?php else: foreach($bookings as $b): ?>
                    <tr>
                        <td><strong><?=htmlspecialchars($b['booking_ref'])?></strong></td>
                        <td>
                            <?=htmlspecialchars($b['guest_name'])?><br>
                            <small class="text-muted"><?=htmlspecialchars($b['guest_email'])?></small>
                        </td>
                        <td><?=htmlspecialchars($b['room_type'])?><br><small><?=htmlspecialchars($b['room_number'])?></small></td>
                        <td><?=htmlspecialchars($b['check_in'])?></td>
                        <td><?=htmlspecialchars($b['nights'])?></td>
                        <td><?=htmlspecialchars($b['check_out'])?></td>
                        <td><strong>$<?=number_format($b['total_price'],2)?></strong></td>
                        <td><span class="status status-<?=str_replace(' ','-',$b['status'])?>"><?=htmlspecialchars($b['status'])?></span></td>
                        <td>
                            <form method="POST" style="display:inline-flex;gap:6px;align-items:center;">
                                <input type="hidden" name="booking_id" value="<?=$b['id']?>">
                                <select name="status" class="filter-select" style="padding:5px 10px;border-radius:6px;font-size:12px;">
                                    <?php foreach($statuses as $s): ?>
                                        <option value="<?=$s?>" <?=$b['status']===$s?'selected':''?>><?=$s?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-primary btn-sm">Save</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($totalPages > 1): ?>
        <div class="pagination">
            <?php for($i=1;$i<=$totalPages;$i++): ?>
                <a href="?page=<?=$i?>&q=<?=urlencode($search)?>&status=<?=urlencode($statusFilter)?>"
                   class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
