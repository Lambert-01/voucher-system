<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Helpers/money.php';
require_once __DIR__ . '/../../app/Helpers/ui.php';
require_once __DIR__ . '/../../app/Models/Voucher.php';
require_once __DIR__ . '/../../app/Models/VoucherBatch.php';
require_once __DIR__ . '/../../app/Models/Company.php';

requireRole('admin');

$pdo = getDB();
$voucherModel = new Voucher($pdo);
$batchModel = new VoucherBatch($pdo);
$companyModel = new Company($pdo);

// Filters
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$batchFilter = $_GET['batch_id'] ?? '';
$companyFilter = $_GET['company_id'] ?? '';

$vouchers = $voucherModel->getAll();

// Apply filters
if ($search) {
    $vouchers = array_filter($vouchers, function($v) use ($search) {
        return stripos($v['voucher_no'], $search) !== false ||
               stripos($v['client_name'], $search) !== false ||
               stripos($v['eva_id'], $search) !== false;
    });
}

if ($statusFilter) {
    $vouchers = array_filter($vouchers, function($v) use ($statusFilter) {
        return $v['status'] === $statusFilter;
    });
}

if ($batchFilter) {
    $vouchers = array_filter($vouchers, function($v) use ($batchFilter) {
        return (string)$v['batch_id'] === (string)$batchFilter;
    });
}

if ($companyFilter) {
    $vouchers = array_filter($vouchers, function($v) use ($companyFilter) {
        return (string)$v['company_id'] === (string)$companyFilter;
    });
}

$batches = $batchModel->getAll();
$companies = $companyModel->getAll();

$title = 'Manage Vouchers';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="fas fa-ticket-alt"></i> Manage Vouchers</h1>
        <p>View and manage all vouchers</p>
    </div>
    <div class="page-actions">
        <a href="<?= htmlspecialchars(appUrl('/admin/import.php')) ?>" class="btn btn-primary"><i class="fas fa-file-upload"></i> Import CSV</a>
        <a href="<?= htmlspecialchars(appUrl('/admin/create-voucher.php')) ?>" class="btn btn-success"><i class="fas fa-plus-circle"></i> Create Voucher</a>
    </div>
</div>

<div class="filter-form">
    <form method="GET" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
        <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 250px;">
            <label><i class="fas fa-search"></i> Search</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Voucher No, Client Name, or EVA ID">
        </div>
        <div class="form-group" style="margin-bottom: 0; min-width: 200px;">
            <label><i class="fas fa-filter"></i> Status</label>
            <select name="status">
                <option value="">All Statuses</option>
                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="partially_used" <?= $statusFilter === 'partially_used' ? 'selected' : '' ?>>Partially Used</option>
                <option value="used" <?= $statusFilter === 'used' ? 'selected' : '' ?>>Used</option>
                <option value="blocked" <?= $statusFilter === 'blocked' ? 'selected' : '' ?>>Blocked</option>
                <option value="expired" <?= $statusFilter === 'expired' ? 'selected' : '' ?>>Expired</option>
                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-bottom: 0;"><i class="fas fa-search"></i> Filter</button>
        <?php if ($search || $statusFilter): ?>
        <a href="<?= htmlspecialchars(appUrl('/admin/vouchers.php')) ?>" class="btn btn-secondary" style="margin-bottom: 0;"><i class="fas fa-times"></i> Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <h2><i class="fas fa-list"></i> Vouchers (<?= count($vouchers) ?>)</h2>
    <?php if (count($vouchers) > 0): ?>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Voucher No</th>
                    <th>Client Name</th>
                    <th>EVA ID</th>
                    <th>Company</th>
                    <th>Original Amount</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vouchers as $voucher): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($voucher['voucher_no']) ?></strong></td>
                    <td><?= htmlspecialchars($voucher['client_name']) ?></td>
                    <td><?= htmlspecialchars($voucher['eva_id'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($voucher['company_name']) ?></td>
                    <td><?= formatMoney($voucher['original_amount']) ?></td>
                    <td><strong><?= formatMoney($voucher['balance']) ?></strong></td>
                    <td><span class="badge <?= statusBadgeClass($voucher['status']) ?>"><?= statusLabel($voucher['status']) ?></span></td>
                    <td>
                        <a href="<?= htmlspecialchars(appUrl('/admin/voucher-view.php?id=' . $voucher['id'])) ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> View</a>
                        <?php if ($voucher['qr_code_path']): ?>
                            <a href="<?= htmlspecialchars(appUrl('/admin/print-card.php?id=' . $voucher['id'])) ?>" class="btn btn-sm btn-secondary" target="_blank"><i class="fas fa-print"></i> Print</a>
                        <?php else: ?>
                            <a href="<?= htmlspecialchars(appUrl('/admin/generate-qr.php?id=' . $voucher['id'])) ?>" class="btn btn-sm btn-warning"><i class="fas fa-qrcode"></i> QR</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-ticket-alt"></i>
        <p>No vouchers found</p>
        <?php if ($search || $statusFilter): ?>
        <p style="font-size: 0.875rem; margin-top: 8px;">Try adjusting your filters</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../app/Views/layouts/main.php';
