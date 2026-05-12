<?php
$pageTitle = 'Dashboard';
require_once 'header.php';

// Fetch dashboard stats
$totalUsers    = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalBookings = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalOrders   = $db->query("SELECT COUNT(*) FROM food_orders")->fetchColumn();
$totalRevenue  = $db->query("SELECT COALESCE(SUM(total_price),0) FROM bookings WHERE status NOT IN ('Cancelled')")->fetchColumn();
$totalRooms    = $db->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$totalApps     = $db->query("SELECT COUNT(*) FROM applications")->fetchColumn();

// Recent bookings
$recentBookings = $db->query(
    "SELECT b.booking_ref, b.guest_name, r.room_type, b.check_in, b.total_price, b.status
     FROM bookings b JOIN rooms r ON b.room_id = r.id
     ORDER BY b.created_at DESC LIMIT 6"
)->fetchAll();

// Recent orders
$recentOrders = $db->query(
    "SELECT o.order_ref, o.guest_name, o.room_number, o.total_amount, o.status, o.created_at
     FROM food_orders o ORDER BY o.created_at DESC LIMIT 6"
)->fetchAll();

// Monthly revenue (last 6 months)
$monthlyRevenue = $db->query(
    "SELECT DATE_FORMAT(created_at,'%b %Y') AS month,
            SUM(total_price) AS revenue, COUNT(*) AS bookings
     FROM bookings WHERE status NOT IN ('Cancelled') AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY YEAR(created_at), MONTH(created_at)
     ORDER BY created_at ASC"
)->fetchAll();
?>

<!-- Stats Row -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="bx bx-group"></i></div>
        <div><div class="stat-value"><?= number_format($totalUsers) ?></div><div class="stat-label">Total Users</div></div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon"><i class="bx bx-calendar-check"></i></div>
        <div><div class="stat-value"><?= number_format($totalBookings) ?></div><div class="stat-label">Total Bookings</div></div>
    </div>
    <div class="stat-card gold">
        <div class="stat-icon"><i class="bx bx-dollar-circle"></i></div>
        <div><div class="stat-value">$<?= number_format($totalRevenue, 0) ?></div><div class="stat-label">Total Revenue</div></div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon"><i class="bx bx-restaurant"></i></div>
        <div><div class="stat-value"><?= number_format($totalOrders) ?></div><div class="stat-label">Food Orders</div></div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon"><i class="bx bxs-bed"></i></div>
        <div><div class="stat-value"><?= number_format($totalRooms) ?></div><div class="stat-label">Rooms</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bx bx-briefcase"></i></div>
        <div><div class="stat-value"><?= number_format($totalApps) ?></div><div class="stat-label">Applications</div></div>
    </div>
</div>

<!-- Quick action badges -->
<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:24px;">
    <?php if($pendingBookings>0):?>
    <a href="bookings.php?status=Pending" class="btn btn-warning"><i class="bx bx-bell"></i> <?=$pendingBookings?> Pending Bookings</a>
    <?php endif;?>
    <?php if($pendingOrders>0):?>
    <a href="orders.php?status=Pending" class="btn btn-danger"><i class="bx bx-bell"></i> <?=$pendingOrders?> Pending Orders</a>
    <?php endif;?>
    <?php if($newApps>0):?>
    <a href="careers.php?status=Received" class="btn btn-info"><i class="bx bx-bell"></i> <?=$newApps?> New Applications</a>
    <?php endif;?>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">

    <!-- Recent Bookings -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Bookings</h3>
            <a href="bookings.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Ref</th><th>Guest</th><th>Room</th><th>Total</th><th>Status</th></tr></thead>
                    <tbody class="recent-table">
                    <?php if(empty($recentBookings)):?>
                        <tr><td colspan="5" class="empty-state">No bookings yet.</td></tr>
                    <?php else: foreach($recentBookings as $b): ?>
                        <tr>
                            <td><?=htmlspecialchars($b['booking_ref'])?></td>
                            <td><?=htmlspecialchars($b['guest_name'])?></td>
                            <td><?=htmlspecialchars($b['room_type'])?></td>
                            <td>$<?=number_format($b['total_price'],2)?></td>
                            <td><span class="status status-<?=str_replace(' ','-',$b['status'])?>"><?=htmlspecialchars($b['status'])?></span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Food Orders</h3>
            <a href="orders.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Ref</th><th>Guest</th><th>Room</th><th>Total</th><th>Status</th></tr></thead>
                    <tbody class="recent-table">
                    <?php if(empty($recentOrders)):?>
                        <tr><td colspan="5" class="empty-state">No orders yet.</td></tr>
                    <?php else: foreach($recentOrders as $o): ?>
                        <tr>
                            <td><?=htmlspecialchars($o['order_ref'])?></td>
                            <td><?=htmlspecialchars($o['guest_name'])?></td>
                            <td><?=htmlspecialchars($o['room_number'])?></td>
                            <td>$<?=number_format($o['total_amount'],2)?></td>
                            <td><span class="status status-<?=str_replace(' ','-',$o['status'])?>"><?=htmlspecialchars($o['status'])?></span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Revenue Summary -->
<?php if(!empty($monthlyRevenue)): ?>
<div class="card" style="margin-top:0;">
    <div class="card-header"><h3 class="card-title">Monthly Revenue (Last 6 Months)</h3></div>
    <div class="card-body">
        <table>
            <thead><tr><th>Month</th><th>Bookings</th><th>Revenue</th></tr></thead>
            <tbody>
            <?php foreach($monthlyRevenue as $m): ?>
                <tr>
                    <td><?=htmlspecialchars($m['month'])?></td>
                    <td><?=number_format($m['bookings'])?></td>
                    <td style="font-weight:700;color:var(--success);">$<?=number_format($m['revenue'],2)?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
