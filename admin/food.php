<?php
$pageTitle = 'Food Items Management';
require_once 'header.php';

$msg = $msgType = '';

// Toggle availability
if (isset($_GET['toggle'])) {
    $db->prepare("UPDATE food_items SET is_available = 1 - is_available WHERE id = ?")->execute([intval($_GET['toggle'])]);
    $msg = 'Item availability updated.'; $msgType = 'success';
}

// CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = $_POST['form_action'] ?? '';
    $itemCode    = sanitize($_POST['item_code'] ?? '');
    $name        = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $price       = floatval($_POST['price'] ?? 0);
    $category    = sanitize($_POST['category'] ?? 'Main Course');

    if ($action === 'add') {
        try {
            $db->prepare(
                "INSERT INTO food_items (item_code,name,description,price,category,is_available) VALUES (?,?,?,?,?,1)"
            )->execute([$itemCode,$name,$description,$price,$category]);
            $msg = 'Food item added.'; $msgType = 'success';
        } catch (Exception $e) {
            $msg = 'Item code already exists.'; $msgType = 'danger';
        }
    } elseif ($action === 'edit') {
        $fid = intval($_POST['food_id']);
        $db->prepare(
            "UPDATE food_items SET item_code=?,name=?,description=?,price=?,category=? WHERE id=?"
        )->execute([$itemCode,$name,$description,$price,$category,$fid]);
        $msg = 'Food item updated.'; $msgType = 'success';
    } elseif ($action === 'delete') {
        $fid = intval($_POST['food_id']);
        $db->prepare("DELETE FROM food_items WHERE id=?")->execute([$fid]);
        $msg = 'Food item deleted.'; $msgType = 'danger';
    }
}

$foods      = $db->query("SELECT * FROM food_items ORDER BY category, name")->fetchAll();
$categories = ['Main Course','Appetizer','Dessert','Beverage','Special'];
$editFood   = null;
if (isset($_GET['edit'])) {
    $s = $db->prepare("SELECT * FROM food_items WHERE id=?");
    $s->execute([intval($_GET['edit'])]);
    $editFood = $s->fetch();
}
?>

<?php if($msg): ?><div class="alert alert-<?=$msgType?>"><i class="bx bx-info-circle"></i> <?=htmlspecialchars($msg)?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?=$editFood?'Edit Item':'Add Food Item'?></h3>
            <?php if($editFood): ?><a href="food.php" class="btn btn-sm btn-outline">+ Add New</a><?php endif; ?>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="form_action" value="<?=$editFood?'edit':'add'?>">
                <?php if($editFood): ?><input type="hidden" name="food_id" value="<?=$editFood['id']?>"><?php endif; ?>
                <div class="form-row">
                    <div class="form-group">
                        <label>Item Code *</label>
                        <input type="text" name="item_code" class="form-control" required placeholder="e.g. 2009" value="<?=htmlspecialchars($editFood['item_code']??'')?>">
                    </div>
                    <div class="form-group">
                        <label>Price ($) *</label>
                        <input type="number" name="price" class="form-control" required min="0.01" step="0.01" value="<?=htmlspecialchars($editFood['price']??'')?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" name="name" class="form-control" required value="<?=htmlspecialchars($editFood['name']??'')?>">
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" class="form-control">
                        <?php foreach($categories as $c): ?>
                            <option value="<?=$c?>" <?=($editFood['category']??'')===$c?'selected':''?>><?=$c?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3"><?=htmlspecialchars($editFood['description']??'')?></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> <?=$editFood?'Update Item':'Add Item'?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">All Food Items (<?=count($foods)?>)</h3></div>
        <div class="card-body" style="padding:0;">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Code</th><th>Name</th><th>Category</th><th>Price</th><th>Active</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach($foods as $f): ?>
                        <tr>
                            <td><?=htmlspecialchars($f['item_code'])?></td>
                            <td><?=htmlspecialchars($f['name'])?></td>
                            <td><small><?=htmlspecialchars($f['category'])?></small></td>
                            <td>$<?=number_format($f['price'],2)?></td>
                            <td>
                                <a href="?toggle=<?=$f['id']?>" class="status status-<?=$f['is_available']?'Confirmed':'Cancelled'?>">
                                    <?=$f['is_available']?'Yes':'No'?>
                                </a>
                            </td>
                            <td style="display:flex;gap:6px;">
                                <a href="?edit=<?=$f['id']?>" class="btn btn-warning btn-sm"><i class="bx bx-edit"></i></a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="form_action" value="delete">
                                    <input type="hidden" name="food_id" value="<?=$f['id']?>">
                                    <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this item?"><i class="bx bx-trash"></i></button>
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
