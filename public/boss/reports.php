<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Helpers/money.php';
require_once __DIR__ . '/../../app/Helpers/ui.php';

requireRole('boss');

$pdo = getDB();

$month = $_GET['month'] ?? date('Y-m');
$companyId = $_GET['company_id'] ?? '';

// Get companies for filter
$companies = $pdo->query("SELECT * FROM companies WHERE status = 'active' ORDER BY company_name")->fetchAll();

// Build query
$where = ["vb.batch_month LIKE ?"];
$params = [$month . '%'];

if ($companyId) {
    $where[] = "c.id = ?";
    $params[] = $companyId;
}

$sql = "SELECT c.company_name, vb.batch_name, vb.batch_month,
    COUNT(v.id) as total_vouchers,
    SUM(v.original_amount) as total_issued,
    SUM(v.original_amount - v.balance) as total_used,
    SUM(v.balance) as total_remaining,
    COUNT(CASE WHEN v.status = 'active' THEN 1 END) as active_count,
    COUNT(CASE WHEN v.status = 'partially_used' THEN 1 END) as partial_count,
    COUNT(CASE WHEN v.status = 'used' THEN 1 END) as used_count
    FROM companies c
    JOIN voucher_batches vb ON vb.company_id = c.id
    JOIN vouchers v ON v.batch_id = vb.id
    WHERE " . implode(' AND ', $where) . "
    GROUP BY c.id, c.company_name, vb.id, vb.batch_name, vb.batch_month
    ORDER BY c.company_name, vb.batch_month DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

if (($_GET['export'] ?? '') === 'csv') {
    logAudit($pdo, $_SESSION['user_id'], 'report_export', 'Exported company monthly summary report for ' . $month);
    sendCsvDownload('company-summary-' . $month . '.csv', [
        'Company', 'Batch', 'Month', 'Total Vouchers', 'Total Issued', 'Total Used', 'Remaining', 'Active', 'Partial', 'Used'
    ], array_map(function ($row) {
        return [
            $row['company_name'],
            $row['batch_name'],
            $row['batch_month'],
            $row['total_vouchers'],
            $row['total_issued'],
            $row['total_used'],
            $row['total_remaining'],
            $row['active_count'],
            $row['partial_count'],
            $row['used_count'],
        ];
    }, $results));
}

$title = 'Monthly Reports';
ob_start();
?>

<h1><i class="fas fa-chart-bar"></i> Monthly Reports</h1>

<div class="card">
    <h2><i class="fas fa-file-invoice"></i> Available Reports</h2>
    <div class="quick-actions-grid">
        <a href="<?= appUrl('/boss/reports.php') ?>" class="btn btn-primary quick-action-btn">
            <i class="fas fa-building"></i><br>Company Summary
        </a>
        <a href="<?= appUrl('/boss/report-cashier.php') ?>" class="btn btn-info quick-action-btn">
            <i class="fas fa-user-tie"></i><br>Cashier Performance
        </a>
        <a href="<?= appUrl('/boss/report-vouchers.php') ?>" class="btn btn-success quick-action-btn">
            <i class="fas fa-ticket-alt"></i><br>Voucher Status
        </a>
        <a href="<?= appUrl('/boss/report-transactions.php') ?>" class="btn btn-secondary quick-action-btn">
            <i class="fas fa-exchange-alt"></i><br>Transaction Details
        </a>
        <a href="<?= appUrl('/boss/audit-logs.php') ?>" class="btn btn-info quick-action-btn">
            <i class="fas fa-history"></i><br>Audit Logs
        </a>
    </div>
</div>

<div class="card">
    <h2><i class="fas fa-filter"></i> Filter Company Summary</h2>
    <form method="GET" class="filter-form">
        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-calendar"></i> Month</label>
                <input type="month" name="month" value="<?= htmlspecialchars($month) ?>">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-building"></i> Company</label>
                <select name="company_id">
                    <option value="">All Companies</option>
                    <?php foreach ($companies as $comp): ?>
                        <option value="<?= $comp['id'] ?>" <?= $companyId == $comp['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($comp['company_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter Reports</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <h2><i class="fas fa-table"></i> Report Results</h2>
    <div class="report-actions">
        <button onclick="window.print()" class="btn btn-secondary"><i class="fas fa-print"></i> Print</button>
        <a href="<?= htmlspecialchars(appUrl('/boss/reports.php?' . http_build_query(array_merge($_GET, ['export' => 'csv'])))) ?>" class="btn btn-success"><i class="fas fa-file-csv"></i> Export CSV</a>
    </div>
    <table class="table" id="reportTable">
        <thead>
            <tr>
                <th>Company</th>
                <th>Batch</th>
                <th>Month</th>
                <th>Total Vouchers</th>
                <th>Total Issued</th>
                <th>Total Used</th>
                <th>Remaining</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['company_name']) ?></td>
                <td><?= htmlspecialchars($row['batch_name']) ?></td>
                <td><?= htmlspecialchars($row['batch_month']) ?></td>
                <td><?= $row['total_vouchers'] ?></td>
                <td><?= formatMoney($row['total_issued']) ?></td>
                <td><?= formatMoney($row['total_used']) ?></td>
                <td><?= formatMoney($row['total_remaining']) ?></td>
                <td>
                    Active: <?= $row['active_count'] ?><br>
                    Partial: <?= $row['partial_count'] ?><br>
                    Used: <?= $row['used_count'] ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function exportToCSV() {
    const table = document.getElementById('reportTable');
    let csv = [];
    for (let row of table.rows) {
        let cols = [];
        for (let cell of row.cells) {
            cols.push('"' + cell.innerText.replace(/"/g, '""') + '"');
        }
        csv.push(cols.join(','));
    }
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'monthly-report-<?= $month ?>.csv';
    a.click();
}
</script>

<div class="actions">
    <a href="<?= htmlspecialchars(appUrl('/boss/dashboard.php')) ?>" class="btn btn-secondary">Back to Dashboard</a>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../app/Views/layouts/main.php';
