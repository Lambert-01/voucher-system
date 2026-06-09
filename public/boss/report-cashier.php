<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Helpers/money.php';
require_once __DIR__ . '/../../app/Helpers/ui.php';

requireRole('boss');

$pdo = getDB();
$month = $_GET['month'] ?? date('Y-m');
$cashierId = $_GET['cashier_id'] ?? '';

$cashiers = $pdo->query("SELECT id, full_name FROM users WHERE role = 'cashier' AND status = 'active' ORDER BY full_name")->fetchAll();

$where = ["DATE_FORMAT(vt.created_at, '%Y-%m') = ?"];
$params = [$month];

if ($cashierId) {
    $where[] = "vt.cashier_id = ?";
    $params[] = $cashierId;
}

$sql = "SELECT u.full_name as cashier_name,
    COUNT(vt.id) as total_transactions,
    SUM(vt.amount_used) as total_amount,
    MIN(vt.created_at) as first_transaction,
    MAX(vt.created_at) as last_transaction
    FROM voucher_transactions vt
    JOIN users u ON u.id = vt.cashier_id
    WHERE " . implode(' AND ', $where) . "
    GROUP BY vt.cashier_id, u.full_name
    ORDER BY total_amount DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

if (($_GET['export'] ?? '') === 'csv') {
    logAudit($pdo, $_SESSION['user_id'], 'report_export', 'Exported cashier performance report for ' . $month);
    sendCsvDownload('cashier-report-' . $month . '.csv', [
        'Cashier Name', 'Total Transactions', 'Total Amount', 'First Transaction', 'Last Transaction'
    ], array_map(function ($row) {
        return [
            $row['cashier_name'],
            $row['total_transactions'],
            $row['total_amount'],
            $row['first_transaction'],
            $row['last_transaction'],
        ];
    }, $results));
}

$title = 'Cashier Report';
ob_start();
?>

<h1>Cashier Performance Report</h1>

<div class="card">
    <form method="GET" class="filter-form">
        <div class="form-row">
            <div class="form-group">
                <label>Month</label>
                <input type="month" name="month" value="<?= htmlspecialchars($month) ?>">
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
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div style="margin-bottom: 15px;">
        <button onclick="window.print()" class="btn btn-secondary">Print</button>
        <a href="<?= htmlspecialchars(appUrl('/boss/report-cashier.php?' . http_build_query(array_merge($_GET, ['export' => 'csv'])))) ?>" class="btn btn-success">Export CSV</a>
    </div>
    <table class="table" id="reportTable">
        <thead>
            <tr>
                <th>Cashier Name</th>
                <th>Total Transactions</th>
                <th>Total Amount</th>
                <th>First Transaction</th>
                <th>Last Transaction</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['cashier_name']) ?></td>
                <td><?= $row['total_transactions'] ?></td>
                <td><?= formatMoney($row['total_amount']) ?></td>
                <td><?= $row['first_transaction'] ?></td>
                <td><?= $row['last_transaction'] ?></td>
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
    a.download = 'cashier-report-<?= $month ?>.csv';
    a.click();
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../app/Views/layouts/main.php';
