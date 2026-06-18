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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
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
            'total_vouchers' => 0,
            'total_amount' => 0,
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

        $data['batch_code'] = $batchModel->generateCode($company['company_name'], $data['batch_month']);
        $data['batch_name'] = $batchModel->generateName($company['company_name'], $data['batch_month']);

        $pdo->beginTransaction();
        $batchId = $batchModel->create($data);
        logAudit($pdo, $_SESSION['user_id'], 'batch_create', 'Created batch: ' . $data['batch_code']);
        $pdo->commit();

        showNotification('Batch created successfully: ' . $data['batch_code'], 'success');
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
