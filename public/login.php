<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/auth.php';
require_once __DIR__ . '/../app/Helpers/money.php';
require_once __DIR__ . '/../app/Models/User.php';

if (isLoggedIn()) {
    redirectTo('/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $pdo = getDB();
    $userModel = new User($pdo);
    $user = $userModel->authenticate($username, $password);
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['LAST_ACTIVITY'] = time();
        
        logAudit($pdo, $user['id'], 'login', 'User logged in');
        
        if ($user['role'] === 'boss') {
            redirectTo('/boss/dashboard.php');
        } elseif ($user['role'] === 'admin') {
            redirectTo('/admin/dashboard.php');
        } else {
            redirectTo('/cashier/scan.php');
        }
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - N.HONEST Voucher System</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(appUrl('/assets/css/style.css')) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-logo">
            <img class="login-brand-img" src="<?= htmlspecialchars(appUrl('/assets/images/logo.png')) ?>" alt="N.HONEST Supermarket">
            <h1 style="color: white; margin: 0; font-size: 2rem;">N.HONEST Supermarket</h1>
            <p style="color: rgba(255,255,255,0.9); margin-top: 8px; font-size: 1.125rem; font-weight: 500;">Voucher Management System</p>
        </div>
        
        <div class="login-box">
            <h1><i class="fas fa-shield-alt"></i> Staff Login</h1>
            <h2>Secure access for authorized personnel</h2>
            
            <?php if (isset($_SESSION['timeout'])): ?>
                <div class="alert alert-warning"><i class="fas fa-clock"></i>Your session expired due to inactivity. Please log in again.</div>
                <?php unset($_SESSION['timeout']); ?>
            <?php elseif ($error): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Username</label>
                    <input type="text" name="username" placeholder="Enter your username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-large"><i class="fas fa-sign-in-alt"></i> Login to System</button>
            </form>
            
            <div class="login-info">
                <p><i class="fas fa-info-circle"></i> <strong>Development Mode</strong></p>
                <p style="font-size: 0.75rem; margin-top: 6px;">Default credentials: boss/admin/cashier | password123</p>
            </div>
        </div>
        
        <div class="login-footer">
            <p><strong>N.HONEST Supermarket</strong> - Kisimenti</p>
            <p style="margin-top: 4px;"><i class="fas fa-phone"></i> 0788633739 | <i class="fas fa-globe"></i> honestsupermarket.com</p>
        </div>
    </div>
</body>
</html>
