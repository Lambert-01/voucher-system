<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Helpers/money.php';
require_once __DIR__ . '/../../app/Helpers/ui.php';

requireRole('boss');

$pdo = getDB();
$status = $_GET['status'] ?? '';
$companyId = $_GET['company_id'] ?? '';

$companies = $pdo->query("SELECT * FROM companies WHERE status = 'active' ORDER BY company_name")->fetchAll();

$where = ["1=1"];
$params = [];

if ($status) {
    $where[] = "v.status = ?";
    $params[] = $status;
}

if ($companyId) {
    $where[] = "c.id = ?";
    $params[] = $companyId;
}

$sql = "SELECT v.voucher_no, v.client_name, v.eva_id, c.company_name, vb.batch_name,
    v.original_amount, v.balance, v.status, v.created_at
    FROM vouchers v
    JOIN voucher_batches vb ON vb.id = v.batch_id
    JOIN companies c ON c.id = vb.company_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY v.created_at DESC
    LIMIT 1000";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

if (($_GET['export'] ?? '') === 'csv') {
    logAudit($pdo, $_SESSION['user_id'], 'report_export', 'Exported voucher status report');
    sendCsvDownload('voucher-status-report.csv', [
        'Voucher No', 'Client Name', 'EVA ID', 'Company', 'Batch', 'Original', 'Balance', 'Status', 'Created'
    ], array_map(function ($row) {
        return [
            $row['voucher_no'],
            $row['client_name'],
            $row['eva_id'],
            $row['company_name'],
            $row['batch_name'],
            $row['original_amount'],
            $row['balance'],
            $row['status'],
            $row['created_at'],
        ];
    }, $results));
}

$statusOptions = ['active', 'partially_used', 'used', 'expired', 'cancelled', 'blocked'];

$title = 'Voucher Status Report';
ob_start();
?>

<h1>Voucher Status Report</h1>

<div class="card">
    <form method="GET" class="filter-form">
        <div class="form-row">
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="">All Status</option>
                    <?php foreach ($statusOptions as $s): ?>
                        <option value="<?= $s ?>" <?= $status == $s ? 'selected' : '' ?>>
                            <?= strtoupper($s) ?>
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
        <a href="<?= htmlspecialchars(appUrl('/boss/report-vouchers.php?' . http_build_query(array_merge($_GET, ['export' => 'csv'])))) ?>" class="btn btn-success">Export CSV</a>
    </div>
    <table class="table" id="reportTable">
        <thead>
            <tr>
                <th>Voucher No</th>
                <th>Client Name</th>
                <th>EVA ID</th>
                <th>Company</th>
                <th>Batch</th>
                <th>Original</th>
                <th>Balance</th>
                <th>Status</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['voucher_no']) ?></td>
                <td><?= htmlspecialchars($row['client_name']) ?></td>
                <td><?= htmlspecialchars($row['eva_id']) ?></td>
                <td><?= htmlspecialchars($row['company_name']) ?></td>
                <td><?= htmlspecialchars($row['batch_name']) ?></td>
                <td><?= formatMoney($row['original_amount']) ?></td>
                <td><?= formatMoney($row['balance']) ?></td>
                <td><span class="badge badge-<?= $row['status'] ?>"><?= strtoupper($row['status']) ?></span></td>
                <td><?= $row['created_at'] ?></td>
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
            let text = cell.innerText.replace(/\s+/g, ' ').trim();
            cols.push('"' + text.replace(/"/g, '""') + '"');
        }
        csv.push(cols.join(','));
    }
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'voucher-status-report.csv';
    a.click();
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../app/Views/layouts/main.php';
