<?php
$pageTitle = 'Food Orders Management';
require_once 'header.php';

$msg = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId   = intval($_POST['order_id']);
    $newStatus = sanitize($_POST['status']);
    $allowed   = ['Pending','Preparing','Out for Delivery','Delivered','Cancelled'];
    if (in_array($newStatus, $allowed)) {
        $db->prepare("UPDATE food_orders SET status=? WHERE id=?")->execute([$newStatus, $orderId]);
        $msg = 'Order status updated.';
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
if ($statusFilter) { $where[] = 'o.status = ?'; $params[] = $statusFilter; }
if ($search)       { $where[] = '(o.order_ref LIKE ? OR o.guest_name LIKE ? OR o.room_number LIKE ?)'; $params = array_merge($params,["%$search%","%$search%","%$search%"]); }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = $db->prepare("SELECT COUNT(*) FROM food_orders o $whereSql");
$total->execute($params);
$totalRows  = $total->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$stmt = $db->prepare(
    "SELECT o.*,
            GROUP_CONCAT(fi.name,' x',foi.quantity ORDER BY fi.name SEPARATOR ' | ') AS items_detail
     FROM food_orders o
     LEFT JOIN food_order_items foi ON o.id = foi.order_id
     LEFT JOIN food_items fi ON foi.food_item_id = fi.id
     $whereSql GROUP BY o.id ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$orders   = $stmt->fetchAll();
$statuses = ['Pending','Preparing','Out for Delivery','Delivered','Cancelled'];
?>

<?php if($msg): ?><div class="alert alert-success"><i class="bx bx-check-circle"></i><?=htmlspecialchars($msg)?></div><?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Orders (<?=number_format($totalRows)?>)</h3>
    </div>
    <div class="card-body">
        <form method="GET" class="filter-bar">
            <input type="text" name="q" class="search-input" placeholder="Search ref, guest, room…" value="<?=htmlspecialchars($search)?>">
            <select name="status" class="filter-select">
                <option value="">All Statuses</option>
                <?php foreach($statuses as $s): ?>
                    <option value="<?=$s?>" <?=$statusFilter===$s?'selected':''?>><?=$s?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-search"></i> Filter</button>
            <?php if($search||$statusFilter): ?><a href="orders.php" class="btn btn-sm btn-outline">Clear</a><?php endif; ?>
        </form>

        <div class="table-wrap">
            <table>
                <thead><tr><th>Ref</th><th>Guest</th><th>Room</th><th>Items</th><th>Total</th><th>Delivery</th><th>Status</th><th>Update</th></tr></thead>
                <tbody>
                <?php if(empty($orders)): ?>
                    <tr><td colspan="8"><div class="empty-state"><i class="bx bxs-bowl-rice"></i>No orders found.</div></td></tr>
                <?php else: foreach($orders as $o): ?>
                    <tr>
                        <td><strong><?=htmlspecialchars($o['order_ref'])?></strong></td>
                        <td><?=htmlspecialchars($o['guest_name'])?></td>
                        <td><?=htmlspecialchars($o['room_number'])?></td>
                        <td style="max-width:200px;font-size:12px;"><?=htmlspecialchars($o['items_detail'] ?? '—')?></td>
                        <td><strong>$<?=number_format($o['total_amount'],2)?></strong></td>
                        <td style="font-size:12px;"><?=htmlspecialchars($o['delivery_datetime'])?></td>
                        <td><span class="status status-<?=str_replace([' '],'-',$o['status'])?>"><?=htmlspecialchars($o['status'])?></span></td>
                        <td>
                            <form method="POST" style="display:inline-flex;gap:6px;align-items:center;">
                                <input type="hidden" name="order_id" value="<?=$o['id']?>">
                                <select name="status" class="filter-select" style="padding:5px 10px;border-radius:6px;font-size:12px;">
                                    <?php foreach($statuses as $s): ?>
                                        <option value="<?=$s?>" <?=$o['status']===$s?'selected':''?>><?=$s?></option>
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
        <?php if($totalPages>1): ?>
        <div class="pagination">
            <?php for($i=1;$i<=$totalPages;$i++): ?>
                <a href="?page=<?=$i?>&q=<?=urlencode($search)?>&status=<?=urlencode($statusFilter)?>" class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
