<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Helpers/money.php';
require_once __DIR__ . '/../../app/Helpers/qr.php';
require_once __DIR__ . '/../../app/Helpers/xlsx.php';
require_once __DIR__ . '/../../app/Models/VoucherBatch.php';
require_once __DIR__ . '/../../app/Models/Voucher.php';

requireRole('admin');

$pdo = getDB();
$batchModel = new VoucherBatch($pdo);
$voucherModel = new Voucher($pdo);

$success = '';
$error = '';
$imported = 0;
$skipped = 0;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    try {
        $batchId = $_POST['batch_id'];
        $batch = $batchModel->getById($batchId);
        
        if (!$batch) {
            throw new Exception('Batch not found');
        }
        
        $file = $_FILES['import_file']['tmp_name'];
        $originalName = $_FILES['import_file']['name'] ?? '';
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $rows = [];

        if ($extension === 'xlsx') {
            $rows = parseManualVoucherRegister($file);
        } else {
            $handle = fopen($file, 'r');
            fgetcsv($handle);

            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) < 3) continue;
                $amount = parseAmount($data[3] ?? '436800');
                $rows[] = [
                    'voucher_no' => trim($data[0]),
                    'client_name' => trim($data[1]),
                    'eva_id' => trim($data[2] ?? ''),
                    'original_amount' => $amount,
                    'balance' => $amount,
                    'status' => 'active',
                ];
            }

            fclose($handle);
        }

        foreach ($rows as $row) {
            $voucherNo = trim($row['voucher_no']);
            $clientName = trim($row['client_name']);
            $amount = (float)$row['original_amount'];
            $balance = (float)($row['balance'] ?: $amount);
            $status = strtolower(trim($row['status'] ?: 'active'));

            if ($voucherNo === '' || $clientName === '' || $amount <= 0) {
                $skipped++;
                $errors[] = "Skipped invalid row for voucher '$voucherNo'.";
                continue;
            }

            if (!in_array($status, ['active', 'partially_used', 'used', 'blocked', 'expired', 'cancelled'], true)) {
                $status = 'active';
            }

            if ($voucherModel->getByVoucherNo($voucherNo)) {
                $skipped++;
                $errors[] = "Skipped duplicate voucher: $voucherNo";
                continue;
            }

            $voucherId = $voucherModel->create([
                'batch_id' => $batchId,
                'voucher_no' => $voucherNo,
                'client_name' => $clientName,
                'eva_id' => $row['eva_id'] ?? '',
                'original_amount' => $amount,
                'balance' => $balance,
                'status' => $status
            ]);
            
            $qrPath = __DIR__ . '/../assets/qrcodes/' . $voucherNo . '.png';
            generateSimpleQRCode($voucherNo, $qrPath);
            
            $pdo->prepare("UPDATE vouchers SET qr_code_path = ? WHERE id = ?")->execute([
                '/assets/qrcodes/' . $voucherNo . '.png',
                $voucherId
            ]);
            
            $imported++;
        }

        $batchModel->updateTotals($batchId);
        
        $success = "Successfully imported $imported vouchers";
        if ($skipped > 0) {
            $success .= " ($skipped skipped)";
        }
        logAudit($pdo, $_SESSION['user_id'], 'voucher_import', "Imported $imported vouchers from $extension file to batch ID $batchId");
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$batches = $batchModel->getAll();

$title = 'Import Vouchers';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1>Import Vouchers</h1>
        <p>Move the manual Excel master register into the digital voucher system.</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="alert alert-warning">
        <div>
            <strong>Import notes</strong>
            <ul>
                <?php foreach (array_slice($errors, 0, 8) as $message): ?>
                    <li><?= htmlspecialchars($message) ?></li>
                <?php endforeach; ?>
                <?php if (count($errors) > 8): ?>
                    <li><?= count($errors) - 8 ?> more skipped rows...</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <h2>Supported Import Files</h2>
    <p><strong>Recommended:</strong> upload the manual workbook <code>N_Honest_Voucher_System_Split_Client_Fiches.xlsx</code>. The system reads the <strong>Master Register</strong> sheet and creates digital vouchers from it.</p>
    <p>You can also upload a CSV file with these columns:</p>
    <ol>
        <li>Voucher Number (e.g., HSV-2026-0001)</li>
        <li>Client Name (e.g., ALLELUIA ALAIN)</li>
        <li>EVA ID (optional, e.g., PX-Q3-2026-CB80)</li>
        <li>Amount (e.g., 436800)</li>
    </ol>
    <p><strong>Example CSV content:</strong></p>
    <pre>Voucher No,Client Name,EVA ID,Amount
HSV-2026-0001,ALLELUIA ALAIN,PX-Q3-2026-CB80,436800
HSV-2026-0002,BAHIGIRORA JEAN DE LA CROIX,PX-Q3-2026-CB70,436800</pre>
</div>

<div class="card">
    <h2>Upload Excel or CSV File</h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Select Batch *</label>
            <select name="batch_id" required>
                <option value="">Select Batch</option>
                <?php foreach ($batches as $batch): ?>
                    <option value="<?= $batch['id'] ?>">
                        <?= htmlspecialchars($batch['batch_code']) ?> - <?= htmlspecialchars($batch['batch_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Voucher File *</label>
            <input type="file" name="import_file" accept=".csv,.xlsx" required>
            <small>Use the existing manual Excel workbook or a simple CSV export.</small>
        </div>
        
        <button type="submit" class="btn btn-primary">Import Vouchers</button>
    </form>
</div>

<div class="actions">
    <a href="<?= htmlspecialchars(appUrl('/admin/vouchers.php')) ?>" class="btn btn-secondary">Back to Vouchers</a>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../app/Views/layouts/main.php';
