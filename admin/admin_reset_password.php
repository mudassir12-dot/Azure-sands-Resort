<?php
/**
 * Azure Sands Resort - Admin Password Reset Utility
 * USE THIS TO SET/RESET ADMIN PASSWORD
 * Delete this file after use for security
 */

require_once __DIR__ . '/../config/database.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $username = sanitize($_POST['username'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($action === 'reset') {
        // Validate input
        if (!$username) {
            $message = '❌ Please enter a username.';
        } elseif (!$new_password) {
            $message = '❌ Please enter a new password.';
        } elseif (strlen($new_password) < 8) {
            $message = '❌ Password must be at least 8 characters.';
        } elseif ($new_password !== $confirm_password) {
            $message = '❌ Passwords do not match.';
        } else {
            try {
                $db = getDB();
                
                // Check if admin exists
                $stmt = $db->prepare("SELECT id FROM admins WHERE username = ?");
                $stmt->execute([$username]);
                $admin = $stmt->fetch();

                if (!$admin) {
                    $message = '❌ Admin user not found.';
                } else {
                    // Hash and update password
                    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $stmt = $db->prepare("UPDATE admins SET password = ? WHERE username = ?");
                    $stmt->execute([$hashed_password, $username]);

                    $success = true;
                    $message = '✅ Password reset successfully! You can now log in with the new password.';
                }
            } catch (Exception $e) {
                $message = '❌ Database error: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Password Reset – Azure Sands Resort</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <style>
        :root {
            --primary: #0A2463;
            --secondary: #D4AF37;
            --success: #28a745;
            --danger: #dc3545;
            --radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #0A2463 0%, #1a3a8f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .reset-card {
            background: #fff;
            border-radius: 20px;
            padding: 50px 44px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .reset-logo {
            text-align: center;
            margin-bottom: 32px;
        }

        .reset-logo h1 {
            font-family: 'Playfair Display', serif;
            color: var(--primary);
            font-size: 1.8rem;
        }

        .reset-logo p {
            color: #6C757D;
            font-size: 14px;
            margin-top: 6px;
        }

        .badge {
            display: inline-block;
            background: var(--secondary);
            color: var(--primary);
            font-size: 11px;
            font-weight: 700;
            padding: 3px 12px;
            border-radius: 999px;
            margin-bottom: 12px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .message {
            border-radius: var(--radius);
            padding: 14px 16px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-group {
            margin-bottom: 22px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #212529;
            margin-bottom: 7px;
        }

        .form-group input {
            width: 100%;
            padding: 13px 16px 13px 44px;
            border: 2px solid #E9ECEF;
            border-radius: var(--radius);
            font-size: 15px;
            font-family: 'Montserrat', sans-serif;
            transition: border-color 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .form-group i {
            position: absolute;
            left: 14px;
            top: 41px;
            color: #6C757D;
            font-size: 20px;
        }

        .btn-reset {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--primary), #1a3a8f);
            color: #fff;
            border: none;
            border-radius: 999px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Montserrat', sans-serif;
        }

        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(10, 36, 99, 0.35);
        }

        .btn-reset:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid var(--primary);
            padding: 14px;
            border-radius: var(--radius);
            margin-bottom: 24px;
            font-size: 13px;
            color: #004085;
            line-height: 1.6;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #6C757D;
            font-size: 13px;
            text-decoration: none;
        }

        .back-link:hover {
            color: var(--primary);
        }

        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 12px;
            border-radius: var(--radius);
            font-size: 12px;
            margin-top: 20px;
            text-align: center;
        }

        .success-message {
            display: none;
        }

        .success-message.show {
            display: block;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 16px;
            border-radius: var(--radius);
            margin-bottom: 24px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="reset-card">
        <div class="reset-logo">
            <span class="badge">Password Reset</span>
            <h1>Azure Sands Resort</h1>
            <p>Reset Admin Password</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
            <div class="info-box">
                <i class="bx bx-info-circle"></i> 
                Enter the admin username and set a new password. This password must be at least 8 characters long.
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="reset">

                <div class="form-group">
                    <label for="username">Admin Username</label>
                    <input type="text" id="username" name="username" placeholder="e.g., admin" required>
                    <i class="bx bx-user"></i>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" placeholder="At least 8 characters" required>
                    <i class="bx bx-lock-alt"></i>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
                    <i class="bx bx-lock-alt"></i>
                </div>

                <button type="submit" class="btn-reset">Reset Password</button>
            </form>

            <div class="warning">
                ⚠️ <strong>Security Warning:</strong> Delete this file (reset-password.php) after resetting your password!
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 30px 0;">
                <i class="bx bx-check-circle" style="font-size: 60px; color: var(--success);"></i>
                <h3 style="color: var(--primary); margin-top: 20px;">Password Reset Successful!</h3>
                <p style="color: #6C757D; margin: 10px 0;">You can now log in with your new password.</p>
                <a href="login.php" style="display: inline-block; margin-top: 20px; padding: 12px 30px; background: var(--primary); color: white; text-decoration: none; border-radius: 999px; font-weight: 600;">Go to Login</a>
            </div>
        <?php endif; ?>

        <a href="login.php" class="back-link">← Back to Admin Login</a>
    </div>
</body>
</html>
