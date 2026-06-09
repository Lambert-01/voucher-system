<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Helpers/money.php';
require_once __DIR__ . '/../../app/Helpers/qr.php';
require_once __DIR__ . '/../../app/Models/VoucherBatch.php';
require_once __DIR__ . '/../../app/Models/Voucher.php';

requireRole('admin');

$pdo = getDB();
$batchModel = new VoucherBatch($pdo);
$voucherModel = new Voucher($pdo);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $voucherNo = trim($_POST['voucher_no']);
        $clientName = trim($_POST['client_name']);
        $evaId = trim($_POST['eva_id'] ?? '');
        $amount = parseAmount($_POST['original_amount']);
        $batchId = $_POST['batch_id'];
        
        // Create voucher
        $voucherId = $voucherModel->create([
            'batch_id' => $batchId,
            'voucher_no' => $voucherNo,
            'client_name' => $clientName,
            'eva_id' => $evaId,
            'original_amount' => $amount,
            'balance' => $amount,
            'status' => 'active'
        ]);
        
        // Generate QR code
        $qrPath = __DIR__ . '/../assets/qrcodes/' . $voucherNo . '.png';
        generateSimpleQRCode($voucherNo, $qrPath);
        
        $pdo->prepare("UPDATE vouchers SET qr_code_path = ? WHERE id = ?")->execute([
            '/assets/qrcodes/' . $voucherNo . '.png',
            $voucherId
        ]);
        
        // Update batch totals
        $batchModel->updateTotals($batchId);
        
        $success = 'Voucher created successfully';
        logAudit($pdo, $_SESSION['user_id'], 'voucher_create', "Created voucher: $voucherNo");
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$batches = $batchModel->getAll();

$title = 'Create Voucher';
ob_start();
?>

<h1>Create Single Voucher</h1>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <h2>Voucher Details</h2>
    <form method="POST">
        <div class="form-group">
            <label>Batch *</label>
            <select name="batch_id" required>
                <option value="">Select Batch</option>
                <?php foreach ($batches as $batch): ?>
                    <option value="<?= $batch['id'] ?>">
                        <?= htmlspecialchars($batch['batch_code']) ?> - <?= htmlspecialchars($batch['batch_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Voucher Number *</label>
                <input type="text" name="voucher_no" required placeholder="e.g., HSV-2026-0001">
            </div>
            
            <div class="form-group">
                <label>Amount *</label>
                <input type="number" name="original_amount" step="0.01" required placeholder="436800">
            </div>
        </div>
        
        <div class="form-group">
            <label>Client Name *</label>
            <input type="text" name="client_name" required placeholder="Full name of beneficiary">
        </div>
        
        <div class="form-group">
            <label>EVA ID (Optional)</label>
            <input type="text" name="eva_id" placeholder="e.g., PX-Q3-2026-CB80">
        </div>
        
        <button type="submit" class="btn btn-primary">Create Voucher</button>
    </form>
</div>

<div class="actions">
    <a href="<?= htmlspecialchars(appUrl('/admin/vouchers.php')) ?>" class="btn btn-secondary">Back to Vouchers</a>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../app/Views/layouts/main.php';
