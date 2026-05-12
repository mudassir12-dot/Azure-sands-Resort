<?php
$pageTitle = 'Rooms Management';
require_once 'header.php';

$msg = $msgType = '';

// Toggle availability
if (isset($_GET['toggle'])) {
    $rid  = intval($_GET['toggle']);
    $stmt = $db->prepare("UPDATE rooms SET is_available = 1 - is_available WHERE id = ?");
    $stmt->execute([$rid]);
    $msg = 'Room availability updated.'; $msgType = 'success';
}

// Add / Edit room
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = $_POST['form_action'] ?? '';
    $roomNumber  = sanitize($_POST['room_number'] ?? '');
    $roomType    = sanitize($_POST['room_type'] ?? '');
    $price       = floatval($_POST['price_per_night'] ?? 0);
    $capacity    = intval($_POST['capacity'] ?? 2);
    $description = sanitize($_POST['description'] ?? '');
    $badge       = sanitize($_POST['badge'] ?? '');

    if ($action === 'add') {
        try {
            $db->prepare(
                "INSERT INTO rooms (room_number,room_type,price_per_night,capacity,description,badge,is_available)
                 VALUES (?,?,?,?,?,?,1)"
            )->execute([$roomNumber,$roomType,$price,$capacity,$description,$badge]);
            $msg = 'Room added successfully.'; $msgType = 'success';
        } catch (Exception $e) {
            $msg = 'Room number already exists.'; $msgType = 'danger';
        }
    } elseif ($action === 'edit') {
        $rid = intval($_POST['room_id']);
        $db->prepare(
            "UPDATE rooms SET room_number=?,room_type=?,price_per_night=?,capacity=?,description=?,badge=? WHERE id=?"
        )->execute([$roomNumber,$roomType,$price,$capacity,$description,$badge,$rid]);
        $msg = 'Room updated successfully.'; $msgType = 'success';
    } elseif ($action === 'delete') {
        $rid = intval($_POST['room_id']);
        $db->prepare("DELETE FROM rooms WHERE id=?")->execute([$rid]);
        $msg = 'Room deleted.'; $msgType = 'danger';
    }
}

$rooms    = $db->query("SELECT * FROM rooms ORDER BY room_number")->fetchAll();
$types    = ['Deluxe Ocean View','Executive Suite','Presidential Suite','Family Suite','Premium Villa','Oceanfront Bungalow'];
$editRoom = null;
if (isset($_GET['edit'])) {
    $s = $db->prepare("SELECT * FROM rooms WHERE id=?");
    $s->execute([intval($_GET['edit'])]);
    $editRoom = $s->fetch();
}
?>

<?php if($msg): ?><div class="alert alert-<?=$msgType?>"><i class="bx bx-info-circle"></i> <?=htmlspecialchars($msg)?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;">

    <!-- Add / Edit Form -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= $editRoom ? 'Edit Room' : 'Add New Room' ?></h3>
            <?php if($editRoom): ?><a href="rooms.php" class="btn btn-sm btn-outline">+ Add New</a><?php endif; ?>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="form_action" value="<?=$editRoom?'edit':'add'?>">
                <?php if($editRoom): ?><input type="hidden" name="room_id" value="<?=$editRoom['id']?>"><?php endif; ?>
                <div class="form-row">
                    <div class="form-group">
                        <label>Room Number *</label>
                        <input type="text" name="room_number" class="form-control" required value="<?=htmlspecialchars($editRoom['room_number']??'')?>">
                    </div>
                    <div class="form-group">
                        <label>Price / Night ($) *</label>
                        <input type="number" name="price_per_night" class="form-control" required min="1" step="0.01" value="<?=htmlspecialchars($editRoom['price_per_night']??'')?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Room Type *</label>
                    <select name="room_type" class="form-control" required>
                        <?php foreach($types as $t): ?>
                            <option value="<?=$t?>" <?=($editRoom['room_type']??'')===$t?'selected':''?>><?=$t?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Capacity (guests)</label>
                        <input type="number" name="capacity" class="form-control" min="1" max="20" value="<?=htmlspecialchars($editRoom['capacity']??2)?>">
                    </div>
                    <div class="form-group">
                        <label>Badge Label</label>
                        <input type="text" name="badge" class="form-control" placeholder="e.g. Best Seller" value="<?=htmlspecialchars($editRoom['badge']??'')?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3"><?=htmlspecialchars($editRoom['description']??'')?></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> <?=$editRoom?'Update Room':'Add Room'?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Rooms List -->
    <div class="card">
        <div class="card-header"><h3 class="card-title">All Rooms (<?=count($rooms)?>)</h3></div>
        <div class="card-body" style="padding:0;">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Room #</th><th>Type</th><th>Price</th><th>Available</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach($rooms as $r): ?>
                        <tr>
                            <td><strong><?=htmlspecialchars($r['room_number'])?></strong></td>
                            <td style="font-size:12px;"><?=htmlspecialchars($r['room_type'])?></td>
                            <td>$<?=number_format($r['price_per_night'],2)?></td>
                            <td>
                                <a href="?toggle=<?=$r['id']?>" class="status status-<?=$r['is_available']?'Confirmed':'Cancelled'?>">
                                    <?=$r['is_available']?'Yes':'No'?>
                                </a>
                            </td>
                            <td style="display:flex;gap:6px;">
                                <a href="?edit=<?=$r['id']?>" class="btn btn-warning btn-sm"><i class="bx bx-edit"></i></a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="form_action" value="delete">
                                    <input type="hidden" name="room_id" value="<?=$r['id']?>">
                                    <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete room <?=htmlspecialchars($r['room_number'])?>?"><i class="bx bx-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
