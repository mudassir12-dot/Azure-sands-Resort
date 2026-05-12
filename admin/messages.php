<?php
$pageTitle = 'Messages & Newsletter';
require_once 'header.php';

// Mark as read
if (isset($_GET['read'])) {
    $db->prepare("UPDATE contact_messages SET is_read=1 WHERE id=?")->execute([intval($_GET['read'])]);
}

$msgs     = $db->query("SELECT * FROM contact_messages ORDER BY is_read ASC, created_at DESC")->fetchAll();
$subs     = $db->query("SELECT * FROM newsletter_subscribers ORDER BY subscribed_at DESC")->fetchAll();
$unread   = $db->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn();
?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Contact Messages <?php if($unread>0):?><span class="badge" style="margin-left:8px;"><?=$unread?> new</span><?php endif;?></h3>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Name</th><th>Email</th><th>Subject</th><th>Date</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if(empty($msgs)): ?>
                        <tr><td colspan="5"><div class="empty-state"><i class="bx bx-envelope"></i>No messages.</div></td></tr>
                    <?php else: foreach($msgs as $m): ?>
                        <tr style="<?=$m['is_read']?'':'background:#fffbf0;'?>">
                            <td><?php if(!$m['is_read']): ?><strong><?php endif; ?><?=htmlspecialchars($m['sender_name'])?><?php if(!$m['is_read']): ?></strong><?php endif; ?></td>
                            <td style="font-size:12px;"><?=htmlspecialchars($m['sender_email'])?></td>
                            <td style="font-size:12px;"><?=htmlspecialchars($m['subject']??'—')?></td>
                            <td style="font-size:11px;"><?=date('M d, Y', strtotime($m['created_at']))?></td>
                            <td>
                                <?php if(!$m['is_read']): ?>
                                    <a href="?read=<?=$m['id']?>" class="btn btn-info btn-sm"><i class="bx bx-check"></i> Mark Read</a>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:12px;">Read</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if($m['message']): ?>
                        <tr style="<?=$m['is_read']?'':'background:#fffdf5;'?>">
                            <td colspan="5" style="padding:8px 16px 14px;font-size:13px;color:var(--medium-gray);">
                                <?=nl2br(htmlspecialchars($m['message']))?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Newsletter (<?=count($subs)?>)</h3></div>
        <div class="card-body" style="padding:0;">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Email</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php if(empty($subs)): ?>
                        <tr><td colspan="2"><div class="empty-state"><i class="bx bx-mail-send"></i>No subscribers.</div></td></tr>
                    <?php else: foreach($subs as $s): ?>
                        <tr>
                            <td style="font-size:13px;"><?=htmlspecialchars($s['email'])?></td>
                            <td style="font-size:11px;"><?=date('M d, Y', strtotime($s['subscribed_at']))?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
