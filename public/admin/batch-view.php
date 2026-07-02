<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Helpers/money.php';
require_once __DIR__ . '/../../app/Models/VoucherBatch.php';
require_once __DIR__ . '/../../app/Models/Voucher.php';

requireRole('admin');

$pdo = getDB();
$batchModel = new VoucherBatch($pdo);
$voucherModel = new Voucher($pdo);

$batchId = $_GET['id'] ?? 0;
$batch = $batchModel->getById($batchId);

if (!$batch) {
    die('Batch not found');
}

$filters = [
    'q' => trim($_GET['q'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
    'cb_from' => trim($_GET['cb_from'] ?? ''),
    'cb_to' => trim($_GET['cb_to'] ?? ''),
    'created_from' => trim($_GET['created_from'] ?? ''),
    'created_to' => trim($_GET['created_to'] ?? ''),
];
$vouchers = $voucherModel->getByBatchFiltered($batchId, $filters);
$hasFilters = $filters['q'] !== '' || $filters['status'] !== '' || $filters['cb_from'] !== '' || $filters['cb_to'] !== '' || $filters['created_from'] !== '' || $filters['created_to'] !== '';
$printQuery = array_filter([
    'id' => $batch['id'],
    'q' => $filters['q'],
    'status' => $filters['status'],
    'cb_from' => $filters['cb_from'],
    'cb_to' => $filters['cb_to'],
    'created_from' => $filters['created_from'],
    'created_to' => $filters['created_to'],
], fn($value) => $value !== '' && $value !== null);

$title = 'Batch Details';
ob_start();
?>

<h1>Batch Details</h1>

<div class="card">
    <h2><?= htmlspecialchars($batch['batch_name']) ?></h2>
    <table class="details-table">
        <tr>
            <th>Batch Code:</th>
            <td><?= htmlspecialchars($batch['batch_code']) ?></td>
        </tr>
        <tr>
            <th>Company:</th>
            <td><?= htmlspecialchars($batch['company_name']) ?></td>
        </tr>
        <tr>
            <th>Period:</th>
            <td><?= htmlspecialchars($batchModel->periodLabel($batch['batch_month'])) ?></td>
        </tr>
        <tr>
            <th>Total Vouchers:</th>
            <td><?= $batch['total_vouchers'] ?></td>
        </tr>
        <tr>
            <th>Total Amount:</th>
            <td><?= formatMoney($batch['total_amount']) ?></td>
        </tr>
        <tr>
            <th>Payment Status:</th>
            <td><span class="badge badge-<?= $batch['payment_status'] ?>"><?= $batch['payment_status'] ?></span></td>
        </tr>
        <tr>
            <th>Payment Reference:</th>
            <td><?= htmlspecialchars($batch['payment_reference'] ?? 'N/A') ?></td>
        </tr>
        <tr>
            <th>Created:</th>
            <td><?= date('Y-m-d H:i:s', strtotime($batch['created_at'])) ?></td>
        </tr>
    </table>
</div>

<div class="card">
    <h2>Filter Vouchers for Card Printing</h2>
    <form method="GET" class="filter-form">
        <input type="hidden" name="id" value="<?= htmlspecialchars($batch['id']) ?>">
        <div class="form-row">
            <div class="form-group">
                <label>Search</label>
                <input type="text" name="q" value="<?= htmlspecialchars($filters['q']) ?>" placeholder="Voucher no, client name, EVA ID">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="">All Statuses</option>
                    <?php foreach (['active', 'partially_used', 'used', 'blocked', 'expired', 'cancelled'] as $status): ?>
                        <option value="<?= $status ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>CB From</label>
                <input type="number" name="cb_from" value="<?= htmlspecialchars($filters['cb_from']) ?>" placeholder="116" min="1">
            </div>
            <div class="form-group">
                <label>CB To</label>
                <input type="number" name="cb_to" value="<?= htmlspecialchars($filters['cb_to']) ?>" placeholder="125" min="1">
            </div>
            <div class="form-group">
                <label>Created From</label>
                <input type="datetime-local" name="created_from" value="<?= htmlspecialchars($filters['created_from']) ?>">
            </div>
            <div class="form-group">
                <label>Created To</label>
                <input type="datetime-local" name="created_to" value="<?= htmlspecialchars($filters['created_to']) ?>">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Apply Filter</button>
            </div>
        </div>
    </form>
    <div class="actions" style="margin-top: 0;">
        <a href="<?= htmlspecialchars(appUrl('/admin/batch-view.php?id=' . $batch['id'])) ?>" class="btn btn-secondary">Clear Filter</a>
        <a href="<?= htmlspecialchars(appUrl('/admin/print-batch.php?' . http_build_query($printQuery))) ?>" class="btn btn-success" target="_blank">
            Print <?= $hasFilters ? 'Filtered' : 'All' ?> Cards (<?= count($vouchers) ?>)
        </a>
    </div>
</div>

<div class="card">
    <h2>Vouchers in this Batch <?= $hasFilters ? '(' . count($vouchers) . ' filtered)' : '' ?></h2>
    <table class="table">
        <thead>
            <tr>
                <th>Voucher No</th>
                <th>Client Name</th>
                <th>EVA ID</th>
                <th>Original Amount</th>
                <th>Balance</th>
                <th>Status</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vouchers as $voucher): ?>
            <tr>
                <td><?= htmlspecialchars($voucher['voucher_no']) ?></td>
                <td><?= htmlspecialchars($voucher['client_name']) ?></td>
                <td><?= htmlspecialchars($voucher['eva_id'] ?? '-') ?></td>
                <td><?= formatMoney($voucher['original_amount']) ?></td>
                <td><?= formatMoney($voucher['balance']) ?></td>
                <td><span class="badge badge-<?= $voucher['status'] ?>"><?= $voucher['status'] ?></span></td>
                <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($voucher['created_at']))) ?></td>
                <td>
                    <a href="<?= htmlspecialchars(appUrl('/admin/voucher-view.php?id=' . $voucher['id'])) ?>" class="btn btn-sm">View</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="actions">
    <a href="<?= htmlspecialchars(appUrl('/admin/batches.php')) ?>" class="btn btn-secondary">Back to Batches</a>
    <a href="<?= htmlspecialchars(appUrl('/admin/print-batch.php?' . http_build_query($printQuery))) ?>" class="btn btn-primary" target="_blank">
        Print <?= $hasFilters ? 'Filtered' : 'All' ?> Cards (<?= count($vouchers) ?>)
    </a>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../app/Views/layouts/main.php';
