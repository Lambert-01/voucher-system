<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Helpers/money.php';
require_once __DIR__ . '/../../app/Models/Company.php';
require_once __DIR__ . '/../../app/Models/VoucherBatch.php';

requireRole('admin');

$pdo = getDB();
$companyModel = new Company($pdo);
$batchModel = new VoucherBatch($pdo);

$success = '';
$error = '';

function batchStartDate($periodValue) {
    if (preg_match('/^\d{4}-\d{2}$/', $periodValue)) {
        return $periodValue . '-01';
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodValue)) {
        return $periodValue;
    }

    if (preg_match('/^(\d{8})-(\d{8})$/', $periodValue, $matches)) {
        $start = DateTime::createFromFormat('Ymd', $matches[1]);
        return $start ? $start->format('Y-m-d') : date('Y-m-d');
    }

    if (preg_match('/^(\d{4}-\d{2}-\d{2})_to_(\d{4}-\d{2}-\d{2})$/', $periodValue, $matches)) {
        return $matches[1];
    }

    return date('Y-m-d');
}

function batchEndDate($periodValue) {
    if (preg_match('/^\d{4}-\d{2}$/', $periodValue)) {
        return date('Y-m-t', strtotime($periodValue . '-01'));
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodValue)) {
        return $periodValue;
    }

    if (preg_match('/^(\d{8})-(\d{8})$/', $periodValue, $matches)) {
        $end = DateTime::createFromFormat('Ymd', $matches[2]);
        return $end ? $end->format('Y-m-d') : date('Y-m-d');
    }

    if (preg_match('/^(\d{4}-\d{2}-\d{2})_to_(\d{4}-\d{2}-\d{2})$/', $periodValue, $matches)) {
        return $matches[2];
    }

    return date('Y-m-d');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? 'create';

        if ($action === 'delete') {
            $batchId = (int)($_POST['batch_id'] ?? 0);
            $batch = $batchModel->getById($batchId);
            if (!$batch) {
                throw new Exception('Batch not found.');
            }

            $voucherCount = $batchModel->voucherCount($batchId);
            if ($voucherCount > 0) {
                throw new Exception('This batch has ' . $voucherCount . ' vouchers. Move or delete vouchers first before deleting the batch.');
            }

            $batchModel->delete($batchId);
            logAudit($pdo, $_SESSION['user_id'], 'batch_delete', 'Deleted batch: ' . $batch['batch_code']);
            showNotification('Batch deleted successfully.', 'success');
            redirectTo('/admin/batches.php');
        }

        if (!in_array($action, ['create', 'edit'], true)) {
            throw new Exception('Invalid batch action.');
        }

        $batchId = (int)($_POST['batch_id'] ?? 0);
        $existingBatch = null;
        if ($action === 'edit') {
            $existingBatch = $batchModel->getById($batchId);
            if (!$existingBatch) {
                throw new Exception('Batch not found.');
            }
        }

        $data = [
            'company_id' => (int)($_POST['company_id'] ?? 0),
            'batch_month' => $batchModel->buildPeriodValue(
                $_POST['period_type'] ?? 'monthly',
                trim($_POST['start_date'] ?? ''),
                trim($_POST['end_date'] ?? '')
            ),
            'payment_status' => $_POST['payment_status'] ?? 'pending',
            'payment_reference' => trim($_POST['payment_reference'] ?? ''),
            'notes' => trim($_POST['notes'] ?? ''),
            'total_vouchers' => $existingBatch['total_vouchers'] ?? 0,
            'total_amount' => $existingBatch['total_amount'] ?? 0,
            'created_by' => $_SESSION['user_id']
        ];

        if ($data['company_id'] <= 0) {
            throw new Exception('Please select a company.');
        }

        if (!in_array($data['payment_status'], ['pending', 'paid', 'cancelled'], true)) {
            $data['payment_status'] = 'pending';
        }

        $company = $companyModel->getById($data['company_id']);
        if (!$company) {
            throw new Exception('Please select a valid company.');
        }

        if ($action === 'edit' && (int)$existingBatch['company_id'] === $data['company_id'] && $existingBatch['batch_month'] === $data['batch_month']) {
            $data['batch_code'] = $existingBatch['batch_code'];
        } else {
            $data['batch_code'] = $batchModel->generateCode($company['company_name'], $data['batch_month']);
        }
        $data['batch_name'] = $batchModel->generateName($company['company_name'], $data['batch_month']);

        $pdo->beginTransaction();
        if ($action === 'edit') {
            $batchModel->update($batchId, $data);
            logAudit($pdo, $_SESSION['user_id'], 'batch_update', 'Updated batch: ' . $data['batch_code']);
            $message = 'Batch updated successfully: ' . $data['batch_code'];
        } else {
            $batchId = $batchModel->create($data);
            logAudit($pdo, $_SESSION['user_id'], 'batch_create', 'Created batch: ' . $data['batch_code']);
            $message = 'Batch created successfully: ' . $data['batch_code'];
        }
        $pdo->commit();

        showNotification($message, 'success');
        redirectTo('/admin/batches.php');
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
}

$companies = $companyModel->getActive();
$batches = $batchModel->getAll();

$title = 'Manage Batches';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1>Manage Voucher Batches</h1>
        <p>Select company and batch period. Batch code and name are generated automatically.</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <h2>Create New Batch</h2>
    <form method="POST">
        <input type="hidden" name="action" value="create">
        <div class="form-row">
            <div class="form-group">
                <label>Company *</label>
                <select name="company_id" required>
                    <option value="">Select Company</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?= $company['id'] ?>"><?= htmlspecialchars($company['company_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Batch Period *</label>
                <select name="period_type" id="period_type" required>
                    <option value="weekly">Weekly</option>
                    <option value="daily">Daily</option>
                    <option value="monthly">Monthly</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Starting Date *</label>
                <input type="date" name="start_date" id="start_date" required value="<?= date('Y-m-d') ?>">
                <small>For weekly and monthly batches, the system uses this date to calculate the period automatically.</small>
            </div>
            
            <div class="form-group">
                <label>Ending Date</label>
                <input type="date" name="end_date" id="end_date">
                <small id="end_date_hint">Auto-calculated for weekly and monthly periods. Enable Custom Range to choose it yourself.</small>
            </div>
        </div>

        <div class="alert alert-info">
            <div>
                <strong>Auto-generated:</strong> the system will create the batch code and name after you submit.
                Examples: <code>IOM-2026-06-18</code> or <code>IOM-2026-06-18-TO-2026-06-24</code>.
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Payment Status</label>
                <select name="payment_status">
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Payment Reference</label>
                <input type="text" name="payment_reference" placeholder="Invoice or receipt number">
            </div>
        </div>
        
        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" rows="3"></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary">Create Batch</button>
    </form>
</div>

<div class="card">
    <h2>Existing Batches</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Batch Code</th>
                <th>Batch Name</th>
                <th>Company</th>
                <th>Period</th>
                <th>Vouchers</th>
                <th>Total Amount</th>
                <th>Payment</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($batches as $batch): ?>
            <tr>
                <td><?= htmlspecialchars($batch['batch_code']) ?></td>
                <td><?= htmlspecialchars($batch['batch_name']) ?></td>
                <td><?= htmlspecialchars($batch['company_name']) ?></td>
                <td><?= htmlspecialchars($batchModel->periodLabel($batch['batch_month'])) ?></td>
                <td><?= $batch['total_vouchers'] ?></td>
                <td><?= formatMoney($batch['total_amount']) ?></td>
                <td><span class="badge badge-<?= $batch['payment_status'] ?>"><?= $batch['payment_status'] ?></span></td>
                <td>
                    <a href="<?= htmlspecialchars(appUrl('/admin/batch-view.php?id=' . $batch['id'])) ?>" class="btn btn-sm">View</a>
                    <button type="button" class="btn btn-sm btn-secondary" data-edit-batch="<?= $batch['id'] ?>">Edit</button>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this batch? Empty batches only can be deleted.');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="batch_id" value="<?= $batch['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <tr id="edit-batch-<?= $batch['id'] ?>" class="batch-edit-row" style="display: none;">
                <td colspan="8">
                    <form method="POST" class="batch-edit-form">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="batch_id" value="<?= $batch['id'] ?>">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Company *</label>
                                <select name="company_id" required>
                                    <?php foreach ($companies as $company): ?>
                                        <option value="<?= $company['id'] ?>" <?= (int)$batch['company_id'] === (int)$company['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($company['company_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Batch Period *</label>
                                <select name="period_type" class="edit-period-type" required>
                                    <option value="daily">Daily</option>
                                    <option value="weekly" selected>Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="custom">Custom Range</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Starting Date *</label>
                                <input type="date" name="start_date" class="edit-start-date" required value="<?= htmlspecialchars(batchStartDate($batch['batch_month'])) ?>">
                            </div>
                            <div class="form-group">
                                <label>Ending Date</label>
                                <input type="date" name="end_date" class="edit-end-date" value="<?= htmlspecialchars(batchEndDate($batch['batch_month'])) ?>">
                                <small>Edit uses the selected period to regenerate the batch code and name.</small>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Payment Status</label>
                                <select name="payment_status">
                                    <option value="pending" <?= $batch['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="paid" <?= $batch['payment_status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                                    <option value="cancelled" <?= $batch['payment_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Payment Reference</label>
                                <input type="text" name="payment_reference" value="<?= htmlspecialchars($batch['payment_reference'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" rows="2"><?= htmlspecialchars($batch['notes'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                        <button type="button" class="btn btn-secondary btn-sm" data-edit-cancel="<?= $batch['id'] ?>">Cancel</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="actions">
    <a href="<?= htmlspecialchars(appUrl('/admin/dashboard.php')) ?>" class="btn btn-secondary">Back to Dashboard</a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const periodType = document.getElementById('period_type');
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const endDateHint = document.getElementById('end_date_hint');

    function formatDate(date) {
        return date.toISOString().slice(0, 10);
    }

    function updateEndDate() {
        if (!periodType || !startDate || !endDate || !startDate.value) {
            return;
        }

        const start = new Date(startDate.value + 'T00:00:00');
        const end = new Date(start);
        endDate.readOnly = periodType.value !== 'custom';

        if (periodType.value === 'daily') {
            endDate.value = formatDate(end);
            endDateHint.textContent = 'Daily batches use the same start and ending date.';
            return;
        }

        if (periodType.value === 'weekly') {
            end.setDate(start.getDate() + 6);
            endDate.value = formatDate(end);
            endDateHint.textContent = 'Weekly batches automatically cover 7 days from the starting date.';
            return;
        }

        if (periodType.value === 'monthly') {
            end.setMonth(start.getMonth() + 1, 0);
            endDate.value = formatDate(end);
            endDateHint.textContent = 'Monthly batches automatically end on the last day of the selected month.';
            return;
        }

        endDate.readOnly = false;
        if (!endDate.value) {
            endDate.value = formatDate(end);
        }
        endDateHint.textContent = 'Custom range lets you choose any ending date after the starting date.';
    }

    periodType.addEventListener('change', updateEndDate);
    startDate.addEventListener('change', updateEndDate);
    updateEndDate();
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../app/Views/layouts/main.php';
