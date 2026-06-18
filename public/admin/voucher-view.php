<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Helpers/money.php';
require_once __DIR__ . '/../../app/Models/Voucher.php';
require_once __DIR__ . '/../../app/Models/VoucherBatch.php';

requireRole(['admin', 'boss']);

$pdo = getDB();
$voucherModel = new Voucher($pdo);
$batchModel = new VoucherBatch($pdo);

$voucherId = $_GET['id'] ?? 0;
$voucher = $voucherModel->getById($voucherId);

if (!$voucher) {
    die('Voucher not found');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'admin') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'block') {
        $voucherModel->updateStatus($voucherId, 'blocked');
        $success = 'Voucher blocked successfully';
        logAudit($pdo, $_SESSION['user_id'], 'voucher_block', "Blocked voucher: " . $voucher['voucher_no']);
        $voucher['status'] = 'blocked';
    } elseif ($action === 'activate') {
        $newStatus = $voucher['balance'] == $voucher['original_amount'] ? 'active' : 'partially_used';
        $voucherModel->updateStatus($voucherId, $newStatus);
        $success = 'Voucher activated successfully';
        logAudit($pdo, $_SESSION['user_id'], 'voucher_activate', "Activated voucher: " . $voucher['voucher_no']);
        $voucher['status'] = $newStatus;
    }
}

$transactions = $voucherModel->getTransactions($voucherId);

$title = 'Voucher Details';
ob_start();
?>

<h1>Voucher Details</h1>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <h2><?= htmlspecialchars($voucher['voucher_no']) ?></h2>
    <table class="details-table">
        <tr>
            <th>Voucher Number:</th>
            <td><strong><?= htmlspecialchars($voucher['voucher_no']) ?></strong></td>
        </tr>
        <tr>
            <th>Client Name:</th>
            <td><?= htmlspecialchars($voucher['client_name']) ?></td>
        </tr>
        <tr>
            <th>EVA ID:</th>
            <td><?= htmlspecialchars($voucher['eva_id'] ?? 'N/A') ?></td>
        </tr>
        <tr>
            <th>Company:</th>
            <td><?= htmlspecialchars($voucher['company_name']) ?></td>
        </tr>
        <tr>
            <th>Batch:</th>
            <td><?= htmlspecialchars($voucher['batch_name']) ?></td>
        </tr>
        <tr>
            <th>Batch Period:</th>
            <td><?= htmlspecialchars($batchModel->periodLabel($voucher['batch_month'])) ?></td>
        </tr>
        <tr>
            <th>Original Amount:</th>
            <td><strong><?= formatMoney($voucher['original_amount']) ?></strong></td>
        </tr>
        <tr>
            <th>Current Balance:</th>
            <td><strong class="balance-amount"><?= formatMoney($voucher['balance']) ?></strong></td>
        </tr>
        <tr>
            <th>Amount Used:</th>
            <td><?= formatMoney($voucher['original_amount'] - $voucher['balance']) ?></td>
        </tr>
        <tr>
            <th>Status:</th>
            <td><span class="badge badge-<?= $voucher['status'] ?>"><?= strtoupper($voucher['status']) ?></span></td>
        </tr>
        <tr>
            <th>QR Code:</th>
            <td>
                <?php if ($voucher['qr_code_path']): ?>
                    <img src="<?= htmlspecialchars(appUrl($voucher['qr_code_path'])) ?>" width="150" height="150">
                <?php else: ?>
                    Not generated
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Created:</th>
            <td><?= date('Y-m-d H:i:s', strtotime($voucher['created_at'])) ?></td>
        </tr>
    </table>
    
    <?php if ($_SESSION['role'] === 'admin'): ?>
    <div class="actions">
        <?php if ($voucher['status'] === 'blocked'): ?>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="activate">
                <button type="submit" class="btn btn-success">Activate Voucher</button>
            </form>
        <?php elseif (in_array($voucher['status'], ['active', 'partially_used'])): ?>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="block">
                <button type="submit" class="btn btn-secondary">Block Voucher</button>
            </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Transaction History</h2>
    <?php if (count($transactions) > 0): ?>
    <table class="table">
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>Receipt No</th>
                <th>Previous Balance</th>
                <th>Amount Used</th>
                <th>New Balance</th>
                <th>Cashier</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $trans): ?>
            <tr>
                <td><?= date('Y-m-d H:i:s', strtotime($trans['created_at'])) ?></td>
                <td><?= htmlspecialchars($trans['receipt_no'] ?? '-') ?></td>
                <td><?= formatMoney($trans['previous_balance']) ?></td>
                <td><?= formatMoney($trans['amount_used']) ?></td>
                <td><?= formatMoney($trans['new_balance']) ?></td>
                <td><?= htmlspecialchars($trans['cashier_name']) ?></td>
                <td><?= htmlspecialchars($trans['notes'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>No transactions yet.</p>
    <?php endif; ?>
</div>

<div class="actions">
    <?php if ($_SESSION['role'] === 'admin'): ?>
        <a href="<?= htmlspecialchars(appUrl('/admin/vouchers.php')) ?>" class="btn btn-secondary">Back to Vouchers</a>
    <?php else: ?>
        <a href="<?= htmlspecialchars(appUrl('/boss/dashboard.php')) ?>" class="btn btn-secondary">Back to Dashboard</a>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../app/Views/layouts/main.php';
