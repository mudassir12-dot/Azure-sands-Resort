<?php
$pageTitle = 'Users Management';
require_once 'header.php';

$msg = $msgType = '';

// Delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $uid = intval($_POST['user_id']);
    $db->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
    $msg = 'User deleted.'; $msgType = 'danger';
}

$search  = sanitize($_GET['q'] ?? '');
$page    = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where  = $search ? "WHERE u.full_name LIKE ? OR u.email LIKE ? OR u.city LIKE ?" : '';
$params = $search ? ["%$search%","%$search%","%$search%"] : [];

$totalRows  = $db->prepare("SELECT COUNT(*) FROM users u $where");
$totalRows->execute($params); $totalRows = $totalRows->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$stmt = $db->prepare(
    "SELECT u.id, u.full_name, u.email, u.gender, u.city, u.loyalty_points,
            u.membership_tier, u.created_at,
            COUNT(DISTINCT b.id) AS booking_count,
            COUNT(DISTINCT o.id) AS order_count
     FROM users u
     LEFT JOIN bookings b ON u.id = b.user_id
     LEFT JOIN food_orders o ON u.id = o.user_id
     $where
     GROUP BY u.id ORDER BY u.created_at DESC LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<?php if($msg): ?><div class="alert alert-<?=$msgType?>"><?=htmlspecialchars($msg)?></div><?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Users (<?=number_format($totalRows)?>)</h3>
    </div>
    <div class="card-body">
        <form method="GET" class="filter-bar">
            <input type="text" name="q" class="search-input" placeholder="Search name, email, city…" value="<?=htmlspecialchars($search)?>">
            <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-search"></i> Search</button>
            <?php if($search): ?><a href="users.php" class="btn btn-sm btn-outline">Clear</a><?php endif; ?>
        </form>
        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>Name</th><th>Email</th><th>City</th><th>Tier</th><th>Points</th><th>Bookings</th><th>Orders</th><th>Joined</th><th>Action</th></tr></thead>
                <tbody>
                <?php if(empty($users)): ?>
                    <tr><td colspan="10"><div class="empty-state"><i class="bx bx-user-x"></i>No users found.</div></td></tr>
                <?php else: foreach($users as $u): ?>
                    <tr>
                        <td><?=$u['id']?></td>
                        <td><?=htmlspecialchars($u['full_name'])?></td>
                        <td style="font-size:12px;"><?=htmlspecialchars($u['email'])?></td>
                        <td><?=htmlspecialchars($u['city']??'—')?></td>
                        <td><span class="status status-Confirmed" style="font-size:11px;"><?=htmlspecialchars($u['membership_tier'])?></span></td>
                        <td><?=number_format($u['loyalty_points'])?></td>
                        <td style="text-align:center;"><?=$u['booking_count']?></td>
                        <td style="text-align:center;"><?=$u['order_count']?></td>
                        <td style="font-size:11px;"><?=date('M d, Y', strtotime($u['created_at']))?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?=$u['id']?>">
                                <button type="submit" name="delete_user" class="btn btn-danger btn-sm" data-confirm="Delete user <?=htmlspecialchars($u['full_name'])?>? This cannot be undone.">
                                    <i class="bx bx-trash"></i>
                                </button>
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
                <a href="?page=<?=$i?>&q=<?=urlencode($search)?>" class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
