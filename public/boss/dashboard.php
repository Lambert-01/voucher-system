<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Helpers/money.php';

requireRole('boss');

$pdo = getDB();

// Handle session timeout message
if (isset($_SESSION['timeout'])) {
    $sessionMessage = 'Your session timed out due to inactivity. Please log in again.';
    unset($_SESSION['timeout']);
}

// Get current month stats
$currentMonth = date('Y-m');
$sql = "SELECT 
    COUNT(DISTINCT v.id) as total_vouchers,
    SUM(v.original_amount) as total_issued,
    SUM(v.original_amount - v.balance) as total_used,
    SUM(v.balance) as total_remaining,
    COUNT(CASE WHEN v.status = 'active' THEN 1 END) as active_vouchers,
    COUNT(CASE WHEN v.status = 'partially_used' THEN 1 END) as partially_used_vouchers,
    COUNT(CASE WHEN v.status = 'used' THEN 1 END) as used_vouchers,
    COUNT(CASE WHEN v.status = 'blocked' THEN 1 END) as blocked_vouchers
FROM vouchers v
JOIN voucher_batches vb ON vb.id = v.batch_id
WHERE vb.batch_month LIKE ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$currentMonth . '%']);
$stats = $stmt->fetch();

// Recent transactions
$recentTrans = $pdo->query("SELECT vt.*, v.voucher_no, v.client_name, u.full_name as cashier_name 
    FROM voucher_transactions vt
    JOIN vouchers v ON v.id = vt.voucher_id
    JOIN users u ON u.id = vt.cashier_id
    ORDER BY vt.created_at DESC LIMIT 10")->fetchAll();

// Company summary
$companies = $pdo->query("SELECT c.company_name, 
    COUNT(DISTINCT v.id) as total_vouchers,
    SUM(v.original_amount) as total_issued,
    SUM(v.balance) as total_remaining
    FROM companies c
    JOIN voucher_batches vb ON vb.company_id = c.id
    JOIN vouchers v ON v.batch_id = vb.id
    WHERE vb.batch_month LIKE '$currentMonth%'
    GROUP BY c.id, c.company_name
    ORDER BY total_issued DESC")->fetchAll();

$title = 'Executive Dashboard';
ob_start();
?>

<?php if (isset($sessionMessage)): ?>
<div class="alert alert-warning"><i class="fas fa-clock"></i><?= htmlspecialchars($sessionMessage) ?></div>
<?php endif; ?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="fas fa-chart-line"></i> Executive Dashboard</h1>
        <p><?= date('F Y') ?> Overview</p>
    </div>
    <div class="page-actions">
        <a href="<?= htmlspecialchars(appUrl('/boss/reports.php')) ?>" class="btn btn-primary"><i class="fas fa-file-chart-line"></i> View Reports</a>
        <a href="<?= htmlspecialchars(appUrl('/boss/audit-logs.php')) ?>" class="btn btn-secondary"><i class="fas fa-shield-alt"></i> Audit Logs</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card stat-primary">
        <h3><i class="fas fa-coins"></i> Total Issued</h3>
        <p class="stat-value" style="font-size: 1.5rem;"><?= formatMoney($stats['total_issued'] ?? 0) ?></p>
        <p class="stat-label"><?= number_format($stats['total_vouchers'] ?? 0) ?> vouchers this month</p>
    </div>
    
    <div class="stat-card stat-warning">
        <h3><i class="fas fa-hand-holding-usd"></i> Total Redeemed</h3>
        <p class="stat-value" style="font-size: 1.5rem;"><?= formatMoney($stats['total_used'] ?? 0) ?></p>
        <?php 
        $usagePercent = $stats['total_issued'] > 0 ? round(($stats['total_used'] / $stats['total_issued']) * 100, 1) : 0;
        ?>
        <p class="stat-label"><?= $usagePercent ?>% redemption rate</p>
    </div>
    
    <div class="stat-card stat-success">
        <h3><i class="fas fa-wallet"></i> Remaining Balance</h3>
        <p class="stat-value" style="font-size: 1.5rem;"><?= formatMoney($stats['total_remaining'] ?? 0) ?></p>
        <p class="stat-label">Available for spending</p>
    </div>
    
    <div class="stat-card stat-info">
        <h3><i class="fas fa-clipboard-check"></i> Voucher Status</h3>
        <p class="stat-value"><?= number_format($stats['active_vouchers'] ?? 0) ?></p>
        <p class="stat-label">Active | Partial: <?= number_format($stats['partially_used_vouchers'] ?? 0) ?> | Used: <?= number_format($stats['used_vouchers'] ?? 0) ?> | Blocked: <?= number_format($stats['blocked_vouchers'] ?? 0) ?></p>
    </div>
</div>

<div class="row">
    <div class="col-6">
        <div class="card">
            <h2><i class="fas fa-building"></i> Company Summary</h2>
            <?php if (count($companies) > 0): ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Vouchers</th>
                            <th>Issued</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($companies as $comp): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($comp['company_name']) ?></strong></td>
                            <td><?= number_format($comp['total_vouchers']) ?></td>
                            <td><?= formatMoney($comp['total_issued']) ?></td>
                            <td><strong><?= formatMoney($comp['total_remaining']) ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-building"></i>
                <p>No company data for this month</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-6">
        <div class="card">
            <h2><i class="fas fa-history"></i> Recent Transactions</h2>
            <?php if (count($recentTrans) > 0): ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Client</th>
                            <th>Amount</th>
                            <th>Cashier</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTrans as $trans): ?>
                        <tr>
                            <td><?= date('M d, H:i', strtotime($trans['created_at'])) ?></td>
                            <td><?= htmlspecialchars($trans['client_name']) ?></td>
                            <td><strong><?= formatMoney($trans['amount_used']) ?></strong></td>
                            <td><?= htmlspecialchars($trans['cashier_name']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-receipt"></i>
                <p>No transactions yet</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../app/Views/layouts/main.php';
