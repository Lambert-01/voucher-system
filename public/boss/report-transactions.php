<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Helpers/money.php';
require_once __DIR__ . '/../../app/Helpers/ui.php';

requireRole('boss');

$pdo = getDB();
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$cashierId = $_GET['cashier_id'] ?? '';
$companyId = $_GET['company_id'] ?? '';

$cashiers = $pdo->query("SELECT id, full_name FROM users WHERE role = 'cashier' AND status = 'active' ORDER BY full_name")->fetchAll();
$companies = $pdo->query("SELECT * FROM companies WHERE status = 'active' ORDER BY company_name")->fetchAll();

$where = ["DATE(vt.created_at) BETWEEN ? AND ?"];
$params = [$startDate, $endDate];

if ($cashierId) {
    $where[] = "vt.cashier_id = ?";
    $params[] = $cashierId;
}

if ($companyId) {
    $where[] = "c.id = ?";
    $params[] = $companyId;
}

$sql = "SELECT vt.created_at, vt.receipt_no, v.voucher_no, v.client_name,
    c.company_name, u.full_name as cashier_name,
    vt.amount_used, vt.previous_balance, vt.new_balance, vt.notes
    FROM voucher_transactions vt
    JOIN vouchers v ON v.id = vt.voucher_id
    JOIN voucher_batches vb ON vb.id = v.batch_id
    JOIN companies c ON c.id = vb.company_id
    JOIN users u ON u.id = vt.cashier_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY vt.created_at DESC
    LIMIT 2000";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

if (($_GET['export'] ?? '') === 'csv') {
    logAudit($pdo, $_SESSION['user_id'], 'report_export', 'Exported transaction report from ' . $startDate . ' to ' . $endDate);
    sendCsvDownload('transaction-report-' . $startDate . '-' . $endDate . '.csv', [
        'Date', 'Receipt No', 'Voucher No', 'Client', 'Company', 'Cashier', 'Amount Used', 'Previous Balance', 'New Balance'
    ], array_map(function ($row) {
        return [
            $row['created_at'],
            $row['receipt_no'],
            $row['voucher_no'],
            $row['client_name'],
            $row['company_name'],
            $row['cashier_name'],
            $row['amount_used'],
            $row['previous_balance'],
            $row['new_balance'],
        ];
    }, $results));
}

$title = 'Transaction Report';
ob_start();
?>

<h1>Detailed Transaction Report</h1>

<div class="card">
    <form method="GET" class="filter-form">
        <div class="form-row">
            <div class="form-group">
                <label>Start Date</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
            </div>
            <div class="form-group">
                <label>End Date</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
            </div>
            <div class="form-group">
                <label>Cashier</label>
                <select name="cashier_id">
                    <option value="">All Cashiers</option>
                    <?php foreach ($cashiers as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $cashierId == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Company</label>
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
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div style="margin-bottom: 15px;">
        <button onclick="window.print()" class="btn btn-secondary">Print</button>
        <a href="<?= htmlspecialchars(appUrl('/boss/report-transactions.php?' . http_build_query(array_merge($_GET, ['export' => 'csv'])))) ?>" class="btn btn-success">Export CSV</a>
    </div>
    <table class="table" id="reportTable">
        <thead>
            <tr>
                <th>Date</th>
                <th>Receipt No</th>
                <th>Voucher No</th>
                <th>Client</th>
                <th>Company</th>
                <th>Cashier</th>
                <th>Amount Used</th>
                <th>Balance Before</th>
                <th>Balance After</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $row): ?>
            <tr>
                <td><?= $row['created_at'] ?></td>
                <td><?= htmlspecialchars($row['receipt_no']) ?></td>
                <td><?= htmlspecialchars($row['voucher_no']) ?></td>
                <td><?= htmlspecialchars($row['client_name']) ?></td>
                <td><?= htmlspecialchars($row['company_name']) ?></td>
                <td><?= htmlspecialchars($row['cashier_name']) ?></td>
                <td><?= formatMoney($row['amount_used']) ?></td>
                <td><?= formatMoney($row['previous_balance']) ?></td>
                <td><?= formatMoney($row['new_balance']) ?></td>
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
    a.download = 'transaction-report-<?= $startDate ?>-<?= $endDate ?>.csv';
    a.click();
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../app/Views/layouts/main.php';
