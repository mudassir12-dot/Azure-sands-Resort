<?php
/**
 * Admin header partial — included at the top of every admin page.
 * Requires $pageTitle to be set before including.
 */
require_once __DIR__ . '/../config/database.php';
startSecureSession();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php'); exit;
}
$adminName = $_SESSION['admin_name'] ?? 'Admin';

// Quick stats for sidebar badges
$db = getDB();
$pendingBookings = $db->query("SELECT COUNT(*) FROM bookings WHERE status='Pending'")->fetchColumn();
$pendingOrders   = $db->query("SELECT COUNT(*) FROM food_orders WHERE status='Pending'")->fetchColumn();
$newApps         = $db->query("SELECT COUNT(*) FROM applications WHERE status='Received'")->fetchColumn();
$pendingReviews  = $db->query("SELECT COUNT(*) FROM reviews WHERE is_approved=0")->fetchColumn();

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> – Azure Sands Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="admin-layout">

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../assets/images/logo.jfif" alt="Logo" class="sidebar-logo">
            <div>
                <h2 class="sidebar-title">Azure Sands</h2>
                <span class="sidebar-sub">Admin Panel</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="index.php"        class="nav-item <?= $currentPage==='index.php'        ?'active':'' ?>"><i class="bx bx-tachometer"></i><span>Dashboard</span></a>
            <a href="rooms.php"        class="nav-item <?= $currentPage==='rooms.php'        ?'active':'' ?>"><i class="bx bxs-bed"></i><span>Rooms</span></a>
            <a href="bookings.php"     class="nav-item <?= $currentPage==='bookings.php'     ?'active':'' ?>">
                <i class="bx bx-calendar"></i><span>Bookings</span>
                <?php if($pendingBookings>0):?><span class="badge"><?=$pendingBookings?></span><?php endif;?>
            </a>
            <a href="food.php"         class="nav-item <?= $currentPage==='food.php'         ?'active':'' ?>"><i class="bx bxs-bowl-rice"></i><span>Food Items</span></a>
            <a href="orders.php"       class="nav-item <?= $currentPage==='orders.php'       ?'active':'' ?>">
                <i class="bx bx-restaurant"></i><span>Orders</span>
                <?php if($pendingOrders>0):?><span class="badge"><?=$pendingOrders?></span><?php endif;?>
            </a>
            <a href="users.php"        class="nav-item <?= $currentPage==='users.php'        ?'active':'' ?>"><i class="bx bx-group"></i><span>Users</span></a>
            <a href="careers.php"      class="nav-item <?= $currentPage==='careers.php'      ?'active':'' ?>">
                <i class="bx bx-briefcase"></i><span>Applications</span>
                <?php if($newApps>0):?><span class="badge"><?=$newApps?></span><?php endif;?>
            </a>
            <a href="messages.php"     class="nav-item <?= $currentPage==='messages.php'     ?'active':'' ?>"><i class="bx bx-envelope"></i><span>Messages</span></a>
            <a href="reviews.php"      class="nav-item <?= $currentPage==='reviews.php'      ?'active':'' ?>">
                <i class="bx bx-star"></i><span>Reviews</span>
                <?php if($pendingReviews>0):?><span class="badge"><?=$pendingReviews?></span><?php endif;?>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="admin-info">
                <span class="admin-avatar"><?= strtoupper(substr($adminName,0,1)) ?></span>
                <div>
                    <span class="admin-name"><?= htmlspecialchars($adminName) ?></span>
                    <span class="admin-role">Administrator</span>
                </div>
            </div>
            <a href="logout.php" class="logout-btn" title="Logout"><i class="bx bx-log-out"></i></a>
        </div>
    </aside>

    <!-- Main content area -->
    <main class="admin-main">
        <div class="topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="bx bx-menu"></i></button>
            <h1 class="page-title"><?= htmlspecialchars($pageTitle ?? '') ?></h1>
            <div class="topbar-right">
                <a href="../index.php" target="_blank" class="view-site-btn"><i class="bx bx-link-external"></i> View Site</a>
            </div>
        </div>
        <div class="admin-content">
