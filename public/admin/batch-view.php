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

$vouchers = $voucherModel->getByBatch($batchId);

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
            <th>Month:</th>
            <td><?= htmlspecialchars($batch['batch_month']) ?></td>
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
    <h2>Vouchers in this Batch</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Voucher No</th>
                <th>Client Name</th>
                <th>EVA ID</th>
                <th>Original Amount</th>
                <th>Balance</th>
                <th>Status</th>
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
    <a href="<?= htmlspecialchars(appUrl('/admin/print-batch.php?id=' . $batch['id'])) ?>" class="btn btn-primary" target="_blank">Print All Cards</a>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../app/Views/layouts/main.php';
