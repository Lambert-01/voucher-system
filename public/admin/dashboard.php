<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Helpers/money.php';

requireRole('admin');

$pdo = getDB();

$stats = $pdo->query("SELECT 
    COUNT(DISTINCT c.id) as total_companies,
    COUNT(DISTINCT vb.id) as total_batches,
    COUNT(DISTINCT v.id) as total_vouchers,
    SUM(v.original_amount) as total_value,
    SUM(v.balance) as total_balance,
    COUNT(CASE WHEN v.status = 'active' THEN 1 END) as active_vouchers,
    COUNT(CASE WHEN v.status = 'used' THEN 1 END) as used_vouchers,
    COUNT(CASE WHEN v.status = 'blocked' THEN 1 END) as blocked_vouchers
    FROM companies c
    LEFT JOIN voucher_batches vb ON vb.company_id = c.id
    LEFT JOIN vouchers v ON v.batch_id = vb.id")->fetch();

$recentBatches = $pdo->query("SELECT vb.*, c.company_name 
    FROM voucher_batches vb
    JOIN companies c ON c.id = vb.company_id
    ORDER BY vb.created_at DESC LIMIT 5")->fetchAll();

$title = 'Admin Dashboard';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
        <p>Manage companies, batches, and vouchers</p>
    </div>
    <div class="page-actions">
        <a href="<?= htmlspecialchars(appUrl('/admin/import.php')) ?>" class="btn btn-primary"><i class="fas fa-file-upload"></i> Import Vouchers</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card stat-primary">
        <h3><i class="fas fa-building"></i> Total Companies</h3>
        <p class="stat-value"><?= $stats['total_companies'] ?? 0 ?></p>
    </div>
    
    <div class="stat-card stat-info">
        <h3><i class="fas fa-layer-group"></i> Total Batches</h3>
        <p class="stat-value"><?= $stats['total_batches'] ?? 0 ?></p>
    </div>
    
    <div class="stat-card stat-success">
        <h3><i class="fas fa-ticket-alt"></i> Total Vouchers</h3>
        <p class="stat-value"><?= $stats['total_vouchers'] ?? 0 ?></p>
        <p class="stat-label">Active: <?= $stats['active_vouchers'] ?? 0 ?> | Used: <?= $stats['used_vouchers'] ?? 0 ?> | Blocked: <?= $stats['blocked_vouchers'] ?? 0 ?></p>
    </div>
    
    <div class="stat-card stat-warning">
        <h3><i class="fas fa-coins"></i> Total Value Issued</h3>
        <p class="stat-value" style="font-size: 1.5rem;"><?= formatMoney($stats['total_value'] ?? 0) ?></p>
        <p class="stat-label">Balance: <?= formatMoney($stats['total_balance'] ?? 0) ?></p>
    </div>
</div>

<div class="quick-actions-grid">
    <a href="<?= htmlspecialchars(appUrl('/admin/companies.php')) ?>" class="quick-action-btn">
        <i class="fas fa-building" style="font-size: 28px; color: var(--brand-green);"></i>
        <span>Manage Companies</span>
    </a>
    <a href="<?= htmlspecialchars(appUrl('/admin/batches.php')) ?>" class="quick-action-btn">
        <i class="fas fa-layer-group" style="font-size: 28px; color: var(--info);"></i>
        <span>Manage Batches</span>
    </a>
    <a href="<?= htmlspecialchars(appUrl('/admin/vouchers.php')) ?>" class="quick-action-btn">
        <i class="fas fa-ticket-alt" style="font-size: 28px; color: var(--success);"></i>
        <span>Manage Vouchers</span>
    </a>
    <a href="<?= htmlspecialchars(appUrl('/admin/create-voucher.php')) ?>" class="quick-action-btn">
        <i class="fas fa-plus-circle" style="font-size: 28px; color: var(--warning);"></i>
        <span>Create Voucher</span>
    </a>
</div>

<div class="card">
    <h2><i class="fas fa-clock"></i> Recent Batches</h2>
    <?php if (count($recentBatches) > 0): ?>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Batch Code</th>
                    <th>Company</th>
                    <th>Month</th>
                    <th>Vouchers</th>
                    <th>Total Amount</th>
                    <th>Payment Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentBatches as $batch): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($batch['batch_code']) ?></strong></td>
                    <td><?= htmlspecialchars($batch['company_name']) ?></td>
                    <td><?= htmlspecialchars($batch['batch_month']) ?></td>
                    <td><?= number_format($batch['total_vouchers']) ?></td>
                    <td><strong><?= formatMoney($batch['total_amount']) ?></strong></td>
                    <td><span class="badge badge-<?= $batch['payment_status'] ?>"><?= $batch['payment_status'] ?></span></td>
                    <td>
                        <a href="<?= htmlspecialchars(appUrl('/admin/batch-view.php?id=' . $batch['id'])) ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <p>No batches created yet</p>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../app/Views/layouts/main.php';
