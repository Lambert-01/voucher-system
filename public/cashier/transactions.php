<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Helpers/auth.php';
require_once __DIR__ . '/../../app/Helpers/money.php';

requireRole('cashier');

$pdo = getDB();

$transactions = $pdo->prepare("SELECT vt.*, v.voucher_no, v.client_name, c.company_name
    FROM voucher_transactions vt
    JOIN vouchers v ON v.id = vt.voucher_id
    JOIN voucher_batches vb ON vb.id = v.batch_id
    JOIN companies c ON c.id = vb.company_id
    WHERE vt.cashier_id = ?
    ORDER BY vt.created_at DESC
    LIMIT 50");
$transactions->execute([$_SESSION['user_id']]);
$results = $transactions->fetchAll();

$title = 'My Transactions';
ob_start();
?>

<h1>My Transactions</h1>

<div class="card">
    <h2>Recent Transactions</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>Voucher No</th>
                <th>Client Name</th>
                <th>Company</th>
                <th>Receipt No</th>
                <th>Previous Balance</th>
                <th>Amount Used</th>
                <th>New Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $trans): ?>
            <tr>
                <td><?= date('Y-m-d H:i:s', strtotime($trans['created_at'])) ?></td>
                <td><?= htmlspecialchars($trans['voucher_no']) ?></td>
                <td><?= htmlspecialchars($trans['client_name']) ?></td>
                <td><?= htmlspecialchars($trans['company_name']) ?></td>
                <td><?= htmlspecialchars($trans['receipt_no'] ?? '-') ?></td>
                <td><?= formatMoney($trans['previous_balance']) ?></td>
                <td><?= formatMoney($trans['amount_used']) ?></td>
                <td><?= formatMoney($trans['new_balance']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="actions">
    <a href="<?= htmlspecialchars(appUrl('/cashier/scan.php')) ?>" class="btn btn-primary">Scan New Voucher</a>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../app/Views/layouts/main.php';
