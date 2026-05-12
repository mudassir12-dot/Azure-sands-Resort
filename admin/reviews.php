<?php
$pageTitle = 'Reviews Management';
require_once 'header.php';

$msg = $msgType = '';

// Approve / reject / delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reviewId = intval($_POST['review_id'] ?? 0);
    $action   = $_POST['form_action'] ?? '';

    if ($action === 'approve') {
        $db->prepare("UPDATE reviews SET is_approved = 1 WHERE id = ?")->execute([$reviewId]);
        $msg = 'Review approved and published.'; $msgType = 'success';
    } elseif ($action === 'reject') {
        $db->prepare("UPDATE reviews SET is_approved = 0 WHERE id = ?")->execute([$reviewId]);
        $msg = 'Review hidden from public.'; $msgType = 'warning';
    } elseif ($action === 'delete') {
        $db->prepare("DELETE FROM reviews WHERE id = ?")->execute([$reviewId]);
        $msg = 'Review deleted.'; $msgType = 'danger';
    }
}

// Filter
$filterApproved = $_GET['approved'] ?? '';
$page    = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where = '';
$params = [];
if ($filterApproved === '1') { $where = 'WHERE is_approved = 1'; }
elseif ($filterApproved === '0') { $where = 'WHERE is_approved = 0'; }

$totalRows  = $db->query("SELECT COUNT(*) FROM reviews $where")->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$reviews = $db->query(
    "SELECT r.*, u.full_name AS user_name, u.email AS user_email
     FROM reviews r
     LEFT JOIN users u ON r.user_id = u.id
     $where
     ORDER BY r.created_at DESC
     LIMIT $perPage OFFSET $offset"
)->fetchAll();

// Stats
$totalReviews    = $db->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
$approvedReviews = $db->query("SELECT COUNT(*) FROM reviews WHERE is_approved=1")->fetchColumn();
$pendingReviews  = $db->query("SELECT COUNT(*) FROM reviews WHERE is_approved=0")->fetchColumn();
$avgRating       = $db->query("SELECT ROUND(AVG(rating),1) FROM reviews WHERE is_approved=1")->fetchColumn();
?>

<?php if($msg): ?><div class="alert alert-<?=$msgType?>"><i class="bx bx-info-circle"></i> <?=htmlspecialchars($msg)?></div><?php endif; ?>

<!-- Stats -->
<div class="stats-grid" style="margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-icon"><i class="bx bx-star"></i></div>
        <div><div class="stat-value"><?=$totalReviews?></div><div class="stat-label">Total Reviews</div></div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon"><i class="bx bx-check-circle"></i></div>
        <div><div class="stat-value"><?=$approvedReviews?></div><div class="stat-label">Approved</div></div>
    </div>
    <div class="stat-card gold">
        <div class="stat-icon"><i class="bx bx-time"></i></div>
        <div><div class="stat-value"><?=$pendingReviews?></div><div class="stat-label">Pending</div></div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon"><i class="bx bxs-star"></i></div>
        <div><div class="stat-value"><?=$avgRating ?: '—'?></div><div class="stat-label">Avg Rating</div></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Reviews (<?=number_format($totalRows)?>)</h3>
        <div style="display:flex;gap:8px;">
            <a href="reviews.php" class="btn btn-sm btn-outline <?=$filterApproved===''?'btn-primary':''?>">All</a>
            <a href="reviews.php?approved=0" class="btn btn-sm btn-outline <?=$filterApproved==='0'?'btn-primary':''?>">Pending</a>
            <a href="reviews.php?approved=1" class="btn btn-sm btn-outline <?=$filterApproved==='1'?'btn-primary':''?>">Approved</a>
        </div>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Reviewer</th><th>Rating</th><th>Comment</th><th>Date</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if(empty($reviews)): ?>
                    <tr><td colspan="6">
                        <div class="empty-state"><i class="bx bx-star"></i>No reviews found.</div>
                    </td></tr>
                <?php else: foreach($reviews as $r): ?>
                    <tr>
                        <td>
                            <strong><?=htmlspecialchars($r['reviewer_name'])?></strong><br>
                            <?php if($r['user_email']): ?>
                                <small class="text-muted"><?=htmlspecialchars($r['user_email'])?></small>
                            <?php else: ?>
                                <small class="text-muted">Guest</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="color:#f0c040;font-size:16px;">
                                <?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) ?>
                            </span>
                            <small style="display:block;color:var(--medium-gray);"><?=$r['rating']?>/5</small>
                        </td>
                        <td style="max-width:300px;font-size:13px;">
                            <?=htmlspecialchars(mb_strimwidth($r['comment'] ?? '', 0, 150, '…'))?>
                        </td>
                        <td style="font-size:12px;"><?=date('M d, Y', strtotime($r['created_at']))?></td>
                        <td>
                            <span class="status <?=$r['is_approved'] ? 'status-Confirmed' : 'status-Pending'?>">
                                <?=$r['is_approved'] ? 'Approved' : 'Pending'?>
                            </span>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <?php if(!$r['is_approved']): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="review_id" value="<?=$r['id']?>">
                                        <button type="submit" name="form_action" value="approve" class="btn btn-success btn-sm">
                                            <i class="bx bx-check"></i> Approve
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="review_id" value="<?=$r['id']?>">
                                        <button type="submit" name="form_action" value="reject" class="btn btn-warning btn-sm">
                                            <i class="bx bx-hide"></i> Hide
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="review_id" value="<?=$r['id']?>">
                                    <button type="submit" name="form_action" value="delete" class="btn btn-danger btn-sm"
                                            data-confirm="Permanently delete this review?">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($totalPages > 1): ?>
        <div class="pagination">
            <?php for($i=1;$i<=$totalPages;$i++): ?>
                <a href="?page=<?=$i?>&approved=<?=urlencode($filterApproved)?>"
                   class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
