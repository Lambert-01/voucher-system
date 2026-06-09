<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Helpers/card.php';
require_once __DIR__ . '/../../app/Models/Voucher.php';
require_once __DIR__ . '/../../app/Models/VoucherBatch.php';

requireRole('admin');

$pdo = getDB();
$voucherModel = new Voucher($pdo);
$batchModel = new VoucherBatch($pdo);

// Handle bulk generation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_id'])) {
    $batchId = (int)$_POST['batch_id'];
    $batch = $batchModel->getById($batchId);
    
    if (!$batch) {
        $error = 'Batch not found';
    } else {
        // Get all vouchers in batch
        $vouchers = $voucherModel->getByBatch($batchId);
        
        if (empty($vouchers)) {
            $error = 'No vouchers found in this batch';
        } else {
            // Generate cards for all vouchers
            $results = bulkGenerateCards($vouchers);
            $success = count($results) . ' cards generated successfully for batch: ' . $batch['batch_name'];
        }
    }
}

// Get all batches for dropdown
$batches = $batchModel->getAll();

require_once __DIR__ . '/../../app/Views/layouts/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3">Generate Voucher Cards</h1>
            <p class="text-muted">Generate ATM-style cards for entire batches</p>
        </div>
    </div>

    <?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Select Batch</h5>
                    <form method="POST" id="generateForm">
                        <div class="mb-3">
                            <label for="batch_id" class="form-label">Voucher Batch</label>
                            <select class="form-select" id="batch_id" name="batch_id" required>
                                <option value="">Select a batch...</option>
                                <?php foreach ($batches as $batch): ?>
                                    <option value="<?= $batch['id'] ?>">
                                        <?= htmlspecialchars($batch['batch_name']) ?> 
                                        (<?= $batch['voucher_count'] ?> vouchers)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-credit-card"></i> Generate Cards
                        </button>
                        <a href="batches.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h5 class="card-title">About Card Generation</h5>
                    <p class="small">This tool generates printable ATM-style voucher cards using your template design.</p>
                    
                    <h6 class="mt-3">What it does:</h6>
                    <ul class="small">
                        <li>Uses side1.png and side2.png templates</li>
                        <li>Overlays voucher data (number, name, amount)</li>
                        <li>Adds QR code to back of card</li>
                        <li>Generates print-ready PNG files</li>
                    </ul>
                    
                    <h6 class="mt-3">Card includes:</h6>
                    <ul class="small">
                        <li>Voucher number</li>
                        <li>Client name</li>
                        <li>EVA ID</li>
                        <li>Original amount</li>
                        <li>QR code for scanning</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('generateForm').addEventListener('submit', function(e) {
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generating...';
});
</script>

<?php require_once __DIR__ . '/../../app/Views/layouts/footer.php'; ?>
