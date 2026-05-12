<?php
/**
 * Azure Sands Resort – Admin Login
 */
require_once __DIR__ . '/../config/database.php';
startSecureSession();

// Already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php'); exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id, full_name, password FROM admins WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true); // Prevent session fixation on admin login
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            header('Location: index.php'); exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login – Azure Sands Resort</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <style>
        :root{--primary:#0A2463;--secondary:#D4AF37;--radius:12px;}
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Montserrat',sans-serif;background:linear-gradient(135deg,#0A2463 0%,#1a3a8f 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;}
        .login-card{background:#fff;border-radius:20px;padding:50px 44px;width:100%;max-width:440px;box-shadow:0 20px 60px rgba(0,0,0,.3);}
        .login-logo{text-align:center;margin-bottom:32px;}
        .login-logo h1{font-family:'Playfair Display',serif;color:var(--primary);font-size:1.8rem;}
        .login-logo p{color:#6C757D;font-size:14px;margin-top:6px;}
        .badge{display:inline-block;background:var(--secondary);color:var(--primary);font-size:11px;font-weight:700;padding:3px 12px;border-radius:999px;margin-bottom:12px;letter-spacing:1px;text-transform:uppercase;}
        .form-group{margin-bottom:22px;position:relative;}
        .form-group label{display:block;font-size:13px;font-weight:600;color:#212529;margin-bottom:7px;}
        .form-group input{width:100%;padding:13px 16px 13px 44px;border:2px solid #E9ECEF;border-radius:var(--radius);font-size:15px;font-family:'Montserrat',sans-serif;transition:border-color .2s;}
        .form-group input:focus{outline:none;border-color:var(--primary);}
        .form-group i{position:absolute;left:14px;top:41px;color:#6C757D;font-size:20px;}
        .btn-login{width:100%;padding:15px;background:linear-gradient(135deg,var(--primary),#1a3a8f);color:#fff;border:none;border-radius:999px;font-size:16px;font-weight:700;cursor:pointer;transition:all .3s;font-family:'Montserrat',sans-serif;}
        .btn-login:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(10,36,99,.35);}
        .error-msg{background:#f8d7da;color:#721c24;border-radius:var(--radius);padding:12px 16px;font-size:14px;margin-bottom:20px;}
        .back-link{display:block;text-align:center;margin-top:20px;color:#6C757D;font-size:13px;text-decoration:none;}
        .back-link:hover{color:var(--primary);}
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-logo">
            <span class="badge">Admin Panel</span>
            <h1>Azure Sands Resort</h1>
            <p>Sign in to the management dashboard</p>
        </div>
        <?php if ($error): ?>
            <div class="error-msg"><i class="bx bx-error-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" placeholder="Enter username" required autocomplete="username">
                <i class="bx bx-user"></i>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter password" required autocomplete="current-password">
                <i class="bx bx-lock-alt"></i>
            </div>
            <button type="submit" class="btn-login">Sign In to Dashboard</button>
        </form>
        <a href="../index.php" class="back-link">← Back to Resort Website</a>
    </div>
</body>
</html>
