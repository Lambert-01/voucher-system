<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Helpers/ui.php';

requireRole(['boss', 'admin']);

$pdo = getDB();
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$userId = $_GET['user_id'] ?? '';
$action = $_GET['action'] ?? '';

$users = $pdo->query("SELECT id, full_name, username, role FROM users WHERE status = 'active' ORDER BY full_name")->fetchAll();

$where = ["DATE(al.created_at) BETWEEN ? AND ?"];
$params = [$startDate, $endDate];

if ($userId) {
    $where[] = "al.user_id = ?";
    $params[] = $userId;
}

if ($action) {
    $where[] = "al.action = ?";
    $params[] = $action;
}

$sql = "SELECT al.*, u.full_name, u.username, u.role
    FROM audit_logs al
    LEFT JOIN users u ON u.id = al.user_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY al.created_at DESC
    LIMIT 1000";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

if (($_GET['export'] ?? '') === 'csv') {
    logAudit($pdo, $_SESSION['user_id'], 'report_export', 'Exported audit log report from ' . $startDate . ' to ' . $endDate);
    sendCsvDownload('audit-logs-' . $startDate . '-' . $endDate . '.csv', [
        'Date/Time', 'User', 'Role', 'Action', 'Description', 'IP Address'
    ], array_map(function ($row) {
        return [
            $row['created_at'],
            $row['full_name'] ?? 'System',
            $row['role'] ?? '-',
            $row['action'],
            $row['description'] ?? '-',
            $row['ip_address'],
        ];
    }, $results));
}

$actions = $pdo->query("SELECT DISTINCT action FROM audit_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

$title = 'Audit Logs';
ob_start();
?>

<h1>System Audit Logs</h1>

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
                <label>User</label>
                <select name="user_id">
                    <option value="">All Users</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $userId == $u['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['full_name']) ?> (<?= $u['role'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Action</label>
                <select name="action">
                    <option value="">All Actions</option>
                    <?php foreach ($actions as $act): ?>
                        <option value="<?= $act ?>" <?= $action == $act ? 'selected' : '' ?>>
                            <?= htmlspecialchars($act) ?>
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
        <a href="<?= htmlspecialchars(appUrl('/boss/audit-logs.php?' . http_build_query(array_merge($_GET, ['export' => 'csv'])))) ?>" class="btn btn-success">Export CSV</a>
    </div>
    <table class="table" id="reportTable">
        <thead>
            <tr>
                <th>Date/Time</th>
                <th>User</th>
                <th>Role</th>
                <th>Action</th>
                <th>Description</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $row): ?>
            <tr>
                <td><?= $row['created_at'] ?></td>
                <td><?= htmlspecialchars($row['full_name'] ?? 'System') ?></td>
                <td><?= htmlspecialchars($row['role'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['action']) ?></td>
                <td><?= htmlspecialchars($row['description'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['ip_address']) ?></td>
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
    a.download = 'audit-logs-<?= $startDate ?>-<?= $endDate ?>.csv';
    a.click();
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../app/Views/layouts/main.php';
