<?php
$pageTitle = 'Applications Management';
require_once 'header.php';

$msg = $msgType = '';

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $appId     = intval($_POST['app_id']);
    $newStatus = sanitize($_POST['status']);
    $allowed   = ['Received','Under Review','Shortlisted','Interview','Hired','Rejected'];
    if (in_array($newStatus, $allowed)) {
        $db->prepare("UPDATE applications SET status=? WHERE id=?")->execute([$newStatus, $appId]);
        $msg = 'Application status updated.'; $msgType = 'success';
    }
}

// Delete application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_app'])) {
    $appId = intval($_POST['app_id']);
    $db->prepare("DELETE FROM applications WHERE id=?")->execute([$appId]);
    $msg = 'Application deleted.'; $msgType = 'danger';
}

$statusFilter = sanitize($_GET['status'] ?? '');
$search       = sanitize($_GET['q'] ?? '');
$page         = max(1, intval($_GET['page'] ?? 1));
$perPage      = 15;
$offset       = ($page - 1) * $perPage;

$where  = [];
$params = [];
if ($statusFilter) { $where[] = 'status = ?'; $params[] = $statusFilter; }
if ($search)       { $where[] = '(applicant_name LIKE ? OR applicant_email LIKE ? OR position_applied LIKE ?)'; $params = array_merge($params,["%$search%","%$search%","%$search%"]); }
$whereSql = $where ? 'WHERE ' . implode(' AND ',$where) : '';

$total = $db->prepare("SELECT COUNT(*) FROM applications $whereSql");
$total->execute($params); $totalRows = $total->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$stmt = $db->prepare("SELECT * FROM applications $whereSql ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$apps     = $stmt->fetchAll();
$statuses = ['Received','Under Review','Shortlisted','Interview','Hired','Rejected'];
?>

<?php if($msg): ?><div class="alert alert-<?=$msgType?>"><?=htmlspecialchars($msg)?></div><?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Job Applications (<?=number_format($totalRows)?>)</h3>
    </div>
    <div class="card-body">
        <form method="GET" class="filter-bar">
            <input type="text" name="q" class="search-input" placeholder="Search name, email, position…" value="<?=htmlspecialchars($search)?>">
            <select name="status" class="filter-select">
                <option value="">All Statuses</option>
                <?php foreach($statuses as $s): ?>
                    <option value="<?=$s?>" <?=$statusFilter===$s?'selected':''?>><?=$s?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-search"></i> Filter</button>
            <?php if($search||$statusFilter): ?><a href="careers.php" class="btn btn-sm btn-outline">Clear</a><?php endif; ?>
        </form>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Ref</th><th>Applicant</th><th>Position</th><th>Start Date</th><th>CV</th><th>Status</th><th>Update</th><th>Del</th></tr></thead>
                <tbody>
                <?php if(empty($apps)): ?>
                    <tr><td colspan="8"><div class="empty-state"><i class="bx bx-briefcase"></i>No applications found.</div></td></tr>
                <?php else: foreach($apps as $a): ?>
                    <tr>
                        <td><strong><?=htmlspecialchars($a['app_ref'])?></strong></td>
                        <td>
                            <?=htmlspecialchars($a['applicant_name'])?><br>
                            <small class="text-muted"><?=htmlspecialchars($a['applicant_email'])?></small>
                        </td>
                        <td><?=htmlspecialchars($a['position_applied'])?></td>
                        <td><?=htmlspecialchars($a['available_date'])?></td>
                        <td>
                            <?php if($a['cv_filename']): ?>
                                <a href="../uploads/cvs/<?=htmlspecialchars($a['cv_filename'])?>" target="_blank" class="btn btn-info btn-sm">
                                    <i class="bx bx-file"></i> View
                                </a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="status status-<?=str_replace(' ','-',$a['status'])?>"><?=htmlspecialchars($a['status'])?></span></td>
                        <td>
                            <form method="POST" style="display:inline-flex;gap:6px;">
                                <input type="hidden" name="app_id" value="<?=$a['id']?>">
                                <select name="status" class="filter-select" style="padding:5px 10px;border-radius:6px;font-size:12px;">
                                    <?php foreach($statuses as $s): ?>
                                        <option value="<?=$s?>" <?=$a['status']===$s?'selected':''?>><?=$s?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-primary btn-sm">Save</button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="app_id" value="<?=$a['id']?>">
                                <button type="submit" name="delete_app" class="btn btn-danger btn-sm" data-confirm="Delete this application?"><i class="bx bx-trash"></i></button>
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
