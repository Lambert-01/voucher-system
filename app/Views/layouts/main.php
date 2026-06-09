<?php
require_once __DIR__ . '/../../Helpers/ui.php';

if (!function_exists('getNotification')) {
    function getNotification() {
        if (!isset($_SESSION['notification'])) {
            return null;
        }

        $notification = $_SESSION['notification'];
        unset($_SESSION['notification']);
        return $notification;
    }
}

$assetVersion = max(
    @filemtime(__DIR__ . '/../../../public/assets/css/style.css') ?: 1,
    @filemtime(__DIR__ . '/../../../public/assets/js/app.js') ?: 1
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="session-timeout" content="<?= $_ENV['SESSION_LIFETIME'] ?? 60 ?>">
    <title><?= $title ?? 'N.HONEST Voucher System' ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(appUrl('/assets/css/style.css?v=' . $assetVersion)) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="<?= isLoggedIn() ? 'logged-in' : 'logged-out' ?>">
    <?php if (isLoggedIn()): ?>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="brand">
                <img class="brand-logo-img" src="<?= htmlspecialchars(appUrl('/assets/images/logo.png')) ?>" alt="N.HONEST Supermarket">
                <div class="brand-text">
                    <h1>N.HONEST Supermarket</h1>
                    <p>Voucher Management System</p>
                </div>
            </div>
            <div class="header-user">
                <div class="user-avatar"><?= strtoupper(substr($_SESSION['full_name'], 0, 1)) ?></div>
                <div class="user-info">
                    <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong>
                    <span><?= strtoupper($_SESSION['role']) ?></span>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-content">
            <ul class="nav-menu">
                <?php if ($_SESSION['role'] === 'boss'): ?>
                    <li><a href="<?= htmlspecialchars(appUrl('/boss/dashboard.php')) ?>"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                    <li><a href="<?= htmlspecialchars(appUrl('/boss/reports.php')) ?>"><i class="fas fa-file-chart-line"></i> Reports</a></li>
                    <li><a href="<?= htmlspecialchars(appUrl('/boss/audit-logs.php')) ?>"><i class="fas fa-shield-alt"></i> Audit Logs</a></li>
                <?php elseif ($_SESSION['role'] === 'admin'): ?>
                    <li><a href="<?= htmlspecialchars(appUrl('/admin/dashboard.php')) ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="<?= htmlspecialchars(appUrl('/admin/companies.php')) ?>"><i class="fas fa-building"></i> Companies</a></li>
                    <li><a href="<?= htmlspecialchars(appUrl('/admin/batches.php')) ?>"><i class="fas fa-layer-group"></i> Batches</a></li>
                    <li><a href="<?= htmlspecialchars(appUrl('/admin/vouchers.php')) ?>"><i class="fas fa-ticket-alt"></i> Vouchers</a></li>
                    <li><a href="<?= htmlspecialchars(appUrl('/admin/import.php')) ?>"><i class="fas fa-file-upload"></i> Import</a></li>
                <?php elseif ($_SESSION['role'] === 'cashier'): ?>
                    <li><a href="<?= htmlspecialchars(appUrl('/cashier/scan.php')) ?>"><i class="fas fa-qrcode"></i> Scan Voucher</a></li>
                    <li><a href="<?= htmlspecialchars(appUrl('/cashier/transactions.php')) ?>"><i class="fas fa-receipt"></i> My Transactions</a></li>
                <?php endif; ?>
            </ul>
            <a href="<?= htmlspecialchars(appUrl('/logout.php')) ?>" class="btn nav-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>
    <?php endif; ?>
    
    <div class="container">
        <?php 
        $notification = getNotification();
        if ($notification): 
        ?>
        <div
            class="alert alert-<?= htmlspecialchars($notification['type']) ?> app-notification"
            data-notification-type="<?= htmlspecialchars($notification['type']) ?>"
            data-notification-message="<?= htmlspecialchars($notification['message']) ?>"
        >
            <i class="fas fa-info-circle"></i><?= htmlspecialchars($notification['message']) ?>
        </div>
        <?php endif; ?>
        
        <?= $content ?>
    </div>
    
    <script src="<?= htmlspecialchars(appUrl('/assets/js/app.js?v=' . $assetVersion)) ?>"></script>
</body>
</html>
